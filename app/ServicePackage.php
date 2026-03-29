<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServicePackage extends Model
{
    protected $table = 'service_package';

    protected $guarded = ['id'];

    public function packageProducts()
    {
        return $this->hasMany(PackageProduct::class, 'package_id');
    }
}
