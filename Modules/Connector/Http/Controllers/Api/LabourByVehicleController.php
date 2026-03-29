<?php

namespace Modules\Connector\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * @group Labour by Vehicle
 * @authenticated
 *
 * APIs for managing labour by vehicle
 */
class LabourByVehicleController extends ApiController
{

    /**
     * Get Labour Products by JobSheet ID
     *
     * @param int $job_sheet_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLabourProductsByJobSheet($job_sheet_id)
    {
        try {
            // Get job sheet with booking and device information
            $job_sheet = DB::table('repair_job_sheets')
                ->leftJoin('bookings', 'repair_job_sheets.booking_id', '=', 'bookings.id')
                ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
                ->where('repair_job_sheets.id', $job_sheet_id)
                ->select([
                    'repair_job_sheets.id as job_sheet_id',
                    'bookings.device_id as booking_device_id',
                    'contact_device.device_id as device_brand_id',
                    'contact_device.models_id as device_model_id',
                    'contact_device.manufacturing_year as device_year'
                ])
                ->first();

            if (!$job_sheet) {
                return response()->json(['error' => 'Job Sheet not found'], 404);
            }

            // Find matching Labour by Vehicle records
            $labour_by_vehicle_matches = DB::table('labour_by_vehicle')
                ->where('device_id', $job_sheet->device_brand_id)
                ->where(function ($query) use ($job_sheet) {
                    $query->whereNull('repair_device_model_id')
                          ->orWhere('repair_device_model_id', $job_sheet->device_model_id);
                })
                ->where(function ($query) use ($job_sheet) {
                    $query->whereNull('from')
                          ->orWhere('from', '<=', $job_sheet->device_year);
                })
                ->where(function ($query) use ($job_sheet) {
                    $query->whereNull('to')
                          ->orWhere('to', '>=', $job_sheet->device_year);
                })
                ->get();

            if ($labour_by_vehicle_matches->isEmpty()) {
                return response()->json([
                    'message' => 'No matching Labour by Vehicle records found for this vehicle',
                    'job_sheet_info' => $job_sheet,
                    'labour_products' => []
                ]);
            }

            // Get all labour product IDs from matching Labour by Vehicle records
            $labour_by_vehicle_ids = $labour_by_vehicle_matches->pluck('id');
            
            $labour_products = DB::table('labour_by_vehicle_products')
                ->leftJoin('products', 'labour_by_vehicle_products.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('variations', 'variations.product_id', '=', 'products.id')
                ->whereIn('labour_by_vehicle_products.labour_by_vehicle_id', $labour_by_vehicle_ids)
                ->where('labour_by_vehicle_products.is_active', 1)
                ->select([
                    'labour_by_vehicle_products.id as mapping_id',
                    'labour_by_vehicle_products.labour_by_vehicle_id',
                    'labour_by_vehicle_products.product_id',
                    'labour_by_vehicle_products.price as labour_price',
                    'labour_by_vehicle_products.is_active',
                    'products.name as product_name',
                    'products.sku',
                    'categories.name as category_name',
                    'variations.default_sell_price as default_price',
                    'variations.sell_price_inc_tax as retail_price'
                ])
                ->groupBy(
                    'labour_by_vehicle_products.id',
                    'labour_by_vehicle_products.labour_by_vehicle_id',
                    'labour_by_vehicle_products.product_id',
                    'labour_by_vehicle_products.price',
                    'labour_by_vehicle_products.is_active',
                    'products.name',
                    'products.sku',
                    'categories.name',
                    'variations.default_sell_price',
                    'variations.sell_price_inc_tax'
                )
                ->get();

            // Group by product to create variations-like structure
            $grouped_products = [];
            foreach ($labour_products as $product) {
                $product_id = $product->product_id;
                
                if (!isset($grouped_products[$product_id])) {
                    $grouped_products[$product_id] = [
                        'product_id' => $product->product_id,
                        'product_name' => $product->product_name,
                        'sku' => $product->sku,
                        'category_name' => $product->category_name
                    ];
                }
                
                $grouped_products[$product_id]['variations'][] = [
                    'mapping_id' => $product->mapping_id,
                  
                    'labour_price' => $product->labour_price,
                   
                ];
            }

            return response()->json([
                'success' => true,
         
                'labour_products' => array_values($grouped_products)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving labour products: ' . $e->getMessage()
            ], 500);
        }
    }
}
