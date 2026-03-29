<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Restaurant\JobEstimator;
use Modules\Repair\Entities\ContactDevice;

class MaintenanceNote extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'maintenance_note';

    /**
     * Get the job sheet associated with the maintenance note.
     */
    public function jobSheet()
    {
        return $this->belongsTo(JobSheet::class, 'job_sheet_id');
    }

    /**
     * Get the user who created the note.
     */
    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Get the repair status associated with the note title.
     */
    public function repairStatus()
    {
        return $this->belongsTo(RepairStatus::class, 'title');
    }

    /**
     * Get the job estimator associated with the maintenance note.
     */
    public function jobEstimator()
    {
        return $this->belongsTo(JobEstimator::class, 'job_estimator_id');
    }

    public function contactDevice()
    {
        return $this->belongsTo(ContactDevice::class, 'contact_device_id');
    }
}
