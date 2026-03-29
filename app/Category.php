<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Combines Category and sub-category
     *
     * @param  int  $business_id
     * @return array
     */
    public static function catAndSubCategories($business_id)
    {
        $all_categories = Category::where('business_id', $business_id)
                                ->where('category_type', 'product')
                                ->orderBy('name', 'asc')
                                ->get()
                                ->toArray();

        if (empty($all_categories)) {
            return [];
        }
        $categories = [];
        $sub_categories = [];

        foreach ($all_categories as $category) {
            if ($category['parent_id'] == 0) {
                $categories[] = $category;
            } else {
                $sub_categories[] = $category;
            }
        }

        $sub_cat_by_parent = [];
        if (! empty($sub_categories)) {
            foreach ($sub_categories as $sub_category) {
                if (empty($sub_cat_by_parent[$sub_category['parent_id']])) {
                    $sub_cat_by_parent[$sub_category['parent_id']] = [];
                }

                $sub_cat_by_parent[$sub_category['parent_id']][] = $sub_category;
            }
        }

        foreach ($categories as $key => $value) {
            if (! empty($sub_cat_by_parent[$value['id']])) {
                $categories[$key]['sub_categories'] = $sub_cat_by_parent[$value['id']];
            }
        }

        return $categories;
    }

    /**
     * Category Dropdown
     *
     * @param  int  $business_id
     * @param  string  $type category type
     * @return array
     */
    public static function forDropdown($business_id, $type)
    {
        $categories = Category::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->where('category_type', $type)
                            ->select(DB::raw('IF(short_code IS NOT NULL, CONCAT(name, "-", short_code), name) as name'), 'id')
                            ->orderBy('name', 'asc')
                            ->get();

        $dropdown = $categories->pluck('name', 'id');

        return $dropdown;
    }

    /**
     * Category Dropdown with hierarchy indentation.
     *
     * @param  int  $business_id
     * @param  string  $type category type
     * @param  int|null  $exclude_id
     * @return array
     */
    public static function forDropdownWithParents($business_id, $type, $exclude_id = null)
    {
        $categories = Category::where('business_id', $business_id)
            ->where('category_type', $type)
            ->select(['id', 'name', 'parent_id'])
            ->orderBy('name', 'asc')
            ->get();

        $children = [];
        foreach ($categories as $category) {
            $children[$category->parent_id][] = $category;
        }

        $dropdown = [];
        $addChildren = function ($parent_id, $prefix) use (&$addChildren, &$children, &$dropdown, $exclude_id) {
            foreach ($children[$parent_id] ?? [] as $category) {
                if (! empty($exclude_id) && (int) $exclude_id === (int) $category->id) {
                    continue;
                }
                $dropdown[$category->id] = $prefix . $category->name;
                $addChildren($category->id, $prefix . '--');
            }
        };

        $addChildren(0, '');

        return $dropdown;
    }

    public function sub_categories()
    {
        return $this->hasMany(\App\Category::class, 'parent_id');
    }

    /**
     * Scope a query to only include main categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyParent($query)
    {
        return $query->where('parent_id', 0);
    }
}
