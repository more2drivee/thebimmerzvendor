<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckEnvAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // التحقق من الأدمن من الـ ENV أولاً
        if (session('is_env_admin') && session('admin_username')) {
            return $next($request);
        }
        
        // ثم التحقق من الـ auth (المستخدمين من الدatابيس)
        if (auth()->check()) {
            // تحقق من أن المستخدم له صلاحية admin
            if (auth()->user()->user_type === 'admin' || auth()->user()->user_type === 'superadmin') {
                return $next($request);
            }
        }
        
        // إذا لم يكن أدمن من ENV ولا من الدatابيس
        return redirect('/login')->with('error', 'Unauthorized access');
    }
}
