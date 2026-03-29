<?php

namespace Modules\CarMarket\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Contact;

class VehicleInquiry extends Model
{
    protected $table = 'cm_vehicle_inquiries';

    protected $guarded = ['id'];

    protected $fillable = [
        'business_id',
        'vehicle_id',
        'buyer_contact_id',
        'inquiry_type',
        'status',
        'message',
        'seller_reply',
        'offered_price',
    ];

    protected $casts = [
        'offered_price' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Contact::class, 'buyer_contact_id');
    }

    // ── Scopes ──

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['new', 'contacted', 'negotiating']);
    }
}
