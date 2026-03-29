<?php

namespace Modules\CarMarket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\CarMarket\Entities\Vehicle;
use Modules\CarMarket\Entities\VehicleMedia;
use Modules\CarMarket\Entities\VehicleInquiry;
use Modules\CarMarket\Entities\VehicleReport;
use Modules\CarMarket\Entities\Favorite;
use Yajra\DataTables\Facades\DataTables;
use App\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Category;

class CarMarketController extends Controller
{
    /**
     * Admin dashboard / listings index
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $stats = [
            'total' => Vehicle::forBusiness($business_id)->count(),
            'active' => Vehicle::forBusiness($business_id)->active()->count(),
            'pending' => Vehicle::forBusiness($business_id)->pending()->count(),
            'sold' => Vehicle::forBusiness($business_id)->where('listing_status', 'sold')->count(),
            'expired' => Vehicle::forBusiness($business_id)->expired()->count(),
            'total_inquiries' => VehicleInquiry::forBusiness($business_id)->count(),
            'new_inquiries' => VehicleInquiry::forBusiness($business_id)->new()->count(),
            'pending_reports' => VehicleReport::whereHas('vehicle', function ($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })->where('status', 'pending')->count(),
        ];

        return view('carmarket::index', compact('stats'));
    }

    /**
     * DataTables endpoint for vehicles list
     */
    public function getVehiclesDatatables(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $vehicles = Vehicle::forBusiness($business_id)
            ->with(['seller', 'primaryImage', 'media'])
            ->select('cm_vehicles.*');

        if ($request->has('listing_status') && $request->listing_status != '') {
            $vehicles->where('listing_status', $request->listing_status);
        }

        if ($request->has('condition') && $request->condition != '') {
            $vehicles->where('condition', $request->condition);
        }

        return DataTables::of($vehicles)
            ->addColumn('title', function ($v) {
                return $v->getTitle();
            })
            ->addColumn('seller_name', function ($v) {
                return optional($v->seller)->name ?? '-';
            })
            ->addColumn('seller_phone', function ($v) {
                return optional($v->seller)->mobile ?? '-';
            })
            ->addColumn('primary_image', function ($v) {
                $img = $v->primaryImage ?? $v->media->first();
                if ($img) {
                    return asset('storage/' . $img->file_path);
                }
                return null;
            })
            ->addColumn('inquiries_count', function ($v) {
                return $v->inquiries()->count();
            })
            ->addColumn('media_count', function ($v) {
                return $v->media()->count();
            })
            ->addColumn('action', function ($v) {
                $html = '<div class="btn-group">';
                $html .= '<button type="button" class="btn btn-xs btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">' . __('messages.actions') . ' <span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>';
                $html .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">';
                
                // View
                $html .= '<li><a href="' . route('carmarket.vehicles.show', $v->id) . '"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';
                
                // Edit
                $html .= '<li><a href="' . route('carmarket.vehicles.edit', $v->id) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                
                $html .= '<li class="divider"></li>';
                
                // Approve - show for pending or rejected
                if (in_array($v->listing_status, ['pending', 'rejected', 'draft'])) {
                    $html .= '<li><a href="#" class="approve-btn" data-id="' . $v->id . '"><i class="fa fa-check text-success"></i> ' . __('carmarket::lang.approve') . '</a></li>';
                }
                
                // Reject - show for pending or active
                if (in_array($v->listing_status, ['pending', 'active'])) {
                    $html .= '<li><a href="#" class="reject-btn" data-id="' . $v->id . '"><i class="fa fa-times text-danger"></i> ' . __('carmarket::lang.reject') . '</a></li>';
                }
                
                // Deactivate (set to pending) - show for active
                if ($v->listing_status == 'active') {
                    $html .= '<li><a href="#" class="deactivate-btn" data-id="' . $v->id . '"><i class="fa fa-pause text-warning"></i> ' . __('carmarket::lang.deactivate') . '</a></li>';
                }
                
                $html .= '</ul></div>';
                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show single vehicle details (admin view)
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $vehicle = Vehicle::forBusiness($business_id)
            ->with([
                'seller',
                'buyer',
                'media',
                'inquiries.buyer',
                'reports.reporter',
                'creator',
                'auditLogs.changedByUser',
                'auditLogs.changedByContact',
            ])
            ->findOrFail($id);

        $similar = $vehicle->getSimilarVehicles();

        return view('carmarket::show', compact('vehicle', 'similar'));
    }

    /**
     * Approve a pending listing
     */
    public function approve(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        $vehicle = Vehicle::forBusiness($business_id)->findOrFail($id);

        if ($vehicle->listing_status !== 'pending') {
            return response()->json(['success' => false, 'msg' => 'Vehicle is not in pending status']);
        }

        $expiryDays = config('carmarket.listing_expiry_days', 90);

        $vehicle->update([
            'listing_status' => 'active',
            'approved_at' => now(),
            'expires_at' => now()->addDays($expiryDays),
        ]);

        return response()->json(['success' => true, 'msg' => 'Listing approved successfully']);
    }

    /**
     * Reject a pending listing
     */
    public function reject(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        $vehicle = Vehicle::forBusiness($business_id)->findOrFail($id);

        $vehicle->update([
            'listing_status' => 'rejected',
            'rejection_reason' => $request->input('reason', ''),
        ]);

        return response()->json(['success' => true, 'msg' => 'Listing rejected']);
    }

    /**
     * Deactivate an active listing (set back to pending)
     */
    public function deactivate(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        $vehicle = Vehicle::forBusiness($business_id)->findOrFail($id);

        if ($vehicle->listing_status !== 'active') {
            return response()->json(['success' => false, 'msg' => 'Vehicle is not active']);
        }

        $vehicle->update([
            'listing_status' => 'pending',
            'approved_at' => null,
            'expires_at' => null,
        ]);

        return response()->json(['success' => true, 'msg' => 'Listing deactivated successfully']);
    }

    /**
     * Inquiries list (admin)
     */
    public function inquiries()
    {
        $business_id = request()->session()->get('user.business_id');

        return view('carmarket::inquiries');
    }

    /**
     * DataTables endpoint for inquiries
     */
    public function getInquiriesDatatables(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $inquiries = VehicleInquiry::forBusiness($business_id)
            ->with(['vehicle', 'buyer'])
            ->select('cm_vehicle_inquiries.*');

        if ($request->has('status') && $request->status != '') {
            $inquiries->where('status', $request->status);
        }

        return DataTables::of($inquiries)
            ->addColumn('vehicle_title', function ($inq) {
                return optional($inq->vehicle)->getTitle() ?? '-';
            })
            ->addColumn('buyer_name', function ($inq) {
                return optional($inq->buyer)->name ?? '-';
            })
            ->addColumn('buyer_phone', function ($inq) {
                return optional($inq->buyer)->mobile ?? '-';
            })
            ->addColumn('action', function ($inq) {
                $html = '<select class="form-control input-sm inquiry-status-select" data-id="' . $inq->id . '">';
                foreach (['new', 'contacted', 'negotiating', 'closed_won', 'closed_lost'] as $s) {
                    $sel = $inq->status == $s ? 'selected' : '';
                    $html .= "<option value=\"{$s}\" {$sel}>{$s}</option>";
                }
                $html .= '</select>';
                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Update inquiry status
     */
    public function updateInquiryStatus(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        $inquiry = VehicleInquiry::forBusiness($business_id)->findOrFail($id);
        $inquiry->update(['status' => $request->input('status')]);

        return response()->json(['success' => true]);
    }

    /**
     * Reports list (admin)
     */
    public function reports()
    {
        return view('carmarket::reports');
    }

    /**
     * DataTables endpoint for reports
     */
    public function getReportsDatatables(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $reports = VehicleReport::whereHas('vehicle', function ($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->with(['vehicle', 'reporter', 'reviewer'])->select('cm_vehicle_reports.*');

        if ($request->has('status') && $request->status != '') {
            $reports->where('status', $request->status);
        }

        return DataTables::of($reports)
            ->addColumn('vehicle_title', function ($r) {
                return optional($r->vehicle)->getTitle() ?? '-';
            })
            ->addColumn('reporter_name', function ($r) {
                return optional($r->reporter)->name ?? '-';
            })
            ->addColumn('action', function ($r) {
                $html = '<select class="form-control input-sm report-status-select" data-id="' . $r->id . '">';
                foreach (['pending', 'reviewed', 'resolved', 'dismissed'] as $s) {
                    $sel = $r->status == $s ? 'selected' : '';
                    $html .= "<option value=\"{$s}\" {$sel}>{$s}</option>";
                }
                $html .= '</select>';
                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Update report status
     */
    public function updateReportStatus(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        $report = VehicleReport::whereHas('vehicle', function ($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);

        $report->update([
            'status' => $request->input('status'),
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by' => Auth::id(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Settings page
     */
    public function settings()
    {
        return view('carmarket::settings');
    }

    /**
     * Show create form
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        $sellers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->orderBy('name')
            ->get();

        // Get brands (device categories)
        $brands = Category::where('business_id', $business_id)
            ->where('category_type', 'device')
            ->where('parent_id', 0)
            ->orderBy('name')
            ->get();

        return view('carmarket::create', compact('sellers', 'brands'));
    }

    /**
     * Get models by brand (for cascading dropdown)
     */
    public function getModelsByBrand($brandId)
    {
        $models = DB::table('repair_device_models')
            ->where('device_id', $brandId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($models);
    }

    /**
     * Store new vehicle listing
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = Auth::id();

        $validated = $request->validate([
            'brand_category_id' => 'nullable|exists:categories,id',
            'repair_device_model_id' => 'nullable|exists:repair_device_models,id',
            'make' => 'nullable|string|max:120',
            'model_name' => 'nullable|string|max:120',
            'year' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'trim_level' => 'nullable|string|max:120',
            'body_type' => 'nullable|string|max:60',
            'color' => 'nullable|string|max:60',
            'mileage_km' => 'nullable|integer|min:0',
            'engine_capacity_cc' => 'nullable|integer|min:0',
            'cylinder_count' => 'nullable|integer|min:2|max:16',
            'fuel_type' => 'nullable|string|max:40',
            'transmission' => 'nullable|string|max:40',
            'condition' => 'required|in:new,used',
            'factory_paint' => 'boolean',
            'imported_specs' => 'boolean',
            'license_type' => 'nullable|string|max:60',
            'listing_price' => 'required|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'condition_notes' => 'nullable|string',
            'vin_number' => 'nullable|string|max:80',
            'plate_number' => 'nullable|string|max:80',
            'location_city' => 'nullable|string|max:120',
            'location_area' => 'nullable|string|max:120',
            'seller_contact_id' => 'required|exists:contacts,id',
            'is_featured' => 'boolean',
            'is_premium' => 'boolean',
            'listing_status' => 'in:draft,pending',
            'images.*' => 'image|max:5120',
        ]);

        $validated['business_id'] = $business_id;
        $validated['created_by'] = $user_id;
        $validated['updated_by'] = $user_id;
        $validated['factory_paint'] = $request->has('factory_paint') ? (bool)$request->factory_paint : false;
        $validated['imported_specs'] = $request->has('imported_specs') ? (bool)$request->imported_specs : false;
        $validated['is_featured'] = $request->has('is_featured') ? (bool)$request->is_featured : false;
        $validated['is_premium'] = $request->has('is_premium') ? (bool)$request->is_premium : false;
        $validated['listing_status'] = $request->input('listing_status', 'pending');

        DB::beginTransaction();
        try {
            $vehicle = Vehicle::create($validated);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $order = 0;
                foreach ($request->file('images') as $image) {
                    Log::info('CarMarket image upload start', [
                        'business_id' => $business_id,
                        'vehicle_id' => $vehicle->id,
                        'original_name' => $image->getClientOriginalName(),
                        'size_kb' => $image->getSize() / 1024,
                    ]);
                    $path = $image->store('carmarket/' . $business_id, 'public');
                    Log::info('CarMarket image stored', [
                        'business_id' => $business_id,
                        'vehicle_id' => $vehicle->id,
                        'stored_path' => $path,
                        'url' => asset('storage/' . $path),
                    ]);
                    
                    VehicleMedia::create([
                        'vehicle_id' => $vehicle->id,
                        'media_type' => 'image',
                        'file_path' => $path,
                        'is_primary' => $order === 0,
                        'display_order' => $order++,
                    ]);
                }
                $vehicle->update(['media_count' => $order]);
            }

            DB::commit();

            return redirect()->route('carmarket.index')
                ->with('status', ['success' => 1, 'msg' => __('carmarket::lang.vehicle_created')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $vehicle = Vehicle::forBusiness($business_id)
            ->with(['media', 'brandCategory', 'deviceModel'])
            ->findOrFail($id);

        $sellers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->orderBy('name')
            ->get();

        // Get brands (device categories)
        $brands = Category::where('business_id', $business_id)
            ->where('category_type', 'device')
            ->where('parent_id', 0)
            ->orderBy('name')
            ->get();

        // Get models for selected brand
        $models = [];
        if ($vehicle->brand_category_id) {
            $models = DB::table('repair_device_models')
                ->where('device_id', $vehicle->brand_category_id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return view('carmarket::edit', compact('vehicle', 'sellers', 'brands', 'models'));
    }

    /**
     * Update vehicle listing
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = Auth::id();

        $vehicle = Vehicle::forBusiness($business_id)->findOrFail($id);

        $validated = $request->validate([
            'brand_category_id' => 'nullable|exists:categories,id',
            'repair_device_model_id' => 'nullable|exists:repair_device_models,id',
            'make' => 'nullable|string|max:120',
            'model_name' => 'nullable|string|max:120',
            'year' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'trim_level' => 'nullable|string|max:120',
            'body_type' => 'nullable|string|max:60',
            'color' => 'nullable|string|max:60',
            'mileage_km' => 'nullable|integer|min:0',
            'engine_capacity_cc' => 'nullable|integer|min:0',
            'cylinder_count' => 'nullable|integer|min:2|max:16',
            'fuel_type' => 'nullable|string|max:40',
            'transmission' => 'nullable|string|max:40',
            'condition' => 'required|in:new,used',
            'factory_paint' => 'boolean',
            'imported_specs' => 'boolean',
            'license_type' => 'nullable|string|max:60',
            'listing_price' => 'required|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'condition_notes' => 'nullable|string',
            'vin_number' => 'nullable|string|max:80',
            'plate_number' => 'nullable|string|max:80',
            'location_city' => 'nullable|string|max:120',
            'location_area' => 'nullable|string|max:120',
            'seller_contact_id' => 'required|exists:contacts,id',
            'is_featured' => 'boolean',
            'is_premium' => 'boolean',
            'listing_status' => 'in:draft,pending,active,sold,reserved,rejected',
            'images.*' => 'image|max:5120',
        ]);

        $validated['updated_by'] = $user_id;
        $validated['factory_paint'] = $request->has('factory_paint') ? (bool)$request->factory_paint : false;
        $validated['imported_specs'] = $request->has('imported_specs') ? (bool)$request->imported_specs : false;
        $validated['is_featured'] = $request->has('is_featured') ? (bool)$request->is_featured : false;
        $validated['is_premium'] = $request->has('is_premium') ? (bool)$request->is_premium : false;

        DB::beginTransaction();
        try {
            $vehicle->update($validated);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $order = $vehicle->media()->max('display_order') ?? -1;
                foreach ($request->file('images') as $image) {
                    Log::info('CarMarket image upload update start', [
                        'business_id' => $business_id,
                        'vehicle_id' => $vehicle->id,
                        'original_name' => $image->getClientOriginalName(),
                        'size_kb' => $image->getSize() / 1024,
                    ]);
                    $path = $image->store('carmarket/' . $business_id, 'public');
                    Log::info('CarMarket image update stored', [
                        'business_id' => $business_id,
                        'vehicle_id' => $vehicle->id,
                        'stored_path' => $path,
                        'url' => asset('storage/' . $path),
                    ]);
                    
                    VehicleMedia::create([
                        'vehicle_id' => $vehicle->id,
                        'media_type' => 'image',
                        'file_path' => $path,
                        'is_primary' => false,
                        'display_order' => ++$order,
                    ]);
                }
                $vehicle->update(['media_count' => $vehicle->media()->count()]);
            }

            DB::commit();

            return redirect()->route('carmarket.index')
                ->with('status', ['success' => 1, 'msg' => __('carmarket::lang.vehicle_updated')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * Delete a media file
     */
    public function deleteMedia($vehicleId, $mediaId)
    {
        $business_id = request()->session()->get('user.business_id');

        $vehicle = Vehicle::forBusiness($business_id)->findOrFail($vehicleId);
        $media = $vehicle->media()->findOrFail($mediaId);

        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        // Update media count
        $vehicle->update(['media_count' => $vehicle->media()->count()]);

        return response()->json(['success' => true]);
    }

    /**
     * Set primary image
     */
    public function setPrimaryImage($vehicleId, $mediaId)
    {
        $business_id = request()->session()->get('user.business_id');

        $vehicle = Vehicle::forBusiness($business_id)->findOrFail($vehicleId);
        
        // Reset all to non-primary
        $vehicle->media()->update(['is_primary' => false]);
        
        // Set selected as primary
        $vehicle->media()->where('id', $mediaId)->update(['is_primary' => true]);

        return response()->json(['success' => true]);
    }
}
