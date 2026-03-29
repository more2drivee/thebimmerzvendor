<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\User;
use App\Contact;
use Carbon\Carbon;
use App\Utils\SmsUtil;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Modules\Sms\Entities\SmsLog;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Renderable;

class ContactLoginController extends Controller
{
    /**
     * Log authentication events with comprehensive details
     */
    private function logAuthEvent($event, $mobile = null, $userId = null, $status = 'info', $details = [])
    {
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'event' => $event,
            'mobile' => $mobile ? $this->sanitizeMobileForLog($mobile) : null,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => $details
        ];

        Log::info("AUTH_EVENT: {$event}", $logData);
    }

    /**
     * Sanitize mobile number for logging (mask middle digits)
     */
    private function sanitizeMobileForLog($mobile)
    {
        if (strlen($mobile) > 6) {
            return substr($mobile, 0, 3) . '****' . substr($mobile, -3);
        }
        return '****';
    }

    /**
     * Check and enforce rate limiting for authentication attempts
     */
    private function checkRateLimit($key, $maxAttempts = 5, $decayMinutes = 15)
    {
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
        
            return false;
        }
        
        return true;
    }

    /**
     * Increment rate limit counter
     */
    private function incrementRateLimit($key, $decayMinutes = 15)
    {
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addMinutes($decayMinutes));
        return $attempts;
    }

    /**
     * Clear rate limit counter on successful operation
     */
    private function clearRateLimit($key)
    {
        Cache::forget($key);
    }

    /**
     * Normalize phone number by removing spaces, + signs, country codes like +20, +2
     * Example: +20 109 055 5070 becomes 01090555070
     */
    private function normalizePhoneNumber($phone)
    {
        // Remove all spaces, + signs, and other non-numeric characters except digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove common country codes (20 for Egypt, 2 for some regions)
        $phone = preg_replace('/^(20|2)/', '', $phone);
        
        // Ensure it starts with 0 if it doesn't already
        if (!empty($phone) && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }
        
        return $phone;
    }
    public function saveDataRegister(Request $request)
    {
   
        $normalizedMobile = null;

        try {
    

            $validator = Validator::make($request->all(), [
                'mobile' => 'required|digits_between:10,17',
                'name' => 'required|string|min:2|max:100',
                'password' => 'required|string|min:6|max:255',
                'code' => 'required|string|size:5'
            ]);

            if ($validator->fails()) {
             

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($request->mobile);

            
            // Verify SMS code from cache
            $cacheKey = 'sms_code_' . $normalizedMobile;
            $storedCode = Cache::get($cacheKey);
            
            if (!$storedCode) {
              
                
                return response()->json([
                    'success' => false,
                    'message' => 'SMS code expired or not found. Please request a new code.'
                ], 401);
            }
            
            if ($storedCode !== $request->code) {
             
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid SMS code. Please try again.'
                ], 401);
            }
            
  
            Cache::forget($cacheKey);  // Clear the code after verification
        
            // Rate limiting for registration attempts
            if (!$this->checkRateLimit('registration', $normalizedMobile, 3, 60)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many registration attempts. Please try again later.'
                ], 429);
            }

            $mobileExists = DB::table('contacts')
                ->where('mobile', $normalizedMobile)
                ->exists();

            if ($mobileExists) {
                $contact_id = DB::table('contacts')
                    ->where('mobile', $normalizedMobile)
                    ->value('id');

                // Check if user already exists
                $existingUser = DB::table('users')->where('crm_contact_id', $contact_id)->first();
                if ($existingUser) {
                
                    $this->incrementRateLimit('registration', $normalizedMobile);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'User already exists with this mobile number'
                    ], 409);
                }

          
             
            } else {
                $name_contact = explode(" ", trim($request->name), 2);
                $cat_sourec = DB::table('categories')->where('name', 'web app')->where('category_type', 'source')->select('id')->first();
                
                $contact_id = DB::table('contacts')->insertGetId([
                    "created_at" => now(),
                    "updated_at" => now(),
                    "business_id" => 1,
                    "mobile" => $normalizedMobile,
                    "name" => $request->name,
                    "crm_source" => $cat_sourec ? $cat_sourec->id : null,
                    "first_name" => $name_contact[0],
                    "last_name" => isset($name_contact[1]) ? $name_contact[1] : '',
                    "contact_type" => "individual",
                    "type" => "customer",
                    "created_by" => User::first()->id
                ]);

            
            }

            $name = explode(" ", trim($request->name), 2);

            $usernameBase = $normalizedMobile ?: Str::slug($request->name, '_');
            if (empty($usernameBase)) {
                $usernameBase = 'user';
            }

            $username = $usernameBase;
            $suffix = 1;
            while (User::where('username', $username)->exists()) {
                $username = $usernameBase . '_' . $suffix++;
            }

            $user = User::create([
                "user_type" => "user_customer",
                "first_name" => $name[0],
                "last_name" => isset($name[1]) ? $name[1] : '',
                "username" => $username,
                "password" => Hash::make($request->password),
                "business_id" => \App\Business::query()->value('id') ?? 1,
                "crm_contact_id" => $contact_id,
                "created_at" => now(),
                "updated_at" => now(),
                "status" => "active",
                "allow_login" => 1
            ]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user account'
                ], 500);
            }

            $token = $user->createToken('auth_token')->accessToken;

           

            return response()->json([
                'success' => true,
                'token' => $token,
                'message' => 'Account created successfully'
            ]);

        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the account',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

 

    /**
     * Resend OTP for login flow
     */
    public function resendLoginOtp(Request $request)
    {
        $startTime = microtime(true);
        $normalizedMobile = null;

        try {
       

            $validator = Validator::make($request->all(), [
                'mobile' => 'required|string',
            ]);

            if ($validator->fails()) {
             
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($request->mobile);
            

            // Check rate limiting for resend attempts
            $rateLimitKey = 'resend_login_otp_' . $normalizedMobile;
            if (!$this->checkRateLimit($rateLimitKey, 3, 10)) { // 3 attempts per 10 minutes
           
                return response()->json([
                    'success' => false,
                    'message' => 'Too many resend attempts. Please try again later.'
                ], 429);
            }

            // Send via existing helper (returns JSON with code)
            $response = $this->sendSmsToUsers($normalizedMobile);
            $payload = json_decode($response->getContent(), true);

        

            if (!empty($payload['success'])) {
                $this->incrementRateLimit($rateLimitKey, 10);
                
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
       
                return response()->json([
                    'success' => true,
                    'message' => 'OTP resent successfully',
                    'code' => $payload['code'] ?? ''
                ]);
            }

        

            return response()->json([
                'success' => false,
                'message' => $payload['message'] ?? 'Failed to resend OTP'
            ], 500);
            
        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
         
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resending OTP',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Resend OTP for registration flow
     */
    public function resendRegistrationOtp(Request $request)
    {
        $startTime = microtime(true);
        $normalizedMobile = null;

        try {
         
            $validator = Validator::make($request->all(), [
                'mobile' => 'required|string',
            ]);

            if ($validator->fails()) {
                
                
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($request->mobile);
            
         
            // Check rate limiting for resend attempts
            $rateLimitKey = 'resend_registration_otp_' . $normalizedMobile;
            if (!$this->checkRateLimit($rateLimitKey, 3, 10)) { // 3 attempts per 10 minutes
               
                return response()->json([
                    'success' => false,
                    'message' => 'Too many resend attempts. Please try again later.'
                ], 429);
            }

            // Check if mobile number exists (for existing contact without user)
            $contact = DB::table('contacts')->where('mobile', $normalizedMobile)->first();
            
            if ($contact) {
                // Check if user already exists
                $existingUser = DB::table('users')->where('crm_contact_id', $contact->id)->first();
                if ($existingUser) {
                  
                    return response()->json([
                        'success' => false,
                        'message' => 'User already exists with this mobile number'
                    ], 409);
                }
                
            
            }

            // Send OTP via existing helper (returns JSON with code)
            $response = $this->sendSmsToUsers($normalizedMobile);
            $payload = json_decode($response->getContent(), true);

      

            if (!empty($payload['success'])) {
                $this->incrementRateLimit($rateLimitKey, 10);
                
               
                return response()->json([
                    'success' => true,
                    'message' => 'OTP resent successfully',
                    'code' => $payload['code'] ?? ''
                ]);
            }

    

            return response()->json([
                'success' => false,
                'message' => $payload['message'] ?? 'Failed to resend OTP'
            ], 500);
            
        } catch (\Exception $e) {
            

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resending OTP',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Resend OTP for forgot password flow
     */
    public function resendForgotPasswordOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($request->mobile);

            // Ensure the contact and user exist
            $contact = DB::table('contacts')->where('mobile', $normalizedMobile)->first();
            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not found'
                ], 404);
            }

            $user = DB::table('users')->where('crm_contact_id', $contact->id)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found for this mobile number'
                ], 404);
            }

            // Rate limit: 30 seconds cooldown between resends
            $cooldownKey = 'forgot_password_last_sent_' . $normalizedMobile;
            $lastSent = Cache::get($cooldownKey);
            if ($lastSent && now()->diffInSeconds($lastSent) < 30) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting a new OTP.',
                    'retry_after' => 30 - now()->diffInSeconds($lastSent)
                ], 429);
            }

            // Generate a new OTP and cache it (10 minutes TTL)
            $otp = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $sms_body = $otp . ' رمز إعادة تعيين كلمة المرور ';

            Cache::put('forgot_password_otp_' . $normalizedMobile, $otp, now()->addMinutes(10));
            Cache::put('forgot_password_time_' . $normalizedMobile, now(), now()->addMinutes(10));

            // Ensure business context is available for SMS sending
            if (!session()->has('user.business_id')) {
                session(['user.business_id' => 1]);
            }

            $smsResult = SmsUtil::sendEpusheg($normalizedMobile, $sms_body);
            $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
            
            if ($smsSent) {
                // Log SMS
                SmsLog::create([
                    'contact_id' => null,
                    'transaction_id' => null,
                    'job_sheet_id' => null,
                    'mobile' => $normalizedMobile,
                    'message_content' => $sms_body,
                    'status' => 'sent',
                    'error_message' => null,
                    'provider_balance' => is_array($smsResult) ? $smsResult['balance'] : SmsUtil::getLastNetBalance(),
                    'sent_at' => now(),
                ]);
                
                Cache::put($cooldownKey, now(), now()->addMinutes(10));
                return response()->json([
                    'success' => true,
                    'message' => 'OTP resent successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP - Please check SMS configuration'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resending OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $startTime = microtime(true);
        $normalizedMobile = null;
        $userId = null;

        try {
        
            // Validate the request
            $validator = Validator::make($request->all(), [
                'mobile' => 'required|string',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
             
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($request->mobile);
            
       
            // Check rate limiting for login attempts
            $rateLimitKey = 'login_attempts_' . $normalizedMobile;
            if (!$this->checkRateLimit($rateLimitKey, 20, 15)) {
             
                return response()->json([
                    'success' => false,
                    'error' => 'Too many login attempts. Please try again later.'
                ], 429);
            }

            // Find the contact by mobile
            $contact = DB::table('contacts')->where('mobile', $normalizedMobile)->first();
            
            if (!$contact) {
                $this->incrementRateLimit($rateLimitKey, 15);
              
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid credentials'
                ], 401);
            }

        
            // Find the user by contact ID (exclude soft deleted users)
            $user = User::where('crm_contact_id', $contact->id)->whereNull('deleted_at')->first();
            
            if (!$user) {
                $this->incrementRateLimit($rateLimitKey, 15);
              
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid credentials'
                ], 401);
            }

            $userId = $user->id;
           
            // Check if user account is active
            if ($user->status !== 'active' || !$user->allow_login) {
                $this->incrementRateLimit($rateLimitKey, 15);
              
                
                return response()->json([
                    'success' => false,
                    'error' => 'Account is not active'
                ], 401);
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                $this->incrementRateLimit($rateLimitKey, 15);
             
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid credentials'
                ], 401);
            }

        
            // Generate a new token for the user
            $token = $user->createToken('auth_token')->accessToken;
            
            // Clear rate limiting on successful login
            $this->clearRateLimit($rateLimitKey);
            
          

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $contact->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'mobile' => $normalizedMobile
                ]
            ]);

        } catch (\Exception $e) {
         

            return response()->json([
                'success' => false,
                'error' => 'An error occurred during login',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    public function sendSmsToUsers($number)
    {
        $startTime = microtime(true);
        $normalizedMobile = null;

        try {
            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($number);
            
        
            $body = implode('', array_rand(array_flip(range(0, 9)), 5));
            $sms_body = $body . ' رقم تأكيد تسجيل الدخول ';

        

            // Ensure business context is available for SMS sending
            if (!session()->has('user.business_id')) {
                session(['user.business_id' => 1]);
            }

            $smsResult = SmsUtil::sendEpusheg($normalizedMobile, $sms_body);
            $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
            
            if ($smsSent) {
                // Log SMS
                SmsLog::create([
                    'contact_id' => null,
                    'transaction_id' => null,
                    'job_sheet_id' => null,
                    'mobile' => $normalizedMobile,
                    'message_content' => $sms_body,
                    'status' => 'sent',
                    'error_message' => null,
                    'provider_balance' => is_array($smsResult) ? $smsResult['balance'] : SmsUtil::getLastNetBalance(),
                    'sent_at' => now(),
                ]);
                
                // Store SMS code in cache for 10 minutes
                $cacheKey = 'sms_code_' . $normalizedMobile;
                Cache::put($cacheKey, $body, now()->addMinutes(10));
                
         
           
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully!',
                    'code' => $body
                ]);
            }

      
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS - Please check SMS configuration',
            ], 500);

        } catch (\Exception $e) {
         
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    public function checkPhone(Request $request)
    {
        $startTime = microtime(true);
        $normalizedMobile = null;

        try {
           

            $validator = Validator::make($request->all(), [
                'mobile' => 'required|string|min:10|max:15'
            ]);

            if ($validator->fails()) {
              

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid mobile number format',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Rate limiting for phone check attempts
            $clientIp = $request->ip();
            if (!$this->checkRateLimit('check_phone', $clientIp, 10, 60)) {
                
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.'
                ], 429);
            }

            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($request->mobile);
            
          
            $contact = DB::table('contacts')
                ->where('mobile', $normalizedMobile)
                ->first();

            if ($contact) {
                $user = DB::table('users')->where('crm_contact_id', $contact->id)->first();
                
                if ($user) {
                 
               
                    
                    return response()->json([
                        "success" => true,
                        "data" => [
                            'result' => 'user found',
                            'code' => '',
                            'name' => $contact->name,
                        ]
                    ]);
                }
                
             
            }
            
            
            // Send SMS OTP for new user registration
            $smsJsonResponse = $this->sendSmsToUsers($normalizedMobile);
            
            // Decode JSON response to array
            $smsResponseData = json_decode($smsJsonResponse->getContent(), true);
            
       
            
            return response()->json([
                "success" => true,
                "data" => [
                    'result' => 'user not found',
                    'code' => $smsResponseData['code'] ?? '',
                    'sms_sent' => $smsResponseData['success'] ?? false,
                ]
            ]);

        } catch (\Exception $e) {
        
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking phone number',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Send OTP for forgot password
     */
    public function forgotPassword(Request $request)
    {
        $startTime = microtime(true);
        $normalizedMobile = null;
        $userId = null;

        try {
          

            $validator = Validator::make($request->all(), [
                'mobile' => 'required|string',
            ]);

            if ($validator->fails()) {
             
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($request->mobile);
            
     
            // Check rate limiting for forgot password attempts
            $rateLimitKey = 'forgot_password_attempts_' . $normalizedMobile;
            if (!$this->checkRateLimit($rateLimitKey, 3, 60)) { // 3 attempts per hour
              
                return response()->json([
                    'success' => false,
                    'message' => 'Too many password reset attempts. Please try again later.'
                ], 429);
            }

            // Check if mobile exists in contacts
            $contact = DB::table('contacts')
                ->where('mobile', $normalizedMobile)
                ->first();

            if (!$contact) {
                $this->incrementRateLimit($rateLimitKey, 60);
          
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not found'
                ], 404);
            }

       
            // Check if user exists for this contact
            $user = DB::table('users')
                ->where('crm_contact_id', $contact->id)
                ->first();

            if (!$user) {
                $this->incrementRateLimit($rateLimitKey, 60);
              
                
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found for this mobile number'
                ], 404);
            }

            $userId = $user->id;
          
            // Check cooldown period for OTP requests (30 seconds between requests)
            $cooldownKey = 'forgot_password_last_sent_' . $normalizedMobile;
            $lastSent = Cache::get($cooldownKey);
            if ($lastSent && now()->diffInSeconds($lastSent) < 30) {
             
                
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait before requesting a new OTP.',
                    'retry_after' => 30 - now()->diffInSeconds($lastSent)
                ], 429);
            }

            // Generate OTP
            $otp = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $sms_body = $otp . ' رمز إعادة تعيين كلمة المرور ';

    

            // Store OTP in cache with TTL (API routes are stateless; avoid sessions)
            Cache::put('forgot_password_otp_' . $normalizedMobile, $otp, now()->addMinutes(10));
            Cache::put('forgot_password_time_' . $normalizedMobile, now(), now()->addMinutes(10));
            
            // Ensure business context is available for SMS sending
            if (!session()->has('user.business_id')) {
                session(['user.business_id' => 1]);
            }

            // Send SMS
            $smsResult = SmsUtil::sendEpusheg($normalizedMobile, $sms_body);
            $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
            
            if ($smsSent) {
                // Log SMS
                SmsLog::create([
                    'contact_id' => null,
                    'transaction_id' => null,
                    'job_sheet_id' => null,
                    'mobile' => $normalizedMobile,
                    'message_content' => $sms_body,
                    'status' => 'sent',
                    'error_message' => null,
                    'provider_balance' => is_array($smsResult) ? $smsResult['balance'] : SmsUtil::getLastNetBalance(),
                    'sent_at' => now(),
                ]);
                
                Cache::put($cooldownKey, now(), now()->addMinutes(10));
                
           
                
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your mobile number'
                ]);
            }

       
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP - Please check SMS configuration'
            ], 500);

        } catch (\Exception $e) {
        

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending OTP',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Verify OTP and reset password
     */
    public function resetPassword(Request $request)
    {
        $startTime = microtime(true);
        $normalizedMobile = null;
        $userId = null;

        try {
      
            $validator = Validator::make($request->all(), [
                'mobile' => 'required|string',
                'otp' => 'required|digits:5',
                'new_password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
             
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Normalize the mobile number
            $normalizedMobile = $this->normalizePhoneNumber($request->mobile);
    
            // Check rate limiting for reset password attempts
            $rateLimitKey = 'reset_password_attempts_' . $normalizedMobile;
            if (!$this->checkRateLimit($rateLimitKey, 5, 15)) { // 5 attempts per 15 minutes
         
                return response()->json([
                    'success' => false,
                    'message' => 'Too many password reset attempts. Please try again later.'
                ], 429);
            }

            // Check if OTP exists and is valid (from cache)
            $storedOtp = Cache::get('forgot_password_otp_' . $normalizedMobile);
            $otpTime = Cache::get('forgot_password_time_' . $normalizedMobile);
            
      

            if (!$storedOtp || !$otpTime) {
                $this->incrementRateLimit($rateLimitKey, 15);
             
                return response()->json([
                    'success' => false,
                    'message' => 'OTP not found. Please request a new OTP.'
                ], 400);
            }

            // Check if OTP is expired (10 minutes) - cache already enforces TTL, but keep extra guard
            if (!$otpTime || now()->diffInMinutes($otpTime) > 10) {
                Cache::forget('forgot_password_otp_' . $normalizedMobile);
                Cache::forget('forgot_password_time_' . $normalizedMobile);
                $this->incrementRateLimit($rateLimitKey, 15);
               
                
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new OTP.'
                ], 400);
            }

            // Verify OTP
            if ((string) $storedOtp !== (string) $request->otp) {
                $this->incrementRateLimit($rateLimitKey, 15);
               
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP. Please check and try again.'
                ], 400);
            }

            // Find contact and user
            $contact = DB::table('contacts')
                ->where('mobile', $normalizedMobile)
                ->first();

            if (!$contact) {
               
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile number not found'
                ], 404);
            }

            
            $user = User::where('crm_contact_id', $contact->id)->first();

            if (!$user) {
              
                
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found'
                ], 404);
            }

            $userId = $user->id;
          
            // Check if the new password is different from the current one
            if (Hash::check($request->new_password, $user->password)) {
           
                return response()->json([
                    'success' => false,
                    'message' => 'New password must be different from your current password'
                ], 400);
            }

            // Update password
            $oldPasswordHash = $user->password;
            $user->password = Hash::make($request->new_password);
            $user->updated_at = now();
            $user->save();

        

            // Clear OTP from cache
            Cache::forget('forgot_password_otp_' . $normalizedMobile);
            Cache::forget('forgot_password_time_' . $normalizedMobile);
            
            // Clear rate limiting on successful reset
            $this->clearRateLimit($rateLimitKey);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
          

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (\Exception $e) {
           

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resetting password',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Display a listing of the resource. 
     * @return Renderable
     */
    public function index()
    {
        return view('connector::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('connector::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('connector::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('connector::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Check if the request is from a suspicious source
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        $userAgent = $request->userAgent();
        $ip = $request->ip();
        
        // Check for common bot user agents
        $suspiciousAgents = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget'
        ];
        
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }
        
        // Check for suspicious IP patterns (this is basic, you might want to use a proper service)
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return false; // Allow localhost for development
        }
        
        return false;
    }

    /**
     * Add security headers to response
     */
    private function addSecurityHeaders($response)
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        return $response;
    }

    /**
     * Validate password strength
     */
    private function isPasswordStrong(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'is_strong' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check for common password patterns
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            '123456', 'password', '123456789', '12345678', '12345',
            '1234567', '1234567890', 'qwerty', 'abc123', '111111',
            '123123', 'admin', 'letmein', 'welcome', 'monkey'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * Generate secure OTP
     */
    private function generateSecureOTP(int $length = 6): string
    {
        $characters = '0123456789';
        $otp = '';
        
        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $otp;
    }

    /**
     * Soft delete contact account
     * 
     * @authenticated
     * @response {
     *     "success": true,
     *     "message": "Account deleted successfully"
     * }
     */
    public function softDeleteAccount(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            DB::beginTransaction();
            
            $user = User::where('id', $user->id)->update([
                'deleted_at' => now(),
                'status' => 'inactive',
                'allow_login' => 0
            ]);
      

            User::where('id', $user->id)->update([
                'deleted_at' => now(),
                'status' => 'inactive',
                'allow_login' => 0
            ]);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting account',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }
}