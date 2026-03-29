<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Business;
use App\BusinessLocation;
use Illuminate\Support\Facades\Auth;
use Modules\Connector\Transformers\BusinessLocationResource;

/**
 * @group Business Location management
 * @authenticated
 *
 * APIs for managing business locations
 */
class BusinessLocationController extends ApiController
{
    /**
     * List business locations
     *
     * @response {
            "data": [
                {
                    "id": 1,
                    "business_id": 1,
                    "location_id": null,
                    "name": "Awesome Shop",
                    "landmark": "Linking Street",
                    "country": "USA",
                    "state": "Arizona",
                    "city": "Phoenix",
                    "zip_code": "85001",
                    "mobile": null,
                    "latitude": null,
                    "longitude": null,
                    "invoice_scheme_id": 1,
                    "invoice_layout_id": 1,
                    "selling_price_group_id": null,
                    "print_receipt_on_invoice": 1,
                    "receipt_printer_type": "browser",
                    "printer_id": null,
                    "alternate_number": null,
                    "email": null,
                    "website": null,
                    "featured_products": [
                        "5",
                        "71"
                    ],
                    "is_active": 1,
                    "payment_methods": [
                        {
                            "name": "cash",
                            "label": "Cash",
                            "account_id": "1"
                        },
                        {
                            "name": "card",
                            "label": "Card",
                            "account_id": null
                        },
                        {
                            "name": "cheque",
                            "label": "Cheque",
                            "account_id": null
                        },
                        {
                            "name": "bank_transfer",
                            "label": "Bank Transfer",
                            "account_id": null
                        },
                        {
                            "name": "other",
                            "label": "Other",
                            "account_id": null
                        },
                        {
                            "name": "custom_pay_1",
                            "label": "Custom Payment 1",
                            "account_id": null
                        },
                        {
                            "name": "custom_pay_2",
                            "label": "Custom Payment 2",
                            "account_id": null
                        },
                        {
                            "name": "custom_pay_3",
                            "label": "Custom Payment 3",
                            "account_id": null
                        }
                    ],
                    "custom_field1": null,
                    "custom_field2": null,
                    "custom_field3": null,
                    "custom_field4": null,
                    "deleted_at": null,
                    "created_at": "2018-01-04 02:15:20",
                    "updated_at": "2020-06-05 00:56:54"
                }
            ]
        }
     */
    public function booking_app_locations()
    {
        $user = Auth::user();

        $business_id = $user->business_id;

        $query = BusinessLocation::where('business_id', $business_id);

        $business_locations = $query->Active()->get(['id', 'name', 'landmark', 'country', 'state', 'city', 'mobile', 'latitude', 'longitude', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4']);

        // Get custom labels for location fields
        $business = Business::find($business_id);
        $custom_labels = !empty($business->custom_labels) ? json_decode($business->custom_labels, true) : [];
        $location_labels = $custom_labels['location'] ?? [];

        // Transform custom fields to use labels as keys
        $transformed_locations = $business_locations->map(function ($location) use ($location_labels) {
            $data = $location->toArray();

            // Replace custom_field keys with label names
            for ($i = 1; $i <= 4; $i++) {
                $field_key = 'custom_field' . $i;
                $label_key = 'custom_field_' . $i;
                $label_name = $location_labels[$label_key] ?? null;

                if ($label_name) {
                    // Use label as key, remove original field
                    $data[$label_name] = $data[$field_key];
                }
                unset($data[$field_key]);
            }

            return $data;
        });

        return response()->json($transformed_locations);
    }



        public function index()
    {
        $user = Auth::user();

        $business_id = $user->business_id;

        $permitted_locations = $user->permitted_locations($business_id);

        $query = BusinessLocation::where('business_id', $business_id);

        if ($permitted_locations != 'all') {
            $query->whereIn('id', $permitted_locations);
        }
        $business_locations = $query->Active()->get();

        return BusinessLocationResource::collection($business_locations);
    }

    /**
     * Get the specified business location
     *
     * @urlParam location required  comma separated ids of the business location Example: 1
     * @response {
            "data": [
                {
                    "id": 1,
                    "business_id": 1,
                    "location_id": null,
                    "name": "Awesome Shop",
                    "landmark": "Linking Street",
                    "country": "USA",
                    "state": "Arizona",
                    "city": "Phoenix",
                    "zip_code": "85001",
                    "mobile": null,
                    "latitude": null,
                    "longitude": null,
                    "invoice_scheme_id": 1,
                    "invoice_layout_id": 1,
                    "selling_price_group_id": null,
                    "print_receipt_on_invoice": 1,
                    "receipt_printer_type": "browser",
                    "printer_id": null,
                    "alternate_number": null,
                    "email": null,
                    "website": null,
                    "featured_products": [
                        "5",
                        "71"
                    ],
                    "is_active": 1,
                    "payment_methods": [
                        {
                            "name": "cash",
                            "label": "Cash",
                            "account_id": "1"
                        },
                        {
                            "name": "card",
                            "label": "Card",
                            "account_id": null
                        },
                        {
                            "name": "cheque",
                            "label": "Cheque",
                            "account_id": null
                        },
                        {
                            "name": "bank_transfer",
                            "label": "Bank Transfer",
                            "account_id": null
                        },
                        {
                            "name": "other",
                            "label": "Other",
                            "account_id": null
                        },
                        {
                            "name": "custom_pay_1",
                            "label": "Custom Payment 1",
                            "account_id": null
                        },
                        {
                            "name": "custom_pay_2",
                            "label": "Custom Payment 2",
                            "account_id": null
                        },
                        {
                            "name": "custom_pay_3",
                            "label": "Custom Payment 3",
                            "account_id": null
                        }
                    ],
                    "custom_field1": null,
                    "custom_field2": null,
                    "custom_field3": null,
                    "custom_field4": null,
                    "deleted_at": null,
                    "created_at": "2018-01-04 02:15:20",
                    "updated_at": "2020-06-05 00:56:54"
                }
            ]
        }
     */
    public function show($location_ids)
    {
        $user = Auth::user();

        $business_id = $user->business_id;
        $location_ids = explode(',', $location_ids);

        $locations = BusinessLocation::where('business_id', $business_id)
                        ->whereIn('id', $location_ids)
                        ->get();

        return BusinessLocationResource::collection($locations);
    }
}
