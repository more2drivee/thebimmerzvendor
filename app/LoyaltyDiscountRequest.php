<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoyaltyDiscountRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'points_requested' => 'integer',
        'amount_discounted' => 'decimal:4',
        'order_total' => 'decimal:4',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    public function scopeForContact($query, $contact_id)
    {
        return $query->where('contact_id', $contact_id);
    }
}
