<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Product;

class CheckCarServiceSetting extends Model
{
    protected $table = 'checkcar_service_settings';

    protected $fillable = [
        'business_id',
        'product_id',
        'type',
        'value',
        'watermark_image',
        'created_by',
    ];

    protected $casts = [
        'business_id' => 'integer',
        'product_id' => 'integer',
        'type' => 'string',
        'value' => 'string',
        'watermark_image' => 'string',
        'created_by' => 'integer',
    ];

    /**
     * Relationship: selected product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Scope: Get setting for specific business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('checkcar_service_settings.business_id', $businessId);
    }
}
