<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GenericSparePart extends Model
{
    protected $table = 'generic_spare_parts';

    protected $fillable = [
        'name',
        'description',
        'type',
        'business_id',
        'created_by',
        'updated_by',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
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
