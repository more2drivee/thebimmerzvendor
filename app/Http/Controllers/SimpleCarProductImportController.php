<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use App\Brands;
use App\Unit;
use App\Variation;
use App\Transaction;
use App\ProductCompatibility;
use App\BusinessLocation;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
use Excel;
use DB;
use Modules\Repair\Entities\DeviceModel;

class SimpleCarProductImportController extends Controller
{
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('simple_car_product_import.index');
    }

    public function store(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');

        $file = $request->file('excel');
        
        // Use PhpSpreadsheet to get calculated values
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        $rows = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 0; $col < 7; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col + 1, $row)->getCalculatedValue();
                $rowData[] = $cellValue;
            }
            $rows[] = $rowData;
        }

        DB::beginTransaction();

        try {

            // Default unit
            $unit = Unit::firstOrCreate(
                ['business_id' => $business_id, 'actual_name' => 'قطعة'],
                ['short_name' => 'قطعة', 'created_by' => $user_id]
            );

            $location = BusinessLocation::where('business_id', $business_id)->first();

            foreach (array_slice($rows, 1) as $row_index => $row) {

                $name        = trim($row[0]);
                $qty_raw    = $row[1];
                $sku         = trim($row[6]);

                if ($name === '') {
                    continue;
                }

                $qty = is_numeric($qty_raw) ? (int) $qty_raw : 0;
                $cost = 0;

                if ($sku === '') {
                    $sku = $this->generateSku($business_id, $name);
                }

                $unique_sku = $this->getUniqueSku($business_id, $sku);

                $brand_id = $this->getOrCreateBrand($business_id, $user_id);

                if (empty($brand_id) || $brand_id == 0) {
                    continue;
                }

                $this->createProductWithOpeningStock($business_id, $user_id, $unit, $location, $name, $unique_sku, $brand_id, $qty, $cost);
            }

            DB::commit();
            return back()->with('status', ['success' => 1, 'msg' => 'تم الاستيراد بنجاح']);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }
    }

    private function __addProductLocation($product, $location_id)
    {
        $count = DB::table('product_locations')->where('product_id', $product->id)
                                            ->where('location_id', $location_id)
                                            ->count();
        
        if ($count == 0) {
            DB::table('product_locations')->insert(['product_id' => $product->id,
                'location_id' => $location_id, ]);
        }
    }

    private function createProductWithOpeningStock($business_id, $user_id, $unit, $location, $name, $sku, $brand_id, $qty, $cost)
    {
        $next_id = DB::select("SELECT COALESCE(MAX(id) + 1, 1) as next_id FROM products")[0]->next_id;

        $product = Product::create([
            'id' => $next_id,
            'business_id' => $business_id,
            'created_by' => $user_id,
            'name' => $name,
            'sku' => $sku,
            'type' => 'single',
            'unit_id' => $unit->id,
            'enable_stock' => 1,
            'brand_id' => $brand_id
        ]);

        $this->productUtil->createSingleProductVariation(
            $product,
            $product->sku,
            $cost,
            $cost,
            0,
            0,
            0
        );

        $variation = Variation::where('product_id', $product->id)->first();

        if (!$variation) {
            return;
        }

        $this->__addProductLocation($product, $location->id);

        if ($qty > 0) {
            $transaction = Transaction::create([
                'business_id' => $business_id,
                'type' => 'opening_stock',
                'opening_stock_product_id' => $product->id,
                'status' => 'received',
                'location_id' => $location->id,
                'final_total' => $qty * $cost,
                'created_by' => $user_id
            ]);

            $transaction->purchase_lines()->create([
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => $qty,
                'purchase_price' => $cost,
                'purchase_price_inc_tax' => $cost
            ]);

            $this->productUtil->updateProductQuantity(
                $location->id,
                $product->id,
                $variation->id,
                $qty,
                0,
                null,
                false
            );
        }
    }

    private function getUniqueSku($business_id, $sku)
    {
        $unique_sku = $sku;
        $counter = 1;

        while (Product::where('business_id', $business_id)->where('sku', $unique_sku)->exists()) {
            $unique_sku = $sku . '-c' . $counter;
            $counter++;
        }

        return $unique_sku;
    }

    private function generateSku($business_id, $name)
    {
        $base_sku = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        $base_sku = strtoupper(substr($base_sku, 0, 20));

        $sku = $base_sku;
        $counter = 1;

        while (Product::where('business_id', $business_id)->where('sku', $sku)->exists()) {
            $sku = $base_sku . '-' . $counter;
            $counter++;
        }

        return $sku;
    }

    private function getOrCreateBrand($business_id, $user_id)
    {
        $brand = Brands::where('business_id', $business_id)->first();

        if (!$brand) {
            $next_id = DB::select("SELECT COALESCE(MAX(id) + 1, 1) as next_id FROM brands")[0]->next_id;

            $brand = Brands::create([
                'id' => $next_id,
                'business_id' => $business_id,
                'name' => 'عام',
                'description' => 'Default brand',
                'created_by' => $user_id
            ]);
        }

        return $brand->id;
    }
}
