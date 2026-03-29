<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Business;

class VersionController extends Controller
{
    /**
     * Get app version information
     */
    public function getVersionInfo()
    {
        
        $business = Business::find(1);
        
        $common_settings = !empty($business->common_settings) ? $business->common_settings : [];
        $version_settings = $common_settings['version_settings'] ?? [];
        
        $versionInfo = [
            'latest_version_name' => $version_settings['latest_version_name'] ?? '',
            'force_update' => !empty($version_settings['force_update']),
            'apk_url' => $version_settings['apk_url'] ?? '',
            'message' => $version_settings['message'] ?? ''
        ];

        return response()->json($versionInfo);
    }

    /**
     * Check if update is required
     */
    public function checkUpdate(Request $request)
    {
    
        $business = Business::find(1);
        
        $common_settings = !empty($business->common_settings) ? $business->common_settings : [];
        $version_settings = $common_settings['version_settings'] ?? [];
        
        $currentVersion = $request->input('current_version');

        $response = [
            'update_required' => false,
            'force_update' => false,
            'latest_version' => $version_settings['latest_version_name'] ?? '',
            'apk_url' => $version_settings['apk_url'] ?? '',
            'message' => $version_settings['message'] ?? ''
        ];

        // Version checking logic removed - always return no update required

        return response()->json($response);
    }
}
