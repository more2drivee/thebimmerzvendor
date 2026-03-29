<?php

namespace Modules\VinManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class VinGroup extends Model
{
    protected $table = 'vin_groups';

    protected $fillable = [
        'name',
        'color',
        'text',
        'business_id',
    ];

    public function vinNumbers()
    {
        return $this->belongsToMany(VinNumber::class, 'vin_group_vin_number', 'vin_group_id', 'vin_number_id')
            ->withTimestamps();
    }
}