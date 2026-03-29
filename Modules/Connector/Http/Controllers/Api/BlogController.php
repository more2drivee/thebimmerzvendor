<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Connector\Transformers\BlogResource;

/**
 * @group Blog management
 * @authenticated
 *
 * APIs for managing blogs
 */
class BlogController extends ApiController
{
    /**
     * List blogs
     */
    public function index()
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $query = Blog::with(['category', 'subCategory'])->where('business_id', $business_id);

        // Search by name (title or title_ar)
        if (!empty(request()->input('name'))) {
            $searchTerm = request()->input('name');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('title_ar', 'like', '%' . $searchTerm . '%');
            });
        }

        // Search by category (category_id or category name)
        if (!empty(request()->input('category'))) {
            $category = request()->input('category');
            $query->where(function ($q) use ($category) {
                $q->where('category_id', $category)
                  ->orWhereHas('category', function ($q) use ($category) {
                      $q->where('name', 'like', '%' . $category . '%');
                  });
            });
        }

        if (!empty(request()->input('title'))) {
            $query->where('title', 'like', '%' . request()->input('title') . '%');
        }

        if (!empty(request()->input('status'))) {
            $query->where('status', request()->input('status'));
        }

        $blogs = $query->paginate($this->perPage);

        return BlogResource::collection($blogs);
    }

    /**
     * Get specified blog
     */
    public function show($id)
    {
        $user = Auth::user();
        $business_id = $user->business_id;

        $blog = Blog::where('business_id', $business_id)->findOrFail($id);

        return new BlogResource($blog);
    }
}
