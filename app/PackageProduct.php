<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PackageProduct extends Model
{
    protected $table = 'package_product';

    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }
}
