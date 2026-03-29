<?php

namespace App\Http\Middleware;

use App\Contact;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SupplierApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token required'
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $supplier = null;
        
        // Check each supplier's token in cache
        $suppliers = Contact::whereIn('type', ['supplier', 'both'])->get();
        foreach ($suppliers as $s) {
            $cachedData = \Cache::get("supplier_token_{$s->id}");
            if ($cachedData && isset($cachedData['token']) && $cachedData['token'] === $hashedToken) {
                // Check if token is still valid (not expired)
                if (isset($cachedData['expires_at']) && now()->lt($cachedData['expires_at'])) {
                    $supplier = $s;
                    break;
                }
            }
        }
        
        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Add supplier to request for use in controllers
        $request->merge(['supplier' => $supplier]);
        
        return $next($request);
    }
}
