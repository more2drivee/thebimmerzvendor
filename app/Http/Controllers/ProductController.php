<?php

namespace App\Http\Controllers;

use Excel;
use App\Unit;
use App\Media;
use App\Brands;
use App\Product;
use App\TaxRate;
use App\Business;
use App\Category;
use App\Warranty;
use App\Variation;
use App\PurchaseLine;
use App\BusinessLocation;
use App\ProductVariation;
use App\Utils\ModuleUtil;
use App\SellingPriceGroup;
use App\Utils\ProductUtil;
use App\VariationTemplate;
use App\TransactionSellLine;
use App\VariationGroupPrice;
use App\ProductJobOrder;
use Illuminate\Http\Request;
use App\Exports\ProductsExport;
use App\VariationLocationDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Repair\Entities\DeviceModel;
use Yajra\DataTables\Facades\DataTables;
use App\Events\ProductsCreatedOrModified;

use App\Utils\Util;
class ProductController extends Controller
{
    /**
     * All Utils instance.
     */
    private $commonUtil;
    protected $productUtil;

    protected $moduleUtil;

    private $barcode_types;
    protected $Util;

    /**
     * Search products for Select2 dropdown
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchProducts(Request $request)
    {
        if ($request->ajax()) {
            $term = $request->input('q', '');
            $page = $request->input('page', 1);
            $perPage = 10;
            
            $query = Product::where('business_id', request()->session()->get('user.business_id'))
                ->where('virtual_product', 0)
                ->where('is_client_flagged', 0)
                ->where('is_inactive', 0)
                ->where(function($q) use ($term) {
                    $q->where('name', 'like', '%' . $term . '%')
                      ->orWhere('sku', 'like', '%' . $term . '%');
                });
            
            $total = $query->count();
            $products = $query->select('id', 'name as text', 'sku')
                ->skip(($page - 1) * $perPage)
                ->take($perPage + 1)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->text . ' (' . $item->sku . ')'
                    ];
                });
            
            $more = $products->count() > $perPage;
            if ($more) {
                $products->pop();
            }
            
            return response()->json([
                'results' => $products,
                'pagination' => [
                    'more' => $more
                ]
            ]);
        }
        
        abort(404);
    }

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;

        //barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('product.view') && ! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');

        if (request()->ajax()) {
            //Filter by location
            $location_id = request()->get('location_id', null);
            $permitted_locations = auth()->user()->permitted_locations();

            $query = Product::with(['media'])
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->join('units', 'products.unit_id', '=', 'units.id')
                ->leftJoin('categories as c1', 'products.category_id', '=', 'c1.id')
                ->leftJoin('categories as c2', 'products.sub_category_id', '=', 'c2.id')
                ->leftJoin('tax_rates', 'products.tax', '=', 'tax_rates.id')
                ->join('variations as v', 'v.product_id', '=', 'products.id')
                ->leftJoin('variation_location_details as vld', function ($join) use ($permitted_locations) {
                    $join->on('vld.variation_id', '=', 'v.id');
                    if ($permitted_locations != 'all') {
                        $join->whereIn('vld.location_id', $permitted_locations);
                    }
                })
                ->whereNull('v.deleted_at')
                ->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier')
                ->where('products.enable_stock', 1)
           
                ->where('products.virtual_product', 0)
                ->where('products.is_client_flagged', 0);

            if (! empty($location_id) && $location_id != 'none') {
                if ($permitted_locations == 'all' || in_array($location_id, $permitted_locations)) {
                    $query->whereHas('product_locations', function ($query) use ($location_id) {
                        $query->where('product_locations.location_id', '=', $location_id);
                    });
                }
            } elseif ($location_id == 'none') {
                $query->doesntHave('product_locations');
            } else {
                if ($permitted_locations != 'all') {
                    $query->whereHas('product_locations', function ($query) use ($permitted_locations) {
                        $query->whereIn('product_locations.location_id', $permitted_locations);
                    });
                } else {
                    $query->with('product_locations');
                }
            }

            $products = $query->select(
                'products.id',
                'products.name as product',
                'products.type',
                'c1.name as category',
                'c2.name as sub_category',
                'units.actual_name as unit',
                'brands.name as brand',
                'tax_rates.name as tax',
                'products.sku',
                'products.image',
                'products.enable_stock',
                'products.is_inactive',
                'products.not_for_selling',
                'products.product_custom_field1',
                'products.product_custom_field2',
                'products.product_custom_field3',
                'products.product_custom_field4',
                'products.product_custom_field5',
                'products.product_custom_field6',
                'products.product_custom_field7',
                'products.product_custom_field8',
                'products.product_custom_field9',
                'products.product_custom_field10',
                'products.product_custom_field11',
                'products.product_custom_field12',
                'products.product_custom_field13',
                'products.product_custom_field14',
                'products.product_custom_field15',
                'products.product_custom_field16',
                'products.product_custom_field17',
                'products.product_custom_field18',
                'products.product_custom_field19',
                'products.product_custom_field20',
                'products.alert_quantity',
                DB::raw('SUM(vld.qty_available) as current_stock'),
                DB::raw('MAX(v.sell_price_inc_tax) as max_price'),
                DB::raw('MIN(v.sell_price_inc_tax) as min_price'),
                DB::raw('MAX(v.dpp_inc_tax) as max_purchase_price'),
                DB::raw('MIN(v.dpp_inc_tax) as min_purchase_price')
            );

            //if woocomerce enabled add field to query
            if ($is_woocommerce) {
                $products->addSelect('woocommerce_disable_sync');
            }

            $products->groupBy('products.id');

            $type = request()->get('type', null);
            if (! empty($type)) {
                $products->where('products.type', $type);
            }

            $category_id = request()->get('category_id', null);
            if (! empty($category_id)) {
                $products->where('products.category_id', $category_id);
            }

            $sub_category_id = request()->get('sub_category_id', null);
            if (! empty($sub_category_id)) {
                $products->where('products.sub_category_id', $sub_category_id);
            }

            $brand_id = request()->get('brand_id', null);
            if (! empty($brand_id)) {
                $products->where('products.brand_id', $brand_id);
            }

            $unit_id = request()->get('unit_id', null);
            if (! empty($unit_id)) {
                $products->where('products.unit_id', $unit_id);
            }

            $tax_id = request()->get('tax_id', null);
            if (! empty($tax_id)) {
                $products->where('products.tax', $tax_id);
            }

            $active_state = request()->get('active_state', null);
            $is_inactive = request()->get('is_inactive', null);
            if ($is_inactive !== null && $is_inactive !== '') {
                $products->where('products.is_inactive', (int) $is_inactive);
            } else {
                if ($active_state === 'inactive') {
                    $products->Inactive();
                } else {
                    $products->Active();
                }
            }

            if ((int) $is_inactive !== 1 && $active_state !== 'inactive') {
                $products->where('products.enable_stock', 1);
            }

            $not_for_selling = request()->get('not_for_selling', null);
            if ($not_for_selling == 'true') {
                $products->ProductNotForSales();
            }

            $woocommerce_enabled = request()->get('woocommerce_enabled', 0);
            if ($woocommerce_enabled == 1) {
                $products->where('products.woocommerce_disable_sync', 0);
            }

            $device_id = request()->get('device_id', null);
            $repair_model_id = request()->get('repair_model_id', null);

            if (! empty($device_id) || ! empty($repair_model_id)) {
                $products->leftJoin('product_compatibility as pc', function ($join) use ($device_id, $repair_model_id) {
                    $join->on('pc.product_id', '=', 'products.id');
                    
                    if (! empty($device_id)) {
                        $join->leftJoin('repair_device_models as dm', 'pc.model_id', '=', 'dm.id')
                            ->where('dm.device_id', $device_id);
                    }
                    
                    if (! empty($repair_model_id)) {
                        $join->where('pc.model_id', $repair_model_id);
                    }
                })
                ->where(function ($query) {
                    $query->whereNotNull('pc.id');
                });
            }

            return Datatables::of($products)
                ->addColumn(
                    'product_locations',
                    function ($row) {
                        return $row->product_locations->implode('name', ', ');
                    }
                )
                ->editColumn('category', '{{$category}} @if(!empty($sub_category))<br/> -- {{$sub_category}}@endif')
                ->addColumn(
                    'action',
                    function ($row) use ($selling_price_group_count) {
                        $html =
                            '<div class="btn-group"><button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info tw-w-max dropdown-toggle" data-toggle="dropdown" aria-expanded="false">' . __('messages.actions') . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-left" role="menu"><li><a href="' . action([\App\Http\Controllers\LabelsController::class, 'show']) . '?product_id=' . $row->id . '" data-toggle="tooltip" title="' . __('lang_v1.label_help') . '"><i class="fa fa-barcode"></i> ' . __('barcode.labels') . '</a></li>';

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action([\App\Http\Controllers\ProductController::class, 'view'], [$row->id]) . '" class="view-product"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';
                        }

                        if (auth()->user()->can('product.update')) {
                            $html .=
                                '<li><a href="' . action([\App\Http\Controllers\ProductController::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $html .=
                                '<li><a href="' . action([\App\Http\Controllers\ProductController::class, 'destroy'], [$row->id]) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
                        }

                        if ($row->is_inactive == 1) {
                            $html .=
                                '<li><a href="' . action([\App\Http\Controllers\ProductController::class, 'activate'], [$row->id]) . '" class="activate-product"><i class="fas fa-check-circle"></i> ' . __('lang_v1.reactivate') . '</a></li>';
                        }

                        $html .= '<li class="divider"></li>';

                        if ($row->enable_stock == 1 && auth()->user()->can('product.opening_stock')) {
                            $html .=
                                '<li><a href="#" data-href="' . action([\App\Http\Controllers\OpeningStockController::class, 'add'], ['product_id' => $row->id]) . '" class="add-opening-stock"><i class="fa fa-database"></i> ' . __('lang_v1.add_edit_opening_stock') . '</a></li>';
                        }

                        if (auth()->user()->can('product.view')) {
                            $html .=
                                '<li><a href="' . action([\App\Http\Controllers\ProductController::class, 'productStockHistory'], [$row->id]) . '"><i class="fas fa-history"></i> ' . __('lang_v1.product_stock_history') . '</a></li>';
                        }

                        if (auth()->user()->can('product.create')) {
                            if ($selling_price_group_count > 0) {
                                $html .=
                                    '<li><a href="' . action([\App\Http\Controllers\ProductController::class, 'addSellingPrices'], [$row->id]) . '"><i class="fas fa-money-bill-alt"></i> ' . __('lang_v1.add_selling_price_group_prices') . '</a></li>';
                            }

                            $html .=
                                '<li><a href="' . action([\App\Http\Controllers\ProductController::class, 'create'], ['d' => $row->id]) . '"><i class="fa fa-copy"></i> ' . __('lang_v1.duplicate_product') . '</a></li>';
                        }

                        if (! empty($row->media->first())) {
                            $html .=
                                '<li><a href="' . $row->media->first()->display_url . '" download="' . $row->media->first()->display_name . '"><i class="fas fa-download"></i> ' . __('lang_v1.product_brochure') . '</a></li>';
                        }

                        if (auth()->user()->can('product.delete')) {
                            $product_name_attr = e(strip_tags($row->product));
                            $html .= '<li><a href="#" class="merge-product-action" data-product-id="' . $row->id . '" data-product-name="' . $product_name_attr . '"><i class="fa fa-random"></i> ' . __('lang_v1.merge_selected') . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                // In your datatable method where you define the product column
                // ... existing code ...

                // ... existing code ...
                ->editColumn('product', function ($row) use ($is_woocommerce) {
                    $product = $row->is_inactive == 1 ? $row->product . ' <span class="label bg-gray">' . __('lang_v1.inactive') . '</span>' : $row->product;

                    $product = $row->not_for_selling == 1 ? $product . ' <span class="label bg-gray">' . __('lang_v1.not_for_selling') .
                        '</span>' : $product;

                    if ($is_woocommerce && ! $row->woocommerce_disable_sync) {
                        $product = $product . '<br><i class="fab fa-wordpress"></i>';
                    }

                    return $product; // Removed the info button from here
                })
                // ... existing code ...
                ->addColumn('info_button', function ($row) {
                    $compatibilities = \App\ProductCompatibility::where('product_id', $row->id)
                        ->with(['deviceModel', 'brandCategory'])
                        ->get();

                    if ($compatibilities->isEmpty()) {
                        return '<button type="button" class="btn btn-xs btn-success open-compatibility-modal" data-product-id="' . $row->id . '" data-product-name="' . e($row->product) . '"><i class="fa fa-plus"></i></button>';
                    }

                    $html = '<div style="white-space:nowrap;">';
                    $shown = $compatibilities->take(2);
                    foreach ($shown as $compat) {
                        $brand = $compat->brandCategory ? $compat->brandCategory->name : '';
                        $model = $compat->deviceModel ? $compat->deviceModel->name : '';
                        $label = trim($brand . ' ' . $model);
                        if ($compat->from_year || $compat->to_year) {
                            $label .= ' (' . ($compat->from_year ?? '') . '-' . ($compat->to_year ?? '') . ')';
                        }
                        $html .= '<div class="label label-info" style="display:block;margin:1px 0;white-space:normal;">' . e($label) . '</div>';
                    }
                    if ($compatibilities->count() > 2) {
                        $html .= '<div class="label label-default" style="display:block;margin:1px 0;">+' . ($compatibilities->count() - 2) . '</div>';
                    }
                    $html .= '<button type="button" class="btn btn-xs btn-primary open-compatibility-modal" data-product-id="' . $row->id . '" data-product-name="' . e($row->product) . '"><i class="fa fa-eye"></i></button>';
                    $html .= '</div>';
                    return $html;
                })



                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="' . $row->image_url . '" alt="Product image" class="product-thumbnail-small"></div>';
                })
                ->editColumn('type', '@lang("lang_v1." . $type)')
                ->addColumn('mass_delete', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->id . '">';
                })
                ->editColumn('current_stock', function ($row) {
                    if ($row->enable_stock) {
                        $stock = $this->productUtil->num_f($row->current_stock, false, null, true);

                        return $stock . ' ' . $row->unit;
                    } else {
                        return '--';
                    }
                })
                //                 ->editColumn('product', function ($row) use ($is_woocommerce) {
                //     $product = $row->is_inactive == 1 ? $row->product . ' <span class="label bg-gray">' . __('lang_v1.inactive') . '</span>' : $row->product;

                //     $product = $row->not_for_selling == 1 ? $product . ' <span class="label bg-gray">' . __('lang_v1.not_for_selling') .
                //         '</span>' : $product;

                //     if ($is_woocommerce && ! $row->woocommerce_disable_sync) {
                //         $product = $product . '<br><i class="fab fa-wordpress"></i>';
                //     }

                //     // Add info button for brand and model details
                //     $product .= ' <button type="button" class="btn btn-xs btn-info product-details-btn" data-product-id="'.$row->id.'" data-toggle="modal" data-target="#productDetailsModal"><i class="fa fa-info-circle"></i></button>';

                //     return $product;
                // })
                ->addColumn(
                    'purchase_price',
                    '<div style="white-space: nowrap;">@format_currency($min_purchase_price) @if($max_purchase_price != $min_purchase_price && $type == "variable") -  @format_currency($max_purchase_price)@endif </div>'
                )
                ->addColumn(
                    'selling_price',
                    '<div style="white-space: nowrap;">@format_currency($min_price) @if($max_price != $min_price && $type == "variable") -  @format_currency($max_price)@endif </div>'
                )
                ->filterColumn('products.sku', function ($query, $keyword) {
                    $query->where('products.name', 'like', "%{$keyword}%")
                        ->orWhere('products.sku', 'like', "%{$keyword}%")
                        ->orWhereHas('variations', function ($q) use ($keyword) {
                            $q->where('sub_sku', 'like', "%{$keyword}%");
                        });
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can('product.view')) {
                            return  action([\App\Http\Controllers\ProductController::class, 'view'], [$row->id]);
                        } else {
                            return '';
                        }
                    },
                ])
                ->rawColumns(['action', 'info_button', 'image', 'mass_delete', 'product', 'selling_price', 'purchase_price', 'category', 'current_stock'])
                ->make(true);
        }

        $rack_enabled = (request()->session()->get('business.enable_racks') || request()->session()->get('business.enable_row') || request()->session()->get('business.enable_position'));

        $categories = Category::forDropdown($business_id, 'product');

        $brands = Brands::forDropdown($business_id);

        $units = Unit::forDropdown($business_id);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, false);
        $taxes = $tax_dropdown['tax_rates'];

        $business_locations = BusinessLocation::forDropdown($business_id);
        $business_locations->prepend(__('lang_v1.none'), 'none');

        if ($this->moduleUtil->isModuleInstalled('Manufacturing') && (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module'))) {
            $show_manufacturing_data = true;
        } else {
            $show_manufacturing_data = false;
        }

        //list product screen filter from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_filters_for_list_product_screen');

        $is_admin = $this->productUtil->is_admin(auth()->user());

        return view('product.index')
            ->with(compact(
                'rack_enabled',
                'categories',
                'brands',
                'units',
                'taxes',
                'business_locations',
                'show_manufacturing_data',
                'pos_module_data',
                'is_woocommerce',
                'is_admin'
            ));
    }

    /**
     * Get product brand and model details for modal
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Get product brand and model details for modal
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Get product brand and model details for modal
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getProductDetails($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $product = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->where('virtual_product', 0)
                ->where('is_client_flagged', 0)
                ->with('compatibility')
                ->first();

            $brands = [];
            $model_years = [];

            if (!empty($product->brand_category)) {
                $brand_category_ids = json_decode($product->brand_category, true);
                if (!empty($brand_category_ids) && is_array($brand_category_ids)) {
                    $brands = Category::whereIn('id', $brand_category_ids)
                        ->select('name', 'id')
                        ->get();
                }
            }

            // Get compatibility data from product_compatibility table
            if ($product->compatibility->count() > 0) {
                // Get all model IDs to fetch in a single query
                $model_ids = $product->compatibility->pluck('model_id')->filter()->unique()->toArray();
                $brand_category_ids = $product->compatibility->pluck('brand_category_id')->filter()->unique()->toArray();

                // Fetch all models in a single query
                $models = DB::table('repair_device_models')
                    ->whereIn('id', $model_ids)
                    ->pluck('name', 'id')
                    ->toArray();

                // Fetch all brand categories in a single query
                $brand_categories = Category::whereIn('id', $brand_category_ids)
                    ->pluck('name', 'id')
                    ->toArray();

                foreach ($product->compatibility as $compatibility) {
                    $model_id = $compatibility->model_id;
                    $brand_category_id = $compatibility->brand_category_id;

                    // Get model name from the models array
                    $model_name = $models[$model_id] ?? 'Unknown Model';

                    // Get brand/make name from the brand_categories array
                    $make = $brand_categories[$brand_category_id] ?? '';

                    $model_years[] = [
                        'model_id' => $model_id,
                        'brand_category_id' => $brand_category_id,
                        'make' => $make,
                        'model_name' => $make ? "$make $model_name" : $model_name,
                        'from_year' => $compatibility->from_year,
                        'to_year' => $compatibility->to_year
                    ];
                }


            }

            return view('product.partials.product_details_modal')
                ->with(compact('product', 'brands', 'model_years'));
        }
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

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for products quota
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (! $this->moduleUtil->isQuotaAvailable('products', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action([\App\Http\Controllers\ProductController::class, 'index']));
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brand_category = Category::forDropdown($business_id, 'device');
        $models = DB::table('repair_device_models')
            ->pluck('name', 'id');

        $brands = Brands::forDropdown($business_id);
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;
        $barcode_default = $this->productUtil->barcode_default();

        $default_profit_percent = request()->session()->get('business.default_profit_percent');

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        //Duplicate product
        $duplicate_product = null;
        $rack_details = null;

        $sub_categories = [];
        $sub_sub_categories = [];
        $sub_sub_sub_categories = [];
        if (! empty(request()->input('d'))) {
            $duplicate_product = Product::where('business_id', $business_id)->find(request()->input('d'));
            $duplicate_product->name .= ' (copy)';

            if (! empty($duplicate_product->category_id)) {
                $sub_categories = Category::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->category_id)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            if (! empty($duplicate_product->sub_category_id)) {
                $sub_sub_categories = Category::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->sub_category_id)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            if (! empty($duplicate_product->sub_sub_category_id)) {
                $sub_sub_sub_categories = Category::where('business_id', $business_id)
                    ->where('parent_id', $duplicate_product->sub_sub_category_id)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            //Rack details
            if (! empty($duplicate_product->id)) {
                $rack_details = $this->productUtil->getRackDetails($business_id, $duplicate_product->id);
            }
        }

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');
        $product_types = $this->product_types();

        $common_settings = session()->get('business.common_settings');
        $warranties = Warranty::forDropdown($business_id);

        //product screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_product_screen_top_view');

        return view('product.create')
            ->with(compact('categories', 'brands', 'brand_category', 'models', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'barcode_default', 'business_locations', 'duplicate_product', 'sub_categories', 'sub_sub_categories', 'sub_sub_sub_categories', 'rack_details', 'selling_price_group_count', 'module_form_parts', 'product_types', 'common_settings', 'warranties', 'pos_module_data'));
    }

    private function product_types()
    {
        //Product types also includes modifier.
        return [
            'single' => __('lang_v1.single'),
            'variable' => __('lang_v1.variable'),
            'combo' => __('lang_v1.combo'),
        ];
    }

    public function viewexitpermission()
    {
        return view('product.exit_permission');
    }

    public function exitPermission()
    {
        $user = Auth::user()->location_id;
  


        $products = DB::table('product_joborder')
            ->leftjoin('repair_job_sheets', 'repair_job_sheets.id', '=', 'product_joborder.job_order_id')
            ->leftJoin('transactions as t_under', function ($join) {
                $join->on('t_under.repair_job_sheet_id', '=', 'repair_job_sheets.id')
                     ->where('t_under.status', 'under processing');
            })
            ->leftJoin('transaction_sell_lines as tsl_under', function ($join) {
                $join->on('tsl_under.transaction_id', '=', 't_under.id')
                     ->on('tsl_under.product_id', '=', 'product_joborder.product_id');
            })
            ->leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
            ->leftjoin('contact_device', 'contact_device.id', '=', 'bookings.device_id')
            ->leftjoin('workshops', 'workshops.id', '=', 'repair_job_sheets.workshop_id')
            ->leftjoin('products', 'products.id', '=', 'product_joborder.product_id')
            ->leftjoin('categories', 'categories.id', '=', 'contact_device.device_id')
            ->leftjoin('repair_device_models', 'repair_device_models.id', '=', 'contact_device.models_id')
            ->leftjoin('business_locations', 'business_locations.id', '=', 'bookings.location_id')
            ->leftjoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftjoin('variation_location_details', 'variation_location_details.variation_id', '=', 'variations.id')
            ->where('product_joborder.delivered_status', 0)
            ->where('product_joborder.client_approval', 1)
            ->where('products.enable_stock', 1)
            ->where('bookings.location_id', $user )
            // Show only items with available qty, except allow if job sheet transaction is under processing
            ->where(function ($q) {
                $q->where('variation_location_details.qty_available', '>', 0)
                  ->orWhereNotNull('tsl_under.id');
            })

            ->select(
                'repair_job_sheets.job_sheet_no',
                'business_locations.name as location',
                'product_joborder.created_at',
                'products.name AS product',
                'products.sku AS SKU',
                'product_joborder.quantity AS Quantity',
                'workshops.name AS workshop',
                'product_joborder.id',
                'product_joborder.out_for_deliver',
                'contact_device.chassis_number',
                'contact_device.plate_number',
                'contact_device.color',
                'categories.name AS Category',
                'repair_device_models.name AS model',
            )
            ->get();

    
     
        return Datatables::of($products)
            ->addColumn('action', function ($product) {
                if ($product->out_for_deliver == 0) {
                    return '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max" onclick="window.location.href=\'' . route('editDeliverStatus', $product->id) . '\'">' . __('messages.active') . '</button>';
                } else {
                    return '<p>' . __('messages.product_exit') . '</p>';
                }
            })
            ->rawColumns(['action'])
            ->make(true);

        
    }

    public function editDeliverStatus($id)
    {
        DB::table('product_joborder')->where('id', $id)->update([
            'out_for_deliver' => 1
            // 'delivered_status' => 1
        ]);
    $user = Auth::user();
    $fcmData = [
    'message' => [
        'notification' => [
            'title' => 'Spare parts were added to the Job Order: ' . $id,
            'title_ar' => 'تمت إضافة قطع غيار إلى أمر الشغل: ' . $id,
        ],
        'data' => [
            'body' => 'Spare parts were added to the Job Order ID: ' . $id,
            'body_ar' => 'تمت إضافة قطع غيار إلى أمر الشغل رقم: ' . $id,
        ],                      
    ],
];
     $Util = new \App\Utils\Util();
        $adminsAndCashers =$Util->get_adminsAndCashers($user->business_id);
        $admin_ids = $adminsAndCashers->pluck('id')->toArray();

    if ( !empty($admin_ids)) {
        dispatch(new \App\Jobs\SendNotifications(
            $admin_ids,
            $fcmData,
            'SparePartsAddedToJobOrder',
            $fcmData
        ));
    }
        return redirect()->route('viewexitpermission');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request);
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        // return response()->json($request->all());
        try {
            $business_id = $request->session()->get('user.business_id');
            $form_fields = ['name', 'brand_category', 'manufacturing_year', 'brand_id', 'unit_id', 'category_id', 'tax', 'type', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_description', 'sub_unit_ids', 'preparation_time_in_minutes', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_custom_field5', 'product_custom_field6', 'product_custom_field7', 'product_custom_field8', 'product_custom_field9', 'product_custom_field10', 'product_custom_field11', 'product_custom_field12', 'product_custom_field13', 'product_custom_field14', 'product_custom_field15', 'product_custom_field16', 'product_custom_field17', 'product_custom_field18', 'product_custom_field19', 'product_custom_field20', 'is_ecom',];

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (! empty($module_form_fields)) {
                $form_fields = array_merge($form_fields, $module_form_fields);
            }

            $product_details = $request->only($form_fields);
            $product_details['business_id'] = $business_id;
            $product_details['created_by'] = $request->session()->get('user.id');

            $product_details['enable_stock'] = (! empty($request->input('enable_stock')) && $request->input('enable_stock') == 1) ? 1 : 0;
            $product_details['not_for_selling'] = (! empty($request->input('not_for_selling')) && $request->input('not_for_selling') == 1) ? 1 : 0;
            $product_details['is_ecom'] = (! empty($request->input('is_ecom')) && $request->input('is_ecom') == 1) ? 1 : 0;

            if ($request->has('repair_model_id')) {
                $product_details['repair_model_id'] = json_encode($request->input('repair_model_id'));
            }
            if ($request->has('brand_category')) {
                $product_details['brand_category'] = json_encode($request->input('brand_category'));
            }
            if ($request->has('category_id')) {
                $product_details['category_id'] = $request->input('category_id');
            }



            if (! empty($request->input('sub_category_id'))) {
                $product_details['sub_category_id'] = $request->input('sub_category_id');
            }

            if (! empty($request->input('sub_sub_category_id'))) {
                $product_details['sub_sub_category_id'] = $request->input('sub_sub_category_id');
            }

            if (! empty($request->input('sub_sub_sub_category_id'))) {
                $product_details['sub_sub_sub_category_id'] = $request->input('sub_sub_sub_category_id');
            }



            if (! empty($request->input('secondary_unit_id'))) {
                $product_details['secondary_unit_id'] = $request->input('secondary_unit_id');
            }

            if (empty($product_details['sku'])) {
                $product_details['sku'] = ' ';
            }

            if (! empty($product_details['alert_quantity'])) {
                $product_details['alert_quantity'] = $this->productUtil->num_uf($product_details['alert_quantity']);
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (! empty($request->input('expiry_period_type')) && ! empty($request->input('expiry_period')) && ! empty($expiry_enabled) && ($product_details['enable_stock'] == 1)) {
                $product_details['expiry_period_type'] = $request->input('expiry_period_type');
                $product_details['expiry_period'] = $this->productUtil->num_uf($request->input('expiry_period'));
            }

            if (! empty($request->input('enable_sr_no')) && $request->input('enable_sr_no') == 1) {
                $product_details['enable_sr_no'] = 1;
            }

            //upload document
            $product_details['image'] = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'), 'image');
            $common_settings = session()->get('business.common_settings');

            $product_details['warranty_id'] = ! empty($request->input('warranty_id')) ? $request->input('warranty_id') : null;

            DB::beginTransaction();

            $product = Product::create($product_details);

            event(new ProductsCreatedOrModified($product_details, 'added'));

            if (empty(trim($request->input('sku')))) {
                $sku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $sku;
                $product->save();
            }

            // Save compatibility data from the form
            if ($request->has('compatibility')) {
                $compatibilityData = $request->input('compatibility');

                // Save new compatibility data
                foreach ($compatibilityData as $data) {
                    // Validate that brand_id and model_id are present before saving
                    if (empty($data['brand_category_id']) || empty($data['model_id'])) {
                        continue; // Skip rows with missing brand or model
                    }

                    $compatibility = new \App\ProductCompatibility();
                    $compatibility->product_id = $product->id;
                    $compatibility->model_id = $data['model_id'];
                    $compatibility->brand_category_id = $data['brand_category_id'];
                    $compatibility->from_year = $data['from_year'] ?? null;
                    $compatibility->to_year = $data['to_year'] ?? null;
                    $compatibility->motor_cc = $data['motor_cc'] ?? null;
                    $compatibility->save();
                }
            }
            // Backward compatibility for old form format
            else if ($request->has(['model_name']) && !empty(array_filter($request->input('model_name'))) &&
                $request->has(['from_year', 'to_year']) && !empty(array_filter($request->input('from_year'))) && !empty(array_filter($request->input('to_year')))) {

                // Get the arrays of model names and years
                $modelNames = $request->input('model_name');
                $fromYears = $request->input('from_year');
                $toYears = $request->input('to_year');

                // Save compatibility data
                for ($i = 0; $i < count($modelNames); $i++) {
                    if (!empty($modelNames[$i]) && !empty($fromYears[$i]) && !empty($toYears[$i])) {
                        // Create compatibility record
                        $compatibility = new \App\ProductCompatibility();
                        $compatibility->product_id = $product->id;
                        $compatibility->model_id = $modelNames[$i];
                        $compatibility->brand_category_id = null; // No brand category in old format
                        $compatibility->from_year = $fromYears[$i];
                        $compatibility->to_year = $toYears[$i];
                        $compatibility->save();
                    }
                }
            }

            //Add product locations
            $product_locations = $request->input('product_locations');
            if (! empty($product_locations)) {
                $product->product_locations()->sync($product_locations);
            }

            if ($product->type == 'single') {
                // Get values with defaults if not present
                $single_dpp = $request->has('single_dpp') ? $request->input('single_dpp') : 0;
                $single_dpp_inc_tax = $request->has('single_dpp_inc_tax') ? $request->input('single_dpp_inc_tax') : 0;
                $profit_percent = $request->has('profit_percent') ? $request->input('profit_percent') : 0;
                $single_dsp = $request->has('single_dsp') ? $request->input('single_dsp') : 0;
                $single_dsp_inc_tax = $request->has('single_dsp_inc_tax') ? $request->input('single_dsp_inc_tax') : 0;

                $this->productUtil->createSingleProductVariation(
                    $product->id,
                    $product->sku,
                    $single_dpp,
                    $single_dpp_inc_tax,
                    $profit_percent,
                    $single_dsp,
                    $single_dsp_inc_tax
                );
            } elseif ($product->type == 'variable') {
                if (! empty($request->input('product_variation'))) {
                    $input_variations = $request->input('product_variation');
                    $sku_type = $request->input('sku_type', null);

                    $this->productUtil->createVariableProductVariations($product->id, $input_variations, $sku_type);
                }
            } elseif ($product->type == 'combo') {
                //Create combo_variations array by combining variation_id and quantity.
                $combo_variations = [];
                if (! empty($request->input('composition_variation_id'))) {
                    $composition_variation_id = $request->input('composition_variation_id');
                    $quantity = $request->input('quantity');
                    $unit = $request->input('unit');

                    foreach ($composition_variation_id as $key => $value) {
                        $combo_variations[] = [
                            'variation_id' => $value,
                            'quantity' => $this->productUtil->num_uf($quantity[$key]),
                            'unit_id' => $unit[$key],
                        ];
                    }
                }

                // Get values with defaults if not present
                $item_level_purchase_price_total = $request->has('item_level_purchase_price_total') ? $request->input('item_level_purchase_price_total') : 0;
                $purchase_price_inc_tax = $request->has('purchase_price_inc_tax') ? $request->input('purchase_price_inc_tax') : 0;
                $profit_percent = $request->has('profit_percent') ? $request->input('profit_percent') : 0;
                $selling_price = $request->has('selling_price') ? $request->input('selling_price') : 0;
                $selling_price_inc_tax = $request->has('selling_price_inc_tax') ? $request->input('selling_price_inc_tax') : 0;

                $this->productUtil->createSingleProductVariation(
                    $product->id,
                    $product->sku,
                    $item_level_purchase_price_total,
                    $purchase_price_inc_tax,
                    $profit_percent,
                    $selling_price,
                    $selling_price_inc_tax,
                    $combo_variations
                );
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (! empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            //Set Module fields
            if (! empty($request->input('has_module_data'))) {
                $this->moduleUtil->getModuleData('after_product_saved', ['product' => $product, 'request' => $request]);
            }

            Media::uploadMedia($product->business_id, $product, $request, 'product_brochure', true);

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('product.product_added_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];

            return redirect('products')->with('status', $output);
        }

        if ($request->input('submit_type') == 'submit_n_add_opening_stock') {
            return redirect()->action(
                [\App\Http\Controllers\OpeningStockController::class, 'add'],
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                [\App\Http\Controllers\ProductController::class, 'addSellingPrices'],
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                [\App\Http\Controllers\ProductController::class, 'create']
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $details = $this->productUtil->getRackDetails($business_id, $id, true);

        return view('product.show')->with(compact('details'));
    }


    public function edit($id)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $categories = Category::forDropdown($business_id, 'product');
        $brand_category = Category::forDropdown($business_id, 'device');

        // Get all models in a single query and cache them
        $models = DB::table('repair_device_models')->pluck('name', 'id');
        $brands = Brands::forDropdown($business_id);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;

        // Eager load all necessary relationships in a single query
        $product = Product::where('business_id', $business_id)
            ->with(['product_locations', 'compatibility'])
            ->where('id', $id)
            ->firstOrFail();

        // Decode JSON fields safely
        $product->brand_category = json_decode($product->brand_category, true) ?: [];

        // Get compatibility data from product_compatibility table
        $compatibility_data = [];

        if ($product->compatibility->count() > 0) {
            // Format compatibility data from the relationship
            foreach ($product->compatibility as $compat) {
                // Get model details
                $model_id = $compat->model_id;
                $model_display_name = '';

                // If model_id exists, get the model name from the repair_device_models table
                if ($model_id) {
                    $model = DB::table('repair_device_models')->find($model_id);
                    if ($model) {
                        $model_display_name = $model->name;
                    }
                }

                $compatibility_data[] = [
                    'id' => $compat->id,
                    'model_id' => $model_id,
                    'model_display_name' => $model_display_name,
                    'brand_category_id' => $compat->brand_category_id,
                    'from_year' => $compat->from_year,
                    'to_year' => $compat->to_year,
                    'motor_cc' => $compat->motor_cc ?? ''
                ];
            }
        }

        // dd($product->compatibility);

        // Sub-category
        $sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', $product->category_id)
            ->pluck('name', 'id')
            ->toArray();
        $sub_categories = ['' => 'None'] + $sub_categories;

        $sub_sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', $product->sub_category_id)
            ->pluck('name', 'id')
            ->toArray();
        $sub_sub_categories = ['' => 'None'] + $sub_sub_categories;

        $sub_sub_sub_categories = Category::where('business_id', $business_id)
            ->where('parent_id', $product->sub_sub_category_id)
            ->pluck('name', 'id')
            ->toArray();
        $sub_sub_sub_categories = ['' => 'None'] + $sub_sub_sub_categories;

        $default_profit_percent = request()->session()->get('business.default_profit_percent');

        // Get units
        $units = Unit::forDropdown($business_id, true);
        $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, true);

        // Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        // Rack details
        $rack_details = $this->productUtil->getRackDetails($business_id, $id);

        $selling_price_group_count = SellingPriceGroup::countSellingPriceGroups($business_id);

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');
        $product_types = $this->product_types();
        $common_settings = session()->get('business.common_settings');
        $warranties = Warranty::forDropdown($business_id);

        // For single products, preload variation details so the price form
        // can be rendered server-side on the edit page.
        $product_deatails = null;
        if ($product->type == 'single') {
            $product_deatails = ProductVariation::where('product_id', $product->id)
                ->with(['variations', 'variations.media'])
                ->first();
        }

        // Product screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_product_screen_top_view');

        $alert_quantity = ! is_null($product->alert_quantity)
            ? $this->productUtil->num_f($product->alert_quantity, false, null, true)
            : null;


        return view('product.edit')
            ->with(compact(
                'categories',
                'brand_category',
                'compatibility_data',
                'models',
                'brands',
                'units',
                'sub_units',
                'taxes',
                'tax_attributes',
                'barcode_types',
                'product',
                'sub_categories',
                'sub_sub_categories',
                'sub_sub_sub_categories',
                'default_profit_percent',
                'business_locations',
                'rack_details',
                'selling_price_group_count',
                'module_form_parts',
                'product_types',
                'common_settings',
                'warranties',
                'pos_module_data',
                'product_deatails',
                'alert_quantity'
            ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $product_details = $request->only(['name', 'brand_id', 'unit_id', 'category_id', 'tax', 'barcode_type', 'sku', 'alert_quantity', 'tax_type', 'weight', 'product_description', 'sub_unit_ids', 'preparation_time_in_minutes', 'product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4', 'product_custom_field5', 'product_custom_field6', 'product_custom_field7', 'product_custom_field8', 'product_custom_field9', 'product_custom_field10', 'product_custom_field11', 'product_custom_field12', 'product_custom_field13', 'product_custom_field14', 'product_custom_field15', 'product_custom_field16', 'product_custom_field17', 'product_custom_field18', 'product_custom_field19', 'product_custom_field20', 'is_ecom',]);

            DB::beginTransaction();

            $product = Product::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['product_variations'])
                ->first();

            $module_form_fields = $this->moduleUtil->getModuleFormField('product_form_fields');
            if (! empty($module_form_fields)) {
                foreach ($module_form_fields as $column) {
                    $product->$column = $request->input($column);
                }
            }
            // Save compatibility data from the form
            if ($request->has('compatibility')) {
                // Delete existing compatibility data
                $product->compatibility()->delete();

                $compatibilityData = $request->input('compatibility');

                // Save new compatibility data
                foreach ($compatibilityData as $data) {
                    // Validate that brand_id and model_id are present before saving
                    if (empty($data['brand_category_id']) || empty($data['model_id'])) {
                        continue; // Skip rows with missing brand or model
                    }

                    $compatibility = new \App\ProductCompatibility();
                    $compatibility->product_id = $product->id;
                    $compatibility->model_id = $data['model_id'];
                    $compatibility->brand_category_id = $data['brand_category_id'];
                    $compatibility->from_year = $data['from_year'] ?? null;
                    $compatibility->to_year = $data['to_year'] ?? null;
                    $compatibility->motor_cc = $data['motor_cc'] ?? null;
                    $compatibility->save();
                }

                // Set manufacturing_year to null since we're now using the compatibility table
                $product->manufacturing_year = null;
            }



            $product->repair_model_id = !empty($request->input('repair_model_id'))
                ? (is_array($request->input('repair_model_id')) ? $request->input('repair_model_id') : json_decode($request->input('repair_model_id'), true))
                : null;

            $product->brand_category = !empty($request->input('brand_category'))
                ? (is_array($request->input('brand_category')) ? $request->input('brand_category') : json_decode($request->input('brand_category'), true))
                : null;


            $product->name = $product_details['name'] ?? $product->name;
            $product->brand_id = $product_details['brand_id'] ?? $product->brand_id;
            $product->unit_id = $product_details['unit_id'] ?? $product->unit_id;
            $product->category_id = $product_details['category_id'] ?? $product->category_id;
            $product->tax = $product_details['tax'] ?? $product->tax;
            $product->barcode_type = $product_details['barcode_type'] ?? $product->barcode_type;
            $product->sku = $product_details['sku'] ?? $product->sku;
            $product->alert_quantity = ! empty($product_details['alert_quantity']) ? $this->productUtil->num_uf($product_details['alert_quantity']) : ($product_details['alert_quantity'] ?? $product->alert_quantity);
            $product->tax_type = $product_details['tax_type'] ?? $product->tax_type;
            $product->weight = $product_details['weight'] ?? $product->weight;
            $product->product_custom_field1 = $product_details['product_custom_field1'] ?? '';
            $product->product_custom_field2 = $product_details['product_custom_field2'] ?? '';
            $product->product_custom_field3 = $product_details['product_custom_field3'] ?? '';
            $product->product_custom_field4 = $product_details['product_custom_field4'] ?? '';
            $product->product_custom_field5 = $product_details['product_custom_field5'] ?? '';
            $product->product_custom_field6 = $product_details['product_custom_field6'] ?? '';
            $product->product_custom_field7 = $product_details['product_custom_field7'] ?? '';
            $product->product_custom_field8 = $product_details['product_custom_field8'] ?? '';
            $product->product_custom_field9 = $product_details['product_custom_field9'] ?? '';
            $product->product_custom_field10 = $product_details['product_custom_field10'] ?? '';
            $product->product_custom_field11 = $product_details['product_custom_field11'] ?? '';
            $product->product_custom_field12 = $product_details['product_custom_field12'] ?? '';
            $product->product_custom_field13 = $product_details['product_custom_field13'] ?? '';
            $product->product_custom_field14 = $product_details['product_custom_field14'] ?? '';
            $product->product_custom_field15 = $product_details['product_custom_field15'] ?? '';
            $product->product_custom_field16 = $product_details['product_custom_field16'] ?? '';
            $product->product_custom_field17 = $product_details['product_custom_field17'] ?? '';
            $product->product_custom_field18 = $product_details['product_custom_field18'] ?? '';
            $product->product_custom_field19 = $product_details['product_custom_field19'] ?? '';
            $product->product_custom_field20 = $product_details['product_custom_field20'] ?? '';

            $product->product_description = $product_details['product_description'] ?? $product->product_description;
            $product->sub_unit_ids = ! empty($product_details['sub_unit_ids']) ? $product_details['sub_unit_ids'] : $product->sub_unit_ids;
            $product->preparation_time_in_minutes = $product_details['preparation_time_in_minutes'] ?? $product->preparation_time_in_minutes;
            $product->warranty_id = ! empty($request->input('warranty_id')) ? $request->input('warranty_id') : null;
            $product->secondary_unit_id = ! empty($request->input('secondary_unit_id')) ? $request->input('secondary_unit_id') : null;

            if (! empty($request->input('enable_stock')) && $request->input('enable_stock') == 1) {
                $product->enable_stock = 1;
            } else {
                $product->enable_stock = 0;
            }

            $product->not_for_selling = (! empty($request->input('not_for_selling')) && $request->input('not_for_selling') == 1) ? 1 : 0;

            $product->is_ecom = (! empty($request->input('is_ecom')) && $request->input('is_ecom') == 1) ? 1 : 0;

            if (! empty($request->input('sub_category_id'))) {
                $product->sub_category_id = $request->input('sub_category_id');
            } else {
                $product->sub_category_id = null;
            }

            if (! empty($request->input('sub_sub_category_id'))) {
                $product->sub_sub_category_id = $request->input('sub_sub_category_id');
            } else {
                $product->sub_sub_category_id = null;
            }

            if (! empty($request->input('sub_sub_sub_category_id'))) {
                $product->sub_sub_sub_category_id = $request->input('sub_sub_sub_category_id');
            } else {
                $product->sub_sub_sub_category_id = null;
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (! empty($expiry_enabled)) {
                if (! empty($request->input('expiry_period_type')) && ! empty($request->input('expiry_period')) && ($product->enable_stock == 1)) {
                    $product->expiry_period_type = $request->input('expiry_period_type');
                    $product->expiry_period = $this->productUtil->num_uf($request->input('expiry_period'));
                } else {
                    $product->expiry_period_type = null;
                    $product->expiry_period = null;
                }
            }

            if (! empty($request->input('enable_sr_no')) && $request->input('enable_sr_no') == 1) {
                $product->enable_sr_no = 1;
            } else {
                $product->enable_sr_no = 0;
            }

            //upload document
            $file_name = $this->productUtil->uploadFile($request, 'image', config('constants.product_img_path'), 'image');
            if (! empty($file_name)) {

                //If previous image found then remove
                if (! empty($product->image_path) && file_exists($product->image_path)) {
                    unlink($product->image_path);
                }

                $product->image = $file_name;
                //If product image is updated update woocommerce media id
                if (! empty($product->woocommerce_media_id)) {
                    $product->woocommerce_media_id = null;
                }
            }

            $product->save();
            $product->touch();

            event(new ProductsCreatedOrModified($product, 'updated'));

            //Add product locations
            $product_locations = ! empty($request->input('product_locations')) ?
                $request->input('product_locations') : [];

            $permitted_locations = auth()->user()->permitted_locations();
            //If not assigned location exists don't remove it
            if ($permitted_locations != 'all') {
                $existing_product_locations = $product->product_locations()->pluck('id');

                foreach ($existing_product_locations as $pl) {
                    if (! in_array($pl, $permitted_locations)) {
                        $product_locations[] = $pl;
                    }
                }
            }

            $product->product_locations()->sync($product_locations);

            if ($product->type == 'single') {
                $single_data = $request->only(['single_variation_id', 'single_dpp', 'single_dpp_inc_tax', 'single_dsp_inc_tax', 'profit_percent', 'single_dsp']);
                $tax_rate = 0;
                if (! empty($product->tax)) {
                    $tax_rate = (float) TaxRate::where('id', $product->tax)->value('amount');
                }

                // Check if single_variation_id is present in the request
                if (!empty($single_data['single_variation_id'])) {
                    $variation = Variation::where('id', $single_data['single_variation_id'])
                        ->where('product_id', $product->id)
                        ->first();

                    if (empty($variation)) {
                        $variation = Variation::where('product_variation_id', $single_data['single_variation_id'])
                            ->where('product_id', $product->id)
                            ->first();
                    }

                    if (empty($variation)) {
                        $variation = Variation::where('product_id', $product->id)
                            ->orderBy('id', 'asc')
                            ->first();
                    }

                    if ($variation) {
                        // Only update fields that are present in the request
                        $variation->sub_sku = $product->sku;

                        if (isset($single_data['single_dpp'])) {
                            $variation->default_purchase_price = $this->productUtil->num_uf($single_data['single_dpp']);
                        }

                        if (isset($single_data['single_dpp_inc_tax'])) {
                            $variation->dpp_inc_tax = $this->productUtil->num_uf($single_data['single_dpp_inc_tax']);
                        }

                        if (isset($single_data['profit_percent'])) {
                            $variation->profit_percent = $this->productUtil->num_uf($single_data['profit_percent']);
                        }

                        $single_dsp = !empty($single_data['single_dsp']) ? $this->productUtil->num_uf($single_data['single_dsp']) : null;
                        $single_dsp_inc_tax = !empty($single_data['single_dsp_inc_tax']) ? $this->productUtil->num_uf($single_data['single_dsp_inc_tax']) : null;

                        if (is_null($single_dsp) && ! is_null($single_dsp_inc_tax)) {
                            $single_dsp = $this->productUtil->calc_percentage_base($single_dsp_inc_tax, $tax_rate);
                        }

                        if (is_null($single_dsp_inc_tax) && ! is_null($single_dsp)) {
                            $single_dsp_inc_tax = $this->productUtil->calc_percentage($single_dsp, $tax_rate, $single_dsp);
                        }

                        if (! is_null($single_dsp)) {
                            $variation->default_sell_price = $single_dsp;
                        }

                        if (! is_null($single_dsp_inc_tax)) {
                            $variation->sell_price_inc_tax = $single_dsp_inc_tax;
                        }

                        $variation->save();

                        Media::uploadMedia($product->business_id, $variation, $request, 'variation_images');
                    }
                }
            } elseif ($product->type == 'variable') {
                //Update existing variations
                $input_variations_edit = $request->get('product_variation_edit');
                if (! empty($input_variations_edit)) {
                    $sku_type = $request->input('sku_type', null);
                    $this->productUtil->updateVariableProductVariations($product->id, $input_variations_edit, $sku_type);
                }

                //Add new variations created.
                $input_variations = $request->input('product_variation');
                if (! empty($input_variations)) {
                    $sku_type = $request->input('sku_type', null);
                    $this->productUtil->createVariableProductVariations($product->id, $input_variations, $sku_type);
                }
            } elseif ($product->type == 'combo') {

                //Create combo_variations array by combining variation_id and quantity.
                $combo_variations = [];
                if (! empty($request->input('composition_variation_id'))) {
                    $composition_variation_id = $request->input('composition_variation_id');
                    $quantity = $request->input('quantity');
                    $unit = $request->input('unit');

                    foreach ($composition_variation_id as $key => $value) {
                        $combo_variations[] = [
                            'variation_id' => $value,
                            'quantity' => $quantity[$key],
                            'unit_id' => $unit[$key],
                        ];
                    }
                }

                // Check if combo_variation_id is present in the request
                if ($request->has('combo_variation_id')) {
                    $variation = Variation::find($request->input('combo_variation_id'));

                    if ($variation) {
                        $variation->sub_sku = $product->sku;

                        if ($request->has('item_level_purchase_price_total')) {
                            $variation->default_purchase_price = $this->productUtil->num_uf($request->input('item_level_purchase_price_total'));
                        }

                        if ($request->has('purchase_price_inc_tax')) {
                            $variation->dpp_inc_tax = $this->productUtil->num_uf($request->input('purchase_price_inc_tax'));
                        }

                        if ($request->has('profit_percent')) {
                            $variation->profit_percent = $this->productUtil->num_uf($request->input('profit_percent'));
                        }

                        if ($request->has('selling_price')) {
                            $variation->default_sell_price = $this->productUtil->num_uf($request->input('selling_price'));
                        }

                        if ($request->has('selling_price_inc_tax')) {
                            $variation->sell_price_inc_tax = $this->productUtil->num_uf($request->input('selling_price_inc_tax'));
                        }

                        if (!empty($combo_variations)) {
                            $variation->combo_variations = $combo_variations;
                        }

                        $variation->save();
                    }
                }
            }

            //Add product racks details.
            $product_racks = $request->get('product_racks', null);
            if (! empty($product_racks)) {
                $this->productUtil->addRackDetails($business_id, $product->id, $product_racks);
            }

            $product_racks_update = $request->get('product_racks_update', null);
            if (! empty($product_racks_update)) {
                $this->productUtil->updateRackDetails($business_id, $product->id, $product_racks_update);
            }

            //Set Module fields
            if (! empty($request->input('has_module_data'))) {
                $this->moduleUtil->getModuleData('after_product_saved', ['product' => $product, 'request' => $request]);
            }

            Media::uploadMedia($product->business_id, $product, $request, 'product_brochure', true);

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('product.product_updated_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        if ($request->input('submit_type') == 'update_n_edit_opening_stock') {
            return redirect()->action(
                [\App\Http\Controllers\OpeningStockController::class, 'add'],
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'submit_n_add_selling_prices') {
            return redirect()->action(
                [\App\Http\Controllers\ProductController::class, 'addSellingPrices'],
                [$product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                [\App\Http\Controllers\ProductController::class, 'create']
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $can_be_deleted = true;
                $error_msg = '';

                //Check if any purchase or transfer exists
                $count = PurchaseLine::join(
                    'transactions as T',
                    'purchase_lines.transaction_id',
                    '=',
                    'T.id'
                )
                    ->whereIn('T.type', ['purchase'])
                    ->where('T.business_id', $business_id)
                    ->where('purchase_lines.product_id', $id)
                    ->count();
                if ($count > 0) {
                    $can_be_deleted = false;
                    $error_msg = __('lang_v1.purchase_already_exist');
                } else {
                    //Check if any opening stock sold
                    $count = PurchaseLine::join(
                        'transactions as T',
                        'purchase_lines.transaction_id',
                        '=',
                        'T.id'
                    )
                        ->where('T.type', 'opening_stock')
                        ->where('T.business_id', $business_id)
                        ->where('purchase_lines.product_id', $id)
                        ->where('purchase_lines.quantity_sold', '>', 0)
                        ->count();
                    if ($count > 0) {
                        $can_be_deleted = false;
                        $error_msg = __('lang_v1.opening_stock_sold');
                    } else {
                        //Check if any stock is adjusted
                        $count = PurchaseLine::join(
                            'transactions as T',
                            'purchase_lines.transaction_id',
                            '=',
                            'T.id'
                        )
                            ->where('T.business_id', $business_id)
                            ->where('purchase_lines.product_id', $id)
                            ->where('purchase_lines.quantity_adjusted', '>', 0)
                            ->count();
                        if ($count > 0) {
                            $can_be_deleted = false;
                            $error_msg = __('lang_v1.stock_adjusted');
                        }
                    }
                }

                $product = Product::where('id', $id)
                    ->where('business_id', $business_id)
                    ->with('variations')
                    ->first();

                // check for enable stock = 0 product
                if ($product->enable_stock == 0) {
                    $t_count = TransactionSellLine::join(
                        'transactions as T',
                        'transaction_sell_lines.transaction_id',
                        '=',
                        'T.id'
                    )
                        ->where('T.business_id', $business_id)
                        ->where('transaction_sell_lines.product_id', $id)
                        ->count();

                    if ($t_count > 0) {
                        $can_be_deleted = false;
                        $error_msg = "can't delete product exit in sell";
                    }
                }

                //Check if product is added as an ingredient of any recipe
                if ($this->moduleUtil->isModuleInstalled('Manufacturing')) {
                    $variation_ids = $product->variations->pluck('id');

                    $exists_as_ingredient = \Modules\Manufacturing\Entities\MfgRecipeIngredient::whereIn('variation_id', $variation_ids)
                        ->exists();
                    if ($exists_as_ingredient) {
                        $can_be_deleted = false;
                        $error_msg = __('manufacturing::lang.added_as_ingredient');
                    }
                }

                if ($can_be_deleted) {
                    if (! empty($product)) {
                        DB::beginTransaction();
                        //Delete variation location details by variation_id (foreign key constraint)
                        $variation_ids = $product->variations->pluck('id');
                        VariationLocationDetails::whereIn('variation_id', $variation_ids)
                            ->delete();
                        $product->delete();
                        event(new ProductsCreatedOrModified($product, 'deleted'));
                        DB::commit();
                    }

                    $output = [
                        'success' => true,
                        'msg' => __('lang_v1.product_delete_success'),
                    ];
                } else {
                    $output = [
                        'success' => false,
                        'msg' => $error_msg,
                    ];
                }
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Get subcategories list for a category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSubCategories(Request $request)
    {
        if (! empty($request->input('cat_id'))) {
            $category_id = $request->input('cat_id');
            $business_id = $request->session()->get('user.business_id');
            $sub_categories = Category::where('business_id', $business_id)
                ->where('parent_id', $category_id)
                ->select(['name', 'id'])
                ->get();
            $html = '<option value="">None</option>';
            if (! empty($sub_categories)) {
                foreach ($sub_categories as $sub_category) {
                    $html .= '<option value="' . $sub_category->id . '">' . $sub_category->name . '</option>';
                }
            }
            echo $html;
            exit;
        }
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductVariationFormPart(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $action = $request->input('action');
        if ($request->input('action') == 'add') {
            if ($request->input('type') == 'single') {
                return view('product.partials.single_product_form_part')
                    ->with(['profit_percent' => $profit_percent]);
            } elseif ($request->input('type') == 'variable') {
                $variation_templates = VariationTemplate::where('business_id', $business_id)->pluck('name', 'id')->toArray();
                $variation_templates = ['' => __('messages.please_select')] + $variation_templates;

                return view('product.partials.variable_product_form_part')
                    ->with(compact('variation_templates', 'profit_percent', 'action'));
            } elseif ($request->input('type') == 'combo') {
                return view('product.partials.combo_product_form_part')
                    ->with(compact('profit_percent', 'action'));
            }
        } elseif ($request->input('action') == 'edit' || $request->input('action') == 'duplicate') {
            $product_id = $request->input('product_id');
            $action = $request->input('action');
            if ($request->input('type') == 'single') {
                $product_deatails = ProductVariation::where('product_id', $product_id)
                    ->with(['variations', 'variations.media'])
                    ->first();

                return view('product.partials.edit_single_product_form_part')
                    ->with(compact('product_deatails', 'action'));
            } elseif ($request->input('type') == 'variable') {
                $product_variations = ProductVariation::where('product_id', $product_id)
                    ->with(['variations', 'variations.media'])
                    ->get();

                return view('product.partials.variable_product_form_part')
                    ->with(compact('product_variations', 'profit_percent', 'action'));
            } elseif ($request->input('type') == 'combo') {
                $product_deatails = ProductVariation::where('product_id', $product_id)
                    ->with(['variations', 'variations.media'])
                    ->first();
                $combo_variations = $this->productUtil->__getComboProductDetails($product_deatails['variations'][0]->combo_variations, $business_id);

                $variation_id = $product_deatails['variations'][0]->id;
                $profit_percent = $product_deatails['variations'][0]->profit_percent;

                return view('product.partials.combo_product_form_part')
                    ->with(compact('combo_variations', 'profit_percent', 'action', 'variation_id'));
            }
        }
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getVariationValueRow(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $variation_index = $request->input('variation_row_index');
        $value_index = $request->input('value_index') + 1;

        $row_type = $request->input('row_type', 'add');

        return view('product.partials.variation_value_row')
            ->with(compact('profit_percent', 'variation_index', 'value_index', 'row_type'));
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductVariationRow(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $variation_templates = VariationTemplate::where('business_id', $business_id)
            ->pluck('name', 'id')->toArray();
        $variation_templates = ['' => __('messages.please_select')] + $variation_templates;

        $row_index = $request->input('row_index', 0);
        $action = $request->input('action');

        return view('product.partials.product_variation_row')
            ->with(compact('variation_templates', 'row_index', 'action', 'profit_percent'));
    }

    /**
     * Get product form parts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getVariationTemplate(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business = Business::findorfail($business_id);
        $profit_percent = $business->default_profit_percent;

        $template = VariationTemplate::where('id', $request->input('template_id'))
            ->with(['values'])
            ->first();
        $row_index = $request->input('row_index');

        $values = [];
        foreach ($template->values as $v) {
            $values[] = [
                'id' => $v->id,
                'text' => $v->name,
            ];
        }

        return [
            'html' => view('product.partials.product_variation_template')
                ->with(compact('template', 'row_index', 'profit_percent'))->render(),
            'values' => $values,
        ];
    }

    /**
     * Return the view for combo product row
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getComboProductEntryRow(Request $request)
    {
        if (request()->ajax()) {
            $product_id = $request->input('product_id');
            $variation_id = $request->input('variation_id');
            $business_id = $request->session()->get('user.business_id');

            if (! empty($product_id)) {
                $product = Product::where('id', $product_id)
                    ->with(['unit'])
                    ->first();

                $query = Variation::where('product_id', $product_id)
                    ->with(['product_variation']);

                if ($variation_id !== '0') {
                    $query->where('id', $variation_id);
                }
                $variations = $query->get();

                $sub_units = $this->productUtil->getSubUnits($business_id, $product['unit']->id);

                return view('product.partials.combo_product_entry_row')
                    ->with(compact('product', 'variations', 'sub_units'));
            }
        }
    }

    /**
     * Retrieves products list.
     *
     * @param  string  $q
     * @param  bool  $check_qty
     * @return JSON
     */
    public function getProducts()
    {
        if (request()->ajax()) {
            $search_term = request()->input('term', '');
            $location_id = request()->input('location_id', null);
            $check_qty = request()->input('check_qty', false);
            $price_group_id = request()->input('price_group', null);
            $business_id = request()->session()->get('user.business_id');
            $not_for_selling = request()->get('not_for_selling', null);
            $price_group_id = request()->input('price_group', '');
            $product_types = request()->get('product_types', []);

            $search_fields = request()->get('search_fields', ['name', 'sku']);
            if (in_array('sku', $search_fields)) {
                $search_fields[] = 'sub_sku';
            }

            $result = $this->productUtil->filterProduct($business_id, $search_term, $location_id, $not_for_selling, $price_group_id, $product_types, $search_fields, $check_qty);

            return json_encode($result);
        }
    }

    /**
     * Retrieves products list without variation list
     *
     * @param  string  $q
     * @param  bool  $check_qty
     * @return JSON
     */
    public function getProductsWithoutVariations()
    {
        if (request()->ajax()) {
            $term = request()->input('term', '');
            //$location_id = request()->input('location_id', '');

            //$check_qty = request()->input('check_qty', false);

            $business_id = request()->session()->get('user.business_id');

            $products = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->where('products.business_id', $business_id)
                ->where('products.virtual_product', 0)
                ->where('products.type', '!=', 'modifier');

            //Include search
            if (! empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term . '%');
                    $query->orWhere('sku', 'like', '%' . $term . '%');
                    $query->orWhere('sub_sku', 'like', '%' . $term . '%');
                });
            }

            //Include check for quantity
            // if($check_qty){
            //     $products->where('VLD.qty_available', '>', 0);
            // }

            $products = $products->groupBy('products.id')
                ->select(
                    'products.id as product_id',
                    'products.name',
                    'products.type',
                    'products.enable_stock',
                    'products.sku',
                    'products.id as id',
                    DB::raw('CONCAT(products.name, " - ", products.sku) as text')
                )
                ->orderBy('products.name')
                ->get();

            return json_encode($products);
        }
    }

    /**
     * Checks if product sku already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkProductSku(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $sku = $request->input('sku');
        $product_id = $request->input('product_id');

        //check in products table
        $query = Product::where('business_id', $business_id)
            ->where('virtual_product', 0)
            ->where('is_client_flagged', 0)
            ->where('sku', $sku);
        if (! empty($product_id)) {
            $query->where('id', '!=', $product_id);
        }
        $count = $query->count();

        //check in variation table if $count = 0
        if ($count == 0) {
            $query2 = Variation::where('sub_sku', $sku)
                ->join('products', 'variations.product_id', '=', 'products.id')
                ->where('business_id', $business_id);

            if (! empty($product_id)) {
                $query2->where('product_id', '!=', $product_id);
            }

            if (! empty($request->input('variation_id'))) {
                $query2->where('variations.id', '!=', $request->input('variation_id'));
            }
            $count = $query2->count();
        }
        if ($count == 0) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    /**
     * Validates multiple variation skus
     */
    public function validateVaritionSkus(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $all_skus = $request->input('skus');

        $skus = [];
        foreach ($all_skus as $key => $value) {
            $skus[] = $value['sku'];
        }

        //check product table is sku present
        $product = Product::where('business_id', $business_id)
            ->where('virtual_product', 0)
            ->where('is_client_flagged', 0)
            ->whereIn('sku', $skus)
            ->first();

        if (! empty($product)) {
            return ['success' => 0, 'sku' => $product->sku];
        }

        foreach ($all_skus as $key => $value) {
            $query = Variation::where('sub_sku', $value['sku'])
                ->join('products', 'variations.product_id', '=', 'products.id')
                ->where('business_id', $business_id);

            if (! empty($value['variation_id'])) {
                $query->where('variations.id', '!=', $value['variation_id']);
            }
            $variation = $query->first();

            if (! empty($variation)) {
                return ['success' => 0, 'sku' => $variation->sub_sku];
            }
        }

        return ['success' => 1];
    }

    /**
     * Loads quick add product modal.
     *
     * @return \Illuminate\Http\Response
     */
    public function quickAdd()
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $product_name = ! empty(request()->input('product_name')) ? request()->input('product_name') : '';

        $product_for = ! empty(request()->input('product_for')) ? request()->input('product_for') : null;

        $business_id = request()->session()->get('user.business_id');
        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::forDropdown($business_id);
        $units = Unit::forDropdown($business_id, true);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $barcode_types = $this->barcode_types;

        $default_profit_percent = Business::where('id', $business_id)->value('default_profit_percent');

        $locations = BusinessLocation::forDropdown($business_id);

        $enable_expiry = request()->session()->get('business.enable_product_expiry');
        $enable_lot = request()->session()->get('business.enable_lot_number');

        $module_form_parts = $this->moduleUtil->getModuleData('product_form_part');

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        $common_settings = session()->get('business.common_settings');
        $warranties = Warranty::forDropdown($business_id);

        return view('product.partials.quick_add_product')
            ->with(compact('categories', 'brands', 'units', 'taxes', 'barcode_types', 'default_profit_percent', 'tax_attributes', 'product_name', 'locations', 'product_for', 'enable_expiry', 'enable_lot', 'module_form_parts', 'business_locations', 'common_settings', 'warranties'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveQuickProduct(Request $request)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $form_fields = [
                'name',
                'brand_id',
                'unit_id',
                'category_id',
                'tax',
                'barcode_type',
                'tax_type',
                'sku',
                'alert_quantity',
                'type',
                'sub_unit_ids',
                'sub_category_id',
                'weight',
                'product_description',
                'product_custom_field1',
                'product_custom_field2',
                'product_custom_field3',
                'product_custom_field4',
                'product_custom_field5',
                'product_custom_field6',
                'product_custom_field7',
                'product_custom_field8',
                'product_custom_field9',
                'product_custom_field10',
                'product_custom_field11',
                'product_custom_field12',
                'product_custom_field13',
                'product_custom_field14',
                'product_custom_field15',
                'product_custom_field16',
                'product_custom_field17',
                'product_custom_field18',
                'product_custom_field19',
                'product_custom_field20'
            ];

            $module_form_fields = $this->moduleUtil->getModuleData('product_form_fields');
            if (! empty($module_form_fields)) {
                foreach ($module_form_fields as $key => $value) {
                    if (! empty($value) && is_array($value)) {
                        $form_fields = array_merge($form_fields, $value);
                    }
                }
            }
            $product_details = $request->only($form_fields);

            $product_details['type'] = empty($product_details['type']) ? 'single' : $product_details['type'];
            $product_details['business_id'] = $business_id;
            $product_details['created_by'] = $request->session()->get('user.id');
            if (! empty($request->input('enable_stock')) && $request->input('enable_stock') == 1) {
                $product_details['enable_stock'] = 1;
                //TODO: Save total qty
                //$product_details['total_qty_available'] = 0;
            }
            if (! empty($request->input('not_for_selling')) && $request->input('not_for_selling') == 1) {
                $product_details['not_for_selling'] = 1;
            }
            if (empty($product_details['sku'])) {
                $product_details['sku'] = ' ';
            }

            if (! empty($product_details['alert_quantity'])) {
                $product_details['alert_quantity'] = $this->productUtil->num_uf($product_details['alert_quantity']);
            }

            $expiry_enabled = $request->session()->get('business.enable_product_expiry');
            if (! empty($request->input('expiry_period_type')) && ! empty($request->input('expiry_period')) && ! empty($expiry_enabled)) {
                $product_details['expiry_period_type'] = $request->input('expiry_period_type');
                $product_details['expiry_period'] = $this->productUtil->num_uf($request->input('expiry_period'));
            }

            if (! empty($request->input('enable_sr_no')) && $request->input('enable_sr_no') == 1) {
                $product_details['enable_sr_no'] = 1;
            }

            $product_details['warranty_id'] = ! empty($request->input('warranty_id')) ? $request->input('warranty_id') : null;

            DB::beginTransaction();

            $product = Product::create($product_details);
            event(new ProductsCreatedOrModified($product_details, 'added'));

            if (empty(trim($request->input('sku')))) {
                $sku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $sku;
                $product->save();
            }

            $this->productUtil->createSingleProductVariation(
                $product->id,
                $product->sku,
                $request->input('single_dpp'),
                $request->input('single_dpp_inc_tax'),
                $request->input('profit_percent'),
                $request->input('single_dsp'),
                $request->input('single_dsp_inc_tax')
            );

            if ($product->enable_stock == 1 && ! empty($request->input('opening_stock'))) {
                $user_id = $request->session()->get('user.id');

                $transaction_date = $request->session()->get('financial_year.start');
                $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();

                $this->productUtil->addSingleProductOpeningStock($business_id, $product, $request->input('opening_stock'), $transaction_date, $user_id);
            }

            //Add product locations
            $product_locations = $request->input('product_locations');
            if (! empty($product_locations)) {
                $product->product_locations()->sync($product_locations);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('product.product_added_success'),
                'product' => $product,
                'variation' => $product->variations->first(),
                'locations' => $product_locations,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Get models for a specific brand/category
     *
     * @param int $brandId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModelsByBrand($brandId)
    {
        try {
            // Fetch the models related to the brand
            $models = DB::table('repair_device_models')
                ->where('device_id', $brandId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json($models);
        } catch (\Exception $e) {
            \Log::error('Error fetching models by brand: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch models'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function view($id)
    {
        if (! auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $product = Product::where('business_id', $business_id)
                ->with(['brand', 'unit', 'category', 'sub_category', 'product_tax', 'variations', 'variations.product_variation', 'variations.group_prices', 'variations.media', 'product_locations', 'warranty', 'media'])
                ->findOrFail($id);

            $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');

            $allowed_group_prices = [];
            foreach ($price_groups as $key => $value) {
                if (auth()->user()->can('selling_price_group.' . $key)) {
                    $allowed_group_prices[$key] = $value;
                }
            }

            $group_price_details = [];

            foreach ($product->variations as $variation) {
                foreach ($variation->group_prices as $group_price) {
                    $group_price_details[$variation->id][$group_price->price_group_id] = ['price' => $group_price->price_inc_tax, 'price_type' => $group_price->price_type, 'calculated_price' => $group_price->calculated_price];
                }
            }

            $rack_details = $this->productUtil->getRackDetails($business_id, $id, true);

            $combo_variations = [];
            if ($product->type == 'combo') {
                $combo_variations = $this->productUtil->__getComboProductDetails($product['variations'][0]->combo_variations, $business_id);
            }

            return view('product.view-modal')->with(compact(
                'product',
                'rack_details',
                'allowed_group_prices',
                'group_price_details',
                'combo_variations'
            ));
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
        }
    }

    /**
     * Mass deletes products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(Request $request)
    {
        if (! auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $purchase_exist = false;

            if (! empty($request->input('selected_rows'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_rows = explode(',', $request->input('selected_rows'));

                $products = Product::where('business_id', $business_id)
                    ->whereIn('id', $selected_rows)
                    ->with(['purchase_lines', 'variations'])
                    ->get();
                $deletable_products = [];

                $is_mfg_installed = $this->moduleUtil->isModuleInstalled('Manufacturing');

                DB::beginTransaction();

                foreach ($products as $product) {
                    $can_be_deleted = true;
                    //Check if product is added as an ingredient of any recipe
                    if ($is_mfg_installed) {
                        $variation_ids = $product->variations->pluck('id');

                        $exists_as_ingredient = \Modules\Manufacturing\Entities\MfgRecipeIngredient::whereIn('variation_id', $variation_ids)
                            ->exists();
                        $can_be_deleted = ! $exists_as_ingredient;
                    }

                    //Delete if no purchase found
                    if (empty($product->purchase_lines->toArray()) && $can_be_deleted) {
                        //Delete variation location details
                        VariationLocationDetails::where('product_id', $product->id)
                            ->delete();
                        $product->delete();
                        event(new ProductsCreatedOrModified($product, 'Deleted'));
                    } else {
                        $purchase_exist = true;
                    }
                }

                DB::commit();
            }

            if (! $purchase_exist) {
                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.deleted_success'),
                ];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.products_could_not_be_deleted'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Merge multiple products into a target product and delete sources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mergeProducts(Request $request)
    {
        if (! auth()->user()->can('product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $target_id = (int) $request->input('target_product_id');

        $source_input = $request->input('source_product_ids');
        $source_ids = collect(is_array($source_input) ? $source_input : explode(',', (string) $source_input))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($target_id === 0 || $source_ids->isEmpty()) {
            return response()->json(['success' => false, 'msg' => __('lang_v1.no_row_selected')]);
        }

        if ($source_ids->contains($target_id)) {
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }

        $target = Product::where('business_id', $business_id)->findOrFail($target_id);
        $sources = Product::where('business_id', $business_id)
            ->whereIn('id', $source_ids)
            ->get();

        if ($sources->isEmpty()) {
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }

        try {
            DB::beginTransaction();

            $sourceIdsArr = $sources->pluck('id');

            // Get source variations BEFORE re-parenting
            $sourceVariations = Variation::whereIn('product_id', $sourceIdsArr)->get();
            $targetVariations = Variation::where('product_id', $target_id)->get();

            // Create variation mapping: source variations -> target variations
            $variationMapping = [];

            foreach ($sourceVariations as $sourceVar) {
                $matchedTargetVar = $targetVariations->first(function ($targetVar) use ($sourceVar) {
                    return $targetVar->name === $sourceVar->name &&
                           $targetVar->sub_sku === $sourceVar->sub_sku;
                });

                if ($matchedTargetVar) {
                    $variationMapping[$sourceVar->id] = $matchedTargetVar->id;
                } elseif ($targetVariations->isNotEmpty()) {
                    $variationMapping[$sourceVar->id] = $targetVariations->first()->id;
                }
            }

            // Re-parent product variations and variations
            $sourceProductVariationIds = ProductVariation::whereIn('product_id', $sourceIdsArr)->pluck('id');
            if ($sourceProductVariationIds->isNotEmpty()) {
                ProductVariation::whereIn('id', $sourceProductVariationIds)->update(['product_id' => $target_id]);
                Variation::whereIn('product_variation_id', $sourceProductVariationIds)->update(['product_id' => $target_id]);
            }

            // Update variation_id in all transaction-related tables
            if (!empty($variationMapping)) {
                foreach ($variationMapping as $sourceVarId => $targetVarId) {
                    if ($sourceVarId === $targetVarId) {
                        continue;
                    }

                    PurchaseLine::where('variation_id', $sourceVarId)->update(['variation_id' => $targetVarId]);
                    TransactionSellLine::where('variation_id', $sourceVarId)->update(['variation_id' => $targetVarId]);

                    if (DB::getSchemaBuilder()->hasTable('stock_adjustment_lines')) {
                        DB::table('stock_adjustment_lines')
                            ->where('variation_id', $sourceVarId)
                            ->update(['variation_id' => $targetVarId]);
                    }

                    VariationLocationDetails::where('variation_id', $sourceVarId)
                        ->update(['variation_id' => $targetVarId, 'product_id' => $target_id]);
                }
            }

            // Re-point transactional and related records (product_id)
            TransactionSellLine::whereIn('product_id', $sourceIdsArr)->update(['product_id' => $target_id]);
            PurchaseLine::whereIn('product_id', $sourceIdsArr)->update(['product_id' => $target_id]);
            ProductJobOrder::whereIn('product_id', $sourceIdsArr)->update(['product_id' => $target_id]);
            VariationLocationDetails::whereIn('product_id', $sourceIdsArr)->update(['product_id' => $target_id]);

            // Re-point additional product-related tables
            DB::table('package_product')
                ->whereIn('product_id', $sourceIdsArr)
                ->update(['product_id' => $target_id]);

            DB::table('product_compatibility')
                ->whereIn('product_id', $sourceIdsArr)
                ->update(['product_id' => $target_id]);

            DB::table('product_racks')
                ->whereIn('product_id', $sourceIdsArr)
                ->update(['product_id' => $target_id]);

            // Re-point modifier sets if present
            if (DB::getSchemaBuilder()->hasTable('res_product_modifier_sets')) {
                DB::table('res_product_modifier_sets')
                    ->whereIn('product_id', $sourceIdsArr)
                    ->update(['product_id' => $target_id]);
            }

            // Re-point product workshop if present
            if (DB::getSchemaBuilder()->hasTable('product_workshop')) {
                DB::table('product_workshop')
                    ->whereIn('product_id', $sourceIdsArr)
                    ->update(['product_id' => $target_id]);
            }

            // Clean up product location pivot entries for old products
            DB::table('product_locations')->whereIn('product_id', $sourceIdsArr)->delete();

            // Consolidate variation_location_details (sum quantities for same variation_id + location_id)
            DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_vld_consolidate');
            DB::statement(<<<SQL
                CREATE TEMPORARY TABLE tmp_vld_consolidate AS
                SELECT variation_id, location_id, SUM(qty_available) as sum_qty, MIN(id) as keep_id
                FROM variation_location_details
                WHERE product_id = ?
                GROUP BY variation_id, location_id
            SQL, [$target_id]);

            DB::statement(<<<SQL
                UPDATE variation_location_details vld
                JOIN tmp_vld_consolidate tmp ON vld.variation_id = tmp.variation_id AND vld.location_id = tmp.location_id
                SET vld.qty_available = tmp.sum_qty
                WHERE vld.product_id = ?
            SQL, [$target_id]);

            DB::statement(<<<SQL
                DELETE v1 FROM variation_location_details v1
                JOIN tmp_vld_consolidate tmp ON v1.variation_id = tmp.variation_id AND v1.location_id = tmp.location_id
                WHERE v1.product_id = ? AND v1.id <> tmp.keep_id
            SQL, [$target_id]);

            DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_vld_consolidate');

            // Merge duplicate variations for the target product
            $this->mergeProductVariations($target_id);

            // Delete source products (hard delete to avoid soft deletes)
            Product::whereIn('id', $sourceIdsArr)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.deleted_success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('Product merge failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * Merge duplicate variations for a product after merging products.
     *
     * @param  int  $productId
     * @return void
     */
    private function mergeProductVariations($productId)
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
            WHERE product_id = ? AND name IS NOT NULL AND sub_sku IS NOT NULL
            GROUP BY product_id, name, sub_sku
            HAVING COUNT(*) > 1
        SQL, [$productId]);

        $rows = DB::table('duplicate_variation_mapping')->count();
        if ($rows === 0) {
            return;
        }

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

        // Update discount_variations if present
        if (DB::getSchemaBuilder()->hasTable('discount_variations')) {
            DB::statement(<<<SQL
                UPDATE discount_variations dv
                JOIN variations v ON v.id = dv.variation_id
                JOIN duplicate_variation_mapping dvm
                    ON dvm.product_id = v.product_id AND dvm.name = v.name AND dvm.sub_sku = v.sub_sku
                SET dv.variation_id = dvm.keep_variation_id
                WHERE dv.variation_id <> dvm.keep_variation_id
            SQL);
        }

        // Update variation_group_prices if present
        if (DB::getSchemaBuilder()->hasTable('variation_group_prices')) {
            DB::statement(<<<SQL
                UPDATE variation_group_prices vgp
                JOIN variations v ON v.id = vgp.variation_id
                JOIN duplicate_variation_mapping dvm
                    ON dvm.product_id = v.product_id AND dvm.name = v.name AND dvm.sub_sku = v.sub_sku
                SET vgp.variation_id = dvm.keep_variation_id
                WHERE vgp.variation_id <> dvm.keep_variation_id
            SQL);
        }

        // Consolidate qty_available per (keep_variation_id, location_id)
        // Create a helper temp table for sums
        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_vld_sums');
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE tmp_vld_sums AS
            SELECT variation_id, location_id, SUM(qty_available) AS sum_qty
            FROM variation_location_details
            WHERE variation_id IN (SELECT keep_variation_id FROM duplicate_variation_mapping)
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
            WHERE v1.variation_id IN (SELECT keep_variation_id FROM duplicate_variation_mapping)
        SQL);

        // Finally hard delete duplicate variation rows (except keep)
        // Use forceDelete to bypass soft deletes
        DB::statement(<<<SQL
            DELETE v FROM variations v
            JOIN duplicate_variation_mapping dvm
              ON dvm.product_id = v.product_id AND dvm.name = v.name AND dvm.sub_sku = v.sub_sku
            WHERE v.id <> dvm.keep_variation_id
        SQL);

        // Clean up temporary table
        DB::statement('DROP TEMPORARY TABLE IF EXISTS duplicate_variation_mapping');
    }

    /**
     * Shows form to add selling price group prices for a product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addSellingPrices($id)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $product = Product::where('business_id', $business_id)
            ->with(['variations', 'variations.group_prices', 'variations.product_variation'])
            ->findOrFail($id);

        $price_groups = SellingPriceGroup::where('business_id', $business_id)
            ->active()
            ->get();
        $variation_prices = [];
        foreach ($product->variations as $variation) {
            foreach ($variation->group_prices as $group_price) {
                $variation_prices[$variation->id][$group_price->price_group_id] = ['price' => $group_price->price_inc_tax, 'price_type' => $group_price->price_type];
            }
        }

        return view('product.add-selling-prices')->with(compact('product', 'price_groups', 'variation_prices'));
    }

    /**
     * Saves selling price group prices for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveSellingPrices(Request $request)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $product = Product::where('business_id', $business_id)
                ->with(['variations'])
                ->findOrFail($request->input('product_id'));
            DB::beginTransaction();
            foreach ($product->variations as $variation) {
                $variation_group_prices = [];
                foreach ($request->input('group_prices') as $key => $value) {
                    if (isset($value[$variation->id])) {
                        $variation_group_price =
                            VariationGroupPrice::where('variation_id', $variation->id)
                            ->where('price_group_id', $key)
                            ->first();
                        if (empty($variation_group_price)) {
                            $variation_group_price = new VariationGroupPrice([
                                'variation_id' => $variation->id,
                                'price_group_id' => $key,
                            ]);
                        }

                        $variation_group_price->price_inc_tax = $this->productUtil->num_uf($value[$variation->id]['price']);
                        $variation_group_price->price_type = $value[$variation->id]['price_type'];
                        $variation_group_prices[] = $variation_group_price;
                    }
                }

                if (! empty($variation_group_prices)) {
                    $variation->group_prices()->saveMany($variation_group_prices);
                }
            }
            //Update product updated_at timestamp
            $product->touch();

            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->input('submit_type') == 'submit_n_add_opening_stock') {
            return redirect()->action(
                [\App\Http\Controllers\OpeningStockController::class, 'add'],
                ['product_id' => $product->id]
            );
        } elseif ($request->input('submit_type') == 'save_n_add_another') {
            return redirect()->action(
                [\App\Http\Controllers\ProductController::class, 'create']
            )->with('status', $output);
        }

        return redirect('products')->with('status', $output);
    }

    public function viewGroupPrice($id)
    {
        if (! auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $product = Product::where('business_id', $business_id)
            ->where('id', $id)
            ->with(['variations', 'variations.product_variation', 'variations.group_prices'])
            ->first();

        $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');

        $allowed_group_prices = [];
        foreach ($price_groups as $key => $value) {
            if (auth()->user()->can('selling_price_group.' . $key)) {
                $allowed_group_prices[$key] = $value;
            }
        }

        $group_price_details = [];

        foreach ($product->variations as $variation) {
            foreach ($variation->group_prices as $group_price) {
                $group_price_details[$variation->id][$group_price->price_group_id] = $group_price->price_inc_tax;
            }
        }

        return view('product.view-product-group-prices')->with(compact('product', 'allowed_group_prices', 'group_price_details'));
    }

    /**
     * Mass deactivates products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDeactivate(Request $request)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (! empty($request->input('selected_products'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_products = explode(',', $request->input('selected_products'));

                DB::beginTransaction();

                $products = Product::where('business_id', $business_id)
                    ->whereIn('id', $selected_products)
                    ->update(['is_inactive' => 1]);

                DB::commit();
            }

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.products_deactivated_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Activates the specified resource from storage.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function activate($id)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                $product = Product::where('id', $id)
                    ->where('business_id', $business_id)
                    ->update(['is_inactive' => 0]);

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.updated_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Deletes a media file from storage and database.
     *
     * @param  int  $media_id
     * @return json
     */
    public function deleteMedia($media_id)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                Media::deleteMedia($business_id, $media_id);

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.file_deleted_successfully'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function getProductsApi($id = null)
    {
        try {
            $api_token = request()->header('API-TOKEN');
            $filter_string = request()->header('FILTERS');
            $order_by = request()->header('ORDER-BY');

            parse_str($filter_string, $filters);

            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $limit = ! empty(request()->input('limit')) ? request()->input('limit') : 10;

            $location_id = $api_settings->location_id;

            $query = Product::where('business_id', $api_settings->business_id)
                ->active()
                ->where('products.virtual_product', 0)
                ->where('products.is_client_flagged', 0)
                ->with([
                    'brand',
                    'unit',
                    'category',
                    'sub_category',
                    'product_variations',
                    'product_variations.variations',
                    'product_variations.variations.media',
                    'product_variations.variations.variation_location_details' => function ($q) use ($location_id) {
                        $q->where('location_id', $location_id);
                    },
                ]);

            if (! empty($filters['categories'])) {
                $query->whereIn('category_id', $filters['categories']);
            }

            if (! empty($filters['brands'])) {
                $query->whereIn('brand_id', $filters['brands']);
            }

            if (! empty($filters['category'])) {
                $query->where('category_id', $filters['category']);
            }

            if (! empty($filters['sub_category'])) {
                $query->where('sub_category_id', $filters['sub_category']);
            }

            if ($order_by == 'name') {
                $query->orderBy('name', 'asc');
            } elseif ($order_by == 'date') {
                $query->orderBy('created_at', 'desc');
            }

            if (empty($id)) {
                $products = $query->paginate($limit);
            } else {
                $products = $query->find($id);
            }
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($products);
    }

    public function getVariationsApi()
    {
        try {
            $api_token = request()->header('API-TOKEN');
            $variations_string = request()->header('VARIATIONS');

            if (is_numeric($variations_string)) {
                $variation_ids = intval($variations_string);
            } else {
                parse_str($variations_string, $variation_ids);
            }

            $api_settings = $this->moduleUtil->getApiSettings($api_token);
            $location_id = $api_settings->location_id;
            $business_id = $api_settings->business_id;

            $query = Variation::with([
                'product_variation',
                'product' => function ($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                },
                'product.unit',
                'variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                },
            ]);

            $variations = is_array($variation_ids) ? $query->whereIn('id', $variation_ids)->get() : $query->where('id', $variation_ids)->first();
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($variations);
    }

    /**
     * Shows form to edit multiple products at once.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkEdit(Request $request)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $selected_products_string = $request->input('selected_products');
        if (! empty($selected_products_string)) {
            $selected_products = explode(',', $selected_products_string);
            $business_id = $request->session()->get('user.business_id');

            $products = Product::where('business_id', $business_id)
                ->whereIn('id', $selected_products)
                ->with(['variations', 'variations.product_variation', 'variations.group_prices', 'product_locations'])
                ->get();

            $all_categories = Category::catAndSubCategories($business_id);

            $categories = [];
            $sub_categories = [];
            foreach ($all_categories as $category) {
                $categories[$category['id']] = $category['name'];

                if (! empty($category['sub_categories'])) {
                    foreach ($category['sub_categories'] as $sub_category) {
                        $sub_categories[$category['id']][$sub_category['id']] = $sub_category['name'];
                    }
                }
            }

            $brands = Brands::forDropdown($business_id);

            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
            $taxes = $tax_dropdown['tax_rates'];
            $tax_attributes = $tax_dropdown['attributes'];

            $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');
            $business_locations = BusinessLocation::forDropdown($business_id);

            return view('product.bulk-edit')->with(compact(
                'products',
                'categories',
                'brands',
                'taxes',
                'tax_attributes',
                'sub_categories',
                'price_groups',
                'business_locations'
            ));
        }
    }

    /**
     * Updates multiple products at once.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkUpdate(Request $request)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $products = $request->input('products');
            $business_id = $request->session()->get('user.business_id');

            DB::beginTransaction();
            foreach ($products as $id => $product_data) {
                $update_data = [
                    'category_id' => $product_data['category_id'],
                    'sub_category_id' => $product_data['sub_category_id'],
                    'brand_id' => $product_data['brand_id'],
                    'tax' => $product_data['tax'],
                ];

                //Update product
                $product = Product::where('business_id', $business_id)
                    ->findOrFail($id);

                $product->update($update_data);

                //Add product locations
                $product_locations = ! empty($product_data['product_locations']) ?
                    $product_data['product_locations'] : [];
                $product->product_locations()->sync($product_locations);

                $variations_data = [];

                //Format variations data
                foreach ($product_data['variations'] as $key => $value) {
                    $variation = Variation::where('product_id', $product->id)->findOrFail($key);
                    $variation->default_purchase_price = $this->productUtil->num_uf($value['default_purchase_price']);
                    $variation->dpp_inc_tax = $this->productUtil->num_uf($value['dpp_inc_tax']);
                    $variation->profit_percent = $this->productUtil->num_uf($value['profit_percent']);
                    $variation->default_sell_price = $this->productUtil->num_uf($value['default_sell_price']);
                    $variation->sell_price_inc_tax = $this->productUtil->num_uf($value['sell_price_inc_tax']);
                    $variations_data[] = $variation;

                    //Update price groups
                    if (! empty($value['group_prices'])) {
                        foreach ($value['group_prices'] as $k => $v) {
                            VariationGroupPrice::updateOrCreate(
                                ['price_group_id' => $k, 'variation_id' => $variation->id],
                                ['price_inc_tax' => $this->productUtil->num_uf($v)]
                            );
                        }
                    }
                }
                $product->variations()->saveMany($variations_data);
            }
            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('products')->with('status', $output);
    }

    /**
     * Adds product row to edit in bulk edit product form
     *
     * @param  int  $product_id
     * @return \Illuminate\Http\Response
     */
    public function getProductToEdit($product_id)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $product = Product::where('business_id', $business_id)
            ->with(['variations', 'variations.product_variation', 'variations.group_prices'])
            ->findOrFail($product_id);
        $all_categories = Category::catAndSubCategories($business_id);

        $categories = [];
        $sub_categories = [];
        foreach ($all_categories as $category) {
            $categories[$category['id']] = $category['name'];

            if (! empty($category['sub_categories'])) {
                foreach ($category['sub_categories'] as $sub_category) {
                    $sub_categories[$category['id']][$sub_category['id']] = $sub_category['name'];
                }
            }
        }

        $brands = Brands::forDropdown($business_id);

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('product.partials.bulk_edit_product_row')->with(compact(
            'product',
            'categories',
            'brands',
            'taxes',
            'tax_attributes',
            'sub_categories',
            'price_groups',
            'business_locations'
        ));
    }

    /**
     * Gets the sub units for the given unit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $unit_id
     * @return \Illuminate\Http\Response
     */
    public function getSubUnits(Request $request)
    {
        if (! empty($request->input('unit_id'))) {
            $unit_id = $request->input('unit_id');
            $business_id = $request->session()->get('user.business_id');
            $sub_units = $this->productUtil->getSubUnits($business_id, $unit_id, true);

            //$html = '<option value="">' . __('lang_v1.all') . '</option>';
            $html = '';
            if (! empty($sub_units)) {
                foreach ($sub_units as $id => $sub_unit) {
                    $html .= '<option value="' . $id . '">' . $sub_unit['name'] . '</option>';
                }
            }

            return $html;
        }
    }

    public function updateProductLocation(Request $request)
    {
        if (! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $selected_products = $request->input('products');
            $update_type = $request->input('update_type');
            $location_ids = $request->input('product_location');

            $business_id = $request->session()->get('user.business_id');

            $product_ids = explode(',', $selected_products);

            $products = Product::where('business_id', $business_id)
                ->whereIn('id', $product_ids)
                ->with(['product_locations'])
                ->get();
            DB::beginTransaction();
            foreach ($products as $product) {
                $product_locations = $product->product_locations->pluck('id')->toArray();

                if ($update_type == 'add') {
                    $product_locations = array_unique(array_merge($location_ids, $product_locations));
                    $product->product_locations()->sync($product_locations);
                } elseif ($update_type == 'remove') {
                    foreach ($product_locations as $key => $value) {
                        if (in_array($value, $location_ids)) {
                            unset($product_locations[$key]);
                        }
                    }
                    $product->product_locations()->sync($product_locations);
                }
            }
            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function productStockHistory($id)
    {
        if (! auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {

            //for ajax call $id is variation id else it is product id
            $stock_details = $this->productUtil->getVariationStockDetails($business_id, $id, request()->input('location_id'));
            $stock_history = $this->productUtil->getVariationStockHistory($business_id, $id, request()->input('location_id'));

            //if mismach found update stock in variation location details
            if (isset($stock_history[0]) && (float) $stock_details['current_stock'] != (float) $stock_history[0]['stock']) {
                VariationLocationDetails::where(
                    'variation_id',
                    $id
                )
                    ->where('location_id', request()->input('location_id'))
                    ->update(['qty_available' => $stock_history[0]['stock']]);
                $stock_details['current_stock'] = $stock_history[0]['stock'];
            }

            return view('product.stock_history_details')
                ->with(compact('stock_details', 'stock_history'));
        }

        $product = Product::where('business_id', $business_id)
            ->with(['variations', 'variations.product_variation'])
            ->findOrFail($id);

        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('product.stock_history')
            ->with(compact('product', 'business_locations'));
    }

    public function test(){
        return view('test.test');
    }
    public function recalcAllProductStocks()
    {
        if (! auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get all variations for this business
        $variations = Variation::whereHas('product', function ($q) use ($business_id) {
            $q->where('business_id', $business_id)
                ->where('enable_stock', 1); // Only products with stock enabled
        })->get();

        $fixed_count = 0;

        foreach ($variations as $variation) {

            // Loop through all locations for this business
            $locations = BusinessLocation::where('business_id', $business_id)->pluck('id');

            foreach ($locations as $location_id) {

                // Get current stock details and history (same as your function)
                $stock_details = $this->productUtil->getVariationStockDetails($business_id, $variation->id, $location_id);
                $stock_history = $this->productUtil->getVariationStockHistory($business_id, $variation->id, $location_id);

                // If stock history exists
                if (isset($stock_history[0])) {
                    $new_stock = (float) $stock_history[0]['stock'];
                    $old_stock = (float) $stock_details['current_stock'];

                    // If mismatch → fix it
                    if ($new_stock != $old_stock) {
                        VariationLocationDetails::where('variation_id', $variation->id)
                            ->where('location_id', $location_id)
                            ->update(['qty_available' => $new_stock]);

                        $fixed_count++;
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Stock recalculation completed. Fixed {$fixed_count} mismatched items."
        ]);
    }

    /**
     * Toggle WooComerce sync
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toggleWooCommerceSync(Request $request)
    {
        try {
            $selected_products = $request->input('woocommerce_products_sync');
            $woocommerce_disable_sync = $request->input('woocommerce_disable_sync');

            $business_id = $request->session()->get('user.business_id');
            $product_ids = explode(',', $selected_products);

            DB::beginTransaction();
            if ($this->moduleUtil->isModuleInstalled('Woocommerce')) {
                Product::where('business_id', $business_id)
                    ->whereIn('id', $product_ids)
                    ->update(['woocommerce_disable_sync' => $woocommerce_disable_sync]);
            }
            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Get available car models from product_compatibility table
     */
    public function getCarModels()
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            
            $models = DB::table('product_compatibility as pc')
                ->join('products as p', 'pc.product_id', '=', 'p.id')
                ->leftJoin('repair_device_models as dm', 'pc.model_id', '=', 'dm.id')
                ->where('p.business_id', $business_id)
                ->whereNotNull('pc.model_id')
                ->groupBy('pc.model_id', 'dm.name')
                ->orderBy('dm.name')
                ->select('pc.model_id as id', DB::raw('COALESCE(dm.name, CONCAT("Model #", pc.model_id)) as name'))
                ->get();
            
            return response()->json($models);
        } catch (\Exception $e) {
            Log::error('Error getting car models: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Get available car brands (device categories)
     */
    public function getCarBrands()
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            
            $brands = DB::table('categories')
                ->where('business_id', $business_id)
                ->where('category_type', 'device')
                ->orderBy('name')
                ->select('id', 'name')
                ->get();
            
            return response()->json($brands);
        } catch (\Exception $e) {
            Log::error('Error getting car brands: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Get car models by brand (device_id)
     */
    public function getCarModelsByBrand(Request $request)
    {
        try {
            $brand_id = $request->get('brand_id');
            
            if (empty($brand_id)) {
                return response()->json([]);
            }
            
            $models = DB::table('repair_device_models')
                ->where('device_id', $brand_id)
                ->orderBy('name')
                ->select('id', 'name')
                ->get();
            
            return response()->json($models);
        } catch (\Exception $e) {
            Log::error('Error getting car models by brand: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Get all compatibility records for a product (for modal)
     */
    public function getProductCompatibility($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $product = Product::where('business_id', $business_id)->findOrFail($id);

            $compatibilities = \App\ProductCompatibility::where('product_id', $id)
                ->with(['deviceModel', 'brandCategory'])
                ->get()
                ->map(function ($compat) {
                    return [
                        'id' => $compat->id,
                        'brand_category_id' => $compat->brand_category_id,
                        'brand_category_name' => $compat->brandCategory ? $compat->brandCategory->name : '',
                        'model_id' => $compat->model_id,
                        'model_name' => $compat->deviceModel ? $compat->deviceModel->name : '',
                        'from_year' => $compat->from_year,
                        'to_year' => $compat->to_year,
                    ];
                });

            return response()->json(['success' => true, 'data' => $compatibilities]);
        } catch (\Exception $e) {
            Log::error('Error getting product compatibility: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Store a new compatibility record for a product
     */
    public function storeProductCompatibility(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $product = Product::where('business_id', $business_id)->findOrFail($request->product_id);

            $compat = new \App\ProductCompatibility();
            $compat->product_id = $request->product_id;
            $compat->brand_category_id = $request->brand_category_id ?: null;
            $compat->model_id = $request->model_id ?: null;
            $compat->from_year = $request->from_year ?: null;
            $compat->to_year = $request->to_year ?: null;
            $compat->save();

            return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $e) {
            Log::error('Error storing product compatibility: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Delete a compatibility record
     */
    public function deleteProductCompatibility($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $compat = \App\ProductCompatibility::findOrFail($id);

            // Verify the product belongs to this business
            $product = Product::where('business_id', $business_id)->findOrFail($compat->product_id);

            $compat->delete();

            return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $e) {
            Log::error('Error deleting product compatibility: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Function to download all products in xlsx format
     */
    public function downloadExcel()
    {
        $is_admin = $this->productUtil->is_admin(auth()->user());
        if (! $is_admin) {
            abort(403, 'Unauthorized action.');
        }

        $filename = 'products-export-' . \Carbon::now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ProductsExport, $filename);
    }
}
