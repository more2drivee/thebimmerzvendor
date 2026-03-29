<?php

namespace Modules\VinManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class VinNumber extends Model
{
    protected $table = 'vin_numbers';

    protected $fillable = [
        'car_brand',
        'car_model',
        'color',
        'vin_number',
        'year',
        'manufacturer',
        'car_type',
        'transmission',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function groups()
    {
        return $this->belongsToMany(VinGroup::class, 'vin_group_vin_number', 'vin_number_id', 'vin_group_id')
            ->withTimestamps();
    }
}