<?php

namespace Modules\Repair\Http\Controllers;

use App\Unit;
use App\User;
use App\Product;
use App\ProductCompatibility;
use App\Contact;
use App\Transaction;
use App\BusinessLocation;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Events\PurchaseCreatedOrModified;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Repair\Entities\JobSheet;
use Illuminate\Support\Facades\Notification;
use Modules\Repair\Entities\MaintenanceNote;
use App\Notifications\MaintenanceNoteUpdated;
use Modules\Connector\Http\Controllers\Api\SparePartsController;

class MaintenanceNoteController extends Controller
{
    protected ProductUtil $productUtil;
    protected TransactionUtil $transactionUtil;
    protected SparePartsController $sparePartsController;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, SparePartsController $sparePartsController)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->sparePartsController = $sparePartsController;
    }

    /**
     * AJAX supplier search for Select2 in maintenance note line items.
     * Filters by optional location_id (preferred) or derives it from note_id.
     */
    public function suppliersSearch(Request $request)
    {
    
        $q = trim((string) $request->input('q', ''));
      

        $query = Contact::query()
            ->whereIn('contacts.type', ['supplier', 'both'])
            ->where('contacts.contact_status', 'active');


        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($s) use ($like) {
                $s->where('contacts.name', 'like', $like)
                //   ->orWhere('contacts.supplier_business_name', 'like', $like)
                  ->orWhere('contacts.mobile', 'like', $like)
                  ->orWhere('contacts.contact_id', 'like', $like)
                  ->orWhere('contacts.first_name', 'like', $like)
                  ->orWhere('contacts.middle_name', 'like', $like)
                  ->orWhere('contacts.last_name', 'like', $like);
            });
        }

        $suppliers = $query
            ->orderBy('contacts.name')
            ->limit(20)
            ->get([
                'contacts.id',
                DB::raw("TRIM(CONCAT(COALESCE(contacts.supplier_business_name, ''), CASE WHEN COALESCE(contacts.supplier_business_name, '') <> '' THEN ' - ' ELSE '' END, COALESCE(contacts.name, ''))) AS text"),
            ]);

        return response()->json([
            'results' => $suppliers->map(function ($row) {
                return ['id' => (string) $row->id, 'text' => $row->text];
            })->values(),
        ]);
    }

   
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        /** @var \App\User&\Illuminate\Contracts\Auth\Access\Authorizable|null $user */
        $user = Auth::user();
        if (! ($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            abort(403, 'Unauthorized action.');
        }

        $isAdmin = $user->hasRole('Admin#'.$business_id) || $user->can('superadmin');
        $permittedLocations = $user->permitted_locations($business_id);

        $selectedLocation = $request->input('location_id');
        $selectedStatus = $request->input('status');
        $jobSheetSearch = trim((string) $request->input('job_sheet'));

        if ($permittedLocations !== 'all' && ! $isAdmin) {
            $allowed = (array) $permittedLocations;
            if ($selectedLocation && ! in_array((int) $selectedLocation, $allowed, true)) {
                $selectedLocation = null;
            }
        }

        $notesQuery = $this->maintenanceNoteQuery(
            $selectedLocation ? (int) $selectedLocation : null,
            $selectedStatus,
            $jobSheetSearch,
            $permittedLocations,
            $isAdmin
        );

        /** @var \Illuminate\Pagination\LengthAwarePaginator $notes */
        $notes = $notesQuery
            ->latest()
            ->paginate(12)
            ->appends($request->only('location_id', 'status', 'job_sheet'));

        $statusMeta = $this->getStatusMeta();

        // Transform results
        $notes->getCollection()->transform(function (MaintenanceNote $note) {
            return $this->decorateMaintenanceNote($note);
        });

        $categories = DB::table('categories')
            ->where('category_type', 'product')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = DB::table('brands')
            ->orderBy('name')
            ->get(['id', 'name']);

        $units = DB::table('units')
            ->orderBy('actual_name')
            ->get(['id', 'actual_name']);

        $subCategories = DB::table('categories')
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $suppliers = DB::table('contacts')
            ->where('type', 'supplier')
            ->orderBy('name')
            ->get(['id', 'name', 'supplier_business_name'])
            ->map(function ($supplier) {
                $parts = array_filter([$supplier->supplier_business_name, $supplier->name]);
                $supplier->display_name = ! empty($parts) ? implode(' - ', $parts) : ($supplier->name ?? '');
                return $supplier;
            });

        $locations = BusinessLocation::where('business_id', $business_id)
            ->active()
            ->when($permittedLocations !== 'all' && ! $isAdmin, function ($query) use ($permittedLocations) {
                $query->whereIn('id', (array) $permittedLocations);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $availableStatuses = MaintenanceNote::where('category_status', 'purchase_req')
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->pluck('status')
            ->filter()
            ->values();

        return view('repair::maintenance_notes.index', compact(
            'notes',
            'statusMeta',
            'categories',
            'brands',
            'units',
            'subCategories',
            'suppliers',
            'locations',
            'selectedLocation',
            'selectedStatus',
            'jobSheetSearch',
            'availableStatuses',
            'isAdmin'
        ));
    }

    public function quickAddSupplier(Request $request, $id)
    {
        /** @var \App\User|null $user */
        $user = Auth::user();
        if (! ($user && ($user->can('supplier.create') || $user->can('supplier.view_own')))) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized_action'),
            ], 403);
        }

        $input = $request->only([
            'first_name',
            'middle_name',
            'mobile',
        ]);

        $validator = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $note = MaintenanceNote::where('id', $id)
            ->where('category_status', 'purchase_req')
            ->first();

        if (! $note) {
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ], 404);
        }

        $business_id = $request->session()->get('user.business_id');
        
        // Build the full name from first_name and middle_name
        $nameArray = array_filter([
            $input['first_name'],
            $input['middle_name'],
        ]);
        $fullName = trim(implode(' ', $nameArray));

        $contact = new Contact();
        $contact->business_id = $business_id;
        $contact->type = 'supplier';
        $contact->first_name = $input['first_name'];
        $contact->middle_name = $input['middle_name'] ?? null;
        $contact->name = $fullName;
        $contact->mobile = $input['mobile'];
        $contact->created_by = $user->id;
        $contact->contact_status = 'active';
        $contact->save();

        $displayName = $contact->name;

        return response()->json([
            'success' => true,
            'message' => __('contact.added_success'),
            'supplier' => [
                'id' => $contact->id,
                'display_name' => $displayName,
            ],
        ]);
    }

    public function apiIndex(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        /** @var \App\User&\Illuminate\Contracts\Auth\Access\Authorizable|null $user */
        $user = Auth::user();
        if (! ($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $isAdmin = $user->hasRole('Admin#'.$business_id) || $user->can('superadmin');
        $permittedLocations = $user->permitted_locations($business_id);

        $selectedLocation = $request->input('location_id');
        $selectedStatus = $request->input('status');
        $jobSheetSearch = trim((string) $request->input('job_sheet'));

        if ($permittedLocations !== 'all' && ! $isAdmin) {
            $allowed = (array) $permittedLocations;
            if ($selectedLocation && ! in_array((int) $selectedLocation, $allowed, true)) {
                $selectedLocation = null;
            }
        }

        $notesQuery = $this->maintenanceNoteQuery(
            $selectedLocation ? (int) $selectedLocation : null,
            $selectedStatus,
            $jobSheetSearch,
            $permittedLocations,
            $isAdmin
        );

        $notes = $notesQuery
            ->latest()
            ->paginate(12)
            ->appends($request->only('location_id', 'status', 'job_sheet'));

        $formattedNotes = $notes->getCollection()->map(function (MaintenanceNote $note) {
            return $this->formatMaintenanceNoteForApi($note);
        });

        return response()->json([
            'success' => true,
            'notes' => $formattedNotes,
            'pagination' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
            'filters' => [
                'location_id' => $selectedLocation,
                'status' => $selectedStatus,
                'job_sheet' => $jobSheetSearch,
            ],
        ]);
    }

    public function data($id, Request $request)
    {
        $user = Auth::user();
        if (!($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            abort(403, 'Unauthorized action.');
        }

        $note = $this->maintenanceNoteQuery()
            ->where('maintenance_note.id', $id)
            ->first();

        if (! $note) {
            return response()->json(['success' => false, 'message' => 'Maintenance note not found'], 404);
        }

        $decoratedNote = $this->decorateMaintenanceNote($note);
        $lineItems = $this->fetchMaintenanceLineItems($note);

        // Ensure product compatibility records exist for products already present in PJO when modal opens
        try {
            if (!empty($lineItems)) {
                $productIds = collect($lineItems)->pluck('product_id')->filter()->unique()->values()->all();
                if (!empty($productIds)) {
                    $this->ensureCompatibilityForProducts($productIds, $note);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to ensure product compatibility on modal open', [
                'note_id' => $note->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'note' => $decoratedNote,
            'line_items' => $lineItems,
        ], 200);
    }

    public function searchProducts(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $user = Auth::user();
        if (!($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            abort(403, 'Unauthorized action.');
        }

        $query = $request->get('q', '');
        $location_id = auth()->user()->location_id  ;
        $enable_stock = $request->get('enable_stock', null);

        // Use user's location_id if not explicitly provided
        $userLocationId = $user->location_id;
        if (empty($location_id) && !empty($userLocationId)) {
            $location_id = $userLocationId;
        }

        $products = DB::table('products')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            // Ensure product is explicitly assigned to one or more business locations
            ->leftJoin('product_locations as pl', 'pl.product_id', '=', 'products.id')
            ->where('products.business_id', $business_id)
            ->where('products.virtual_product', 0)
            ->where('products.is_client_flagged', 0)
            ->where('products.is_inactive', 0)
            ->when($enable_stock !== null && $enable_stock !== '', function ($q) use ($enable_stock) {
                $q->where('products.enable_stock', (int) $enable_stock);
            })
            ->where(function ($q) use ($query) {
                $q->where('products.name', 'like', "%{$query}%")
                  ->orWhere('products.sku', 'like', "%{$query}%");
            })
            
         
            ->where('pl.location_id', (int) $location_id)
        
            // Exclude products that do not have any linked business location (product_locations)
            ->whereNotNull('pl.location_id')
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'brands.name as brand_name',
                'categories.name as category_name',
                DB::raw('COALESCE(MIN(variations.sell_price_inc_tax), 0) as price'),
                DB::raw('COALESCE(MAX(variations.sell_price_inc_tax), 0) as max_price'),
                DB::raw('(
                    SELECT COALESCE(SUM(vld.qty_available), 0)
                    FROM variations v
                    LEFT JOIN variation_location_details vld ON vld.variation_id = v.id
                    WHERE v.product_id = products.id
                    AND vld.location_id = ' . (int) $location_id . '
                ) as qty_available'),
                DB::raw('(
                    SELECT NULLIF(MAX(pl.purchase_price), 0)
                    FROM purchase_lines as pl
                    WHERE pl.product_id = products.id
                ) as purchase_price')
            ])
            ->groupBy('products.id', 'products.name', 'products.sku', 'brands.name', 'categories.name')
            ->limit(20)
            ->get();

        // Build compatibility summary per product (e.g. "BMW 3 Series 2015-2019; Audi A4 2012-2014")
        $compatibilityByProduct = [];
        if ($products->isNotEmpty()) {
            $productIds = $products->pluck('id')->all();

            $compatRows = DB::table('product_compatibility as pc')
                ->leftJoin('repair_device_models as dm', 'pc.model_id', '=', 'dm.id')
                ->leftJoin('categories as bc', 'pc.brand_category_id', '=', 'bc.id')
                ->whereIn('pc.product_id', $productIds)
                ->select(
                    'pc.product_id',
                    'pc.from_year',
                    'pc.to_year',
                    'dm.name as model_name',
                    'bc.name as brand_name'
                )
                ->orderBy('bc.name')
                ->orderBy('dm.name')
                ->orderBy('pc.from_year')
                ->limit(5)
                ->get();

            foreach ($compatRows as $row) {
                $brand = $row->brand_name ?? '';
                $model = $row->model_name ?? '';

                $yearRange = null;
                if (!empty($row->from_year) && !empty($row->to_year)) {
                    $yearRange = $row->from_year.'-'.$row->to_year;
                } elseif (!empty($row->from_year)) {
                    $yearRange = (string) $row->from_year;
                } elseif (!empty($row->to_year)) {
                    $yearRange = (string) $row->to_year;
                }

                $parts = array_filter([$brand, $model, $yearRange], function ($v) {
                    return $v !== null && $v !== '';
                });

                if (!empty($parts)) {
                    $label = implode(' ', $parts);
                    $compatibilityByProduct[$row->product_id][] = $label;
                }
            }

            // De-duplicate labels per product
            foreach ($compatibilityByProduct as $productId => $labels) {
                $compatibilityByProduct[$productId] = implode('; ', array_values(array_unique($labels)));
            }
        }

        return response()->json([
            'success' => true,
            'products' => $products->map(function ($product) use ($compatibilityByProduct) {
                return [
                    'id' => $product->id,
                    'text' => $product->name . ($product->sku ? ' (' . $product->sku . ')' : ''),
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'brand_name' => $product->brand_name,
                    'category_name' => $product->category_name,
                    'price' => $product->price,
                    'qty_available' => (float) ($product->qty_available ?? 0),
                    'purchase_price' => $product->purchase_price !== null ? (float) $product->purchase_price : null,
                    'compatibility' => $compatibilityByProduct[$product->id] ?? null,
                ];
            })
        ]);
    }

    public function addProduct(Request $request, $id)
    {
        $user = Auth::user();
        if (!($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            abort(403, 'Unauthorized action.');
        }

        $note = MaintenanceNote::with(['jobSheet', 'jobEstimator'])->find($id);

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Maintenance note not found'], 404);
        }

        $product_id = $request->input('product_id');
        $quantity = $request->input('quantity', 1);
        $price = $request->input('price', 0);
        $purchase_price = $request->input('purchase_price');
        if ($purchase_price === '') {
            $purchase_price = null;
        }
        $supplier_id = $request->input('supplier_id');
        $notes = $request->input('notes', '');
        $clientApproval = (int) $request->input('client_approval', 0);

        if (!$product_id) {
            return response()->json(['success' => false, 'message' => 'Product ID is required'], 400);
        }

        if ($supplier_id) {
            $supplier = DB::table('contacts')
                ->where('id', $supplier_id)
                ->select(['name', 'supplier_business_name'])
                ->first();

            if ($supplier) {
                $parts = array_filter([$supplier->supplier_business_name, $supplier->name]);
            
            }
        }

        // Check if product already exists in joborder for this note
        $existing = DB::table('product_joborder')
            ->when($note->job_sheet_id, function ($query) use ($note) {
                $query->where('job_order_id', $note->job_sheet_id);
            })
            ->when(!$note->job_sheet_id && $note->job_estimator_id, function ($query) use ($note) {
                $query->where('job_estimator_id', $note->job_estimator_id);
            })
            ->where('product_id', $product_id)
            ->first();

        if ($existing) {
            // Update existing record
            DB::table('product_joborder')
                ->where('id', $existing->id)
                ->update([
                    'quantity' => $existing->quantity + $quantity,
                    'price' => $price,
                    'purchase_price' => $purchase_price !== null ? $purchase_price : $existing->purchase_price,
                    'supplier_id' => $supplier_id,
                    'client_approval' => $clientApproval,
                    'Notes' => $notes,
                ]);
            $actionType = 'تم تحديث كمية المنتج';
        } else {
            // Create new record
            DB::table('product_joborder')->insert([
                'job_order_id' => $note->job_sheet_id,
                'job_estimator_id' => $note->job_estimator_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $price,
                'purchase_price' => $purchase_price,
                'supplier_id' => $supplier_id,
                'client_approval' => $clientApproval,
                'Notes' => $notes,
            ]);
            $actionType = 'تم إضافة المنتج';
        }

        // Auto-link product compatibility to the job's contact device (job sheet or estimator)
        try {
            $contactDevice = null;

            // Prefer job sheet -> booking -> device
            if ($note->jobSheet && $note->jobSheet->booking && $note->jobSheet->booking->device) {
                $contactDevice = $note->jobSheet->booking->device;
            } elseif ($note->jobEstimator && $note->jobEstimator->device) {
                // Fallback to estimator device
                $contactDevice = $note->jobEstimator->device;
            }

            if ($contactDevice) {
                $brandCategoryId = $contactDevice->device_id ?? null;
                $modelId = $contactDevice->models_id ?? null;
                $year = $contactDevice->manufacturing_year ?? null;
                $motorCc = $contactDevice->motor_cc ?? null;

                // Only create compatibility when we have at least one meaningful attribute
                if ($brandCategoryId || $modelId || $year || $motorCc) {
                    $exists = ProductCompatibility::query()
                        ->where('product_id', $product_id)
                        ->when($brandCategoryId, function ($q) use ($brandCategoryId) {
                            $q->where('brand_category_id', $brandCategoryId);
                        }, function ($q) {
                            $q->whereNull('brand_category_id');
                        })
                        ->when($modelId, function ($q) use ($modelId) {
                            $q->where('model_id', $modelId);
                        }, function ($q) {
                            $q->whereNull('model_id');
                        })
                        ->when($year, function ($q) use ($year) {
                            $q->where('from_year', $year)->where('to_year', $year);
                        }, function ($q) {
                            $q->whereNull('from_year')->whereNull('to_year');
                        })
                        ->when($motorCc, function ($q) use ($motorCc) {
                            $q->where('motor_cc', $motorCc);
                        })
                        ->exists();

                    if (! $exists) {
                        ProductCompatibility::create([
                            'product_id' => $product_id,
                            'brand_category_id' => $brandCategoryId,
                            'model_id' => $modelId,
                            'from_year' => $year,
                            'to_year' => $year,
                            'motor_cc' => $motorCc,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to auto-add product compatibility from maintenance note', [
                'note_id' => $note->id,
                'product_id' => $product_id,
                'error' => $e->getMessage(),
            ]);
        }

        // Update maintenance note status to approved since product has been added
        $note->update(['status' => 'approved']);

        $this->dispatchMaintenanceNoteNotification($note, $actionType, [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $price,
            'purchase_price' => $purchase_price,
            'client_approval' => $clientApproval,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully'
        ]);
    }

    public function updateLine(Request $request, $id, $lineId)
    {
        $user = Auth::user();
        if (!($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            abort(403, 'Unauthorized action.');
        }

        $note = MaintenanceNote::where('id', $id)->first();

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Maintenance note not found'], 404);
        }

        $line = DB::table('product_joborder')
            ->where('id', $lineId)
            ->when($note->job_sheet_id, function ($query) use ($note) {
                $query->where('job_order_id', $note->job_sheet_id);
            })
            ->when(!$note->job_sheet_id && $note->job_estimator_id, function ($query) use ($note) {
                $query->where('job_estimator_id', $note->job_estimator_id);
            })
            ->first();

        if (!$line) {
            return response()->json(['success' => false, 'message' => 'Line item not found'], 404);
        }

        $purchase_price = $request->input('purchase_price');
        if ($purchase_price === '') {
            $purchase_price = null;
        }

        $supplier_id = $request->input('supplier_id');
        $clientApproval = (int) $request->input('client_approval', $line->client_approval ?? 0);

        if ($supplier_id) {
            $supplier = DB::table('contacts')
                ->where('id', $supplier_id)
                ->select(['name', 'supplier_business_name'])
                ->first();

            if ($supplier) {
                $parts = array_filter([$supplier->supplier_business_name, $supplier->name]);
              
            }
        }

        $newQuantity = (float) $request->input('quantity', $line->quantity);
        $outForDeliverInput = $request->input('out_for_deliver', null);

        // Validate out_for_deliver when switching to true (1)
        // Skip validation if out_for_deliver is already true (1) - user is just keeping it true
        $isAlreadyOutForDeliver = (int)($line->out_for_deliver ?? 0) === 1;
        $isSwitchingToTrue = $outForDeliverInput !== null && (int)$outForDeliverInput === 1;
        
        if ($isSwitchingToTrue && !$isAlreadyOutForDeliver) {
            // Check product stock settings
            $productRow = DB::table('products')->where('id', $line->product_id)->select('enable_stock')->first();
            $enableStock = $productRow ? (int) $productRow->enable_stock : 0;

            if ($enableStock === 1) {
                // Determine location & transaction
                $locationId = null;
                $transactionId = null;
                if ($note->job_sheet_id) {
                    $locationId = DB::table('repair_job_sheets')->where('id', $note->job_sheet_id)->value('location_id');
                    $transactionId = DB::table('transactions')->where('repair_job_sheet_id', $note->job_sheet_id)->orderByDesc('id')->value('id');
                } else if ($note->job_estimator_id) {
                    $locationId = DB::table('job_estimator')->where('id', $note->job_estimator_id)->value('location_id');
                }

                // Sum qty available across all variations of product at location
                $variationIds = DB::table('variations')->where('product_id', $line->product_id)->pluck('id');
                $stockQty = 0.0;
                if (!empty($locationId) && $variationIds->count() > 0) {
                    $stockQty = (float) DB::table('variation_location_details')
                        ->whereIn('variation_id', $variationIds->all())
                        ->where('location_id', $locationId)
                        ->sum('qty_available');
                }

                // Quantity already present in transaction for this product
                $qtyInTransaction = 0.0;
                if (!empty($transactionId) && $variationIds->count() > 0) {
                    $qtyInTransaction = (float) DB::table('transaction_sell_lines')
                        ->where('transaction_id', $transactionId)
                        ->whereIn('variation_id', $variationIds->all())
                        ->sum('quantity');
                }

                $totalAvailable = $stockQty + $qtyInTransaction;
                if ($newQuantity > $totalAvailable) {
                    return response()->json([
                        'success' => false,
                        'message' => __('messages.insufficient_stock') ?: 'Insufficient stock to mark as out for delivery',
                        'meta' => [
                            'requested' => $newQuantity,
                            'available' => $totalAvailable,
                        ],
                    ], 422);
                }
            }
        }

        $data = [
            'quantity' => $newQuantity,
            'price' => $request->input('price', $line->price),
            'purchase_price' => $purchase_price !== null ? $purchase_price : $line->purchase_price,
            'supplier_id' => $supplier_id,
            'client_approval' => $clientApproval,
            'Notes' => $request->input('notes', $line->Notes),
        ];

        if ($outForDeliverInput !== null) {
            $data['out_for_deliver'] = (int) $outForDeliverInput;
        }

     
        DB::table('product_joborder')
            ->where('id', $lineId)
            ->update($data);

        $this->dispatchMaintenanceNoteNotification($note, 'تم تحديث بند المنتج', [
            'line_id' => $lineId,
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'purchase_price' => $data['purchase_price'],
            'client_approval' => $clientApproval,
            'out_for_deliver' => $data['out_for_deliver'] ?? $line->out_for_deliver ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Line item updated successfully',
        ]);
    }

    public function deleteLine(Request $request, $id, $lineId)
    {
        $user = Auth::user();
        if (!($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            abort(403, 'Unauthorized action.');
        }

        $note = MaintenanceNote::select('id', 'job_sheet_id', 'job_estimator_id')
            ->where('id', $id)
            ->first();

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Maintenance note not found'], 404);
        }

        $deleted = DB::table('product_joborder')
            ->where('id', $lineId)
            ->when($note->job_sheet_id, function ($query) use ($note) {
                $query->where('job_order_id', $note->job_sheet_id);
            })
            ->when(!$note->job_sheet_id && $note->job_estimator_id, function ($query) use ($note) {
                $query->where('job_estimator_id', $note->job_estimator_id);
            })
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Line item not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Line item deleted successfully',
        ]);
    }

    public function subCategories(Request $request, $categoryId)
    {
        /*
        $user = Auth::user();
        if (!($user && ($user->can('product.create') || $user->can('product.view')))) {
            abort(403, 'Unauthorized action.');
        }
        */

        $business_id = $request->session()->get('user.business_id');

        $subCategories = DB::table('categories')
            ->where('parent_id', $categoryId)
            /*
            ->where(function ($query) use ($business_id) {
                $query->where('business_id', $business_id)
                    ->orWhereNull('business_id');
            })
            */
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'sub_categories' => $subCategories,
        ]);
    }

    public function quickCreateProduct(Request $request)
    {
        /*
        $user = Auth::user();
        if (!($user && ($user->can('product.create') || $user->can('product.view')))) {
            abort(403, 'Unauthorized action.');
        }
        */
        $user = Auth::user();

        $business_id = $request->session()->get('user.business_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'product_locations' => ['required', 'array', 'min:1'],
            'product_locations.*' => ['integer', 'exists:business_locations,id'],
        ]);

        $unitId = $validated['unit_id'] ?? Unit::where('business_id', $business_id)->value('id');
        if (!$unitId) {
            $unitId = Unit::whereNull('business_id')->value('id');
        }

        if (!$unitId) {
            return response()->json([
                'success' => false,
                'message' => 'Unit configuration is required before creating products.',
            ], 422);
        }

        $providedSku = $validated['sku'] ?? null;
        $initialSku = $providedSku ?: 'tmp-' . $business_id . '-' . Str::uuid();
        $price = $validated['price'];

        DB::beginTransaction();

        try {
            $product = Product::create([
                'name' => $validated['name'],
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $unitId,
                'brand_id' => $validated['brand_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'tax' => null,
                'tax_type' => 'exclusive',
                'enable_stock' => 1,
                'alert_quantity' => 0,
                'sku' => $initialSku,
                'barcode_type' => 'C128',
                'created_by' => $user->id,
                'not_for_selling' => 0,
            ]);

            if (!$providedSku) {
                $generatedSku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $generatedSku;
                $product->save();
            }

            $product->refresh();

            // Attach selected business locations to the product
            $productLocations = $validated['product_locations'] ?? [];
            if (!empty($productLocations)) {
                $product->product_locations()->sync($productLocations);
            }

            $this->productUtil->createSingleProductVariation(
                $product->id,
                $product->sku,
                0,
                0,
                0,
                $price,
                $price
            );

            $product->setAttribute('default_sell_price', $price);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('product.product_added_success'),
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $price,
                    'display_name' => trim($product->name . ($product->sku ? ' (' . $product->sku . ')' : '')),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Batch save multiple products with sync functionality
     */
    public function batchSaveProducts(Request $request, $id)
    {
        $user = Auth::user();
        if (!($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            abort(403, 'Unauthorized action.');
        }

        $note = MaintenanceNote::where('id', $id)->first();

        if (!$note) {
            Log::warning('Maintenance note not found', ['note_id' => $id]);
            return response()->json(['success' => false, 'message' => 'Maintenance note not found'], 404);
        }

        $data = $request->input('data', []);

        if (!is_array($data) || empty($data)) {
            Log::warning('No products provided for batch save', ['note_id' => $id]);
            return response()->json(['success' => false, 'message' => 'No products provided'], 400);
        }

       
        DB::beginTransaction();

        try {
            // Process all products - save new and update existing
            $processedProducts = [];
            foreach ($data as $productData) {
                $product_id = $productData['product_id'] ?? null;
                $line_id = $productData['line_id'] ?? null;
                $quantity = $productData['quantity'] ?? 1;
                $price = $productData['price'] ?? 0;
                $purchase_price = $productData['purchase_price'] ?? null;
                $supplier_id = $productData['supplier_id'] ?? null;
                $notes = $productData['notes'] ?? '';
                $clientApproval = (int) ($productData['client_approval'] ?? 0);

                if (!$product_id) {
                    Log::debug('Skipping product without ID', ['product_data' => $productData]);
                    continue;
                }

                // If a specific line_id is provided, update that exact line first
                $updatedByLineId = false;
                if (!empty($line_id)) {
                    $line = DB::table('product_joborder')
                        ->when($note->job_sheet_id, function ($query) use ($note) {
                            $query->where('job_order_id', $note->job_sheet_id);
                        })
                        ->when(!$note->job_sheet_id && $note->job_estimator_id, function ($query) use ($note) {
                            $query->where('job_estimator_id', $note->job_estimator_id);
                        })
                        ->where('id', $line_id)
                        ->first();

                    if ($line) {
                       

                        DB::table('product_joborder')
                            ->where('id', $line_id)
                            ->update([
                                'product_id' => $product_id, // in case product changed in UI in future
                                'quantity' => $quantity,
                                'price' => $price,
                                'purchase_price' => $purchase_price !== null ? $purchase_price : $line->purchase_price,
                                'supplier_id' => $supplier_id,
                                'client_approval' => $clientApproval,
                                'Notes' => $notes,
                            ]);

                        $processedProducts[] = [
                            'product_id' => $product_id,
                            'quantity' => $quantity,
                            'price' => $price,
                        ];

                        $updatedByLineId = true;
                    }
                }

                if ($updatedByLineId) {
                    // Already handled this row using line_id
                    continue;
                }

                // Otherwise, check if a product line already exists for this note and update it
                $existing = DB::table('product_joborder')
                    ->when($note->job_sheet_id, function ($query) use ($note) {
                        $query->where('job_order_id', $note->job_sheet_id);
                    })
                    ->when(!$note->job_sheet_id && $note->job_estimator_id, function ($query) use ($note) {
                        $query->where('job_estimator_id', $note->job_estimator_id);
                    })
                    ->where('product_id', $product_id)
                    ->first();

                if ($existing) {
                    // Update existing
                    Log::info('Updating existing product', [
                        'product_id' => $product_id,
                        'old_qty' => $existing->quantity,
                        'new_qty' => $quantity,
                    ]);
                    DB::table('product_joborder')
                        ->where('id', $existing->id)
                        ->update([
                            'quantity' => $quantity,
                            'price' => $price,
                            'purchase_price' => $purchase_price !== null ? $purchase_price : $existing->purchase_price,
                            'supplier_id' => $supplier_id,
                            'client_approval' => $clientApproval,
                            'Notes' => $notes,
                        ]);
                } else {
                    // Create new
                    Log::info('Creating new product', [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'client_approval' => $clientApproval,
                    ]);
                    DB::table('product_joborder')->insert([
                        'job_order_id' => $note->job_sheet_id,
                        'job_estimator_id' => $note->job_estimator_id,
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'purchase_price' => $purchase_price,
                        'supplier_id' => $supplier_id,
                        'client_approval' => $clientApproval,
                        'Notes' => $notes,
                    ]);
                }

                $processedProducts[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
            }

            // Update maintenance note status to approved
            $note->update(['status' => 'approved']);
        

            // Use SparePartsController's checkStockAndCreatePurchaseRequisition method
            // This will handle both new and existing purchase requisitions
            if ($note->job_sheet_id && !empty($processedProducts)) {
            
                $this->sparePartsController->checkStockAndCreatePurchaseRequisition($data, $note->job_sheet_id);
              
            } else {
                if (!$note->job_sheet_id) {
                    Log::warning('No job_sheet_id found, skipping purchase requisition sync', ['note_id' => $id]);
                } else {
                    Log::info('No products to sync for purchase requisition', ['note_id' => $id]);
                }
            }

            DB::commit();

            // If this maintenance note is linked only to a job sheet (not an estimator),
            // mirror the spare-parts behavior so that client_approval drives transaction
            // sell lines and inventory adjustments in the same way as the
            // SparePartsController flow.
            if (!empty($note->job_sheet_id) && empty($note->job_estimator_id)) {
                try {
                    $jobSheet = JobSheet::find($note->job_sheet_id);

                    if ($jobSheet && !empty($jobSheet->contact_id)) {
                        // Rebuild the data payload expected by store_spareparts from the
                        // current product_joborder state for this job sheet.
                        $sparePartsData = DB::table('product_joborder')
                            ->where('job_order_id', $note->job_sheet_id)
                            ->select(
                                'product_id',
                                'quantity',
                                'price',
                                'delivered_status',
                                'out_for_deliver',
                                'client_approval',
                                'product_status'
                            )
                            ->get()
                            ->map(function ($row) {
                                return [
                                    'product_id' => (int) $row->product_id,
                                    'quantity' => (float) $row->quantity,
                                    'price' => (float) $row->price,
                                    'delivered_status' => (int) ($row->delivered_status ?? 0),
                                    'out_for_deliver' => (int) ($row->out_for_deliver ?? 0),
                                    'client_approval' => (int) ($row->client_approval ?? 0),
                                    'product_status' => $row->product_status ?? 'black',
                                ];
                            })
                            ->values()
                            ->all();

                        if (!empty($sparePartsData)) {
                            $sparePartsRequest = new Request([
                                'job_order_id' => (int) $note->job_sheet_id,
                                'contact_id' => (int) $jobSheet->contact_id,
                                'data' => $sparePartsData,
                            ]);

                            // Delegate to SparePartsController so that sell lines and
                            // variation_location_details are updated consistently with
                            // the spare-parts UI flow (client_approval, 1/1/1, etc.).
                            $this->sparePartsController->store_spareparts($sparePartsRequest);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to sync spare parts from maintenance note', [
                        'note_id' => $note->id,
                        'job_sheet_id' => $note->job_sheet_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (!empty($processedProducts)) {
                $notificationContext = [
                    'processed_count' => count($processedProducts),
                    'products' => $processedProducts,
                ];

                if (!empty($note->job_sheet_id)) {
                    $jobSheetNo = DB::table('repair_job_sheets')
                        ->where('id', $note->job_sheet_id)
                        ->value('job_sheet_no');
                    if (!empty($jobSheetNo)) {
                        $notificationContext['job_sheet_no'] = $jobSheetNo;
                    }
                } elseif (!empty($note->job_estimator_id)) {
                    $jobEstimatorNo = DB::table('job_estimator')
                        ->where('id', $note->job_estimator_id)
                        ->value('estimate_no');
                    if (!empty($jobEstimatorNo)) {
                        $notificationContext['job_estimator_no'] = $jobEstimatorNo;
                    }
                }

                $this->dispatchMaintenanceNoteNotification($note, 'تم حفظ جميع المنتجات', $notificationContext);
            }

            return response()->json([
                'success' => true,
                'message' => 'Products saved successfully',
                'processed_count' => count($processedProducts),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            

            return response()->json([
                'success' => false,
                'message' => 'Error saving products: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getPurchaseOrders(Request $request, $id)
    {
        $user = Auth::user();
        if (!($user && ($user->can('purchase.create') || $user->can('purchase.view')))) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $note = MaintenanceNote::where('id', $id)->first();
        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Maintenance note not found'], 404);
        }
        if (!$note->job_sheet_id) {
            return response()->json(['success' => false, 'message' => 'Job sheet not found'], 404);
        }
        $purchaseOrders = DB::table('transactions as t')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('purchase_lines as pl', 'pl.transaction_id', '=', 't.id')
            ->where('t.type', 'purchase')
            ->where('t.repair_job_sheet_id', $note->job_sheet_id)
            ->whereNotNull('t.contact_id')
            ->select([
                't.id',
                't.ref_no',
                't.transaction_date',
                't.final_total',
                't.status',
                DB::raw("CASE WHEN c.supplier_business_name IS NOT NULL AND c.supplier_business_name <> '' THEN CONCAT(c.supplier_business_name, ' - ', c.name) ELSE c.name END as supplier_name"),
                DB::raw('COALESCE(SUM(pl.quantity), 0) as total_qty'),
                DB::raw('COUNT(DISTINCT pl.id) as line_count')
            ])
            ->groupBy('t.id', 't.ref_no', 't.transaction_date', 't.final_total', 't.status', 'c.supplier_business_name', 'c.name')
            ->orderByDesc('t.id')
            ->get();

        $poWithLines = [];
        foreach ($purchaseOrders as $po) {
            $lines = DB::table('purchase_lines as pl')
                ->leftJoin('products as p', 'pl.product_id', '=', 'p.id')
                ->leftJoin('product_joborder as pjo', function($join) use ($note) {
                    $join->on('pjo.product_id', '=', 'pl.product_id')
                         ->where(function($q) use ($note) {
                             $q->where('pjo.job_order_id', $note->job_sheet_id)
                               ->orWhere('pjo.job_estimator_id', $note->job_estimator_id);
                         });
                })
                ->where('pl.transaction_id', $po->id)
                // Group by purchase line to avoid duplicates when multiple product_joborder rows exist
                ->groupBy('pl.id', 'p.name', 'p.sku', 'pl.quantity', 'pl.purchase_price_inc_tax')
                ->select([
                    'pl.id',
                    'p.name as product_name',
                    'p.sku',
                    'pl.quantity',
                    'pl.asked_qty',
                    DB::raw('pl.purchase_price_inc_tax as unit_price'),
                    DB::raw('(pl.quantity * pl.purchase_price_inc_tax) as line_total')
                ])
                ->get();

            $poWithLines[] = [
                'id' => $po->id,
                'ref_no' => $po->ref_no,
                'transaction_date' => $po->transaction_date,
                'final_total' => $po->final_total,
                'status' => $po->status,
                'supplier_name' => $po->supplier_name,
                'total_qty' => $po->total_qty,
                'line_count' => $po->line_count,
                'lines' => $lines,
            ];
        }

        return response()->json([
            'success' => true,
            'purchase_orders' => $poWithLines,
            'note_id' => $id,
            'job_sheet_id' => $note->job_sheet_id,
        ]);
    }

    public function updatePurchaseOrderStatus(Request $request, $id, $poId)
    {
        $user = Auth::user();
   

        $note = MaintenanceNote::where('id', $id)->first();

        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Maintenance note not found'], 404);
        }

        $status = $request->input('status');
        $validStatuses = ['pending', 'received', 'partial', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            return response()->json(['success' => false, 'message' => 'Invalid status'], 400);
        }

        // Get quantities for each line
        $quantities = $request->input('quantities', []);
        $askedQuantities = $request->input('asked_quantities', []);

        try {
            DB::beginTransaction();

            // Get the transaction (purchase order)
            $transaction = Transaction::find($poId);
            
            if (!$transaction || $transaction->type !== 'purchase') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
            }

            // Store the before status for stock adjustment
            $before_status = $transaction->status;

            // Update purchase order status
            $transaction->update(['status' => $status]);

            // Update purchase lines quantities if provided
            if (!empty($quantities) && is_array($quantities)) {
                $validationErrors = [];
                
                foreach ($quantities as $lineId => $newQty) {
                    $newQty = (float) $newQty;
                    $askedQty = isset($askedQuantities[$lineId]) ? (float) $askedQuantities[$lineId] : $newQty;
                    
                    // Validation: asked_qty must be at least 1
                    if ($askedQty < 1) {
                        $validationErrors[] = "Line ID {$lineId}: Asked quantity must be at least 1";
                        continue;
                    }
                    
                    // Validation: qty must be at least 1
                    if ($newQty < 1) {
                        $validationErrors[] = "Line ID {$lineId}: Quantity must be at least 1";
                        continue;
                    }
                    
                    // Validation: qty cannot exceed asked_qty
                    if ($newQty > $askedQty) {
                        $validationErrors[] = "Line ID {$lineId}: Quantity ({$newQty}) cannot exceed asked quantity ({$askedQty})";
                        continue;
                    }
                    
                    $updateData = [
                        'quantity' => $newQty,
                        'asked_qty' => $askedQty
                    ];
                    
                    DB::table('purchase_lines')
                        ->where('id', $lineId)
                        ->where('transaction_id', $transaction->id)
                        ->update($updateData);
                }
                
                // If there are validation errors, return them
                if (!empty($validationErrors)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed: ' . implode('; ', $validationErrors)
                    ], 400);
                }
                
                // Reload purchase lines relationship to get updated quantities
                $transaction->load('purchase_lines');
                
                // Recalculate final_total based on updated quantities
                $final_total = 0;
                foreach ($transaction->purchase_lines as $line) {
                    $final_total += ($line->purchase_price_inc_tax * $line->quantity);
                }
                $transaction->final_total = $final_total;
                $transaction->save();
            }

            // Update payment status based on the new status
            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            // Process purchase lines to update stock when status changes
            // This handles stock increases when status changes to 'received'
            // and stock decreases when status changes from 'received' to something else
            $purchases = $transaction->purchase_lines->map(function ($line) {
                return [
                    'purchase_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'quantity' => $line->quantity,
                    'pp_without_discount' => $line->pp_without_discount,
                    'discount_percent' => $line->discount_percent,
                    'purchase_price' => $line->purchase_price,
                    'purchase_price_inc_tax' => $line->purchase_price_inc_tax,
                    'item_tax' => $line->item_tax,
                    'purchase_line_tax_id' => $line->tax_id,
                    'lot_number' => $line->lot_number,
                    'mfg_date' => $line->mfg_date,
                    'exp_date' => $line->exp_date,
                    'sub_unit_id' => $line->sub_unit_id,
                ];
            })->toArray();

            $business_id = $transaction->business_id;
            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

            // This will handle stock updates based on status change
            $this->productUtil->createOrUpdatePurchaseLines($transaction, $purchases, $currency_details, false, $before_status);

            // Adjust stock over selling if found
            $this->productUtil->adjustStockOverSelling($transaction);

            // Log activity
            $this->transactionUtil->activityLog($transaction, 'updated');

            // Dispatch event
            PurchaseCreatedOrModified::dispatch($transaction);

            DB::commit();

            $this->syncRepairSparePartsForReceivedPurchase($transaction->fresh());

            return response()->json([
                'success' => true,
                'message' => 'Purchase order status updated successfully',
                'new_status' => $status
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating purchase order status: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update purchase order'], 500);
        }
    }

    protected function maintenanceNoteQuery(
        ?int $selectedLocation = null,
        ?string $selectedStatus = null,
        ?string $jobSheetSearch = null,
        $permittedLocations = null,
        bool $isAdmin = false
    ): Builder {
        $query = MaintenanceNote::query()
            ->select('maintenance_note.*')
            ->with([
                'creator:id,first_name,last_name,surname',
                'jobSheet' => function ($jobSheetQuery) {
                    $jobSheetQuery->select([
                        'repair_job_sheets.id',
                        'repair_job_sheets.job_sheet_no',
                        'repair_job_sheets.location_id',
                        'repair_job_sheets.booking_id',
                        'repair_job_sheets.device_model_id',
                        'repair_job_sheets.device_id',
                        'repair_job_sheets.brand_id',
                        'repair_job_sheets.created_by',
                    ])->with([
                        'booking' => function ($bookingQuery) {
                            $bookingQuery->select([
                                'bookings.id',
                                'bookings.contact_id',
                                'bookings.location_id',
                                'bookings.device_id',
                            ])->with([
                                'customer:id,name,first_name,middle_name,last_name',
                                'device' => function ($deviceQuery) {
                                    $deviceQuery->select([
                                        'contact_device.id',
                                        'contact_device.models_id',
                                        'contact_device.device_id',
                                        'contact_device.manufacturing_year',
                                        'contact_device.chassis_number',
                                        'contact_device.plate_number',
                                        'contact_device.color',
                                        'contact_device.car_type',
                                        'contact_device.motor_cc',
                                        'contact_device.contact_id',
                                    ])->with([
                                        'deviceModel:id,name',
                                        'deviceCategory:id,name',
                                        'contact:id,name,first_name,middle_name,last_name',
                                    ]);
                                },
                            ]);
                        },
                        'Device:id,name',
                        'Brand:id,name',
                        'deviceModel:id,name',
                        'businessLocation:id,name',
                        'invoices:id,repair_job_sheet_id,status',
                    ]);
                },
                'jobEstimator' => function ($estimatorQuery) {
                    $estimatorQuery->select([
                        'job_estimator.id',
                        'job_estimator.estimate_no',
                        'job_estimator.device_id',
                        'job_estimator.location_id',
                        'job_estimator.contact_id',
                        'job_estimator.created_by',
                        'job_estimator.estimator_status',
                    ])->with([
                        'device' => function ($deviceQuery) {
                            $deviceQuery->select([
                                'contact_device.id',
                                'contact_device.models_id',
                                'contact_device.device_id',
                                'contact_device.manufacturing_year',
                                'contact_device.chassis_number',
                                'contact_device.plate_number',
                                'contact_device.color',
                                'contact_device.car_type',
                                'contact_device.contact_id',
                            ])->with([
                                'deviceModel:id,name',
                                'deviceCategory:id,name',
                                'contact:id,name,first_name,middle_name,last_name',
                            ]);
                        },
                        'customer:id,name,first_name,middle_name,last_name',
                        'location:id,name',
                        'creator:id,first_name,last_name,surname',
                    ]);
                },
                'repairStatus:id,name',
            ])
            ->where('maintenance_note.category_status', 'purchase_req')
            ->where(function ($outer) {
                $outer->where(function ($jobSheetPath) {
                    $jobSheetPath
                        ->whereNotNull('maintenance_note.job_sheet_id')
                        ->whereHas('jobSheet', function ($jobSheetQuery) {
                            $jobSheetQuery->whereHas('invoices', function ($invoiceQuery) {
                                $invoiceQuery->where('status', 'under processing');
                            });
                        });
                })->orWhere(function ($estimatorPath) {
                    $estimatorPath
                        ->whereNotNull('maintenance_note.job_estimator_id')
                        ->whereHas('jobEstimator', function ($estimatorQuery) {
                            $estimatorQuery->whereIn('estimator_status', ['pending', 'replied']);
                        });
                });
            });

        if ($selectedStatus) {
            $query->where('maintenance_note.status', $selectedStatus);
        }

        if ($selectedLocation) {
            $query->where(function ($locationQuery) use ($selectedLocation) {
                $locationQuery
                    ->whereHas('jobSheet', function ($jobSheetQuery) use ($selectedLocation) {
                        $jobSheetQuery->where('location_id', $selectedLocation);
                    })
                    ->orWhereHas('jobEstimator', function ($estimatorQuery) use ($selectedLocation) {
                        $estimatorQuery->where('location_id', $selectedLocation);
                    });
            });
        }

        if ($jobSheetSearch !== null && $jobSheetSearch !== '') {
            $query->whereHas('jobSheet', function ($jobSheetQuery) use ($jobSheetSearch) {
                $jobSheetQuery->where('job_sheet_no', 'like', "%{$jobSheetSearch}%");
            });
        }

        if ($permittedLocations !== 'all' && ! $isAdmin) {
            $allowed = array_filter((array) $permittedLocations);
            if (! empty($allowed)) {
                $query->where(function ($locationQuery) use ($allowed) {
                    $locationQuery
                        ->whereHas('jobSheet', function ($jobSheetQuery) use ($allowed) {
                            $jobSheetQuery->whereIn('location_id', $allowed);
                        })
                        ->orWhereHas('jobEstimator', function ($estimatorQuery) use ($allowed) {
                            $estimatorQuery->whereIn('location_id', $allowed);
                        });
                });
            }
        }

        return $query;
    }

    protected function decorateMaintenanceNote(MaintenanceNote $note): MaintenanceNote
    {
        $statusMeta = $this->getStatusMeta();

        $creatorParts = array_filter([
            $note->creator->surname ?? null,
            $note->creator->first_name ?? null,
            $note->creator->last_name ?? null,
        ]);
        $note->engineer_name = ! empty($creatorParts)
            ? implode(' ', $creatorParts)
            : __('repair::lang.not_applicable');

        $origin = 'job_sheet';
        $locationId = null;
        $reference = '';
        $contactName = null;
        $vehicleBrand = null;
        $vehicleModel = null;
        $vehicleYear = null;
        $fallbackDeviceName = null;
        $fallbackDeviceModelName = null;
        $vin = null;
        $plate = null;
        $color = null;
        $carType = null;
        $motor_cc = null;

        if ($note->jobSheet) {
            $origin = 'job_sheet';
            $reference = $note->jobSheet->job_sheet_no ?? '';
            $locationId = $note->jobSheet->location_id;

            $fallbackDeviceName = optional($note->jobSheet->Device)->name;
            $fallbackDeviceModelName = optional($note->jobSheet->deviceModel)->name;

            $booking = $note->jobSheet->booking;
            if ($booking) {
                $device = $booking->device;
                if ($device) {
                    $vehicleBrand = optional($device->deviceCategory)->name ?? $vehicleBrand;
                    $vehicleModel = optional($device->deviceModel)->name ?? $vehicleModel;
                    if ($device->manufacturing_year) {
                        $vehicleYear = $vehicleYear ?? $device->manufacturing_year;
                    }
                    $vin = $vin ?: $device->chassis_number;
                    $plate = $plate ?: $device->plate_number;
                    $color = $color ?: $device->color;
                    $carType = $carType ?: $device->car_type;
                    $motor_cc = $motor_cc ?: $device->motor_cc;
                    if (! $contactName && $device->contact) {
                        $contactName = $device->contact->name
                            ?? $this->buildContactFullName($device->contact);
                    }
                }

                if (! $contactName && $booking->customer) {
                    $contactName = $booking->customer->name
                        ?? $this->buildContactFullName($booking->customer);
                }
            }
        } elseif ($note->jobEstimator) {
            $origin = 'job_estimator';
            $reference = $note->jobEstimator->estimate_no ?? '';
            $locationId = $note->jobEstimator->location_id;

            $device = $note->jobEstimator->device;
            if ($device) {
                $fallbackDeviceName = $fallbackDeviceName ?? optional($device->deviceCategory)->name;
                $fallbackDeviceModelName = $fallbackDeviceModelName ?? optional($device->deviceModel)->name;
                $vehicleBrand = $vehicleBrand ?? optional($device->deviceCategory)->name;
                $vehicleModel = $vehicleModel ?? optional($device->deviceModel)->name;
                if ($device->manufacturing_year) {
                    $vehicleYear = $vehicleYear ?? $device->manufacturing_year;
                }
                $vin = $vin ?: $device->chassis_number;
                $plate = $plate ?: $device->plate_number;
                $color = $color ?: $device->color;
                $carType = $carType ?: $device->car_type;
                $motor_cc = $motor_cc ?: $device->motor_cc;
                if (! $contactName && $device->contact) {
                    $contactName = $device->contact->name
                        ?? $this->buildContactFullName($device->contact);
                }
            }

            if (! $contactName && $note->jobEstimator->customer) {
                $contactName = $note->jobEstimator->customer->name
                    ?? $this->buildContactFullName($note->jobEstimator->customer);
            }
        }

        $vehicleBrand = $vehicleBrand ?? $fallbackDeviceName;
        $vehicleModel = $vehicleModel ?? $fallbackDeviceModelName;

        $vehicleParts = array_filter([
            $vehicleBrand,
            $vehicleModel ? '('.$vehicleModel.')' : null,
            $vehicleYear,
        ], fn ($value) => $value !== null && $value !== '');

        $note->vehicle_display = ! empty($vehicleParts)
            ? trim(implode(' ', $vehicleParts))
            : __('repair::lang.not_applicable');

        $note->vin_display = $vin ?: __('repair::lang.not_applicable');
        $note->plate_number = $plate ?: '—';
        $note->color = $color ?: '—';
        $note->car_type = $carType ?: '—';
        $note->motor_cc = $motor_cc ?: '—';
        $note->contact_name = $contactName ?: __('repair::lang.not_applicable');

        $note->origin = $origin;
        $note->origin_label = $origin === 'job_sheet'
            ? __('repair::lang.job_sheet')
            : __('restaurant.job_estimator');
        $note->display_reference = $reference;
        $note->location_id = $locationId;

        $noteStatusKey = $note->status ?? 'awaiting_reply';
        $note->status_config = $statusMeta[$noteStatusKey] ?? $this->defaultStatusMeta($noteStatusKey);

        return $note;
    }

    protected function formatMaintenanceNoteForApi(MaintenanceNote $note): array
    {
        $decorated = $this->decorateMaintenanceNote($note);

        return [
            'id' => $decorated->id,
            'job_sheet_no' => $decorated->jobSheet->job_sheet_no ?? null,
            'estimate_no' => $decorated->jobEstimator->estimate_no ?? null,
            'engineer_name' => $decorated->engineer_name,
            'vehicle_display' => $decorated->vehicle_display,
            'vin_display' => $decorated->vin_display,
            'plate_number' => $decorated->plate_number,
            'color' => $decorated->color,
            'car_type' => $decorated->car_type,
            'motor_cc' => $decorated->motor_cc,
            'status' => $decorated->status,
            'status_config' => $decorated->status_config,
            'content' => $decorated->content,
            'created_at' => $decorated->created_at,
            'updated_at' => $decorated->updated_at,
            'location_id' => $decorated->location_id,
            'origin' => $decorated->origin,
            'origin_label' => $decorated->origin_label,
            'display_reference' => $decorated->display_reference,
        ];
    }

    protected function fetchMaintenanceLineItems(MaintenanceNote $note)
    {
        return DB::table('product_joborder as pjo')
            ->leftJoin('products', 'products.id', '=', 'pjo.product_id')
            ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('contacts as suppliers', 'suppliers.id', '=', 'pjo.supplier_id')
            ->leftJoin('purchase_lines', function ($join) {
                $join->on('purchase_lines.product_id', '=', 'pjo.product_id');
            })
            ->when($note->job_sheet_id, function ($query) use ($note) {
                $query->where('pjo.job_order_id', $note->job_sheet_id);
            })
            ->when(! $note->job_sheet_id && $note->job_estimator_id, function ($query) use ($note) {
                $query->where('pjo.job_estimator_id', $note->job_estimator_id);
            })
            ->select([
                'pjo.id as line_id',
                'pjo.product_id',
                'pjo.quantity',
                'pjo.supplier_id',
                'pjo.client_approval',
                'pjo.delivered_status',
                'pjo.out_for_deliver',
                DB::raw('COALESCE(NULLIF(pjo.purchase_price, 0), NULLIF(MAX(purchase_lines.purchase_price), 0)) as purchase_price'),
                'products.name as part_name',
                'products.sku as part_sku',
                DB::raw('COALESCE(NULLIF(pjo.price, 0), NULLIF(MAX(variations.sell_price_inc_tax), 0), 0) as end_user_price'),
                'pjo.Notes as Notes',
                DB::raw("CASE WHEN suppliers.supplier_business_name IS NOT NULL AND suppliers.supplier_business_name <> '' THEN CONCAT(suppliers.supplier_business_name, ' - ', suppliers.name) ELSE suppliers.name END as supplier_display"),
            ])
            ->groupBy(
                'pjo.id',
                'pjo.product_id',
                'pjo.quantity',
                'pjo.supplier_id',
                'pjo.client_approval',
                'pjo.delivered_status',
                'pjo.out_for_deliver',
                'pjo.purchase_price',
                'pjo.price',
                'products.name',
                'products.sku',
                'pjo.Notes',
                'suppliers.supplier_business_name',
                'suppliers.name'
            )
            ->orderBy('products.name')
            ->get();
    }

    protected function ensureCompatibilityForProducts(array $productIds, MaintenanceNote $note): void
    {
        $contactDevice = null;

        if ($note->jobSheet && $note->jobSheet->booking && $note->jobSheet->booking->device) {
            $contactDevice = $note->jobSheet->booking->device;
        } elseif ($note->jobEstimator && $note->jobEstimator->device) {
            $contactDevice = $note->jobEstimator->device;
        }

        if (!$contactDevice) {
            return;
        }

        $brandCategoryId = $contactDevice->device_id ?? null;
        $modelId = $contactDevice->models_id ?? null;
        $year = $contactDevice->manufacturing_year ?? null;
        $motorCc = $contactDevice->motor_cc ?? null;

        // Require brand, model & year to be present; otherwise skip to avoid saving incomplete/wrong data
        if (!($brandCategoryId && $modelId && $year)) {
            return;
        }

        // Load existing compatibility rows for these products with same car attributes
        $existingRows = ProductCompatibility::query()
            ->whereIn('product_id', $productIds)
            ->when($brandCategoryId, function ($q) use ($brandCategoryId) {
                $q->where('brand_category_id', $brandCategoryId);
            }, function ($q) {
                $q->whereNull('brand_category_id');
            })
            ->when($modelId, function ($q) use ($modelId) {
                $q->where('model_id', $modelId);
            }, function ($q) {
                $q->whereNull('model_id');
            })
            ->when($motorCc, function ($q) use ($motorCc) {
                $q->where('motor_cc', $motorCc);
            }, function ($q) {
                $q->whereNull('motor_cc');
            })
            ->get();

        if (empty($productIds)) {
            return;
        }

        $existingByProduct = $existingRows->groupBy('product_id');
        $toInsert = [];
        $now = now();

        foreach ($productIds as $pid) {
            $rows = $existingByProduct->get($pid, collect());

            // No existing compatibility at all for this product + car combo: create a new one
            if ($rows->isEmpty()) {
                $toInsert[] = [
                    'product_id' => $pid,
                    'brand_category_id' => $brandCategoryId,
                    'model_id' => $modelId,
                    'from_year' => $year,
                    'to_year' => $year,
                    'motor_cc' => $motorCc,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                continue;
            }

            // If we don't know the year we cannot learn/extend a range, so stop here
            if ($year === null) {
                continue;
            }

            // 1) Check if any existing range already covers this year (or is year-agnostic)
            /** @var \App\ProductCompatibility|null $covering */
            $covering = $rows->first(function ($row) use ($year) {
                // If from/to are null, treat as covering everything and upgrade it when we learn a year
                if ($row->from_year === null && $row->to_year === null) {
                    return true;
                }

                return $row->from_year !== null
                    && $row->to_year !== null
                    && $row->from_year <= $year
                    && $row->to_year >= $year;
            });

            if ($covering) {
                // If it was previously open-ended (no years) but we now know a year, set the initial range
                if ($covering->from_year === null && $covering->to_year === null) {
                    $covering->from_year = $year;
                    $covering->to_year = $year;
                    $covering->save();
                }

                continue;
            }

            // 2) No covering range exists; decide whether to extend the closest overlapping/adjacent
            $ranged = $rows->filter(function ($row) {
                return $row->from_year !== null && $row->to_year !== null;
            });

            if ($ranged->isEmpty()) {
                // All existing rows are year-agnostic but none selected above (should be rare)
                $toInsert[] = [
                    'product_id' => $pid,
                    'brand_category_id' => $brandCategoryId,
                    'model_id' => $modelId,
                    'from_year' => $year,
                    'to_year' => $year,
                    'motor_cc' => $motorCc,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                continue;
            }

            // Pick the nearest existing range to this year
            /** @var \App\ProductCompatibility $target */
            $target = $ranged->sortBy(function ($row) use ($year) {
                if ($year < $row->from_year) {
                    return $row->from_year - $year;
                }

                if ($year > $row->to_year) {
                    return $year - $row->to_year;
                }

                return 0;
            })->first();

            $from = (int) $target->from_year;
            $to = (int) $target->to_year;

            $isAdjacentOrInside = ($year >= $from && $year <= $to)
                || $year === $from - 1
                || $year === $to + 1;

            if ($isAdjacentOrInside) {
                // Extend the existing range to include this year (learn wider compatibility)
                $target->from_year = min($from, $year);
                $target->to_year = max($to, $year);
                $target->save();
            } else {
                // Year is far from any known ranges: create a separate compatibility range
                $toInsert[] = [
                    'product_id' => $pid,
                    'brand_category_id' => $brandCategoryId,
                    'model_id' => $modelId,
                    'from_year' => $year,
                    'to_year' => $year,
                    'motor_cc' => $motorCc,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($toInsert)) {
            DB::table('product_compatibility')->insert($toInsert);
        }
    }

    protected function getStatusMeta(): array
    {
        return [
            'awaiting_reply' => [
                'label' => __('repair::lang.status_awaiting_reply'),
                'color' => '#f59e0b',
                'badge_bg' => '#fef3c7',
                'badge_text' => '#92400e',
            ],
            'approved' => [
                'label' => __('repair::lang.status_approved'),
                'color' => '#10b981',
                'badge_bg' => '#d1fae5',
                'badge_text' => '#065f46',
            ],
        ];
    }

    protected function defaultStatusMeta(string $statusKey): array
    {
        $label = Str::headline($statusKey);

        return [
            'label' => $label,
            'color' => '#9ca3af',
            'badge_bg' => '#f3f4f6',
            'badge_text' => '#374151',
        ];
    }

    protected function buildContactFullName($contact): ?string
    {
        if (! $contact) {
            return null;
        }

        $parts = array_filter([
            $contact->first_name ?? null,
            $contact->middle_name ?? null,
            $contact->last_name ?? null,
        ]);

        if (! empty($parts)) {
            return implode(' ', $parts);
        }

        return $contact->name ?? null;
    }

    protected function dispatchMaintenanceNoteNotification(MaintenanceNote $note, string $action, array $context = []): void
    {
        /** @var JobSheet|null $jobSheet */
        $jobSheet = JobSheet::where('id', $note->job_sheet_id)
            ->select(['id', 'job_sheet_no', 'service_staff', 'contact_id'])
            ->first();

        $jobEstimatorNo = null;
        if (empty($note->job_sheet_id) && !empty($note->job_estimator_id)) {
            $jobEstimatorNo = DB::table('job_estimator')
                ->where('id', $note->job_estimator_id)
                ->value('estimate_no');
        }

        $payload = array_merge([
            'note_id' => $note->id,
            'job_sheet_id' => $note->job_sheet_id,
            'job_sheet_no' => $jobSheet?->job_sheet_no,
            'job_estimator_no' => $jobEstimatorNo,
            'action' => $action,
        ], $context);

        $users = User::where('allow_login', 1)
            ->select([
                'id',
                'email',
                DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS name")
            ])
            ->get();


        foreach ($users as $user) {
            Notification::send($user, new MaintenanceNoteUpdated($payload));
        }

    }

    private function getLinkedSellTransactionForPurchase(Transaction $purchase): ?Transaction
    {
        if (! empty($purchase->repair_job_sheet_id)) {
            $sell = Transaction::where('type', 'sell')
                ->where('repair_job_sheet_id', $purchase->repair_job_sheet_id)
                ->orderByDesc('id')
                ->first();

            if (! empty($sell)) {
                return $sell;
            }
        }

        if (! empty($purchase->invoice_ref) && is_numeric($purchase->invoice_ref)) {
            return Transaction::where('id', (int) $purchase->invoice_ref)
                ->where('type', 'sell')
                ->first();
        }

        return null;
    }

    private function syncRepairSparePartsForReceivedPurchase(Transaction $purchase): void
    {
        try {
            if ($purchase->type !== 'purchase' || $purchase->status !== 'received') {
                return;
            }

            $sell_transaction = $this->getLinkedSellTransactionForPurchase($purchase);
            if (empty($sell_transaction) || $sell_transaction->status === 'final') {
                return;
            }

            $job_order_id = (int) ($sell_transaction->repair_job_sheet_id ?? $purchase->repair_job_sheet_id ?? 0);
            $contact_id = (int) ($sell_transaction->contact_id ?? 0);

            if ($job_order_id <= 0 || $contact_id <= 0) {
                return;
            }

            $job_products = DB::table('product_joborder')
                ->where('job_order_id', $job_order_id)
                ->select([
                    'product_id',
                    'quantity',
                    'price',
                    'delivered_status',
                    'out_for_deliver',
                    'client_approval',
                    'product_status',
                ])
                ->get();

            if ($job_products->isEmpty()) {
                return;
            }

            $payload_data = $job_products->map(function ($product) {
                return [
                    'product_id' => (int) $product->product_id,
                    'quantity' => (float) $product->quantity,
                    'price' => (float) ($product->price ?? 0),
                    'delivered_status' => (int) ($product->delivered_status ?? 0),
                    'out_for_deliver' => (int) ($product->out_for_deliver ?? 0),
                    'client_approval' => (int) ($product->client_approval ?? 0),
                    'product_status' => $product->product_status,
                ];
            })->values()->toArray();

            $sync_request = new Request([
                'job_order_id' => $job_order_id,
                'contact_id' => $contact_id,
                'data' => $payload_data,
            ]);

            app(\Modules\Connector\Http\Controllers\Api\SparePartsController::class)
                ->store_spareparts($sync_request);
        } catch (\Exception $e) {
            \Log::error('Purchase spare-parts sync failed', [
                'purchase_id' => $purchase->id ?? null,
                'repair_job_sheet_id' => $purchase->repair_job_sheet_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
