<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Business;
use App\System;
use App\Utils\ModuleUtil;
class AdminDashboardController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

  
    public function index()
    {
       
        $isEnvAdmin = session('is_env_admin', false);
        $adminUsername = session('admin_username', null);
        
        if ($isEnvAdmin && $adminUsername) {
            Log::info('Admin Dashboard Loaded - ENV Admin', [
                'username' => $adminUsername
            ]);
        } else if (auth()->check()) {
            Log::info('Admin Dashboard Loaded - DB User', [
                'user' => auth()->user()->username ?? null
            ]);
        } else {
            
            return redirect('/login')->with('error', 'Please login first');
        }

        $business = Business::where('id', 1)->first();
          $common_settings = [];
        if (!empty($business->common_settings)) {
            if (is_string($business->common_settings)) {
                $decoded = json_decode($business->common_settings, true);
                $common_settings = $decoded ?: [];
            } elseif (is_array($business->common_settings)) {
                $common_settings = $business->common_settings;
            }
        }

        $qrcodesettings = $common_settings;
        $modules = $this->moduleUtil->availableModules();
        $enabled_modules = ! empty($business->enabled_modules) ? $business->enabled_modules : [];
        $version_settings = $common_settings['version_settings'] ?? [];
        
        // Fetch OAuth clients for QR code generation
        $urls = DB::table('oauth_clients')->select('id', 'secret', 'redirect')->get();
        
        // Fetch notification and social login settings from admin_dashboard_settings table
        $notification_settings = $this->getNotificationSettings(1);
        $social_login_settings = $this->getSocialLoginSettings(1);
        
        $settings = DB::table('admin_dashboard_settings')->pluck('value', 'key')->toArray();
        return view('admin.dashboard.index', compact('settings', 'qrcodesettings', 'modules', 'enabled_modules', 'version_settings', 'common_settings', 'urls', 'notification_settings', 'social_login_settings'));
    }


    public function saveSettings(Request $request)
    {
        $data = $request->except('_token');

        $business = Business::where('id', 1)->first();
        if (! empty($business)) {
            if ($request->has('enabled_modules')) {
                $business->enabled_modules = $request->input('enabled_modules', []);
            }

            if ($request->has('version_settings')) {
                $common_settings = ! empty($business->common_settings) && is_array($business->common_settings)
                    ? $business->common_settings
                    : [];
                $version_settings = $request->input('version_settings', []);
                $version_settings['force_update'] = ! empty($version_settings['force_update']) ? 1 : 0;
                $common_settings['version_settings'] = $version_settings;
                $business->common_settings = $common_settings;
            }

            // Save scanqrcode settings (common_settings)
            if ($request->has('_scanqrcode_form')) {
                $common_settings = ! empty($business->common_settings) && is_array($business->common_settings)
                    ? $business->common_settings
                    : [];
                foreach ($request->input('common_settings', []) as $key => $value) {
                    $common_settings[$key] = $value;
                }
                $business->common_settings = $common_settings;
            }

            $business->save();
        }

        // Save notification settings to System table
        if ($request->has('_notifications_form')) {
            $notification_settings = $request->input('notification_settings', []);
            System::updateOrCreate(
                ['key' => 'notification_settings_1'],
                ['value' => json_encode($notification_settings)]
            );
            cache()->forget('notification_settings_1');
        }

        // Save social login settings to admin_dashboard_settings table
        if ($request->has('_social_login_form')) {
            $social_login_settings = $request->input('social_login_settings', []);
            
            // Handle Apple .p8 file upload
            if ($request->hasFile('apple_service_file')) {
                $file = $request->file('apple_service_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                
                // Create directory if not exists
                $directory = storage_path('app/public/apple-login');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                // Move file to storage
                $file->move($directory, $filename);
                
                // Store filename in settings
                $social_login_settings['apple_service_file'] = $filename;
            } else {
                // Preserve existing file if no new upload
                $existingSettings = $this->getSocialLoginSettings(1);
                if (!empty($existingSettings['apple_service_file'])) {
                    $social_login_settings['apple_service_file'] = $existingSettings['apple_service_file'];
                }
            }
            
            DB::table('admin_dashboard_settings')->updateOrInsert(
                ['key' => 'social_login_settings_1'],
                [
                    'value' => json_encode($social_login_settings),
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
        }

        unset($data['enabled_modules'], $data['version_settings'], $data['_modules_form'], $data['_version_form'], $data['_scanqrcode_form'], $data['_notifications_form'], $data['_social_login_form'], $data['common_settings'], $data['notification_settings'], $data['social_login_settings'], $data['apple_service_file']);

        foreach ($data as $key => $value) {

            if ($request->hasFile($key)) {
                $file = $request->file($key);

                $path = $file->store('admin-dashboard', 'public');

                $value = $path;
            }

            DB::table('admin_dashboard_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
        }

        return redirect()->back()->with('success', 'تم حفظ الإعدادات بنجاح!');
    }

    /**
     * Get notification settings from database
     *
     * @param int $business_id
     * @return array
     */
    private function getNotificationSettings($business_id)
    {
        $settings = System::where('key', 'notification_settings_' . $business_id)->value('value');

        return $settings ? json_decode($settings, true) : [];
    }

    /**
     * Get social login settings from database
     *
     * @param int $business_id
     * @return array
     */
    private function getSocialLoginSettings($business_id)
    {
        $settings = DB::table('admin_dashboard_settings')
            ->where('key', 'social_login_settings_' . $business_id)
            ->value('value');

        return $settings ? json_decode($settings, true) : [];
    }
}
