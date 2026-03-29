<?php

namespace App\Http\Controllers;

use App\Blog;
use App\Category;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BlogController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $blogs = Blog::where('business_id', $business_id)
                ->select(['id', 'title', 'blog_date', 'status', 'image']);

            return Datatables::of($blogs)
                ->editColumn('image', function ($row) {
                    if (!empty($row->image)) {
                        return '<img src="' . asset('storage/' . $row->image) . '" width="50px" height="50px">';
                    }
                    return '';
                })
                ->editColumn('status', function ($row) {
                    if ($row->status == 'published') {
                        return '<span class="label label-success">' . __('blog.published') . '</span>';
                    } else {
                        return '<span class="label label-warning">' . __('blog.draft') . '</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $edit_url = action([\App\Http\Controllers\BlogController::class, 'edit'], $row->id);
                    $delete_url = action([\App\Http\Controllers\BlogController::class, 'destroy'], $row->id);

                    return '<button type="button" class="btn btn-primary btn-xs edit_blog_button" data-href="' . $edit_url . '"><i class="fa fa-edit"></i></button>
                            <button type="button" class="btn btn-danger btn-xs delete_blog_button" data-href="' . $delete_url . '"><i class="fa fa-trash"></i></button>';
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('blog.index');
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $categories = Category::forDropdown($business_id, 'blog');
        $sub_categories = [];

        return view('blog.create')->with(compact('categories', 'sub_categories'));
    }

    public function store(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $input = $request->only(['title', 'content', 'blog_date', 'status', 'category_id', 'sub_category_id', 'title_ar', 'content_ar']);
            $input['business_id'] = $business_id;

            if ($request->hasFile('image')) {
                $fileName = time() . '_' . $request->file('image')->getClientOriginalName();
                $filePath = "blogs/{$fileName}";
                Storage::disk('public')->putFileAs('blogs', $request->file('image'), $fileName);
                $input['image'] = $filePath;
            }

            Blog::create($input);

            $output = [
                'success' => true,
                'msg' => __("blog.added_success")
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $blog = Blog::where('business_id', $business_id)->findOrFail($id);

        $categories = Category::forDropdown($business_id, 'blog');
        $sub_categories = Category::where('business_id', $business_id)
            ->where('category_type', 'blog')
            ->where('parent_id', $blog->category_id)
            ->pluck('name', 'id')
            ->toArray();
        $sub_categories = ['' => 'None'] + $sub_categories;

        return view('blog.edit')->with(compact('blog', 'categories', 'sub_categories'));
    }

    public function update(Request $request, $id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $blog = Blog::where('business_id', $business_id)->findOrFail($id);

            $input = $request->only(['title', 'content', 'blog_date', 'status', 'category_id', 'sub_category_id', 'title_ar', 'content_ar']);

            if ($request->hasFile('image')) {
                $old_image = $blog->image;
                $fileName = time() . '_' . $request->file('image')->getClientOriginalName();
                $filePath = "blogs/{$fileName}";
                Storage::disk('public')->putFileAs('blogs', $request->file('image'), $fileName);
                $input['image'] = $filePath;
                if (!empty($old_image)) {
                    Storage::disk('public')->delete($old_image);
                }
            }

            $blog->update($input);

            $output = [
                'success' => true,
                'msg' => __("blog.updated_success")
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function destroy($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $blog = Blog::where('business_id', $business_id)->findOrFail($id);

            if (!empty($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }

            $blog->delete();

            $output = [
                'success' => true,
                'msg' => __("blog.deleted_success")
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $blog = Blog::where('business_id', $business_id)->findOrFail($id);

        return view('blog.show')->with(compact('blog'));
    }

    public function uploadImage(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = "blogs/content/" . $fileName;

                Storage::disk('public')->putFileAs('blogs/content', $file, $fileName);

                return response()->json([
                    'location' => asset('storage/' . $filePath)
                ]);
            }

            return response()->json(['error' => 'No file uploaded'], 400);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['error' => 'Upload failed'], 500);
        }
    }

    public function createCategory()
    {
        $business_id = request()->session()->get('user.business_id');
        $parent_categories = Category::where('business_id', $business_id)
            ->where('category_type', 'blog')
            ->where('parent_id', 0)
            ->pluck('name', 'id');

        return view('blog.category_create')->with(compact('parent_categories'));
    }

    public function storeCategory(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|integer',
            ]);

            $category = new Category();
            $category->name = $request->input('name');
            $category->short_code = $request->input('short_code');
            $category->description = $request->input('description');
            $category->parent_id = $request->input('parent_id') ?: 0;
            $category->category_type = 'blog';
            $category->business_id = $business_id;
            $category->created_by = auth()->user()->id;
            $category->save();

            return response()->json([
                'success' => true,
                'data' => $category,
                'msg' => __('category.added_success')
            ]);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }
}
