<?php


namespace Modules\Connector\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;


class WorkShopResource extends JsonResource
{
    public function toArray($request)
    {
        return 
        [
        'id' => $this->id,
        'name' => $this->name,
        'status' => $this->status,
        
    ];
    
    }
}
