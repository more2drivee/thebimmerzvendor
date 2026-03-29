<?php


namespace Modules\Connector\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceStaffResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'service_staffs' => $this->resource, // Since it's a list, this will automatically be an array of objects.
        ];
    }
}