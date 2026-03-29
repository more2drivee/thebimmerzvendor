<?php

namespace Modules\ArtificialIntelligence\Services;

use Illuminate\Support\Facades\Http;
use Modules\Repair\Entities\DeviceModel;

class ArtificialIntelligenceService
{
    /**
     * Chat with OpenAI API
     * 
     * @param array $messages
     * @param array $options
     * @return array
     */
    public function chatGPT($data, $options = [])
    {
        $model = $options['model'] ?? 'gpt-4-turbo';
        $max_tokens = $options['max_tokens'] ?? 3000;

        // Retrieve API key from configuration (works even when config is cached) or fallback to .env
        $apiKey = trim(config('openai.api_key') ?? env('OPENAI_API_KEY'), '\'\"');
        
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model'    => $model,
            'messages' => $data,
            'max_tokens' => $max_tokens,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('OpenAI API request failed: ' . $response->body());
    }

    /**
     * Process request through OpenRouter
     * 
     * @param array $data
     * @param array $options
     * @return array
     */
    public function openRouter($data, $options = [])
    {
        // Use a free model by default
        $model = $options['model'] ?? 'mistralai/mistral-7b-instruct';
        $max_tokens = $options['max_tokens'] ?? 3000;
        
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'Content-Type'  => 'application/json',
            'HTTP-Referer' => env('APP_URL', 'http://localhost'), // Required for free tier
            'X-Title' => 'POS System' // Helps with free tier usage tracking
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model'    => $model,
            'messages' => $data,
            'max_tokens' => $max_tokens,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('OpenRouter API request failed: ' . $response->body());
    }

    /**
     * Process request through HuggingFace
     * 
     * @param array $data
     * @param array $options
     * @return array
     */
    public function huggingFace($data, $options = [])
    {
        // Use a free model by default
        $model = $options['model'] ?? 'mistralai/Mistral-7B-Instruct-v0.2';
        $max_tokens = $options['max_tokens'] ?? 1000;
        
        // Convert OpenAI format to HuggingFace format
        $prompt = '';
        foreach ($data as $message) {
            if ($message['role'] === 'system') {
                $prompt .= "System: " . $message['content'] . "\n\n";
            } else if ($message['role'] === 'user') {
                $prompt .= "User: " . $message['content'] . "\n\n";
            } else if ($message['role'] === 'assistant') {
                $prompt .= "Assistant: " . $message['content'] . "\n\n";
            }
        }
        $prompt .= "Assistant: ";
        
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . env('HUGGINGFACE_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post("https://api-inference.huggingface.co/models/{$model}", [
            'inputs' => $prompt,
            'parameters' => [
                'max_new_tokens' => $max_tokens,
                'return_full_text' => false
            ]
        ]);

        if ($response->successful()) {
            $result = $response->json();
            
            // Format response to match OpenAI format
            return [
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => $result[0]['generated_text'] ?? ''
                        ]
                    ]
                ]
            ];
        }

        throw new \Exception('HuggingFace API request failed: ' . $response->body());
    }

    /**
     * Process request through Groq
     * 
     * @param array $data
     * @param array $options
     * @return array
     */
    public function groq($data, $options = [])
    {
        $model = $options['model'] ?? 'llama3-70b-8192';
        $max_tokens = $options['max_tokens'] ?? 3000;
        
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'    => $model,
            'messages' => $data,
            'max_tokens' => $max_tokens,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Groq API request failed: ' . $response->body());
    }

    /**
     * Process request through Qwen
     * 
     * @param string|array $data
     * @param array $options
     * @return array
     */
    public function qwen($data, $options = [])
    {
        $model = $options['model'] ?? 'qwen-max';
        $max_tokens = $options['max_tokens'] ?? 3000;
        
        // Convert string to messages format if needed
        $messages = is_string($data) ? [['role' => 'user', 'content' => $data]] : $data;
        
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer ' . env('QWEN_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation', [
            'model'    => $model,
            'input'    => [
                'messages' => $messages
            ],
            'parameters' => [
                'max_tokens' => $max_tokens
            ]
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Qwen API request failed: ' . $response->body());
    }

    /**
     * Process request through Gemini
     * 
     * @param string|array $data
     * @param array $options
     * @return array
     */
    public function gemini($data, $options = [])
    {
        $model = $options['model'] ?? 'gemini-2.0-flash';
        $max_tokens = $options['max_tokens'] ?? 3000;
        
        // Format the data for Gemini API
        $contents = [];
        
        if (is_string($data)) {
            // Handle simple string input
            $contents[] = [
                'parts' => [
                    ['text' => $data]
                ]
            ];
        } else {
            // Handle array of messages (convert from OpenAI format to Gemini format)
            $parts = [];
            
            foreach ($data as $message) {
                if (isset($message['role']) && isset($message['content'])) {
                    // For system messages, prefix with "System: "
                    if ($message['role'] === 'system') {
                        $parts[] = ['text' => "System: " . $message['content']];
                    } 
                    // For user and assistant messages, just use the content
                    else {
                        $parts[] = ['text' => $message['content']];
                    }
                }
            }
            
            $contents[] = ['parts' => $parts];
        }
        
        $response = Http::timeout(60)->withHeaders([
            'x-goog-api-key' => env('GEMINI_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent", [
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => $max_tokens
            ]
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Gemini API request failed: ' . $response->body());
    }

    // Remove this duplicate method
    // /**
    //  * Process request through HuggingFace
    //  * 
    //  * @param array $data
    //  * @param string $model
    //  * @param array $options
    //  * @return array
    //  */
    // public function huggingFace($data, $model, $options = [])
    // {
    //     // Implementation for HuggingFace API
    //     throw new \Exception('HuggingFace API not implemented yet');
    // }

    /**
     * Validate product data using multiple AI models
     * 
     * @param string $sku SKU number to validate
     * @param array $options Additional options for validation
     * @return array Validation results from multiple models
     */
    public function validateProductData($sku, $options = [])
    {
        $providers = $options['providers'] ?? ['openai', 'gemini', 'groq'];
        $threshold = $options['threshold'] ?? 2; // Minimum number of models that must agree
        $results = [];
        $validationPrompt = $this->buildValidationPrompt($sku);
        
        foreach ($providers as $provider) {
            try {
                $result = $this->processValidationWithProvider($provider, $validationPrompt, $options);
                $results[$provider] = $this->parseValidationResult($result, $provider);
            } catch (\Exception $e) {
                $results[$provider] = [
                    'error' => $e->getMessage(),
                    'status' => 'failed'
                ];
            }
        }
        
        return [
            'individual_results' => $results,
            'consensus' => $this->buildConsensus($results, $threshold),
            'validation_passed' => $this->determineValidationStatus($results, $threshold)
        ];
    }
    
    /**
     * Build the validation prompt for AI models
     * 
     * @param string $sku SKU to validate
     * @return array Formatted prompt for AI models
     */
    private function buildValidationPrompt($sku)
    {
        $systemPrompt = "You are a product data validator. Your task is to validate the SKU number and identify all brands and models compatible with this product. Return ONLY valid JSON with no additional text.";
        
        $userPrompt = "Validate the following SKU: {$sku}. 
        
        Provide the following information:
        1. Whether this appears to be a valid SKU for an automotive part
        2. All car brands compatible with this part
        3. All car models compatible with this part, with their year ranges
        
        Return your response in this exact JSON format:
        {
            \"is_valid\": true/false,
            \"confidence\": 0-100,
            \"product_name\": \"[product name]\",
            \"brands\": [\"Brand1\", \"Brand2\"],
            \"models\": [
                {
                    \"brand\": \"Brand1\",
                    \"model\": \"Model1\",
                    \"from_year\": YYYY,
                    \"to_year\": YYYY
                }
            ],
            \"validation_notes\": \"[any notes about validation]\"
        }";
        
        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ];
    }
    
    /**
     * Process validation with a specific AI provider
     * 
     * @param string $provider AI provider name
     * @param array $prompt Validation prompt
     * @param array $options Additional options
     * @return array Raw response from the AI provider
     */
    private function processValidationWithProvider($provider, $prompt, $options = [])
    {
        switch ($provider) {
            case 'openai':
                return $this->chatGPT($prompt, $options['openai'] ?? []);
            case 'gemini':
                return $this->gemini($prompt, $options['gemini'] ?? []);
            case 'groq':
                return $this->groq($prompt, $options['groq'] ?? []);
            case 'qwen':
                return $this->qwen($prompt, $options['qwen'] ?? []);
            case 'openrouter':
                return $this->openRouter($prompt, $options['openrouter'] ?? []);
            default:
                throw new \Exception("Unsupported AI provider: {$provider}");
        }
    }
    
    /**
     * Parse validation result from AI response
     * 
     * @param array $result Raw AI response
     * @param string $provider Provider name
     * @return array Parsed validation data
     */
    private function parseValidationResult($result, $provider)
    {
        try {
            $content = '';
            
            switch ($provider) {
                case 'gemini':
                    $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    break;
                case 'qwen':
                    $content = $result['output']['text'] ?? '';
                    break;
                default:
                    $content = $result['choices'][0]['message']['content'] ?? '';
            }
            
            // Clean up the content (remove markdown code blocks if present)
            $content = preg_replace('/```(?:json)?\s*([\s\S]+?)\s*```/', '$1', $content);
            $content = trim($content);
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                    'raw_content' => $content,
                    'status' => 'failed'
                ];
            }
            
            $data['status'] = 'success';
            return $data;
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to parse result: ' . $e->getMessage(),
                'status' => 'failed'
            ];
        }
    }
    
    /**
     * Build consensus from multiple AI model results
     * 
     * @param array $results Results from multiple AI models
     * @param int $threshold Minimum number of models that must agree
     * @return array Consensus data
     */
    private function buildConsensus($results, $threshold)
    {
        $validResults = array_filter($results, function($result) {
            return isset($result['status']) && $result['status'] === 'success';
        });
        
        if (count($validResults) < $threshold) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Not enough valid responses from AI models'
            ];
        }
        
        // Count brand occurrences
        $brandCounts = [];
        foreach ($validResults as $result) {
            if (isset($result['brands']) && is_array($result['brands'])) {
                foreach ($result['brands'] as $brand) {
                    $brandCounts[$brand] = ($brandCounts[$brand] ?? 0) + 1;
                }
            }
        }
        
        // Count model occurrences
        $modelCounts = [];
        foreach ($validResults as $result) {
            if (isset($result['models']) && is_array($result['models'])) {
                foreach ($result['models'] as $modelData) {
                    $key = $modelData['brand'] . '|' . $modelData['model'];
                    if (!isset($modelCounts[$key])) {
                        $modelCounts[$key] = [
                            'count' => 0,
                            'data' => $modelData
                        ];
                    }
                    $modelCounts[$key]['count']++;
                }
            }
        }
        
        // Filter brands and models that meet the threshold
        $consensusBrands = array_keys(array_filter($brandCounts, function($count) use ($threshold) {
            return $count >= $threshold;
        }));
        
        $consensusModels = array_values(array_map(function($item) {
            return $item['data'];
        }, array_filter($modelCounts, function($item) use ($threshold) {
            return $item['count'] >= $threshold;
        })));
        
        // Determine overall validity
        $validityVotes = array_count_values(array_map(function($result) {
            return $result['is_valid'] ?? false;
        }, $validResults));
        
        $isValid = ($validityVotes[true] ?? 0) >= $threshold;
        
        return [
            'is_valid' => $isValid,
            'brands' => $consensusBrands,
            'models' => $consensusModels,
            'confidence' => $this->calculateAverageConfidence($validResults),
            'status' => 'success'
        ];
    }
    
    /**
     * Calculate average confidence from multiple results
     * 
     * @param array $results Results from multiple AI models
     * @return float Average confidence score
     */
    private function calculateAverageConfidence($results)
    {
        $confidences = array_filter(array_map(function($result) {
            return $result['confidence'] ?? null;
        }, $results));
        
        if (empty($confidences)) {
            return 0;
        }
        
        return round(array_sum($confidences) / count($confidences));
    }
    
    /**
     * Determine overall validation status
     * 
     * @param array $results Results from multiple AI models
     * @param int $threshold Minimum number of models that must agree
     * @return bool Whether validation passed
     */
    private function determineValidationStatus($results, $threshold)
    {
        $validResults = array_filter($results, function($result) {
            return isset($result['status']) && $result['status'] === 'success';
        });
        
        if (count($validResults) < $threshold) {
            return false;
        }
        
        $validityVotes = array_count_values(array_map(function($result) {
            return $result['is_valid'] ?? false;
        }, $validResults));
        
        return ($validityVotes[true] ?? 0) >= $threshold;
    }
}