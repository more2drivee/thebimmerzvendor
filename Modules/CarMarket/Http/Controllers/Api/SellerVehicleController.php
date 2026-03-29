<?php

namespace Modules\CarMarket\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Modules\CarMarket\Entities\Vehicle;
use Modules\CarMarket\Entities\VehicleMedia;
use Modules\CarMarket\Entities\VehicleInquiry;
use Modules\CarMarket\Entities\VehicleAuditLog;

class SellerVehicleController extends Controller
{
    protected $auditableFields = [
        'brand_category_id',
        'repair_device_model_id',
        'make',
        'model_name',
        'year',
        'listing_price',
        'condition',
        'body_type',
        'fuel_type',
        'transmission',
        'mileage_km',
        'engine_capacity_cc',
        'cylinder_count',
        'color',
        'trim_level',
        'vin_number',
        'plate_number',
        'description',
        'location_city',
        'location_area',
        'latitude',
        'longitude',
        'factory_paint',
        'imported_specs',
        'license_type',
        'condition_notes',
        'min_price',
        'license_3year_cost',
        'insurance_annual_cost',
        'insurance_rate_pct',
        'currency',
    ];

    /**
     * List seller's own vehicles
     * GET /connector/api/carmarket/seller/vehicles
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Seller contact not found'], 403);
        }

        $query = Vehicle::forSeller($contact_id)
            ->with(['primaryImage', 'media', 'brandCategory:id,name', 'deviceModel:id,name'])
            ->withCount(['inquiries', 'favorites']);

        if ($request->has('listing_status') && $request->listing_status != '') {
            $query->where('listing_status', $request->listing_status);
        }

        $perPage = $request->input('per_page', 15);
        if ($perPage == -1) {
            $vehicles = $query->premiumFirst()->latest()->get();
        } else {
            $vehicles = $query->premiumFirst()->latest()->paginate($perPage);
        }

        return response()->json(['success' => true, 'data' => $vehicles]);
    }

    /**
     * Show single vehicle detail (seller's own)
     * GET /connector/api/carmarket/seller/vehicles/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $vehicle = Vehicle::forSeller($contact_id)
            ->with(['media', 'inquiries.buyer', 'favorites'])
            ->withCount(['inquiries', 'favorites'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $vehicle]);
    }

    /**
     * Create a new vehicle listing
     * POST /connector/api/carmarket/seller/vehicles
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Seller contact not found'], 403);
        }

        $validator = Validator::make($request->all(), [
            'brand_category_id' => 'required|exists:categories,id',
            'repair_device_model_id' => 'required|exists:repair_device_models,id',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'listing_price' => 'required|numeric|min:0',
            'condition' => 'required|in:new,used',
            'body_type' => 'nullable|in:sedan,suv,coupe,hatchback,truck,van,convertible,wagon,pickup,other',
            'fuel_type' => 'nullable|in:gas,diesel,electric,hybrid,natural_gas',
            'transmission' => 'nullable|in:automatic,manual',
            'mileage_km' => 'nullable|integer|min:0',
            'engine_capacity_cc' => 'nullable|integer|min:0',
            'cylinder_count' => 'nullable|integer|min:1|max:16',
            'color' => 'nullable|string|max:50',
            'trim_level' => 'nullable|string|max:100',
            'vin_number' => 'nullable|string|max:50',
            'plate_number' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'location_city' => 'nullable|string|max:100',
            'location_area' => 'nullable|string|max:100',
            'ownership_costs' => 'nullable|array',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'factory_paint' => 'nullable|boolean',
            'imported_specs' => 'nullable|boolean',
            'license_type' => 'nullable|in:seller_owned,private,commercial',
            'condition_notes' => 'nullable|string',
            'min_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'license_3year_cost' => 'nullable|numeric|min:0',
            'insurance_annual_cost' => 'nullable|numeric|min:0',
            'insurance_rate_pct' => 'nullable|numeric|min:0|max:100',
            'images' => 'nullable|array',
            'images.*' => 'nullable|string', // base64 strings
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $business_id = $user->business_id;

        try {
            DB::beginTransaction();

            $data = $validator->validated();

            $modelBelongsToBrand = DB::table('repair_device_models')
                ->where('id', $data['repair_device_model_id'])
                ->where('device_id', $data['brand_category_id'])
                ->exists();

            if (!$modelBelongsToBrand) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'msg' => 'Selected model does not belong to the selected brand',
                ], 422);
            }

            $data['make'] = DB::table('categories')
                ->where('id', $data['brand_category_id'])
                ->value('name') ?? '';

            $data['model_name'] = DB::table('repair_device_models')
                ->where('id', $data['repair_device_model_id'])
                ->value('name') ?? '';

            $data['business_id'] = $business_id;
            $data['seller_contact_id'] = $contact_id;
            $data['created_by'] = $user->id;
            $data['listing_status'] = 'pending';
            $data['currency'] = $request->input('currency', 'EGP');

            $vehicle = Vehicle::create($data);

            // Handle base64 images if provided
            $uploadedMedia = [];
            if (!empty($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $index => $base64Image) {
                    $uploadedMedia[] = $this->saveBase64Image($vehicle->id, $base64Image, $index, $business_id);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => 'Vehicle listing created successfully. Pending admin approval.',
                'data' => $vehicle->load(['media', 'brandCategory:id,name', 'deviceModel:id,name']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CarMarket store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Error creating listing'], 500);
        }
    }

    /**
     * Update seller's own vehicle listing
     * PUT /connector/api/carmarket/seller/vehicles/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $vehicle = Vehicle::forSeller($contact_id)->findOrFail($id);

        if (in_array($vehicle->listing_status, ['sold'])) {
            return response()->json(['success' => false, 'msg' => 'Cannot edit a sold listing'], 403);
        }

        $validator = Validator::make($request->all(), [
            'brand_category_id' => 'nullable|exists:categories,id',
            'repair_device_model_id' => 'nullable|exists:repair_device_models,id',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'listing_price' => 'nullable|numeric|min:0',
            'condition' => 'nullable|in:new,used',
            'body_type' => 'nullable|in:sedan,suv,coupe,hatchback,truck,van,convertible,wagon,pickup,other',
            'fuel_type' => 'nullable|in:gas,diesel,electric,hybrid,natural_gas',
            'transmission' => 'nullable|in:automatic,manual',
            'mileage_km' => 'nullable|integer|min:0',
            'engine_capacity_cc' => 'nullable|integer|min:0',
            'cylinder_count' => 'nullable|integer|min:1|max:16',
            'color' => 'nullable|string|max:50',
            'trim_level' => 'nullable|string|max:100',
            'vin_number' => 'nullable|string|max:50',
            'plate_number' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'location_city' => 'nullable|string|max:100',
            'location_area' => 'nullable|string|max:100',
            'ownership_costs' => 'nullable|array',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'factory_paint' => 'nullable|boolean',
            'imported_specs' => 'nullable|boolean',
            'license_type' => 'nullable|in:seller_owned,private,commercial',
            'condition_notes' => 'nullable|string',
            'min_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'license_3year_cost' => 'nullable|numeric|min:0',
            'insurance_annual_cost' => 'nullable|numeric|min:0',
            'insurance_rate_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $validatedData = array_filter($validator->validated(), function ($v) {
                return $v !== null;
            });

            $brandId = $validatedData['brand_category_id'] ?? $vehicle->brand_category_id;
            $modelId = $validatedData['repair_device_model_id'] ?? $vehicle->repair_device_model_id;

            if (!empty($brandId) && !empty($modelId)) {
                $modelBelongsToBrand = DB::table('repair_device_models')
                    ->where('id', $modelId)
                    ->where('device_id', $brandId)
                    ->exists();

                if (!$modelBelongsToBrand) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'msg' => 'Selected model does not belong to the selected brand',
                    ], 422);
                }
            }

            if (!empty($brandId)) {
                $validatedData['make'] = DB::table('categories')
                    ->where('id', $brandId)
                    ->value('name') ?? '';
            }

            if (!empty($modelId)) {
                $validatedData['model_name'] = DB::table('repair_device_models')
                    ->where('id', $modelId)
                    ->value('name') ?? '';
            }

            $changeSet = $this->extractChangedFields($vehicle, $validatedData);

            if (empty($changeSet['changed_fields'])) {
                DB::rollBack();
                return response()->json([
                    'success' => true,
                    'msg' => 'No changes detected',
                    'data' => $vehicle->fresh()->load('media'),
                ]);
            }

            $vehicle->update($validatedData);

            // Any seller edit requires fresh admin approval.
            $vehicle->update([
                'listing_status' => 'pending',
                'approved_at' => null,
                'rejection_reason' => null,
            ]);

            $auditLog = VehicleAuditLog::create([
                'business_id' => $user->business_id,
                'vehicle_id' => $vehicle->id,
                'changed_by_user_id' => $user->id,
                'changed_by_contact_id' => $contact_id,
                'change_source' => 'seller_api',
                'action' => 'seller_updated_listing',
                'changed_fields' => $changeSet['changed_fields'],
                'old_values' => $changeSet['old_values'],
                'new_values' => $changeSet['new_values'],
                'notes' => 'Seller updated listing. Sent back to pending approval.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => 'Vehicle updated and sent for admin re-approval',
                'data' => $vehicle->fresh()->load('media'),
                'requires_reapproval' => true,
                'audit_log' => [
                    'id' => $auditLog->id,
                    'changed_fields' => $changeSet['changed_fields'],
                    'created_at' => $auditLog->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CarMarket update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Error updating listing'], 500);
        }
    }

    protected function extractChangedFields(Vehicle $vehicle, array $validatedData): array
    {
        $changedFields = [];
        $oldValues = [];
        $newValues = [];

        foreach ($this->auditableFields as $field) {
            if (!array_key_exists($field, $validatedData)) {
                continue;
            }

            $old = $vehicle->{$field};
            $new = $validatedData[$field];

            if ((string) $old !== (string) $new) {
                $changedFields[] = $field;
                $oldValues[$field] = $old;
                $newValues[$field] = $new;
            }
        }

        return [
            'changed_fields' => $changedFields,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ];
    }

    /**
     * Delete seller's own vehicle listing
     * DELETE /connector/api/carmarket/seller/vehicles/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $vehicle = Vehicle::forSeller($contact_id)->findOrFail($id);

        $vehicle->delete();

        return response()->json(['success' => true, 'msg' => 'Listing deleted']);
    }

    /**
     * Upload media for a vehicle
     * POST /connector/api/carmarket/seller/vehicles/{id}/media
     */
    public function uploadMedia(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $vehicle = Vehicle::forSeller($contact_id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'media_type' => 'nullable|in:exterior,interior,engine,documents,other',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $mediaType = $request->input('media_type', 'exterior');
        $uploaded = [];

        foreach ($request->file('images') as $index => $image) {
            $fileName = 'cm_' . $vehicle->id . '_' . time() . '_' . $index . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('carmarket/' . $user->business_id, $fileName, 'public');

            $isFirst = $vehicle->media()->count() === 0 && $index === 0;

            $media = VehicleMedia::create([
                'vehicle_id' => $vehicle->id,
                'media_type' => $mediaType,
                'file_path' => $path,
                'file_name' => $image->getClientOriginalName(),
                'file_size_kb' => intval($image->getSize() / 1024),
                'display_order' => $vehicle->media()->count(),
                'is_primary' => $isFirst,
            ]);

            $uploaded[] = $media;
        }

        return response()->json([
            'success' => true,
            'msg' => count($uploaded) . ' image(s) uploaded',
            'data' => $uploaded,
        ]);
    }

    /**
     * Delete a media item
     * DELETE /connector/api/carmarket/seller/vehicles/{vehicleId}/media/{mediaId}
     */
    public function deleteMedia(Request $request, $vehicleId, $mediaId)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $vehicle = Vehicle::forSeller($contact_id)->findOrFail($vehicleId);
        $media = $vehicle->media()->findOrFail($mediaId);

        // If deleting primary, assign next one as primary
        if ($media->is_primary) {
            $next = $vehicle->media()->where('id', '!=', $mediaId)->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        // Delete file from storage
        \Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['success' => true, 'msg' => 'Media deleted']);
    }

    /**
     * Set primary image
     * POST /connector/api/carmarket/seller/vehicles/{vehicleId}/media/{mediaId}/set-primary
     */
    public function setPrimaryMedia(Request $request, $vehicleId, $mediaId)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $vehicle = Vehicle::forSeller($contact_id)->findOrFail($vehicleId);

        // Reset all to non-primary
        $vehicle->media()->update(['is_primary' => false]);

        // Set the selected one as primary
        $vehicle->media()->where('id', $mediaId)->update(['is_primary' => true]);

        return response()->json(['success' => true, 'msg' => 'Primary image updated']);
    }

    /**
     * Get inquiries for seller's vehicles
     * GET /connector/api/carmarket/seller/inquiries
     */
    public function inquiries(Request $request)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $query = VehicleInquiry::whereHas('vehicle', function ($q) use ($contact_id) {
            $q->where('seller_contact_id', $contact_id);
        })->with(['vehicle', 'buyer']);

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 15);
        $inquiries = $query->latest()->paginate($perPage);

        return response()->json(['success' => true, 'data' => $inquiries]);
    }

    /**
     * Reply to an inquiry
     * POST /connector/api/carmarket/seller/inquiries/{id}/reply
     */
    public function replyInquiry(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $inquiry = VehicleInquiry::whereHas('vehicle', function ($q) use ($contact_id) {
            $q->where('seller_contact_id', $contact_id);
        })->findOrFail($id);

        $inquiry->update([
            'seller_reply' => $request->input('reply'),
            'status' => 'contacted',
        ]);

        return response()->json(['success' => true, 'msg' => 'Reply sent', 'data' => $inquiry->fresh()]);
    }

    /**
     * Mark vehicle as sold
     * POST /connector/api/carmarket/seller/vehicles/{id}/mark-sold
     */
    public function markSold(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $vehicle = Vehicle::forSeller($contact_id)->findOrFail($id);

        $vehicle->update([
            'listing_status' => 'sold',
            'sold_at' => now(),
            'sold_price' => $request->input('sold_price', $vehicle->listing_price),
            'buyer_contact_id' => $request->input('buyer_contact_id'),
        ]);

        return response()->json(['success' => true, 'msg' => 'Vehicle marked as sold', 'data' => $vehicle->fresh()]);
    }

    /**
     * Seller dashboard stats
     * GET /connector/api/carmarket/seller/dashboard
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $stats = [
            'total_listings' => Vehicle::forSeller($contact_id)->count(),
            'active' => Vehicle::forSeller($contact_id)->active()->count(),
            'pending' => Vehicle::forSeller($contact_id)->pending()->count(),
            'sold' => Vehicle::forSeller($contact_id)->where('listing_status', 'sold')->count(),
            'total_views' => Vehicle::forSeller($contact_id)->sum('view_count'),
            'total_inquiries' => VehicleInquiry::whereHas('vehicle', function ($q) use ($contact_id) {
                $q->where('seller_contact_id', $contact_id);
            })->count(),
            'new_inquiries' => VehicleInquiry::whereHas('vehicle', function ($q) use ($contact_id) {
                $q->where('seller_contact_id', $contact_id);
            })->new()->count(),
        ];

        $recentInquiries = VehicleInquiry::whereHas('vehicle', function ($q) use ($contact_id) {
            $q->where('seller_contact_id', $contact_id);
        })->with(['vehicle', 'buyer'])->latest()->limit(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_inquiries' => $recentInquiries,
            ],
        ]);
    }

    /**
     * Save base64 encoded image for a vehicle
     */
    private function saveBase64Image(int $vehicleId, string $base64String, int $index = 0, int $businessId = null): ?VehicleMedia
    {
        try {
            // Handle data URL format (data:image/jpeg;base64,...)
            if (strpos($base64String, ',') !== false) {
                $parts = explode(',', $base64String, 2);
                $base64String = $parts[1] ?? $parts[0];
            }

            // Validate base64
            if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $base64String)) {
                \Log::warning('Invalid base64 string for vehicle image', ['vehicle_id' => $vehicleId]);
                return null;
            }

            $binary = base64_decode($base64String, true);
            if ($binary === false) {
                \Log::warning('Failed to decode base64 for vehicle image', ['vehicle_id' => $vehicleId]);
                return null;
            }

            // Detect mime type from binary
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($binary);

            // Map mime to extension
            $extensionMap = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];

            $extension = $extensionMap[$mime] ?? 'jpg';

            // Generate filename and path
            $fileName = 'cm_' . $vehicleId . '_' . time() . '_' . $index . '.' . $extension;
            $path = 'carmarket/' . $businessId . '/' . $fileName;

            // Save to storage
            Storage::disk('public')->put($path, $binary);

            // Count existing media to determine display_order
            $mediaCount = VehicleMedia::where('vehicle_id', $vehicleId)->count();
            $isFirst = $mediaCount === 0 && $index === 0;

            // Create media record
            return VehicleMedia::create([
                'vehicle_id' => $vehicleId,
                'media_type' => 'exterior',
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size_kb' => intval(strlen($binary) / 1024),
                'display_order' => $mediaCount,
                'is_primary' => $isFirst,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving base64 image for vehicle', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
