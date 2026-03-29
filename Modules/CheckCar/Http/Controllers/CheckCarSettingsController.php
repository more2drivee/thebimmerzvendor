<?php

namespace Modules\CheckCar\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\CheckCar\Entities\CheckCarElement;
use Modules\CheckCar\Entities\CheckCarElementOption;
use Modules\CheckCar\Entities\CheckCarPhraseTemplate;
use Modules\CheckCar\Entities\CheckCarQuestionCategory;
use Modules\CheckCar\Entities\CheckCarQuestionSubcategory;
use Modules\CheckCar\Entities\CheckCarServiceSetting;
use Modules\CheckCar\Entities\PrivacyPolicy;
use App\Product;
use Yajra\DataTables\Facades\DataTables;

class CheckCarSettingsController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();

        // Determine effective location for settings (admin can switch, others fixed to their own)
        $requestedLocationId = $request->get('location_id');
        $userLocationId = optional($user)->location_id;
        $isAdmin = $user && ($user->can('superadmin') || $user->hasAnyPermission('Admin#' . $user->business_id) || $user->can('access_all_locations'));

        $effectiveLocationId = $userLocationId;
        if ($isAdmin && !empty($requestedLocationId)) {
            $effectiveLocationId = (int) $requestedLocationId;
        }

        Log::info('CheckCarSettingsController@index called', [
            'user_id' => optional($user)->id,
            'user_location_id' => $userLocationId,
            'requested_location_id' => $requestedLocationId,
            'is_admin' => $isAdmin,
        ]);

        $sectionKeys = [
            'exterior',
            'chassis',
            'engine',
            'suspension',
            'battery',
            'tires',
            'trunk',
            'interior',
        ];

        $sectionLabels = [
            'exterior' => __('checkcar::lang.section_exterior'),
            'chassis' => __('checkcar::lang.section_chassis'),
            'engine' => __('checkcar::lang.section_engine'),
            'suspension' => __('checkcar::lang.section_suspension'),
            'battery' => __('checkcar::lang.section_battery'),
            'tires' => __('checkcar::lang.section_tires'),
            'trunk' => __('checkcar::lang.section_trunk'),
            'interior' => __('checkcar::lang.section_interior'),
        ];

        // For admins, always show all data (no location filter). Non-admins see only their own location.
        $locationId = $isAdmin ? null : $effectiveLocationId;

        $categories = Schema::hasTable('checkcar_question_categories')
            ? CheckCarQuestionCategory::forLocation($locationId)->ordered()->get()
            : collect();

        $subcategories = Schema::hasTable('checkcar_question_subcategories')
            ? CheckCarQuestionSubcategory::forLocation($locationId)->with('category')->ordered()->get()
            : collect();

        $templates = Schema::hasTable('checkcar_phrase_templates')
            ? CheckCarPhraseTemplate::forLocation($locationId)->with('element')->orderBy('element_id')->orderBy('id')->get()
            : collect();

        $elements = Schema::hasTable('checkcar_elements')
            ? CheckCarElement::forLocation($locationId)->with(['category', 'subcategory'])->ordered()->get()
            : collect();

        $elementOptions = Schema::hasTable('checkcar_element_options')
            ? CheckCarElementOption::forLocation($locationId)->with('element')->ordered()->get()
            : collect();

        Log::info('CheckCarSettingsController@index data counts', [
            'location_id_used' => $locationId,
            'categories_count' => $categories->count(),
            'subcategories_count' => $subcategories->count(),
            'elements_count' => $elements->count(),
            'templates_count' => $templates->count(),
        ]);

        // Locations list and names for location switcher and display
        $locations = collect();
        $locationNames = collect();
        if ($user) {
            $business_id = $request->session()->get('user.business_id');

            if ($isAdmin && method_exists($user, 'permitted_locations')) {
                $permitted = $user->permitted_locations($business_id);
                if ($permitted === 'all') {
                    $locations = \App\BusinessLocation::where('business_id', $business_id)->orderBy('name')->get();
                } elseif (is_array($permitted) && !empty($permitted)) {
                    $locations = \App\BusinessLocation::where('business_id', $business_id)
                        ->whereIn('id', $permitted)
                        ->orderBy('name')
                        ->get();
                }
            } elseif ($userLocationId) {
                // Non-admin: make sure their own location is available for display
                $locations = \App\BusinessLocation::where('business_id', $business_id)
                    ->where('id', $userLocationId)
                    ->orderBy('name')
                    ->get();
            }

            $locationNames = $locations->pluck('name', 'id');
        }

        // Load current service setting for this business and default type
        $serviceSetting = null;
        $serviceProducts = collect();
        $privacyPolicy = null;
        if (Schema::hasTable('checkcar_service_settings') && Schema::hasTable('products')) {
            $business_id = $request->session()->get('user.business_id');
            if ($business_id) {
                $serviceSetting = CheckCarServiceSetting::with('product')
                    ->forBusiness($business_id)
                    ->where('checkcar_service_settings.type', 'service')
                    ->first();

                // Dropdown should render service products with stock disabled (enable_stock = 0) or NULL
                $serviceProducts = Product::where(function ($q) {
                        $q->where('enable_stock', 0)
                          ->orWhereNull('enable_stock');
                    })
                    ->orderBy('name')
                    ->get(['id', 'name', 'sku']);

                // Load privacy policy for this business if table exists
                if (Schema::hasTable('privacy_policies')) {
                    $privacyPolicy = PrivacyPolicy::forBusiness($business_id);
                }
            }
        }

        // Element types for dropdown
        $elementTypes = [
            'single' => 'Single Selection',
            'multiple' => 'Multiple Selection',
            'text' => 'Text Input',
        ];

        return view('checkcar::settings.index', compact(
            'sectionKeys',
            'sectionLabels',
            'categories',
            'subcategories',
            'templates',
            'elements',
            'elementOptions',
            'elementTypes',
            'serviceSetting',
            'serviceProducts',
            'privacyPolicy',
            'locations',
            'locationId',
            'locationNames',
            'isAdmin'
        ));
    }

    /**
     * Update Privacy Policy text for CheckCar settings (per business)
     */
    public function updatePrivacyPolicy(Request $request)
    {
        if (!Schema::hasTable('privacy_policies')) {
            abort(500, 'privacy_policies table is not migrated yet.');
        }

        $data = $request->validate([
            'privacy_policy' => 'required|string',
        ]);

        $business_id = $request->session()->get('user.business_id');
        if (!$business_id) {
            abort(403, 'Business not found in session');
        }

        PrivacyPolicy::updateOrCreate(
            ['business_id' => $business_id],
            ['content' => $data['privacy_policy']]
        );

        return redirect()->back()->with('status', __('messages.success'));
    }

    public function storeCategory(Request $request)
    {
        if (!Schema::hasTable('checkcar_question_categories')) {
            abort(500, 'checkcar_question_categories table is not migrated yet.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'nullable|string',
            'location_id' => 'nullable|integer|exists:business_locations,id',
        ]);

        $user = auth()->user();
        $data['created_by'] = optional($user)->id;

        // Resolve location for category: admin can choose any permitted location; others use their own
        $userLocationId = optional($user)->location_id;
        $isAdmin = $user && ($user->can('superadmin') || $user->hasAnyPermission('Admin#' . $user->business_id) || $user->can('access_all_locations'));
        $effectiveLocationId = $userLocationId;
        if ($isAdmin && $request->filled('location_id')) {
            $effectiveLocationId = (int) $request->input('location_id');
        }

        $data['location_id'] = $effectiveLocationId;
        $data['active'] = $request->has('active') ? 1 : 0;
        $data['sort_order'] = $request->input('sort_order', 0);

        CheckCarQuestionCategory::create($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function updateCategory(Request $request, $category)
    {
        if (!Schema::hasTable('checkcar_question_categories')) {
            abort(500, 'checkcar_question_categories table is not migrated yet.');
        }

        $cat = CheckCarQuestionCategory::findOrFail($category);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'nullable|string',
            'location_id' => 'nullable|integer|exists:business_locations,id',
        ]);

        $user = auth()->user();
        $userLocationId = optional($user)->location_id;
        $isAdmin = $user && ($user->can('superadmin') || $user->hasAnyPermission('Admin#' . $user->business_id) || $user->can('access_all_locations'));
        $effectiveLocationId = $userLocationId;
        if ($isAdmin && $request->filled('location_id')) {
            $effectiveLocationId = (int) $request->input('location_id');
        }

        $data['location_id'] = $effectiveLocationId;
        $data['active'] = $request->has('active') ? 1 : 0;
        $data['sort_order'] = $request->input('sort_order', 0);

        $cat->update($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function destroyCategory(Request $request, $category)
    {
        if (!Schema::hasTable('checkcar_question_categories')) {
            abort(500, 'checkcar_question_categories table is not migrated yet.');
        }

        CheckCarQuestionCategory::whereKey($category)->delete();
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function storeTemplate(Request $request)
    {
        if (!Schema::hasTable('checkcar_phrase_templates')) {
            abort(500, 'checkcar_phrase_templates table is not migrated yet.');
        }

        $data = $request->validate([
            'element_id' => 'required|integer|exists:checkcar_elements,id',
            'phrase' => 'required|string',
        ]);

        $data['created_by'] = auth()->id();
        $data['section_key'] = 'element'; // Default section key for element presets

        CheckCarPhraseTemplate::create($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function updateTemplate(Request $request, $template)
    {
        if (!Schema::hasTable('checkcar_phrase_templates')) {
            abort(500, 'checkcar_phrase_templates table is not migrated yet.');
        }

        $tpl = CheckCarPhraseTemplate::findOrFail($template);

        $data = $request->validate([
            'element_id' => 'required|integer|exists:checkcar_elements,id',
            'phrase' => 'required|string',
        ]);

        $tpl->update($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function destroyTemplate(Request $request, $template)
    {
        if (!Schema::hasTable('checkcar_phrase_templates')) {
            abort(500, 'checkcar_phrase_templates table is not migrated yet.');
        }

        CheckCarPhraseTemplate::whereKey($template)->delete();
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function storeSubcategory(Request $request)
    {
        if (!Schema::hasTable('checkcar_question_subcategories')) {
            abort(500, 'checkcar_question_subcategories table is not migrated yet.');
        }

        $data = $request->validate([
            'category_id' => 'required|integer|exists:checkcar_question_categories,id',
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'nullable|string',
            'location_id' => 'nullable|integer|exists:business_locations,id',
        ]);

        $user = auth()->user();
        $data['created_by'] = optional($user)->id;

        // Resolve location: default to category's location, but allow admin override via dropdown
        $category = CheckCarQuestionCategory::findOrFail($data['category_id']);
        $categoryLocationId = $category->location_id;
        $userLocationId = optional($user)->location_id;
        $isAdmin = $user && ($user->can('superadmin') || $user->hasAnyPermission('Admin#' . $user->business_id) || $user->can('access_all_locations'));
        $effectiveLocationId = $categoryLocationId ?? $userLocationId;
        if ($isAdmin && $request->filled('location_id')) {
            $effectiveLocationId = (int) $request->input('location_id');
        }

        $data['location_id'] = $effectiveLocationId;
        $data['active'] = $request->has('active') ? 1 : 0;
        $data['sort_order'] = $request->input('sort_order', 0);

        CheckCarQuestionSubcategory::create($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function updateSubcategory(Request $request, $subcategory)
    {
        if (!Schema::hasTable('checkcar_question_subcategories')) {
            abort(500, 'checkcar_question_subcategories table is not migrated yet.');
        }

        $subcat = CheckCarQuestionSubcategory::findOrFail($subcategory);

        $data = $request->validate([
            'category_id' => 'required|integer|exists:checkcar_question_categories,id',
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'nullable|string',
            'location_id' => 'nullable|integer|exists:business_locations,id',
        ]);

        $user = auth()->user();

        $category = CheckCarQuestionCategory::findOrFail($data['category_id']);
        $categoryLocationId = $category->location_id;
        $userLocationId = optional($user)->location_id;
        $isAdmin = $user && ($user->can('superadmin') || $user->hasAnyPermission('Admin#' . $user->business_id) || $user->can('access_all_locations'));
        $effectiveLocationId = $categoryLocationId ?? $userLocationId;
        if ($isAdmin && $request->filled('location_id')) {
            $effectiveLocationId = (int) $request->input('location_id');
        }

        $data['location_id'] = $effectiveLocationId;
        $data['active'] = $request->has('active') ? 1 : 0;
        $data['sort_order'] = $request->input('sort_order', 0);

        $subcat->update($data);
        return response()->json([
            'success' => true,
            'message' => 'messages.success',
        ]);
    }

    public function destroySubcategory(Request $request, $subcategory)
    {
        if (!Schema::hasTable('checkcar_question_subcategories')) {
            abort(500, 'checkcar_question_subcategories table is not migrated yet.');
        }

        CheckCarQuestionSubcategory::whereKey($subcategory)->delete();
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function storeElement(Request $request)
    {
        if (!Schema::hasTable('checkcar_elements')) {
            abort(500, 'checkcar_elements table is not migrated yet.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:single,multiple,text',
            'category_id' => 'nullable|integer|exists:checkcar_question_categories,id',
            'subcategory_id' => 'nullable|integer|exists:checkcar_question_subcategories,id',
            'sort_order' => 'nullable|integer',
            'required' => 'nullable|string',
            'max_options' => 'nullable|integer|min:0',
            'active' => 'nullable|string',
            'location_id' => 'nullable|integer|exists:business_locations,id',
        ]);

        $user = auth()->user();
        $data['created_by'] = optional($user)->id;

        // Resolve location for element: prefer subcategory/category location, allow admin override
        $categoryLocationId = null;
        $subcategoryLocationId = null;
        if (!empty($data['subcategory_id'])) {
            $subcategory = CheckCarQuestionSubcategory::find($data['subcategory_id']);
            $subcategoryLocationId = optional($subcategory)->location_id;
        }
        if (!empty($data['category_id'])) {
            $category = CheckCarQuestionCategory::find($data['category_id']);
            $categoryLocationId = optional($category)->location_id;
        }

        $userLocationId = optional($user)->location_id;
        $isAdmin = $user && ($user->can('superadmin') || $user->hasAnyPermission('Admin#' . $user->business_id) || $user->can('access_all_locations'));
        $effectiveLocationId = $subcategoryLocationId ?? $categoryLocationId ?? $userLocationId;
        if ($isAdmin && $request->filled('location_id')) {
            $effectiveLocationId = (int) $request->input('location_id');
        }

        $data['location_id'] = $effectiveLocationId;
        $data['active'] = $request->has('active') ? 1 : 0;
        $data['required'] = $request->has('required') ? 1 : 0;
        $data['sort_order'] = $request->input('sort_order', 0);
        $data['max_options'] = $request->input('max_options', 0);
        $data['type'] = $request->input('type', 'text');

        $element = CheckCarElement::create($data);

        // Save element options
        if ($request->has('element_options')) {
            foreach ($request->input('element_options', []) as $option) {
                if (!empty($option['label'])) {
                    CheckCarElementOption::create([
                        'element_id' => $element->id,
                        'label' => $option['label'],
                        'sort_order' => $option['sort_order'] ?? 0,
                    ]);
                }
            }
        }

        // Save phrase templates
        if ($request->has('phrase_templates')) {
            foreach ($request->input('phrase_templates', []) as $template) {
                if (!empty($template['phrase'])) {
                    CheckCarPhraseTemplate::create([
                        'element_id' => $element->id,
                        'section_key' => 'element',
                        'phrase' => $template['phrase'],
                        'preset_key' => 'element_' . $element->id,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        }
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function updateElement(Request $request, $element)
    {
        if (!Schema::hasTable('checkcar_elements')) {
            abort(500, 'checkcar_elements table is not migrated yet.');
        }

        $el = CheckCarElement::findOrFail($element);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:single,multiple,text',
            'category_id' => 'nullable|integer|exists:checkcar_question_categories,id',
            'subcategory_id' => 'nullable|integer|exists:checkcar_question_subcategories,id',
            'sort_order' => 'nullable|integer',
            'required' => 'nullable|string',
            'max_options' => 'nullable|integer|min:0',
            'active' => 'nullable|string',
            'location_id' => 'nullable|integer|exists:business_locations,id',
        ]);

        $user = auth()->user();

        $categoryLocationId = null;
        $subcategoryLocationId = null;
        if (!empty($data['subcategory_id'])) {
            $subcategory = CheckCarQuestionSubcategory::find($data['subcategory_id']);
            $subcategoryLocationId = optional($subcategory)->location_id;
        }
        if (!empty($data['category_id'])) {
            $category = CheckCarQuestionCategory::find($data['category_id']);
            $categoryLocationId = optional($category)->location_id;
        }

        $userLocationId = optional($user)->location_id;
        $isAdmin = $user && ($user->can('superadmin') || $user->hasAnyPermission('Admin#' . $user->business_id) || $user->can('access_all_locations'));
        $effectiveLocationId = $subcategoryLocationId ?? $categoryLocationId ?? $userLocationId;
        if ($isAdmin && $request->filled('location_id')) {
            $effectiveLocationId = (int) $request->input('location_id');
        }

        $data['location_id'] = $effectiveLocationId;
        $data['active'] = $request->has('active') ? 1 : 0;
        $data['required'] = $request->has('required') ? 1 : 0;
        $data['sort_order'] = $request->input('sort_order', 0);
        $data['max_options'] = $request->input('max_options', 0);
        $data['type'] = $request->input('type', 'text');

        $el->update($data);

        // Update element options - delete existing and recreate
        CheckCarElementOption::where('element_id', $el->id)->delete();
        if ($request->has('edit_element_options')) {
            foreach ($request->input('edit_element_options', []) as $option) {
                if (!empty($option['label'])) {
                    CheckCarElementOption::create([
                        'element_id' => $el->id,
                        'label' => $option['label'],
                        'sort_order' => $option['sort_order'] ?? 0,
                    ]);
                }
            }
        }

        // Update phrase templates - delete existing and recreate
        CheckCarPhraseTemplate::where('element_id', $el->id)->delete();
        if ($request->has('edit_phrase_templates')) {
            foreach ($request->input('edit_phrase_templates', []) as $template) {
                if (!empty($template['phrase'])) {
                    CheckCarPhraseTemplate::create([
                        'element_id' => $el->id,
                        'section_key' => 'element',
                        'phrase' => $template['phrase'],
                        'preset_key' => 'element_' . $el->id,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function destroyElement(Request $request, $element)
    {
        if (!Schema::hasTable('checkcar_elements')) {
            abort(500, 'checkcar_elements table is not migrated yet.');
        }

        CheckCarElement::whereKey($element)->delete();
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    /**
     * Get element data with options and phrase templates for AJAX
     */
    public function getElementData($element)
    {
        $el = CheckCarElement::with(['options' => function ($q) {
            $q->orderBy('sort_order')->orderBy('id');
        }])->findOrFail($element);

        $phraseTemplates = CheckCarPhraseTemplate::where('element_id', $element)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'element' => $el,
            'options' => $el->options,
            'phrase_templates' => $phraseTemplates,
        ]);
    }

    // Element Options CRUD (standalone - type is now on element, not option)
    public function storeElementOption(Request $request)
    {
        if (!Schema::hasTable('checkcar_element_options')) {
            abort(500, 'checkcar_element_options table is not migrated yet.');
        }

        $data = $request->validate([
            'element_id' => 'required|integer|exists:checkcar_elements,id',
            'label' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $data['sort_order'] = $request->input('sort_order', 0);

        CheckCarElementOption::create($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function updateElementOption(Request $request, $option)
    {
        if (!Schema::hasTable('checkcar_element_options')) {
            abort(500, 'checkcar_element_options table is not migrated yet.');
        }

        $opt = CheckCarElementOption::findOrFail($option);

        $data = $request->validate([
            'element_id' => 'required|integer|exists:checkcar_elements,id',
            'label' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $data['sort_order'] = $request->input('sort_order', 0);

        $opt->update($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    public function destroyElementOption(Request $request, $option)
    {
        if (!Schema::hasTable('checkcar_element_options')) {
            abort(500, 'checkcar_element_options table is not migrated yet.');
        }

        CheckCarElementOption::whereKey($option)->delete();
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    /**
     * Get full structure: categories -> subcategories -> elements
     * Returns nested JSON structure for API consumption
     */
    public function getFullStructure(Request $request)
    {
        $locationId = optional(auth()->user())->location_id;

        $categories = CheckCarQuestionCategory::active()
            ->forLocation($locationId)
            ->ordered()
            ->with([
                'subcategories' => function ($q) {
                    $q->active()->ordered();
                },
                'subcategories.elements' => function ($q) {
                    $q->active()->forLocation(optional(auth()->user())->location_id)->ordered();
                },
            ])
            ->get();

        $structure = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'section_key' => $category->section_key,
                'sort_order' => $category->sort_order,
                'subcategories' => $category->subcategories->map(function ($subcategory) {
                    return [
                        'id' => $subcategory->id,
                        'name' => $subcategory->name,
                        'sort_order' => $subcategory->sort_order,
                        'elements' => $subcategory->elements->map(function ($element) {
                            return [
                                'id' => $element->id,
                                'name' => $element->name,
                                'required' => $element->required,
                                'max_options' => $element->max_options,
                                'sort_order' => $element->sort_order,
                                'options' => $element->options->map(function ($option) {
                                    return [
                                        'id' => $option->id,
                                        'type' => $option->type,
                                        'label' => $option->label,
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $structure,
            'element_types' => [
                'single' => 'Single Selection',
                'multiple' => 'Multiple Selection',
                'text' => 'Text Input',
            ],
        ]);
    }

    /**
     * Get element by ID with full details
     */
    public function getElement(Request $request, $element)
    {
        $el = CheckCarElement::with(['category', 'subcategory', 'options'])->findOrFail($element);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $el->id,
                'name' => $el->name,
                'required' => $el->required,
                'max_options' => $el->max_options,
                'sort_order' => $el->sort_order,
                'active' => $el->active,
                'category_id' => $el->category_id,
                'subcategory_id' => $el->subcategory_id,
                'category' => $el->category ? [
                    'id' => $el->category->id,
                    'name' => $el->category->name,
                ] : null,
                'subcategory' => $el->subcategory ? [
                    'id' => $el->subcategory->id,
                    'name' => $el->subcategory->name,
                ] : null,
                'options' => $el->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'type' => $option->type,
                        'label' => $option->label,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get elements by subcategory
     */
    public function getElementsBySubcategory(Request $request, $subcategory)
    {
        $locationId = optional(auth()->user())->location_id;

        $elements = CheckCarElement::where('subcategory_id', $subcategory)
            ->active()
            ->forLocation($locationId)
            ->ordered()
            ->with('options')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $elements->map(function ($el) {
                return [
                    'id' => $el->id,
                    'name' => $el->name,
                    'required' => $el->required,
                    'max_options' => $el->max_options,
                    'sort_order' => $el->sort_order,
                    'options' => $el->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'type' => $option->type,
                            'label' => $option->label,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Get subcategories by category
     */
    public function getSubcategoriesByCategory(Request $request, $category)
    {
        $locationId = optional(auth()->user())->location_id;

        $subcategories = CheckCarQuestionSubcategory::where('category_id', $category)
            ->active()
            ->forLocation($locationId)
            ->ordered()
            ->with(['elements' => function ($q) use ($locationId) {
                $q->active()->forLocation($locationId)->ordered();
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subcategories->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'sort_order' => $sub->sort_order,
                    'elements_count' => $sub->elements->count(),
                    'elements' => $sub->elements->map(function ($el) {
                        return [
                            'id' => $el->id,
                            'name' => $el->name,
                            'required' => $el->required,
                            'max_options' => $el->max_options,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Store element preset (phrase template)
     */
    public function storeElementPreset(Request $request, $element)
    {
        if (!Schema::hasTable('checkcar_phrase_templates')) {
            abort(500, 'checkcar_phrase_templates table is not migrated yet.');
        }

        $data = $request->validate([
            'preset_key' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'phrase' => 'required|string',
        ]);

        $data['element_id'] = $element;
        $data['created_by'] = auth()->id();

        CheckCarPhraseTemplate::create($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    /**
     * Update element preset (phrase template)
     */
    public function updateElementPreset(Request $request, $element, $preset)
    {
        if (!Schema::hasTable('checkcar_phrase_templates')) {
            abort(500, 'checkcar_phrase_templates table is not migrated yet.');
        }

        $template = CheckCarPhraseTemplate::where('id', $preset)
            ->where('element_id', $element)
            ->firstOrFail();

        $data = $request->validate([
            'preset_key' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'phrase' => 'required|string',
        ]);

        $template->update($data);
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    /**
     * Delete element preset (phrase template)
     */
    public function destroyElementPreset(Request $request, $element, $preset)
    {
        if (!Schema::hasTable('checkcar_phrase_templates')) {
            abort(500, 'checkcar_phrase_templates table is not migrated yet.');
        }

        CheckCarPhraseTemplate::where('id', $preset)
            ->where('element_id', $element)
            ->delete();
        return response()->json(['success' => true, 'message' => __('messages.success')]);
    }

    /**
     * Get element presets
     */
    public function getElementPresets(Request $request, $element)
    {
        if (!Schema::hasTable('checkcar_phrase_templates')) {
            return response()->json(['success' => false, 'message' => 'Table not available']);
        }

        $locationId = optional(auth()->user())->location_id;

        $presets = CheckCarPhraseTemplate::where('element_id', $element)
            ->forLocation($locationId)
            ->presets()
            ->orderBy('preset_key')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $presets->map(function ($preset) {
                return [
                    'id' => $preset->id,
                    'preset_key' => $preset->preset_key,
                    'label' => $preset->label,
                    'phrase' => $preset->phrase,
                ];
            }),
        ]);
    }

    /**
     * Get services for sidebar datatable (similar to ServiceController)
     */
    public function getServicesSidebar(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        // Start from checkcar_service_settings rows and join related product data for all types
        $services = CheckCarServiceSetting::forBusiness($business_id)
            ->leftJoin('products', 'products.id', '=', 'checkcar_service_settings.product_id')
            ->select([
                'checkcar_service_settings.id',
                'checkcar_service_settings.product_id',
                'products.name',
                'checkcar_service_settings.type as setting_type',
                'checkcar_service_settings.value',
                'checkcar_service_settings.watermark_image',
            ]);

        if ($request->ajax()) {
            return DataTables::of($services)
                ->editColumn('watermark_image', function ($row) {
                    if (empty($row->watermark_image)) {
                        return '';
                    }

                    // Build URL using current domain and storage path
                    $url = asset('storage/' . ltrim($row->watermark_image, '/'));
                    return '<img src="' . e($url) . '" alt="Watermark" style="max-height:40px; max-width:120px;" />';
                })
                ->addColumn('action', function ($row) use ($business_id) {
                    // Edit button to open modal with pre-filled data
                    $html = '<button type="button" class="btn btn-primary btn-xs js-edit-service" ';
                    $html .= 'data-id="' . $row->id . '" ';
                    $html .= 'data-product-id="' . $row->product_id . '" ';
                    $html .= 'data-product-name="' . e($row->name) . '" ';
                    $html .= 'data-type="' . e($row->setting_type) . '" ';
                    $html .= 'data-value="' . e($row->value) . '" ';
                    $html .= 'data-watermark-image="' . e($row->watermark_image) . '">';
                    $html .= '<i class="fa fa-edit"></i> ' . __('messages.edit');
                    $html .= '</button>';
                    return $html;
                })
                ->rawColumns(['action', 'watermark_image'])
                ->make(true);
        }

        // Get current selected product for service type
        $selectedProduct = null;
        if (Schema::hasTable('checkcar_service_settings')) {
            $setting = CheckCarServiceSetting::forBusiness($business_id)
                ->where('checkcar_service_settings.type', 'service')
                ->with('product')
                ->first();
            if ($setting && $setting->product) {
                $selectedProduct = $setting->product;
            }
        }

        return view('checkcar::settings.service_sidebar', compact('selectedProduct'));
    }

    /**
     * Store selected service for CheckCar
     */
    public function storeSelectedService(Request $request)
    {
        if (!Schema::hasTable('checkcar_service_settings')) {
            return response()->json([
                'success' => false,
                'message' => 'Service settings table not available. Please run migrations.'
            ], 500);
        }

        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'type' => 'nullable|string|max:50',
            'value' => 'nullable|string|max:255',
            'watermark_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        // Default to 'service' type for product-based settings
        $type = $request->input('type', 'service');

        try {
            // Find existing setting for this business & type to preserve old watermark if no new file
            $existing = CheckCarServiceSetting::forBusiness($business_id)
                ->where('type', $type)
                ->first();

            $watermarkPath = $existing ? $existing->watermark_image : null;
            if ($request->hasFile('watermark_image')) {
                $watermarkPath = $request->file('watermark_image')->store('checkcar/watermarks', 'public');
            }

            $setting = CheckCarServiceSetting::updateOrCreate(
                [
                    'business_id' => $business_id,
                    'type' => $type,
                ],
                [
                    'product_id' => $request->product_id,
                    'value' => $request->input('value'),
                    'watermark_image' => $watermarkPath,
                    'created_by' => $user_id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => __('Service selected successfully'),
                'data' => [
                    'product_id' => $setting->product_id,
                    'value' => $setting->value,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update existing service setting
     */
    public function updateServiceSetting(Request $request, $service)
    {
        if (!Schema::hasTable('checkcar_service_settings')) {
            return response()->json([
                'success' => false,
                'message' => 'Service settings table not available. Please run migrations.'
            ], 500);
        }

        $setting = CheckCarServiceSetting::findOrFail($service);

        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'type' => 'nullable|string|max:50',
            'value' => 'nullable|string|max:255',
            'watermark_image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $type = $request->input('type', $setting->type);

        // Check if changing type would conflict with existing record (only one per type)
        if ($type !== $setting->type) {
            $existingForType = CheckCarServiceSetting::forBusiness($business_id)
                ->where('checkcar_service_settings.type', $type)
                ->where('checkcar_service_settings.id', '!=', $setting->id)
                ->first();

            if ($existingForType) {
                return response()->json([
                    'success' => false,
                    'message' => __('checkcar::lang.type_already_exists')
                ], 422);
            }
        }

        try {
            $watermarkPath = $setting->watermark_image;
            if ($request->hasFile('watermark_image')) {
                $watermarkPath = $request->file('watermark_image')->store('checkcar/watermarks', 'public');
            }

            $setting->update([
                'product_id' => $request->input('product_id'),
                'type' => $type,
                'value' => $request->input('value'),
                'watermark_image' => $watermarkPath,
                'created_by' => $user_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('messages.success'),
                'data' => [
                    'id' => $setting->id,
                    'product_id' => $setting->product_id,
                    'type' => $setting->type,
                    'value' => $setting->value,
                    'watermark_image' => $setting->watermark_image,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current selected service (product) for CheckCar
     */
    public function getSelectedService(Request $request)
    {
        if (!Schema::hasTable('checkcar_service_settings')) {
            return response()->json([
                'success' => false,
                'message' => 'Service settings table not available'
            ], 500);
        }

        $business_id = $request->session()->get('user.business_id');
        $setting = CheckCarServiceSetting::with('product')
            ->forBusiness($business_id)
            ->where('checkcar_service_settings.type', 'service')
            ->first();

        if ($setting && $setting->product) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $setting->product->id,
                    'name' => $setting->product->name,
                    'sku' => $setting->product->sku,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No service selected'
        ], 404);
    }
}
