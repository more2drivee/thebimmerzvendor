<?php

namespace App\Http\Controllers;

use App\SellingPriceGroup;
use App\Utils\Util;
use App\Variation;
use App\VariationGroupPrice;
use DB;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class SellingPriceGroupController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $price_groups = SellingPriceGroup::where('business_id', $business_id)
                        ->select(['name', 'description', 'id', 'is_active']);

            return Datatables::of($price_groups)
                ->addColumn(
                    'action',
                    '<button data-href="{{action(\'App\Http\Controllers\SellingPriceGroupController@edit\', [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                        <button data-href="{{action(\'App\Http\Controllers\SellingPriceGroupController@destroy\', [$id])}}" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete_spg_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                        &nbsp;
                        <button data-href="{{action(\'App\Http\Controllers\SellingPriceGroupController@activateDeactivate\', [$id])}}" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs  @if($is_active) tw-dw-btn-error @else tw-dw-btn-success @endif activate_deactivate_spg"><i class="fas fa-power-off"></i> @if($is_active) @lang("messages.deactivate") @else @lang("messages.activate") @endif</button>'
                )
                ->removeColumn('is_active')
                ->removeColumn('id')
                ->rawColumns([2])
                ->make(false);
        }

        return view('selling_price_group.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        return view('selling_price_group.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'description']);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;

            $spg = SellingPriceGroup::create($input);

            //Create a new permission related to the created selling price group
            Permission::create(['name' => 'selling_price_group.'.$spg->id]);

            $output = ['success' => true,
                'data' => $spg,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\SellingPriceGroup  $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function show(SellingPriceGroup $sellingPriceGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\SellingPriceGroup  $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $spg = SellingPriceGroup::where('business_id', $business_id)->find($id);

            return view('selling_price_group.edit')
                ->with(compact('spg'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SellingPriceGroup  $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'description']);
                $business_id = $request->session()->get('user.business_id');

                $spg = SellingPriceGroup::where('business_id', $business_id)->findOrFail($id);
                $spg->name = $input['name'];
                $spg->description = $input['description'];
                $spg->save();

                $output = ['success' => true,
                    'msg' => __('lang_v1.updated_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\SellingPriceGroup  $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $spg = SellingPriceGroup::where('business_id', $business_id)->findOrFail($id);
                $spg->delete();

                $output = ['success' => true,
                    'msg' => __('lang_v1.deleted_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Show interface to download product price excel file.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateProductPrice(){
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        return view('selling_price_group.update_product_price');
    }

    /**
     * Exports selling price group prices for all the products in xls format
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        $business_id = request()->user()->business_id;
        $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->get();

        $variations = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
                            ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                            ->where('p.business_id', $business_id)
                            ->whereIn('p.type', ['single', 'variable'])
                            ->select('sub_sku', 'p.name as product_name', 'variations.name as variation_name', 'p.type', 'variations.id', 'pv.name as product_variation_name', 'sell_price_inc_tax')
                            ->with(['group_prices'])
                            ->get();
        $export_data = [];
        foreach ($variations as $variation) {
            $temp = [];
            $temp['product'] = $variation->type == 'single' ? $variation->product_name : $variation->product_name.' - '.$variation->product_variation_name.' - '.$variation->variation_name;
            $temp['sku'] = $variation->sub_sku;
            $temp['Selling Price Including Tax'] = $variation->sell_price_inc_tax;

            foreach ($price_groups as $price_group) {
                $price_group_id = $price_group->id;
                $variation_pg = $variation->group_prices->filter(function ($item) use ($price_group_id) {
                    return $item->price_group_id == $price_group_id;
                });

                $temp[$price_group->name] = $variation_pg->isNotEmpty() ? $variation_pg->first()->price_inc_tax : '';
            }
            $export_data[] = $temp;
        }

        if (ob_get_contents()) {
            ob_end_clean();
        }
        ob_start();

        return collect($export_data)->downloadExcel(
            'product_prices.xlsx',
            null,
            true
        );
    }

    /**
     * Imports the uploaded file to database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        try {
            $notAllowed = $this->commonUtil->notAllowedInDemo();
            if (! empty($notAllowed)) {
                return $notAllowed;
            }

            //Set maximum php execution time and memory
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');
            ini_set('post_max_size', '500M');
            ini_set('upload_max_filesize', '500M');
            ini_set('max_input_time', 0);

            Log::info('Starting product price import');

            if ($request->hasFile('product_group_prices')) {
                $file = $request->file('product_group_prices');
                Log::info('File uploaded', ['file_name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);

                $business_id = request()->user()->business_id;
                $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->get();
                Log::info('Price groups loaded', ['count' => $price_groups->count()]);

                $skipped_skus = [];
                $updated_count = 0;
                $total_rows = 0;
                $imported_pgs = [];

                // Get price group columns from file headers
                Log::info('Reading Excel file');
                $parsed_array = Excel::toArray([], $file);
                Log::info('Excel file parsed', ['sheets' => count($parsed_array), 'rows_in_first_sheet' => count($parsed_array[0])]);

                $headers = $parsed_array[0][0];
                Log::info('Headers extracted', ['headers' => $headers]);

                foreach ($headers as $key => $value) {
                    if (! empty($value) && $key > 2) {
                        $imported_pgs[$key] = $value;
                    }
                }
                Log::info('Price group columns identified', ['columns' => $imported_pgs]);

                // Remove header row
                $imported_data = array_splice($parsed_array[0], 1);
                Log::info('Data rows extracted (excluding header)', ['total_rows' => count($imported_data)]);

                // Process in chunks
                $chunk_size = 500;
                $chunk_num = 0;
                foreach (array_chunk($imported_data, $chunk_size) as $chunk) {
                    $chunk_num++;
                    Log::info("Processing chunk {$chunk_num}", ['rows_in_chunk' => count($chunk)]);

                    DB::beginTransaction();

                    try {
                        foreach ($chunk as $row) {
                            $total_rows++;

                            $sku = trim($row[1] ?? '');
                            if (empty($sku)) {
                                continue;
                            }

                            $variation = Variation::where('sub_sku', $sku)->first();
                            if (empty($variation)) {
                                $skipped_skus[] = $sku;
                                continue;
                            }

                            //Check if product base price is changed
                            if(isset($row[2]) && $variation->sell_price_inc_tax != $row[2]){
                                //update price for base selling price, adjust default_sell_price, profit %
                                $variation->sell_price_inc_tax = $row[2];
                                $tax = $variation->product->product_tax()->get();
                                $tax_percent = !empty($tax) && !empty($tax->first()) ? $tax->first()->amount : 0;
                                $variation->default_sell_price = $this->commonUtil->calc_percentage_base($row[2], $tax_percent);
                                $variation->profit_percent = $this->commonUtil
                                                ->get_percent($variation->default_purchase_price, $variation->default_sell_price);
                                $variation->update();
                                $updated_count++;
                            }

                            //update selling price for price groups
                            foreach ($imported_pgs as $k => $v) {
                                $price_group = $price_groups->filter(function ($item) use ($v) {
                                    return strtolower($item->name) == strtolower($v);
                                });

                                if ($price_group->isNotEmpty()) {
                                    if (isset($row[$k]) && ! is_null($row[$k])) {
                                        if (! is_numeric($row[$k])) {
                                            throw new \Exception("Non-numeric price value found");
                                        }
                                        VariationGroupPrice::updateOrCreate(
                                            ['variation_id' => $variation->id,
                                                'price_group_id' => $price_group->first()->id,
                                            ],
                                            ['price_inc_tax' => $row[$k],
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                        DB::commit();
                        Log::info("Chunk {$chunk_num} committed", ['updated_in_chunk' => $updated_count]);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error("Chunk {$chunk_num} failed", ['error' => $e->getMessage()]);
                        throw $e;
                    }

                    gc_collect_cycles();
                }

                Log::info('Import completed', [
                    'total_rows' => $total_rows,
                    'updated_count' => $updated_count,
                    'skipped_count' => count($skipped_skus)
                ]);

                $msg = __('lang_v1.product_prices_imported_successfully');
                $msg .= " (Total rows: {$total_rows})";
                if ($updated_count > 0) {
                    $msg .= " (Updated: {$updated_count})";
                }
                if (!empty($skipped_skus)) {
                    $msg .= " (Skipped: " . count($skipped_skus) . " - SKUs not found)";
                    Log::info('Skipped SKUs during price import', [
                        'skus' => array_slice($skipped_skus, 0, 50),
                        'total_skipped' => count($skipped_skus)
                    ]);
                }
                $output = ['success' => 1, 'msg' => $msg];
            } else {
                Log::warning('No file uploaded');
                $output = ['success' => 0, 'msg' => 'No file uploaded'];
            }
        } catch (\Exception $e) {
            \Log::emergency('Import error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $output = ['success' => 0,
                'msg' => $e->getMessage(),
            ];

            return redirect('update-product-price')->with('notification', $output);
        }

        return redirect('update-product-price')->with('status', $output);
    }

    /**
     * Activate/deactivate selling price group.
     */
    public function activateDeactivate($id)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $spg = SellingPriceGroup::where('business_id', $business_id)->find($id);
            $spg->is_active = $spg->is_active == 1 ? 0 : 1;
            $spg->save();

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];

            return $output;
        }
    }
}
