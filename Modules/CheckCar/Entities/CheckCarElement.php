<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;

class CheckCarElement extends Model
{
    protected $table = 'checkcar_elements';

    protected $guarded = ['id'];

    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
        'max_options' => 'integer',
    ];

    protected $fillable = [
        'name',
        'type',
        'category_id',
        'subcategory_id',
        'sort_order',
        'required',
        'max_options',
        'active',
        'created_by',
        'location_id',
    ];

    public function category()
    {
        return $this->belongsTo(CheckCarQuestionCategory::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(CheckCarQuestionSubcategory::class, 'subcategory_id');
    }

    /**
     * Get options for this element
     */
    public function options()
    {
        return $this->hasMany(CheckCarElementOption::class, 'element_id')->ordered();
    }

    /**
     * Get phrase templates for this element (presets)
     */
    public function presets()
    {
        return $this->hasMany(CheckCarPhraseTemplate::class, 'element_id')
            ->whereNotNull('element_id')
            ->whereNotNull('preset_key');
    }

    /**
     * Get presets as key-value pairs from phrase templates
     */
    public function getPresetsArray(): array
    {
        return $this->presets()
            ->pluck('phrase', 'preset_key')
            ->toArray();
    }

    /**
     * Check if element has presets
     */
    public function hasPresets(): bool
    {
        return $this->presets()->count() > 0;
    }

    /**
     * Check if unlimited options can be selected
     */
    public function isUnlimitedOptions(): bool
    {
        return $this->max_options === 0 || $this->max_options === null;
    }

    /**
     * Check if single option only
     */
    public function isSingleOption(): bool
    {
        return $this->max_options === 1;
    }

    /**
     * Scope to get active elements
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
