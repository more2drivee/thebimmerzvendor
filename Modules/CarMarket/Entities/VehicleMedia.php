<?php

namespace Modules\CarMarket\Entities;

use Illuminate\Database\Eloquent\Model;

class VehicleMedia extends Model
{
    protected $table = 'cm_vehicle_media';

    protected $guarded = ['id'];

    protected $fillable = [
        'vehicle_id',
        'media_type',
        'file_path',
        'file_name',
        'file_size_kb',
        'display_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function getFilePathAttribute(): string
    {
        return ltrim((string) $this->getRawOriginal('file_path'), '/');
    }

    public function getFullUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
