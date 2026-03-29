<?php

namespace Modules\CarMarket\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Contact;
use App\User;

class VehicleReport extends Model
{
    protected $table = 'cm_vehicle_reports';

    protected $guarded = ['id'];

    protected $fillable = [
        'vehicle_id',
        'reported_by_contact_id',
        'reason',
        'details',
        'status',
        'admin_notes',
        'reviewed_by',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function reporter()
    {
        return $this->belongsTo(Contact::class, 'reported_by_contact_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
