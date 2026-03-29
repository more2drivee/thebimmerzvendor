<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Return the value of the property
     *
     * @param $key string
     * @return mixed
     */
    public static function getProperty($key)
    {
        $allVersions = self::getAllVersions();
        return $allVersions[$key] ?? null;
    }

    /**
     * Get all module versions in a single cached query
     *
     * @return array
     */
    public static function getAllVersions()
    {
        return cache()->remember('system_all_versions', 86400, function () {
            return System::whereIn('key', [
                'accounting_version',
                'artificialintelligence_version',
                'connector_version',
                'crm_version',
                'essentials_version',
                'project_version',
                'repair_version',
                'spreadsheet_version',
                'survey_version',
                'timemanagement_version',
                'treasury_version',
                'vinmanagement_version',
            ])->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Return the value of the multiple properties
     *
     * @param $keys array
     * @return array
     */
    public static function getProperties($keys, $pluck = false)
    {
        $cacheKey = 'system_properties_' . md5(json_encode($keys));
        
        return cache()->remember($cacheKey, 86400, function () use ($keys, $pluck) {
            if ($pluck == true) {
                return System::whereIn('key', $keys)
                    ->pluck('value', 'key');
            } else {
                return System::whereIn('key', $keys)
                    ->get()
                    ->toArray();
            }
        });
    }

    /**
     * Return the system default currency details
     *
     * @param void
     * @return object
     */
    public static function getCurrency()
    {
        $c_id = System::where('key', 'app_currency_id')
                ->first()
                ->value;

        $currency = Currency::find($c_id);

        return $currency;
    }

    /**
     * Set the property
     *
     * @param $key
     * @param $value
     * @return void
     */
    public static function setProperty($key, $value)
    {
        System::where('key', $key)
            ->update(['value' => $value]);
    }

    /**
     * Remove the specified property
     *
     * @param $key
     * @return void
     */
    public static function removeProperty($key)
    {
        System::where('key', $key)
            ->delete();
    }

    /**
     * Add a new property, if exist update the value
     *
     * @param $key
     * @param $value
     * @return void
     */
    public static function addProperty($key, $value)
    {
        System::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
