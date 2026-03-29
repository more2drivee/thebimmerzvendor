<?php

namespace Modules\Repair\Entities;

use Illuminate\Database\Eloquent\Model;

class ContactDevice extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_device';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public $timestamps = false;

    public function deviceModel()
    {
        return $this->belongsTo(DeviceModel::class, 'models_id');
    }

    public function deviceCategory()
    {
        return $this->belongsTo(\App\Category::class, 'device_id');
    }

    /**
     * Alias for deviceCategory relationship
     */
    public function category()
    {
        return $this->belongsTo(\App\Category::class, 'device_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function brandOriginVariant()
    {
        return $this->belongsTo(\App\BrandOriginVariant::class, 'brand_origin_variant_id');
    }
}
