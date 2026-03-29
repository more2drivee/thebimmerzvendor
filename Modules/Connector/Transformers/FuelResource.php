<?php


namespace Modules\Connector\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class FuelResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
      
        ];
    }
}