<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;

class EssentialsEmployeeWarning extends Model
{
    protected $guarded = ['id'];

    protected $table = 'essentials_employee_warnings';

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(\App\User::class, 'issued_by');
    }
}
