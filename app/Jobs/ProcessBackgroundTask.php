<?php

namespace App\Jobs;

use App\Brands;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Unit;
use App\Utils\ProductUtil;
use App\Variation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Repair\Entities\DeviceModel;
use App\Utils\ModuleUtil;

class ProcessBackgroundTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    protected $productUtil;
    protected $moduleUtil;
    private $barcode_types;

    public function __construct($data = [])
    {
        $this->data = $data;
        $this->productUtil = new ProductUtil();
        $this->moduleUtil = new ModuleUtil();
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    public function handle()
    {
        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            // Get business details
            $business_id = $this->data['business_id'] ?? session()->get('user.business_id');
            $user_id = $this->data['user_id'] ?? session()->get('user.id');
            
            if (empty($business_id) || empty($user_id)) {
                Log::error('Business ID or User ID not provided for ProcessBackgroundTask');
                return false;
            }

            // Get SKU from the data
            $sku = $this->data['sku'] ?? null;
            $product_name = $this->data['product_name'] ?? null;
            
            if (empty($sku)) {
                Log::error('SKU is required for ProcessBackgroundTask');
                return false;
            }

            // Get product with SKU
            $product = Product::where('sku', $sku)
                            ->where('business_id', $business_id)
                            ->first();
            
            if (!$product) {
                Log::error('Product not found with SKU: ' . $sku);
                return false;
            }

            // Update product with AI details
    
            $result = $this->updateProductWithAI($product, $sku, $product_name);

            if ($result) {
                // Ensure ai_flag is set to false after successful update
                $product->ai_flag = false;
                $product->save();
            }

            Log::info('Successfully processed product with SKU: ' . $sku);
            return true;

        } catch (\Exception $e) {
            Log::error('Error in ProcessBackgroundTask: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update existing product with AI details
     */
    private function updateProductWithAI($product, $sku, $product_name = null)
    {
        try {
            // Get AI product details
            $ai_details = $this->getProductDetailsFromAI($sku, $product_name);
            if (!$ai_details) {
                Log::error('Could not get AI details for SKU: ' . $sku);
                return false;
            }

            // Update product with AI details
            $product->name = $ai_details['product_name'];

            // Update manufacturing years
            if (isset($ai_details['manufacturing_year'])) {
                $product->manufacturing_year = $ai_details['manufacturing_year'];
            }

            // Update brand categories
            if (isset($ai_details['brand_category'])) {
                $product->brand_category = $ai_details['brand_category'];
            }

            // Set ai_flag to false to indicate this product has been processed
            $product->ai_flag = false;
            
            // Save the updated product
            $product->save();
            
            Log::info('Successfully updated product with AI details', [
                'sku' => $sku,
                'product_id' => $product->id
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error updating product with AI: ' . $e->getMessage(), [
                'sku' => $sku,
                'product_id' => $product->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Create new product with AI details
     */
    private function createProductWithAI($sku, $product_name = null, $business_id, $user_id)
    {
        try {
            // Get AI product details
            $ai_details = $this->getProductDetailsFromAI($sku, $product_name);
            if (!$ai_details) {
                Log::error('Could not get AI details for SKU: ' . $sku);
                return false;
            }

            // Create new product with basic details
            $product = new Product();
            $product->business_id = $business_id;
            $product->created_by = $user_id;
            $product->name = $ai_details['product_name'];
            $product->sku = $sku;
            
            // Set manufacturing years
            if (isset($ai_details['manufacturing_year'])) {
                $product->manufacturing_year = $ai_details['manufacturing_year'];
            } else {
                $product->manufacturing_year = json_encode([]);
            }

            // Set brand categories
            if (isset($ai_details['brand_category'])) {
                $product->brand_category = $ai_details['brand_category'];
            } else {
                $product->brand_category = json_encode([]);
            }

            // Set default values for required fields
            $product->type = 'single';
            
            // Get default unit
            $unit = Unit::where('business_id', $business_id)->first();
            $product->unit_id = $unit ? $unit->id : 1;
            
            $product->tax_type = 'exclusive';
            $product->enable_stock = 1;
            $product->alert_quantity = 5;
            $product->barcode_type = 'C128';
            
            // Set ai_flag to false to indicate this product has been processed
            $product->ai_flag = false;
            
            // Save the product
            $product->save();
            
            // If auto generate sku generate new sku
            if ($product->sku == ' ') {
                $sku = $this->productUtil->generateProductSku($product->id);
                $product->sku = $sku;
                $product->save();
            }
            
            // Create single product variation
            $this->createSingleProductVariation($product);
            
            Log::info('Successfully created product with AI details', [
                'sku' => $sku,
                'product_id' => $product->id
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error creating product with AI: ' . $e->getMessage(), [
                'sku' => $sku,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Create a single product variation
     */
    private function createSingleProductVariation($product)
    {
        try {
            $variation = new Variation();
            $variation->product_id = $product->id;
            $variation->name = 'DUMMY';
            $variation->sub_sku = $product->sku;
            $variation->default_purchase_price = 0;
            $variation->dpp_inc_tax = 0;
            $variation->profit_percent = 0;
            $variation->default_sell_price = 0;
            $variation->sell_price_inc_tax = 0;
            $variation->save();
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error creating single product variation: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function chatWithOpenAI(array $messages, $model = 'gemini-2.0-flash')
    {
        try {
            Log::info('Sending request to Gemini:', ['messages' => $messages]);
            
            // Convert OpenAI message format to Gemini format
            $prompt = '';
            foreach ($messages as $message) {
                if ($message['role'] === 'system') {
                    $prompt .= $message['content'] . "\n\n";
                } else if ($message['role'] === 'user') {
                    $prompt .= $message['content'];
                }
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . env('GEMINI_API_KEY'), [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
        
            ]);

            if ($response->successful()) {
                Log::info('Successful response from Gemini');
                $result = $response->json();
                $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                // Clean JSON response
                $content = preg_replace('/```json\s*([\s\S]+?)\s*```/', '$1', $content);
                
                // Convert Gemini response to OpenAI format
                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => $content
                            ]
                        ]
                    ]
                ];
            }

            Log::error('Gemini API request failed:', ['response' => $response->body()]);
            throw new \Exception('API request failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error in chatWithOpenAI: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getProductDetailsFromAI($sku, $product_name = null)
    {
        try {
            Log::info('Getting product details from AI for SKU: ' . $sku);
            
            // Validate SKU
            if (empty($sku)) {
                throw new \Exception('SKU cannot be empty');
            }

            $systemMessage = "You are an automotive product assistant.
            - ONLY return valid JSON in the exact structure provided.
            - Use exact SKU matching.
            - The product name must be in Arabic, and all other fields in English.
            - Include ALL applicable car models across ALL brands, using accurate data from reliable sources.
            - Prioritize accuracy by referencing real-world automotive part databases and websites.
            - Do not guess or use placeholder data.
            - When searching for information, prioritize automotive parts websites and databases.
            - Ensure all years are valid and logical (from_year should be less than or equal to to_year).
            - Model names should be the specific model name, without the brand name prefix. For example, return 'Toledo' instead of 'Seat Toledo'.
            - Model names should be standardized and consistent.";
            
            $userMessage = "Retrieve all details for the product with SKU: $sku";
            if (!empty($product_name)) {
                $userMessage .= " and product name: $product_name";
            }
            $userMessage .= ". Include the product name in Arabic, all car models across all brands that are compatible with this SKU (with model names in English and their compatibility year ranges), and all brands that have at least one compatible model. Respond in JSON using exactly this structure:
            {
                \"product_name\": \"[Arabic product name]\",
                \"models\": [
                    {
                        \"model_name\": \"[English model name]\",
                        \"from_year\": \"[year]\",
                        \"to_year\": \"[year]\"
                    }
                ],
                \"brand_category\": [\"[English brand names]\"]
            }";
            
            $messages = [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user',  'content' => $userMessage]
            ];
            $response = $this->chatWithOpenAI($messages, 'gemini-2.0-flash');
            
            if (!isset($response['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response structure from AI');
            }

            $ai_content = $response['choices'][0]['message']['content'];
            
            if (empty($ai_content)) {
                throw new \Exception('Empty response from AI');
            }

            // Clean and validate JSON response
            $ai_content = preg_replace('/```json|```/', '', trim($ai_content));
            $product_details = json_decode($ai_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from AI: ' . json_last_error_msg());
            }

            // Validate required fields
            if (!isset($product_details['product_name']) || empty($product_details['product_name'])) {
                throw new \Exception('Product name is missing in AI response');
            }

            if (!isset($product_details['models']) || !is_array($product_details['models'])) {
                throw new \Exception('Models array is missing or invalid in AI response');
            }

            if (!isset($product_details['brand_category']) || !is_array($product_details['brand_category'])) {
                throw new \Exception('Brand category array is missing or invalid in AI response');
            }

            // Get business ID from session or data
            $business_id = $this->data['business_id'] ?? session()->get('user.business_id');
            if (!$business_id) {
                throw new \Exception('Business ID not found');
            }

            // Process brand categories
            $brand_categories_array = [];
            if (!empty($product_details['brand_category'])) {
                $brand_names = is_array($product_details['brand_category'])
                    ? $product_details['brand_category']
                    : array_map('trim', explode(',', $product_details['brand_category']));

                // Remove any empty or invalid brand names
                $brand_names = array_filter($brand_names);

                if (!empty($brand_names)) {
                    $categories = Category::where(function ($query) use ($brand_names) {
                        foreach ($brand_names as $name) {
                            $query->orWhere('name', 'LIKE', '%' . $name . '%');
                        }
                    })->get();

                    foreach ($categories as $category) {
                        $brand_categories_array[] = (string) $category->id; // Store only the ID as string
                    }
                }
            }

            // Format manufacturing years and models in the required structure
            $manufacturing_years = [
                'from_year' => [],
                'to_year' => [],
                'model_name' => []
            ];

            // Process models and years with validation
            if (!empty($product_details['models'])) {
                foreach ($product_details['models'] as $modelData) {
                    // Validate model data
                    if (!isset($modelData['model_name']) || !isset($modelData['from_year']) || !isset($modelData['to_year'])) {
                        continue; // Skip invalid model entries
                    }

                    // Validate years
                    $from_year = intval($modelData['from_year']);
                    $to_year = intval($modelData['to_year']);
                    
                    if ($from_year > 0 && $to_year > 0 && $from_year <= $to_year) {
                        // Check if model exists in database
                        $device_model = DeviceModel::where('name', 'LIKE', '%' . trim($modelData['model_name']) . '%')->first();
                        
                        if ($device_model) {
                            $manufacturing_years['from_year'][] = (string)$from_year;
                            $manufacturing_years['to_year'][] = (string)$to_year;
                            $manufacturing_years['model_name'][] = $device_model->id;
                        }
                    }
                }
            }
            
            // Combine all data
            $product_details['brand_category'] = json_encode($brand_categories_array);
            $product_details['manufacturing_year'] = json_encode($manufacturing_years);

            Log::info('Successfully got product details from AI', [
                'sku' => $sku,
                'details' => $product_details
            ]);

            return $product_details;
        } catch (\Exception $e) {
            Log::error('Error getting product details from AI: ' . $e->getMessage(), [
                'sku' => $sku,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}