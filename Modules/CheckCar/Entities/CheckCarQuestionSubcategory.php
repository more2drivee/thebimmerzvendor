<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;

class CheckCarQuestionSubcategory extends Model
{
    protected $table = 'checkcar_question_subcategories';

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'sort_order',
        'active',
        'created_by',
        'location_id',
    ];

    public function category()
    {
        return $this->belongsTo(CheckCarQuestionCategory::class, 'category_id');
    }

    /**
     * Get all elements under this subcategory
     */
    public function elements()
    {
        return $this->hasMany(CheckCarElement::class, 'subcategory_id');
    }

    /**
     * Scope to get active subcategories
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
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
}
