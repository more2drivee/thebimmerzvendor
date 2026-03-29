<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductCompatibility extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_compatibility';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the product that owns the compatibility record.
     */
    public function product()
    {
        return $this->belongsTo(\App\Product::class);
    }

    /**
     * Get the device model associated with this compatibility record.
     */
    public function deviceModel()
    {
        return $this->belongsTo(\Modules\Repair\Entities\DeviceModel::class, 'model_id');
    }

    /**
     * Get the brand category associated with this compatibility record.
     */
    public function brandCategory()
    {
        return $this->belongsTo(\App\Category::class, 'brand_category_id');
    }
}
