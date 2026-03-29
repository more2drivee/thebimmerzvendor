<?php

namespace Modules\Repair\Http\Controllers;

use App\BusinessLocation;
use App\Brands;
use App\Barcode;
use App\Product;
use App\Business;
use App\Category;
use App\Unit;
use App\Variation;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
// use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Repair\Utils\RepairUtil;
use Modules\Repair\Entities\RepairStatus;
use Modules\Repair\Entities\FlatRateService;

class RepairSettingsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $repairUtil;

    protected $moduleUtil;

    protected $productUtil;

    /**
     * Constructor
     *
     * @param  RepairUtil  $repairUtil
     * @return void
     */
    public function __construct(RepairUtil $repairUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->repairUtil = $repairUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.create')))) {
            abort(403, 'Unauthorized action.');
        }

        $barcode_settings = Barcode::where('business_id', $business_id)
                                ->orWhereNull('business_id')
                                ->pluck('name', 'id');

        $repair_settings = $this->repairUtil->getRepairSettings($business_id);

        $jobsheet_pdf_settings = $this->repairUtil->getJobsheetPdfSettings($business_id);

        $default_product_name = __('repair::lang.no_default_product_selected');
        if (! empty($repair_settings['default_product'])) {
            $default_product = Variation::where('id', $repair_settings['default_product'])
                        ->with(['product_variation', 'product'])
                        ->first();

            $default_product_name = $default_product->product->type == 'single' ? $default_product->product->name.' - '.$default_product->product->sku : $default_product->product->name.' ('.$default_product->name.') - '.$default_product->sub_sku;
        }

        //barcode types
        $barcode_types = $this->moduleUtil->barcode_types();
        $repair_statuses = RepairStatus::getRepairSatuses($business_id);

        $brands = Brands::forDropdown($business_id, false, true);
        $devices = Category::forDropdown($business_id, 'device');
        $module_category_data = $this->moduleUtil->getTaxonomyData('device');
        // Preload locations for tabs that need them
        $business_locations = BusinessLocation::forDropdown($business_id, false, false, false, true);
        $workshops = DB::table('workshops')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
        return view('repair::settings.index')
                ->with(compact('barcode_settings', 'repair_settings', 'default_product_name',
                'barcode_types', 'repair_statuses', 'brands', 'devices',
                'module_category_data', 'jobsheet_pdf_settings', 'business_locations', 'workshops'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.create')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['barcode_id', 'default_product', 'barcode_type', 'repair_tc_condition', 'job_sheet_prefix', 'problem_reported_by_customer', 'product_condition', 'product_configuration', 'job_sheet_custom_field_1', 'job_sheet_custom_field_2', 'job_sheet_custom_field_3', 'job_sheet_custom_field_4', 'job_sheet_custom_field_5', 'default_repair_checklist']);

            $default_status = $request->get('default_status');
            if (! empty($default_status) && is_numeric($default_status)) {
                $input['default_status'] = $default_status;
            } else {
                $input['default_status'] = '';
            }
            Business::where('id', $business_id)
                        ->update(['repair_settings' => json_encode($input)]);

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function updateJobsheetSettings(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') ||
        ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.create')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['customer_label', 'client_id_label', 'client_tax_label', 'label_width', 'label_height']);

            $checkboxes = ['contact_custom_fields', 'show_customer', 'show_client_id',
                'show_customer_name_in_label', 'show_customer_address_in_label', 'show_customer_phone_in_label',
                'show_customer_alt_phone_in_label', 'show_customer_email_in_label', 'show_sales_person_in_label',
                'show_barcode_in_label', 'show_status_in_label', 'show_due_date_in_label', 'show_technician_in_label',
                'show_problem_in_label', 'show_job_sheet_number', 'show_sr_no_in_label', 'show_brand_in_label', 'show_location_in_label',
                'show_password_in_label', ];
            foreach ($checkboxes as $checkbox) {
                if ($request->has($checkbox)) {
                    $input[$checkbox] = $request->input($checkbox);
                }
            }

            Business::where('id', $business_id)
                        ->update(['repair_jobsheet_settings' => json_encode($input)]);

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
    /**
     * List flat rate services for DataTable.
     */
    public function flatRate(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = FlatRateService::with(['businessLocation:id,name'])
                ->where('business_id', $business_id);

            if ($request->filled('location_id')) {
                $query->where('business_location_id', $request->get('location_id'));
            }

            if ($request->filled('active')) {
                $query->where('is_active', (bool) $request->get('active'));
            }

            return \Yajra\DataTables\Facades\DataTables::of($query)
                ->addColumn('price_per_hour', function ($row) {
                    return $this->productUtil->num_f($row->price_per_hour, false, null, true);
                })
                ->addColumn('location', function ($row) {
                    return optional($row->businessLocation)->name;
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="label label-success">'.__('lang_v1.active').'</span>'
                        : '<span class="label label-default">'.__('sale.inactive').'</span>';
                })
                ->addColumn('action', function ($row) {
                    $showUrl = route('repair.flat_rate.show', $row->id);
                    $updateUrl = route('repair.flat_rate.update', $row->id);
                    $deleteUrl = route('repair.flat_rate.destroy', $row->id);
                    return '<div class="btn-group" role="group" aria-label="Actions">
                        <button type="button" class="btn btn-xs btn-primary edit-flat-rate" data-show="'.$showUrl.'" data-update="'.$updateUrl.'">'.__('messages.edit').'</button>
                        <button type="button" class="btn btn-xs btn-danger delete-flat-rate" data-delete="'.$deleteUrl.'">'.__('messages.delete').'</button>
                    </div>';
                })
                ->rawColumns(['is_active','action'])
                ->make(true);
        }

        abort(404);
    }

    /**
     * Store a flat rate service.
     */
    public function storeFlatRate(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'business_location_id' => 'required|integer',
            'price_per_hour' => 'required|numeric|min:0',
          
            'is_active' => 'nullable|boolean',
        ]);

        try {
            \DB::beginTransaction();

            $is_active = (bool)($validated['is_active'] ?? false);
            // Allow multiple active flat rates per location; do not deactivate others

            $flat = FlatRateService::create([
                'business_id' => $business_id,
                'business_location_id' => $validated['business_location_id'],
                'name' => $validated['name'],
                'price_per_hour' => $validated['price_per_hour'],
                'is_active' => $is_active,
            ]);

            $this->updateServicePricesForFlatRate($flat);

            \DB::commit();
            return response()->json(['success' => true, 'message' => __('lang_v1.updated_success')]);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Show a single flat rate service.
     */
    public function showFlatRate(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        $flat = FlatRateService::where('business_id', $business_id)->findOrFail($id);
        return response()->json([
            'id' => $flat->id,
            'name' => $flat->name,
            'price_per_hour' => $flat->price_per_hour,
            'business_location_id' => $flat->business_location_id,
            'is_active' => (bool) $flat->is_active,
        ]);
    }

    /**
     * Update a flat rate service.
     */
    public function updateFlatRate(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'business_location_id' => 'required|integer',
            'price_per_hour' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            \DB::beginTransaction();
            $flat = FlatRateService::where('business_id', $business_id)->findOrFail($id);

            $is_active = (bool)($validated['is_active'] ?? false);
            // Allow multiple active flat rates per location; do not deactivate others

            $flat->fill([
                'name' => $validated['name'],
                'business_location_id' => $validated['business_location_id'],
                'price_per_hour' => $validated['price_per_hour'],
                'is_active' => $is_active,
            ])->save();

            $this->updateServicePricesForFlatRate($flat);

            \DB::commit();
            return response()->json(['success' => true, 'message' => __('lang_v1.updated_success')]);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Delete a flat rate service.
     */
    public function deleteFlatRate(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        try {
            $flat = FlatRateService::where('business_id', $business_id)->findOrFail($id);
            $flat->delete();
            return response()->json(['success' => true, 'message' => __('lang_v1.deleted_success')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return response()->json(['success' => false, 'message' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Get the active flat rate for a given location.
     */
    public function activeFlatRate(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');

        if (empty($location_id)) {
            return response()->json(['success' => false, 'message' => 'location_id is required'], 422);
        }

        $flat = FlatRateService::forBusiness($business_id)
            ->forLocation($location_id)
            ->active()
            ->first();

        if (!$flat) {
            return response()->json(['success' => true, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $flat->id,
                'name' => $flat->name,
                'price_per_hour' => (float) $flat->price_per_hour,
                'business_location_id' => $flat->business_location_id,
            ],
        ]);
    }

    private function updateServicePricesForFlatRate(FlatRateService $flat): void
    {
        $services = Product::where('business_id', $flat->business_id)
            ->where('enable_stock', 0)
            ->where('product_custom_field1', 'per_hour')
            ->where('product_custom_field2', $flat->id)
            ->get();

        foreach ($services as $service) {
            if ($service->serviceHours === null) {
                continue;
            }

            $hours = (float) $service->serviceHours;
            $newPrice = round((float) $flat->price_per_hour * $hours, 2);

            $service->variations()->update([
                'default_sell_price' => $newPrice,
                'sell_price_inc_tax' => $newPrice,
            ]);
        }
    }
}
