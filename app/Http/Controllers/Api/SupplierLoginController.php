<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class SupplierLoginController extends Controller
{
    /**
     * Handle supplier login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
  public function login(Request $request)
{
    try {

        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        Log::info('Supplier login request', ['username' => $request->username]);

        $supplier = Contact::where(function($query) use ($request) {
            $query->where('username', $request->username)
                  ->orWhere('email', $request->username)
                  ->orWhere('mobile', $request->username);
        })
            ->whereIn('type', ['supplier', 'both'])
            ->where('contact_status', 'active')
            ->first();

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found or inactive'
            ], 404);
        }

        // Check password - handle case where password field might not exist
        $supplierPassword = $supplier->password ?? null;
        
        // If password field doesn't exist or is null, you might need to use a different approach
        // For now, let's assume password is stored in a custom field or use mobile as password for testing
        if (!$supplierPassword) {
            // You might want to use a different field or implement your own logic
            // For example, use mobile as temporary password for testing
            $supplierPassword = $supplier->mobile; // This is just for testing
        }
        
        if (!Hash::check($request->password, $supplierPassword)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create simple token like normal applications
        $plainTextToken = Str::random(32); // Shorter token like normal apps
        $hashedToken = hash('sha256', $plainTextToken);
        
        // Store token in cache for 24 hours
        \Cache::put("supplier_token_{$supplier->id}", [
            'token' => $hashedToken,
            'supplier_id' => $supplier->id,
            'expires_at' => now()->addHours(24)
        ], now()->addHours(24));

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'email' => $supplier->email,
                    'mobile' => $supplier->mobile,
                    'business_name' => $supplier->supplier_business_name,
                    'contact_id' => $supplier->contact_id,
                    'type' => $supplier->type,
                ],
                'token' => $plainTextToken
            ]
        ], 200);

    } catch (\Throwable $e) {   // مهم جدا مش Exception

        Log::error('Login Error', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(), // هيظهرلك السبب الحقيقي
        ], 500);
    }
}
    /**
     * Logout supplier (revoke token).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            $hashedToken = hash('sha256', $token);
            
            // Simple way: check each supplier's token in cache
            $suppliers = Contact::whereIn('type', ['supplier', 'both'])->get();
            foreach ($suppliers as $supplier) {
                $cachedData = \Cache::get("supplier_token_{$supplier->id}");
                if ($cachedData && isset($cachedData['token']) && $cachedData['token'] === $hashedToken) {
                    \Cache::forget("supplier_token_{$supplier->id}");
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Get authenticated supplier profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        // Get supplier from middleware
        $supplier = $request->supplier;
        
        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'email' => $supplier->email,
                    'mobile' => $supplier->mobile,
                    'business_name' => $supplier->supplier_business_name,
                    'contact_id' => $supplier->contact_id,
                    'type' => $supplier->type,
                    'address' => $supplier->contact_address,
                    'city' => $supplier->city,
                    'state' => $supplier->state,
                    'country' => $supplier->country,
                    'zip_code' => $supplier->zip_code,
                ]
            ]
        ], 200);
    }
}
