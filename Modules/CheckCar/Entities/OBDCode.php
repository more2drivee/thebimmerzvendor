<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;

class OBDCode extends Model
{
    protected $table = 'obd_codes';
    protected $fillable = ['code', 'description', 'details', 'severity', 'created_by'];
}
