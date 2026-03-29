<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\CheckCar\Entities\PrivacyPolicy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PrivacyPolicyController extends Controller
{
    /**
     * Get privacy policy for the current business
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
          

            $policy = PrivacyPolicy::first();

            if (!$policy) {
                // Create default policy if none exists
                $policy = PrivacyPolicy::create([
                  
                    'content' => $this->getDefaultPolicyContent()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $policy->id,
                    'business_id' => $policy->business_id,
                    'content' => $policy->content,
                    'updated_at' => $policy->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('PrivacyPolicyController@show: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch privacy policy'
            ], 500);
        }
    }

    /**
     * Update privacy policy for the current business
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'content' => 'required|string'
            ]);

            $business_id = optional(Auth::user())->business_id;
            
            if (!$business_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found'
                ], 404);
            }

            $policy = PrivacyPolicy::forBusiness($business_id);

            if (!$policy) {
                // Create if doesn't exist
                $policy = PrivacyPolicy::create([
                    'business_id' => $business_id,
                    'content' => $request->content
                ]);
            } else {
                // Update existing
                $policy->update([
                    'content' => $request->content
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Privacy policy updated successfully',
                'data' => [
                    'id' => $policy->id,
                    'business_id' => $policy->business_id,
                    'content' => $policy->content,
                    'updated_at' => $policy->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('PrivacyPolicyController@update: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update privacy policy'
            ], 500);
        }
    }

    /**
     * Get default privacy policy content
     *
     * @return string
     */
    private function getDefaultPolicyContent(): string
    {
        return "Privacy Policy

Last Updated: " . date('Y-m-d') . "

1. Information We Collect
   - Personal information (name, contact details)
   - Vehicle information (make, model, VIN)
   - Service and inspection records

2. How We Use Your Information
   - To provide vehicle inspection services
   - To communicate with you about your vehicle
   - To maintain accurate service records

3. Information Sharing
   - We do not sell your personal information
   - Information is only shared with authorized service providers
   - We comply with all applicable data protection laws

4. Data Security
   - We implement appropriate security measures
   - Access to information is restricted to authorized personnel
   - We regularly review our security practices

5. Your Rights
   - Access to your personal information
   - Correction of inaccurate information
   - Deletion of your information (where legally permitted)

6. Contact Us
   If you have questions about this privacy policy, please contact us.

This policy may be updated from time to time. Continued use of our services indicates acceptance of any changes.";
    }
}
