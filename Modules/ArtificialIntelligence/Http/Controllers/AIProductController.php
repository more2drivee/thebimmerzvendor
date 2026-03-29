<?php
/**
 * AIProductController
 *
 * This controller handles interactions with external AI/API services for product data.
 *
 * Special handling for SKUs containing forward slashes (/):
 * 1. First attempt: Pass SKU as a query parameter instead of in the URL path
 *    - Uses format: /api/product?sku={sku} instead of /api/product/{sku}
 *
 * 2. Fallback approach (if first attempt fails):
 *    - Replace forward slashes with hyphens: "HU712/6X" becomes "HU712-6X"
 *    - URL encode the modified SKU and use it in the path
 *    - Send the original SKU in a custom header (X-Original-SKU)
 *
 * This multi-step approach ensures maximum compatibility with different API implementations.
 */

namespace Modules\ArtificialIntelligence\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\ApiSetting;
use App\Category;
use App\Product;
use App\ProductCompatibility;
use Modules\Repair\Entities\DeviceModel;
use Maatwebsite\Excel\Facades\Excel;

class AIProductController extends Controller
{
    public function __construct()
    {
        // No dependencies needed
    }

    /**
     * Get product details from database or external API
     * This method is maintained for backward compatibility
     * but now delegates to getProductDetailsFromAI
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductDetails(Request $request)
    {
        return $this->getProductDetailsFromAI($request);
    }



    /**
     * Get product details directly from AI/external API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductDetailsFromAI(Request $request)
    {
        $sku_input = $request->input('sku');
        $product_name = $request->input('product_name');

        if (empty($sku_input)) {
            return response()->json(['success' => false, 'error' => 'SKU is required'], 400);
        }

        try {

            // Step 2: Check if we have permission to access external API
            // $permission = \App\DataPermission::where('permission_key', 'access_external_product_api')
            //     ->where('is_active', 1)
            //     ->first();

            // if (!$permission) {
            //     Log::info('External API access denied: Permission not found or inactive');
            //     return response()->json([
            //         'success' => false,
            //         'error' => 'Permission denied to access external API',
            //         'details' => 'Required permission is not active'
            //     ], 403);
            // }

            // Step 3: Get API settings from database
            $apiSettings = ApiSetting::first();

            if (!$apiSettings) {
                Log::info('External API access failed: API settings not found');
                return response()->json(['success' => false, 'error' => 'API settings not found'], 500);
            }

            // Step 4: Make API request with settings from database
            // Fix double slash in API URL by trimming trailing slash from base_url
            $baseUrl = rtrim($apiSettings->base_url, '/');

            // Check if SKU contains forward slashes
            if (strpos($sku_input, '/') !== false) {
                // If SKU contains forward slashes, use a different endpoint format
                // Pass SKU as a query parameter instead of in the URL path
                $url = $baseUrl . '/api/product';

                // Prepare query parameters
                $queryParams = ['sku' => $sku_input]; // Pass SKU as a query parameter

                Log::info("SKU contains forward slash, using query parameter approach");
            } else {
                // For SKUs without forward slashes, use the original URL format
                $url = $baseUrl . '/api/product/' . $sku_input;

                // Prepare query parameters
                $queryParams = [];
            }

            // Include product name as a query parameter if available
            // Laravel's HTTP client will automatically URL encode query parameters
            if (!empty($product_name)) {
                $queryParams['product_name'] = $product_name;
                Log::info("Making API request for SKU: {$sku_input} with product name: {$product_name}");
            } else {
                Log::info("Making API request for SKU: {$sku_input} without product name");
            }

            // Make the API request with headers
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-API-Token' => $apiSettings->token,
                'X-API-Domain' => $apiSettings->domain
            ])->get($url, $queryParams);

            // Log the full response for debugging
            Log::info("API Response for SKU {$sku_input}:", [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'queryParams' => $queryParams
            ]);

            // If the request fails and we're using the query parameter approach for a SKU with forward slashes,
            // try an alternative approach by replacing forward slashes with a different character
            if (!$response->successful() && strpos($sku_input, '/') !== false && isset($queryParams['sku'])) {
                Log::info("Query parameter approach failed, trying alternative encoding for SKU with forward slashes");

                // Try replacing forward slashes with a different character (e.g., '-' or '_')
                $modified_sku = str_replace('/', '-', $sku_input);
                $url = $baseUrl . '/api/product/' . urlencode($modified_sku);

                // Remove the sku from query parameters since we're now using it in the URL path
                unset($queryParams['sku']);

                // Add a special header to indicate the SKU has been modified
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'X-API-Token' => $apiSettings->token,
                    'X-API-Domain' => $apiSettings->domain,
                    'X-Original-SKU' => $sku_input // Send the original SKU in a header
                ])->get($url, $queryParams);

                Log::info("Tried alternative approach with modified SKU: {$modified_sku}");
            }

            if ($response->successful()) {
                $apiResponse = $response->json();

                // Check if the response has the expected structure
                if (isset($apiResponse['success']) && $apiResponse['success'] && isset($apiResponse['data'])) {
                    // Format the data for the product creation form
                    $formattedData = $this->formatProductData($apiResponse['data']);

                    // Add formatted data to the response
                    $apiResponse['formatted_data'] = $formattedData;

                    // For direct access in the frontend
                    if (isset($formattedData['product_name'])) {
                        $apiResponse['product_name'] = $formattedData['product_name'];
                    }

                    // Add model years data for the dropdown
                    if (isset($formattedData['model_years']) && !empty($formattedData['model_years'])) {
                        $apiResponse['model_years'] = $formattedData['model_years'];
                    }

                    // We're not creating or updating products here anymore
                    // Just returning the compatibility data to the blade template
                    // The product will be created when the user submits the form

                    $apiResponse['source'] = 'external_api';
                    return response()->json($apiResponse);
                } else {
                    Log::warning('External API response has unexpected format', ['response' => $apiResponse]);
                    return response()->json(['success' => false, 'error' => 'Invalid API response format'], 500);
                }
            } else {
                Log::info('External API request failed: ' . $response->status() . ' - ' . $response->body());
                return response()->json(['success' => false, 'error' => 'External API request failed'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error getting product details from AI: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Format product data for the product creation form
     *
     * @param array $data
     * @return array
     */
    private function formatProductData($data)
    {
        $formatted = [];

        // Extract product name from title
        if (isset($data['product_info']['title'])) {
            $formatted['product_name'] = $data['product_info']['title'];
        }

        // Extract SKU/Item Number
        if (isset($data['product_info']['Item Number'])) {
            $formatted['sku'] = $data['product_info']['Item Number'];
        }

        // Extract category
        if (isset($data['product_info']['Category'])) {
            $formatted['category'] = $data['product_info']['Category'];
        }

        // Extract supplier if available
        if (isset($data['product_info']['Supplier'])) {
            $formatted['supplier'] = $data['product_info']['Supplier'];
        }

        // Format vehicle compatibility data for dropdown
        $formatted['compatibility'] = [];
        $formatted['model_years'] = []; // For model year dropdown

        // Track processed combinations to avoid duplicates
        $processedCombinations = [];

        if (isset($data['vehicle_compatibility']) && is_array($data['vehicle_compatibility'])) {
            // Handle the case where vehicle_compatibility is an object with make names as keys
            if (count(array_filter(array_keys($data['vehicle_compatibility']), 'is_string')) > 0) {
                foreach ($data['vehicle_compatibility'] as $make => $models) {
                    foreach ($models as $model) {
                        if (isset($model['model'])) {
                            // Get year range data - handle different possible structures
                            $from_year = null;
                            $to_year = null;

                            if (isset($model['year_from']) && isset($model['year_to'])) {
                                // Direct year_from and year_to properties (common in the example)
                                $from_year = $model['year_from'];
                                $to_year = $model['year_to'];
                            } else if (isset($model['year_range'])) {
                                // Handle case where year_range is an object
                                if (is_array($model['year_range'])) {
                                    $from_year = $model['year_range']['from'] ?? null;
                                    $to_year = $model['year_range']['to'] ?? null;
                                }
                            } else if (isset($model['from_year']) && isset($model['to_year'])) {
                                // Direct from_year and to_year properties
                                $from_year = $model['from_year'];
                                $to_year = $model['to_year'];
                            }

                            // Only add if we have valid year data
                            if ($from_year !== null && $to_year !== null) {
                                // Get brand category if it exists (don't create new ones)
                                $brandCategory = $this->getBrandCategory($make);

                                // Skip if brand doesn't exist in database
                                if (!$brandCategory) {
                                    Log::info("Skipping compatibility for non-existent brand: {$make}");
                                    continue;
                                }

                                // Create a model name that's specific to this brand
                                // This ensures models with the same name but different brands are handled correctly
                                $modelName = str_replace($make . ' ', '', $make . ' ' . $model['model']);
                                $modelObj = $this->getModel($modelName, $brandCategory->id);

                                // Skip if model doesn't exist in database
                                if (!$modelObj) {
                                    Log::info("Skipping compatibility for non-existent model: {$modelName}");
                                    continue;
                                }

                                // Create a unique key for this combination
                                $combinationKey = $make . '|' . $model['model'] . '|' . $from_year . '|' . $to_year;

                                // Skip if we've already processed this exact combination
                                if (isset($processedCombinations[$combinationKey])) {
                                    Log::info("Skipping duplicate combination: {$combinationKey}");
                                    continue;
                                }

                                // Mark this combination as processed
                                $processedCombinations[$combinationKey] = true;

                                // For compatibility list - use the correct field names with actual IDs
                                $formatted['compatibility'][] = [
                                    'brand_category_id' => $brandCategory->id,
                                    'model_id' => $modelObj->id,
                                    'make' => $make, // For reference only, not saved to DB
                                    'model' => $model['model'], // For reference only, not saved to DB
                                    'from_year' => $from_year,
                                    'to_year' => $to_year
                                ];

                                // For model year dropdown - keep the same structure for UI but include IDs
                                $formatted['model_years'][] = [
                                    'make' => $make,
                                    'model_name' => $make . ' ' . $model['model'],
                                    'from_year' => $from_year,
                                    'to_year' => $to_year,
                                    'model_id' => $modelObj->id,
                                    'brand_category_id' => $brandCategory->id
                                ];
                            }
                        }
                    }
                }
            } else {
                // Handle the case where vehicle_compatibility is an array of makes
                foreach ($data['vehicle_compatibility'] as $makeData) {
                    if (is_array($makeData) && isset($makeData[0]) && isset($makeData[0]['model'])) {
                        $make = key($makeData);
                        foreach ($makeData as $model) {
                            if (isset($model['model'])) {
                                // Get year range data
                                $from_year = null;
                                $to_year = null;

                                if (isset($model['year_from']) && isset($model['year_to'])) {
                                    // Direct year_from and year_to properties
                                    $from_year = $model['year_from'];
                                    $to_year = $model['year_to'];
                                } else if (isset($model['year_range'])) {
                                    if (is_array($model['year_range'])) {
                                        $from_year = $model['year_range']['from'] ?? null;
                                        $to_year = $model['year_range']['to'] ?? null;
                                    }
                                } else if (isset($model['from_year']) && isset($model['to_year'])) {
                                    // Direct from_year and to_year properties
                                    $from_year = $model['from_year'];
                                    $to_year = $model['to_year'];
                                }

                                // Only add if we have valid year data
                                if ($from_year !== null && $to_year !== null) {
                                    // Get brand category if it exists (don't create new ones)
                                    $brandCategory = $this->getBrandCategory($make);

                                    // Skip if brand doesn't exist in database
                                    if (!$brandCategory) {
                                        Log::info("Skipping compatibility for non-existent brand: {$make}");
                                        continue;
                                    }

                                    $modelName = str_replace($make . ' ', '', $make . ' ' . $model['model']);
                                    $modelObj = $this->getModel($modelName, $brandCategory->id);

                                    // Skip if model doesn't exist in database
                                    if (!$modelObj) {
                                        Log::info("Skipping compatibility for non-existent model: {$modelName}");
                                        continue;
                                    }

                                    // Create a unique key for this combination
                                    $combinationKey = $make . '|' . $model['model'] . '|' . $from_year . '|' . $to_year;

                                    // Skip if we've already processed this exact combination
                                    if (isset($processedCombinations[$combinationKey])) {
                                        Log::info("Skipping duplicate combination: {$combinationKey}");
                                        continue;
                                    }

                                    // Mark this combination as processed
                                    $processedCombinations[$combinationKey] = true;

                                    // For compatibility list - use the correct field names with actual IDs
                                    $formatted['compatibility'][] = [
                                        'brand_category_id' => $brandCategory->id,
                                        'model_id' => $modelObj->id,
                                        'model' => $model['model'], // For reference only, not saved to DB
                                        'from_year' => $from_year,
                                        'to_year' => $to_year
                                    ];

                                    // For model year dropdown - keep the same structure for UI but include IDs
                                    $formatted['model_years'][] = [
                                        'make' => $make,
                                        'model_name' => $make . ' ' . $model['model'],
                                        'from_year' => $from_year,
                                        'to_year' => $to_year,
                                        'model_id' => $modelObj->id,
                                        'brand_category_id' => $brandCategory->id
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        Log::info("Formatted data contains " . count($formatted['compatibility']) . " unique compatibility records");
        return $formatted;
    }

    /**
     * Process product request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processProductRequest(Request $request)
    {
        try {
            $type = $request->input('type');

            switch ($type) {
                case 'product_details':
                    return $this->getProductDetails($request);
                case 'product_details_ai':
                    return $this->getProductDetailsFromAI($request);
                default:
                    return response()->json(['success' => false, 'error' => 'Invalid request type'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing request: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process Excel import with product data
     * Directly handles the Excel file and adds compatibility data to existing products
     * First filters products that exist in the database, then processes them one by one with sleep between API requests
     *
     * Supports processing specific rows from the Excel file:
     * - start_row: The row number to start processing from (1-based, default: 1)
     * - max_rows: Maximum number of rows to process (default: all rows)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processExcelImport(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'products_csv' => 'required|file|mimes:csv,xls,xlsx',
                'sleep_seconds' => 'nullable|integer|min:1|max:30',
                'start_row' => 'nullable|integer|min:1',
                'max_rows' => 'nullable|integer|min:1'
            ]);

            $sleepSeconds = $request->input('sleep_seconds', 5); // Default to 5 seconds if not provided
            $startRow = $request->input('start_row', 1); // Default to first row if not provided
            $maxRows = $request->input('max_rows'); // Default to all rows if not provided
            $file = $request->file('products_csv');

            // Parse the Excel file
            $parsed_array = Excel::toArray([], $file);

            // Remove header row
            $imported_data = array_splice($parsed_array[0], 1);

            // Apply start_row parameter (adjust for 0-based index and header removal)
            $adjusted_start_row = $startRow - 1;
            if ($adjusted_start_row > 0) {
                Log::info("Starting from row {$startRow} (adjusted index: {$adjusted_start_row})");
                $imported_data = array_slice($imported_data, $adjusted_start_row);
            }

            // Apply max_rows parameter if provided
            if (!empty($maxRows)) {
                $original_count = count($imported_data);
                $imported_data = array_slice($imported_data, 0, $maxRows);
                Log::info("Processing {$maxRows} rows out of {$original_count} available rows");
            }

            $all_products = [];
            $skus_to_check = [];

            foreach ($imported_data as $value) {
                // Check if we have enough columns
                if (count($value) < 6) {
                    continue;
                }

                $product_name = trim($value[0]);
                $sku = trim($value[5]);

                // Skip if SKU is empty
                if (empty($sku)) {
                    continue;
                }

                // Add to all products array
                $all_products[] = [
                    'product_name' => $product_name,
                    'sku' => $sku,
                    'exists_in_db' => false // Will be updated after DB check
                ];

                // Add to SKUs to check
                $skus_to_check[] = $sku;
            }

            // Get all products that exist in the database with these SKUs (in a single query)
            $existing_products = \App\Product::whereIn('sku', $skus_to_check)->pluck('id', 'sku')->toArray();

            // Mark products that exist in the database
            $products_to_process = [];
            $unprocessed_products = [];

            foreach ($all_products as &$product) {
                if (isset($existing_products[$product['sku']])) {
                    $product['exists_in_db'] = true;
                    $product['product_id'] = $existing_products[$product['sku']];
                    $products_to_process[] = $product;
                } else {
                    $unprocessed_products[] = $product;
                }
            }

            $total_in_excel = count($all_products);
            $total_to_process = count($products_to_process);

            Log::info("Found {$total_to_process} of {$total_in_excel} products in database. Processing only existing products.");

            // Process only products that exist in the database
            $results = [];
            $count = 0;

            foreach ($products_to_process as $product) {
                $count++;
                Log::info("Processing product {$count}/{$total_to_process}: SKU={$product['sku']}");

                // Check if the client is still connected
                if (connection_status() != CONNECTION_NORMAL) {
                    Log::info("Client disconnected, stopping processing at product {$count}/{$total_to_process}");
                    throw new \Exception("Client disconnected");
                }

                // Flush output buffer to allow for progress updates
                if (ob_get_level() > 0) {
                    ob_flush();
                    flush();
                }

                // Process one product at a time
                $result = $this->processProductFromExcel($product, $count, $total_to_process);
                $results[] = $result;

                // Sleep between requests to avoid overwhelming the external API
                if ($count < $total_to_process) {
                    Log::info("Sleeping for {$sleepSeconds} seconds before next request");
                    sleep($sleepSeconds);
                }
            }

            // Group unprocessed products by first word of product name (usually brand)
            $grouped_unprocessed = [];
            foreach ($unprocessed_products as $product) {
                // Extract first word as brand (simple approach)
                $name_parts = explode(' ', trim($product['product_name']));
                $brand = !empty($name_parts[0]) ? $name_parts[0] : 'Unknown';

                if (!isset($grouped_unprocessed[$brand])) {
                    $grouped_unprocessed[$brand] = [];
                }

                $grouped_unprocessed[$brand][] = $product;

                // Add to results with appropriate status
                $results[] = [
                    'sku' => $product['sku'],
                    'product_name' => $product['product_name'],
                    'success' => false,
                    'error' => 'Product not found in database. Only existing products can be updated.',
                    'status' => 'skipped',
                    'product_status' => 'not_found',
                    'brand' => $brand
                ];
            }

            // Create summary of unprocessed products by brand
            $brand_summary = [];
            foreach ($grouped_unprocessed as $brand => $products) {
                $brand_summary[] = [
                    'brand' => $brand,
                    'count' => count($products)
                ];
            }

            // Sort by count descending
            usort($brand_summary, function($a, $b) {
                return $b['count'] - $a['count'];
            });

            // Calculate row range information
            $row_range_info = [
                'start_row' => $startRow,
                'max_rows' => $maxRows,
                'actual_rows_processed' => count($imported_data),
                'total_rows_in_file' => count($parsed_array[0]) + 1 // Add 1 for the header row
            ];

            return response()->json([
                'success' => true,
                'message' => 'Excel import processed successfully',
                'total_products' => $total_in_excel,
                'products_in_database' => $total_to_process,
                'products_not_in_database' => count($unprocessed_products),
                'successful_products' => count(array_filter($results, function($item) { return $item['success'] === true; })),
                'failed_products' => count(array_filter($results, function($item) { return $item['success'] === false && $item['status'] !== 'skipped'; })),
                'skipped_products' => count(array_filter($results, function($item) { return $item['status'] === 'skipped'; })),
                'brand_summary' => $brand_summary,
                'row_range' => $row_range_info,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            // Check if this is a client disconnect (request aborted)
            if (strpos($e->getMessage(), 'Client disconnected') !== false ||
                strpos($e->getMessage(), 'Connection aborted') !== false) {
                Log::info('Excel import process was stopped by the user');
                return response()->json([
                    'success' => false,
                    'error' => 'Process stopped by user',
                    'message' => 'Excel import process was stopped by the user',
                    'stopped_by_user' => true
                ], 499); // 499 is a common code for client closed request
            }

            Log::error('Error processing Excel import: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process Excel import'
            ], 500);
        }
    }

    /**
     * Get a brand category if it exists in the database
     *
     * @param string $brandName
     * @return \App\Category|null
     */
    private function getBrandCategory($brandName)
    {
        // Try to find existing brand category
        $brandCategory = \App\Category::where('name', $brandName)
            ->where('category_type', 'device')
            ->first();

       

        return $brandCategory;
    }

    /**
     * Get a device model if it exists in the database
     *
     * @param string $modelName
     * @param int $brandCategoryId
     * @return \Modules\Repair\Entities\DeviceModel|null
     */
    private function getModel($modelName, $brandCategoryId)
    {
        // Try to find existing model
        $model = \Modules\Repair\Entities\DeviceModel::where('name', $modelName)
            ->where('device_id', $brandCategoryId)
            ->first();

        if (!$model) {
            // Also try to find the model without requiring an exact brand match
            // This helps when the model exists but might be associated with a different brand variant
            $model = \Modules\Repair\Entities\DeviceModel::where('name', $modelName)->first();

            if ($model) {
                Log::info("Found model with different brand: {$modelName}");
            } else {
                Log::info("Model not found in database: {$modelName} for brand category ID: {$brandCategoryId}");
            }
        }

        return $model;
    }

    /**
     * Process a single product from Excel import
     * Gets data from external API and adds compatibility data to existing product
     *
     * @param array $product Product with 'sku', 'product_name', and optionally 'product_id' keys
     * @param int $currentCount Current product count for logging
     * @param int $totalCount Total product count for logging
     * @return array Result of processing the product
     */
    public function processProductFromExcel(array $product, int $currentCount, int $totalCount)
    {
        $sku = $product['sku'] ?? null;
        $product_name = $product['product_name'] ?? null;
        $product_id = $product['product_id'] ?? null;

        if (empty($sku)) {
            return [
                'sku' => $sku,
                'product_name' => $product_name,
                'success' => false,
                'error' => 'SKU is required',
                'status' => 'error'
            ];
        }

        // Get existing product - either by ID if provided or by SKU
        $existingProduct = null;

        if ($product_id) {
            $existingProduct = \App\Product::find($product_id);
        }

        // If no product_id was provided or the product wasn't found by ID, try by SKU
        if (!$existingProduct) {
            $existingProduct = \App\Product::where('sku', $sku)->first();
        }

        // If product doesn't exist, skip it
        if (!$existingProduct) {
            return [
                'sku' => $sku,
                'product_name' => $product_name,
                'success' => false,
                'error' => 'Product not found in database. Only existing products can be updated.',
                'status' => 'error',
                'product_status' => 'not_found'
            ];
        }

        try {
            // Get API settings from database
            $apiSettings = ApiSetting::first();

            if (!$apiSettings) {
                return [
                    'sku' => $sku,
                    'product_name' => $product_name,
                    'success' => false,
                    'error' => 'API settings not found',
                    'status' => 'error',
                    'product_status' => 'existing'
                ];
            }
            // Make API request with settings from database
            $baseUrl = rtrim($apiSettings->base_url, '/');

            // Check if SKU contains forward slashes
            if (strpos($sku, '/') !== false) {
                // If SKU contains forward slashes, use a different endpoint format
                // Pass SKU as a query parameter instead of in the URL path
                $url = $baseUrl . '/api/product';

                // Prepare query parameters
                $queryParams = ['sku' => $sku]; // Pass SKU as a query parameter

                Log::info("SKU contains forward slash, using query parameter approach ({$currentCount}/{$totalCount})");
            } else {
                // For SKUs without forward slashes, use the original URL format
                $url = $baseUrl . '/api/product/' . $sku;

                // Prepare query parameters
                $queryParams = [];
            }

            // Include product name as a query parameter if available
            // Laravel's HTTP client will automatically URL encode query parameters
            if (!empty($product_name)) {
                $queryParams['product_name'] = $product_name;
                Log::info("Making API request for SKU: {$sku} with product name: {$product_name} ({$currentCount}/{$totalCount})");
            } else {
                Log::info("Making API request for SKU: {$sku} without product name ({$currentCount}/{$totalCount})");
            }

            // Make the API request with headers
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-API-Token' => $apiSettings->token,
                'X-API-Domain' => $apiSettings->domain
            ])->get($url, $queryParams);

            // Log the full response for debugging
            Log::info("API Response for SKU {$sku}:", [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'queryParams' => $queryParams
            ]);

            // If the request fails and we're using the query parameter approach for a SKU with forward slashes,
            // try an alternative approach by replacing forward slashes with a different character
            if (!$response->successful() && strpos($sku, '/') !== false && isset($queryParams['sku'])) {
                Log::info("Query parameter approach failed, trying alternative encoding for SKU with forward slashes ({$currentCount}/{$totalCount})");

                // Try replacing forward slashes with a different character (e.g., '-' or '_')
                $modified_sku = str_replace('/', '-', $sku);
                $url = $baseUrl . '/api/product/' . urlencode($modified_sku);

                // Remove the sku from query parameters since we're now using it in the URL path
                unset($queryParams['sku']);

                // Add a special header to indicate the SKU has been modified
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'X-API-Token' => $apiSettings->token,
                    'X-API-Domain' => $apiSettings->domain,
                    'X-Original-SKU' => $sku // Send the original SKU in a header
                ])->get($url, $queryParams);

                Log::info("Tried alternative approach with modified SKU: {$modified_sku} ({$currentCount}/{$totalCount})");
            }

            if ($response->successful()) {
                $apiResponse = $response->json();

                // Check if the response has the expected structure
                if (isset($apiResponse['success']) && $apiResponse['success'] && isset($apiResponse['data'])) {
                    // Format the data for the product creation form
                    $formattedData = $this->formatProductData($apiResponse['data']);

                    // Clear existing compatibility data for this product to avoid duplicates
                    $this->clearExistingCompatibility($existingProduct);

                    // Add compatibility data to the existing product
                    $addedCount = $this->addCompatibilityToExistingProduct($existingProduct, $formattedData);

                    $message = "Added {$addedCount} compatibility records to product";

                    // Return success result
                    return [
                        'sku' => $sku,
                        'product_name' => $product_name,
                        'success' => true,
                        'product_id' => $existingProduct->id,
                        'source' => 'external_api',
                        'status' => 'success',
                        'product_status' => 'existing',
                        'message' => $message,
                        'compatibility_count' => $addedCount
                    ];
                } else {
                    Log::warning("External API response has unexpected format for SKU: {$sku}");
                    return [
                        'sku' => $sku,
                        'product_name' => $product_name,
                        'success' => false,
                        'error' => 'Invalid API response format',
                        'status' => 'error',
                        'product_status' => 'existing'
                    ];
                }
            } else {
                $errorMessage = "External API request failed for SKU: {$sku} - Status: {$response->status()}";
                Log::info($errorMessage);
                return [
                    'sku' => $sku,
                    'product_name' => $product_name,
                    'success' => false,
                    'error' => $errorMessage,
                    'status' => 'error',
                    'product_status' => 'existing'
                ];
            }
        } catch (\Exception $e) {
            $errorMessage = "Error processing SKU {$sku}: " . $e->getMessage();
            Log::error($errorMessage);
            return [
                'sku' => $sku,
                'product_name' => $product_name,
                'success' => false,
                'error' => $errorMessage,
                'status' => 'error',
                'product_status' => 'existing'
            ];
        }
    }

    /**
     * Clear existing compatibility data for a product
     *
     * @param \App\Product $product The product to clear compatibility for
     * @return void
     */
    private function clearExistingCompatibility($product)
    {
        Log::info("Clearing existing compatibility data for product ID: {$product->id}");
        $product->compatibility()->delete();
    }

    /**
     * Process product data from Excel import (legacy method, kept for backward compatibility)
     * Now delegates to processProductFromExcel for each product
     *
     * @param array $products Array of products with 'sku' and 'product_name' keys
     * @param int $sleepSeconds Number of seconds to sleep between API requests
     * @return array Results of processing each product
     */
    public function processProductsFromExcel(array $products, int $sleepSeconds = 10)
    {
        $results = [];
        $count = 0;
        $total = count($products);

        Log::info("Starting to process {$total} products from Excel import");

        foreach ($products as $product) {
            // // Skip products without SKU
            // if (empty($product['sku'])) {
            //     $results[] = [
            //         'sku' => null,
            //         'product_name' => $product['product_name'] ?? null,
            //         'success' => false,
            //         'error' => 'SKU is required',
            //         'status' => 'skipped'
            //     ];
            //     continue;
            // }

            $count++;

            // Process one product at a time
            $result = $this->processProductFromExcel($product, $count, $total);
            $results[] = $result;

            // Sleep between requests to avoid overwhelming the external API
            if ($count < $total) {
                Log::info("Sleeping for {$sleepSeconds} seconds before next request");
                sleep($sleepSeconds);
            }
        }

        Log::info("Completed processing {$total} products from Excel import");
        return $results;
    }

    /**
     * Add compatibility data to an existing product
     * This method assumes clearExistingCompatibility has been called first
     *
     * @param \App\Product $product The existing product
     * @param array $formattedData The formatted data from the API
     * @return int Number of compatibility records added
     */
    private function addCompatibilityToExistingProduct($product, $formattedData)
    {
        if (!isset($formattedData['compatibility']) || empty($formattedData['compatibility'])) {
            Log::info("No compatibility data to add for product ID: {$product->id}");
            return 0;
        }

        Log::info("Adding compatibility data to product ID: {$product->id}");

        // Track unique combinations to avoid duplicates within the same import batch
        $uniqueRecords = [];
        $addedCount = 0;
        $skippedCount = 0;
        $duplicateCount = 0;

        // Add new compatibility records
        foreach ($formattedData['compatibility'] as $data) {
            // Ensure required fields are present
            if (empty($data['model_id']) || empty($data['brand_category_id'])) {
                Log::warning("Skipping compatibility record with missing model_id or brand_category_id for product ID: {$product->id}");
                $skippedCount++;
                continue;
            }

            // Create a unique key for this compatibility record
            $key = $data['model_id'] . '-' .
                   $data['brand_category_id'] . '-' .
                   ($data['from_year'] ?? 'null') . '-' .
                   ($data['to_year'] ?? 'null');

            // Skip if this exact record already exists in this batch
            if (isset($uniqueRecords[$key])) {
                Log::info("Skipping duplicate compatibility record in batch: {$key}");
                $duplicateCount++;
                continue;
            }

            // Mark this record as processed
            $uniqueRecords[$key] = true;

            // Check if this exact record already exists in the database
            $existingRecord = \App\ProductCompatibility::where('product_id', $product->id)
                ->where('model_id', $data['model_id'])
                ->where('brand_category_id', $data['brand_category_id'])
                ->where(function($query) use ($data) {
                    // Match either both from_year and to_year, or both are null
                    if (isset($data['from_year']) && isset($data['to_year'])) {
                        $query->where('from_year', $data['from_year'])
                              ->where('to_year', $data['to_year']);
                    } else {
                        $query->whereNull('from_year')
                              ->whereNull('to_year');
                    }
                })
                ->first();

            if ($existingRecord) {
                Log::info("Skipping existing compatibility record in database: {$key}");
                $skippedCount++;
                continue;
            }

            // Create compatibility record
            $compatibility = new \App\ProductCompatibility();
            $compatibility->product_id = $product->id;
            $compatibility->model_id = $data['model_id'];
            $compatibility->brand_category_id = $data['brand_category_id'];
            $compatibility->from_year = $data['from_year'] ?? null;
            $compatibility->to_year = $data['to_year'] ?? null;
            $compatibility->save();

            $addedCount++;
        }

        Log::info("Added {$addedCount} new compatibility records, skipped {$skippedCount} existing records, {$duplicateCount} duplicates in batch for product ID: {$product->id}");

        return $addedCount;
    }
}
