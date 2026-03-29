<?php

namespace Modules\CarMarket\Entities;

use Illuminate\Database\Eloquent\Model;

class VehicleAuditLog extends Model
{
    protected $table = 'cm_vehicle_audit_logs';

    protected $guarded = ['id'];

    protected $fillable = [
        'business_id',
        'vehicle_id',
        'changed_by_user_id',
        'changed_by_contact_id',
        'change_source',
        'action',
        'changed_fields',
        'old_values',
        'new_values',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function changedByUser()
    {
        return $this->belongsTo(\App\User::class, 'changed_by_user_id');
    }

    public function changedByContact()
    {
        return $this->belongsTo(\App\Contact::class, 'changed_by_contact_id');
    }
}
