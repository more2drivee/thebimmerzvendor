<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login'); 
        }

        $admins = explode(',', env('ADMINISTRATOR_USERNAMES', ''));

        if (in_array($user->username, $admins)) {
            return $next($request);
        }

        return redirect('/not-authorized');
    }
}
