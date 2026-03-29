<?php

namespace App\Utils;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Business;

class SmsUtil
{
    /**
     * Last net balance reported by SMS provider in the current request.
     *
     * @var float|null
     */
    protected static ?float $lastNetBalance = null;

    /**
     * Get last net balance value set during sendEpusheg() in this request.
     */
    public static function getLastNetBalance(): ?float
    {
        return static::$lastNetBalance;
    }

    /**
     * Send SMS via Epusheg bulk API.
     *
     * @param string $mobile Recipient mobile number
     * @param string $message Message content (UTF-8)
     * @return array|bool Array with 'success' and 'balance' on success, false otherwise
     */
    public static function sendEpusheg(string $mobile, string $message)
    {
        try {
            // Reset last balance at the start of each send
            static::$lastNetBalance = null;
            // Determine current business id from session or authenticated user
            $business_id = null;
            if (session()->has('user.business_id')) {
                $business_id = session('user.business_id');
            } elseif (Auth::check()) {
                $business_id = Auth::user()->business_id ?? null;
            }

            if (empty($business_id)) {
                Log::warning('Epusheg SMS: no business id in session or auth', [
                    'mobile' => $mobile,
                ]);
                return false;
            }

            $business = Business::find($business_id);
            if (! $business) {
                Log::warning('Epusheg SMS: business not found', ['business_id' => $business_id]);
                return false;
            }

            $sms_settings = $business->sms_settings;
            if (is_string($sms_settings)) {
                $sms_settings = json_decode($sms_settings, true) ?: [];
            }

            // Ensure required db fields are present
            if (empty($sms_settings) || empty($sms_settings['url'])) {
                Log::warning('Epusheg SMS: sms_settings missing or url empty', [
                    'business_id' => $business_id,
                    'mobile' => $mobile,
                ]);
                return false;
            }

            // Fields we will explicitly use from DB (no env fallback)
            $sms_api_url = $sms_settings['url'];
            $method = isset($sms_settings['request_method']) ? strtoupper($sms_settings['request_method']) : 'GET';

            $to_param = $sms_settings['send_to_param_name'] ?? 'to';
            $msg_param = $sms_settings['msg_param_name'] ?? 'message';

            // For Epusheg provider ensure standard parameter names as the API expects them
            $is_epusheg = stripos($sms_api_url, 'epusheg') !== false;
            if ($is_epusheg) {
                $to_param = 'to';
                $msg_param = 'message';
            }

            // Normalize URL - remove trailing ? or & which may cause provider parsing issues
            $sms_api_url = rtrim($sms_api_url, "?&\t\n\r");

            $from = $sms_settings['from'] ?? "";
            if (!empty($from)) {
                $message = trim($message . ' from ' . $from);
            }

            $params = [];
            $params[$to_param] = $mobile;
            $params[$msg_param] = $message;

            // Add explicit auth keys from DB if present
            if (! empty($sms_settings['username'])) {
                $params['username'] = $sms_settings['username'];
            }
            if (! empty($sms_settings['password'])) {
                $params['password'] = $sms_settings['password'];
            }
            if (! empty($sms_settings['api_key'])) {
                $params['api_key'] = $sms_settings['api_key'];
            }
            if (! empty($sms_settings['from'])) {
                $params['from'] = $sms_settings['from'];
            }

      
            Log::info('Sending SMS');
            

            $response = Http::withoutVerifying()->get($sms_api_url, $params);

       
            if ($response->successful()) {
                $data = $response->json();
                $netBalance = null;

                if (is_array($data)) {
                    $cacheKeyBase = 'sms.' . $business_id . '.';

                    if (array_key_exists('net_balance', $data)) {
                        $netBalance = (float) $data['net_balance'];

                        // Store last balance for this request and log it
                        static::$lastNetBalance = $netBalance;

                        Log::info('Epusheg SMS net balance updated', [
                       
                            'mobile' => $mobile,
                            'net_balance' => $netBalance,
                        ]);
                    }

                    // Keep last raw response cached for debugging/inspection
                    Cache::put($cacheKeyBase . 'last_response', $data, now()->addHours(12));
                }

                return [
                    'success' => true,
                    'balance' => $netBalance,
                ];
            }

            Log::warning('Epusheg SMS failed', [
                'mobile'   => $mobile,
                'status'   => method_exists($response, 'status') ? $response->status() : null,
                'response' => method_exists($response, 'body') ? $response->body() : null,
                'url' => $sms_api_url,
                'params' => $params,
            ]);
        } catch (\Throwable $e) {
            Log::error('Epusheg SMS exception', [
                'mobile' => $mobile,
                'error'  => $e->getMessage(),
            ]);
        }

        return false;
    }
}