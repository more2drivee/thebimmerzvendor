<?php

namespace Modules\CarMarket\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Contact;
use App\User;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $table = 'cm_vehicles';

    protected $guarded = ['id'];

    protected $fillable = [
        'business_id',
        'seller_contact_id',
        'created_by',
        'vin_number',
        'plate_number',
        'brand_category_id',
        'repair_device_model_id',
        'make',
        'model_name',
        'year',
        'trim_level',
        'body_type',
        'color',
        'mileage_km',
        'engine_capacity_cc',
        'cylinder_count',
        'fuel_type',
        'transmission',
        'condition',
        'factory_paint',
        'imported_specs',
        'license_type',
        'condition_notes',
        'listing_price',
        'min_price',
        'currency',
        'license_3year_cost',
        'insurance_annual_cost',
        'insurance_rate_pct',
        'location_city',
        'location_area',
        'ownership_costs',
        'latitude',
        'longitude',
        'listing_status',
        'is_premium',
        'is_featured',
        'description',
        'view_count',
        'approved_at',
        'expires_at',
        'sold_at',
        'buyer_contact_id',
        'sold_price',
        'rejection_reason',
    ];

    protected $casts = [
        'factory_paint' => 'boolean',
        'imported_specs' => 'boolean',
        'is_premium' => 'boolean',
        'is_featured' => 'boolean',
        'listing_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'sold_price' => 'decimal:2',
        'license_3year_cost' => 'decimal:2',
        'insurance_annual_cost' => 'decimal:2',
        'insurance_rate_pct' => 'decimal:2',
        'ownership_costs' => 'array',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'sold_at' => 'datetime',
    ];

    // ── Relationships ──

    public function seller()
    {
        return $this->belongsTo(Contact::class, 'seller_contact_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Contact::class, 'buyer_contact_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function media()
    {
        return $this->hasMany(VehicleMedia::class, 'vehicle_id')->orderBy('display_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(VehicleMedia::class, 'vehicle_id')->where('is_primary', true);
    }

    public function inquiries()
    {
        return $this->hasMany(VehicleInquiry::class, 'vehicle_id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'vehicle_id');
    }

    public function reports()
    {
        return $this->hasMany(VehicleReport::class, 'vehicle_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(VehicleAuditLog::class, 'vehicle_id')->latest();
    }

    public function brandCategory()
    {
        return $this->belongsTo(\App\Category::class, 'brand_category_id');
    }

    public function deviceModel()
    {
        return $this->belongsTo(\Modules\Repair\Entities\DeviceModel::class, 'repair_device_model_id');
    }

    // ── Scopes ──

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeActive($query)
    {
        return $query->where('listing_status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('listing_status', 'pending');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePremiumFirst($query)
    {
        return $query->orderByDesc('is_premium')->orderByDesc('is_featured');
    }

    public function scopeForSeller($query, $contactId)
    {
        return $query->where('seller_contact_id', $contactId);
    }

    public function scopeExpired($query)
    {
        return $query->where('listing_status', 'active')
                     ->where('expires_at', '<', now());
    }

    // ── Accessors ──

    protected function getMakeAttribute($value)
    {
        if ($this->brand_category_id && $this->brandCategory) {
            return $this->brandCategory->name;
        }
        return $value ?? '';
    }

    protected function getModelNameAttribute($value)
    {
        if ($this->repair_device_model_id && $this->deviceModel) {
            return $this->deviceModel->name;
        }
        return $value ?? '';
    }

    // ── Helpers ──

    public function getTitle(): string
    {
        $brand = optional($this->brandCategory)->name ?? $this->make ?? '';
        $model = optional($this->deviceModel)->name ?? $this->model_name ?? '';
        return "{$brand} {$model} {$this->year}";
    }

    public function getBrandName(): string
    {
        return optional($this->brandCategory)->name ?? $this->make ?? '';
    }

    public function getModelName(): string
    {
        return optional($this->deviceModel)->name ?? $this->model_name ?? '';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function getSimilarVehicles($limit = 5)
    {
        $priceRange = config('carmarket.similar_price_range_pct', 20);
        $minPrice = $this->listing_price * (1 - $priceRange / 100);
        $maxPrice = $this->listing_price * (1 + $priceRange / 100);

        return static::active()
            ->where('id', '!=', $this->id)
            ->where('business_id', $this->business_id)
            ->where(function ($q) use ($minPrice, $maxPrice) {
                $q->whereBetween('listing_price', [$minPrice, $maxPrice])
                  ->orWhere(function ($q2) {
                      // Match by brand category and model if available
                      if ($this->brand_category_id) {
                          $q2->where('brand_category_id', $this->brand_category_id);
                          if ($this->repair_device_model_id) {
                              $q2->where('repair_device_model_id', $this->repair_device_model_id);
                          }
                      } else {
                          // Fallback to text fields
                          $q2->where('make', $this->make)
                             ->where('model_name', $this->model_name);
                      }
                  });
            })
            ->premiumFirst()
            ->limit($limit)
            ->get();
    }
}
