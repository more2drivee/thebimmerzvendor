<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    protected $table = 'workshops';

    protected $guarded = ['id'];

    protected $casts = [
        'business_location_id' => 'integer',
        'business_id' => 'integer',
    ];

    public function jobSheets()
    {
        return $this->belongsToMany(JobSheet::class, 'job_sheet_workshop', 'workshop_id', 'job_sheet_id')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function technicians()
    {
        return $this->belongsToMany(
            \App\User::class,
            'workshop_technician_attendance',
            'workshop_id',
            'user_id'
        )->withPivot(['attendance_id', 'joined_at', 'left_at', 'notes'])
         ->withTimestamps();
    }
}
