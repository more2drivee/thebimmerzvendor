<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;

class EssentialsEmployeeBonus extends Model
{
    protected $guarded = ['id'];

    protected $table = 'essentials_employee_bonuses';

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
