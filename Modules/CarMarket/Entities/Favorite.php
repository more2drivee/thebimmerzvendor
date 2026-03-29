<?php

namespace Modules\CarMarket\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Contact;

class Favorite extends Model
{
    protected $table = 'cm_favorites';

    protected $guarded = ['id'];

    protected $fillable = [
        'contact_id',
        'vehicle_id',
        'notify_price_change',
    ];

    protected $casts = [
        'notify_price_change' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
