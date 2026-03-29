<?php

namespace App\Restaurant;

use Illuminate\Database\Eloquent\Model;
use Modules\Repair\Entities\ContactDevice;

class JobEstimator extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_estimator';

    public function customer()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function serviceType()
    {
        return $this->belongsTo(\App\TypesOfService::class, 'service_type_id');
    }

    public function device()
    {
        return $this->belongsTo(ContactDevice::class, 'device_id');
    }

    /**
     * Create a new job estimator
     *
     * @param array $input
     * @return JobEstimator
     */
    public static function createEstimator($input)
    {
        $data = [
            'contact_id' => $input['contact_id'],
            'device_id' => $input['device_id'],
            'business_id' => $input['business_id'],
            'location_id' => $input['location_id'],
            'created_by' => $input['created_by'],
            'service_type_id' => $input['service_type_id'] ?? null,
            'vehicle_details' => $input['vehicle_details'] ?? null,
            'payment_details' => $input['payment_details'] ?? null,
            'payment_image_path' => $input['payment_image_path'] ?? null,
            'send_sms' => $input['send_sms'] ?? false,
            'expected_delivery_date' => $input['expected_delivery_date'] ?? null,
            'estimator_status' => $input['estimator_status'] ?? 'pending',
        ];

        return JobEstimator::create($data);
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'pending' => __('lang_v1.pending'),
            'sent' => __('lang_v1.sent'),
            'approved' => __('lang_v1.approved'),
            'rejected' => __('lang_v1.rejected'),
            'converted_to_order' => __('lang_v1.converted_to_order'),
        ];

        return $statuses[$this->estimator_status] ?? $this->estimator_status;
    }

}
