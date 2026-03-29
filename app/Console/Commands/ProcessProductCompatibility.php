<?php

namespace App\Console\Commands;

use App\ApiSetting;
use App\Category;
use App\Product;
use App\ProductCompatibility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Repair\Entities\DeviceModel;

class ProcessProductCompatibility extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-compatibility';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process 10 products without compatibility data and fetch compatibility from external service';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Starting product compatibility process...');
            Log::info('Starting scheduled product compatibility updates');

            // Get products without compatibility data (limit to 10)
            $products = $this->getProductsWithoutCompatibility(10);

            if ($products->isEmpty()) {
                $this->info('No products found without compatibility data.');
                Log::info('No products found without compatibility data');
                return 0;
            }

            $this->info('Found ' . $products->count() . ' products to process.');
            Log::info('Found ' . $products->count() . ' products to process');

            // Get API settings
            $apiSettings = ApiSetting::first();
            if (!$apiSettings) {
                $this->error('API settings not found. Cannot proceed.');
                Log::error('API settings not found. Cannot proceed with compatibility updates.');
                return 1;
            }

            $processedCount = 0;
            $successCount = 0;
            $failedCount = 0;

            // Process each product
            foreach ($products as $product) {
                $processedCount++;
                $this->info("Processing product {$processedCount}/{$products->count()}: {$product->name} (SKU: {$product->sku})");
                
                try {
                    $result = $this->processProduct($product, $apiSettings);
                    
                    if ($result['success']) {
                        $successCount++;
                        $this->info("✓ Successfully added {$result['compatibility_count']} compatibility records");
                    } else {
                        $failedCount++;
                        $this->error("✗ Failed: {$result['error']}");
                                    $product->update(['ai_flag' => 1]);
                    }
                    
                    // Sleep for 2 seconds between requests to avoid overwhelming the API
                    if ($processedCount < $products->count()) {
                        sleep(10);
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->error("Error processing product {$product->id}: " . $e->getMessage());
                    Log::error("Error processing product {$product->id}: " . $e->getMessage());
                }
            }

            $this->info("Completed processing {$processedCount} products. Success: {$successCount}, Failed: {$failedCount}");
            Log::info("Completed processing {$processedCount} products. Success: {$successCount}, Failed: {$failedCount}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error in product compatibility process: ' . $e->getMessage());
            Log::error('Error in product compatibility process: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get products without compatibility data
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getProductsWithoutCompatibility($limit = 10)
    {
        return Product::whereDoesntHave('compatibility')
            ->where('sku', '!=', '')
            ->where('ai_flag', 0)
            ->limit($limit)
            ->get();
    }

    /**
     * Process a single product to get compatibility data
     *
     * @param Product $product
     * @param ApiSetting $apiSettings
     * @return array
     */
    private function processProduct($product, $apiSettings)
    {
        $sku = $product->sku;
        $product_name = $product->name;

        try {
            // Make API request with settings from database
            $baseUrl = rtrim($apiSettings->base_url, '/');

            // Check if SKU contains forward slashes
            if (strpos($sku, '/') !== false) {
                // If SKU contains forward slashes, use a different endpoint format
                // Pass SKU as a query parameter instead of in the URL path
                $url = $baseUrl . '/api/product';

                // Prepare query parameters
                $queryParams = ['sku' => $sku]; // Pass SKU as a query parameter

                Log::info("SKU contains forward slash, using query parameter approach");
            } else {
                // For SKUs without forward slashes, use the original URL format
                $url = $baseUrl . '/api/product/' . $sku;

                // Prepare query parameters
                $queryParams = [];
            }

            // Include product name as a query parameter if available
            if (!empty($product_name)) {
                $queryParams['product_name'] = $product_name;
                Log::info("Making API request for SKU: {$sku} with product name: {$product_name}");
            } else {
                Log::info("Making API request for SKU: {$sku} without product name");
            }

            // Make the API request with headers
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-API-Token' => $apiSettings->token,
                'X-API-Domain' => $apiSettings->domain
            ])->get($url, $queryParams);

            // Log the response status
            Log::info("API Response status for SKU {$sku}: {$response->status()}");

            // If the request fails and we're using the query parameter approach for a SKU with forward slashes,
            // try an alternative approach by replacing forward slashes with a different character
            if (!$response->successful() && strpos($sku, '/') !== false && isset($queryParams['sku'])) {
                Log::info("Query parameter approach failed, trying alternative encoding for SKU with forward slashes");

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

                Log::info("Tried alternative approach with modified SKU: {$modified_sku}");
            }

            if ($response->successful()) {
                $apiResponse = $response->json();

                // Check if the response has the expected structure
                if (isset($apiResponse['success']) && $apiResponse['success'] && isset($apiResponse['data'])) {
                    // Format the data for compatibility
                    $formattedData = $this->formatProductData($apiResponse['data']);

                    // Clear existing compatibility data for this product to avoid duplicates
                    $this->clearExistingCompatibility($product);

                    // Add compatibility data to the product
                    $addedCount = $this->addCompatibilityToProduct($product, $formattedData);

                    $message = "Added {$addedCount} compatibility records to product";

                    // Return success result
                    return [
                        'success' => true,
                        'product_id' => $product->id,
                        'source' => 'external_api',
                        'message' => $message,
                        'compatibility_count' => $addedCount
                    ];
                } else {
                    Log::warning("External API response has unexpected format for SKU: {$sku}");
                    return [
                        'success' => false,
                        'error' => 'Invalid API response format',
                    ];
                }
            } else {
                $errorMessage = "External API request failed for SKU: {$sku} - Status: {$response->status()}";
                Log::info($errorMessage);
                return [
                    'success' => false,
                    'error' => $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            $errorMessage = "Error processing SKU {$sku}: " . $e->getMessage();
            Log::error($errorMessage);
            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Format product data from API response
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
     * Get a brand category if it exists in the database
     *
     * @param string $brandName
     * @return \App\Category|null
     */
    private function getBrandCategory($brandName)
    {
        // Try to find existing brand category
        return Category::where('name', $brandName)
            ->where('category_type', 'device')
            ->first();
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
        $model = DeviceModel::where('name', $modelName)
            ->where('device_id', $brandCategoryId)
            ->first();

        if (!$model) {
            // Also try to find the model without requiring an exact brand match
            // This helps when the model exists but might be associated with a different brand variant
            $model = DeviceModel::where('name', $modelName)->first();

            if ($model) {
                Log::info("Found model with different brand: {$modelName}");
            } else {
                Log::info("Model not found in database: {$modelName} for brand category ID: {$brandCategoryId}");
            }
        }

        return $model;
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
     * Add compatibility data to a product
     *
     * @param \App\Product $product The product to add compatibility to
     * @param array $formattedData The formatted data from the API
     * @return int Number of compatibility records added
     */
    private function addCompatibilityToProduct($product, $formattedData)
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
            $existingRecord = ProductCompatibility::where('product_id', $product->id)
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
            $compatibility = new ProductCompatibility();
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
