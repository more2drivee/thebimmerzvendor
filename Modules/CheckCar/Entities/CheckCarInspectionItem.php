<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\CheckCar\Entities\CheckCarElementOption;

class CheckCarInspectionItem extends Model
{
    protected $table = 'checkcar_inspection_items';

    protected $guarded = ['id'];

    protected $fillable = [
        'inspection_id',
        'element_id',
        'title',
        'option_ids',
        'images',
        'note',
    ];

    protected $casts = [
        'option_ids' => 'array',
        'images' => 'array',
    ];

    /**
     * Get the inspection this item belongs to
     */
    public function inspection()
    {
        return $this->belongsTo(CarInspection::class, 'inspection_id');
    }

    /**
     * Get the element (question/check item)
     */
    public function element()
    {
        return $this->belongsTo(CheckCarElement::class, 'element_id');
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(CheckCarQuestionCategory::class, 'category_id');
    }

    /**
     * Get the subcategory
     */
    public function subcategory()
    {
        return $this->belongsTo(CheckCarQuestionSubcategory::class, 'subcategory_id');
    }

    /**
     * Get display value - returns selected option labels
     */
    public function getDisplayValue(): string
    {
        $optionIds = $this->option_ids ?? [];
        if (empty($optionIds)) {
            return '-';
        }
        
        $options = CheckCarElementOption::whereIn('id', $optionIds)->get();
        return $options->pluck('label')->implode(', ');
    }

    /**
     * Scope to order by element sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->join('checkcar_elements', 'checkcar_inspection_items.element_id', '=', 'checkcar_elements.id')
            ->orderBy('checkcar_elements.sort_order')
            ->orderBy('checkcar_inspection_items.id');
    }

    /**
     * Scope to filter by category through element relationship
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->whereHas('element', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    /**
     * Scope to filter by subcategory through element relationship
     */
    public function scopeBySubcategory($query, $subcategoryId)
    {
        return $query->whereHas('element', function ($q) use ($subcategoryId) {
            $q->where('subcategory_id', $subcategoryId);
        });
    }
}
