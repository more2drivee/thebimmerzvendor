<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\User;
use App\System;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Utils\SmsUtil;
use Modules\Sms\Entities\SmsLog;
use Carbon\Carbon;

class SocialAuthController extends Controller
{
    /**
     * POST /auth/social-customer-login
     *
     * Accepts: token, unique_id, email, medium (google|facebook|apple)
     */
    public function socialCustomerLogin(Request $request)
    {
        try {
            // Base validation - email and name are optional for Apple (they're in JWT)
            $validator = Validator::make($request->all(), [
                'access_token'       => 'nullable|string',
                'token'              => 'nullable|string',
                'authorization_code' => 'required_if:medium,apple|string',
                'unique_id'          => 'required|string',
                'email'              => 'nullable|email',
                'name'               => 'nullable|string',
                'medium'             => 'required|string|in:google,facebook,apple',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }

            $medium = $request->medium;

            if ($medium === 'google') {
                return $this->handleGoogleLogin($request);
            }

            if ($medium === 'apple') {
                return $this->handleAppleLogin($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'This login medium is not supported yet.'
            ], 403);

        } catch (\Exception $e) {
            Log::error('SocialAuth: socialCustomerLogin error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during social login',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    // ─── Google ────────────────────────────────────────────────────────

    private function handleGoogleLogin(Request $request)
    {
        // Google token already validated by Flutter client
        // We trust the email/name from the request
        $data = [
            'email' => $request->email,
            'name'  => $request->name ?? explode('@', $request->email)[0],
        ];

        return $this->processSocialUser($request, $data, 'google');
    }

    // ─── Apple ─────────────────────────────────────────────────────────

    private function handleAppleLogin(Request $request)
    {
        // Get Apple settings from database (business_id = 1 for now)
        $appleSettings = $this->getAppleLoginSettings();

        $missingFields = [];
        foreach (['team_id', 'key_id', 'client_id', 'service_file'] as $field) {
            if (empty($appleSettings[$field])) {
                $missingFields[] = $field;
            }
        }

     

        if (!empty($missingFields)) {
            Log::warning('SocialAuth: Apple login is not configured', [
                'business_id'    => $appleSettings['business_id'] ?? null,
                'missing_fields' => $missingFields,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Apple login is not configured'
            ], 403);
        }
        
        // Check if Apple login is enabled
        if (empty($appleSettings['apple_enabled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Apple login is disabled'
            ], 403);
        }

        // Read the .p8 private key
        $keyPath = storage_path('app/public/apple-login/' . $appleSettings['service_file']);
        if (!file_exists($keyPath)) {
            Log::error('SocialAuth: Apple service key file not found', [
                'business_id' => $appleSettings['business_id'] ?? null,
                'key_path'    => $keyPath,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Apple service key file not found'
            ], 500);
        }

        $privateKey = file_get_contents($keyPath);

        // Build client_secret JWT (ES256)
        $header = json_encode([
            'alg' => 'ES256',
            'kid' => $appleSettings['key_id'],
        ]);

        $now = time();
        $claims = json_encode([
            'iss' => $appleSettings['team_id'],
            'iat' => $now,
            'exp' => $now + (86400 * 60), // 60 days
            'aud' => 'https://appleid.apple.com',
            'sub' => $appleSettings['client_id'],
        ]);

        $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Claims = rtrim(strtr(base64_encode($claims), '+/', '-_'), '=');

        $signingInput = $base64Header . '.' . $base64Claims;

        $ecKey = openssl_pkey_get_private($privateKey);
        if (!$ecKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Apple private key'
            ], 500);
        }

        $signature = '';
        openssl_sign($signingInput, $derSignature, $ecKey, OPENSSL_ALGO_SHA256);

        // Convert DER signature to raw R+S (64 bytes) for ES256
        $signature = $this->derToRaw($derSignature);
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $clientSecret = $signingInput . '.' . $base64Signature;

        // Exchange authorization code for tokens
        $redirectUrl = $appleSettings['redirect_url'] ?? null;

   

        $tokenPayload = [
            'grant_type'    => 'authorization_code',
            'code'          => $request->authorization_code,
            'client_id'     => $appleSettings['client_id'],
            'client_secret' => $clientSecret,
        ];

        if (!empty($redirectUrl)) {
            $tokenPayload['redirect_uri'] = $redirectUrl;
        }

        $appleResponse = Http::asForm()->post('https://appleid.apple.com/auth/token', $tokenPayload);

        if ($appleResponse->failed()) {
            Log::error('SocialAuth: Apple token exchange failed', [
                'status' => $appleResponse->status(),
                'body'   => $appleResponse->body(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid Apple authorization code'
            ], 401);
        }

        $tokenData = $appleResponse->json();

        if (empty($tokenData['id_token'])) {
            return response()->json([
                'success' => false,
                'message' => 'Apple did not return an id_token'
            ], 401);
        }

        // Decode id_token (JWT) to get user claims
        $idTokenParts = explode('.', $tokenData['id_token']);
        if (count($idTokenParts) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Apple id_token format'
            ], 401);
        }

        $data = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);

        if (empty($data['email'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve email from Apple'
            ], 401);
        }

        // if (strtolower($request->email) !== strtolower($data['email'])) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'email_does_not_match'
        //     ], 403);
        // }

        // Apple doesn't always return name; use email prefix as fallback
        if (empty($data['name'])) {
            $data['name'] = explode('@', $data['email'])[0];
        }

        return $this->processSocialUser($request, $data, 'apple');
    }

    /**
     * Convert DER-encoded ECDSA signature to raw R+S (64 bytes)
     */
    private function derToRaw($der)
    {
        $pos = 0;
        $pos++; // skip SEQUENCE tag (0x30)
        $pos++; // skip SEQUENCE length

        $pos++; // skip INTEGER tag (0x02) for R
        $rLen = ord($der[$pos]);
        $pos++;
        $r = substr($der, $pos, $rLen);
        $pos += $rLen;

        $pos++; // skip INTEGER tag (0x02) for S
        $sLen = ord($der[$pos]);
        $pos++;
        $s = substr($der, $pos, $sLen);

        // Pad/trim to 32 bytes each
        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    // ─── Shared social user processing ─────────────────────────────────

    /**
     * Common logic for Google/Apple after token validation.
     *
     * Flow:
     * - User exists + has contact with phone → login directly (token + phone_exist:true)
     * - User exists but no phone → return token + phone_exist:false (Flutter calls update-social-mobile)
     * - No user exists → DON'T create anything, return phone_exist:false + email/name
     *   so Flutter collects phone, then calls update-social-mobile to create contact+user
     *
     * IMPORTANT: For Apple, social_id (sub) is the stable identifier.
     * Apple only returns email ONCE on first sign-in, so we must check social_id first.
     */
    private function processSocialUser(Request $request, array $data, string $medium)
    {
        $uniqueId = $request->unique_id;
        $email = $data['email'] ?? null;

        // Step 1: Check by social_id first (most reliable for Apple)
        $user = null;
        if (!empty($uniqueId)) {
            $user = User::where('social_id', $uniqueId)->first();
        }

        // Step 2: Fallback to email if no user found by social_id
        if (!$user && !empty($email)) {
            $user = User::where('email', $email)->first();
        }

        // Step 3: Check for incomplete registration (user exists but no phone/contact)
        if ($user) {
            // Update social_id if missing (user was found by email but doesn't have social_id)
            if (empty($user->social_id) && !empty($uniqueId)) {
                $user->social_id = $uniqueId;
                $user->login_medium = $medium;
                $user->save();
            }

            $contact = DB::table('contacts')->where('id', $user->crm_contact_id)->first();
            $phoneExist = $contact && !empty($contact->mobile);

            // If user has no phone, they need to complete registration
            if (!$phoneExist) {
                // Generate temporary token for the update-social-mobile call
                $token = $user->createToken('auth_token')->accessToken;

                return response()->json([
                    'success'       => true,
                    'status'        => true,
                    'is_new_user'   => false,
                    'phone_exist'   => false,
                    'token'         => $token,
                    'user_id'       => $user->id, // Include user_id for Flutter to use in update-social-mobile
                    'message'       => 'User exists but phone number is missing. Please provide phone number.',
                    'user'          => [
                        'id'       => $contact ? $contact->id : $user->id,
                        'name'     => $user->first_name . ' ' . $user->last_name,
                        'email'    => $user->email,
                        'unique_id' => $uniqueId,
                        'medium'   => $medium,
                    ]
                ]);
            }

            // User exists with phone → login directly
            $token = $user->createToken('auth_token')->accessToken;

            return response()->json([
                'success'      => true,
                'status'       => true,
                'is_new_user'  => false,
                'phone_exist'  => true,
                'token'        => $token,
                'user'         => [
                    'id'    => $contact ? $contact->id : $user->id,
                    'name'  => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone' => $contact->mobile ?? null,
                ]
            ]);
        }

        // No user exists — return data for Flutter to collect phone first
        // Store social_id in cache temporarily to prevent duplicate account creation
        $pendingKey = 'social_pending_' . $uniqueId;
        Cache::put($pendingKey, [
            'email'     => $email,
            'name'      => $data['name'] ?? '',
            'medium'    => $medium,
            'unique_id' => $uniqueId,
            'created_at' => now()->toIso8601String(),
        ], now()->addMinutes(30));

        return response()->json([
            'success'      => true,
            'status'       => true,
            'is_new_user'  => true,
            'phone_exist'  => false,
            'token'        => null,
            'message'      => 'User not found. Please provide phone number to complete registration.',
            'user'         => [
                'name'      => $data['name'] ?? '',
                'email'     => $email,
                'medium'    => $medium,
                'unique_id' => $uniqueId,
            ]
        ]);
    }

    // ─── Registration with social media ────────────────────────────────

    /**
     * POST /auth/registration-with-social-media
     */
    public function registrationWithSocialMedia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'      => 'required|string|min:2|max:100',
                'email'     => 'required|email',
                'phone'     => 'required|string|min:10|max:17',
                'medium'    => 'nullable|string|in:google,facebook,apple',
                'unique_id' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }

            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already registered'
                ], 409);
            }

            $normalizedPhone = $this->normalizePhoneNumber($request->phone);

            // Check if contact exists by phone (same as ContactLoginController)
            $contact = DB::table('contacts')->where('mobile', $normalizedPhone)->first();

            if ($contact) {
                $existingContactUser = User::where('crm_contact_id', $contact->id)->first();
                if ($existingContactUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User already exists with this phone number'
                    ], 409);
                }
                $contact_id = $contact->id;
            } else {
                // Create contact same as ContactLoginController
                $name_contact = explode(" ", trim($request->name), 2);
                $cat_source = DB::table('categories')
                    ->where('name', 'web app')
                    ->where('category_type', 'source')
                    ->select('id')
                    ->first();

                $contact_id = DB::table('contacts')->insertGetId([
                    "created_at"   => now(),
                    "updated_at"   => now(),
                    "business_id"  => 1,
                    "mobile"       => $normalizedPhone,
                    "name"         => $request->name,
                    "email"        => $request->email,
                    "crm_source"   => $cat_source ? $cat_source->id : null,
                    "first_name"   => $name_contact[0],
                    "last_name"    => isset($name_contact[1]) ? $name_contact[1] : '',
                    "contact_type" => "individual",
                    "type"         => "customer",
                    "created_by"   => User::first()->id
                ]);
            }

            $nameParts = explode(" ", trim($request->name), 2);

            $usernameBase = $normalizedPhone ?: Str::slug($request->name, '_');
            if (empty($usernameBase)) {
                $usernameBase = 'user';
            }
            $username = $usernameBase;
            $suffix = 1;
            while (User::where('username', $username)->exists()) {
                $username = $usernameBase . '_' . $suffix++;
            }

            $language = $request->header('X-localization', 'en');
            $temporaryToken = Str::random(40);

            $user = User::create([
                "user_type"         => "user_customer",
                "first_name"        => $nameParts[0],
                "last_name"         => isset($nameParts[1]) ? $nameParts[1] : '',
                "username"          => $username,
                "email"             => $request->email,
                "password"          => Hash::make(Str::random(16)),
                "business_id"       => \App\Business::query()->value('id') ?? 1,
                "crm_contact_id"    => $contact_id,
                "language"          => $language,
                "login_medium"      => $request->medium ?? 'social',
                "social_id"         => $request->unique_id,
                "temporary_token"   => $temporaryToken,
                "email_verified_at" => now(),
                "status"            => "active",
                "allow_login"       => 1,
                "created_at"        => now(),
                "updated_at"        => now(),
            ]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user account'
                ], 500);
            }

            $token = $user->createToken('auth_token')->accessToken;

            $contact = DB::table('contacts')->where('id', $user->crm_contact_id)->first();

            return response()->json([
                'success'      => true,
                'token'        => $token,
                'status'       => true,
                'phone_exist'  => true,
                'message'      => 'Account created successfully',
                'user'         => [
                    'id'    => $contact ? $contact->id : $user->id,
                    'name'  => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('SocialAuth: registrationWithSocialMedia error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    // ─── Existing account check ────────────────────────────────────────

    /**
     * POST /auth/existing-account-check
     */
    public function existingAccountCheck(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'         => 'required|email',
                'user_response' => 'required|in:0,1',
                'medium'        => 'required|string|in:google,facebook,apple',
                'unique_id'     => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($request->user_response == 1) {
                $user->email_verified_at = now();
                $user->login_medium = $request->medium;
                if ($request->unique_id) {
                    $user->social_id = $request->unique_id;
                }
                $user->save();

                $token = $user->createToken('auth_token')->accessToken;

                $contact = DB::table('contacts')->where('id', $user->crm_contact_id)->first();
                $phoneExist = $contact && !empty($contact->mobile);

                return response()->json([
                    'success'      => true,
                    'token'        => $token,
                    'status'       => true,
                    'phone_exist'  => $phoneExist,
                    'message'      => 'Account verified successfully',
                    'user'         => [
                        'id'    => $contact ? $contact->id : $user->id,
                        'name'  => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                    ]
                ]);
            }

            $temporaryToken = Str::random(40);
            $user->temporary_token = $temporaryToken;
            $user->save();

            return response()->json([
                'success'    => false,
                'status'     => false,
                'temp_token' => $temporaryToken,
                'message'    => 'Account not confirmed. You can register a new account.'
            ]);

        } catch (\Exception $e) {
            Log::error('SocialAuth: existingAccountCheck error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    // ─── Phone Verification Flow ───────────────────────────────────────
 
    /**
     * POST /auth/send-phone-verification-otp
     *
     * Step 1: Check if phone is unique, then send OTP to verify ownership
     *
     * Accepts: phone, email (for context)
     * Returns: success + pending_phone (to be used in step 2)
     */
    public function sendPhoneVerificationOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|string|min:10|max:17',
                'email' => 'required|email',
            ]);
 
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }
 
            $normalizedPhone = $this->normalizePhoneNumber($request->phone);
 
            // Check if phone already used by another contact/user
            $existingContact = DB::table('contacts')->where('mobile', $normalizedPhone)->first();
 
            if ($existingContact) {
                // Check if this contact has a different user
                $existingContactUser = User::where('crm_contact_id', $existingContact->id)->first();
 
                // If user exists and it's not the same email, phone is taken
                if ($existingContactUser && $existingContactUser->email !== $request->email) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This phone number is already linked to another account'
                    ], 409);
                }
            }
 
            // Rate limiting for OTP requests
            $rateLimitKey = 'phone_otp_' . $normalizedPhone;
            $attempts = Cache::get($rateLimitKey, 0);
            if ($attempts >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many OTP requests. Please try again later.'
                ], 429);
            }
 
            // Generate 5-digit OTP
            $otp = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $sms_body = $otp . ' رقم تأكيد رقم الهاتف ';
 
            // Ensure business context for SMS
            if (!session()->has('user.business_id')) {
                session(['user.business_id' => 1]);
            }
 
            // Send SMS
            $smsResult = SmsUtil::sendEpusheg($normalizedPhone, $sms_body);
            $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;
 
            if (!$smsSent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS - Please check SMS configuration'
                ], 500);
            }
 
            // Log SMS
            SmsLog::create([
                'contact_id' => null,
                'transaction_id' => null,
                'job_sheet_id' => null,
                'mobile' => $normalizedPhone,
                'message_content' => $sms_body,
                'status' => 'sent',
                'error_message' => null,
                'provider_balance' => is_array($smsResult) ? $smsResult['balance'] : SmsUtil::getLastNetBalance(),
                'sent_at' => now(),
            ]);
 
            // Store OTP and context in cache (10 minutes TTL)
            $verificationKey = 'phone_verification_' . $normalizedPhone;
            Cache::put($verificationKey, [
                'otp' => $otp,
                'email' => $request->email,
                'phone' => $normalizedPhone,
                'created_at' => now()->toIso8601String(),
            ], now()->addMinutes(10));
 
            // Increment rate limit
            Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes(10));
 
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully to your phone number',
                'pending_phone' => $normalizedPhone,
                'expires_in_minutes' => 10,
            ]);
 
        } catch (\Exception $e) {
            Log::error('SocialAuth: sendPhoneVerificationOtp error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
 
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending OTP',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }
 
    /**
     * POST /auth/verify-phone-and-set-mobile
     *
     * Step 2: Verify OTP code, then create/update user with phone
     *
     * Accepts: phone, otp, email, name, medium, unique_id, user_id (optional)
     * Returns: token + user data
     */
    public function verifyPhoneAndSetMobile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone'     => 'required|string|min:10|max:17',
                'otp'       => 'required|string|size:5',
                'email'     => 'required|email',
                'name'      => 'required|string|min:2|max:100',
                'medium'    => 'required|string|in:google,facebook,apple',
                'unique_id' => 'required|string',
                'user_id'   => 'nullable|integer',
            ]);
 
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }
 
            $normalizedPhone = $this->normalizePhoneNumber($request->phone);
 
            // Verify OTP from cache
            $verificationKey = 'phone_verification_' . $normalizedPhone;
            $verificationData = Cache::get($verificationKey);
 
            if (!$verificationData) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP expired or not found. Please request a new OTP.'
                ], 401);
            }
 
            if ($verificationData['otp'] !== $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code. Please try again.'
                ], 401);
            }
 
            // Verify email matches (security check)
            if ($verificationData['email'] !== $request->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email mismatch. Please restart the verification process.'
                ], 403);
            }
 
            // Clear the verification cache (one-time use)
            Cache::forget($verificationKey);
 
            // Now proceed with user creation/update (original updateSocialMobile logic)
            return $this->processVerifiedPhoneUpdate($request, $normalizedPhone);
 
        } catch (\Exception $e) {
            Log::error('SocialAuth: verifyPhoneAndSetMobile error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
 
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }
 
    /**
     * Process user creation/update after phone verification (internal helper)
     */
    private function processVerifiedPhoneUpdate(Request $request, $normalizedPhone)
    {
        // Check if existing user (multiple lookup strategies)
        $user = null;
        
        // 1. Check by user_id (if provided from previous incomplete registration)
        if ($request->user_id) {
            $user = User::withTrashed()->find($request->user_id);
        }
        
        // 2. Check by social_id (unique_id) - most reliable for Apple users
        if (!$user && !empty($request->unique_id)) {
            $user = User::withTrashed()->where('social_id', $request->unique_id)->first();
        }
        
        // 3. Fallback to email
        if (!$user) {
            $user = User::withTrashed()->where('email', $request->email)->first();
        }
 
        // Check if user is soft deleted
        if ($user && $user->trashed()) {
            return response()->json([
                'success'         => false,
                'is_soft_deleted' => true,
                'user_id'         => $user->id,
                'message'         => 'Account has been deleted. Please restore it to continue.',
                'action'          => 'restore_account'
            ], 410);
        }
 
        // Existing user: just needs phone added
        if ($user) {
            $existingContact = DB::table('contacts')->where('mobile', $normalizedPhone)->first();
 
            if ($existingContact) {
                $existingContactUser = User::where('crm_contact_id', $existingContact->id)
                    ->where('id', '!=', $user->id)
                    ->first();
 
                if ($existingContactUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This phone number is already linked to another account'
                    ], 409);
                }
 
                $user->crm_contact_id = $existingContact->id;
                $user->save();
 
                if (empty($existingContact->email) && !empty($user->email)) {
                    DB::table('contacts')->where('id', $existingContact->id)->update([
                        'email'      => $user->email,
                        'updated_at' => now(),
                    ]);
                }
            } else {
                if ($user->crm_contact_id) {
                    DB::table('contacts')->where('id', $user->crm_contact_id)->update([
                        'mobile'     => $normalizedPhone,
                        'updated_at' => now(),
                    ]);
                } else {
                    $contact_id = $this->createContact($request->name, $request->email, $normalizedPhone);
                    $user->crm_contact_id = $contact_id;
                    $user->save();
                }
            }
 
            $token = $user->createToken('auth_token')->accessToken;
            $contact = DB::table('contacts')->where('id', $user->crm_contact_id)->first();
 
            return response()->json([
                'success'     => true,
                'phone_exist' => true,
                'token'       => $token,
                'message'     => 'Phone number updated successfully',
                'user'        => [
                    'id'    => $contact ? $contact->id : $user->id,
                    'name'  => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone' => $normalizedPhone,
                ]
            ]);
        }
 
        // New user: create contact + user
        $existingContact = DB::table('contacts')->where('mobile', $normalizedPhone)->first();
        $contact_id = null;
 
        if ($existingContact) {
            $existingContactUser = User::where('crm_contact_id', $existingContact->id)->first();
            if ($existingContactUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already exists with this phone number'
                ], 409);
            }
            $contact_id = $existingContact->id;
 
            if (empty($existingContact->email)) {
                DB::table('contacts')->where('id', $existingContact->id)->update([
                    'email'      => $request->email,
                    'updated_at' => now(),
                ]);
            }
        } else {
            $contact_id = $this->createContact($request->name, $request->email, $normalizedPhone);
        }
 
        // Generate unique username
        $usernameBase = $normalizedPhone ?: Str::slug($request->name, '_');
        if (empty($usernameBase)) {
            $usernameBase = 'user';
        }
        $username = $usernameBase;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = $usernameBase . '_' . $suffix++;
        }
 
        $nameParts = explode(' ', trim($request->name), 2);
 
        $user = User::create([
            "user_type"         => "user_customer",
            "first_name"        => $nameParts[0],
            "last_name"         => isset($nameParts[1]) ? $nameParts[1] : '',
            "username"          => $username,
            "email"             => $request->email,
            "password"          => Hash::make(Str::random(16)),
            "business_id"       => \App\Business::query()->value('id') ?? 1,
            "crm_contact_id"    => $contact_id,
            "language"          => 'en',
            "login_medium"      => $request->medium,
            "social_id"         => $request->unique_id,
            "email_verified_at" => now(),
            "status"            => "active",
            "allow_login"       => 1,
            "created_at"        => now(),
            "updated_at"        => now(),
        ]);
 
        $token = $user->createToken('auth_token')->accessToken;
        $contact = DB::table('contacts')->where('id', $contact_id)->first();
 
        return response()->json([
            'success'     => true,
            'phone_exist' => true,
            'token'       => $token,
            'message'     => 'Account created successfully',
            'user'        => [
                'id'    => $contact ? $contact->id : $user->id,
                'name'  => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'phone' => $normalizedPhone,
            ]
        ]);
    }
    /**
     * POST /auth/update-social-mobile
     *
     * Two cases:
     * 1) New user (is_new_user was true) → create contact + user with phone, return token
     * 2) Existing user without phone → update/create contact with phone
     *
     * Accepts: email, name, phone, medium, unique_id, user_id (optional, for existing user)
     */
    public function updateSocialMobile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'     => 'required|email',
                'name'      => 'required|string|min:2|max:100',
                'phone'     => 'required|string|min:10|max:17',
                'medium'    => 'required|string|in:google,facebook,apple',
                'unique_id' => 'required|string',
                'user_id'   => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }

            $normalizedPhone = $this->normalizePhoneNumber($request->phone);

            // Check if existing user (multiple lookup strategies)
            $user = null;
            
            // 1. Check by user_id (if provided from previous incomplete registration)
            if ($request->user_id) {
                $user = User::withTrashed()->find($request->user_id);
            }
            
            // 2. Check by social_id (unique_id) - most reliable for Apple users
            if (!$user && !empty($request->unique_id)) {
                $user = User::withTrashed()->where('social_id', $request->unique_id)->first();
            }
            
            // 3. Fallback to email
            if (!$user) {
                $user = User::withTrashed()->where('email', $request->email)->first();
            }

            // ── Check if user is soft deleted ──
            if ($user && $user->trashed()) {
                return response()->json([
                    'success'        => false,
                    'is_soft_deleted' => true,
                    'user_id' => $user->id,
            
                    'message'        => 'Account has been deleted. Please restore it to continue.',
                    'action'         => 'restore_account'
                ], 410);
            }

            // ── Existing user: just needs phone added ──
            if ($user) {
                $existingContact = DB::table('contacts')->where('mobile', $normalizedPhone)->first();

                if ($existingContact) {
                    $existingContactUser = User::where('crm_contact_id', $existingContact->id)
                        ->where('id', '!=', $user->id)
                        ->first();

                    if ($existingContactUser) {
                        // Return existing user info for Flutter to confirm ownership
                        $existingContactData = DB::table('contacts')->where('id', $existingContactUser->crm_contact_id)->first();
                        return response()->json([
                            'success'             => false,
                            'phone_already_linked' => true,
                            'action'              => 'confirm_ownership',
                            'message'             => 'This phone number is already linked to another account. Is this your account?',
                            'existing_user'       => [
                                'id'         => $existingContactUser->id,
                                'contact_id' => $existingContactData ? $existingContactData->id : null,
                                'name'       => $existingContactUser->first_name . ' ' . $existingContactUser->last_name,
                                'email'      => $existingContactUser->email,
                                'phone'      => $existingContactData ? $existingContactData->mobile : null,
                            ],
                            'current_user' => [
                                'id'    => $user->id,
                                'name'  => $user->first_name . ' ' . $user->last_name,
                                'email' => $user->email,
                            ]
                        ], 409);
                    }

                    $user->crm_contact_id = $existingContact->id;
                    $user->save();

                    if (empty($existingContact->email) && !empty($user->email)) {
                        DB::table('contacts')->where('id', $existingContact->id)->update([
                            'email'      => $user->email,
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    if ($user->crm_contact_id) {
                        DB::table('contacts')->where('id', $user->crm_contact_id)->update([
                            'mobile'     => $normalizedPhone,
                            'updated_at' => now(),
                        ]);
                    } else {
                        $contact_id = $this->createContact($request->name, $request->email, $normalizedPhone);
                        $user->crm_contact_id = $contact_id;
                        $user->save();
                    }
                }

                $token = $user->createToken('auth_token')->accessToken;
                $contact = DB::table('contacts')->where('id', $user->crm_contact_id)->first();

                return response()->json([
                    'success'     => true,
                    'phone_exist' => true,
                    'token'       => $token,
                    'message'     => 'Phone number updated successfully',
                    'user'        => [
                        'id'    => $contact ? $contact->id : $user->id,
                        'name'  => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'phone' => $normalizedPhone,
                    ]
                ]);
            }

            // ── New user: create contact + user (same as ContactLoginController) ──

            // Check if phone already used by another contact/user
            $existingContact = DB::table('contacts')->where('mobile', $normalizedPhone)->first();
            $contact_id = null;

            if ($existingContact) {
                $existingContactUser = User::where('crm_contact_id', $existingContact->id)->first();
                if ($existingContactUser) {
                    // Return existing user info for Flutter to confirm ownership
                    return response()->json([
                        'success'              => false,
                        'phone_already_linked'  => true,
                        'action'               => 'confirm_ownership',
                        'message'              => 'This phone number is already linked to another account. Is this your account?',
                        'existing_user'        => [
                            'id'         => $existingContactUser->id,
                            'contact_id' => $existingContact->id,
                            'name'       => $existingContactUser->first_name . ' ' . $existingContactUser->last_name,
                            'email'      => $existingContactUser->email,
                            'phone'      => $existingContact->mobile,
                        ],
                        'pending_social_user' => [
                            'name'      => $request->name,
                            'email'     => $request->email,
                            'medium'    => $request->medium,
                            'unique_id' => $request->unique_id,
                        ]
                    ], 409);
                }
                $contact_id = $existingContact->id;

                // Update contact email if empty
                if (empty($existingContact->email)) {
                    DB::table('contacts')->where('id', $existingContact->id)->update([
                        'email'      => $request->email,
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $contact_id = $this->createContact($request->name, $request->email, $normalizedPhone);
            }

            // Generate unique username (same as ContactLoginController)
            $usernameBase = $normalizedPhone ?: Str::slug($request->name, '_');
            if (empty($usernameBase)) {
                $usernameBase = 'user';
            }
            $username = $usernameBase;
            $suffix = 1;
            while (User::where('username', $username)->exists()) {
                $username = $usernameBase . '_' . $suffix++;
            }

            $nameParts = explode(' ', trim($request->name), 2);

            $user = User::create([
                "user_type"         => "user_customer",
                "first_name"        => $nameParts[0],
                "last_name"         => isset($nameParts[1]) ? $nameParts[1] : '',
                "username"          => $username,
                "email"             => $request->email,
                "password"          => Hash::make(Str::random(16)),
                "business_id"       => \App\Business::query()->value('id') ?? 1,
                "crm_contact_id"    => $contact_id,
                "language"          => 'en',
                "login_medium"      => $request->medium,
                "social_id"         => $request->unique_id,
                "email_verified_at" => now(),
                "status"            => "active",
                "allow_login"       => 1,
                "created_at"        => now(),
                "updated_at"        => now(),
            ]);

            $token = $user->createToken('auth_token')->accessToken;

            $contact = DB::table('contacts')->where('id', $contact_id)->first();

            return response()->json([
                'success'     => true,
                'phone_exist' => true,
                'token'       => $token,
                'message'     => 'Account created successfully',
                'user'        => [
                    'id'    => $contact ? $contact->id : $user->id,
                    'name'  => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone' => $normalizedPhone,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('SocialAuth: updateSocialMobile error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * POST /auth/restore-deleted-account
     *
     * Restore a soft-deleted user account and activate it
     * Accepts: user_id
     */
    public function restoreDeletedAccount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }

            // Find soft-deleted user
            $user = User::withTrashed()->find($request->user_id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!$user->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is not deleted'
                ], 400);
            }

            // Restore the user
            $user->restore();

            // Activate the account
            $user->update([
                'status'      => 'active',
                'allow_login' => 1,
                'deleted_at'  => null,
            ]);

            // Generate new token
            $token = $user->createToken('auth_token')->accessToken;

            $contact = DB::table('contacts')->where('id', $user->crm_contact_id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Account restored successfully',
               
                'user'    => [
                    'id'    => $contact ? $contact->id : $user->id,
                    'name'  => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone' => $contact ? $contact->mobile : null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('SocialAuth: restoreDeletedAccount error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    /**
     * Get Apple login settings from database
     *
     * @return array
     */
    private function getAppleLoginSettings()
    {
        // Get business_id from current user or default to 1
        $business_id = 1;

        // Try to get from authenticated user
        if (auth()->check() && auth()->user()->business_id) {
            $business_id = auth()->user()->business_id;
        }

        // Pull from admin_dashboard_settings table (source of truth)
        $settings = DB::table('admin_dashboard_settings')
            ->where('key', 'social_login_settings_' . $business_id)
            ->value('value');

        $settingsArray = $settings ? json_decode($settings, true) : [];

        // Map database fields to expected format
        return [
            'business_id'   => $business_id,
            'apple_enabled' => $settingsArray['apple_enabled'] ?? 0,
            'client_id'     => $settingsArray['apple_client_id'] ?? null,
            'team_id'       => $settingsArray['apple_team_id'] ?? null,
            'key_id'        => $settingsArray['apple_key_id'] ?? null,
            'redirect_url'  => $settingsArray['apple_redirect_url'] ?? null,
            'service_file'  => $settingsArray['apple_service_file'] ?? null,
        ];
    }

    /**
     * Create a contact record (same pattern as ContactLoginController)
     */
    private function createContact($name, $email, $normalizedPhone)
    {
        $name_contact = explode(" ", trim($name), 2);
        $cat_source = DB::table('categories')
            ->where('name', 'web app')
            ->where('category_type', 'source')
            ->select('id')
            ->first();

        return DB::table('contacts')->insertGetId([
            "created_at"   => now(),
            "updated_at"   => now(),
            "business_id"  => 1,
            "mobile"       => $normalizedPhone,
            "name"         => $name,
            "email"        => $email,
            "crm_source"   => $cat_source ? $cat_source->id : null,
            "first_name"   => $name_contact[0],
            "last_name"    => isset($name_contact[1]) ? $name_contact[1] : '',
            "contact_type" => "individual",
            "type"         => "customer",
            "created_by"   => User::first()->id
        ]);
    }

    /**
     * Normalize phone number (same logic as ContactLoginController)
     */
    private function normalizePhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = preg_replace('/^(20|2)/', '', $phone);
        if (!empty($phone) && $phone[0] !== '0') {
            $phone = '0' . $phone;
        }
        return $phone;
    }

    // ─── Phone Ownership Confirmation Flow ─────────────────────────────

    /**
     * POST /auth/send-ownership-otp
     *
     * Step 1: Send OTP to confirm ownership of a phone number linked to another account
     *
     * Accepts: existing_user_id, phone
     * Returns: success + OTP sent status
     */
    public function sendOwnershipOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'existing_user_id' => 'required|integer',
                'phone'            => 'required|string|min:10|max:17',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }

            $normalizedPhone = $this->normalizePhoneNumber($request->phone);

            // Verify the user exists and has this phone
            $existingUser = User::find($request->existing_user_id);
            if (!$existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $contact = DB::table('contacts')->where('id', $existingUser->crm_contact_id)->first();
            if (!$contact || $contact->mobile !== $normalizedPhone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number does not match this account'
                ], 403);
            }

            // Rate limiting for OTP requests
            $rateLimitKey = 'ownership_otp_' . $normalizedPhone;
            $attempts = Cache::get($rateLimitKey, 0);
            if ($attempts >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many OTP requests. Please try again later.'
                ], 429);
            }

            // Generate 5-digit OTP
            $otp = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $sms_body = $otp . ' رقم تأكيد ملكية الحساب ';

            // Ensure business context for SMS
            if (!session()->has('user.business_id')) {
                session(['user.business_id' => 1]);
            }

            // Send SMS
            $smsResult = SmsUtil::sendEpusheg($normalizedPhone, $sms_body);
            $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;

            if (!$smsSent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS - Please check SMS configuration'
                ], 500);
            }

            // Log SMS
            SmsLog::create([
                'contact_id'      => $contact->id,
                'transaction_id'  => null,
                'job_sheet_id'    => null,
                'mobile'          => $normalizedPhone,
                'message_content' => $sms_body,
                'status'          => 'sent',
                'error_message'   => null,
                'provider_balance'=> is_array($smsResult) ? $smsResult['balance'] : SmsUtil::getLastNetBalance(),
                'sent_at'         => now(),
            ]);

            // Store OTP in cache (10 minutes TTL)
            $verificationKey = 'ownership_verification_' . $normalizedPhone;
            Cache::put($verificationKey, [
                'otp'              => $otp,
                'existing_user_id' => $existingUser->id,
                'phone'            => $normalizedPhone,
                'created_at'       => now()->toIso8601String(),
            ], now()->addMinutes(10));

            // Increment rate limit
            Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes(10));

            return response()->json([
                'success'           => true,
                'message'           => 'OTP sent successfully to your phone number',
                'existing_user_id'  => $existingUser->id,
                'expires_in_minutes'=> 10,
            ]);

        } catch (\Exception $e) {
            Log::error('SocialAuth: sendOwnershipOtp error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending OTP',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * POST /auth/verify-and-merge-accounts
     *
     * Step 2: Verify OTP and merge social account with existing account
     *
     * Accepts: phone, otp, existing_user_id, social_email, social_name, medium, unique_id
     * Returns: token + merged user data
     */
    public function verifyAndMergeAccounts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone'            => 'required|string|min:10|max:17',
                'otp'              => 'required|string|size:5',
                'existing_user_id' => 'required|integer',
                'social_email'     => 'required|email',
                'social_name'      => 'nullable|string',
                'medium'           => 'required|string|in:google,facebook,apple',
                'unique_id'        => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 403);
            }

            $normalizedPhone = $this->normalizePhoneNumber($request->phone);

            // Verify OTP from cache
            $verificationKey = 'ownership_verification_' . $normalizedPhone;
            $verificationData = Cache::get($verificationKey);

            if (!$verificationData) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP expired or not found. Please request a new OTP.'
                ], 401);
            }

            if ($verificationData['otp'] !== $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code. Please try again.'
                ], 401);
            }

            if ($verificationData['existing_user_id'] != $request->existing_user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID mismatch. Please restart the verification process.'
                ], 403);
            }

            // Clear the verification cache (one-time use)
            Cache::forget($verificationKey);

            // Find the existing user
            $existingUser = User::find($request->existing_user_id);
            if (!$existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Merge: Update existing user with social login info
            $existingUser->social_id = $request->unique_id;
            $existingUser->login_medium = $request->medium;
            
            // Update email if the social email is different and user wants to keep it
            if (!empty($request->social_email) && $existingUser->email !== $request->social_email) {
                // Optionally update email or keep both
                // For now, we'll add it to a notes field or just keep the existing one
                // User can change email later in profile
            }
            
            $existingUser->save();

            // Update contact email if empty
            $contact = DB::table('contacts')->where('id', $existingUser->crm_contact_id)->first();
            if ($contact && empty($contact->email) && !empty($request->social_email)) {
                DB::table('contacts')->where('id', $contact->id)->update([
                    'email'      => $request->social_email,
                    'updated_at' => now(),
                ]);
            }

            // Generate token
            $token = $existingUser->createToken('auth_token')->accessToken;

            return response()->json([
                'success'       => true,
                'phone_exist'   => true,
                'token'         => $token,
                'message'       => 'Accounts merged successfully',
                'user'          => [
                    'id'    => $contact ? $contact->id : $existingUser->id,
                    'name'  => $existingUser->first_name . ' ' . $existingUser->last_name,
                    'email' => $existingUser->email,
                    'phone' => $normalizedPhone,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('SocialAuth: verifyAndMergeAccounts error', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }
}
