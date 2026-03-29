<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Product;
use App\Utils\ProductUtil;
use App\Category;
use App\Unit;
use Excel;

class LabourByVehicleController extends Controller
{
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }
    public function index()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            return $this->getLabourByVehicleDataTable();
        }

        return view('labour_by_vehicle.index');
    }

    public function getLabourByVehicleDataTable()
    {
        $labour_by_vehicle = DB::table('labour_by_vehicle')
            ->leftJoin('categories', 'labour_by_vehicle.device_id', '=', 'categories.id')
            ->leftJoin('repair_device_models', 'labour_by_vehicle.repair_device_model_id', '=', 'repair_device_models.id')
            ->select([
                'labour_by_vehicle.id',
                'labour_by_vehicle.device_id',
                'labour_by_vehicle.from',
                'labour_by_vehicle.to',
                'labour_by_vehicle.repair_device_model_id',
                'labour_by_vehicle.created_at',
                'labour_by_vehicle.updated_at',
                'categories.name as brand_name',
                'repair_device_models.name as model_name',
                DB::raw("CONCAT_WS(' - ', categories.name, repair_device_models.name) as name")
            ]);

        return DataTables::of($labour_by_vehicle)
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">';
                $html .= '<button type="button" class="btn btn-info btn-xs btn-modal edit-labour-vehicle" 
                    data-container=".labour_vehicle_modal" 
                    data-href="' . action([\App\Http\Controllers\LabourByVehicleController::class, 'edit'], [$row->id]) . '" 
                    data-id="' . $row->id . '"
                    data-device-id="' . $row->device_id . '"
                    data-from="' . $row->from . '"
                    data-to="' . $row->to . '"
                    data-repair-device-model-id="' . $row->repair_device_model_id . '">
                    <i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button>';
                $html .= '<button type="button" class="btn btn-danger btn-xs delete-labour-vehicle" 
                    data-href="' . action([\App\Http\Controllers\LabourByVehicleController::class, 'destroy'], [$row->id]) . '">
                    <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';
                $html .= '<button type="button" class="btn btn-primary btn-xs manage-labours" 
                    data-container=".labour_vehicle_modal" 
                    data-href="' . action([\App\Http\Controllers\LabourByVehicleController::class, 'manageLabours'], [$row->id]) . '">
                    <i class="glyphicon glyphicon-list"></i> Manage Labours</button>';
                $html .= '</div>';
                return $html;
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : '-';
            })
            ->editColumn('updated_at', function ($row) {
                return $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $device_categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'device')
            ->select('id', 'name')
            ->get();
        $car_models = DB::table('repair_device_models')->select('id', 'name', 'device_id')->get();

        return view('labour_by_vehicle.create', compact('device_categories', 'car_models'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'device_id' => 'required|integer|exists:categories,id',
                'from' => 'nullable|integer',
                'to' => 'nullable|integer',
                'repair_device_model_id' => 'nullable|integer|exists:repair_device_models,id',
            ]);

            DB::table('labour_by_vehicle')->insert([
                'device_id' => $request->device_id,
                'from' => $request->from,
                'to' => $request->to,
                'repair_device_model_id' => $request->repair_device_model_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Labour by Vehicle created successfully')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $labour_by_vehicle = DB::table('labour_by_vehicle')
            ->leftJoin('categories', 'labour_by_vehicle.device_id', '=', 'categories.id')
            ->leftJoin('repair_device_models', 'labour_by_vehicle.repair_device_model_id', '=', 'repair_device_models.id')
            ->where('labour_by_vehicle.id', $id)
            ->select(
                'labour_by_vehicle.id',
                'labour_by_vehicle.device_id',
                'labour_by_vehicle.repair_device_model_id',
                'categories.name as brand_name',
                'repair_device_models.name as model_name'
            )
            ->first();
        $business_id = request()->session()->get('user.business_id');
        $device_categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'device')
            ->select('id', 'name')
            ->get();
        $car_models = DB::table('repair_device_models')->select('id', 'name', 'device_id')->get();

        return view('labour_by_vehicle.edit', compact('labour_by_vehicle', 'device_categories', 'car_models'));
    }

    public function modelsByBrand(Request $request)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $brand_id = $request->get('brand_id');
        $models = DB::table('repair_device_models')
            ->when(!empty($brand_id), function ($query) use ($brand_id) {
                $query->where('device_id', $brand_id);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($models);
    }

    public function searchLabourByVehicleForm()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $brands = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'device')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('labour_by_vehicle.search_labour_by_vehicle', compact('brands'));
    }

    public function searchLabourProducts(Request $request)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'brand_id' => 'required|integer|exists:categories,id',
            'model_id' => 'required|integer|exists:repair_device_models,id',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        try {
            $brandId = (int) $validated['brand_id'];
            $modelId = (int) $validated['model_id'];
            $year = (int) $validated['year'];

            $products = DB::table('labour_by_vehicle_products')
                ->join('labour_by_vehicle', 'labour_by_vehicle_products.labour_by_vehicle_id', '=', 'labour_by_vehicle.id')
                ->join('products', 'labour_by_vehicle_products.product_id', '=', 'products.id')
                ->where('labour_by_vehicle.device_id', $brandId)
                ->where('labour_by_vehicle.repair_device_model_id', $modelId)
                ->where(function ($query) use ($year) {
                    $query->where(function ($q) use ($year) {
                        $q->whereNotNull('labour_by_vehicle.from')
                            ->whereNotNull('labour_by_vehicle.to')
                            ->where('labour_by_vehicle.from', '<=', $year)
                            ->where('labour_by_vehicle.to', '>=', $year);
                    })->orWhere(function ($q) {
                        $q->whereNull('labour_by_vehicle.from')
                            ->whereNull('labour_by_vehicle.to');
                    });
                })
                ->where('labour_by_vehicle_products.is_active', 1)
                ->select([
                    'products.name',
                    'labour_by_vehicle_products.price',
                ])
                ->orderBy('products.name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'device_id' => 'required|integer|exists:categories,id',
                'from' => 'nullable|integer',
                'to' => 'nullable|integer',
                'repair_device_model_id' => 'nullable|integer|exists:repair_device_models,id',
            ]);

            DB::table('labour_by_vehicle')
                ->where('id', $id)
                ->update([
                    'device_id' => $request->device_id,
                    'from' => $request->from,
                    'to' => $request->to,
                    'repair_device_model_id' => $request->repair_device_model_id,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => __('Labour by Vehicle updated successfully')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::table('labour_by_vehicle')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => __('Labour by Vehicle deleted successfully')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function manageLabours($id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $labour_by_vehicle = DB::table('labour_by_vehicle')->where('id', $id)->first();

        return view('labour_by_vehicle.manage_labours', compact('labour_by_vehicle'));
    }

    public function labourProducts()
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        return view('labour_by_vehicle.labour_products_index');
    }

    public function importLabourByVehicleForm()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('labour_by_vehicle.import_labour_by_vehicle');
    }

    public function importLabourByVehicleStore(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            if (!$request->hasFile('labour_excel')) {
                return back()->with(['status' => ['success' => 0, 'msg' => __('لم يتم رفع ملف إكسل.')]]);
            }

            $file = $request->file('labour_excel');
            $parsed = Excel::toArray([], $file);
            if (empty($parsed)) {
                return back()->with(['status' => ['success' => 0, 'msg' => __('الملف فارغ أو غير صالح.')]]);
            }

            $sheet = $parsed['الادخال'] ?? $parsed[0] ?? [];
            if (count($sheet) < 2) {
                return back()->with(['status' => ['success' => 0, 'msg' => __('لا توجد بيانات بعد صف العناوين.')]]);
            }

            $expected_headers = ['التصنيف', 'الماركه', 'الموديل', 'سنه الصنع', 'البند', 'نوع الصيانه', 'السعر بعد الخصم', 'سعر المصنعيه'];
            $header_row_index = null;
            $headers = [];

            foreach ($sheet as $row_index => $row) {
                if (empty($row) || !is_array($row)) {
                    continue;
                }

                $row_headers = array_map(function ($h) {
                    return is_string($h) ? trim($h) : $h;
                }, $row);

                $matched_headers = 0;
                foreach ($expected_headers as $expected) {
                    if (in_array($expected, $row_headers, true)) {
                        $matched_headers++;
                    }
                }

                if ($matched_headers >= 4) {
                    $header_row_index = $row_index;
                    $headers = $row_headers;
                    break;
                }
            }

            if ($header_row_index === null) {
                return back()->with(['status' => ['success' => 0, 'msg' => __('Could not find header row with expected columns. Please check Excel file structure.')]]);
            }

            $header_map = [];
            foreach ($headers as $idx => $label) {
                $label = is_string($label) ? trim($label) : $label;
                if (!isset($header_map[$label])) {
                    $header_map[$label] = [];
                }
                $header_map[$label][] = $idx;
            }

            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $default_unit = Unit::where('business_id', $business_id)
                ->where(function ($query) {
                    $query->where('short_name', 'Pc(s)')
                        ->orWhere('short_name', 'Each')
                        ->orWhere('actual_name', 'Pieces')
                        ->orWhere('actual_name', 'قطعة');
                })
                ->first();

            if (!$default_unit) {
                $default_unit = Unit::create([
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'actual_name' => 'Pieces',
                    'short_name' => 'Pc(s)',
                    'allow_decimal' => 0,
                ]);
            }

            $required_headers = ['التصنيف', 'الماركه', 'الموديل', 'سنه الصنع', 'البند', 'نوع الصيانه', 'السعر بعد الخصم', 'سعر المصنعيه'];
            $missing_headers = [];
            foreach ($required_headers as $header) {
                if (empty($header_map[$header])) {
                    $missing_headers[] = $header;
                }
            }

            if (!empty($missing_headers)) {
                return back()->with(['status' => ['success' => 0, 'msg' => __('Missing required columns: ') . implode(', ', $missing_headers) . '. Found headers: ' . implode(', ', array_keys($header_map))]]);
            }

            DB::beginTransaction();

            $data_rows = array_slice($sheet, $header_row_index + 1);
            $total_rows = count($data_rows);
            $processed_rows = 0;
            $skipped_rows = 0;
            $error_rows = 0;
            $errors = [];

            foreach ($data_rows as $row_index => $row) {
                $row_no = $row_index + 2;

                $classification = $this->getCellValue($row, $header_map, 'التصنيف');
                $brand_name = $this->getCellValue($row, $header_map, 'الماركه');
                $model_name = $this->getCellValue($row, $header_map, 'الموديل');
                $year_range = $this->getCellValue($row, $header_map, 'سنه الصنع');
                $item_name = $this->getCellValue($row, $header_map, 'البند');
                $maintenance_type = $this->getCellValue($row, $header_map, 'نوع الصيانه');
                $price_after_discount = $this->getNumericValue($row, $header_map, 'السعر بعد الخصم');
                $fallback_price = $this->getNumericValue($row, $header_map, 'سعر المصنعيه');

                if ($item_name === '' || $brand_name === '' || $model_name === '') {
                    $skipped_rows++;
                    $errors[] = "Row $row_no: Missing required fields (item: '$item_name', brand: '$brand_name', model: '$model_name')";
                    continue;
                }

                $price = $price_after_discount !== null ? $price_after_discount : $fallback_price;
                if ($price === null) {
                    $skipped_rows++;
                    $errors[] = "Row $row_no: No price found (السعر بعد الخصم or سعر المصنعيه)";
                    continue;
                }

                try {
                    $parent_device_category_id = 0;
                    if ($classification !== '') {
                        $parent_device_category = Category::firstOrCreate(
                            ['business_id' => $business_id, 'name' => $classification, 'category_type' => 'device'],
                            ['created_by' => $user_id, 'parent_id' => 0]
                        );
                        $parent_device_category_id = $parent_device_category->id;
                    }

                    $brand_category = Category::firstOrCreate(
                        ['business_id' => $business_id, 'name' => $brand_name, 'category_type' => 'device'],
                        ['created_by' => $user_id, 'parent_id' => $parent_device_category_id]
                    );

                    $model = DB::table('repair_device_models')
                        ->where('device_id', $brand_category->id)
                        ->where('name', $model_name)
                        ->first();

                    if (!$model) {
                        $model_id = DB::table('repair_device_models')->insertGetId([
                            'business_id' => $business_id,
                            'created_by' => $user_id,
                            'device_id' => $brand_category->id,
                            'name' => $model_name,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $model_id = $model->id;
                    }

                    [$from_year, $to_year] = $this->parseYearRange($year_range);

                    $labour_by_vehicle = DB::table('labour_by_vehicle')
                        ->where('device_id', $brand_category->id)
                        ->where('repair_device_model_id', $model_id)
                        ->where('from', $from_year)
                        ->where('to', $to_year)
                        ->first();

                    if (!$labour_by_vehicle) {
                        $labour_by_vehicle_id = DB::table('labour_by_vehicle')->insertGetId([
                            'device_id' => $brand_category->id,
                            'repair_device_model_id' => $model_id,
                            'from' => $from_year,
                            'to' => $to_year,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $labour_by_vehicle_id = $labour_by_vehicle->id;
                    }

                    $product_category_id = null;
                    if ($maintenance_type !== '') {
                        $product_category = Category::firstOrCreate(
                            ['business_id' => $business_id, 'name' => $maintenance_type, 'category_type' => 'product'],
                            ['created_by' => $user_id, 'parent_id' => 0]
                        );
                        $product_category_id = $product_category->id;
                    }

                    $product = Product::where('business_id', $business_id)
                        ->where('name', $item_name)
                        ->where('is_labour', 1)
                        ->first();

                    if (!$product) {
                        $tempSku = 'tmp-'.$business_id.'-'.Str::uuid();
                        $product = Product::create([
                            'name' => $item_name,
                            'business_id' => $business_id,
                            'type' => 'single',
                            'unit_id' => $default_unit->id,
                            'category_id' => $product_category_id,
                            'sku' => $tempSku,
                            'enable_stock' => 0,
                            'virtual_product' => 0,
                            'is_client_flagged' => 0,
                            'is_labour' => 1,
                            'created_by' => $user_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $sku = $this->productUtil->generateProductSku($product->id);
                        $product->sku = $sku;
                        $product->save();

                        $this->productUtil->createSingleProductVariation(
                            $product,
                            $sku,
                            0,
                            0,
                            0,
                            $price,
                            $price
                        );
                    } else {
                        $product->category_id = $product_category_id;
                        $product->enable_stock = 0;
                        $product->is_labour = 1;
                        $product->updated_at = now();
                        $product->save();

                        DB::table('variations')
                            ->where('product_id', $product->id)
                            ->update([
                                'default_sell_price' => $price,
                                'sell_price_inc_tax' => $price,
                                'updated_at' => now(),
                            ]);
                    }

                    $mapping = DB::table('labour_by_vehicle_products')
                        ->where('labour_by_vehicle_id', $labour_by_vehicle_id)
                        ->where('product_id', $product->id)
                        ->first();

                    if ($mapping) {
                        DB::table('labour_by_vehicle_products')
                            ->where('id', $mapping->id)
                            ->update([
                                'price' => $price,
                                'is_active' => 1,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('labour_by_vehicle_products')->insert([
                            'labour_by_vehicle_id' => $labour_by_vehicle_id,
                            'product_id' => $product->id,
                            'price' => $price,
                            'is_active' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $processed_rows++;
                } catch (\Exception $e) {
                    $error_rows++;
                    $errors[] = "Row $row_no: " . $e->getMessage();
                    \Log::error("Import labour row $row_no error: " . $e->getMessage(), [
                        'row' => $row,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            DB::commit();

            $msg = "Import completed: $processed_rows processed, $skipped_rows skipped";
            if ($error_rows > 0) {
                $msg .= ", $error_rows errors";
            }
            if (!empty($errors)) {
                $msg .= ". First 5 errors: " . implode('; ', array_slice($errors, 0, 5));
            }

            return back()->with(['status' => ['success' => 1, 'msg' => $msg]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(['status' => ['success' => 0, 'msg' => $e->getMessage()]]);
        }
    }

    private function getCellValue(array $row, array $header_map, string $header): string
    {
        if (empty($header_map[$header])) {
            return '';
        }

        foreach ($header_map[$header] as $index) {
            if (isset($row[$index]) && trim((string) $row[$index]) !== '') {
                return trim((string) $row[$index]);
            }
        }

        return '';
    }

    private function getNumericValue(array $row, array $header_map, string $header): ?float
    {
        if (empty($header_map[$header])) {
            return null;
        }

        foreach ($header_map[$header] as $index) {
            $value = isset($row[$index]) ? trim((string) $row[$index]) : '';
            if ($value !== '' && is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    private function parseYearRange(string $year_range): array
    {
        $year_range = trim($year_range);
        if ($year_range === '') {
            return [null, null];
        }

        $normalized = str_replace(['–', '—'], '-', $year_range);
        $parts = array_map('trim', explode('-', $normalized));
        $from = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : null;
        $to = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : $from;

        return [$from, $to];
    }

    public function getLabourProductsDataTable()
    {
        $business_id = request()->session()->get('user.business_id');

        $products = DB::table('products')
            ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->where('products.business_id', $business_id)
            ->where('products.enable_stock', 0)
            ->where('products.virtual_product', 0)
            ->where('products.is_client_flagged', 0)
            ->where('products.is_labour', 1)
            ->select([
                'products.id',
                'products.name',
                'c1.name as category_name',
                DB::raw('MAX(variations.default_sell_price) as price')
            ])
            ->groupBy('products.id', 'products.name', 'c1.name');

        return DataTables::of($products)
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">';
                $html .= '<button type="button" class="btn btn-info btn-xs btn-modal edit-labour-product" data-container=".labour_products_modal" data-href="' . action([\App\Http\Controllers\LabourByVehicleController::class, 'editLabourProductModal'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> Edit</button>';
                $html .= '</div>';
                return $html;
            })
            ->editColumn('price', function ($row) {
                return $row->price ? number_format($row->price, 2) : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function updateLabourProduct(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'sku' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = auth()->id();

            $product = Product::where('business_id', $business_id)
                ->where('id', $request->id)
                ->where('is_labour', 1)
                ->firstOrFail();

            DB::beginTransaction();

            $product->name = $request->name;
            $product->category_id = $request->category_id;
            $product->sku = $request->sku;
            $product->updated_at = now();
            $product->save();

            $price = (float) $request->price;

            DB::table('variations')
                ->where('product_id', $product->id)
                ->update([
                    'default_sell_price' => $price,
                    'sell_price_inc_tax' => $price,
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Labour product updated successfully')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function createLabourProductModal()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'product')
            ->pluck('name', 'id');

        return view('labour_by_vehicle.create_labour_product', compact('categories'));
    }

    public function editLabourProductModal($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->where('is_labour', 1)
            ->firstOrFail();

        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'product')
            ->pluck('name', 'id');

        $variation = DB::table('variations')
            ->where('product_id', $id)
            ->first();

        return view('labour_by_vehicle.edit_labour_product_modal', compact('product', 'categories', 'variation'));
    }

    public function storeLabourProduct(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'sku' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = auth()->id();

            $default_unit = DB::table('units')
                ->where('business_id', $business_id)
                ->where('short_name', 'Pc(s)')
                ->orWhere('short_name', 'Each')
                ->first();

            if (!$default_unit) {
                $default_unit_id = DB::table('units')->insertGetId([
                    'business_id' => $business_id,
                    'actual_name' => 'Pieces',
                    'short_name' => 'Pc(s)',
                    'allow_decimal' => 0,
                    'created_by' => $user_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $default_unit_id = $default_unit->id;
            }

            DB::beginTransaction();

            $tempSku = 'tmp-'.$business_id.'-'.Str::uuid();
            $product = Product::create([
                'name' => $request->name,
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $default_unit_id,
                'category_id' => $request->category_id,
                'sku' => $tempSku,
                'enable_stock' => 0,
                'virtual_product' => 0,
                'is_client_flagged' => 0,
                'is_labour' => 1,
                'created_by' => $user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sku = $request->sku ?: $this->productUtil->generateProductSku($product->id);
            $product->sku = $sku;
            $product->save();

            $price = (float) $request->price;

            $this->productUtil->createSingleProductVariation(
                $product,
                $sku,
                0,
                0,
                0,
                $price,
                $price
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Labour product created successfully')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addLabourProduct($id)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $labour_by_vehicle = DB::table('labour_by_vehicle')->where('id', $id)->first();

        return view('labour_by_vehicle.add_labour_product', compact('labour_by_vehicle'));
    }

    public function getAvailableProducts($labour_by_vehicle_id)
    {
        $existingProductIds = DB::table('labour_by_vehicle_products')
            ->where('labour_by_vehicle_id', $labour_by_vehicle_id)
            ->pluck('product_id');

        $products = DB::table('products')
            ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->where('products.enable_stock', 0)
            ->where('products.virtual_product', 0)
            ->where('products.is_client_flagged', 0)
            ->where('products.is_labour', 1)
            ->whereNotIn('products.id', $existingProductIds)
            ->select([
                'products.id',
                'products.name',
                'c1.name as category_name',
                'variations.default_sell_price as price'
            ]);

        return DataTables::of($products)
            ->addColumn('action', function ($row) {
                return '<button type="button" class="btn btn-success btn-xs add-product-btn" 
                    data-product-id="' . $row->id . '">
                    <i class="glyphicon glyphicon-plus"></i> Add</button>';
            })
            ->editColumn('price', function ($row) {
                return $row->price ? number_format($row->price, 2) : '-';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getManageLaboursDataTable($labour_by_vehicle_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $products = DB::table('products')
            ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('labour_by_vehicle_products', function ($join) use ($labour_by_vehicle_id) {
                $join->on('products.id', '=', 'labour_by_vehicle_products.product_id')
                    ->where('labour_by_vehicle_products.labour_by_vehicle_id', '=', $labour_by_vehicle_id);
            })
            ->where('products.business_id', $business_id)
            ->where('products.enable_stock', 0)
            ->where('products.virtual_product', 0)
            ->where('products.is_client_flagged', 0)
            ->where('products.is_labour', 1)
            ->select([
                'products.id',
                'products.name',
                'c1.name as category_name',
                'labour_by_vehicle_products.id as mapping_id',
                'labour_by_vehicle_products.price as labour_price',
                'labour_by_vehicle_products.is_active',
                DB::raw('MAX(variations.default_sell_price) as price')
            ])
            ->groupBy('products.id', 'products.name', 'c1.name', 'labour_by_vehicle_products.id', 'labour_by_vehicle_products.price', 'labour_by_vehicle_products.is_active');

        return DataTables::of($products)
            ->addColumn('action', function ($row) {
                return '<button type="button" class="btn btn-primary btn-xs save-labour-price" 
                    data-mapping-id="' . ($row->mapping_id ?? 0) . '"
                    data-product-id="' . $row->id . '">
                    <i class="glyphicon glyphicon-save"></i> Save</button>';
            })
            ->addColumn('type', function ($row) {
                return $row->category_name ?: '-';
            })
            ->addColumn('price_input', function ($row) {
                $value = $row->labour_price !== null ? $row->labour_price : 0;
                return '<input type="text" class="form-control input-sm" id="labour_price_' . $row->id . '" value="' . number_format($value, 2, '.', '') . '">';
            })
            ->addColumn('status', function ($row) {
                $disabled = empty($row->mapping_id) ? 'disabled' : '';
                $checked = !empty($row->is_active) ? 'checked' : '';
                return '<label class="checkbox-inline">'
                    . '<input type="checkbox" class="toggle-labour-product" data-mapping-id="' . ($row->mapping_id ?? 0) . '" ' . $checked . ' ' . $disabled . '>'
                    . '</label>';
            })
            ->rawColumns(['action', 'price_input', 'status'])
            ->make(true);
    }

    public function updateLabourPrice(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'labour_by_vehicle_id' => 'required|integer',
            'product_id' => 'required|integer',
            'price' => 'required|numeric|min:0',
        ]);

        $mapping_id = $request->input('mapping_id');
        $price = $request->input('price');

        if (!empty($mapping_id)) {
            DB::table('labour_by_vehicle_products')
                ->where('id', $mapping_id)
                ->update([
                    'price' => $price,
                    'is_active' => 1,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('labour_by_vehicle_products')->insert([
                'labour_by_vehicle_id' => $request->input('labour_by_vehicle_id'),
                'product_id' => $request->input('product_id'),
                'price' => $price,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => __('Labour price saved')]);
    }

    public function addMultipleProducts(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $labourByVehicleId = $request->input('labour_by_vehicle_id');
            $productIds = $request->input('product_ids', []);

            if (empty($productIds)) {
                return response()->json(['success' => false, 'message' => 'No products selected']);
            }

            $addedCount = 0;
            foreach ($productIds as $productId) {
                // Check if already exists
                $exists = DB::table('labour_by_vehicle_products')
                    ->where('labour_by_vehicle_id', $labourByVehicleId)
                    ->where('product_id', $productId)
                    ->exists();

                if (!$exists) {
                    DB::table('labour_by_vehicle_products')->insert([
                        'labour_by_vehicle_id' => $labourByVehicleId,
                        'product_id' => $productId,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $addedCount++;
                }
            }

            if ($addedCount > 0) {
                return response()->json(['success' => true, 'message' => $addedCount . ' products added successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'All selected products are already assigned']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function toggleLabour(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $labour_by_vehicle_id = $request->labour_by_vehicle_id;
            $product_id = $request->product_id;
            $action = $request->action;

            if ($action === 'add') {
                DB::table('labour_by_vehicle_products')->insert([
                    'labour_by_vehicle_id' => $labour_by_vehicle_id,
                    'product_id' => $product_id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => __('Labour added successfully')
                ]);
            } elseif ($action === 'remove') {
                DB::table('labour_by_vehicle_products')
                    ->where('labour_by_vehicle_id', $labour_by_vehicle_id)
                    ->where('product_id', $product_id)
                    ->delete();

                return response()->json([
                    'success' => true,
                    'message' => __('Labour removed successfully')
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function editLabourProduct($id)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $mapping = DB::table('labour_by_vehicle_products')->where('id', $id)->first();
        
        if (!$mapping) {
            abort(404, 'Mapping not found.');
        }

        $product = DB::table('products')->where('id', $mapping->product_id)->first();
        $variation = DB::table('variations')->where('product_id', $mapping->product_id)->first();

        return view('labour_by_vehicle.edit_labour_product', compact('mapping', 'product', 'variation'));
    }

    public function updateLabourProductMapping(Request $request)
    {
        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $mappingId = $request->mapping_id;
            $isActive = $request->is_active;

            DB::table('labour_by_vehicle_products')
                ->where('id', $mappingId)
                ->update([
                    'is_active' => $isActive,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => __('Labour product updated successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
