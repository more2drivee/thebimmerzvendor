<?php

namespace Modules\Sms\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Business;
use App\Utils\BusinessUtil;

class SmsSettingsController extends Controller
{
    /**
     * Display the standalone SMS & Email settings page.
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('business.id');
        $sms_settings = [];
        $email_settings = [];
        $mail_drivers = ['smtp' => 'SMTP', 'mail' => 'Mail', 'sendmail' => 'Sendmail', 'ses' => 'SES', 'sparkpost' => 'SparkPost', 'log' => 'Log'];
        $allow_superadmin_email_settings = false;

        if (!empty($business_id)) {
            $business = Business::find($business_id);
            $util = app(BusinessUtil::class);
            $sms_settings = empty($business->sms_settings) ? $util->defaultSmsSettings() : $business->sms_settings;
            $email_settings = $business->email_settings ?? [];
            
            // Check if superadmin email settings are allowed
            if (method_exists($util, 'allowSuperadminEmailSettings')) {
                $allow_superadmin_email_settings = $util->allowSuperadminEmailSettings($business_id);
            }
        }

        // Ensure all keys expected by the Blade partial exist to prevent notices/fatals on production
        $full_defaults = [
            'sms_service' => 'other',
            // Generic/Other gateway
            'url' => '',
            'username' => '',
            'password' => '',
            'api_key' => '',
            'from' => '',
            'send_to_param_name' => 'to',
            'msg_param_name' => 'text',
            'request_method' => 'post',
            // Headers
            'header_1' => '', 'header_val_1' => '',
            'header_2' => '', 'header_val_2' => '',
            'header_3' => '', 'header_val_3' => '',
            // Params up to 10
            'param_1' => '', 'param_val_1' => '',
            'param_2' => '', 'param_val_2' => '',
            'param_3' => '', 'param_val_3' => '',
            'param_4' => '', 'param_val_4' => '',
            'param_5' => '', 'param_val_5' => '',
            'param_6' => '', 'param_val_6' => '',
            'param_7' => '', 'param_val_7' => '',
            'param_8' => '', 'param_val_8' => '',
            'param_9' => '', 'param_val_9' => '',
            'param_10' => '', 'param_val_10' => '',
            // Nexmo
            'nexmo_key' => '',
            'nexmo_secret' => '',
            'nexmo_from' => '',
            // Twilio
            'twilio_sid' => '',
            'twilio_token' => '',
            'twilio_from' => '',
        ];

        $sms_settings = array_merge($full_defaults, is_array($sms_settings) ? $sms_settings : []);

        return view('sms::settings.index', compact('sms_settings', 'email_settings', 'mail_drivers', 'allow_superadmin_email_settings'));
    }

    /**
     * Persist SMS and Email settings and stay on the standalone page.
     */
    public function update(Request $request)
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('business.id');
        if (empty($business_id)) {
            abort(403, 'No active business session.');
        }

        $business = Business::findOrFail($business_id);
        
        // Save SMS settings
        $incoming_sms = (array) $request->input('sms_settings', []);
        $current_sms = is_array($business->sms_settings) ? $business->sms_settings : [];
        $business->sms_settings = array_merge($current_sms, $incoming_sms);
        
        // Save Email settings
        $incoming_email = (array) $request->input('email_settings', []);
        $current_email = is_array($business->email_settings) ? $business->email_settings : [];
        $business->email_settings = array_merge($current_email, $incoming_email);
        
        $business->save();

        return redirect()
            ->route('sms.messages.settings')
            ->with('status', [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ]);
    }
}
