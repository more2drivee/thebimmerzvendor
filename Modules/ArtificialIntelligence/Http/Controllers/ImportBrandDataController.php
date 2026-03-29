<?php

namespace Modules\ArtificialIntelligence\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Repair\Entities\DeviceModel;
use App\ApiSetting;
use App\DataPermission;

class ImportBrandDataController extends Controller
{
    public function __construct()
    {
        // No dependencies needed
    }

    /**
     * Import Brand and Models data from database or external API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importBrandAndModels(Request $request)
    {
        // Validate the request manually since we're not using a FormRequest
        $validator = Validator::make($request->all(), [
            'brand' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $brandInput = $validatedData['brand'];

        // Get authenticated user from the request if available, otherwise use a default user
        $user = $request->user();

        // If no authenticated user (public API call), use a default user
        if (!$user) {
            $user = DB::table('users')->where('is_admin', 1)->first();
            if (!$user) {
                return response()->json(['error' => 'No admin user found for unauthenticated request'], 500);
            }
        }

        Log::info('Starting importBrandAndModels', [
            'user_id' => $user->id,
            'brand_input' => $brandInput,
            'authenticated' => (bool)$request->user()
        ]);

        try {

            // // Step 2: If not found in database, check if we have permission to access external API
            // $permission = DataPermission::where('permission_key', 'access_external_product_api')
            //     ->where('is_active', 1)
            //     ->first();
            // Step 2: If not found in database, check if we have permission to access external API
            $permission =true;

            if (!$permission) {
                Log::info('External API access denied: Permission not found or inactive');
                return response()->json([
                    'error' => 'Permission denied to access external API',
                    'details' => 'Required permission is not active'
                ], 403);
            }

            // Step 3: Get API settings from database
            $apiSettings = ApiSetting::first();

            if (!$apiSettings) {
                Log::info('External API access failed: API settings not found');
                return response()->json([
                    'error' => 'API settings not configured',
                    'details' => 'API settings are missing in the database'
                ], 500);
            }

            // Step 4: Make API request with settings from database
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Token' => $apiSettings->token,
                'X-API-Domain' => $apiSettings->domain
            ])->get($apiSettings->base_url . 'api/models/import', [
                'brand' => $brandInput
            ]);

            if (!$response->successful()) {
                Log::info('External API request failed: ' . $response->status() . ' - ' . $response->body());
                return response()->json([
                    'error' => 'External API request failed',
                    'details' => 'Status code: ' . $response->status()
                ], 500);
            }

            // Step 5: Process the API response
            $apiData = $response->json();

            // Log the API response for debugging
            Log::info('API response received', [
                'data_preview' => json_encode(array_keys($apiData)),
                'sample' => json_encode(array_slice($apiData, 0, 3))
            ]);

            // Check if data is nested inside a 'data' key
            if (isset($apiData['data']) && is_array($apiData['data'])) {
                $apiData = $apiData['data'];
                Log::info('Extracted nested data from API response');
            }

            // Initialize missing fields with default values
            if (!isset($apiData['brand']) || empty($apiData['brand'])) {
                Log::warning('API response missing brand field, using input brand name');
                $apiData['brand'] = $brandInput;
            }

            if (!isset($apiData['models']) || !is_array($apiData['models'])) {
                Log::warning('API response missing models array, initializing empty array');
                $apiData['models'] = [];
            }

            if (!isset($apiData['obd_codes']) || !is_array($apiData['obd_codes'])) {
                Log::warning('API response missing obd_codes array, initializing empty array');
                $apiData['obd_codes'] = [];
            }

            // Log the initialized data structure
            Log::info('API data after initialization', [
                'brand' => $apiData['brand'],
                'models_count' => count($apiData['models']),
                'obd_codes_count' => count($apiData['obd_codes'])
            ]);

            // Fill in missing fields with defaults if necessary
            if (!isset($apiData['vin_category_code'])) {
                $apiData['vin_category_code'] = '';
            }

            if (!isset($apiData['country_of_origin']) || empty(trim($apiData['country_of_origin']))) {
                // Try to determine country from the first model
                if (!empty($apiData['models']) &&
                    isset($apiData['models'][0]['manufacturing_country']) &&
                    !empty(trim($apiData['models'][0]['manufacturing_country']))) {
                    $apiData['country_of_origin'] = $apiData['models'][0]['manufacturing_country'];
                } else {
                    $apiData['country_of_origin'] = '-';
                }
                Log::info("Set country of origin to: {$apiData['country_of_origin']}");
            }

            if (!isset($apiData['manufacturing_locations'])) {
                // Extract unique manufacturing countries from models
                $locations = [];
                foreach ($apiData['models'] as $model) {
                    if (isset($model['manufacturing_country']) && !empty($model['manufacturing_country'])) {
                        $locations[] = $model['manufacturing_country'];
                    }
                }
                $apiData['manufacturing_locations'] = array_unique($locations);

                // If still empty, use country of origin
                if (empty($apiData['manufacturing_locations'])) {
                    $apiData['manufacturing_locations'] = [$apiData['country_of_origin']];
                }
            }

            // Validate model structure
            if (!empty($apiData['models'])) {
                foreach ($apiData['models'] as $index => $model) {
                    if (!isset($model['name']) || empty(trim($model['name']))) {
                        Log::warning("Model at index {$index} missing name, skipping");
                        unset($apiData['models'][$index]);
                        continue;
                    }

                    if (!isset($model['vin_model_code'])) {
                        $apiData['models'][$index]['vin_model_code'] = '';
                    }

                    if (!isset($model['manufacturing_country']) || empty(trim($model['manufacturing_country']))) {
                        $apiData['models'][$index]['manufacturing_country'] = $apiData['country_of_origin'] ?? '-';
                    }
                }

                // Reindex models array after potential removals
                $apiData['models'] = array_values($apiData['models']);
            } else {
                Log::warning("No models found in API response for brand {$apiData['brand']}");
            }

            // Validate OBD codes structure
            if (!empty($apiData['obd_codes'])) {
                foreach ($apiData['obd_codes'] as $index => $code) {
                    if (!isset($code['code']) || !isset($code['problem_name']) ||
                        empty(trim($code['code'])) || empty(trim($code['problem_name']))) {
                        Log::warning("OBD code at index {$index} missing required fields, skipping");
                        unset($apiData['obd_codes'][$index]);
                        continue;
                    }
                }

                // Reindex OBD codes array after potential removals
                $apiData['obd_codes'] = array_values($apiData['obd_codes']);
            } else {
                Log::warning("No OBD codes found in API response for brand {$apiData['brand']}");
            }

            // Step 6: Save the data to database for future use
            $result = DB::transaction(function () use ($apiData, $user) {
                // Create the main brand
                $mainBrand = $this->updateOrCreateBrand(
                    $apiData['brand'],
                    $apiData['vin_category_code'],
                    $user,
                    $apiData
                );

                // Save countries data to brand_origin_variants table
                // Save countries data to brand_origin_variants table (excluding the country of origin)
                if (isset($apiData['countries']) && is_array($apiData['countries'])) {
                    foreach ($apiData['countries'] as $countryData) {
                        // Only save if the country is not the origin country
                        if (isset($countryData['name']) && (!isset($countryData['is_origin']) || $countryData['is_origin'] === false)) {
                            // Create a variant name using the brand name and country
                            $variantName = trim($apiData['brand']) . ' ' . trim($countryData['name']);

                            DB::table('brand_origin_variants')->insert([
                                'name' => $variantName,
                                'vin_category_code' => trim($countryData['vin_code'] ?? ''),
                                'parent_id' => $mainBrand->id,
                                'country_of_origin' => trim($countryData['name']), // Country name as the origin for this variant
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // Group models by manufacturing country
                $modelsByCountry = [];
                $countryBrands = [];

                // Add main brand to the country brands array
                $countryBrands[$apiData['country_of_origin']] = $mainBrand;

                foreach ($apiData['models'] as $model) {
                    $country = trim($model['manufacturing_country'] ?? '');
                    if (!empty($country)) {
                        $modelsByCountry[$country][] = $model;
                    } else {
                        // Default group for models without country
                        $modelsByCountry[$apiData['country_of_origin']][] = $model;
                    }
                }

                $modelStats = [
                    'added' => 0,
                    'updated' => 0,
                    'skipped' => 0
                ];

                // Process models by country - create a separate brand for each country
                foreach ($modelsByCountry as $country => $models) {
                    // Skip if this is the main country of origin - we already created that brand
                    if ($country === $apiData['country_of_origin']) {
                        $brand = $mainBrand;
                    } else {
                        // Create a country-specific brand name with format "Brand Country"
                        $countryBrandName = "{$apiData['brand']} {$country}";

                        // Generate a country-specific VIN code if possible
                        $countryVinCode = $this->getCountrySpecificVinCode($country, $apiData['vin_category_code']);

                        // Check if this variant already exists in brand_origin_variants
                        $existingVariant = DB::table('brand_origin_variants')
                            ->where('name', $countryBrandName)
                            ->where('parent_id', $mainBrand->id)
                            ->first();

                        if (!$existingVariant) {
                            // Create a new entry in brand_origin_variants
                            $variantId = DB::table('brand_origin_variants')->insertGetId([
                                'name' => $countryBrandName,
                                'vin_category_code' => $countryVinCode,
                                'parent_id' => $mainBrand->id,
                                'country_of_origin' => $country,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            Log::info("Created new brand variant '{$countryBrandName}' with VIN code '{$countryVinCode}'");

                            // Create a virtual brand object for model syncing
                            $brand = new \stdClass();
                            $brand->id = $mainBrand->id; // Use the main brand ID for model association
                            $brand->name = $countryBrandName;
                            $brand->vin_category_code = $countryVinCode;
                            $brand->variant_id = $variantId; // Store the variant ID for reference
                            $brand->country_of_origin = $country; // Store the country for filtering models
                        } else {
                            // Update the existing variant
                            DB::table('brand_origin_variants')
                                ->where('id', $existingVariant->id)
                                ->update([
                                    'vin_category_code' => $countryVinCode,
                                    'updated_at' => now(),
                                ]);


                            // Create a virtual brand object for model syncing
                            $brand = new \stdClass();
                            $brand->id = $mainBrand->id; // Use the main brand ID for model association
                            $brand->name = $countryBrandName;
                            $brand->vin_category_code = $countryVinCode;
                            $brand->variant_id = $existingVariant->id; // Store the variant ID for reference
                            $brand->country_of_origin = $country; // Store the country for filtering models
                        }

                        // Store the country brand for later reference
                        $countryBrands[$country] = $brand;
                    }

                    // Sync models for this country's brand
                    $stats = $this->syncModels($brand, $models, $user);

                    // Aggregate stats
                    $modelStats['added'] += $stats['added'];
                    $modelStats['updated'] += $stats['updated'];
                    $modelStats['skipped'] += $stats['skipped'];
                }

                // Collect all brand IDs for the OBD group - we only need the main brand ID
                // since all variants are now stored in brand_origin_variants
                $allBrandIds = [$mainBrand->id];

                // Sync OBD codes with all country-specific brands in the same group
                $obdStats = $this->syncObdCodes($mainBrand, $apiData['obd_codes'], $allBrandIds);

                return [
                    'brand' => $mainBrand,
                    'modelStats' => $modelStats,
                    'obdStats' => $obdStats,
                    'countryBrands' => $countryBrands,
                    'countrySpecificBrands' => array_keys($modelsByCountry),
                ];
            });

            $message = sprintf(
                "Import successful for brand '%s' (VIN: %s). Models: %d added, %d updated, %d skipped. OBD Codes: %d added, %d skipped.",
                $result['brand']->name,
                $result['brand']->vin_category_code ?? 'N/A',
                $result['modelStats']['added'],
                $result['modelStats']['updated'],
                $result['modelStats']['skipped'],
                $result['obdStats']['added'],
                $result['obdStats']['skipped']
            );

            Log::info('Import completed successfully', [
                'user_id' => $user->id,
                'brand_id' => $result['brand']->id,
                'brand_name' => $result['brand']->name,
                'stats' => array_merge($result['modelStats'], $result['obdStats']),
                'source' => 'external_api'
            ]);

            // Format the response to match the expected structure in the blade file
            return response()->json([
                'success' => $response->json('success', false), // Use success from API response
                'message' => $response->json('message', $message), // Use message from API response, fallback to generated message
                'data' => [
                    'brand' => $result['brand']->name,
                    'vin_category_code' => $result['brand']->vin_category_code,
                    'country_of_origin' => $apiData['country_of_origin'] ?? 'N/A',
                    'manufacturing_locations' => $apiData['manufacturing_locations'] ?? [],
                    'models' => $apiData['models'],
                    'obd_codes' => $apiData['obd_codes'],
                    'models_count' => count($apiData['models']),
                    'obd_codes_count' => count($apiData['obd_codes'])
                ],
                'review_id' => $response->json('review_id'), // Use review_id from API response
                'source' => $response->json('source', 'external_api'), // Use source from API response, fallback to external_api
                'needs_review' => $response->json('needs_review', true), // Use needs_review from API response, fallback to true
                'ai_model' => $response->json('ai_model'), // Use ai_model from API response
                // Additional fields for the dashboard
                'country_specific_brands' => $result['countrySpecificBrands'] ?? [],
                'added_models' => $result['modelStats']['added'],
                'updated_models' => $result['modelStats']['updated'],
                'skipped_models' => $result['modelStats']['skipped'],
                'added_obd_codes' => $result['obdStats']['added'],
                'skipped_obd_codes' => $result['obdStats']['skipped']
            ], $response->status()); // Use status code from API response

        } catch (ValidationException $e) {
            Log::error('Validation Failed', [
                'error' => $e->errors(),
                'user_id' => $user->id,
                'brand_input' => $brandInput,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid data format received from API.',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Exception during importBrandAndModels', [
                'error_message' => $e->getMessage(),
                'error_trace' => Str::limit($e->getTraceAsString(), 1000), // Limit trace length
                'user_id' => $user->id,
                'brand_input' => $brandInput,
            ]);

            // Provide a more detailed error message to help with debugging
            $errorDetails = app()->environment('production')
                ? 'An unexpected error occurred during the import process. Please try again later.'
                : $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => 'Import process failed',
                'error' => $errorDetails,
                'brand_input' => $brandInput
            ], 500);
        }
    }



    /**
     * Update or create a brand.
     *
     * @param string $brandName
     * @param string $vinCategoryCode
     * @param \App\User $user
     * @param array $parsedData Additional data about the brand (optional)
     * @return Category
     */
    private function updateOrCreateBrand(string $brandName, string $vinCategoryCode, $user, array $parsedData = []): Category
    {
        $brandName = trim($brandName);
        $vinCategoryCode = trim($vinCategoryCode);

        // Extract country of origin from parsed data if available
        $countryOfOrigin = $parsedData['country_of_origin'] ?? null;

        // Don't append country name to brand name anymore
        $brandNameToUse = $brandName;

        // For the main brand (like "kia"), save it in the categories table
        $existingBrand = Category::where('name', $brandNameToUse)->first();
        if ($existingBrand) {
            // Only update VIN code if this is the origin country brand
            // or if the existing VIN code is empty
            if (empty($existingBrand->vin_category_code) ||
                ($countryOfOrigin && $countryOfOrigin === $parsedData['country_of_origin'])) {
                $existingBrand->vin_category_code = $vinCategoryCode;
            }

            // Update country of origin if provided
            if ($countryOfOrigin) {
                $existingBrand->country_of_origin = $countryOfOrigin;
            }

            $existingBrand->save();
            Log::info("Updated existing brand '{$brandNameToUse}' with VIN category code '{$vinCategoryCode}'");
            return $existingBrand;
        } else {
            $brandData = [
                'name'              => $brandNameToUse,
                'business_id'       => $user->business_id,
                'category_type'     => 'device',
                'vin_category_code' => $vinCategoryCode,
                'created_by'        => $user->id,
                'parent_id'         => 0, // Ensure it's a top-level category
            ];

            // Add country of origin if provided
            if ($countryOfOrigin) {
                $brandData['country_of_origin'] = $countryOfOrigin;
            }

            $brand = Category::create($brandData);
            Log::info("Created new brand '{$brandNameToUse}' with VIN category code '{$vinCategoryCode}'");
            return $brand;
        }
    }

    /**
     * Sync models for a brand.
     *
     * @param Category|object $brand - Can be a Category instance or a stdClass for variants
     * @param array $models
     * @param \App\User $user
     * @return array
     */
    private function syncModels($brand, array $models, $user): array
    {
        $added = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($models as $model) {
            $modelName = trim($model['name']);
            $vinModelCode = trim($model['vin_model_code'] ?? '');

            // Convert "N/A" to empty string
            if (strtoupper($vinModelCode) === 'N/A') {
                $vinModelCode = '';
            }

            if (empty($modelName)) {
                $skipped++;
                continue;
            }

            // Get the manufacturing country for this model
            $manufacturingCountry = trim($model['manufacturing_country'] ?? '');

            // Check if this is a variant brand (has variant_id property)
            $isVariant = isset($brand->variant_id);

            // For variants, we need to check if the model's manufacturing country matches the variant's country
            if ($isVariant) {
                // Skip models that don't match this variant's country
                if ($manufacturingCountry && $manufacturingCountry !== $brand->country_of_origin) {
                    Log::info("Skipped model '{$modelName}' - country mismatch for variant brand");
                    $skipped++;
                    continue;
                }
            }

            $existingModel = DeviceModel::where('name', $modelName)->first();
            if ($existingModel) {
                // Only update if the new code is not empty or the existing one is empty
                if (!empty($vinModelCode) || empty($existingModel->vin_model_code)) {
                    $existingModel->vin_model_code = $vinModelCode;
                    $existingModel->save();
                    $updated++;
                    Log::info("Updated existing model '{$modelName}' with VIN model code '{$vinModelCode}'");
                } else {
                    // Model exists but we're not updating the code
                    $skipped++;
                    Log::info("Skipped updating model '{$modelName}' - existing code preserved");
                }
            } else {
                // Create a new model
                DeviceModel::create([
                    'name'           => $modelName,
                    'device_id'      => $brand->id, // Always use the brand ID (for variants, this is the main brand ID)
                    'business_id'    => $user->business_id,
                    'vin_model_code' => $vinModelCode,
                    'created_by'     => $user->id,
                ]);
                $added++;
                Log::info("Created new model '{$modelName}' with VIN model code '{$vinModelCode}'");
            }
        }

        return [
            'added' => $added,
            'updated' => $updated,
            'skipped' => $skipped
        ];
    }

    /**
     * Sync OBD codes for a brand.
     *
     * @param Category $brand
     * @param array $obdCodes
     * @param array $allBrandIds All brand IDs to include in the OBD group
     * @return array
     */
    private function syncObdCodes(Category $brand, array $obdCodes, array $allBrandIds = []): array
    {
        $added = 0;
        $skipped = 0;

        // If no additional brand IDs provided, use just the main brand
        if (empty($allBrandIds)) {
            $allBrandIds = [$brand->id];
        }

        // Find or create OBD group for this brand family
        $obdGroup = DB::table('obd_groups')
            ->whereJsonContains('brand_id', $brand->id)
            ->first();

        if (!$obdGroup) {
            $obdGroupId = DB::table('obd_groups')->insertGetId([
                'brand_id' => json_encode(array_values($allBrandIds)), // Use array_values to ensure numeric indexing
                'name'     => "OBD Group for {$brand->name}",
                // 'created_at' => now(),
                // 'updated_at' => now()
            ]);
        } else {
            // Update the brand_id field to include all country-specific brands
            $obdGroupId = $obdGroup->id;

            // Get existing brand IDs and merge with new ones
            $existingBrandIds = json_decode($obdGroup->brand_id, true) ?? [];

            // If existing brand IDs is an associative array, extract just the values
            if (is_array($existingBrandIds) && array_keys($existingBrandIds) !== range(0, count($existingBrandIds) - 1)) {
                $existingBrandIds = array_values($existingBrandIds);
            }

            // Merge and ensure unique values
            $mergedBrandIds = array_unique(array_merge($existingBrandIds, array_values($allBrandIds)));

            // Update the brand_id field with all brand IDs as a simple array
            DB::table('obd_groups')
                ->where('id', $obdGroupId)
                ->update([
                    'brand_id' => json_encode(array_values($mergedBrandIds))
                ]);
        }

        // Process OBD codes
        foreach ($obdCodes as $obdCode) {
            $code = trim($obdCode['code']);
            $problemName = trim($obdCode['problem_name']);

            if (empty($code) || empty($problemName)) {
                $skipped++;
                continue;
            }

            // Check if code already exists in this group
            $existingCode = DB::table('obd_codes')
                ->where('obd_group_id', $obdGroupId)
                ->where('code', $code)
                ->first();

            if ($existingCode) {
                $skipped++;
                continue;
            }

            // Add new code
            DB::table('obd_codes')->insert([
                'obd_group_id' => $obdGroupId,
                'code' => $code,
                'problem_name' => $problemName,
                // 'created_at' => now(),
                // 'updated_at' => now()
            ]);
            $added++;
        }

        return [
            'added' => $added,
            'skipped' => $skipped
        ];
    }







    /**
     * Get a country-specific VIN code based on country name
     *
     * @param string $country
     * @param string $defaultVinCode
     * @return string
     */
    private function getCountrySpecificVinCode(string $country, string $defaultVinCode): string
    {
        // Map of countries to their typical first VIN character
        $countryVinMap = [
            'Japan' => 'J',
            'South Korea' => 'K',
            'China' => 'L',
            'India' => 'M',
            'United States' => '1',
            'Canada' => '2',
            'Mexico' => '3',
            'Australia' => '6',
            'New Zealand' => '7',
            'United Kingdom' => 'S',
            'Germany' => 'W',
            'Italy' => 'Z',
            'France' => 'V',
            'Sweden' => 'Y',
            'Spain' => 'V',
            'Brazil' => '9',
            'Thailand' => 'M',
            'South Africa' => 'A'
        ];

        // If we have a specific country code, use it as the first character
        if (isset($countryVinMap[$country])) {
            // Keep the rest of the original VIN code if possible
            if (strlen($defaultVinCode) > 1) {
                return $countryVinMap[$country] . substr($defaultVinCode, 1);
            } else {
                return $countryVinMap[$country] . substr($defaultVinCode, 0, 2);
            }
        }

        // If no specific mapping, return the original code
        return $defaultVinCode;
    }


}
