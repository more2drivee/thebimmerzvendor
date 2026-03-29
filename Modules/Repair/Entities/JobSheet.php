<?php

namespace Modules\Repair\Entities;

use App\Variation;
use App\Restaurant\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobSheet extends Model
{
    use SoftDeletes;
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'checklist' => 'array',
        'parts' => 'array',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'repair_job_sheets';

    /**
     * Return the customer for the project.
     */
    public function customer()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    /**
     * user added job sheet.
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Alias for createdBy relationship
     */
    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Get fuel status for job sheet
     */
    public function fuelStatus()
    {
        return $this->belongsTo(\App\FuelStatus::class, 'fuel_id');
    }

    /**
     * Get workshop for job sheet
     */
    public function workshop()
    {
        return $this->belongsTo(\App\Workshop::class, 'workshop_id');
    }

    /**
     * Get location for job sheet
     */
    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    /**
     * Get contact for job sheet
     */
    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    /**
     * Get service type through booking
     */
    public function serviceType()
    {
        return $this->hasOneThrough(
            \App\TypesOfService::class,
            Booking::class,
            'id', // Foreign key on bookings table
            'id', // Foreign key on types_of_services table
            'booking_id', // Local key on job_sheets table
            'service_type_id' // Local key on bookings table
        );
    }

    /**
     * technecian for job sheet.
     */
    public function technician()
    {
        return $this->belongsTo(\App\User::class, 'service_staff');
    }

    /**
     * status of job sheet.
     */
    public function status()
    {
        return $this->belongsTo('Modules\Repair\Entities\RepairStatus', 'status_id');
    }

    /**
     * get device for job sheet
     */
    public function Device()
    {
        return $this->belongsTo(\App\Category::class, 'device_id');
    }

    /**
     * get Brand for job sheet
     */
    public function Brand()
    {
        return $this->belongsTo(\App\Brands::class, 'brand_id');
    }

    /**
     * get device model for job sheet
     */
    public function deviceModel()
    {
        return $this->belongsTo('Modules\Repair\Entities\DeviceModel', 'device_model_id');
    }

    /**
     * get business location for job sheet
     */
    public function businessLocation()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * Get the invoices for the job sheet
     */
    public function invoices()
    {
        return $this->hasMany(\App\Transaction::class, 'repair_job_sheet_id');
    }

    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    /**
     * Get product job orders for this job sheet
     */
    public function productJobOrders()
    {
        return $this->hasMany(\App\ProductJobOrder::class, 'job_order_id');
    }

    /**
     * Maintenance notes linked to this job sheet.
     */
    public function maintenanceNotes()
    {
        return $this->hasMany(MaintenanceNote::class, 'job_sheet_id');
    }

    // Alias to support eager load name maintenance_notes
    public function maintenance_notes()
    {
        return $this->maintenanceNotes();
    }

    public function getPartsUsed()
    {
        $parts = [];
        if (! empty($this->parts)) {
            $variation_ids = [];
            $job_sheet_parts = $this->parts;

            foreach ($job_sheet_parts as $key => $value) {
                $variation_ids[] = $key;
            }

            $variations = Variation::whereIn('id', $variation_ids)
                                ->with(['product_variation', 'product', 'product.unit'])
                                ->get();

            foreach ($variations as $variation) {
                $parts[$variation->id]['variation_id'] = $variation->id;
                $parts[$variation->id]['variation_name'] = $variation->full_name;
                $parts[$variation->id]['unit'] = $variation->product->unit->short_name;
                $parts[$variation->id]['unit_id'] = $variation->product->unit->id;
                $parts[$variation->id]['quantity'] = $job_sheet_parts[$variation->id]['quantity'];
            }
        }

        return $parts;
    }
}
