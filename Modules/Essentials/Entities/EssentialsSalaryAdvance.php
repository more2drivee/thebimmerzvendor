<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;

class EssentialsSalaryAdvance extends Model
{
    protected $guarded = ['id'];

    protected $table = 'essentials_salary_advances';

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
