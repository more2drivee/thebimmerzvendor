<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;

class FlatRateService extends Model
{
    protected $fillable = [
        'business_id',
        'business_location_id',
        'name',
        'price_per_hour',
      
        'is_active',
    ];

    protected $casts = [
        'price_per_hour' => 'decimal:2',
       
        'is_active' => 'boolean',
    ];

    /**
     * Get the business that owns the flat rate service.
     */
    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    /**
     * Get the business location that owns the flat rate service.
     */
    public function businessLocation()
    {
        return $this->belongsTo('App\BusinessLocation');
    }

    /**
     * Scope to get active flat rate services only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get flat rate services for a specific location.
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('business_location_id', $locationId);
    }

    /**
     * Scope to get flat rate services for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
