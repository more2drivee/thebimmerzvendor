<?php

namespace Modules\CountryofOrigin;

use Illuminate\Database\Eloquent\Model;
use Modules\Vendors\Entities\VendorsProduct;

class CountryofOrigin extends Model
{
    protected $table = 'Country_of_Origin';
    protected $fillable = ['name'];

    public function vendorProducts()
    {
        return $this->hasMany(VendorsProduct::class, 'country_id', 'id');
    }
}