<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;

class CheckCarQuestionCategory extends Model
{
    protected $table = 'checkcar_question_categories';

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'section_key',
        'sort_order',
        'active',
        'created_by',
        'location_id',
    ];

    public function subcategories()
    {
        return $this->hasMany(CheckCarQuestionSubcategory::class, 'category_id');
    }

    /**
     * Get all elements directly under this category
     */
    public function elements()
    {
        return $this->hasMany(CheckCarElement::class, 'category_id');
    }

    /**
     * Scope to get active categories
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to filter by location (including global rows where location_id is null)
     */
    public function scopeForLocation($query, $locationId)
    {
        // If location_id column doesn't exist yet, skip filtering
        if (!\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'location_id')) {
            return $query;
        }

        if (empty($locationId)) {
            return $query;
        }

        return $query->where(function ($q) use ($locationId) {
            $q->whereNull('location_id')
              ->orWhere('location_id', $locationId);
        });
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 9999 ELSE sort_order END')
            ->orderBy('id');
    }

    /**
     * Get full structure: category -> subcategories -> elements
     */
    public function getFullStructure()
    {
        return $this->load([
            'subcategories' => function ($q) {
                $q->where('active', true)->orderBy('sort_order')->orderBy('id');
            },
            'subcategories.elements' => function ($q) {
                $q->where('active', true)->orderBy('sort_order')->orderBy('id');
            },
        ]);
    }
}
