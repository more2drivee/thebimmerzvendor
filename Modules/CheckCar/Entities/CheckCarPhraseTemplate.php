<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;

class CheckCarPhraseTemplate extends Model
{
    protected $table = 'checkcar_phrase_templates';

    protected $guarded = ['id'];

    protected $fillable = [
        'section_key',
        'phrase',
        'element_id',
        'preset_key',
        'created_by',
        'location_id',
    ];

    /**
     * Get the element this template belongs to (if it's a preset)
     */
    public function element()
    {
        return $this->belongsTo(CheckCarElement::class, 'element_id');
    }

    /**
     * Scope to get only section templates (not presets)
     */
    public function scopeSectionTemplates($query)
    {
        return $query->whereNull('element_id');
    }

    /**
     * Scope to get only element presets
     */
    public function scopePresets($query)
    {
        return $query->whereNotNull('element_id')
                    ->whereNotNull('preset_key');
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
