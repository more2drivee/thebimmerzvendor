<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --scope=products|variations|all limits which merge to run
     * --dry-run outputs planned changes only
     */
    protected $signature = 'data:merge-duplicates {--scope=all} {--mode=strict} {--auto} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge duplicate products (by SKU) and variations (by product_id+name+sub_sku) and retarget related rows safely';

    public function handle(): int
    {
        $scope = $this->option('scope');
        $dryRun = (bool) $this->option('dry-run');

        if (!in_array($scope, ['products', 'variations', 'all'], true)) {
            $this->error('Invalid --scope. Use products, variations, or all.');
            return self::FAILURE;
        }

        if ($this->option('auto')) {
            $this->info('Running full auto-fix pipeline: products -> variations (aggressive) -> variations (strict cleanup)');
            $this->runAutoFix((bool)$this->option('dry-run'));
            $this->info('Done.');
            return self::SUCCESS;
        }

        if ($scope === 'products' || $scope === 'all') {
            $this->info('Analyzing duplicate products by SKU...');
            $this->mergeDuplicateProductsBySku($dryRun);
        }

        if ($scope === 'variations' || $scope === 'all') {
            $mode = $this->option('mode');
            if (!in_array($mode, ['strict', 'aggressive'], true)) {
                $this->error('Invalid --mode. Use strict or aggressive.');
                return self::FAILURE;
            }

            if ($mode === 'strict') {
                $this->info('Analyzing duplicate variations per product by (name, sub_sku) [strict mode] ...');
                $this->mergeDuplicateVariations($dryRun);
            } else {
                $this->info('Aggressive mode: collapsing multiple variations per product into a single canonical variation...');
                $this->collapseVariationsAggressive($dryRun);
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function mergeDuplicateProductsBySku(bool $dryRun): void
    {
        // Build mapping: for each non-null/non-empty SKU with duplicates, keep the smallest product id
        $duplicates = DB::table('products as p')
            ->selectRaw('MIN(p.id) as keep_id, GROUP_CONCAT(p.id ORDER BY p.id) as all_ids, p.sku, COUNT(*) as cnt')
            ->whereNotNull('p.sku')
            ->where('p.sku', '<>', '')
            ->groupBy('p.sku')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate products found.');
            return;
        }

        $this->table(['SKU', 'Keep ID', 'All IDs', 'Count'], $duplicates->map(fn($r) => [
            $r->sku, $r->keep_id, $r->all_ids, $r->cnt,
        ]));

        if ($dryRun) {
            $this->comment('Dry-run: no changes applied for product merge.');
            return;
        }

        DB::transaction(function () use ($duplicates) {
            foreach ($duplicates as $dup) {
                $ids = collect(explode(',', $dup->all_ids))->map(fn($v) => (int)$v)->unique()->values();
                $keepId = (int) $dup->keep_id;
                $removeIds = $ids->reject(fn($id) => $id === $keepId)->values();
                if ($removeIds->isEmpty()) {
                    continue;
                }

                // Re-point foreign keys to keep product id
                DB::table('variations')->whereIn('product_id', $removeIds)->update(['product_id' => $keepId]);
                DB::table('product_variations')->whereIn('product_id', $removeIds)->update(['product_id' => $keepId]);
                DB::table('purchase_lines')->whereIn('product_id', $removeIds)->update(['product_id' => $keepId]);
                DB::table('transaction_sell_lines')->whereIn('product_id', $removeIds)->update(['product_id' => $keepId]);
                DB::table('variation_location_details')->whereIn('product_id', $removeIds)->update(['product_id' => $keepId]);

                // Optional: product_locations, racks, etc. Only if they exist.
                if (DB::getSchemaBuilder()->hasTable('product_locations')) {
                    DB::table('product_locations')->whereIn('product_id', $removeIds)->update(['product_id' => $keepId]);
                }
                if (DB::getSchemaBuilder()->hasTable('product_racks')) {
                    DB::table('product_racks')->whereIn('product_id', $removeIds)->update(['product_id' => $keepId]);
                }

                // Finally, delete duplicate product records
                DB::table('products')->whereIn('id', $removeIds)->delete();
            }
        });

        $this->info('Product merge completed.');
    }

    private function mergeDuplicateVariations(bool $dryRun): void
    {
        // Create a temporary mapping of duplicates by (product_id, name, sub_sku)
        // Keep the smallest variation id
        DB::statement('DROP TEMPORARY TABLE IF EXISTS duplicate_variation_mapping');
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE duplicate_variation_mapping AS
            SELECT 
                MIN(id) AS keep_variation_id,
                GROUP_CONCAT(id ORDER BY id) AS all_variation_ids,
                product_id,
                name,
                sub_sku,
                COUNT(*) AS duplicate_count
            FROM variations
            WHERE name IS NOT NULL AND sub_sku IS NOT NULL
            GROUP BY product_id, name, sub_sku
            HAVING COUNT(*) > 1
        SQL);

        $rows = DB::table('duplicate_variation_mapping')->count();
        if ($rows === 0) {
            $this->info('No duplicate variations found.');
            return;
        }

        $preview = DB::table('duplicate_variation_mapping as dvm')
            ->join('products as p', 'p.id', '=', 'dvm.product_id')
            ->select('dvm.keep_variation_id', 'dvm.all_variation_ids', 'dvm.product_id', 'p.name as product_name', 'dvm.name as variation_name', 'dvm.sub_sku', 'dvm.duplicate_count')
            ->get();
        $this->table(['Keep Var ID', 'All Var IDs', 'Product ID', 'Product', 'Var Name', 'Sub SKU', 'Dup Cnt'], $preview->map(function ($r) {
            return [
                $r->keep_variation_id,
                $r->all_variation_ids,
                $r->product_id,
                $r->product_name,
                $r->variation_name,
                $r->sub_sku,
                $r->duplicate_count,
            ];
        }));

        if ($dryRun) {
            $this->comment('Dry-run: no changes applied for variation merge.');
            return;
        }

        DB::transaction(function () {
            // Update references to point to keep variation id
            DB::statement(<<<SQL
                UPDATE purchase_lines pl
                JOIN variations v ON v.id = pl.variation_id
                JOIN duplicate_variation_mapping dvm
                    ON dvm.product_id = v.product_id AND dvm.name = v.name AND dvm.sub_sku = v.sub_sku
                SET pl.variation_id = dvm.keep_variation_id
                WHERE pl.variation_id <> dvm.keep_variation_id
            SQL);

            DB::statement(<<<SQL
                UPDATE transaction_sell_lines sl
                JOIN variations v ON v.id = sl.variation_id
                JOIN duplicate_variation_mapping dvm
                    ON dvm.product_id = v.product_id AND dvm.name = v.name AND dvm.sub_sku = v.sub_sku
                SET sl.variation_id = dvm.keep_variation_id
                WHERE sl.variation_id <> dvm.keep_variation_id
            SQL);

            // Variation location details: first repoint, then consolidate per (keep_variation_id, location_id)
            DB::statement(<<<SQL
                UPDATE variation_location_details vld
                JOIN variations v ON v.id = vld.variation_id
                JOIN duplicate_variation_mapping dvm
                    ON dvm.product_id = v.product_id AND dvm.name = v.name AND dvm.sub_sku = v.sub_sku
                SET vld.variation_id = dvm.keep_variation_id,
                    vld.product_id = v.product_id
                WHERE vld.variation_id <> dvm.keep_variation_id
            SQL);

            // Optional tables: stock_adjustment_lines if present
            if (DB::getSchemaBuilder()->hasTable('stock_adjustment_lines')) {
                DB::statement(<<<SQL
                    UPDATE stock_adjustment_lines al
                    JOIN variations v ON v.id = al.variation_id
                    JOIN duplicate_variation_mapping dvm
                        ON dvm.product_id = v.product_id AND dvm.name = v.name AND dvm.sub_sku = v.sub_sku
                    SET al.variation_id = dvm.keep_variation_id
                    WHERE al.variation_id <> dvm.keep_variation_id
                SQL);
            }

            // Consolidate qty_available per (keep_variation_id, location_id)
            // Create a helper temp table for sums
            DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_vld_sums');
            DB::statement(<<<SQL
                CREATE TEMPORARY TABLE tmp_vld_sums AS
                SELECT variation_id, location_id, SUM(qty_available) AS sum_qty
                FROM variation_location_details
                GROUP BY variation_id, location_id
            SQL);

            DB::statement(<<<SQL
                UPDATE variation_location_details vld
                JOIN tmp_vld_sums s ON s.variation_id = vld.variation_id AND s.location_id = vld.location_id
                SET vld.qty_available = s.sum_qty
            SQL);

            // Remove duplicates within variation_location_details keeping the smallest id per (variation_id, location_id)
            DB::statement(<<<SQL
                DELETE v1 FROM variation_location_details v1
                JOIN variation_location_details v2
                  ON v1.variation_id = v2.variation_id
                 AND v1.location_id = v2.location_id
                 AND v1.id > v2.id
            SQL);

            // Finally delete duplicate variation rows (except keep)
            DB::statement(<<<SQL
                DELETE v FROM variations v
                JOIN duplicate_variation_mapping dvm
                  ON dvm.product_id = v.product_id AND dvm.name = v.name AND dvm.sub_sku = v.sub_sku
                WHERE v.id <> dvm.keep_variation_id
            SQL);
        });

        $this->info('Variation merge completed.');
    }

    private function collapseVariationsAggressive(bool $dryRun): void
    {
        // Build candidate products with more than one variation
        $products = DB::table('products as p')
            ->join('variations as v', 'v.product_id', '=', 'p.id')
            ->select('p.id as product_id', 'p.name as product_name', 'p.sku as product_sku', DB::raw('COUNT(v.id) as variation_count'))
            ->groupBy('p.id', 'p.name', 'p.sku')
            ->having('variation_count', '>', 1)
            ->get();

        if ($products->isEmpty()) {
            $this->info('No products with multiple variations found for aggressive collapsing.');
            return;
        }

        $previewRows = [];
        $actions = [];

        foreach ($products as $p) {
            $vars = DB::table('variations')->where('product_id', $p->product_id)->select('id', 'name', 'sub_sku')->get();
            if ($vars->count() < 2) {
                continue;
            }

            // Choose canonical variation with priority:
            // 1) sub_sku equals product.sku (exact match)
            // 2) most references across purchase_lines, transaction_sell_lines, variation_location_details
            // 3) smallest id
            $keepId = null;
            $bySku = $vars->firstWhere('sub_sku', $p->product_sku);
            if ($bySku) {
                $keepId = $bySku->id;
            } else {
                // Compute reference counts
                $refCounts = DB::table('variations as v')
                    ->leftJoin('purchase_lines as pl', 'pl.variation_id', '=', 'v.id')
                    ->leftJoin('transaction_sell_lines as sl', 'sl.variation_id', '=', 'v.id')
                    ->leftJoin('variation_location_details as vld', 'vld.variation_id', '=', 'v.id')
                    ->where('v.product_id', $p->product_id)
                    ->groupBy('v.id')
                    ->select('v.id', DB::raw('COUNT(DISTINCT pl.id) + COUNT(DISTINCT sl.id) + COUNT(DISTINCT vld.id) as refs'))
                    ->get()
                    ->keyBy('id');
                $keepId = $vars->pluck('id')->sort(function ($a, $b) use ($refCounts) {
                    $ra = $refCounts[$a]->refs ?? 0; $rb = $refCounts[$b]->refs ?? 0;
                    if ($ra === $rb) { return $a <=> $b; }
                    return $rb <=> $ra; // desc
                })->first();
            }

            $allIds = $vars->pluck('id')->all();
            $removeIds = collect($allIds)->reject(fn($id) => $id === $keepId)->values();

            $previewRows[] = [
                $p->product_id,
                $p->product_name,
                $p->product_sku,
                $keepId,
                implode(',', $removeIds->all()),
            ];

            $actions[] = [
                'product_id' => (int)$p->product_id,
                'keep_id' => (int)$keepId,
                'remove_ids' => $removeIds->map(fn($i)=>(int)$i)->all(),
            ];
        }

        if (empty($actions)) {
            $this->info('Nothing to collapse.');
            return;
        }

        $this->table(['Product ID', 'Product', 'Product SKU', 'Keep Variation ID', 'Remove Variation IDs'], $previewRows);

        if ($dryRun) {
            $this->comment('Dry-run: no changes applied for aggressive collapsing.');
            return;
        }

        DB::transaction(function () use ($actions) {
            foreach ($actions as $act) {
                if (empty($act['remove_ids'])) { continue; }
                $keepId = $act['keep_id'];
                $productId = $act['product_id'];

                // Repoint references to keepId
                DB::table('purchase_lines')->whereIn('variation_id', $act['remove_ids'])->update(['variation_id' => $keepId, 'product_id' => $productId]);
                DB::table('transaction_sell_lines')->whereIn('variation_id', $act['remove_ids'])->update(['variation_id' => $keepId, 'product_id' => $productId]);
                DB::table('variation_location_details')->whereIn('variation_id', $act['remove_ids'])->update(['variation_id' => $keepId, 'product_id' => $productId]);
                if (DB::getSchemaBuilder()->hasTable('stock_adjustment_lines')) {
                    DB::table('stock_adjustment_lines')->whereIn('variation_id', $act['remove_ids'])->update(['variation_id' => $keepId]);
                }

                // Consolidate stock per location
                DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_sum');
                $idsList = implode(',', $act['remove_ids']);
                $createTmp = "CREATE TEMPORARY TABLE tmp_sum AS\n"
                    . "SELECT variation_id, location_id, SUM(qty_available) as sum_qty\n"
                    . "FROM variation_location_details\n"
                    . "WHERE variation_id = {$keepId} OR variation_id IN ({$idsList})\n"
                    . "GROUP BY variation_id, location_id";
                DB::statement($createTmp);

                $updateKeep = "UPDATE variation_location_details vld\n"
                    . "JOIN (\n"
                    . "    SELECT location_id, SUM(sum_qty) as total_qty\n"
                    . "    FROM tmp_sum\n"
                    . "    GROUP BY location_id\n"
                    . ") s ON s.location_id = vld.location_id\n"
                    . "SET vld.qty_available = s.total_qty\n"
                    . "WHERE vld.variation_id = {$keepId}";
                DB::statement($updateKeep);
                // Remove now-duplicate rows for keep variation per location
                DB::statement(<<<SQL
                    DELETE v1 FROM variation_location_details v1
                    JOIN variation_location_details v2
                      ON v1.variation_id = v2.variation_id
                     AND v1.location_id = v2.location_id
                     AND v1.id > v2.id
                    WHERE v1.variation_id = {$keepId}
                SQL);

                // Delete removed variations
                DB::table('variations')->whereIn('id', $act['remove_ids'])->delete();
            }
        });

        $this->info('Aggressive variation collapsing completed.');
    }

    private function runAutoFix(bool $dryRun): void
    {
        // 1) Merge duplicate products by SKU
        $this->info('Step 1/3: Merging duplicate products by SKU');
        $this->mergeDuplicateProductsBySku($dryRun);

        // 2) Collapse all multi-variation products to single canonical variation
        $this->info('Step 2/3: Collapsing variations aggressively');
        $this->collapseVariationsAggressive($dryRun);

        // 3) Cleanup any remaining exact duplicate variation keys (name+sub_sku)
        $this->info('Step 3/3: Cleaning remaining exact duplicate variations (strict)');
        $this->mergeDuplicateVariations($dryRun);
    }
}
