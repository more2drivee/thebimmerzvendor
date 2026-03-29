<?php

namespace Modules\Sms\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Business;

class EPushService
{
    protected $baseUrl = 'https://api.epusheg.com/api/v2';

    /**
     * Get SMS settings from the current business
     */
    protected function getSettings()
    {
        $business_id = null;
        if (session()->has('user.business_id')) {
            $business_id = session('user.business_id');
        } elseif (Auth::check()) {
            $business_id = Auth::user()->business_id ?? null;
        }

        if (!$business_id) {
            return null;
        }

        $business = Business::find($business_id);
        if (!$business) {
            return null;
        }

        $sms_settings = $business->sms_settings;
        if (is_string($sms_settings)) {
            $sms_settings = json_decode($sms_settings, true) ?: [];
        }

        return $sms_settings;
    }

    /**
     * Send SMS message
     *
     * @param string $mobile
     * @param string $message
     * @return array
     */
    public function send($mobile, $message)
    {
        $settings = $this->getSettings();
        if (!$settings) {
            return [
                'success' => false,
                'message' => 'SMS settings not found',
            ];
        }

        try {
            // Check if it is E-Push URL
            $url = $settings['url'] ?? '';
            if (stripos($url, 'epusheg') === false) {
                // Fallback or error if not configured for E-Push but using this service
                // For now, we assume if this service is used, we want to use E-Push logic
                // or we use the credentials from the settings if they match the E-Push structure
            }
            
            $response = Http::withoutVerifying()->get("{$this->baseUrl}/send_bulk", [
                'username' => $settings['username'] ?? '',
                'password' => $settings['password'] ?? '',
                'api_key' => $settings['api_key'] ?? '',
                'message' => $message,
                'from' => $settings['from'] ?? '',
                'to' => $mobile,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('E-Push SMS Error: ' . $response->body());
            return [
                'success' => false,
                'message' => 'Failed to send SMS',
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('E-Push SMS Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception sending SMS',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get account balance from latest SMS log entry
     *
     * @return float
     */
    public function getBalance()
    {
        // Always get balance from latest log entry, not from cache
        $latestBalance = \Modules\Sms\Entities\SmsLog::whereNotNull('provider_balance')
            ->orderByDesc('id')
            ->value('provider_balance');

        if ($latestBalance !== null) {
            return (float) $latestBalance;
        }

        // Fallback to API call if no log entry exists
        return $this->fetchBalanceFromAPI();
    }

    /**
     * Fetch balance directly from API
     *
     * @return float
     */
    public function fetchBalanceFromAPI()
    {
        $settings = $this->getSettings();
        if (!$settings) {
            return 0.00;
        }

        try {
            $response = Http::withoutVerifying()->get("{$this->baseUrl}/balance", [
                'username' => $settings['username'] ?? '',
                'password' => $settings['password'] ?? '',
                'api_key' => $settings['api_key'] ?? '',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $balance = $data['balance'] ?? $data['net_balance'] ?? 0.00;
                return (float) $balance;
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return 0.00;
    }
}
