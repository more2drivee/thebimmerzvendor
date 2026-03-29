<?php

namespace Modules\Connector\Transformers;

use App\Contact;
use Modules\CheckCar\Entities\CarInspection;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowJobSheetResource extends JsonResource
{
    public function toArray($request)
    {
        $booking = $this->booking;
        $buyerContact = null;
        if ($booking && !empty($booking->buyer_contact_id)) {
            $buyerContact = Contact::find($booking->buyer_contact_id);
        }
        $device = $booking->device ?? null;
        $category = $device->category ?? null;
        $deviceModel = $device->deviceModel ?? null;
        $serviceType = $booking->serviceType ?? null;
        $status = $this->status;
        $workshop = $this->workshop;
        $fuel = $this->fuelStatus;
        $brandOrigin = $device->brandOriginVariant ?? null;
        $location = $this->location;

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'location_name' => $location->name ?? null,
            'contact_id' => $booking->contact_id ?? null,
            'buyer_contact_id' => $booking->buyer_contact_id ?? null,
            'buyer_contact' => $buyerContact ? [
                'id' => $buyerContact->id,
                'first_name' => $buyerContact->first_name ?? null,
                'middle_name' => $buyerContact->middle_name ?? null,
                'last_name' => $buyerContact->last_name ?? null,
                'name' => $buyerContact->name ?? null,
            ] : null,
            'customer' => ($booking && $booking->contact) 
                ? [
                    'id' => $booking->contact->id,
                    'name' => $booking->contact->name,
                    'mobile' => $booking->contact->mobile ?? null,
                ]
                : [
                    'id' => $this->contact->id ?? null,
                    'name' => $this->contact->name ?? null,
                    'mobile' => $this->contact->mobile ?? null,
                ],
            'job_sheet_no' => $this->job_sheet_no,
                        'verification_required' => CarInspection::where('job_sheet_id', $this->id)->first()->verification_required ?? true,

            'contact_device_id' => $device->id ?? null,
            'device_id' => $category->id ?? null,
            'device_name' => $category->name ?? null,
            'device_model_id' => $deviceModel->id ?? null,
            'device_model_name' => $deviceModel->name ?? null,
            'manufacturing_year' => $device->manufacturing_year ?? null,
            'brand_origin_variant_id' => $device->brand_origin_variant_id ?? null,
            'brand_origin_name' => $brandOrigin->name ?? null,
            'brand_origin_country' => $brandOrigin->country_of_origin ?? null,
            'checklist' => $this->checklist,
            'is_callback' => $this->is_callback ? true : false,
            'status_id' => $this->status_id,
            'fuel' => $fuel->name ?? null,
            'fuel_id' => $this->fuel_id,
            'currnt_note' => $this->currnt_note ?? null,
            'defects' => $this->defects,
            'product_condition' => $this->product_condition,
            'estimated_cost' => $this->estimated_cost,
            'created_by' => $this->created_by,
            // 'parts' => $this->parts,
            'car_type' => $device->car_type ?? null,
            'plate_number' => $device->plate_number ?? null,
            'chassie_number' => $device->chassis_number ?? null,
            'color' => $device->color ?? null,
            'motor_cc' => $device->motor_cc ?? null,
            'status_color' => $status->color ?? null,
            'entry_date' => $this->entry_date,
            'delivery_date' => $this->delivery_date,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'booking_id' => $this->booking_id,
            'service_type' => $serviceType->name ?? null,
            'service_type_id' => $serviceType->id ?? null,
            'km' => $this->km,
            'booking_notes' => $booking->booking_note ?? null,
            'car_condition' => $this->car_condition,
            'workshop_name' => $workshop->name ?? null,
            'workshop_id' => $workshop->id ?? null,
            'service_staff' => $this->service_staff ?? [],
            'obd_id' => $this->obd_id ?? [],
            'maintenance_notes' => $this->notes ?? [],
            'maintenance_chat' => $this->chat ?? [],
            'spareParts' => $this->spareParts ?? [],
            'device_job_sheets' => $this->device_job_sheets ?? [],
            'tagged_images' => $this->tagged_images ?? [],
            'booking_images' => $this->booking_images ?? [],
            'media_list' => $this->jobSheet_media_list ?? [],
            'media' => $this->jobSheet_media,
        ];
    }
}