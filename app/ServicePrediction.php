<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServicePrediction extends Model
{
    protected $table = 'service_predictions';

    protected $guarded = ['id'];

    protected $casts = [
        'last_service_date' => 'date',
        'next_expected_date' => 'date',
        'reminder_sent_at' => 'datetime',
        'predicted_services_json' => 'array',
        'predicted_quantity' => 'float',
        'avg_quantity' => 'float',
        'confidence_score' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function device()
    {
        return $this->belongsTo(\Modules\Repair\Entities\ContactDevice::class, 'device_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(\App\Category::class, 'service_category_id');
    }

    public function serviceProduct()
    {
        return $this->belongsTo(\App\Product::class, 'service_product_id');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'due');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeOnTime($query)
    {
        return $query->where('status', 'on_time');
    }
}
