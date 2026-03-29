<?php


namespace Modules\Connector\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;


class JobSheetResource extends JsonResource
{
    public function toArray($request)
    {
        $booking = $this->booking;
        $device = $booking->device ?? null;
        $category = $device->category ?? null;
        $deviceModel = $device->deviceModel ?? null;
        $serviceType = $booking->serviceType ?? null;
        $status = $this->status;
        $workshop = $this->workshop;

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'contact_id' => $this->contact_id,
            'customer' => $booking && $booking->contact ? $booking->contact->name : ($this->contact->name ?? null),
            'device_id' => $category->id ?? null,
            'device_name' => $category->name ?? null,
            'device_model_id' => $deviceModel->id ?? null,
            'device_model_name' => $deviceModel->name ?? null,
            'manufacturing_year' => $device->manufacturing_year ?? null,
            'chassie_number' => $device->chassis_number ?? null,
            'is_callback' => $this->is_callback ? true : false,
            'status_id' => $this->status_id,
            'status_name' => $status->name ?? null,
            'status_color' => $status->color ?? null,
            'service_type' => $serviceType->name ?? null,
            'service_type_id' => $serviceType->id ?? null,
            'is_inspection_service' => $serviceType->is_inspection_service ?? false,
            'car_type'=> $device->car_type ?? null,
            'plate_number' => $device->plate_number ?? null,
            'color' => $device->color ?? null,
            'job_sheet_no' => $this->job_sheet_no,
            'entry_date' => $booking->booking_start ?? null,
            'delivery_date' => $this->delivery_date,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'booking_id' => $this->booking_id,
            'workshop' => $workshop->name ?? null,
            'workshop_id' => $workshop->id ?? null,
            'service_staff' => $this->service_staff,
        ];
        
    }
}
