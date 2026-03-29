<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Modules\CheckCar\Entities\CarInspection;
use Modules\CheckCar\Entities\CheckCarElement;
use Modules\CheckCar\Entities\CheckCarElementOption;
use Modules\CheckCar\Entities\CheckCarInspectionItem;
use Modules\CheckCar\Entities\CheckCarPhraseTemplate;
use Modules\CheckCar\Entities\CheckCarQuestionCategory;
use Modules\CheckCar\Entities\CheckCarQuestionSubcategory;
use Modules\CheckCar\Entities\CheckCarInspectionDocument;
use Modules\Sms\Entities\SmsMessage;
use Modules\Sms\Entities\SmsLog;
use Illuminate\Support\Facades\Storage;
use App\Utils\SmsUtil;
use App\Utils\UrlShortener;

/**
 * @group CheckCar - Car Inspection
 * @authenticated
 *
 * APIs for car inspection management
 */
class CheckCarController extends ApiController
{
    /**
     * Determine effective location for current user.
     * Admin/superadmin users are not restricted by location (returns null).
     */
    private function getEffectiveLocationId($user)
    {
        if (!$user) {
            return null;
        }

        $is_admin = $user->hasAnyPermission('Admin#' . $user->business_id);
        if ($user->can('superadmin') || $is_admin) {
            return null;
        }

        return $user->location_id;
    }

    /**
     * Get inspection structure
     *
     * Returns the full structure of categories, subcategories, elements with their options and presets.
     *
     * @response {
     *   "success": true,
     *   "data": {
     *     "categories": [
     *       {
     *         "id": 1,
     *         "name": "Engine",
     *         "subcategories": [
     *           {
     *             "id": 1,
     *             "name": "Oil System",
     *             "elements": [
     *               {
     *                 "id": 1,
     *                 "name": "Oil Level",
     *                 "type": "dropdown",
     *                 "required": true,
     *                 "options": [
     *                   {"id": 1, "label": "Good", "value": "good"},
     *                   {"id": 2, "label": "Low", "value": "low"}
     *                 ],
     *                 "presets": [
     *                   {"id": 1, "label": "Normal", "phrase": "Oil level is within normal range"}
     *                 ]
     *               }

  
     *             ]
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     */
    public function getStructure()
    {
        $user = Auth::user();
        $locationId = $this->getEffectiveLocationId($user);

        if (!Schema::hasTable('checkcar_question_categories')) {
            return response()->json([
                'success' => false,
                'message' => 'CheckCar module tables not found'
            ], 404);
        }

        // Get all categories with subcategories for this location (global + matching location)
        $categories = CheckCarQuestionCategory::active()
            ->forLocation($locationId)
            ->ordered()
            ->get()
            ->map(function ($category) use ($locationId) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'subcategories' => $this->getSubcategoriesWithElements($category->id, $locationId)
                ];
            });

        // Also get elements without subcategory (directly under category)
        $structure = $categories->map(function ($category) use ($locationId) {
            // Get elements directly under category (no subcategory)
            $directElements = $this->getElementsForCategory($category['id'], null, $locationId);
            
            return [
                'id' => $category['id'],
                'name' => $category['name'],
                'elements' => $directElements,
                'subcategories' => $category['subcategories']
            ];
        });


        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $structure
            ]
        ]);
    }

    private function normalizeOptionIds($optionIds): array
    {
        if (!is_array($optionIds)) {
            return [];
        }

        return collect($optionIds)
            ->map(function ($id) {
                if (is_numeric($id)) {
                    return (int) $id;
                }
                return null;
            })
            ->filter(fn ($id) => !is_null($id))
            ->unique()
            ->values()
            ->toArray();
    }

    private function buildSelectedOptions(array $optionIds): array
    {
        if (empty($optionIds)) {
            return [];
        }

        return CheckCarElementOption::whereIn('id', $optionIds)
            ->get()
            ->map(function ($option) {
                return [
                    'id' => $option->id,
                    'label' => $option->label,
                    'value' => $option->value ?? $option->label,
                ];
            })
            ->values()
            ->toArray();
    }

    private function formatItemImages($images): array
    {
        return collect($images ?? [])->map(function ($image) {
            $filePath = data_get($image, 'file_path');
            $mimeType = data_get($image, 'mime_type', 'image/png');
            return [
                'type' => data_get($image, 'type'),
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'media_type' => $this->getMediaTypeFromMime($mimeType),
                'url' => $filePath ? asset('storage/' . ltrim($filePath, '/')) : null,
            ];
        })->filter(function ($image) {
            return !empty($image['file_path']) || !empty($image['url']);
        })->values()->toArray();
    }

    private function buildSimpleItemsArray($items)
    {
        return $items->map(function ($item) {
            return [
                'element_id' => $item->element_id,
                'title' => $item->title,
                'option_ids' => $item->option_ids ?? array_column($item->selected_options ?? [], 'id'),
                'note' => $item->note,
                'images' => $this->formatItemImages($item->images ?? []),
            ];
        })->values();
    }
    
    /**
     * Get subcategories with elements for a category
     */
    private function getSubcategoriesWithElements($categoryId, $locationId)
    {
        return CheckCarQuestionSubcategory::where('category_id', $categoryId)
            ->active()
            ->ordered()
            ->get()
            ->map(function ($subcategory) use ($categoryId, $locationId) {
                return [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'elements' => $this->getElementsForCategory($categoryId, $subcategory->id, $locationId)
                ];
            });
    }

    /**
     * Get elements for category/subcategory with options and presets
     */
    private function getElementsForCategory($categoryId, $subcategoryId, $locationId)
    {
        $query = CheckCarElement::where('category_id', $categoryId)
            ->active();

        if (!empty($locationId)) {
            $query->forLocation($locationId);
        }

        $query->ordered();

        if ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        } else {
            $query->whereNull('subcategory_id');
        }

        return $query->get()->map(function ($element) use ($locationId) {
            // Get options (label + sort order only; type is now on element)
            $optionsQuery = CheckCarElementOption::where('element_id', $element->id);
            if (!empty($locationId)) {
                $optionsQuery->forLocation($locationId);
            }
            $options = $optionsQuery->ordered()
                ->get()
                ->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'label' => $option->label,
                        'sort_order' => $option->sort_order,
                    ];
                });

            // Get presets (phrase templates)
            $presetsQuery = CheckCarPhraseTemplate::where('element_id', $element->id);
            if (!empty($locationId)) {
                $presetsQuery->forLocation($locationId);
            }
            $presets = $presetsQuery->get()
                ->map(function ($preset) {
                    return [
                        'id' => $preset->id,
                        'phrase' => $preset->phrase,
                    ];
                });

            return [
                'id' => $element->id,
                'name' => $element->name,
                'type' => $element->type,
                'required' => (bool) $element->required,
                'max_options' => $element->max_options ?? 0,
                'options' => $options,
                'presets' => $presets,
            ];
        });
    }

    /**
     * List inspections
     *
     * @queryParam status Filter by status (draft, in_progress, completed, cancelled)
     * @queryParam per_page Number of results per page (default: 20)
     *
     * @response {
     *   "success": true,
     *   "data": [...],
     *   "meta": {"current_page": 1, "total": 10}
     * }
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $locationId = $this->getEffectiveLocationId($user);
        $perPage = $request->get('per_page', 20);

        $query = CarInspection::with('creator');

        if (!empty($locationId)) {
            $query->where('location_id', $locationId);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $inspections = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $inspections->items(),
            'meta' => [
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
                'per_page' => $inspections->perPage(),
                'total' => $inspections->total()
            ]
        ]);
    }

    /**
     * Get single inspection
     *
     * @urlParam id required The inspection ID
     *
     * @response {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "car_brand": "Toyota",
     *     "items": [...]
     *   }
     * }
     */
    public function show($id)
    {
        $user = Auth::user();
        $locationId = $this->getEffectiveLocationId($user);

        $inspectionQuery = CarInspection::with(['items.element', 'items.category', 'items.subcategory', 'creator', 'documents']);
        if (!empty($locationId)) {
            $inspectionQuery->where('location_id', $locationId);
        }

        $inspection = $inspectionQuery->findOrFail($id);

        // Group items by category
        $itemsByCategory = $inspection->items->groupBy(function ($item) {
            return $item->category ? $item->category->name : 'Uncategorized';
        })->map(function ($items, $categoryName) {
            return [
                'category' => $categoryName,
                'items' => $items->groupBy(function ($item) {
                    return $item->subcategory ? $item->subcategory->name : null;
                })->map(function ($subItems, $subcategoryName) {
                    return [
                        'subcategory' => $subcategoryName ?: null,
                        'elements' => $subItems->map(function ($item) {
                            return [
                                'element_id' => $item->element_id,
                                'title' => $item->title,
                                'element_name' => $item->element ? $item->element->name : 'Unknown',
                                'element_type' => $item->element ? $item->element->type : 'unknown',
                                'option_ids' => $item->option_ids ?? [],
                                'note' => $item->note,
                                'images' => $this->formatItemImages($item->images ?? []),
                            ];
                        })
                    ];
                })->values()
            ];
        })->values();

        $documents = $inspection->documents
            ->groupBy('party')
            ->map(function ($docs) {
                return $docs->map(function ($doc) {
                    return [
                        'type' => $doc->document_type,
                        'url' => Storage::disk('public')->url($doc->file_path),
                    ];
                })->values();
            });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $inspection->id,
                'buyer_full_name' => $inspection->buyer_full_name,
                'buyer_phone' => $inspection->buyer_phone,
                'buyer_id_number' => $inspection->buyer_id_number,
                'seller_full_name' => $inspection->seller_full_name,
                'seller_phone' => $inspection->seller_phone,
                'seller_id_number' => $inspection->seller_id_number,
                'car_brand' => $inspection->car_brand,
                'car_model' => $inspection->car_model,
                'car_color' => $inspection->car_color,
                'car_year' => $inspection->car_year,
                'car_chassis_number' => $inspection->car_chassis_number,
                'car_plate_number' => $inspection->car_plate_number,
                'car_kilometers' => $inspection->car_kilometers,
             
                'inspection_team' => $inspection->inspection_team,
                'final_summary' => $inspection->final_summary,
                'overall_rating' => $inspection->overall_rating,
                'status' => $inspection->status,
                'share_token' => $inspection->share_token,
                'created_at' => $inspection->created_at,
                'updated_at' => $inspection->updated_at,
                'creator' => $inspection->creator ? [
                    'id' => $inspection->creator->id,
                    'name' => $inspection->creator->first_name . ' ' . $inspection->creator->last_name
                ] : null,
                'categories' => $itemsByCategory,
                'documents' => $documents
            ]
        ]);
    }

    /**
     * Get inspection by job sheet ID
     *
     * @urlParam job_sheet_id required The job sheet ID
     *
     * @response {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "car_brand": "Toyota",
     *     "items": [...]
     *   }
     * }
     */
    public function getByJobSheet($job_sheet_id)
    {


        $user = Auth::user();
        $locationId = $this->getEffectiveLocationId($user);

        $inspectionQuery = CarInspection::with([
                'items.element',
                'items.category',
                'items.subcategory',
                'creator',
                'documents',
                'buyerContact',
                'sellerContact',
                'contactDevice',
                'jobSheet',
            ]);

        if (!empty($locationId)) {
            $inspectionQuery->where('location_id', $locationId);
        }

        $inspection = $inspectionQuery
            ->where('job_sheet_id', $job_sheet_id)
            ->orderByDesc('created_at')
            ->first();

        if (!$inspection) {
    
            return response()->json([
                'success' => false,
                'message' => 'No inspection found for this job sheet'
            ], 404);
        }

 

        // Ensure a share token exists so that a stable share URL can be returned
        if (empty($inspection->share_token)) {
            $inspection->generateShareToken();
            $inspection->refresh();
        }

        // Sort items by category, subcategory and element sort_order, then group by category/subcategory
        $sortedItems = $inspection->items
            ->sortBy(function ($item) {
                $categoryOrder = $item->category ? (int) $item->category->sort_order : 9999;
                $subcategoryOrder = $item->subcategory ? (int) $item->subcategory->sort_order : 9999;
                $elementOrder = $item->element ? (int) $item->element->sort_order : 9999;

                return sprintf('%05d-%05d-%05d-%05d', $categoryOrder, $subcategoryOrder, $elementOrder, $item->id);
            });

        $itemsByCategory = $sortedItems
            ->groupBy(function ($item) {
                return $item->category ? $item->category->name : 'Uncategorized';
            })
            ->map(function ($items, $categoryName) {
                return [
                    'category' => $categoryName,
                    'items' => $items
                        ->groupBy(function ($item) {
                            return $item->subcategory ? $item->subcategory->name : null;
                        })
                        ->map(function ($subItems, $subcategoryName) {
                            return [
                                'subcategory' => $subcategoryName ?: null,
                                'elements' => $subItems->map(function ($item) {
                                    return [
                                        'element_id' => $item->element_id,
                                        'title' => $item->title,
                                        'element_name' => $item->element ? $item->element->name : 'Unknown',
                                        'element_type' => $item->element ? $item->element->type : 'unknown',
                                        'option_ids' => $item->option_ids ?? [],
                                        'note' => $item->note,
                                        'images' => $this->formatItemImages($item->images ?? []),
                                    ];
                                })
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        $documents = $inspection->documents
            ->groupBy('party')
            ->map(function ($docs) {
                return $docs->map(function ($doc) {
                    $filePath = $doc->file_path;
                    $mimeType = $doc->mime_type ?? 'image/png';
                    return [
                        'id' => $doc->id,
                        'type' => $doc->document_type,
                        'file_path' => $filePath,
                        'mime_type' => $mimeType,
                        'media_type' => $this->getMediaTypeFromMime($mimeType),
                        'url' => $filePath ? asset('storage/' . ltrim($filePath, '/')) : null,
                    ];
                })->values();
            });

        // Format items array with element_id, option_ids, note, images
        $itemsArray = $this->buildSimpleItemsArray($inspection->items);

     
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $inspection->id,
                'booking_id' => $inspection->booking_id,
                'job_sheet_id' => $inspection->job_sheet_id,
                'job_sheet_km' => $inspection->jobSheet ? $inspection->jobSheet->km : null,
                'buyer_contact_id' => $inspection->buyer_contact_id,
                'seller_contact_id' => $inspection->seller_contact_id,
                'contact_device_id' => $inspection->contact_device_id,
                'verification_required' => $inspection->verification_required ?? true,
                'buyer' => $inspection->buyerContact ? [
                    'id' => $inspection->buyerContact->id,
                    'first_name' => $inspection->buyerContact->first_name,
                    'middle_name' => $inspection->buyerContact->middle_name,
                    'last_name' => $inspection->buyerContact->last_name,
                    'name' => $inspection->buyerContact->name,
                    'mobile' => $inspection->buyerContact->mobile,
              
                ] : null,
                'seller' => $inspection->sellerContact ? [
                    'id' => $inspection->sellerContact->id,
                    'first_name' => $inspection->sellerContact->first_name,
                    'middle_name' => $inspection->sellerContact->middle_name,
                    'last_name' => $inspection->sellerContact->last_name,
                    'name' => $inspection->sellerContact->name,
                    'mobile' => $inspection->sellerContact->mobile,
               
                ] : null,
                'contact_device' => $inspection->contactDevice ? [
                    'id' => $inspection->contactDevice->id,
                    'name' => $inspection->contactDevice->device_name ?? $inspection->contactDevice->name ?? null,
                    'model' => $inspection->contactDevice->device_model ?? $inspection->contactDevice->model ?? null,
                    'plate_number' => $inspection->contactDevice->plate_number ?? null,
                    'chassis_number' => $inspection->contactDevice->chassis_number ?? null,
                    'color' => $inspection->contactDevice->color ?? null,
                    'manufacturing_year' => $inspection->contactDevice->manufacturing_year ?? null,
                    'car_type' => $inspection->contactDevice->car_type ?? null,
                ] : null,
                'inspection_team' => $inspection->inspection_team,
                'sections' => $inspection->sections,
                'items' => $itemsArray,
                'final_summary' => $inspection->final_summary,
                'overall_rating' => $inspection->overall_rating,
                'status' => $inspection->status,
                'share_token' => $inspection->share_token,
                'share_url' => $inspection->share_token ? $inspection->getShareUrl() : null,
                'created_at' => $inspection->created_at,
                'updated_at' => $inspection->updated_at,
                'created_by' => $inspection->created_by,
                'creator' => $inspection->creator ? [
                    'id' => $inspection->creator->id,
                    'name' => $inspection->creator->first_name . ' ' . $inspection->creator->last_name
                ] : null,
                'policy_approved' => $inspection->policy_approved,
                'categories' => $itemsByCategory,
                'documents' => $documents
            ]
        ]);
    }



    public function store(Request $request)
    {
        $user = Auth::user();

        // Support both flat body and { "data": { ... } } wrapper
        $input = $request->input('data');
        if (!is_array($input)) {
            $input = $request->all();
        }

        $validator = Validator::make($input, [
            'booking_id' => 'nullable|integer',
            'job_sheet_id' => 'nullable|integer',
            'buyer_contact_id' => 'required|integer|exists:contacts,id',
            'seller_contact_id' => 'required|integer|exists:contacts,id',
            'contact_device_id' => 'required|integer|exists:contact_device,id',
            'car_brand' => 'nullable|string|max:255',
            'car_model' => 'nullable|string|max:255',
            'car_year' => 'nullable|string|max:10',
            'car_color' => 'nullable|string|max:255',
            'car_chassis_number' => 'nullable|string|max:255',
            'car_plate_number' => 'nullable|string|max:255',
            'car_kilometers' => 'nullable|integer|min:0',
            'inspection_team' => 'nullable|array',
            'items' => 'nullable|array',
            'items.*.element_id' => 'required|integer|exists:checkcar_elements,id',
            'items.*.title' => 'nullable|string|max:255',
            'items.*.option_ids' => 'nullable|array',
            'items.*.option_ids.*' => 'integer|exists:checkcar_element_options,id',
            'items.*.note' => 'nullable|string',
            'items.*.images' => 'nullable|array',
            'items.*.images.*' => 'nullable|string',
            'items.*.images' => 'nullable|array',
            'items.*.images.*' => 'nullable|string',
            'final_summary' => 'nullable|string',
            'overall_rating' => 'nullable|integer|min:1|max:5',
            'documents' => 'nullable|array',
            'documents.buyer' => 'nullable|array',
            'documents.buyer.id_front' => 'nullable|string',
            'documents.buyer.id_back' => 'nullable|string',
            'documents.buyer.signature' => 'nullable|string',
            'documents.seller' => 'nullable|array',
            'documents.seller.id_front' => 'nullable|string',
            'documents.seller.id_back' => 'nullable|string',
            'documents.seller.car_license_front' => 'nullable|string',
            'documents.seller.car_license_back' => 'nullable|string',
            'documents.seller.signature' => 'nullable|string',
            'policy_approved' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = $user->id;
        $data['location_id'] = $this->getEffectiveLocationId($user);
        $data['status'] = 'draft';
        // Ensure default value when not provided
        if (!array_key_exists('policy_approved', $data)) {
            $data['policy_approved'] = false;
        }

        // Fetch buyer and seller contact data
        $buyerContact = \App\Contact::findOrFail($data['buyer_contact_id']);
        $sellerContact = \App\Contact::findOrFail($data['seller_contact_id']);

        // Populate denormalized inspection fields from contacts (for convenience/search)
        $data['buyer_full_name'] = $buyerContact->name;
        $data['buyer_phone'] = $buyerContact->mobile;
        $data['buyer_id_number'] = $buyerContact->tax_number;
        $data['seller_full_name'] = $sellerContact->name;
        $data['seller_phone'] = $sellerContact->mobile;
        $data['seller_id_number'] = $sellerContact->tax_number;

        // Extract items
        $items = $data['items'] ?? [];
        unset($data['items']);

        $inspection = CarInspection::create($data);

        // Save inspection items
        foreach ($items as $itemData) {
            $element = CheckCarElement::with(['category', 'subcategory'])->find($itemData['element_id']);
            if (!$element) continue;

            // Normalize option IDs
            $optionIds = $this->normalizeOptionIds($itemData['option_ids'] ?? []);

            $images = [];
            if (!empty($itemData['images']) && is_array($itemData['images'])) {
                $images = $this->processItemImages($inspection, $itemData['images']);
            }

            CheckCarInspectionItem::create([
                'inspection_id' => $inspection->id,
                'element_id' => $element->id,
                'title' => $itemData['title'] ?? null,
                'option_ids' => $optionIds,
                'images' => $images,
                'note' => $itemData['note'] ?? null,
            ]);
        }

        // Documents can come from the same payload (data['documents'])
        $documents = $data['documents'] ?? null;
        if (is_array($documents)) {
            $this->saveInspectionDocuments($inspection, $documents, false);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inspection created successfully',
            'data' => ['id' => $inspection->id]
        ], 201);
    }

    /**
     * Update inspection
     *
     * @urlParam id required The inspection ID
     */
    public function update(Request $request, $id)
    {

        $user = Auth::user();
        $locationId = $this->getEffectiveLocationId($user);

        $inspectionQuery = CarInspection::query();
        if (!empty($locationId)) {
            $inspectionQuery->where('location_id', $locationId);
        }

        $inspection = $inspectionQuery->findOrFail($id);

        // Support both flat body and { "data": { ... } } wrapper
        $input = $request->input('data');
        if (!is_array($input)) {
            $input = $request->all();
        }

        $validator = Validator::make($input, [
            'booking_id' => 'nullable|integer',
            'job_sheet_id' => 'nullable|integer',
            'buyer_contact_id' => 'nullable|integer',
            'seller_contact_id' => 'nullable|integer',
            'car_brand' => 'nullable|string|max:255',
            'car_model' => 'nullable|string|max:255',
            'car_year' => 'nullable|string|max:10',
            'car_color' => 'nullable|string|max:255',
            'car_chassis_number' => 'nullable|string|max:255',
            'car_plate_number' => 'nullable|string|max:255',
            'car_kilometers' => 'nullable|integer|min:0',
            'inspection_team' => 'nullable|array',
            'items' => 'nullable|array',
            'items.*.element_id' => 'required|integer',
            'items.*.option_ids' => 'nullable|array',
            'items.*.option_ids.*' => 'integer',
            'items.*.note' => 'nullable|string',
            'items.*.title' => 'nullable|string',
            'items.*.images' => 'nullable|array',
            'items.*.images.*' => 'nullable|string',
            'final_summary' => 'nullable|string',
            'overall_rating' => 'nullable|integer|min:1|max:5',
            'status' => 'nullable|string|in:draft,in_progress,completed,cancelled',
            'documents' => 'nullable|array',
            'documents.buyer' => 'nullable|array',
            'documents.buyer.id_front' => 'nullable|string',
            'documents.buyer.id_back' => 'nullable|string',
            'documents.buyer.signature' => 'nullable|string',
            'documents.seller' => 'nullable|array',
            'documents.seller.id_front' => 'nullable|string',
            'documents.seller.id_back' => 'nullable|string',
            'documents.seller.car_license_front' => 'nullable|string',
            'documents.seller.car_license_back' => 'nullable|string',
            'documents.seller.signature' => 'nullable|string',
            'policy_approved' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

     

        // Extract items
        $items = $data['items'] ?? null;
        unset($data['items']);


        $inspection->update($data);

        // Update items if provided
        if ($items !== null) {
            $processedElementIds = [];

            foreach ($items as $index => $itemData) {
                $element = CheckCarElement::with(['category', 'subcategory'])->find($itemData['element_id']);
                if (!$element) {
                    continue;
                }

                $processedElementIds[] = $element->id;

                // Normalize option IDs
                $optionIds = $this->normalizeOptionIds($itemData['option_ids'] ?? []);

                // Images handling:
                // - If "images" key is present:
                //     * non-empty array  => check if base64 (new) or already formatted (existing)
                //     * empty array      => clear images
                // - If "images" key is NOT present => keep existing images as-is
                $imagesFieldProvided = array_key_exists('images', $itemData);
                $newImages = null;

                if ($imagesFieldProvided) {
                    
                    if (!empty($itemData['images']) && is_array($itemData['images'])) {
                        // Check if these are new base64 images or already-formatted images from DB
                        $hasBase64 = $this->hasBase64Images($itemData['images']);
                        
                        
                        if ($hasBase64) {
                            // Process new base64 images
                            $newImages = $this->processItemImages($inspection, $itemData['images']);
                        } else {
                            // Already formatted images from DB - convert URLs back to proper format
                            $newImages = $this->convertUrlImagesToProperFormat($itemData['images']);
                        }
                    } else {
                        // Explicitly clear images when an empty array is provided
                        $newImages = [];
                    }
                } else {
                }

                $updatePayload = [
                    'title' => $itemData['title'] ?? null,
                    'option_ids' => $optionIds,
                    'note' => $itemData['note'] ?? null,
                ];

                if ($newImages !== null) {
                    $updatePayload['images'] = $newImages;
                }


                // Always update or create by (inspection_id, element_id)
                CheckCarInspectionItem::updateOrCreate(
                    [
                        'inspection_id' => $inspection->id,
                        'element_id' => $element->id,
                    ],
                    $updatePayload
                );
            }

            // Only update/create items in the payload - do not delete existing items
            // (deletion of items/images should be done via dedicated endpoints)
        }

        // Documents can come from the same payload (data['documents'])
        $documents = $data['documents'] ?? null;
        if (is_array($documents)) {
            $this->saveInspectionDocuments($inspection, $documents, true);
        }

        // Reload inspection with relationships for a rich response, similar to getByJobSheet
        $inspection->load([
            'items.element',
            'items.category',
            'items.subcategory',
            'creator',
            'documents',
            'buyerContact',
            'sellerContact',
            'contactDevice',
            'jobSheet',
        ]);

        // Ensure a share token exists so that a stable share URL can be returned
        if (empty($inspection->share_token)) {
            $inspection->generateShareToken();
            $inspection->refresh();
        }

        // Sort items by category, subcategory and element sort_order, then group by category/subcategory
        $itemsCollection = $inspection->items;
        $sortedItems = collect($itemsCollection)
            ->sortBy(function ($item) {
                $categoryOrder = $item->category ? (int) $item->category->sort_order : 9999;
                $subcategoryOrder = $item->subcategory ? (int) $item->subcategory->sort_order : 9999;
                $elementOrder = $item->element ? (int) $item->element->sort_order : 9999;

                return sprintf('%05d-%05d-%05d-%05d', $categoryOrder, $subcategoryOrder, $elementOrder, $item->id);
            });

        $documentsGrouped = $inspection->documents
            ->groupBy('party')
            ->map(function ($docs) {
                return $docs->map(function ($doc) {
                    $filePath = $doc->file_path;
                    $mimeType = $doc->mime_type ?? 'image/png';
                    return [
                        'id' => $doc->id,
                        'type' => $doc->document_type,
                        'file_path' => $filePath,
                        'mime_type' => $mimeType,
                        'media_type' => $this->getMediaTypeFromMime($mimeType),
                        'url' => $filePath ? asset('storage/' . ltrim($filePath, '/')) : null,
                    ];
                })->values();
            });

        // Simple items array (flat list used by mobile app)
        $itemsArray = $this->buildSimpleItemsArray($inspection->items);

        return response()->json([
            'success' => true,
            'message' => 'Inspection updated successfully',
            'data' => [
                'id' => $inspection->id,
                'booking_id' => $inspection->booking_id,
                'job_sheet_id' => $inspection->job_sheet_id,
                'job_sheet_km' => $inspection->jobSheet ? $inspection->jobSheet->km : null,
                'buyer_contact_id' => $inspection->buyer_contact_id,
                'seller_contact_id' => $inspection->seller_contact_id,
                'contact_device_id' => $inspection->contact_device_id,
                'buyer' => $inspection->buyerContact ? [
                    'id' => $inspection->buyerContact->id,
                    'first_name' => $inspection->buyerContact->first_name,
                    'middle_name' => $inspection->buyerContact->middle_name,
                    'last_name' => $inspection->buyerContact->last_name,
                    'name' => $inspection->buyerContact->name,
                    'mobile' => $inspection->buyerContact->mobile,
                ] : null,
                'seller' => $inspection->sellerContact ? [
                    'id' => $inspection->sellerContact->id,
                    'first_name' => $inspection->sellerContact->first_name,
                    'middle_name' => $inspection->sellerContact->middle_name,
                    'last_name' => $inspection->sellerContact->last_name,
                    'name' => $inspection->sellerContact->name,
                    'mobile' => $inspection->sellerContact->mobile,
                ] : null,
                'contact_device' => $inspection->contactDevice ? [
                    'id' => $inspection->contactDevice->id,
                    'name' => $inspection->contactDevice->device_name ?? $inspection->contactDevice->name ?? null,
                    'model' => $inspection->contactDevice->device_model ?? $inspection->contactDevice->model ?? null,
                    'plate_number' => $inspection->contactDevice->plate_number ?? null,
                    'chassis_number' => $inspection->contactDevice->chassis_number ?? null,
                    'color' => $inspection->contactDevice->color ?? null,
                    'manufacturing_year' => $inspection->contactDevice->manufacturing_year ?? null,
                    'car_type' => $inspection->contactDevice->car_type ?? null,
                ] : null,
                'inspection_team' => $inspection->inspection_team,
                'sections' => $inspection->sections,
                'items' => $itemsArray,
                'final_summary' => $inspection->final_summary,
                'overall_rating' => $inspection->overall_rating,
                'status' => $inspection->status,
                'share_token' => $inspection->share_token,
                'share_url' => $inspection->share_token ? $inspection->getShareUrl() : null,
                'created_at' => $inspection->created_at,
                'updated_at' => $inspection->updated_at,
                'created_by' => $inspection->created_by,
                'creator' => $inspection->creator ? [
                    'id' => $inspection->creator->id,
                    'name' => $inspection->creator->first_name . ' ' . $inspection->creator->last_name,
                ] : null,
                'policy_approved' => $inspection->policy_approved,
             
                'documents' => $documentsGrouped,
            ],
        ]);
    }

    private function hasBase64Images(array $images): bool
    {
        foreach ($images as $image) {
            if (is_string($image)) {
                // Check if it's a base64 data URI (starts with 'data:')
                if (strpos($image, 'data:') === 0) {
                    return true;
                }
            } elseif (is_array($image)) {
                // Check if it has base64/data field with base64 content
                $base64 = $image['base64'] ?? ($image['data'] ?? null);
                if (is_string($base64) && strpos($base64, 'data:') === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    private function convertUrlImagesToProperFormat(array $images): array
    {
        $result = [];
        foreach ($images as $index => $image) {
            if (is_string($image)) {
                // Convert URL string to proper image object format
                // Extract file_path from URL: https://domain/storage/path/to/file.png -> path/to/file.png
                $filePath = $this->extractFilePathFromUrl($image);
                if ($filePath) {
                    $result[] = [
                        'type' => 'image_' . $index,
                        'file_path' => $filePath,
                        'mime_type' => $this->getMimeTypeFromPath($filePath),
                    ];
                }
            } elseif (is_array($image) && isset($image['file_path'])) {
                // Already in proper format - keep as-is
                $result[] = $image;
            }
        }
        return $result;
    }

    private function extractFilePathFromUrl(string $url): ?string
    {
        // Extract path from URL: https://domain/storage/path/to/file.png -> path/to/file.png
        if (strpos($url, '/storage/') !== false) {
            $parts = explode('/storage/', $url);
            return end($parts);
        }
        return null;
    }

    private function getMimeTypeFromPath(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    private function processItemImages(CarInspection $inspection, array $imagesInput): array
    {
        $result = [];
        $seenHashes = [];

        foreach ($imagesInput as $key => $value) {
            $type = (string) $key;
            $base64 = null;

            // Case 1: simple array of base64 strings: ['data:...','data:...']
            if (is_string($value)) {
                $base64 = $value;
                // For numeric keys, give them a generic type like "image_0"
                if (is_int($key)) {
                    $type = 'image_' . $key;
                }

            // Case 2: array/object with fields like ['type' => 'front', 'base64' => '...']
            } elseif (is_array($value)) {
                $type = (string) ($value['type'] ?? $type);
                $base64 = $value['base64'] ?? ($value['data'] ?? null);
            }

            if (!is_string($base64) || trim($base64) === '') {
                continue;
            }

            // Deduplicate identical images within the same request to avoid
            // saving the same image multiple times for one element.
            $hash = md5($base64);
            if (isset($seenHashes[$hash])) {
                continue;
            }
            $seenHashes[$hash] = true;

            [$path, $mime] = $this->storeBase64Image($base64, $inspection->id, 'element', $type);
            if ($path === null) {
                continue;
            }

            $result[] = [
                'type' => $type,
                'file_path' => $path,
                'mime_type' => $mime,
            ];
        }

        return $result;
    }

    private function saveInspectionDocuments(CarInspection $inspection, array $documents, bool $replaceExisting = false): void
    {
        if ($replaceExisting) {
            $inspection->documents()->delete();
        }

        foreach (['buyer', 'seller'] as $party) {
            if (empty($documents[$party]) || !is_array($documents[$party])) {
                continue;
            }

            foreach ($documents[$party] as $type => $base64) {
                if (!is_string($base64) || trim($base64) === '') {
                    continue;
                }

                [$path, $mime] = $this->storeBase64Image($base64, $inspection->id, $party, (string) $type);
                if ($path === null) {
                    continue;
                }

                CheckCarInspectionDocument::create([
                    'inspection_id' => $inspection->id,
                    'party' => $party,
                    'document_type' => (string) $type,
                    'file_path' => $path,
                    'mime_type' => $mime,
                ]);
            }
        }
    }

    private function storeBase64Image(string $base64, int $inspectionId, string $party, string $type): array
    {
        $mime = 'image/png';
        $data = $base64;

        if (preg_match('/^data:(.*?);base64,(.*)$/', $base64, $matches)) {
            $mime = $matches[1];
            $data = $matches[2];
        }

        $binary = base64_decode($data, true);
        if ($binary === false) {
            return [null, null];
        }

        $extension = $this->guessExtensionFromMime($mime);
        $fileName = 'inspection_' . $inspectionId . '_' . $party . '_' . $type . '_' . time() . '_' . uniqid() . '.' . $extension;
        $path = 'checkcar/inspections/' . $inspectionId . '/' . $fileName;

        Storage::disk('public')->put($path, $binary);

        return [$path, $mime];
    }

    private function guessExtensionFromMime(string $mime): string
    {
        $mime = strtolower($mime);

        // Image types
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            return 'jpg';
        }
        if ($mime === 'image/gif') {
            return 'gif';
        }
        if ($mime === 'image/png') {
            return 'png';
        }
        if ($mime === 'image/webp') {
            return 'webp';
        }

        // PDF
        if ($mime === 'application/pdf') {
            return 'pdf';
        }

        // Video types
        if ($mime === 'video/mp4') {
            return 'mp4';
        }
        if ($mime === 'video/quicktime' || $mime === 'video/mov') {
            return 'mov';
        }
        if ($mime === 'video/x-msvideo' || $mime === 'video/avi') {
            return 'avi';
        }
        if ($mime === 'video/webm') {
            return 'webm';
        }
        if ($mime === 'video/3gpp') {
            return '3gp';
        }
        if ($mime === 'video/x-matroska') {
            return 'mkv';
        }

        // Default to png for unknown types
        return 'png';
    }

    /**
     * Determine media type category from MIME type
     */
    private function getMediaTypeFromMime(string $mime): string
    {
        $mime = strtolower($mime);

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if ($mime === 'application/pdf') {
            return 'pdf';
        }

        return 'file';
    }

    /**
     * Delete or clear a single inspection element (options/images)
     *
     * @urlParam id required The inspection ID
     */
    public function deleteItemPart(Request $request, $id)
    {
        $user = Auth::user();
        $locationId = $this->getEffectiveLocationId($user);

        $inspectionQuery = CarInspection::query();
        if (!empty($locationId)) {
            $inspectionQuery->where('location_id', $locationId);
        }

        $inspection = $inspectionQuery->findOrFail($id);

        $input = $request->all();

        $validator = Validator::make($input, [
            'element_id' => 'required|integer|exists:checkcar_elements,id',
            'delete_element' => 'sometimes|boolean',
            'clear_options' => 'sometimes|boolean',
            'clear_images' => 'sometimes|boolean',
            'option_ids' => 'sometimes|array',
            'option_ids.*' => 'integer',
            'image_file_paths' => 'sometimes|array',
            'image_file_paths.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $elementId = $data['element_id'];

        $item = CheckCarInspectionItem::where('inspection_id', $inspection->id)
            ->where('element_id', $elementId)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Inspection item not found for this element',
            ], 404);
        }

        if (!empty($data['delete_element'])) {
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inspection element deleted successfully',
            ]);
        }

        $updated = false;

        if (!empty($data['clear_options'])) {
            $item->option_ids = [];
            $updated = true;
        } elseif (!empty($data['option_ids']) && is_array($data['option_ids'])) {
            // Remove only the specified option IDs from the current option_ids array
            $optionIdsToRemove = $this->normalizeOptionIds($data['option_ids']);
            $currentOptionIds = is_array($item->option_ids) ? $item->option_ids : [];

            $filteredOptionIds = collect($currentOptionIds)
                ->map(function ($id) {
                    return is_numeric($id) ? (int) $id : null;
                })
                ->filter(function ($id) use ($optionIdsToRemove) {
                    return $id !== null && !in_array($id, $optionIdsToRemove, true);
                })
                ->values()
                ->toArray();

            $item->option_ids = $filteredOptionIds;
            $updated = true;
        }

        if (!empty($data['clear_images'])) {
            $item->images = [];
            $updated = true;
        } elseif (!empty($data['image_file_paths']) && is_array($data['image_file_paths'])) {
            $currentImages = $item->images ?? [];
            $pathsToRemove = $data['image_file_paths'];

            $filteredImages = collect($currentImages)->filter(function ($image) use ($pathsToRemove) {
                $path = is_array($image) ? ($image['file_path'] ?? null) : null;
                if ($path === null) {
                    return true;
                }

                return !in_array($path, $pathsToRemove, true);
            })->values()->toArray();

            $item->images = $filteredImages;
            $updated = true;
        }

        if ($updated) {
            $item->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Inspection element updated successfully',
        ]);
    }

    /**
     * Delete inspection
     *
     * @urlParam id required The inspection ID
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $inspection = CarInspection::where('location_id', $user->location_id)
            ->findOrFail($id);

        $inspection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inspection deleted successfully'
        ]);
    }

    /**
     * Generate share link
     *
     * @urlParam id required The inspection ID
     */
    public function generateShareLink($id)
    {
        $user = Auth::user();

        $inspection = CarInspection::where('location_id', $user->location_id)
            ->findOrFail($id);

        // If a share token already exists, reuse it so the public URL stays stable
        // Otherwise generate a new one.
        $token = $inspection->share_token;
        if (empty($token)) {
            $token = $inspection->generateShareToken();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'share_token' => $token,
                'share_url' => $inspection->getShareUrl(),
            ],
        ]);
    }

    /**
     * Send inspection SMS to buyer and seller (manual endpoint)
     *
     * @urlParam id required The inspection ID
     */
    public function sendInspectionSms($id)
    {
        $user = Auth::user();

        $inspection = CarInspection::where('location_id', $user->location_id)
            ->findOrFail($id);

        // Ensure share token exists so share URL is stable
        if (empty($inspection->share_token) && method_exists($inspection, 'generateShareToken')) {
            $inspection->generateShareToken();
            $inspection->refresh();
        }

        // Fetch real Contact records from contacts table using contact IDs from inspection
        $buyerContactModel = null;
        if (!empty($inspection->buyer_contact_id)) {
            $buyerContactModel = \App\Contact::find($inspection->buyer_contact_id);
        }

        $sellerContactModel = null;
        if (!empty($inspection->seller_contact_id)) {
            $sellerContactModel = \App\Contact::find($inspection->seller_contact_id);
        }

        // Build contact objects from actual contact data
        $buyerContact = (object) [
            'id' => $buyerContactModel ? $buyerContactModel->id : null,
            'first_name' => $buyerContactModel ? $buyerContactModel->first_name : null,
            'name' => $buyerContactModel ? $buyerContactModel->name : $inspection->buyer_full_name,
            'mobile' => $buyerContactModel ? $buyerContactModel->mobile : $inspection->buyer_phone,
        ];

        $sellerContact = (object) [
            'id' => $sellerContactModel ? $sellerContactModel->id : null,
            'first_name' => $sellerContactModel ? $sellerContactModel->first_name : null,
            'name' => $sellerContactModel ? $sellerContactModel->name : $inspection->seller_full_name,
            'mobile' => $sellerContactModel ? $sellerContactModel->mobile : $inspection->seller_phone,
        ];


        $this->sendInspectionSmsNotifications($inspection, $buyerContact, $sellerContact);

        return response()->json([
            'success' => true,
            'message' => 'Inspection SMS send triggered',
        ]);
    }

    /**
     * Complete inspection
     *
     * @urlParam id required The inspection ID
     */
    public function complete($id)
    {
        $user = Auth::user();

        $inspection = CarInspection::where('location_id', $user->location_id)
            ->findOrFail($id);

        $inspection->update(['status' => 'completed']);

        return response()->json([
            'success' => true,
            'message' => 'Inspection marked as completed'
        ]);
    }

    /**
     * Send SMS notifications to buyer and seller using single template
     *
     * @param CarInspection $inspection
     * @param \App\Contact $buyerContact
     * @param \App\Contact $sellerContact
     */
    private function sendInspectionSmsNotifications($inspection, $buyerContact, $sellerContact)
    {
        try {

            // Get single SMS template for car inspection
            $smsMessage = SmsMessage::where('name', 'car_inspection')
                ->where('status', 1)
                ->first();

            if (!$smsMessage) {
                Log::warning('Car inspection SMS template not found', [
                    'template_name' => 'car_inspection'
                ]);
                return;
            }

            // Send SMS to buyer
            if (!empty($buyerContact->mobile)) {
        
                $buyerMessageContent = $this->replaceSmsVariables($smsMessage->message_template, $inspection, $buyerContact, 'buyer');
                
                $smsSent = SmsUtil::sendEpusheg($buyerContact->mobile, $buyerMessageContent);
                
                // Log SMS
                SmsLog::create([
                    'sms_message_id' => $smsMessage->id,
                    'contact_id' => $buyerContact->id,
                    'transaction_id' => null,
                    'job_sheet_id' => $inspection->job_sheet_id,
                    'mobile' => $buyerContact->mobile,
                    'message_content' => $buyerMessageContent,
                    'status' => $smsSent ? 'sent' : 'failed',
                    'error_message' => $smsSent ? null : 'Failed to send SMS',
                    'provider_balance' => SmsUtil::getLastNetBalance(),
                    'sent_at' => $smsSent ? now() : null,
                ]);

          
            } else {
                Log::warning('Skipping car inspection SMS for buyer: no mobile', [
                    'inspection_id' => $inspection->id,
                ]);
            }

            // Send SMS to seller
            if (!empty($sellerContact->mobile)) {
        
                $sellerMessageContent = $this->replaceSmsVariables($smsMessage->message_template, $inspection, $sellerContact, 'seller');
                
                $smsSent = SmsUtil::sendEpusheg($sellerContact->mobile, $sellerMessageContent);
                
                // Log SMS
                SmsLog::create([
                    'sms_message_id' => $smsMessage->id,
                    'contact_id' => $sellerContact->id,
                    'transaction_id' => null,
                    'job_sheet_id' => $inspection->job_sheet_id,
                    'mobile' => $sellerContact->mobile,
                    'message_content' => $sellerMessageContent,
                    'status' => $smsSent ? 'sent' : 'failed',
                    'error_message' => $smsSent ? null : 'Failed to send SMS',
                    'provider_balance' => SmsUtil::getLastNetBalance(),
                    'sent_at' => $smsSent ? now() : null,
                ]);

         
            } else {
                Log::warning('Skipping car inspection SMS for seller: no mobile', [
                    'inspection_id' => $inspection->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send car inspection SMS notifications', [
                'inspection_id' => $inspection->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Replace SMS template variables
     *
     * @param string $template
     * @param CarInspection $inspection
     * @param \App\Contact $contact
     * @param string $contactType 'buyer' or 'seller'
     * @return string
     */
    private function replaceSmsVariables($template, $inspection, $contact, $contactType)
    {
        // Get the original share URL and shorten it
        $originalShareUrl = $inspection->share_token ? $inspection->getShareUrl() : '';
        $shortShareUrl = '';
        if (!empty($originalShareUrl)) {
            $urlShortener = new UrlShortener();
            $shortShareUrl = $urlShortener->shorten($originalShareUrl);
        }

        $variables = [
            '{{inspection_id}}' => $inspection->id,
            '{{customer_name}}' => $contact->first_name ?? $contact->name ?? '',
            '{{customer_full_name}}' => $contact->name ?? '',
            '{{customer_mobile}}' => $contact->mobile ?? '',
            '{{car_brand}}' => $inspection->car_brand ?? '',
            '{{car_model}}' => $inspection->car_model ?? '',
            '{{car_year}}' => $inspection->car_year ?? '',
            '{{car_color}}' => $inspection->car_color ?? '',
            '{{car_chassis_number}}' => $inspection->car_chassis_number ?? '',
            '{{car_plate_number}}' => $inspection->car_plate_number ?? '',
            '{{car_kilometers}}' => $inspection->car_kilometers ?? 0,
            '{{inspection_status}}' => $inspection->status ?? '',
            '{{contact_type}}' => $contactType,
            '{{inspection_date}}' => $inspection->created_at ? $inspection->created_at->format('Y-m-d H:i') : '',
            '{{share_url}}' => $shortShareUrl, // Now using shortened URL
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

}
