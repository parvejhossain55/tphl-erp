<?php
/**
 * GeminiService
 * Integrates Google Gemini AI to intelligently parse, clean, and map leads from Google Sheets.
 */

class GeminiService
{
    private string $apiKey;
    private string $model;
    private int $timeout;

    public function __construct(string $apiKey, string $model = 'gemini-2.5-flash', int $timeout = 60)
    {
        $this->apiKey  = trim($apiKey);
        $this->model   = $model;
        $this->timeout = $timeout;
    }

    /**
     * Parse raw leads rows using Gemini AI into structured ERP leads
     *
     * @param array $headers Column headers from Google Sheets
     * @param array $rows Array of raw rows from Google Sheets
     * @param array $projectsConfig Project list from config for accurate matching
     * @return array Extracted leads
     */
    public function parseLeadsWithGemini(array $headers, array $rows, array $projectsConfig): array
    {
        if (empty($this->apiKey) || strpos($this->apiKey, 'YOUR_GEMINI_API_KEY') !== false) {
            throw new InvalidArgumentException("Gemini API Key is missing or invalid in config");
        }

        $projectListText = "";
        foreach ($projectsConfig as $keyword => $info) {
            $projectListText .= "- Keyword: '{$keyword}', Project Name: '{$info['name']}', Project ID: {$info['project_id']}\n";
        }

        $systemInstruction = "You are an intelligent ERP Lead Automation AI for a Real Estate company.
Your task is to parse unstructured or multi-language (Bangla & English) lead rows exported from Google Sheets / Facebook Lead Ads into clean, structured JSON matching our ERP database schema.

Available Projects in ERP:
{$projectListText}

For each row, extract and return a JSON array of objects with the exact following fields:
- campaign_name: string (Original campaign name)
- matched_project_keyword: string (The exact matched project keyword from the list above, or null if unknown)
- project_id: integer or null (The matched project ID from the list)
- platform: string (e.g., 'fb')
- full_name: string (Cleaned client full name)
- phone_number: string (Clean phone number without 'p:' prefix, preserving country code like +880...)
- email: string or null
- job_title: string or null
- company_name: string or null
- city: string or null (e.g., Dhaka, Chittagong)
- country: string (Default 'Bangladesh')
- which_apartment_size_are_you_interested_in: string or null (e.g., '135 - 200 sqft')
- which_floor_are_you_for_shop: string or null (e.g., 'level_1')
- when_are_you_planning_to_buy: string or null (e.g., '6-12_months')
- would_you_like_to_schedule_a_site_visit: string or null (e.g., 'yes', 'no')
- when_is_the_best_time_to_contact_you: string or null (e.g., 'evening (5 pm - 8 pm)')

Return ONLY a valid JSON array of objects. Do not wrap in markdown quotes if possible, output pure JSON.";

        $dataPayload = [
            'headers' => $headers,
            'rows'    => $rows
        ];

        $prompt = "Parse the following lead rows into structured ERP leads:\n" . json_encode($dataPayload, JSON_UNESCAPED_UNICODE);

        $endpoint = sprintf(
        
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $this->model,
            $this->apiKey
        );

        $requestBody = [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $systemInstruction . "\n\n" . $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature'      => 0.1, // Low temperature for high deterministic accuracy
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($requestBody),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("cURL error connecting to Gemini API: " . $curlError);
        }

        if ($httpCode !== 200) {
            throw new RuntimeException(sprintf("Gemini API Error (HTTP %d): %s", $httpCode, $response));
        }

        $decodedResponse = json_decode($response, true);
        $content = $decodedResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($content)) {
            throw new RuntimeException("Empty response received from Gemini API.");
        }

        // Clean any accidental markdown code fences
        $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content));
        $parsedLeads = json_decode($cleanJson, true);

        if (!is_array($parsedLeads)) {
            throw new RuntimeException("Failed to decode JSON from Gemini API: " . json_last_error_msg());
        }

        return $parsedLeads;
    }
}
