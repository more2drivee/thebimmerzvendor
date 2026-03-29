<?php

namespace Modules\CheckCar\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrivacyPolicy extends Model
{
    use HasFactory;

    protected $table = 'privacy_policies';

    protected $guarded = ['id'];

    protected $fillable = [
        'business_id',
        'content',
    ];

    /**
     * Get the business that owns the privacy policy.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Get privacy policy for a business
     *
     * @param int $business_id
     * @return PrivacyPolicy|null
     */
    public static function forBusiness($business_id)
    {
        return static::where('business_id', $business_id)->first();
    }
}
