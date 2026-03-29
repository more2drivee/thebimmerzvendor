<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'content', 'image', 'blog_date', 'status', 'business_id', 'category_id', 'sub_category_id', 'title_ar', 'content_ar'];

    /**
     * Get the business that owns the blog.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    /**
     * Get the category that owns the blog.
     */
    public function category()
    {
        return $this->belongsTo(\App\Category::class, 'category_id');
    }

    /**
     * Get the sub category that owns the blog.
     */
    public function subCategory()
    {
        return $this->belongsTo(\App\Category::class, 'sub_category_id');
    }
}
