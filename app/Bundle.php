<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    protected $table = 'bundles';

    protected $fillable = [
        'reference_no',
        'device_id',
        'repair_device_model_id',
        'manufacturing_year',
        'side_type',
        'price',
        'has_parts_left',
        'description',
        'notes',
        'location_id',
        'created_by',
        'updated_by',
    ];

    public function device()
    {
        return $this->belongsTo(\App\Category::class, 'device_id');
    }

    public function repairDeviceModel()
    {
        return $this->belongsTo(\Modules\Repair\Entities\DeviceModel::class, 'repair_device_model_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\User::class, 'updated_by');
    }
}
