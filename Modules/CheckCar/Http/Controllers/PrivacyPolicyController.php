<?php

namespace Modules\CheckCar\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivacyPolicyController extends Controller
{
    /**
     * Return privacy policy text for a given location (or default business location).
     */
    public function show(Request $request)
    {
        $locationId = $request->query('location_id');

        if ($locationId) {
            $policy = DB::table('business_locations')
                ->where('id', (int) $locationId)
                ->value('privacy_policy');
        } else {
            $businessId = $request->query('business_id');

            $query = DB::table('business_locations');
            if ($businessId) {
                $query->where('business_id', (int) $businessId);
            }

            $policy = $query->orderBy('id')->value('privacy_policy');
        }

        return response()->json([
            'privacy_policy' => $policy ?? '',
        ]);
    }
}
