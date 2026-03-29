<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Rules\ReCaptcha;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // 🔴 الفحص الأول: هل هذا أدمن من الـ ENV؟
        $username = $request->input($this->username());
        $admins = explode(',', env('ADMINISTRATOR_USERNAMES', ''));
        $admins = array_map('trim', $admins);

        if (in_array(trim($username), $admins)) {
            Log::info('Admin user attempting login from ENV', ['username' => $username]);
            
            // ✅ سجل جلسة الأدمن من الـ ENV
            session(['admin_username' => $username, 'is_env_admin' => true]);
            
            return redirect('/admin/dashboard');
        }

        // 🔵 إذا لم يكن أدمن من الـ ENV، تابع العملية الطبيعية (البحث في الدatابيس)
        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

   public function showLoginForm()
{
   $settings = DB::table('admin_dashboard_settings')
    ->where('key', 'like', 'login_%')
    ->orWhere('key', 'carserv_logo')
    ->pluck('value', 'key')
    ->toArray();

    return view('auth.login', compact('settings'));
}

    public function showRegisterForm()
    {
        return view('auth.contact_register');
    }

    public function storeAccount(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'regex:/^\S+\s+\S+$/'],
            ], [
                'name.regex' => 'Enter Full Name',
            ]);

            $mobileExists = DB::table('contacts')
                ->where('mobile', $request->mobile)
                ->exists();
            
                if($mobileExists)
                {
                    return redirect()->back()->withErrors(['error' => 'Registration failed: This mobile number is already registered']);
                }

            $contact_id = DB::table('contacts')->insertGetId([
                "created_at" => now(),
                "updated_at" => now(),
                "business_id" => 1,
                "mobile" => $request->mobile,
                "name" => $request->name, 
                "contact_type" => "individual",
                "type" => "customer",
                "created_by" => 1
            ]);

            $name = explode(" ", $request->name);

            DB::table('users')->insert([
                "user_type" => "user_customer",
                "surname" => $request->surname,
                "first_name" => $name[0],
                "last_name" => $name[1],
                "username" => $request->mobile,
                "password" => Hash::make($request->password),
                "business_id" => 1,
                "crm_contact_id" => $contact_id,
                "created_at" => now(),
                "updated_at" => now(),
                "status" => "active",
                "allow_login" => 1
            ]);

            // return response()->json([
            //     'message' => 'Contact created successfully'
            // ], 201);
            return redirect('/login');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }

    public function logout()
    {
        $this->businessUtil->activityLog(auth()->user(), 'logout');

        request()->session()->flush();
        \Auth::logout();

        return redirect('/login');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
protected function authenticated(Request $request, $user)
{
    $this->businessUtil->activityLog($user, 'login', null, [], false, $user->business_id);

    if (! $user->business->is_active) {
        \Auth::logout();
        return redirect('/login')
            ->with('status', ['success' => 0, 'msg' => __('lang_v1.business_inactive')]);
    } elseif ($user->status != 'active') {
        \Auth::logout();
        return redirect('/login')
            ->with('status', ['success' => 0, 'msg' => __('lang_v1.user_inactive')]);
    } elseif (! $user->allow_login) {
        \Auth::logout();
        return redirect('/login')
            ->with('status', ['success' => 0, 'msg' => __('lang_v1.login_not_allowed')]);
    } elseif (($user->user_type == 'user_customer') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'crm_module')) {
        \Auth::logout();
        return redirect('/login')
            ->with('status', ['success' => 0, 'msg' => __('lang_v1.business_dont_have_crm_subscription')]);
    }

    return redirect('/home');
}

    protected function redirectTo()
    {
        // $user = \Auth::user();
        // if (! $user->can('dashboard.data') && $user->can('sell.create')) {
        //     return '/pos/create';
        // }

        // if ($user->user_type == 'user_customer') {
        //     return 'contact/add-booking';
        //     // return 'contact/contact-dashboard';
        // }

        return '/home_page';
    }

    public function validateLogin(Request $request)
    {
        if(config('constants.enable_recaptcha')){
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
                'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);
        }else{
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
            ]);
        }
    }

}
