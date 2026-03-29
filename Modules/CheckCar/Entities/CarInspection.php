<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;
use App\BusinessLocation;
use App\User;
use App\Contact;
use App\Restaurant\Booking;
use Modules\Repair\Entities\JobSheet;
use Modules\Repair\Entities\ContactDevice;
use Modules\CheckCar\Entities\CheckCarInspectionDocument;

class CarInspection extends Model
{
    protected $table = 'checkcar_inspections';

    protected $guarded = ['id'];

    protected $fillable = [
        'location_id',
        'created_by',
        'booking_id',
        'job_sheet_id',
        'buyer_contact_id',
        'seller_contact_id',
        'contact_device_id',
        'verification_required',
        'inspection_team',
        'sections',
        'final_summary',
        'overall_rating',
        'status',
        'share_token',
        'policy_approved',
    ];

    protected $casts = [
        'inspection_team' => 'array',
        'sections' => 'array',
        'verification_required' => 'boolean',
        'policy_approved' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    /**
     * Get all inspection items (element responses)
     */
    public function items()
    {
        return $this->hasMany(CheckCarInspectionItem::class, 'inspection_id')->ordered();
    }

    public function documents()
    {
        return $this->hasMany(CheckCarInspectionDocument::class, 'inspection_id');
    }

    /**
     * Get items grouped by category
     */
    public function itemsByCategory()
    {
        return $this->items()
            ->orderBy('category_id')
            ->orderBy('subcategory_id')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category_name');
    }

    /**
     * Get the user who created this inspection
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Buyer contact (customer buying the car)
     */
    public function buyerContact()
    {
        return $this->belongsTo(Contact::class, 'buyer_contact_id');
    }

    /**
     * Seller contact (customer selling the car)
     */
    public function sellerContact()
    {
        return $this->belongsTo(Contact::class, 'seller_contact_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function contactDevice()
    {
        return $this->belongsTo(ContactDevice::class, 'contact_device_id');
    }

    /**
     * Related repair job sheet (if any)
     */
    public function jobSheet()
    {
        return $this->belongsTo(JobSheet::class, 'job_sheet_id');
    }

    /**
     * Scope for draft inspections
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for completed inspections
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Generate a unique share token
     */
    public function generateShareToken(): string
    {
        $token = bin2hex(random_bytes(16));
        $this->update(['share_token' => $token]);
        return $token;
    }

    /**
     * Get the share URL
     */
    public function getShareUrl(): string
    {
        return route('checkcar.inspections.public.show', ['inspection' => $this->id, 'token' => $this->share_token]);
    }
}
