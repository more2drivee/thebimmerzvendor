<?php

namespace Modules\CarMarket\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\CarMarket\Entities\Vehicle;
use Modules\CarMarket\Entities\VehicleInquiry;
use Modules\CarMarket\Entities\Favorite;
use Modules\CarMarket\Entities\VehicleReport;
use Modules\CarMarket\Entities\SavedSearch;

class BuyerVehicleController extends Controller
{
    /**
     * Search & list active vehicles (public marketplace) - card/list view
     * GET /connector/api/carmarket/vehicles
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $business_id = $user->business_id;

        $query = Vehicle::forBusiness($business_id)->active();

        // Select only card/list fields
        $query->select([
            'id',
            'make',
            'model_name',
            'year',
            'trim_level',
            'body_type',
            'color',
            'mileage_km',
            'listing_price',
            'currency',
            'location_city',
            'is_premium',
            'is_featured',
            'view_count',
            'brand_category_id',
            'repair_device_model_id',
        ]);

        $query->with(['primaryImage:id,vehicle_id,file_path', 'seller:id,name'])
              ->withCount(['favorites', 'inquiries']);

        // ── Filters ──
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('brand_category_id')) {
            $query->where('brand_category_id', $request->brand_category_id);
        }
        if ($request->filled('repair_device_model_id')) {
            $query->where('repair_device_model_id', $request->repair_device_model_id);
        }
        // Legacy filters for backward compatibility
        if ($request->filled('make')) {
            $query->where('make', $request->make);
        }
        if ($request->filled('model_name')) {
            $query->where('model_name', $request->model_name);
        }
        if ($request->filled('year_from')) {
            $query->where('year', '>=', $request->year_from);
        }
        if ($request->filled('year_to')) {
            $query->where('year', '<=', $request->year_to);
        }
        if ($request->filled('price_from')) {
            $query->where('listing_price', '>=', $request->price_from);
        }
        if ($request->filled('price_to')) {
            $query->where('listing_price', '<=', $request->price_to);
        }
        if ($request->filled('mileage_from')) {
            $query->where('mileage_km', '>=', $request->mileage_from);
        }
        if ($request->filled('mileage_to')) {
            $query->where('mileage_km', '<=', $request->mileage_to);
        }
        if ($request->filled('body_type')) {
            $query->where('body_type', $request->body_type);
        }
        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->fuel_type);
        }
        if ($request->filled('transmission')) {
            $query->where('transmission', $request->transmission);
        }
        if ($request->filled('color')) {
            $query->where('color', $request->color);
        }
        if ($request->filled('location_city')) {
            $query->where('location_city', $request->location_city);
        }
        if ($request->filled('location_area')) {
            $query->where('location_area', $request->location_area);
        }
        if ($request->filled('factory_paint')) {
            $query->where('factory_paint', (bool) $request->factory_paint);
        }
        if ($request->filled('imported_specs')) {
            $query->where('imported_specs', (bool) $request->imported_specs);
        }

        // ── Search keyword ──
        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('make', 'like', "%{$keyword}%")
                  ->orWhere('model_name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('trim_level', 'like', "%{$keyword}%")
                  ->orWhereHas('brandCategory', function ($bq) use ($keyword) {
                      $bq->where('name', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('deviceModel', function ($mq) use ($keyword) {
                      $mq->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        // ── Sorting ──
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'listing_price', 'year', 'mileage_km', 'view_count'];

        if (in_array($sortBy, $allowedSorts)) {
            // Premium listings always first
            $query->premiumFirst()->orderBy($sortBy, $sortDir);
        } else {
            $query->premiumFirst()->latest();
        }

        // ── Pagination ──
        $perPage = $request->input('per_page', 15);
        if ($perPage == -1) {
            $vehicles = $query->get();
        } else {
            $vehicles = $query->paginate($perPage);
        }

        // Add is_favorited flag if buyer is logged in
        $contact_id = $user->crm_contact_id ?? null;
        if ($contact_id) {
            $favIds = Favorite::where('contact_id', $contact_id)->pluck('vehicle_id')->toArray();
            $collection = ($perPage == -1) ? $vehicles : $vehicles->getCollection();
            $collection->transform(function ($v) use ($favIds) {
                $v->is_favorited = in_array($v->id, $favIds);
                return $v;
            });
        }

        // Hide internal fields from list response
        $hiddenFields = [
            'business_id',
            'seller_contact_id',
            'buyer_contact_id',
            'vin_number',
            'plate_number',
            'brand_category_id',
            'repair_device_model_id',
            'engine_capacity_cc',
            'cylinder_count',
            'fuel_type',
            'transmission',
            'condition',
            'factory_paint',
            'imported_specs',
            'license_type',
            'min_price',
            'license_3year_cost',
            'insurance_annual_cost',
            'insurance_rate_pct',
            'listing_status',
            'rejection_reason',
            'expires_at',
            'media_count',
            'created_by',
            'updated_by',
            'deleted_at',
            'brand_category',
            'device_model',
        ];

        if ($perPage == -1) {
            $vehicles->makeHidden($hiddenFields);
        } else {
            $vehicles->getCollection()->makeHidden($hiddenFields);
        }

        return response()->json(['success' => true, 'data' => $vehicles]);
    }

    /**
     * Show single vehicle detail
     * GET /connector/api/carmarket/vehicles/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $business_id = $user->business_id;

        $vehicle = Vehicle::forBusiness($business_id)
            ->active()
            ->with(['media', 'seller:id,name,mobile,email,type'])
            ->withCount(['favorites', 'inquiries'])
            ->findOrFail($id);

        // Increment view count
        $vehicle->increment('view_count');

        // Check if favorited by current buyer
        $contact_id = $user->crm_contact_id ?? null;
        $vehicle->is_favorited = false;
        if ($contact_id) {
            $vehicle->is_favorited = Favorite::where('contact_id', $contact_id)
                ->where('vehicle_id', $id)->exists();
        }

        // Get similar vehicles
        $similar = $vehicle->getSimilarVehicles(6);

        return response()->json([
            'success' => true,
            'data' => [
                'vehicle' => $vehicle,
                'similar' => $similar,
            ],
        ]);
    }

    /**
     * Get available filter options with pagination and search
     * GET /connector/api/carmarket/filters?type=brands&search=toyota&per_page=15
     * 
     * Supported types: brands, models, cities, colors, body_types, fuel_types, transmissions, conditions
     * Returns paginated results with search capability
     */
    public function filters(Request $request)
    {
        $user = $request->user();
        $business_id = $user->business_id;
        $type = $request->input('type', 'brands');
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 15);
        $brandId = $request->input('brand_category_id');

        // Static filter options (non-paginated)
        $staticFilters = [
            'body_types' => ['sedan', 'suv', 'coupe', 'hatchback', 'truck', 'van', 'convertible', 'wagon', 'pickup', 'other'],
            'fuel_types' => ['gas', 'diesel', 'electric', 'hybrid', 'natural_gas'],
            'transmissions' => ['automatic', 'manual'],
            'conditions' => ['new', 'used'],
        ];

        // Return static filters directly
        if (in_array($type, array_keys($staticFilters))) {
            $options = $staticFilters[$type];
            if ($search) {
                $options = array_filter($options, fn($opt) => stripos($opt, $search) !== false);
            }
            return response()->json([
                'success' => true,
                'type' => $type,
                'data' => array_values($options),
            ]);
        }

        // Dynamic filters with pagination
        $base = Vehicle::forBusiness($business_id)->active();

        switch ($type) {
            case 'brands':
                return $this->getPaginatedBrands($request, $business_id, $search, $perPage);

            case 'models':
                return $this->getPaginatedModels($request, $business_id, $brandId, $search, $perPage);

            case 'cities':
                return $this->getPaginatedCities($request, $base, $search, $perPage);

            case 'colors':
                return $this->getPaginatedColors($request, $base, $search, $perPage);

            case 'year_range':
                return response()->json([
                    'success' => true,
                    'type' => 'year_range',
                    'data' => [
                        'min' => $base->min('year'),
                        'max' => $base->max('year'),
                    ],
                ]);

            case 'price_range':
                return response()->json([
                    'success' => true,
                    'type' => 'price_range',
                    'data' => [
                        'min' => $base->min('listing_price'),
                        'max' => $base->max('listing_price'),
                    ],
                ]);

            default:
                return response()->json([
                    'success' => false,
                    'msg' => 'Invalid filter type',
                ], 400);
        }
    }

    /**
     * Get paginated brands with search
     */
    private function getPaginatedBrands($request, $businessId, $search = '', $perPage = 15)
    {
        $query = \App\Category::where('business_id', $businessId)
            ->where('category_type', 'device')
            ->where('parent_id', 0);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $brands = $query->orderBy('name')->paginate($perPage, ['id', 'name']);

        return response()->json([
            'success' => true,
            'type' => 'brands',
            'data' => $brands->items(),
            'pagination' => [
                'total' => $brands->total(),
                'per_page' => $brands->perPage(),
                'current_page' => $brands->currentPage(),
                'last_page' => $brands->lastPage(),
                'has_more' => $brands->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get paginated models with search, optionally filtered by brand
     */
    private function getPaginatedModels($request, $businessId, $brandId = null, $search = '', $perPage = 15)
    {
        $query = \DB::table('repair_device_models as rdm')
            ->join('categories as c', 'rdm.device_id', '=', 'c.id')
            ->where('c.business_id', $businessId)
            ->where('c.category_type', 'device');

        if ($brandId) {
            $query->where('rdm.device_id', $brandId);
        }

        if ($search) {
            $query->where('rdm.name', 'like', "%{$search}%");
        }

        $page = $request->input('page', 1);
        $total = $query->count();
        $models = $query->orderBy('rdm.name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['rdm.id', 'rdm.name', 'rdm.device_id as brand_category_id']);

        return response()->json([
            'success' => true,
            'type' => 'models',
            'data' => $models,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    /**
     * Get paginated cities with search
     */
    private function getPaginatedCities($request, $baseQuery, $search = '', $perPage = 15)
    {
        $query = (clone $baseQuery)->distinct();

        if ($search) {
            $query->where('location_city', 'like', "%{$search}%");
        }

        $page = $request->input('page', 1);
        $total = $query->count('location_city');
        $cities = $query->orderBy('location_city')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->pluck('location_city')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'type' => 'cities',
            'data' => $cities,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    /**
     * Get paginated colors with search
     */
    private function getPaginatedColors($request, $baseQuery, $search = '', $perPage = 15)
    {
        $query = (clone $baseQuery)->distinct();

        if ($search) {
            $query->where('color', 'like', "%{$search}%");
        }

        $page = $request->input('page', 1);
        $total = $query->count('color');
        $colors = $query->orderBy('color')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->pluck('color')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'type' => 'colors',
            'data' => $colors,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
        ]);
    }
    /**
     * Get all filter options at once with optional search
     * GET /connector/api/carmarket/filters-all?search=camry
     * Search works across all filter types (brands, models, cities, colors, body_types, fuel_types, transmissions, conditions)
     * Returns only dynamic filters (brands, models, cities, colors) filtered by vehicles in cm_vehicles
     */
    public function allFilters(Request $request)
    {
        $user = $request->user();
        $business_id = $user->business_id;
        $search = trim((string) $request->input('search', ''));
        $brandCategoryId = $request->input('brand_category_id');
        $repairDeviceModelId = $request->input('repair_device_model_id');

        $brandIdsFromSearch = [];
        $modelIdsFromSearch = [];

        if ($search !== '') {
            $brandIdsFromSearch = DB::table('categories')
                ->where('business_id', $business_id)
                ->where('category_type', 'device')
                ->where('parent_id', 0)
                ->where('name', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $modelIdsFromSearch = DB::table('repair_device_models as rdm')
                ->join('categories as c', 'c.id', '=', 'rdm.device_id')
                ->where('c.business_id', $business_id)
                ->where('c.category_type', 'device')
                ->where('rdm.name', 'like', "%{$search}%")
                ->pluck('rdm.id')
                ->toArray();
        }

        $base = Vehicle::forBusiness($business_id)
            ->active()
            ->with(['brandCategory:id,name', 'deviceModel:id,name'])
            ->when($brandCategoryId, function ($query) use ($brandCategoryId) {
                $query->where('brand_category_id', $brandCategoryId);
            })
            ->when($repairDeviceModelId, function ($query) use ($repairDeviceModelId) {
                $query->where('repair_device_model_id', $repairDeviceModelId);
            })
            ->when($request->filled('condition'), function ($query) use ($request) {
                $query->where('condition', $request->input('condition'));
            })
            ->when($request->filled('body_type'), function ($query) use ($request) {
                $query->where('body_type', $request->input('body_type'));
            })
            ->when($request->filled('fuel_type'), function ($query) use ($request) {
                $query->where('fuel_type', $request->input('fuel_type'));
            })
            ->when($request->filled('transmission'), function ($query) use ($request) {
                $query->where('transmission', $request->input('transmission'));
            })
            ->when($request->filled('color'), function ($query) use ($request) {
                $query->where('color', $request->input('color'));
            })
            ->when($request->filled('location_city'), function ($query) use ($request) {
                $query->where('location_city', $request->input('location_city'));
            })
            ->when($request->filled('year_from'), function ($query) use ($request) {
                $query->where('year', '>=', $request->input('year_from'));
            })
            ->when($request->filled('year_to'), function ($query) use ($request) {
                $query->where('year', '<=', $request->input('year_to'));
            })
            ->when($request->filled('price_from'), function ($query) use ($request) {
                $query->where('listing_price', '>=', $request->input('price_from'));
            })
            ->when($request->filled('price_to'), function ($query) use ($request) {
                $query->where('listing_price', '<=', $request->input('price_to'));
            })
            ->when($search !== '', function ($query) use ($search, $brandIdsFromSearch, $modelIdsFromSearch) {
                $query->where(function ($subQuery) use ($search, $brandIdsFromSearch, $modelIdsFromSearch) {
                    $subQuery->where('location_city', 'like', "%{$search}%")
                        ->orWhere('color', 'like', "%{$search}%")
                        ->orWhere('body_type', 'like', "%{$search}%")
                        ->orWhere('fuel_type', 'like', "%{$search}%")
                        ->orWhere('transmission', 'like', "%{$search}%")
                        ->orWhere('condition', 'like', "%{$search}%");

                    if (!empty($brandIdsFromSearch)) {
                        $subQuery->orWhereIn('brand_category_id', $brandIdsFromSearch);
                    }

                    if (!empty($modelIdsFromSearch)) {
                        $subQuery->orWhereIn('repair_device_model_id', $modelIdsFromSearch);
                    }
                });
            });

        $vehicles = $base->get();

        $payload = $vehicles->map(function ($vehicle) {
            $payload = $vehicle->toArray();
            $payload['brand'] = $vehicle->brandCategory->name ?? null;
            $payload['model'] = $vehicle->deviceModel->name ?? null;
            unset($payload['brand_category'], $payload['device_model']);
            return $payload;
        });

        return $payload;
    }

    /**
     * Get models by brand (for cascading dropdown)
     * GET /connector/api/carmarket/brands/{brandId}/models
     */
    public function getModelsByBrand(Request $request, $brandId)
    {
        $models = \DB::table('repair_device_models')
            ->where('device_id', $brandId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $models]);
    }

    /**
     * Send inquiry to seller
     * POST /connector/api/carmarket/vehicles/{id}/inquiry
     */
    public function sendInquiry(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Login required to send inquiry'], 403);
        }

        $vehicle = Vehicle::active()->findOrFail($id);

        // Prevent seller from inquiring on own vehicle
        if ($vehicle->seller_contact_id == $contact_id) {
            return response()->json(['success' => false, 'msg' => 'Cannot inquire on your own listing'], 422);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:1000',
            'inquiry_type' => 'nullable|in:whatsapp,call,email,in_app,other',
            'offered_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $inquiry = VehicleInquiry::create([
            'business_id' => $vehicle->business_id,
            'vehicle_id' => $vehicle->id,
            'buyer_contact_id' => $contact_id,
            'inquiry_type' => $request->input('inquiry_type', 'in_app'),
            'message' => $request->input('message'),
            'offered_price' => $request->input('offered_price'),
            'status' => 'new',
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Inquiry sent successfully',
            'data' => $inquiry->load('vehicle'),
        ], 201);
    }

    /**
     * Get buyer's own inquiries
     * GET /connector/api/carmarket/buyer/inquiries
     */
    public function myInquiries(Request $request)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Login required'], 403);
        }

        $inquiries = VehicleInquiry::where('buyer_contact_id', $contact_id)
            ->with(['vehicle.primaryImage', 'vehicle.seller:id,name,mobile'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $inquiries]);
    }

    /**
     * Toggle favorite
     * POST /connector/api/carmarket/vehicles/{id}/favorite
     */
    public function toggleFavorite(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Login required'], 403);
        }

        $vehicle = Vehicle::findOrFail($id);

        $existing = Favorite::where('contact_id', $contact_id)->where('vehicle_id', $id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'msg' => 'Removed from favorites', 'is_favorited' => false]);
        }

        Favorite::create([
            'contact_id' => $contact_id,
            'vehicle_id' => $id,
            'notify_price_change' => $request->input('notify_price_change', false),
        ]);

        return response()->json(['success' => true, 'msg' => 'Added to favorites', 'is_favorited' => true]);
    }

    /**
     * Get buyer's favorites
     * GET /connector/api/carmarket/buyer/favorites
     */
    public function favorites(Request $request)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Login required'], 403);
        }

        $favorites = Favorite::where('contact_id', $contact_id)
            ->with(['vehicle.primaryImage', 'vehicle.seller:id,name,mobile'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $favorites]);
    }

    /**
     * Report a listing
     * POST /connector/api/carmarket/vehicles/{id}/report
     */
    public function reportListing(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Login required'], 403);
        }

        $vehicle = Vehicle::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|in:fake_listing,wrong_price,wrong_info,fraud,duplicate,other',
            'details' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $report = VehicleReport::create([
            'vehicle_id' => $id,
            'reported_by_contact_id' => $contact_id,
            'reason' => $request->reason,
            'details' => $request->details,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'msg' => 'Report submitted', 'data' => $report], 201);
    }

    /**
     * Save a search
     * POST /connector/api/carmarket/buyer/saved-searches
     */
    public function saveSearch(Request $request)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Login required'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'filters' => 'required|array',
            'notify_new_matches' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'msg' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $search = SavedSearch::create([
            'contact_id' => $contact_id,
            'name' => $request->input('name'),
            'filters' => $request->input('filters'),
            'notify_new_matches' => $request->input('notify_new_matches', false),
        ]);

        return response()->json(['success' => true, 'msg' => 'Search saved', 'data' => $search], 201);
    }

    /**
     * Get saved searches
     * GET /connector/api/carmarket/buyer/saved-searches
     */
    public function savedSearches(Request $request)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        if (empty($contact_id)) {
            return response()->json(['success' => false, 'msg' => 'Login required'], 403);
        }

        $searches = SavedSearch::where('contact_id', $contact_id)->latest()->get();

        return response()->json(['success' => true, 'data' => $searches]);
    }

    /**
     * Delete a saved search
     * DELETE /connector/api/carmarket/buyer/saved-searches/{id}
     */
    public function deleteSavedSearch(Request $request, $id)
    {
        $user = $request->user();
        $contact_id = $user->crm_contact_id ?? null;

        $search = SavedSearch::where('contact_id', $contact_id)->findOrFail($id);
        $search->delete();

        return response()->json(['success' => true, 'msg' => 'Saved search deleted']);
    }

    /**
     * Get featured/homepage vehicles
     * GET /connector/api/carmarket/featured
     */
    public function featured(Request $request)
    {
        $user = $request->user();
        $business_id = $user->business_id;

        $featured = Vehicle::forBusiness($business_id)
            ->active()
            ->featured()
            ->with(['primaryImage', 'seller:id,name'])
            ->premiumFirst()
            ->limit($request->input('limit', 10))
            ->get();

        $latest = Vehicle::forBusiness($business_id)
            ->active()
            ->with(['primaryImage', 'seller:id,name'])
            ->latest()
            ->limit($request->input('limit', 10))
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'featured' => $featured,
                'latest' => $latest,
            ],
        ]);
    }
}
