<?php

namespace App\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\Product;
use App\ProductCompatibility;
use App\TaxRate;
use App\Transaction;
use App\Unit;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use Carbon\Carbon;
use DB;
use Excel;
use Illuminate\Http\Request;
use Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Modules\Repair\Entities\DeviceModel;

class UniversalProductImportController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $businessUtil;

    protected $transactionUtil;

    /**
     * Constructor
     */
    public function __construct(
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil
    ) {
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function index()
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $import_fields = $this->importFields();

        return view('import_products.universal')->with(compact('business_locations', 'import_fields'));
    }

    public function downloadTemplate()
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $headers = [
            'Product Name',
            'SKU',
            'Unit',
            'Manage Stock (1/0)',
            'Category',
            'Sub Category',
            'Brand',
            'Tax Name',
            'Tax Amount',
            'Selling Price',
            'Purchase Price',
            'Opening Stock Qty',
            'Opening Stock Unit Cost',
            'Opening Stock Location',
            'Supplier',
            'Purchase Qty',
            'Purchase Location',
            'Vehicle Brand',
            'Vehicle Model',
            'From Year',
            'To Year',
            'Motor CC',
            'Model Year Range',
        ];

        $example = [
            'Brake Pad Front',
            'BP-001',
            'pcs',
            '1',
            'Spare Parts',
            'Brake System',
            'Honda',
            null,
            null,
            '120',
            '80',
            '50',
            '80',
            'Main Warehouse',
            null,
            '20',
            'Main Warehouse',
            'Honda',
            'CBR',
            '2018',
            '2022',
            '150',
            'CBR 2018-2022; CB 2019-2021',
        ];

        return Excel::download(
            new class($headers, $example) implements FromArray {
                private $data;

                public function __construct($headers, $example)
                {
                    $this->data = [$headers, $example];
                }

                public function array(): array
                {
                    return $this->data;
                }
            },
            'universal_product_import_template.xlsx'
        );
    }

    public function preview(Request $request)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $notAllowed = $this->businessUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            if ($request->hasFile('products_excel')) {
                $file_name = time().'_'.$request->products_excel->getClientOriginalName();
                $request->products_excel->storeAs('temp', $file_name);

                $parsed_array = $this->parseDataPreview($file_name);

                if (empty($parsed_array) || count($parsed_array) < 2) {
                    return redirect()->back()->with('notification', ['msg' => 'File is empty or invalid']);
                }

                $import_fields = $this->importFields();
                foreach ($import_fields as $key => $value) {
                    $import_fields[$key] = $value['label'];
                }

                $headers = $parsed_array[0];
                $match_array = [];
                foreach ($headers as $key => $value) {
                    $match_percentage = [];
                    foreach ($import_fields as $k => $v) {
                        similar_text($value, $v, $percentage);
                        $match_percentage[$k] = $percentage;
                    }
                    $max_key = array_keys($match_percentage, max($match_percentage))[0];
                    $match_array[$key] = $match_percentage[$max_key] >= 50 ? $max_key : null;
                }

                $business_id = request()->session()->get('user.business_id');
                $business_locations = BusinessLocation::forDropdown($business_id, true);

                $settings = $this->collectSettings($request);

                return view('import_products.universal_preview')->with(compact('parsed_array', 'import_fields', 'file_name', 'match_array', 'business_locations', 'settings'));
            }
        } catch (\Exception $e) {
            Log::error('Preview error: ' . $e->getMessage());
            return redirect()->back()->with('notification', ['msg' => 'Error processing file: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '256M');
            ini_set('post_max_size', '500M');
            ini_set('upload_max_filesize', '500M');
            ini_set('max_input_time', 0);

            $file_name = $request->input('file_name');
            $import_fields = $request->input('import_fields', []);
            $settings = $this->collectSettings($request);

            $file_path = public_path('uploads/temp/'.$file_name);

            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $default_unit = $this->getOrCreateUnit($settings['default_unit'], $business_id, $user_id);

            $errors = [];
            $created_count = 0;
            $chunk_size = 50;

            Log::info("Starting import from file: {$file_name}");

            $cache = [
                'skus' => Product::where('business_id', $business_id)->pluck('id', 'sku')->toArray(),
                'categories' => [],
                'brands' => [],
                'units' => [$default_unit->id => $default_unit],
                'locations' => BusinessLocation::where('business_id', $business_id)->pluck('id', 'name')->toArray(),
                'suppliers' => Contact::where('business_id', $business_id)->where('type', 'supplier')->pluck('id', 'name')->toArray(),
                'taxes' => [],
            ];

            $parsed_array = $this->parseDataStreaming($file_name);
            unset($parsed_array[0]);

            foreach (array_chunk($parsed_array, $chunk_size) as $chunk) {
                DB::beginTransaction();

                try {
                    foreach ($chunk as $row) {
                        $sku = $this->getMappedValue($row, $import_fields, 'sku');
                        $name = $this->getMappedValue($row, $import_fields, 'product_name');

                        $can_update_by_sku = false;
                        if (! empty($sku)) {
                            $sku_trimmed = trim($sku);
                            if (isset($cache['skus'][$sku_trimmed])) {
                                $can_update_by_sku = true;
                            }
                        }

                        if (empty($name) && ! $can_update_by_sku) {
                            continue;
                        }

                        $unit_name = $this->getMappedValue($row, $import_fields, 'unit');
                        $category_name = $this->getMappedValue($row, $import_fields, 'category');
                        $sub_category_name = $this->getMappedValue($row, $import_fields, 'sub_category');
                        $brand_name = $this->getMappedValue($row, $import_fields, 'brand');

                        $manage_stock_value = $this->getMappedValue($row, $import_fields, 'manage_stock');
                        $enable_stock = $this->resolveManageStock($manage_stock_value, $settings['default_manage_stock']);

                        $unit = $default_unit;
                        if (! empty($unit_name)) {
                            $unit = $this->getOrCreateUnitCached($unit_name, $business_id, $user_id, $cache);
                        }

                        $category = null;
                        $sub_category = null;

                        if (! empty($category_name)) {
                            $category = $this->getOrCreateCategoryCached($category_name, $business_id, $user_id, 'product', null, $cache);
                        }

                        if (! empty($sub_category_name)) {
                            $parent_id = $category ? $category->id : null;
                            $sub_category = $this->getOrCreateCategoryCached($sub_category_name, $business_id, $user_id, 'product', $parent_id, $cache);
                        }

                        $brand = null;
                        if (! empty($brand_name)) {
                            $brand = $this->getOrCreateBrandCached($brand_name, $business_id, $user_id, $cache);
                        }

                        $tax_name = $this->getMappedValue($row, $import_fields, 'tax_name');
                        $tax_amount = $this->getMappedValue($row, $import_fields, 'tax_amount');
                        $tax = $this->resolveTaxCached($tax_name, $tax_amount, $settings['default_tax_amount'], $business_id, $user_id, $errors, $cache);

                        $purchase_price = $this->getNumericValue($this->getMappedValue($row, $import_fields, 'purchase_price'));
                        $selling_price = $this->getNumericValue($this->getMappedValue($row, $import_fields, 'selling_price'));

                        $product_data = [
                            'business_id' => $business_id,
                            'created_by' => $user_id,
                            'name' => $name,
                            'type' => 'single',
                            'enable_stock' => $enable_stock,
                            'unit_id' => $unit->id,
                            'sku' => ! empty($sku) ? trim($sku) : ' ',
                            'tax' => null,
                            'tax_type' => 'exclusive',
                        ];

                        if (! empty($category)) {
                            $product_data['category_id'] = $category->id;
                        }
                        if (! empty($sub_category)) {
                            $product_data['sub_category_id'] = $sub_category->id;
                        }
                        if (! empty($brand)) {
                            $product_data['brand_id'] = $brand->id;
                        }

                        $is_update = false;
                        $product = null;
                        if (! empty($sku)) {
                            $sku_trimmed = trim($sku);
                            if (isset($cache['skus'][$sku_trimmed])) {
                                if ((int) $settings['update_existing'] === 0) {
                                    continue;
                                }
                                $product = Product::find($cache['skus'][$sku_trimmed]);
                                $is_update = true;
                            }
                        }

                        if (! $is_update) {
                            $product = Product::create($product_data);
                            if ($product->sku == ' ') {
                                $product->sku = $this->productUtil->generateProductSku($product->id);
                                $product->save();
                            }
                            $cache['skus'][$product->sku] = $product->id;

                            $location_name = $this->getMappedValue($row, $import_fields, 'opening_stock_location');
                            $location_id = null;
                            if (! empty($location_name) && isset($cache['locations'][$location_name])) {
                                $location_id = $cache['locations'][$location_name];
                            } else {
                                $location = BusinessLocation::where('business_id', $business_id)->first();
                                $location_id = $location ? $location->id : null;
                            }

                            if ($location_id) {
                                DB::table('product_locations')->insert([
                                    'product_id' => $product->id,
                                    'location_id' => $location_id,
                                ]);
                            }
                        } else {
                            $update_data = array_filter($product_data, function ($value, $key) {
                                if (in_array($key, ['business_id', 'sku'])) {
                                    return false;
                                }
                                if ($key === 'name' && empty($value)) {
                                    return false;
                                }
                                return true;
                            }, ARRAY_FILTER_USE_BOTH);
                            if (! empty($update_data)) {
                                $product->update($update_data);
                            }
                        }

                        $item_tax = 0;
                        if (! empty($tax)) {
                            $item_tax = $this->productUtil->calc_percentage($purchase_price, $tax->amount);
                        }

                        $purchase_price_inc_tax = $purchase_price + $item_tax;
                        $selling_price_inc_tax = $selling_price + ($tax ? $this->productUtil->calc_percentage($selling_price, $tax->amount) : 0);

                        $variation = Variation::where('product_id', $product->id)->first();
                        if (! $variation) {
                            $this->productUtil->createSingleProductVariation(
                                $product,
                                $product->sku,
                                $purchase_price,
                                $purchase_price_inc_tax,
                                0,
                                $selling_price,
                                $selling_price_inc_tax,
                                []
                            );
                        } else {
                            $update_data = [];
                            if ($purchase_price > 0) {
                                $update_data['default_purchase_price'] = $purchase_price;
                                $update_data['dpp_inc_tax'] = $purchase_price_inc_tax;
                            }
                            if ($selling_price > 0) {
                                $update_data['default_sell_price'] = $selling_price;
                                $update_data['sell_price_inc_tax'] = $selling_price_inc_tax;
                            }

                            if (! empty($update_data)) {
                                Log::info("Updating variation prices", [
                                    'variation_id' => $variation->id,
                                    'update_data' => $update_data,
                                ]);
                                $variation->update($update_data);
                            }
                        }

                        $this->handleCompatibility($row, $import_fields, $business_id, $user_id, $product);

                        if ($settings['create_opening_stock'] == 1 && $enable_stock == 1) {
                            $opening_stock_qty = $this->getNumericValue($this->getMappedValue($row, $import_fields, 'opening_stock_qty'));
                            if ($opening_stock_qty > 0) {
                                $location_name = $this->getMappedValue($row, $import_fields, 'opening_stock_location');
                                $location_id = null;
                                if (! empty($location_name) && isset($cache['locations'][$location_name])) {
                                    $location_id = $cache['locations'][$location_name];
                                } else {
                                    $location = BusinessLocation::where('business_id', $business_id)->first();
                                    $location_id = $location ? $location->id : null;
                                }
                                
                                if ($location_id) {
                                    $unit_cost = $this->getNumericValue($this->getMappedValue($row, $import_fields, 'opening_stock_unit_cost'));
                                    if ($unit_cost <= 0) {
                                        $unit_cost = $purchase_price;
                                    }

                                    $opening_stock = [
                                        'quantity' => $opening_stock_qty,
                                        'location_id' => $location_id,
                                        'exp_date' => null,
                                        'is_update' => $is_update,
                                    ];
                                    $this->addOpeningStock($opening_stock, $product, $business_id, $unit_cost);
                                }
                            }
                        }

                        if ($settings['create_purchase'] == 1) {
                            $purchase_qty = $this->getNumericValue($this->getMappedValue($row, $import_fields, 'purchase_qty'));
                            if ($purchase_qty <= 0) {
                                $purchase_qty = $this->getNumericValue($this->getMappedValue($row, $import_fields, 'opening_stock_qty'));
                            }

                            if ($purchase_qty > 0) {
                                $supplier_name = $this->getMappedValue($row, $import_fields, 'supplier');
                                $supplier_id = null;
                                if (! empty($supplier_name) && isset($cache['suppliers'][$supplier_name])) {
                                    $supplier_id = $cache['suppliers'][$supplier_name];
                                }
                                
                                if (! empty($supplier_id)) {
                                    $location_name = $this->getMappedValue($row, $import_fields, 'purchase_location');
                                    $location_id = null;
                                    if (! empty($location_name) && isset($cache['locations'][$location_name])) {
                                        $location_id = $cache['locations'][$location_name];
                                    } else {
                                        $location = BusinessLocation::where('business_id', $business_id)->first();
                                        $location_id = $location ? $location->id : null;
                                    }
                                    
                                    if ($location_id) {
                                        $this->createPurchaseTransaction($product, $purchase_qty, $purchase_price, $tax, $business_id, $user_id, $location_id, $supplier_id, $is_update);
                                    }
                                }
                            }
                        }

                        $created_count++;
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Chunk processing failed: " . $e->getMessage());
                    $errors[] = "Error: " . $e->getMessage();
                }

                gc_collect_cycles();
            }

            $output = [
                'success' => 1,
                'msg' => $this->buildResultMessage($created_count, $errors),
            ];
        } catch (\Exception $e) {
            Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage(),
            ];

            @unlink($file_path);

            return redirect('import-products/universal')->with('notification', $output);
        }

        @unlink($file_path);

        return redirect('import-products/universal')->with('status', $output);
    }

    private function parseDataPreview($file_name)
    {
        $array = Excel::toArray([], public_path('uploads/temp/'.$file_name))[0];

        $headers = array_filter($array[0]);
        unset($array[0]);
        $parsed_array[] = $headers;
        
        $sample_count = 0;
        $max_sample = 100;
        
        foreach ($array as $row) {
            if ($sample_count >= $max_sample) {
                break;
            }
            $temp = [];
            foreach ($row as $k => $v) {
                if (array_key_exists($k, $headers)) {
                    $temp[] = $v;
                }
            }
            if (! empty(array_filter($temp))) {
                $parsed_array[] = $temp;
                $sample_count++;
            }
        }

        return $parsed_array;
    }

    private function parseDataStreaming($file_name)
    {
        $array = Excel::toArray([], public_path('uploads/temp/'.$file_name))[0];

        $headers = array_filter($array[0]);
        unset($array[0]);
        $parsed_array[] = $headers;
        foreach ($array as $row) {
            $temp = [];
            foreach ($row as $k => $v) {
                if (array_key_exists($k, $headers)) {
                    $temp[] = $v;
                }
            }
            $parsed_array[] = $temp;
        }

        return $parsed_array;
    }

    private function getOrCreateUnitCached($unit_name, $business_id, $user_id, &$cache)
    {
        $key = $unit_name;
        if (isset($cache['units'][$key])) {
            return $cache['units'][$key];
        }

        $unit = Unit::where('business_id', $business_id)
            ->where(function ($query) use ($unit_name) {
                $query->where('actual_name', $unit_name)
                    ->orWhere('short_name', $unit_name);
            })
            ->first();

        if (! $unit) {
            $unit = Unit::create([
                'business_id' => $business_id,
                'created_by' => $user_id,
                'actual_name' => $unit_name,
                'short_name' => $unit_name,
                'allow_decimal' => 0,
            ]);
        }

        $cache['units'][$key] = $unit;
        return $unit;
    }

    private function getOrCreateCategoryCached($category_name, $business_id, $user_id, $category_type, $parent_id, &$cache)
    {
        $key = $category_name . '_' . ($parent_id ?? '0');
        if (isset($cache['categories'][$key])) {
            return $cache['categories'][$key];
        }

        if ($parent_id) {
            $category = Category::updateOrCreate(
                ['business_id' => $business_id, 'name' => $category_name, 'category_type' => $category_type],
                ['created_by' => $user_id, 'parent_id' => $parent_id]
            );
        } else {
            $category = Category::firstOrCreate(
                ['business_id' => $business_id, 'name' => $category_name, 'category_type' => $category_type],
                ['created_by' => $user_id]
            );
        }

        $cache['categories'][$key] = $category;
        return $category;
    }

    private function getOrCreateBrandCached($brand_name, $business_id, $user_id, &$cache)
    {
        $key = $brand_name;
        if (isset($cache['brands'][$key])) {
            return $cache['brands'][$key];
        }

        $brand = Brands::firstOrCreate(
            ['business_id' => $business_id, 'name' => $brand_name],
            ['created_by' => $user_id]
        );

        $cache['brands'][$key] = $brand;
        return $brand;
    }

    private function resolveTaxCached($tax_name, $tax_amount, $default_tax_amount, $business_id, $user_id, &$errors, &$cache)
    {
        if (empty($tax_name)) {
            return null;
        }

        $key = $tax_name;
        if (isset($cache['taxes'][$key])) {
            return $cache['taxes'][$key];
        }

        $tax = TaxRate::where('business_id', $business_id)
            ->where('name', $tax_name)
            ->first();

        if (! empty($tax)) {
            $cache['taxes'][$key] = $tax;
            return $tax;
        }

        $tax_amount = $tax_amount !== null && $tax_amount !== '' ? $tax_amount : $default_tax_amount;
        if ($tax_amount === null || $tax_amount === '') {
            $errors[] = "Tax '$tax_name' missing amount";
            return null;
        }

        $tax = TaxRate::create([
            'business_id' => $business_id,
            'name' => $tax_name,
            'amount' => $tax_amount,
            'is_tax_group' => 0,
            'created_by' => $user_id,
        ]);

        $cache['taxes'][$key] = $tax;
        return $tax;
    }

    private function parseData($file_name)
    {
        $array = Excel::toArray([], public_path('uploads/temp/'.$file_name))[0];

        $headers = array_filter($array[0]);
        unset($array[0]);
        $parsed_array[] = $headers;
        foreach ($array as $row) {
            $temp = [];
            foreach ($row as $k => $v) {
                if (array_key_exists($k, $headers)) {
                    $temp[] = $v;
                }
            }
            $parsed_array[] = $temp;
        }

        return $parsed_array;
    }

    private function importFields()
    {
        return [
            'product_name' => ['label' => 'Product Name'],
            'sku' => ['label' => 'SKU'],
            'unit' => ['label' => 'Unit'],
            'manage_stock' => ['label' => 'Manage Stock (1/0)'],
            'category' => ['label' => 'Category'],
            'sub_category' => ['label' => 'Sub Category'],
            'brand' => ['label' => 'Brand'],
            'tax_name' => ['label' => 'Tax Name'],
            'tax_amount' => ['label' => 'Tax Amount'],
            'selling_price' => ['label' => 'Selling Price'],
            'purchase_price' => ['label' => 'Purchase Price'],
            'opening_stock_qty' => ['label' => 'Opening Stock Qty'],
            'opening_stock_unit_cost' => ['label' => 'Opening Stock Unit Cost'],
            'opening_stock_location' => ['label' => 'Opening Stock Location'],
            'supplier' => ['label' => 'Supplier'],
            'purchase_qty' => ['label' => 'Purchase Qty'],
            'purchase_location' => ['label' => 'Purchase Location'],
            'vehicle_brand' => ['label' => 'Vehicle Brand'],
            'vehicle_model' => ['label' => 'Vehicle Model'],
            'from_year' => ['label' => 'From Year'],
            'to_year' => ['label' => 'To Year'],
            'motor_cc' => ['label' => 'Motor CC'],
            'model_year_range' => ['label' => 'Model Year Range'],
        ];
    }

    private function collectSettings(Request $request)
    {
        return [
            'default_unit' => $request->input('default_unit', 'pcs'),
            'default_manage_stock' => $request->input('default_manage_stock', '1'),
            'default_tax_amount' => $request->input('default_tax_amount', null),
            'create_opening_stock' => $request->input('create_opening_stock', 0),
            'create_purchase' => $request->input('create_purchase', 0),
            'require_supplier' => $request->input('require_supplier', 0),
            'auto_create_supplier' => $request->input('auto_create_supplier', 0),
            'create_location' => $request->input('create_location', 1),
            'update_existing' => $request->input('update_existing', 1),
        ];
    }

    private function getMappedValue($row, $mapping, $field)
    {
        if (empty($mapping) || ! in_array($field, $mapping)) {
            return null;
        }

        $index = array_search($field, $mapping);
        if ($index === false || ! isset($row[$index])) {
            return null;
        }

        $value = $row[$index];
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    private function getNumericValue($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) preg_replace('/[^0-9.]/', '', (string) $value);
    }

    private function resolveManageStock($value, $default)
    {
        if ($value === null || $value === '') {
            return (int) $default;
        }

        return (string) $value === '1' ? 1 : 0;
    }

    private function getOrCreateUnit($unit_name, $business_id, $user_id)
    {
        $unit = Unit::where('business_id', $business_id)
            ->where(function ($query) use ($unit_name) {
                $query->where('actual_name', $unit_name)
                    ->orWhere('short_name', $unit_name);
            })
            ->first();

        if (! $unit) {
            $unit = Unit::create([
                'business_id' => $business_id,
                'created_by' => $user_id,
                'actual_name' => $unit_name,
                'short_name' => $unit_name,
                'allow_decimal' => 0,
            ]);
        }

        return $unit;
    }

    private function resolveTax($tax_name, $tax_amount, $default_tax_amount, $business_id, $user_id, $row_no, &$errors)
    {
        if (empty($tax_name)) {
            return null;
        }

        $tax = TaxRate::where('business_id', $business_id)
            ->where('name', $tax_name)
            ->first();

        if (! empty($tax)) {
            return $tax;
        }

        $tax_amount = $tax_amount !== null && $tax_amount !== '' ? $tax_amount : $default_tax_amount;
        if ($tax_amount === null || $tax_amount === '') {
            $errors[] = "Tax '$tax_name' missing amount at row $row_no";
            return null;
        }

        return TaxRate::create([
            'business_id' => $business_id,
            'name' => $tax_name,
            'amount' => $tax_amount,
            'is_tax_group' => 0,
            'created_by' => $user_id,
        ]);
    }

    private function resolveLocation($location_name, $business_id, $user_id, $create_location)
    {
        if (! empty($location_name)) {
            $location = BusinessLocation::where('business_id', $business_id)
                ->where('name', $location_name)
                ->first();
            if (! empty($location)) {
                return $location;
            }

            if ((int) $create_location === 1) {
                $invoice_scheme = InvoiceScheme::getDefault($business_id);
                $invoice_scheme_id = $invoice_scheme ? $invoice_scheme->id : null;

                $invoice_layout = InvoiceLayout::where('business_id', $business_id)->first();
                $invoice_layout_id = $invoice_layout ? $invoice_layout->id : null;

                $default_payment_accounts = [
                    'cash' => ['is_enabled' => '1', 'account' => null],
                    'card' => ['is_enabled' => '1', 'account' => null],
                    'cheque' => ['is_enabled' => '1', 'account' => null],
                    'bank_transfer' => ['is_enabled' => '1', 'account' => null],
                    'other' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_1' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_2' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_3' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_4' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_5' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_6' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_7' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_8' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_9' => ['is_enabled' => '1', 'account' => null],
                    'custom_pay_10' => ['account' => null],
                    'custom_pay_11' => ['account' => null],
                    'custom_pay_12' => ['account' => null],
                    'custom_pay_13' => ['account' => null],
                    'custom_pay_14' => ['account' => null],
                    'custom_pay_15' => ['account' => null],
                    'custom_pay_16' => ['account' => null],
                    'custom_pay_17' => ['account' => null],
                ];

                $accounting_default_map = [
                    'sale' => ['payment_account' => '88', 'deposit_to' => '24'],
                    'sell_payment' => ['payment_account' => null, 'deposit_to' => null],
                    'purchases' => ['payment_account' => null, 'deposit_to' => null],
                    'purchase_payment' => ['payment_account' => null, 'deposit_to' => null],
                    'expense' => ['payment_account' => null, 'deposit_to' => null],
                ];

                return BusinessLocation::create([
                    'business_id' => $business_id,
                    'name' => $location_name,
                    'location_id' => null,
                    'invoice_scheme_id' => $invoice_scheme_id,
                    'sale_invoice_scheme_id' => $invoice_scheme_id,
                    'invoice_layout_id' => $invoice_layout_id,
                    'sale_invoice_layout_id' => $invoice_layout_id,
                    'print_receipt_on_invoice' => 1,
                    'receipt_printer_type' => 'browser',
                    'created_by' => $user_id,
                    'is_active' => 1,
                    'default_payment_accounts' => json_encode($default_payment_accounts),
                    'accounting_default_map' => json_encode($accounting_default_map),
                ]);
            }
        }

        return BusinessLocation::where('business_id', $business_id)->first();
    }

    private function resolveSupplier($supplier_name, $business_id, $user_id, $settings, $row_no, &$errors)
    {
        if (! empty($supplier_name)) {
            $supplier = Contact::where('business_id', $business_id)
                ->where('type', 'supplier')
                ->where('name', $supplier_name)
                ->first();

            if (! empty($supplier)) {
                return $supplier;
            }

            return Contact::create([
                'business_id' => $business_id,
                'type' => 'supplier',
                'name' => $supplier_name,
                'created_by' => $user_id,
                'contact_status' => 'active',
            ]);
        }

        $default_supplier = Contact::where('business_id', $business_id)
            ->where('type', 'supplier')
            ->where('name', 'Default Supplier')
            ->first();

        if (! empty($default_supplier)) {
            return $default_supplier;
        }

        return Contact::create([
            'business_id' => $business_id,
            'type' => 'supplier',
            'name' => 'Default Supplier',
            'created_by' => $user_id,
            'contact_status' => 'active',
        ]);
    }

    private function handleCompatibility($row, $import_fields, $business_id, $user_id, $product)
    {
        $vehicle_brand_name = $this->getMappedValue($row, $import_fields, 'vehicle_brand');
        $vehicle_model_name = $this->getMappedValue($row, $import_fields, 'vehicle_model');
        $from_year = $this->getMappedValue($row, $import_fields, 'from_year');
        $to_year = $this->getMappedValue($row, $import_fields, 'to_year');
        $motor_cc = $this->getMappedValue($row, $import_fields, 'motor_cc');
        $model_year_range = $this->getMappedValue($row, $import_fields, 'model_year_range');

        if (! empty($model_year_range) && ! empty($vehicle_brand_name)) {
            $brand_category = Category::firstOrCreate(
                ['business_id' => $business_id, 'name' => $vehicle_brand_name, 'category_type' => 'device'],
                ['created_by' => $user_id]
            );

            $vehicle_brand = Brands::firstOrCreate(
                ['business_id' => $business_id, 'name' => $vehicle_brand_name],
                ['created_by' => $user_id]
            );

            $segments = array_filter(array_map('trim', preg_split('/[;،]+/u', $model_year_range)));
            foreach ($segments as $seg) {
                $segClean = $seg;
                $brandPrefix = $brand_category->name;
                if (stripos($segClean, $brandPrefix) === 0) {
                    $segClean = trim(substr($segClean, strlen($brandPrefix)));
                }

                $from = null;
                $to = null;
                if (preg_match('/(\d{4})\s*[\x{2013}-]\s*(\d{4})/u', $segClean, $m)) {
                    $from = intval($m[1]);
                    $to = intval($m[2]);
                    $model_str = trim(str_replace($m[0], '', $segClean));
                } elseif (preg_match('/(\d{4})/u', $segClean, $m2)) {
                    $from = intval($m2[1]);
                    $to = $from;
                    $model_str = trim(str_replace($m2[1], '', $segClean));
                } else {
                    $model_str = $segClean;
                }

                if ($model_str === '') {
                    continue;
                }

                $device_model = DeviceModel::firstOrCreate(
                    ['business_id' => $business_id, 'brand_id' => $vehicle_brand->id, 'name' => $model_str],
                    ['created_by' => $user_id]
                );

                $exists = ProductCompatibility::where('product_id', $product->id)
                    ->where('brand_category_id', $brand_category->id)
                    ->where('model_id', $device_model->id)
                    ->where('from_year', $from)
                    ->where('to_year', $to)
                    ->where('motor_cc', $motor_cc)
                    ->exists();

                if (! $exists) {
                    ProductCompatibility::create([
                        'product_id' => $product->id,
                        'brand_category_id' => $brand_category->id,
                        'model_id' => $device_model->id,
                        'from_year' => $from,
                        'to_year' => $to,
                        'motor_cc' => $motor_cc,
                    ]);
                }
            }

            return;
        }

        if (empty($vehicle_brand_name) || empty($vehicle_model_name)) {
            return;
        }

        $brand_category = Category::firstOrCreate(
            ['business_id' => $business_id, 'name' => $vehicle_brand_name, 'category_type' => 'device'],
            ['created_by' => $user_id]
        );

        $vehicle_brand = Brands::firstOrCreate(
            ['business_id' => $business_id, 'name' => $vehicle_brand_name],
            ['created_by' => $user_id]
        );

        $device_model = DeviceModel::firstOrCreate(
            ['business_id' => $business_id, 'brand_id' => $vehicle_brand->id, 'name' => $vehicle_model_name],
            ['created_by' => $user_id]
        );

        $from_year_int = ! empty($from_year) ? (int) $from_year : null;
        $to_year_int = ! empty($to_year) ? (int) $to_year : null;

        $exists = ProductCompatibility::where('product_id', $product->id)
            ->where('brand_category_id', $brand_category->id)
            ->where('model_id', $device_model->id)
            ->where('from_year', $from_year_int)
            ->where('to_year', $to_year_int)
            ->where('motor_cc', $motor_cc)
            ->exists();

        if (! $exists) {
            ProductCompatibility::create([
                'product_id' => $product->id,
                'brand_category_id' => $brand_category->id,
                'model_id' => $device_model->id,
                'from_year' => $from_year_int,
                'to_year' => $to_year_int,
                'motor_cc' => $motor_cc,
            ]);
        }
    }

    private function addOpeningStock($opening_stock, $product, $business_id, $unit_cost_before_tax)
    {
        $user_id = request()->session()->get('user.id');

        $variation = Variation::where('product_id', $product->id)->first();

        $transaction_date = request()->session()->get('financial_year.start');
        $transaction_date = Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();

        $tax_percent = ! empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
        $tax_id = ! empty($product->product_tax->id) ? $product->product_tax->id : null;

        $item_tax = $this->productUtil->calc_percentage($unit_cost_before_tax, $tax_percent);
        $total_before_tax = $opening_stock['quantity'] * ($unit_cost_before_tax + $item_tax);

        $transaction = Transaction::create([
            'type' => 'opening_stock',
            'opening_stock_product_id' => $product->id,
            'status' => 'received',
            'business_id' => $business_id,
            'transaction_date' => $transaction_date,
            'total_before_tax' => $total_before_tax,
            'location_id' => $opening_stock['location_id'],
            'final_total' => $total_before_tax,
            'payment_status' => 'paid',
            'created_by' => $user_id,
        ]);

        $transaction->purchase_lines()->create([
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => $opening_stock['quantity'],
            'item_tax' => $item_tax,
            'tax_id' => $tax_id,
            'pp_without_discount' => $unit_cost_before_tax,
            'purchase_price' => $unit_cost_before_tax,
            'purchase_price_inc_tax' => $unit_cost_before_tax + $item_tax,
            'exp_date' => ! empty($opening_stock['exp_date']) ? $opening_stock['exp_date'] : null,
        ]);

        if (! empty($opening_stock['is_update'])) {
            $current_qty = $this->productUtil->getCurrentStock($variation->id, $opening_stock['location_id']);
            $new_qty = $current_qty + $opening_stock['quantity'];
            $this->productUtil->updateProductQuantity($opening_stock['location_id'], $product->id, $variation->id, $new_qty, $current_qty);
        } else {
            $this->productUtil->updateProductQuantity($opening_stock['location_id'], $product->id, $variation->id, $opening_stock['quantity']);
        }
        $this->addProductLocation($product, $opening_stock['location_id']);
    }

    private function addProductLocation($product, $location_id)
    {
        $count = DB::table('product_locations')->where('product_id', $product->id)
            ->where('location_id', $location_id)
            ->count();
        if ($count == 0) {
            DB::table('product_locations')->insert([
                'product_id' => $product->id,
                'location_id' => $location_id,
            ]);
        }
    }

    private function createPurchaseTransaction($product, $quantity, $purchase_price, $tax, $business_id, $user_id, $location_id, $supplier_id, $is_update = false)
    {
        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
        $exchange_rate = 1;

        $item_tax = 0;
        $tax_id = null;
        if (! empty($tax)) {
            $tax_id = $tax->id;
            $item_tax = $this->productUtil->calc_percentage($purchase_price, $tax->amount);
        }
        $purchase_price_inc_tax = $purchase_price + $item_tax;

        $total_before_tax = $quantity * $purchase_price;
        $final_total = $quantity * $purchase_price_inc_tax;

        $transaction_data = [
            'business_id' => $business_id,
            'created_by' => $user_id,
            'type' => 'purchase',
            'status' => 'received',
            'contact_id' => $supplier_id,
            'location_id' => $location_id,
            'transaction_date' => Carbon::now()->toDateTimeString(),
            'total_before_tax' => $total_before_tax,
            'final_total' => $final_total,
            'payment_status' => 'due',
            'exchange_rate' => $exchange_rate,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_charges' => 0,
        ];

        $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
        $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count);

        $transaction = Transaction::create($transaction_data);

        $variation = Variation::where('product_id', $product->id)->first();

        $purchases = [[
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => $quantity,
            'pp_without_discount' => $purchase_price,
            'discount_percent' => 0,
            'purchase_price' => $purchase_price,
            'purchase_price_inc_tax' => $purchase_price_inc_tax,
            'item_tax' => $item_tax,
            'purchase_line_tax_id' => $tax_id,
            'product_unit_id' => $product->unit_id,
            'sub_unit_id' => null,
        ]];

        $this->productUtil->createOrUpdatePurchaseLines($transaction, $purchases, $currency_details, 0);
        $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
    }

    private function buildResultMessage($created_count, $errors)
    {
        $msg = "Imported {$created_count} products";
        if (! empty($errors)) {
            $msg .= ' | Warnings: '.implode(' | ', $errors);
        }

        return $msg;
    }
}
