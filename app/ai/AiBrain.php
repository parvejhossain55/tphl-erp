<?php
namespace ai;

require_once dirname(__DIR__) . '/helpers/DbSchemaHelper.php';
require_once dirname(__DIR__) . '/helpers/SqlValidator.php';

use SqlValidator;
use DbSchemaHelper;
use AiDataService;

class AiBrain {

    /**
     * Main handle message function for chatbot queries.
     * Communicates with Gemini API, handles tool calling, and queries the database.
     */
    public static function handleMessage(string $message, int $role_id, \mysqli $db): string {
        // Ensure session is started for context retention
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (PHP_SAPI === 'cli' && !isset($_SESSION)) {
            $_SESSION = [];
        }

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
                              "CRITICAL SECURITY RULE: You must NEVER disclose database structure details, schema definitions, tables, table names, list of tables, count of tables, SQL queries, column names, or anything related to the technical architecture of the database to the user. If a user asks questions like 'how many tables are there?', 'tell me the table names', 'what is the schema?', or requests any SQL query/database structure info, you MUST politely refuse to answer in Bengali (e.g., 'দুঃখিত, নিরাপত্তাজনিত কারণে আমাদের ডাটাবেজের টেকনিক্যাল গঠন বা টেবিল সংক্রান্ত তথ্য শেয়ার করা সম্ভব নয়।'). You are only permitted to answer functional, business-level or project-related questions using the data retrieved (e.g., project names, counts, financials, client bookings, tasks, etc.) without revealing how they are stored or structured. " .
                              "CRITICAL SOFT-DELETE RULE: ONLY the tables 'projects', 'leads', 'flats', 'lead_bookings', 'employees', and 'lead_follow_ups' contain a 'deleted_at' column to support soft-deletion. When generating ANY SQL query involving these specific tables, you MUST exclude soft-deleted records by adding 'tableName.deleted_at IS NULL' filter in your WHERE/AND clauses. DO NOT add 'deleted_at IS NULL' filter for any other tables (such as 'project_phases', 'project_phase_items', 'project_phase_item_details', 'work_types', 'phase_monthly_targets', 'phase_monthly_target_items', 'phase_weekly_progress', 'phase_weekly_progress_items', 'daily_progress_logs', 'daily_work_item_logs', 'tasks', 'users', 'boq_items') because they DO NOT have a 'deleted_at' column and adding it will cause a database error. " .
                              "PROJECT NAME FUZZY MATCHING RULE: Users might make slight typos or use synonyms in project names (e.g. typing 'test project by developer' instead of 'test project from developer'). If your SQL query on a specific project name returns no results, you MUST try a fallback search using partial words (e.g. `LIKE '%developer%'` or `LIKE '%test%'`) or retrieve all active project names (`SELECT id, name FROM projects WHERE deleted_at IS NULL`) to identify the closest match. Never claim a project does not exist without attempting to find similar names first. " .
                              "COMPANY-WIDE FINANCIAL OVERVIEW RULE: When asked for the company's overall/total cash in, cash out, or net cashflow (e.g., 'আমাদের টোটাল ক্যাশফ্লো এবং ক্যাশ ইন ও ক্যাশ আউট এর হিসাব দেখাও'), you MUST execute this exact query to get the total cash in and total cash out, because company-wide cashout involves multiple union tables:\n" .
                              "SELECT (\n" .
                              "  (SELECT COALESCE(SUM(CASE WHEN payment_type = 'land_share' THEN land_share_amount ELSE amount END), 0) FROM lead_payments WHERE payment_status IN ('paid','partially_paid') AND verification_status = 'verified') +\n" .
                              "  (SELECT COALESCE(SUM(amount), 0) FROM management_cashin WHERE payment_status = 'paid') +\n" .
                              "  (SELECT COALESCE(SUM(at.amount), 0) FROM adjustment_transactions at JOIN expense_adjustments ea ON at.adjustment_id = ea.id WHERE at.transaction_type = 'cashin' AND ea.status IN ('approved', 'partially_paid', 'fully_paid'))\n" .
                              ") AS total_cash_in,\n" .
                              "(\n" .
                              "  SELECT COALESCE(SUM(total_amount), 0)\n" .
                              "  FROM (\n" .
                              "    SELECT amount as total_amount FROM management_cashout WHERE payment_status = 'paid'\n" .
                              "    UNION ALL\n" .
                              "    SELECT cp.amount as total_amount FROM cashout_payments cp LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')\n" .
                              "    UNION ALL\n" .
                              "    SELECT net_salary as total_amount FROM payslips WHERE status = 'paid'\n" .
                              "    UNION ALL\n" .
                              "    SELECT COALESCE(approved_amount, bill_amount) as total_amount FROM contractor_bill_requests WHERE status IN ('approved', 'paid', 'partially_paid', 'fully_paid')\n" .
                              "    UNION ALL\n" .
                              "    SELECT COALESCE(approved_amount, net_payable) as total_amount FROM vendor_payment_requests WHERE status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid')\n" .
                              "    UNION ALL\n" .
                              "    SELECT amount_paid as total_amount FROM con_financial_transactions WHERE ref_type = 'operational_fund'\n" .
                              "    UNION ALL\n" .
                              "    SELECT at.amount as total_amount FROM adjustment_transactions at JOIN expense_adjustments ea ON at.adjustment_id = ea.id WHERE at.transaction_type = 'cashout' AND ea.status IN ('approved', 'partially_paid', 'fully_paid')\n" .
                              "    UNION ALL\n" .
                              "    SELECT amount as total_amount FROM fund_requisitions WHERE status = 'paid'\n" .
                              "    UNION ALL\n" .
                              "    SELECT lfr.requested_amount as total_amount FROM land_fund_requisitions lfr WHERE lfr.mgmt_status = 'Approved' AND lfr.accounts_status = 'Paid'\n" .
                              "  ) combined\n" .
                              ") AS total_cash_out;\n" .
                              "// Phase 2 Progress & Ongoing Phase Rules:\n" .
                              "PROJECT PROGRESS, REMAINING PROGRESS, AND ONGOING PHASE RULES:\n" .
                              "1. To calculate overall planned and actual progress of a project, check workflow_version first. If it is 'phase_based', you MUST run the CRITICAL EXACT SQL TEMPLATE from the schema guidelines.\n" .
                              "2. STALE COLUMNS WARNING: For projects with workflow_version = 'phase_based', the columns actual_progress and planned_progress inside project_phases (and project_phase_items) are unassigned and contain 0.00. You MUST NEVER select these columns directly for phase-based projects. Instead, to show phase progress, you MUST run the CRITICAL PHASE BREAKDOWN SQL TEMPLATE and report phase_actual_volume_pct as the actual progress and phase_planned_volume_pct as the planned progress of that phase.\n" .
                              "3. Remaining project progress is calculated as (100.0 - overall actual progress).\n" .
                              "4. Active/Ongoing Phase and Phase completion: To find the ongoing phase and its completion %, execute the CRITICAL PHASE BREAKDOWN SQL TEMPLATE. The ongoing phase is the first phase (by phase_order ASC) where phase_actual_volume_pct < 100. Report the phase name, its volume-based completion % (phase_actual_volume_pct), its phase weight (phase_weight), and its project contribution (phase_contribution)."
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
                    ],
                    [
                        "name" => "get_pending_approvals",
                        "description" => "Get list of all pending approval requests in the ERP (petty cash, contractor bills, vendor payments, cashout, and land fund requisitions)."
                    ],
                    [
                        "name" => "get_project_progress_details",
                        "description" => "Get calculated project completion percentage, remaining progress, current active phase, and timeline start/end dates for a project.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "project_id" => [
                                    "type" => "INTEGER",
                                    "description" => "The ID of the project."
                                ]
                            ],
                            "required" => ["project_id"]
                        ]
                    ],
                    [
                        "name" => "get_site_engineer_performance",
                        "description" => "Get summary of reports submitted and approved per site engineer."
                    ],
                    [
                        "name" => "get_project_stock_levels",
                        "description" => "Get list of material names, stock quantities, units, and warehouses for a project.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "project_id" => [
                                    "type" => "INTEGER",
                                    "description" => "The ID of the project."
                                ]
                            ],
                            "required" => ["project_id"]
                        ]
                    ],
                    [
                        "name" => "get_sales_performance",
                        "description" => "Get bookings count, discount value, total revenue, and performance of sales executives."
                    ],
                    [
                        "name" => "get_flat_inventory_summary",
                        "description" => "Get sold vs available flat counts, sold values, and available inventory values. Can be filtered by project_id.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "project_id" => [
                                    "type" => "INTEGER",
                                    "description" => "Optional project ID to filter by."
                                ]
                            ]
                        ]
                    ],
                    [
                        "name" => "get_financial_summary",
                        "description" => "Get company-wide cashflow overview (total cash in, total cash out, and net cashflow)."
                    ]
                ]
            ]
        ];

        // Retrieve or initialize conversation history from PHP session
        $contents = $_SESSION['ai_chat_history'] ?? [];
        if (empty($contents)) {
            $contents = [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => "Database Schema:\n" . $schemaText . "\n\nUser Question: " . $message]
                    ]
                ]
            ];
        } else {
            $contents[] = [
                "role" => "user",
                "parts" => [
                    ["text" => $message]
                ]
            ];
        }

        $logFile = dirname(__DIR__, 2) . '/logs/ai_chat_debug.log';
        $logData = date('Y-m-d H:i:s') . " - USER MESSAGE: " . $message . PHP_EOL;

        // Loop up to 5 times to allow recursive/multi-turn tool calls
        for ($iteration = 0; $iteration < 5; $iteration++) {
            // Clean up empty functionCall args in history to avoid Gemini API validation issues
            foreach ($contents as &$content) {
                if (isset($content['parts'])) {
                    foreach ($content['parts'] as &$part) {
                        if (isset($part['functionCall']) && (empty($part['functionCall']['args']) || is_array($part['functionCall']['args']))) {
                            // If it's an array and empty, force to stdClass to serialize as {}
                            if (is_array($part['functionCall']['args']) && count($part['functionCall']['args']) === 0) {
                                $part['functionCall']['args'] = new \stdClass();
                            }
                        }
                    }
                    unset($part);
                }
            }
            unset($content);

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

                $dbResult = [];
                $validCall = false;

                if ($functionName === 'run_database_query' && isset($args['query'])) {
                    $sqlQuery = $args['query'];
                    $logData .= "GENERATED SQL: " . $sqlQuery . PHP_EOL;
                    $validCall = true;

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
                } elseif ($functionName === 'get_pending_approvals') {
                    $dbResult = AiDataService::getPendingApprovals($dbConnection);
                    $validCall = true;
                } elseif ($functionName === 'get_project_progress_details' && isset($args['project_id'])) {
                    $dbResult = AiDataService::getProjectTimelineAndProgress($dbConnection, (int)$args['project_id']);
                    $validCall = true;
                } elseif ($functionName === 'get_site_engineer_performance') {
                    $dbResult = AiDataService::getSiteEngineerPerformance($dbConnection);
                    $validCall = true;
                } elseif ($functionName === 'get_project_stock_levels' && isset($args['project_id'])) {
                    $dbResult = AiDataService::getProjectStockMaterials($dbConnection, (int)$args['project_id']);
                    $validCall = true;
                } elseif ($functionName === 'get_sales_performance') {
                    $dbResult = AiDataService::getSalesExecutivePerformance($dbConnection);
                    $validCall = true;
                } elseif ($functionName === 'get_flat_inventory_summary') {
                    $pid = isset($args['project_id']) ? (int)$args['project_id'] : null;
                    $dbResult = AiDataService::getFlatSalesInventorySummary($dbConnection, $pid);
                    $validCall = true;
                } elseif ($functionName === 'get_financial_summary') {
                    $dbResult = AiDataService::getCompanyFinancialSummary($dbConnection);
                    $validCall = true;
                }

                if ($validCall) {
                    $logData .= "TOOL RESULT: " . json_encode($dbResult) . PHP_EOL;

                    // Feed back to Gemini
                    $contents[] = $candidate['content']; // Append assistant function call request to history
                    $contents[] = [
                        "role" => "tool",
                        "parts" => [
                            [
                                "functionResponse" => [
                                    "name" => $functionName,
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
                
                if (!empty($finalText)) {
                    // Append model's response to history
                    $contents[] = [
                        "role" => "model",
                        "parts" => [
                            ["text" => $finalText]
                        ]
                    ];
                    
                    // Prune history if it exceeds 40 messages to save tokens (keep first message containing schema)
                    if (count($contents) > 40) {
                        $schemaMsg = $contents[0];
                        $lastElements = array_slice($contents, -15);
                        $contents = array_merge([$schemaMsg], $lastElements);
                    }
                    
                    $_SESSION['ai_chat_history'] = $contents;
                    return $finalText;
                } else {
                    return "দুঃখিত, কোনো উত্তর পাওয়া যায়নি।";
                }
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
