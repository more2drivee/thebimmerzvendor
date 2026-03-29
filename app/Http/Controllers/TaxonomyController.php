<?php

namespace App\Http\Controllers;

use App\Category;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TaxonomyController extends Controller
{
   
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && ! auth()->user()->can('category.view') && ! auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $can_edit = true;
            if ($category_type == 'product' && ! auth()->user()->can('category.update')) {
                $can_edit = false;
            }

            $can_delete = true;
            if ($category_type == 'product' && ! auth()->user()->can('category.delete')) {
                $can_delete = false;
            }

            $business_id = request()->session()->get('user.business_id');

            $category = Category::where('business_id', $business_id)
                            ->where('category_type', $category_type)
                            ->select(['name', 'short_code', 'description', 'id', 'parent_id', 'logo']);

            return Datatables::of($category)
                ->addColumn(
                    'action', function ($row) use ($can_edit, $can_delete, $category_type) {
                        $html = '';
                        if ($can_edit) {
                            $html .= '<button data-href="'.action([\App\Http\Controllers\TaxonomyController::class, 'edit'], [$row->id]).'?type='.$category_type.'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary edit_category_button"><i class="glyphicon glyphicon-edit"></i>'.__('messages.edit').'</button>';
                        }

                        if ($can_delete) {
                            $html .= '&nbsp;<button data-href="'.action([\App\Http\Controllers\TaxonomyController::class, 'destroy'], [$row->id]).'" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete_category_button"><i class="glyphicon glyphicon-trash"></i> '.__('messages.delete').'</button>';
                        }

                        return $html;
                    }
                )
                ->editColumn('name', function ($row) {
                    if ($row->parent_id != 0) {
                        return '--'.$row->name;
                    } else {
                        return $row->name;
                    }
                })
                ->addColumn('logo', function ($row) {
                    if (!empty($row->logo)) {
                        return '<img src="'.asset('storage/' . $row->logo).'" alt="Logo" style="max-width: 50px; max-height: 50px;">';
                    }
                    return '';
                })
                ->removeColumn('id')
                ->removeColumn('parent_id')
                ->rawColumns(['action', 'logo'])
                ->make(true);
        }

        $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

        return view('taxonomy.index')->with(compact('module_category_data', 'module_category_data'));
    }


    public function importCategories(Request $request)
    {
        if (! auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);
        
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $category_type = $request->input('category_type', 'product');
        $business_id = $request->session()->get('user.business_id');
        $created_by = $request->session()->get('user.id');

        $added_categories = 0;
        $added_subcategories = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $category_name = isset($row[0]) ? trim($row[0]) : '';
            $subcategory_cell = isset($row[1]) ? trim($row[1]) : '';
            $sub_subcategory_cell = isset($row[2]) ? trim($row[2]) : '';
            $sub_sub_subcategory_cell = isset($row[3]) ? trim($row[3]) : '';

            if ($index === 0) {
                $lower_headers = strtolower($category_name . ' ' . $subcategory_cell . ' ' . $sub_subcategory_cell . ' ' . $sub_sub_subcategory_cell);
                if (strpos($lower_headers, 'category') !== false || strpos($lower_headers, 'subcategory') !== false) {
                    continue;
                }
            }

            if (empty($category_name)) {
                $skipped++;
                continue;
            }

            $category = Category::where('business_id', $business_id)
                ->where('category_type', $category_type)
                ->where('parent_id', 0)
                ->where('name', $category_name)
                ->first();

            if (empty($category)) {
                $category = Category::create([
                    'name' => $category_name,
                    'business_id' => $business_id,
                    'created_by' => $created_by,
                    'category_type' => $category_type,
                    'parent_id' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                $added_categories++;
            }

            $subcategories = ! empty($subcategory_cell)
                ? array_filter(array_map('trim', preg_split('/[;,\n]+/', $subcategory_cell)))
                : [];
            $sub_subcategories = ! empty($sub_subcategory_cell)
                ? array_filter(array_map('trim', preg_split('/[;,\n]+/', $sub_subcategory_cell)))
                : [];
            $sub_sub_subcategories = ! empty($sub_sub_subcategory_cell)
                ? array_filter(array_map('trim', preg_split('/[;,\n]+/', $sub_sub_subcategory_cell)))
                : [];

            if (! empty($subcategories)) {
                foreach ($subcategories as $subcategory_name) {
                    $subcategory = Category::where('business_id', $business_id)
                        ->where('category_type', $category_type)
                        ->where('parent_id', $category->id)
                        ->where('name', $subcategory_name)
                        ->first();

                    if (empty($subcategory)) {
                        $subcategory = Category::create([
                            'name' => $subcategory_name,
                            'business_id' => $business_id,
                            'created_by' => $created_by,
                            'category_type' => $category_type,
                            'parent_id' => $category->id,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        $added_subcategories++;
                    }

                    if (! empty($sub_subcategories)) {
                        foreach ($sub_subcategories as $sub_subcategory_name) {
                            $sub_subcategory = Category::where('business_id', $business_id)
                                ->where('category_type', $category_type)
                                ->where('parent_id', $subcategory->id)
                                ->where('name', $sub_subcategory_name)
                                ->first();

                            if (empty($sub_subcategory)) {
                                $sub_subcategory = Category::create([
                                    'name' => $sub_subcategory_name,
                                    'business_id' => $business_id,
                                    'created_by' => $created_by,
                                    'category_type' => $category_type,
                                    'parent_id' => $subcategory->id,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now(),
                                ]);
                                $added_subcategories++;
                            }

                            if (! empty($sub_sub_subcategories)) {
                                foreach ($sub_sub_subcategories as $sub_sub_subcategory_name) {
                                    $existing_sub_sub_sub = Category::where('business_id', $business_id)
                                        ->where('category_type', $category_type)
                                        ->where('parent_id', $sub_subcategory->id)
                                        ->where('name', $sub_sub_subcategory_name)
                                        ->first();

                                    if (empty($existing_sub_sub_sub)) {
                                        Category::create([
                                            'name' => $sub_sub_subcategory_name,
                                            'business_id' => $business_id,
                                            'created_by' => $created_by,
                                            'category_type' => $category_type,
                                            'parent_id' => $sub_subcategory->id,
                                            'created_at' => Carbon::now(),
                                            'updated_at' => Carbon::now(),
                                        ]);
                                        $added_subcategories++;
                                    } else {
                                        $skipped++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($added_categories > 0 || $added_subcategories > 0) {
            return back()->with(
                'success',
                "Imported {$added_categories} categories and {$added_subcategories} subcategories. {$skipped} duplicates/empty rows skipped."
            );
        }

        return back()->with('error', 'No new categories added. All were duplicates or empty.');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && ! auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

        $parent_categories = Category::forDropdownWithParents($business_id, $category_type);

        return view('taxonomy.create')
                    ->with(compact('parent_categories', 'module_category_data', 'category_type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $category_type = request()->input('category_type');
        if ($category_type == 'product' && ! auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'short_code', 'category_type', 'description']);
            if (! empty($request->input('add_as_sub_cat')) && $request->input('add_as_sub_cat') == 1 && ! empty($request->input('parent_id'))) {
                $input['parent_id'] = $request->input('parent_id');
            } else {
                $input['parent_id'] = 0;
            }
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['created_by'] = $request->session()->get('user.id');

            if ($request->hasFile('logo')) {
                $fileName = time() . '_' . $request->file('logo')->getClientOriginalName();
                $filePath = "categories/logos/{$fileName}";
                Storage::disk('public')->putFileAs('categories/logos', $request->file('logo'), $fileName);
                $input['logo'] = $filePath;
            }

            $category = Category::create($input);
            $output = ['success' => true,
                'data' => $category,
                'msg' => __('category.added_success'),
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
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category_type = request()->get('type');
        if ($category_type == 'product' && ! auth()->user()->can('category.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $category = Category::where('business_id', $business_id)->find($id);

            $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

            $parent_categories = Category::forDropdownWithParents($business_id, $category_type, $id);
            $is_parent = false;

            if ($category->parent_id == 0) {
                $is_parent = true;
                $selected_parent = null;
            } else {
                $selected_parent = $category->parent_id;
            }

            return view('taxonomy.edit')
                ->with(compact('category', 'parent_categories', 'is_parent', 'selected_parent', 'module_category_data'));
        }
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
        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'description']);
                $business_id = $request->session()->get('user.business_id');

                $category = Category::where('business_id', $business_id)->findOrFail($id);

                if ($category->category_type == 'product' && ! auth()->user()->can('category.update')) {
                    abort(403, 'Unauthorized action.');
                }

                $category->name = $input['name'];
                $category->description = $input['description'];
                $category->short_code = $request->input('short_code');

                if ($request->hasFile('logo')) {
                    $old_logo = $category->logo;
                    $fileName = time() . '_' . $request->file('logo')->getClientOriginalName();
                    $filePath = "categories/logos/{$fileName}";
                    Storage::disk('public')->putFileAs('categories/logos', $request->file('logo'), $fileName);
                    $category->logo = $filePath;
                    if (!empty($old_logo)) {
                        Storage::disk('public')->delete($old_logo);
                    }
                }

                if (! empty($request->input('add_as_sub_cat')) && $request->input('add_as_sub_cat') == 1 && ! empty($request->input('parent_id'))) {
                    $category->parent_id = $request->input('parent_id');
                } else {
                    $category->parent_id = 0;
                }
                $category->save();

                $output = ['success' => true,
                    'msg' => __('category.updated_success'),
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $category = Category::where('business_id', $business_id)->findOrFail($id);

                if ($category->category_type == 'product' && ! auth()->user()->can('category.delete')) {
                    abort(403, 'Unauthorized action.');
                }

                $category->delete();

                $output = ['success' => true,
                    'msg' => __('category.deleted_success'),
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

    public function getCategoriesApi()
    {
        try {
            $api_token = request()->header('API-TOKEN');

            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $categories = Category::catAndSubCategories($api_settings->business_id);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($categories);
    }

    /**
     * get taxonomy index page
     * through ajax
     *
     * @return \Illuminate\Http\Response
     */
    public function getTaxonomyIndexPage(Request $request)
    {
        if (request()->ajax()) {
            $category_type = $request->get('category_type');
            $module_category_data = $this->moduleUtil->getTaxonomyData($category_type);

            return view('taxonomy.ajax_index')
                ->with(compact('module_category_data', 'category_type'));
        }
    }
}
