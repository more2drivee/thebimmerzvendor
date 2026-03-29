<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;

class CheckCarElementOption extends Model
{
    protected $table = 'checkcar_element_options';

    protected $guarded = ['id'];

    protected $fillable = [
        'element_id',
        'label',
        'sort_order',
        'location_id',
    ];

    /**
     * Get the element this option belongs to
     */
    public function element()
    {
        return $this->belongsTo(CheckCarElement::class, 'element_id');
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
