<?php

namespace Modules\ArtificialIntelligence\Http\Controllers;

use App\ApiSetting;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
// use App\DataPermission;

class VINLookupController extends Controller
{
    /**
     * VIN year code mapping (10th character)
     * @var array
     */
    protected $vinYearMap = [
        'Y' => 2000, '1' => 2001, '2' => 2002, '3' => 2003, '4' => 2004, '5' => 2005,
        '6' => 2006, '7' => 2007, '8' => 2008, '9' => 2009, 'A' => 2010, 'B' => 2011,
        'C' => 2012, 'D' => 2013, 'E' => 2014, 'F' => 2015, 'G' => 2016, 'H' => 2017,
        'J' => 2018, 'K' => 2019, 'L' => 2020, 'M' => 2021, 'N' => 2022, 'P' => 2023,
        'R' => 2024, 'S' => 2025, 'T' => 2026, 'V' => 2027, 'W' => 2028, 'X' => 2029,
        '0' => 2030,
    ];

    public function __construct()
    {
        // No dependencies needed
    }

    /**
     * Lookup chassis/VIN number information
     * First checks database for matches, then uses external API if needed
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookupChassis(Request $request)
    {
        // Handle both GET and POST requests
        $rawChassisInput = $request->input('chassis_number');



        $normalizedVin = null;

        try {
            // --- 1. Input Normalization and Validation ---
            $normalizedVin = strtoupper(trim((string)$rawChassisInput));

            $validator = Validator::make(['chassis_number' => $normalizedVin], [
                'chassis_number' => [
                    'required',
                    'string',
                    'size:17',
                    'regex:/^[A-HJ-NPR-Z0-9]{17}$/'
                ],
            ], [
                'chassis_number.required' => 'VIN/Chassis number is required.',
                'chassis_number.size' => 'VIN/Chassis number must be exactly 17 characters long.',
                'chassis_number.regex' => 'VIN/Chassis number contains invalid characters or format.',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed for chassis lookup', [
                    'errors' => $validator->errors(),
                    'input' => $rawChassisInput,
                    'normalized' => $normalizedVin
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $vin = $normalizedVin;
            Log::info("Starting chassis lookup", ['vin' => $vin]);

            // --- 2. Decode VIN Components ---
            $manufacturerCode = substr($vin, 0, 3);      // Full WMI (3 chars)
            $manufacturerCode2 = substr($vin, 1, 2);     // Last 2 chars of WMI (for partial matching)
            $countryCode = substr($vin, 0, 1);           // First char (country code)
            $modelCodeCandidate = substr($vin, 3, 5);    // VDS Candidate (chars 4-8)
            $yearCode = $vin[9];                         // 10th character

            Log::debug("Decoded VIN components", [
                'wmi_full' => $manufacturerCode,
                'wmi_partial' => $manufacturerCode2,
                'country_code' => $countryCode,
                'vds_candidate' => $modelCodeCandidate,
                'year_code' => $yearCode
            ]);

            // --- 3. Determine Year from VIN ---
            $year = $this->vinYearMap[$yearCode] ?? null;
            if (!$year) {
                Log::warning("Could not determine year from code", ['year_code' => $yearCode, 'vin' => $vin]);
            }

            // --- 4. Database Lookup: Brand (direct match only) ---
            // First try exact match on vin_category_code in categories table
            $brand = DB::table('categories')
                ->where('vin_category_code', $manufacturerCode)
                ->where('category_type', 'device')
                ->select('id', 'name', 'vin_category_code')
                ->first();

            // If no match found in categories, search in brand_origin_variants table
            if (!$brand) {
                Log::info("Brand not found in categories table, searching in brand_origin_variants", [
                    'wmi' => $manufacturerCode
                ]);

                $brandVariant = DB::table('brand_origin_variants')
                    ->where('vin_category_code', $manufacturerCode)
                    ->select('id', 'name', 'vin_category_code', 'parent_id')
                    ->first();

                if ($brandVariant) {
                    // If found in variants, get the parent category
                    $brand = DB::table('categories')
                        ->where('id', $brandVariant->parent_id)
                        ->where('category_type', 'device')
                        ->select('id', 'name', 'vin_category_code')
                        ->first();

                    Log::info("Brand variant found, using parent category", [
                        'variant_id' => $brandVariant->id,
                        'variant_name' => $brandVariant->name,
                        'parent_id' => $brandVariant->parent_id,
                        'parent_name' => $brand->name ?? null
                    ]);
                }
            }

            Log::info("Brand lookup result", [
                'brand_found' => $brand ? true : false,
                'brand_id' => $brand->id ?? null,
                'brand_name' => $brand->name ?? null,
                'brand_vin_code' => $brand->vin_category_code ?? null
            ]);

            $brandId = $brand->id ?? null;
            $modelId = null;

            // --- 5. Database Lookup: Model (Prioritized Matching) ---
            if ($brandId) {
                Log::info("Brand found in database", ['brand_id' => $brandId, 'brand_name' => $brand->name ?? null]);

                // Strategy 1: Try exact match first with adjusted candidate
                Log::debug("Attempting exact model lookup", ['brand_id' => $brandId, 'vds_candidate' => $modelCodeCandidate]);
                $model = DB::table('repair_device_models')
                    ->where('device_id', $brandId)
                    ->where('vin_model_code', $modelCodeCandidate)
                    ->select('id', 'name', 'vin_model_code')
                    ->first();

                if ($model) {
                    $modelId = $model->id;
                    Log::info("Exact model match found", [
                        'model_id' => $modelId,
                        'model_name' => $model->name,
                        'matched_code' => $model->vin_model_code
                    ]);
                } else {
                    // Strategy 2: Fallback to prefix match if exact match failed
                    Log::info("Exact model match failed. Attempting prefix match.", ['brand_id' => $brandId]);

                    $model = DB::table('repair_device_models')
                        ->where('device_id', $brandId)
                        ->whereNotNull('vin_model_code')
                        ->where('vin_model_code', '!=', '')
                        ->whereRaw('? LIKE CONCAT(vin_model_code, "%")', [$modelCodeCandidate])
                        ->select('id', 'name', 'vin_model_code')
                        ->orderByRaw('LENGTH(vin_model_code) DESC')
                        ->first();

                    if ($model) {
                        $modelId = $model->id;
                        Log::info("Prefix model match found", [
                            'model_id' => $modelId,
                            'model_name' => $model->name,
                            'matched_code' => $model->vin_model_code
                        ]);
                    } else {
                        Log::warning("No model found in database", ['brand_id' => $brandId, 'vds_candidate' => $modelCodeCandidate]);
                    }
                }
            } else {
                Log::warning("Brand not found in database", ['wmi' => $manufacturerCode, 'vin' => $vin]);
            }

            // --- 6. Call external API if:
            // a) Brand found but model not found, OR
            // b) Neither brand nor model found
            if (($brandId && !$modelId) || (!$brandId)) {
                $logMessage = $brandId ? "Brand found but model not found" : "Neither brand nor model found";
                Log::info($logMessage . ", checking external API", [
                    'vin' => $vin,
                    'brand_id' => $brandId,
                    'brand_name' => $brand->name ?? null
                ]);

                // Get API settings, default business ID and a valid user ID from database
                $user = Auth::user();
                $apiSettings = ApiSetting::first();
                $defaultBusinessId = $user->business_id;
                $defaultUserId = $user->id;

                if (!$apiSettings) {
                    Log::info('External API access failed: API settings not found');
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'year' => $year,
                            'source' => 'database_only',
                            'ai_analysis' => []
                        ],
                        'message' => 'Limited data available from database only. API settings are not configured.'
                    ], 200);
                }

                // Make API request with settings from database
                $baseUrl = rtrim($apiSettings->base_url, '/');
                $url = $baseUrl . '/api/vin/lookup';

                // Prepare request parameters
                $requestParams = [
                    'chassis_number' => $vin
                ];

                // If brand is found but model is not, include brand information in the request
                if ($brandId && !$modelId && isset($brand->name)) {
                    $requestParams['brand'] = $brand->name;
                }

                // Log what we're sending to the external API
                Log::info("Sending data to external API", [
                    'vin' => $vin,
                    'includes_brand' => isset($requestParams['brand']),
                    'brand_name' => $requestParams['brand'] ?? null,
                    'request_params' => $requestParams
                ]);

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-API-Token' => $apiSettings->token,
                    'X-API-Domain' => $apiSettings->domain
                ])->get($url, $requestParams);

                if ($response->successful()) {
                    $apiResponse = $response->json();
                    Log::debug('External API response', ['response' => $apiResponse]);

                    // Handle direct API response format (without ai_analysis wrapper)
                    if (isset($apiResponse['success']) && $apiResponse['success'] === true) {
                        $aiData = $apiResponse['data'] ?? [];
                        
                        // Check if we have the direct format with brand_name and model_name
                        if (isset($aiData['brand_name']) || isset($aiData['model_name'])) {
                            $brandName = $aiData['brand_name'] ?? null;
                            $modelName = $aiData['model_name'] ?? null;
                            $brandVinCode = $aiData['brand_vin_code'] ?? null;
                            $modelVinCode = $aiData['model_vin_code'] ?? null;
                            
                            // Always use the year from API if available, fallback to vinYearMap
                            $year = $aiData['year'] ?? $year;
                            
                            $brandCreated = false;
                            $modelCreated = false;
                            
                            // Find or create brand record
                            if ($brandName) {
                                $brand = DB::table('categories')
                                    ->where('name', 'like', '%' . $brandName . '%')
                                    ->where('category_type', 'device')
                                    ->first();

                                if (!$brand) {
                                    // Create new brand record
                                    $brandId = DB::table('categories')->insertGetId([
                                        'name' => $brandName,
                                        'category_type' => 'device',
                                        'vin_category_code' => $brandVinCode,
                                        'business_id' => $defaultBusinessId,
                                        'created_by' => $defaultUserId,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]);
                                    $brandCreated = true;
                                    Log::info("Created new brand from VIN lookup", [
                                        'brand_id' => $brandId,
                                        'brand_name' => $brandName
                                    ]);
                                } else {
                                    $brandId = $brand->id;
                                    // Update VIN code if it's empty
                                    if (empty($brand->vin_category_code) && $brandVinCode) {
                                        DB::table('categories')
                                            ->where('id', $brandId)
                                            ->update(['vin_category_code' => $brandVinCode]);
                                    }
                                }

                                // Find or create model record if we have a brand
                                if ($modelName && $brandId) {
                                    $model = DB::table('repair_device_models')
                                        ->where('device_id', $brandId)
                                        ->where('name', 'like', '%' . $modelName . '%')
                                        ->first();

                                    if (!$model) {
                                        // Create new model record
                                        $modelId = DB::table('repair_device_models')->insertGetId([
                                            'name' => $modelName,
                                            'device_id' => $brandId,
                                            'vin_model_code' => $modelVinCode,
                                            'business_id' => $defaultBusinessId,
                                            'created_by' => $defaultUserId,
                                            'created_at' => now(),
                                            'updated_at' => now()
                                        ]);
                                        $modelCreated = true;
                                        Log::info("Created new model from VIN lookup", [
                                            'model_id' => $modelId,
                                            'model_name' => $modelName,
                                            'brand_id' => $brandId
                                        ]);
                                    } else {
                                        $modelId = $model->id;
                                        // Update VIN code if it's empty
                                        if (empty($model->vin_model_code) && $modelVinCode) {
                                            DB::table('repair_device_models')
                                                ->where('id', $modelId)
                                                ->update(['vin_model_code' => $modelVinCode]);
                                        }
                                    }
                                }
                            }

                            // Prepare AI analysis data for response
                            $aiAnalysis = [
                                'brand_name' => $brandName,
                                'model_name' => $modelName,
                                'brand_vin_code' => $brandVinCode,
                                'model_vin_code' => $modelVinCode,
                                'year' => $year
                            ];

                            // --- 7. Save country_of_origin as brand origin variant if it doesn't exist ---
                            $variantId = null;
                            $variantCreated = false;
                            if ($brandId && isset($aiData['country_of_origin']) && !empty($aiData['country_of_origin'])) {
                                $countryOfOrigin = trim((string)$aiData['country_of_origin']);
                                
                                // Check if this country variant already exists for this brand (case/whitespace-insensitive)
                                $existingVariant = DB::table('brand_origin_variants')
                                    ->where('parent_id', $brandId)
                                    ->whereRaw('LOWER(TRIM(country_of_origin)) = LOWER(TRIM(?))', [$countryOfOrigin])
                                    ->first();
                                
                                if ($existingVariant) {
                                    $variantId = $existingVariant->id;
                                    Log::info("Brand origin variant already exists", [
                                        'variant_id' => $variantId,
                                        'brand_id' => $brandId,
                                        'country_of_origin' => $countryOfOrigin
                                    ]);
                                } else {
                                    // Create new brand origin variant
                                    $variantId = DB::table('brand_origin_variants')->insertGetId([
                                        'name' => $brandName . ' (' . $countryOfOrigin . ')',
                                        'parent_id' => $brandId,
                                        'country_of_origin' => $countryOfOrigin,
                                        'vin_category_code' => $brandVinCode ?? null,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]);
                                    $variantCreated = true;
                                    Log::info("Created new brand origin variant from AI response", [
                                        'variant_id' => $variantId,
                                        'brand_id' => $brandId,
                                        'country_of_origin' => $countryOfOrigin,
                                        'brand_name' => $brandName
                                    ]);
                                }
                            }

                            // Return response with brand and model IDs
                            $responseData = [
                                'success' => true,
                                'data' => [
                                    'year' => $year,
                                    'brand_id' => $brandId ?? null,
                                    'model_id' => $modelId ?? null,
                                    'brand_name' => $brandName,
                                    'model_name' => $modelName,
                                    'brand_vin_code' => $brandVinCode,
                                    'model_vin_code' => $modelVinCode,
                                    'country_of_origin' => $aiData['country_of_origin'] ?? null,
                                  
                                    'variant_name' => ($variantId && $brandName && isset($aiData['country_of_origin'])) ? $brandName . ' (' . $aiData['country_of_origin'] . ')' : null,
                                    'variant_vin_code' => $brandVinCode ?? null,
                                    'variant_country_of_origin' => $aiData['country_of_origin'] ?? null,
                                    'brand_created' => $brandCreated,
                                    'model_created' => $modelCreated,
                                    'variant_created' => $variantCreated,
                                    'ai_analysis' => $aiAnalysis,
                                    'source' => 'database_and_ai',
                                    'needs_review' => $apiResponse['needs_review'] ?? false,
                                    'review_id' => $apiResponse['review_id'] ?? null,
                                    'ai_model' => $apiResponse['ai_model'] ?? null
                                ],
                                'source' => $apiResponse['source'] ?? 'ai',
                                'needs_review' => $apiResponse['needs_review'] ?? false,
                                'review_id' => $apiResponse['review_id'] ?? null,
                                'ai_model' => $apiResponse['ai_model'] ?? null
                            ];
                            
                            Log::info("External API lookup completed", ['vin' => $vin, 'result' => $responseData]);
                            return response()->json($responseData, 200);
                        }
                        // Handle the existing ai_analysis format
                        else if (isset($apiResponse['data']) && isset($apiResponse['data']['ai_analysis'])) {
                            // Existing code for handling ai_analysis format
                            $aiAnalysis = $apiResponse['data']['ai_analysis'];
                            $brandName = $aiAnalysis['brand_name'] ?? null;
                            $modelName = $aiAnalysis['model_name'] ?? null;
                            $brandVinCode = $aiAnalysis['brand_vin_code'] ?? null;
                            $modelVinCode = $aiAnalysis['model_vin_code'] ?? null;
                            
                            // Rest of your existing code for handling ai_analysis format
                            // ...
                        }
                    }
                }
            }

            // --- 7. Prepare and Return Response ---
            // Format the response with a consistent structure
            $aiAnalysis = [];

            // If we have brand and model IDs, get their names
            if ($brandId) {
                $brand = DB::table('categories')->where('id', $brandId)->first();
                if ($brand) {
                    $aiAnalysis['brand_name'] = $brand->name;
                    $aiAnalysis['brand_vin_code'] = $brand->vin_category_code ?? '';
                }
            }

            if ($modelId) {
                $model = DB::table('repair_device_models')->where('id', $modelId)->first();
                if ($model) {
                    $aiAnalysis['model_name'] = $model->name;
                    $aiAnalysis['model_vin_code'] = $model->vin_model_code ?? '';
                }
            }

            // Add year if available
            if ($year) {
                $aiAnalysis['year'] = $year;
            }

            // Check if we found a brand variant to include variant information
            $variantInfo = [];
            if (isset($brandVariant) && $brandVariant) {
                $variantInfo = [
                    'variant_id' => $brandVariant->id,
                    'variant_name' => $brandVariant->name,
                    'variant_vin_code' => $brandVariant->vin_category_code,
                    'variant_country_of_origin' => $brandVariant->country_of_origin ?? 'Unknown'
                ];
                // Merge variant info into ai_analysis
                $aiAnalysis = array_merge($aiAnalysis, $variantInfo);
            }

            // Create response with standardized structure
            $responseData = [
                'success' => true,
                'data' => [
                    'year' => $year,
                    'ai_analysis' => $aiAnalysis,
                    'source' => 'database_variant',
                    'needs_review' => false,
                    'review_id' => null,
                    'ai_model' => null
                ]
            ];

            // Add brand_id and model_id if available
            if ($brandId) {
                $responseData['data']['brand_id'] = $brandId;
                $responseData['data']['brand_name'] = $aiAnalysis['brand_name'] ?? null;
                $responseData['data']['brand_vin_code'] = $aiAnalysis['brand_vin_code'] ?? null;
            }

            if ($modelId) {
                $responseData['data']['model_id'] = $modelId;
                $responseData['data']['model_name'] = $aiAnalysis['model_name'] ?? null;
                $responseData['data']['model_vin_code'] = $aiAnalysis['model_vin_code'] ?? null;
            }

            // Add country_of_origin if available from variant
            if (isset($variantInfo['variant_country_of_origin'])) {
                $responseData['data']['country_of_origin'] = $variantInfo['variant_country_of_origin'];
            }

            Log::info("Chassis lookup completed", ['vin' => $vin, 'result' => $responseData]);

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            // --- 8. Error Handling ---
            Log::error("Error in chassis lookup: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'vin_input' => $normalizedVin ?? $rawChassisInput ?? 'not_provided',
                'trace_preview' => Str::limit($e->getTraceAsString(), 500)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while analyzing the chassis information.'
            ], 500);
        }
    }


}
