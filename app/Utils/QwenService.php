<?php

namespace App\Utils;


use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;


class QwenService
{
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function sendMessage($message)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $message]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            return 'Error: Unable to get response from Gemini';

        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}