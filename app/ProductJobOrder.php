<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Modules\Repair\Entities\JobSheet;

class ProductJobOrder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_joborder';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the product for this job order
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the job sheet for this product order
     */
    public function jobSheet()
    {
        return $this->belongsTo(JobSheet::class, 'job_order_id');
    }

    /**
     * Get variations for the product
     */
    public function variations()
    {
        return $this->hasMany(Variation::class, 'product_id', 'product_id');
    }
}
