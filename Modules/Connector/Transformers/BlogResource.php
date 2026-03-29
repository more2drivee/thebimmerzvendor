<?php

namespace Modules\Connector\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_ar' => $this->title_ar,
            'content' => strip_tags($this->content),
            'content_ar' => strip_tags($this->content_ar),
            'image' => $this->image,
            'image_url' => !empty($this->image) ? asset('storage/' . $this->image) : null,
            'blog_date' => $this->blog_date,
            'status' => $this->status,
            'business_id' => $this->business_id,
            'category_id' => $this->category_id,
            'category' => !empty($this->category) ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'sub_category_id' => $this->sub_category_id,
            'sub_category' => !empty($this->subCategory) ? [
                'id' => $this->subCategory->id,
                'name' => $this->subCategory->name,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
