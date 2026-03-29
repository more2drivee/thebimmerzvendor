<?php

namespace App\Console\Commands;

use App\Brands;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\Product;
use App\ProductCompatibility;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionPayment;
use App\Unit;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Repair\Entities\DeviceModel;

class ImportInventoryFromExcel extends Command
{
    protected $signature = 'inventory:import-excel {file=corrected_inventory_fixed.xlsx} {--business-id=1} {--location-id=1}';

    protected $description = 'Import products from Excel file with opening stock and compatibility';

    protected $productUtil;
    protected $transactionUtil;
    protected $businessId = 1;
    protected $locationId = 1;
    protected $supplierId = null;
    protected $userId = 1;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        parent::__construct();
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function handle()
    {
        try {
            $this->businessId = $this->option('business-id') ?? 1;
            $this->locationId = $this->option('location-id') ?? 1;
            $file = $this->argument('file');

            // Get a valid user ID from the business
            $user = \App\User::where('business_id', $this->businessId)->first();
            if ($user) {
                $this->userId = $user->id;
            }

            $this->info("Starting inventory import from: {$file}");
            $this->info("Business ID: {$this->businessId}, Location ID: {$this->locationId}");

            // Check file exists
            if (!file_exists($file)) {
                $this->error("File not found: {$file}");
                return 1;
            }

            // Get or create default supplier
            $this->supplierId = $this->getOrCreateDefaultSupplier();
            $this->info("Using Supplier ID: {$this->supplierId}");

            // Read Excel file
            $data = Excel::toArray([], $file);
            if (empty($data) || empty($data[0])) {
                $this->error("No data found in Excel file");
                return 1;
            }

            $rows = $data[0];
            // Skip header row
            array_shift($rows);

            $this->info("Found " . count($rows) . " rows to process");

            // First pass: accumulate quantities for duplicate SKUs and track names without SKUs
            $skuAccumulator = [];
            $nameAccumulator = []; // For rows without SKUs
            foreach ($rows as $index => $row) {
                $name = trim($row[0] ?? '');
                $qty = (int) ($row[1] ?? 0);
                $brand = trim($row[2] ?? '');
                $sku = trim($row[3] ?? '');
                $carBrand = trim($row[4] ?? '');
                $carModel = trim($row[5] ?? '');

                if (empty($name)) continue;

                if (!empty($sku)) {
                    // Accumulate by SKU
                    if (!isset($skuAccumulator[$sku])) {
                        $skuAccumulator[$sku] = [
                            'name' => $name,
                            'brand' => $brand,
                            'qty' => $qty,
                            'car_brand' => $carBrand,
                            'car_model' => $carModel,
                            'count' => 1
                        ];
                    } else {
                        $skuAccumulator[$sku]['qty'] += $qty;
                        $skuAccumulator[$sku]['count']++;
                    }
                } else {
                    // Accumulate by name for rows without SKU
                    $nameKey = $name . '|' . $brand; // Include brand to distinguish same name different brand
                    if (!isset($nameAccumulator[$nameKey])) {
                        $nameAccumulator[$nameKey] = [
                            'name' => $name,
                            'brand' => $brand,
                            'qty' => $qty,
                            'car_brand' => $carBrand,
                            'car_model' => $carModel,
                            'count' => 1
                        ];
                    } else {
                        $nameAccumulator[$nameKey]['qty'] += $qty;
                        $nameAccumulator[$nameKey]['count']++;
                    }
                }
            }

            // Report duplicates
            $skuDuplicates = array_filter($skuAccumulator, function($item) {
                return $item['count'] > 1;
            });
            $nameDuplicates = array_filter($nameAccumulator, function($item) {
                return $item['count'] > 1;
            });

            if (!empty($skuDuplicates)) {
                $this->info("\nFound " . count($skuDuplicates) . " SKUs with duplicates:");
                foreach ($skuDuplicates as $sku => $data) {
                    $this->line("  SKU: {$sku} - Found {$data['count']} times, Total Qty: {$data['qty']}");
                }
            }

            if (!empty($nameDuplicates)) {
                $this->info("\nFound " . count($nameDuplicates) . " names without SKUs with duplicates:");
                foreach ($nameDuplicates as $nameKey => $data) {
                    $this->line("  Name: '{$data['name']}' (Brand: {$data['brand']}) - Found {$data['count']} times, Total Qty: {$data['qty']}");
                }
            }

            if (!empty($skuDuplicates) || !empty($nameDuplicates)) {
                $this->info("");
            }

            DB::beginTransaction();

            $productsCreated = 0;
            $stockUpdated = 0;
            $compatibilityAdded = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    $rowNum = $index + 2; // +2 because we skipped header and 0-indexed

                    // Extract data from row
                    $name = trim($row[0] ?? '');
                    $qty = (int) ($row[1] ?? 0);
                    $brand = trim($row[2] ?? '');
                    $sku = trim($row[3] ?? '');
                    $carBrand = trim($row[4] ?? '');
                    $carModel = trim($row[5] ?? '');

                    // Skip empty rows
                    if (empty($name)) {
                        continue;
                    }

                    // Process using accumulated data
                    if (!empty($sku)) {
                        if (!isset($skuAccumulator[$sku])) {
                            continue; // Skip if not in accumulator (shouldn't happen)
                        }
                        // Use accumulated data
                        $accData = $skuAccumulator[$sku];
                        $product = $this->createOrFindProduct($accData['name'], $accData['brand'], $sku);
                        $qty = $accData['qty'];
                        $carBrand = $accData['car_brand'];
                        $carModel = $accData['car_model'];
                        
                        // Mark as processed
                        unset($skuAccumulator[$sku]);
                    } else {
                        // Empty SKU - check name accumulator
                        $nameKey = $name . '|' . $brand;
                        if (!isset($nameAccumulator[$nameKey])) {
                            continue; // Skip if not in accumulator (shouldn't happen)
                        }
                        // Use accumulated data
                        $accData = $nameAccumulator[$nameKey];
                        $product = $this->createNewProduct($accData['name'], $accData['brand']);
                        $qty = $accData['qty'];
                        $carBrand = $accData['car_brand'];
                        $carModel = $accData['car_model'];
                        
                        // Mark as processed
                        unset($nameAccumulator[$nameKey]);
                    }
                    if (!$product) {
                        $errors[] = "Row {$rowNum}: Failed to create/find product '{$name}'";
                        continue;
                    }

                    $isExistingProduct = $product->wasRecentlyCreated === false;
                    
                    if (!$isExistingProduct) {
                        $productsCreated++;
                        // Log new product creation for debugging
                        $this->line("Created new product: '{$name}' (SKU: '{$sku}')");
                    } else {
                        // Log existing product found
                        $this->line("Found existing product: '{$name}' (ID: {$product->id})");
                    }

                    // Assign product to location (required for products to show in UI)
                    $this->assignProductToLocation($product);

                    // Get or create variation (single variation per product)
                    $variation = $this->getOrCreateVariation($product);
                    if (!$variation) {
                        $errors[] = "Row {$rowNum}: Failed to get/create variation for product '{$name}'";
                        continue;
                    }

                    // Update opening stock (qty as opening stock)
                    if ($qty > 0) {
                        if ($isExistingProduct) {
                            // For existing products, just update the opening stock
                            $this->updateExistingProductStock($product, $variation, $qty);
                        } else {
                            // For new products, create opening stock transaction
                            $this->createOpeningStockTransaction($product, $variation, $qty);
                        }
                        $stockUpdated++;
                    }

                    // Add product compatibility if car_brand and car_model are provided
                    if (!empty($carBrand) && !empty($carModel)) {
                        if ($this->addProductCompatibility($product, $carBrand, $carModel)) {
                            $compatibilityAdded++;
                        }
                    }

                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNum}: " . $e->getMessage();
                    continue;
                }
            }

            DB::commit();

            // Summary
            $this->info("\n" . str_repeat("=", 60));
            $this->info("Import completed successfully!");
            $this->info("New products created: {$productsCreated}");
            $this->info("Existing products updated: " . ($stockUpdated - $productsCreated));
            $this->info("Total products processed: {$stockUpdated}");
            $this->info("Compatibility records added: {$compatibilityAdded}");

            if (!empty($errors)) {
                $this->warn("\nWarnings/Errors encountered:");
                foreach ($errors as $error) {
                    $this->warn("  - {$error}");
                }
            }

            $this->info(str_repeat("=", 60));

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Get or create default supplier
     */
    private function getOrCreateDefaultSupplier()
    {
        $supplier = Contact::where('business_id', $this->businessId)
            ->where('type', 'supplier')
            ->where('name', 'Default Supplier')
            ->first();

        if (!$supplier) {
            $supplier = Contact::create([
                'business_id' => $this->businessId,
                'name' => 'Default Supplier',
                'type' => 'supplier',
                'contact_type' => 'individual',
                'mobile' => '0000000000', // Unique mobile to avoid duplicate constraint
                'created_by' => $this->userId,
            ]);
            $this->info("Created default supplier: {$supplier->id}");
        }

        return $supplier->id;
    }

    /**
     * Create new product (without checking for existing)
     */
    private function createNewProduct($name, $brand)
    {
        $brandId = null;
        if (!empty($brand)) {
            $brandObj = Brands::firstOrCreate(
                ['business_id' => $this->businessId, 'name' => $brand],
                ['created_by' => $this->userId]
            );
            $brandId = $brandObj->id;
        }

        // Get default unit (usually 'Piece')
        $unit = Unit::where('business_id', $this->businessId)
            ->where('short_name', 'Pc')
            ->first();
        if (!$unit) {
            $unit = Unit::where('business_id', $this->businessId)->first();
        }

        $productData = [
            'business_id' => $this->businessId,
            'name' => $name,
            'type' => 'single',
            'enable_stock' => 1,
            'created_by' => $this->userId,
            'brand_id' => $brandId,
            'unit_id' => $unit ? $unit->id : null,
        ];

        $product = Product::create($productData);

        // Auto-generate SKU
        $product->sku = $this->productUtil->generateProductSku($product->id);
        $product->save();

        return $product;
    }

    /**
     * Create or find product
     */
    private function createOrFindProduct($name, $brand, $sku)
    {
        // Try to find by SKU first
        if (!empty($sku)) {
            $product = Product::where('business_id', $this->businessId)
                ->where('sku', $sku)
                ->first();
            if ($product) {
                return $product;
            }
        }

        // Try to find by name
        $product = Product::where('business_id', $this->businessId)
            ->where('name', $name)
            ->first();
        if ($product) {
            return $product;
        }

        // Create new product
        $brandId = null;
        if (!empty($brand)) {
            $brandObj = Brands::firstOrCreate(
                ['business_id' => $this->businessId, 'name' => $brand],
                ['created_by' => $this->userId]
            );
            $brandId = $brandObj->id;
        }

        // Get default unit (usually 'Piece')
        $unit = Unit::where('business_id', $this->businessId)
            ->where('short_name', 'Pc')
            ->first();
        if (!$unit) {
            $unit = Unit::where('business_id', $this->businessId)->first();
        }

        $productData = [
            'business_id' => $this->businessId,
            'name' => $name,
            'type' => 'single',
            'enable_stock' => 1,
            'created_by' => $this->userId,
            'brand_id' => $brandId,
            'unit_id' => $unit ? $unit->id : null,
        ];

        if (!empty($sku)) {
            $productData['sku'] = $sku;
        }

        $product = Product::create($productData);

        // Auto-generate SKU if not provided
        if (empty($sku)) {
            $product->sku = $this->productUtil->generateProductSku($product->id);
            $product->save();
        }

        return $product;
    }

    /**
     * Get or create single variation for product
     */
    private function getOrCreateVariation($product)
    {
        // Check if product already has a variation
        $variation = Variation::where('product_id', $product->id)->first();

        if ($variation) {
            return $variation;
        }

        // Create single product variation with 0 prices
        $this->productUtil->createSingleProductVariation(
            $product,
            $product->sku,
            0, // purchase_price
            0, // dpp_inc_tax
            0, // profit_percent
            0, // selling_price
            0  // selling_price_inc_tax
        );

        // Return the newly created variation
        return Variation::where('product_id', $product->id)->first();
    }

    /**
     * Assign product to location (required for products to show in UI)
     */
    private function assignProductToLocation($product)
    {
        // Check if product is already assigned to this location
        $exists = DB::table('product_locations')
            ->where('product_id', $product->id)
            ->where('location_id', $this->locationId)
            ->exists();

        if (!$exists) {
            DB::table('product_locations')->insert([
                'product_id' => $product->id,
                'location_id' => $this->locationId,
            ]);
        }
    }

    /**
     * Update existing product stock (add to current stock via opening stock transaction)
     */
    private function updateExistingProductStock($product, $variation, $qty)
    {
        // For existing products, also create an opening stock transaction to maintain history
        $this->createOpeningStockTransaction($product, $variation, $qty);
    }

    /**
     * Create opening stock transaction
     */
    private function createOpeningStockTransaction($product, $variation, $qty)
    {
        // Create opening stock transaction
        $transaction = Transaction::create([
            'business_id' => $this->businessId,
            'location_id' => $this->locationId,
            'type' => 'opening_stock',
            'status' => 'final',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'total_before_tax' => 0,
            'final_total' => 0,
            'ref_number' => 'OS-' . $product->id . '-' . Str::random(5),
            'created_by' => $this->userId,
            'payment_status' => 'paid',
            'additional_notes' => "Opening stock for {$product->name} from Excel import"
        ]);

        // Create purchase line for opening stock (this will be counted in stock history)
        PurchaseLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => $qty,
            'purchase_price' => 0,
            'purchase_price_inc_tax' => 0,
            'item_tax' => 0,
            'tax_id' => null,
            'lot_number' => null,
        ]);

        // Update stock quantity - opening stock transactions with status 'final' should increase stock
        if ($transaction->status == 'final') {
            $this->productUtil->updateProductQuantity(
                $this->locationId,
                $product->id,
                $variation->id,
                $qty,
                0,
                null,
                false // already in database format
            );
        }

        // Create payment record
        TransactionPayment::create([
            'transaction_id' => $transaction->id,
            'amount' => 0,
            'method' => 'cash',
            'paid_on' => now()->format('Y-m-d'),
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Add product compatibility
     */
    private function addProductCompatibility($product, $carBrand, $carModel)
    {
        try {
            // Find brand category (device type)
            $brandCategory = Category::where('name', $carBrand)
                ->where('category_type', 'device')
                ->first();

            if (!$brandCategory) {
                // Brand category doesn't exist, skip
                return false;
            }

            // Find device model
            $model = DeviceModel::where('name', $carModel)
                ->where('device_id', $brandCategory->id)
                ->first();

            if (!$model) {
                // Try without brand constraint
                $model = DeviceModel::where('name', $carModel)->first();
            }

            if (!$model) {
                // Model doesn't exist, skip
                return false;
            }

            // Check if compatibility already exists
            $exists = ProductCompatibility::where('product_id', $product->id)
                ->where('model_id', $model->id)
                ->where('brand_category_id', $brandCategory->id)
                ->exists();

            if ($exists) {
                return false;
            }

            // Create compatibility record
            ProductCompatibility::create([
                'product_id' => $product->id,
                'model_id' => $model->id,
                'brand_category_id' => $brandCategory->id,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->warn("Failed to add compatibility for {$product->name} ({$carBrand} {$carModel}): " . $e->getMessage());
            return false;
        }
    }
}
