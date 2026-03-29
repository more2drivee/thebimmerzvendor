<?php

namespace Modules\Connector\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'booking_name' => $this->booking_name,
            'is_callback' => $this->is_callback ? true : false,
            'contact_id' => $this->contact_id,
            'contact_name' => $this->contact_name,
            'location_id' => $this->location_id,
            'location_name' => $this->location_name,
            'booking_start' => $this->booking_start,
            'booking_end' => $this->booking_end,
            'booking_status' => $this->booking_status,
            'booking_note' => $this->booking_note,
            'created_by' => $this->created_by,
            'service_type_id' => $this->service_type_id,
            'services_type' => $this->services_type,
            'device_id' => $this->device_id,
            'car_type' => $this->car_type,
            'car_brand' => $this->car_brand,
            'car_model' => $this->car_model,
            'car_model_id' => $this->car_model_id,
            'car_chassis_number' => $this->car_chassis_number,
            'car_plate_number' => $this->car_plate_number,
            'manufacturing_year' => $this->manufacturing_year,
            'car_color' => $this->car_color,
            'motor_cc' => $this->motor_cc,
        ];
    }
}
