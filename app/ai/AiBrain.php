<?php
namespace ai;

require_once dirname(__DIR__) . '/helpers/DbSchemaHelper.php';
require_once dirname(__DIR__) . '/helpers/SqlValidator.php';

use SqlValidator;
use DbSchemaHelper;

class AiBrain {

    /**
     * Main handle message function for chatbot queries.
     * Communicates with Gemini API, handles tool calling, and queries the database.
     */
    public static function handleMessage(string $message, int $role_id, \mysqli $db): string {
        // Load configuration
        $config = require dirname(__DIR__) . '/config/config.php';
        $apiKey = $config['gemini_api_key'] ?? '';

        if (empty($apiKey) || $apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
            return "দুঃখিত, জেমিনি এপিআই কি (API Key) কনফিগার করা হয়নি। অনুগ্রহ করে `app/config/config.php` ফাইলে আপনার API Key যুক্ত করুন।";
        }

        // Establish connection to read-only user if available
        $aiUser = $config['db_ai_user'] ?? 'root';
        $aiPass = $config['db_ai_pass'] ?? '';
        
        $aiDb = @new \mysqli($config['db_host'], $aiUser, $aiPass, $config['db_name']);
        if ($aiDb->connect_error) {
            // Fallback to the main $db connection if read-only user is not set up yet
            $aiDb = $db;
        } else {
            $aiDb->set_charset("utf8mb4");
        }

        try {
            return self::runGeminiConversation($message, $apiKey, $aiDb);
        } catch (\Throwable $e) {
            // Log error
            $logFile = dirname(__DIR__, 2) . '/logs/chat_error.log';
            if (!file_exists(dirname($logFile))) {
                mkdir(dirname($logFile), 0777, true);
            }
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            return "দুঃখিত, আপনার প্রশ্নটি প্রসেস করার সময় একটি এপিআই ত্রুটি ঘটেছে। অনুগ্রহ করে আবার চেষ্টা করুন।";
        } finally {
            // Close database connection if we opened a separate one
            if (isset($aiDb) && $aiDb !== $db) {
                $aiDb->close();
            }
        }
    }

    /**
     * Executes the Conversation with Gemini 1.5 API
     */
    private static function runGeminiConversation(string $message, string $apiKey, \mysqli $dbConnection): string {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent?key=" . $apiKey;

        // Fetch database schema documentation
        $schemaText = DbSchemaHelper::getSchemaDescription();

        // Build system instructions
        $systemInstruction = [
            "parts" => [
                [
                    "text" => "You are a helpful and intelligent company ERP assistant. " .
                              "Answer user queries accurately by calling the 'run_database_query' tool to query the database. " .
                              "Format numbers as currency in BDT (৳) where appropriate. " .
                              "Always respond in natural, friendly Bengali language (বাংলা). " .
                              "Do not start your responses with greetings like 'আসসালামু আলাইকুম' (Assalamu Alaikum), 'হ্যালো', or 'হাই' since the initial greeting has already been delivered in the UI. Just answer the query directly in a helpful and professional tone. Never use 'নমস্কার' (Namaskar) or 'আদাব'. " .
                              "CRITICAL SOFT-DELETE RULE: ONLY the tables 'projects', 'leads', 'flats', 'lead_bookings', 'employees', and 'lead_follow_ups' contain a 'deleted_at' column to support soft-deletion. When generating ANY SQL query involving these specific tables, you MUST exclude soft-deleted records by adding 'tableName.deleted_at IS NULL' filter in your WHERE/AND clauses. DO NOT add 'deleted_at IS NULL' filter for any other tables (such as 'project_phases', 'project_phase_items', 'project_phase_item_details', 'work_types', 'phase_monthly_targets', 'phase_monthly_target_items', 'phase_weekly_progress', 'phase_weekly_progress_items', 'daily_progress_logs', 'daily_work_item_logs', 'tasks', 'users', 'boq_items') because they DO NOT have a 'deleted_at' column and adding it will cause a database error. " .
                              "PROJECT NAME FUZZY MATCHING RULE: Users might make slight typos or use synonyms in project names (e.g. typing 'test project by developer' instead of 'test project from developer'). If your SQL query on a specific project name returns no results, you MUST try a fallback search using partial words (e.g. `LIKE '%developer%'` or `LIKE '%test%'`) or retrieve all active project names (`SELECT id, name FROM projects WHERE deleted_at IS NULL`) to identify the closest match. Never claim a project does not exist without attempting to find similar names first."
                ]
            ]
        ];

        // Prepare tools schema
        $tools = [
            [
                "functionDeclarations" => [
                    [
                        "name" => "run_database_query",
                        "description" => "Execute a read-only SELECT SQL query on the database to fetch projects, bookings, leads, floor setups, materials, expenses, and employee counts. Output must be a valid SELECT query. Do not perform any mutations.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "query" => [
                                    "type" => "STRING",
                                    "description" => "The SELECT SQL query to execute."
                                ]
                            ],
                            "required" => ["query"]
                        ]
                    ]
                ]
            ]
        ];

        // Step 1: Initial call to Gemini with the user message
        $contents = [
            [
                "role" => "user",
                "parts" => [
                    ["text" => "Database Schema:\n" . $schemaText . "\n\nUser Question: " . $message]
                ]
            ]
        ];

        $logFile = dirname(__DIR__, 2) . '/logs/ai_chat_debug.log';
        $logData = date('Y-m-d H:i:s') . " - USER MESSAGE: " . $message . PHP_EOL;

        // Loop up to 5 times to allow recursive/multi-turn tool calls
        for ($iteration = 0; $iteration < 5; $iteration++) {
            $response = self::postCurl($url, [
                "contents" => $contents,
                "systemInstruction" => $systemInstruction,
                "tools" => $tools
            ]);

            $logData .= "ITERATION " . $iteration . " RESPONSE: " . json_encode($response) . PHP_EOL;

            $candidate = $response['candidates'][0] ?? null;
            if (!$candidate) {
                file_put_contents($logFile, $logData, FILE_APPEND);
                throw new \Exception("Invalid API response from Gemini: " . json_encode($response));
            }

            $parts = $candidate['content']['parts'] ?? [];
            $functionCall = null;
            foreach ($parts as $p) {
                if (isset($p['functionCall'])) {
                    $functionCall = $p['functionCall'];
                    break;
                }
            }

            if ($functionCall) {
                $functionName = $functionCall['name'];
                $args = $functionCall['args'];

                if ($functionName === 'run_database_query' && isset($args['query'])) {
                    $sqlQuery = $args['query'];
                    $dbResult = [];
                    $logData .= "GENERATED SQL: " . $sqlQuery . PHP_EOL;

                    try {
                        // Validate SQL query using our SqlValidator security layer
                        SqlValidator::validate($sqlQuery);

                        // Execute SQL
                        $result = $dbConnection->query($sqlQuery);
                        if ($result) {
                            if ($result instanceof \mysqli_result) {
                                $dbResult = $result->fetch_all(MYSQLI_ASSOC);
                            } else {
                                $dbResult = ["success" => $result];
                            }
                        } else {
                            $dbResult = ["error" => $dbConnection->error];
                        }
                    } catch (\Throwable $e) {
                        $dbResult = ["error" => $e->getMessage()];
                    }

                    $logData .= "SQL RESULT: " . json_encode($dbResult) . PHP_EOL;

                    // Feed back to Gemini
                    $contents[] = $candidate['content']; // Append assistant function call request to history
                    $contents[] = [
                        "role" => "tool",
                        "parts" => [
                            [
                                "functionResponse" => [
                                    "name" => "run_database_query",
                                    "response" => ["result" => $dbResult]
                                ]
                            ]
                        ]
                    ];
                } else {
                    $logData .= "UNKNOWN FUNCTION CALL: " . $functionName . PHP_EOL;
                    break;
                }
            } else {
                // No function call, this is the final text response!
                $finalText = "";
                foreach ($parts as $p) {
                    if (isset($p['text'])) {
                        $finalText .= $p['text'];
                    }
                }
                $logData .= "FINAL TEXT: " . $finalText . PHP_EOL . PHP_EOL;
                file_put_contents($logFile, $logData, FILE_APPEND);
                return !empty($finalText) ? $finalText : "দুঃখিত, কোনো উত্তর পাওয়া যায়নি।";
            }
        }

        $logData .= "EXCEEDED MAX ITERATIONS" . PHP_EOL . PHP_EOL;
        file_put_contents($logFile, $logData, FILE_APPEND);
        return "দুঃখিত, আমি উত্তরটি খুঁজে পাচ্ছি না।";
    }

    /**
     * Helper function to execute cURL POST requests.
     */
    private static function postCurl(string $url, array $data): array {
        $ch = curl_init($url);
        $payload = json_encode($data);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Local environment compatibility

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL Error: " . $error);
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("JSON Decode Error: " . json_last_error_msg());
        }

        return $decoded;
    }
}
