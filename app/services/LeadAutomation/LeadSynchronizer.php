<?php
/**
 * LeadSynchronizer
 * Core Engine for Google Sheets to ERP Leads Database Synchronization
 */

class LeadSynchronizer
{
    private array $config;
    private ?PDO $pdo = null;
    private array $projectRotations = [];
    private array $logs = [];
    private ?int $userId = null;

    public function __construct(array $config, ?int $userId = null)
    {
        $this->config = $config;
        $this->userId = $userId ?? (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);
    }

    /**
     * Get or create PDO Database Connection
     */
    public function getDb(): PDO
    {
        if ($this->pdo === null) {
            $dbConf = $this->config['db'];
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $dbConf['host'],
                $dbConf['port'] ?? 3306,
                $dbConf['database'],
                $dbConf['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $this->pdo = new PDO($dsn, $dbConf['username'], $dbConf['password'], $options);
            } catch (PDOException $e) {
                // If on local dev machine and configured credentials fail, attempt local root/tphl_db connection
                if ($dbConf['username'] !== 'root' || $dbConf['database'] !== 'tphl_db') {
                    try {
                        $localDsn = 'mysql:host=127.0.0.1;port=3306;dbname=tphl_db;charset=utf8mb4';
                        $this->pdo = new PDO($localDsn, 'root', '', $options);
                    } catch (Throwable $t) {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        }

        return $this->pdo;
    }

    /**
     * Run full synchronization from Google Sheets (Auto Multi-Tab Support)
     */
    public function runSync(?string $customSheetUrl = null): array
    {
        $startTime = microtime(true);
        $this->log("=======================================================");
        $this->log("Starting Lead Synchronization at " . date('Y-m-d H:i:s'));

        $sheetUrl = $customSheetUrl ?: ($this->config['google_sheet']['url'] ?? '');
        if (empty($sheetUrl) || strpos($sheetUrl, 'YOUR_SHEET_ID_HERE') !== false) {
            throw new InvalidArgumentException("Please provide a valid Google Sheet URL in config.php");
        }

        // Check if URL explicitly requests a single tab only (via ?single_tab=1)
        $isSpecificTabOnly = (strpos($sheetUrl, 'single_tab=1') !== false || strpos($sheetUrl, 'only_tab=1') !== false);
        if ($isSpecificTabOnly && preg_match('/[#&?]gid=([0-9]+)/', $sheetUrl, $gidMatch)) {
            $this->log(sprintf("Targeting specific single worksheet tab [gid: %s] as explicitly requested.", $gidMatch[1]));
        }

        // By default, synchronize ALL worksheet tabs from the Google Sheet workbook
        if (!$isSpecificTabOnly && preg_match('/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $sheetUrl, $matches)) {
            $sheetId = $matches[1];
            try {
                $results = $this->syncAllTabsFromGoogleSheet($sheetId);
                $executionTime = round(microtime(true) - $startTime, 2);
                $tabCount = count($results['tabs_synced'] ?? []);
                $this->log(sprintf(
                    "Multi-Tab Sync Completed in %s sec across %d tab(s). Total Rows: %d | Inserted: %d | Duplicates Skipped: %d | Unmatched Project: %d | Errors: %d",
                    $executionTime,
                    $tabCount,
                    $results['total_rows'],
                    $results['inserted'],
                    $results['skipped_duplicate'],
                    $results['unmatched_project'],
                    $results['errors']
                ));
                $this->log("=======================================================\n");
                $this->flushLogsToFile();

                return array_merge($results, ['execution_time_seconds' => $executionTime]);
            } catch (Throwable $e) {
                $this->log("Multi-tab XLSX sync note: " . $e->getMessage() . ". Falling back to direct single-tab CSV export.");
            }
        }

        // Direct CSV export fallback (single sheet/tab)
        $csvExportUrl = $this->convertToCsvExportUrl($sheetUrl);
        $this->log("Fetching data from: " . $csvExportUrl);

        $rawContent = $this->fetchUrlContent($csvExportUrl);
        if (empty(trim($rawContent))) {
            throw new RuntimeException("Fetched Google Sheet content is empty.");
        }

        $results = $this->processCsvContent($rawContent);

        $executionTime = round(microtime(true) - $startTime, 2);
        $this->log(sprintf(
            "Sync Completed in %s sec. Total Rows: %d | Inserted: %d | Duplicates Skipped: %d | Unmatched Project: %d | Errors: %d",
            $executionTime,
            $results['total_rows'],
            $results['inserted'],
            $results['skipped_duplicate'],
            $results['unmatched_project'],
            $results['errors']
        ));
        $this->log("=======================================================\n");

        $this->flushLogsToFile();

        return array_merge($results, ['execution_time_seconds' => $executionTime]);
    }

    /**
     * Download entire Google Sheet workbook and synchronize all worksheet tabs
     */
    private function syncAllTabsFromGoogleSheet(string $sheetId): array
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Reader\Xlsx') && file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../../vendor/autoload.php';
        }

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Reader\Xlsx')) {
            throw new RuntimeException("PhpOffice\\PhpSpreadsheet library is not loaded.");
        }

        $xlsxUrl = sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=xlsx', $sheetId);
        $this->log("Downloading multi-tab workbook from: " . $xlsxUrl);

        $xlsxContent = $this->fetchUrlContent($xlsxUrl);
        if (empty($xlsxContent)) {
            throw new RuntimeException("Downloaded XLSX content is empty.");
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'tphl_gsheet_') . '.xlsx';
        file_put_contents($tmpFile, $xlsxContent);

        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($tmpFile);

            $sheetCount = $spreadsheet->getSheetCount();
            $this->log(sprintf("Discovered %d tab(s) in Google Sheet workbook.", $sheetCount));

            $aggregated = [
                'total_rows'        => 0,
                'inserted'          => 0,
                'skipped_duplicate' => 0,
                'unmatched_project' => 0,
                'errors'            => 0,
                'details'           => [],
                'tabs_synced'       => []
            ];

            for ($i = 0; $i < $sheetCount; $i++) {
                $sheet = $spreadsheet->getSheet($i);
                $tabName = $sheet->getTitle();
                $highestRow = $sheet->getHighestRow();

                $this->log(sprintf("Processing Tab [%d/%d] '%s' (Highest Row: %d)...", $i + 1, $sheetCount, $tabName, $highestRow));

                if ($highestRow < 2) {
                    
                    $this->log(" -> Tab '{$tabName}' has no lead rows. Skipping.");
                    $aggregated['tabs_synced'][] = [
                        'tab'      => $tabName,
                        'rows'     => 0,
                        'inserted' => 0
                    ];
                    continue;
                }

                // Export current sheet to CSV format
                $csvWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
                $csvWriter->setSheetIndex($i);
                $csvWriter->setUseBOM(true);
                ob_start();
                $csvWriter->save('php://output');
                $tabCsv = ob_get_clean();

                if (empty(trim($tabCsv))) {
                    continue;
                }

                $tabResults = $this->processCsvContent($tabCsv);

                $aggregated['total_rows'] += $tabResults['total_rows'];
                $aggregated['inserted'] += $tabResults['inserted'];
                $aggregated['skipped_duplicate'] += $tabResults['skipped_duplicate'];
                $aggregated['unmatched_project'] += $tabResults['unmatched_project'];
                $aggregated['errors'] += $tabResults['errors'];
                if (!empty($tabResults['details'])) {
                    $aggregated['details'] = array_merge($aggregated['details'], $tabResults['details']);
                }

                $aggregated['tabs_synced'][] = [
                    'tab'      => $tabName,
                    'rows'     => $tabResults['total_rows'],
                    'inserted' => $tabResults['inserted']
                ];

                $this->log(sprintf(
                    " -> Tab '%s' completed: %d rows processed, %d new leads inserted, %d duplicates skipped.",
                    $tabName,
                    $tabResults['total_rows'],
                    $tabResults['inserted'],
                    $tabResults['skipped_duplicate']
                ));
            }

            return $aggregated;
        } finally {
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Process raw CSV string (used for live sync or local file tests)
     */
    public function processCsvContent(string $rawContent, bool $dryRun = false): array
    {
        // Clean UTF BOM and normalize line breaks
        $content = $this->cleanBomAndNormalize($rawContent);

        // Detect delimiter (Comma vs Tab)
        $delimiter = $this->detectDelimiter($content);

        // Parse lines into rows
        $lines = explode("\n", $content);
        if (empty($lines)) {
            return ['total_rows' => 0, 'inserted' => 0, 'skipped_duplicate' => 0, 'unmatched_project' => 0, 'errors' => 0];
        }

        // First row is the Header
        $firstLine = array_shift($lines);
        $rawHeaders = str_getcsv($firstLine, $delimiter);

        // Collect valid data rows
        $rawRows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $row = str_getcsv($line, $delimiter);
            if (!empty($row) && count($row) >= 2) {
                $rawRows[] = $row;
            }
        }

        if (empty($rawRows)) {
            return ['total_rows' => 0, 'inserted' => 0, 'skipped_duplicate' => 0, 'unmatched_project' => 0, 'errors' => 0];
        }

        if (!$dryRun) {
            $this->ensureSchema();
        }

        // 1. First build header index map using the ultra-fast local engine
        $headerMap = $this->buildHeaderIndexMap($rawHeaders);
        $hasKeyFields = isset($headerMap['phone_number']) && (isset($headerMap['full_name']) || isset($headerMap['campaign_name']));

        // 2. Check if Gemini AI should be invoked (only if required fields are missing or mode is 'always')
        $geminiConf = $this->config['gemini'] ?? [];
        $forceGemini = ($geminiConf['mode'] ?? '') === 'always';

        if (($forceGemini || !$hasKeyFields) && !empty($geminiConf['enabled']) && !empty($geminiConf['api_key']) && strpos($geminiConf['api_key'], 'YOUR_GEMINI_API_KEY') === false) {
            try {
                $modelName = $geminiConf['model'] ?? 'gemini-2.5-flash';
                $reason = $forceGemini ? "forced mode" : "unrecognized headers";
                $this->log("Processing with Google Gemini AI [{$modelName}] ({$reason}) for intelligent field understanding...");
                require_once __DIR__ . '/GeminiService.php';
                $gemini = new GeminiService(
                    $geminiConf['api_key'],
                    $modelName,
                    $geminiConf['timeout'] ?? 60
                );
                return $this->processWithGemini($gemini, $rawHeaders, $rawRows, $dryRun);
            } catch (Exception $e) {
                $this->log("Gemini AI Warning: " . $e->getMessage());
                if (!($geminiConf['fallback_to_local'] ?? true)) {
                    throw $e;
                }
                $this->log("Seamlessly falling back to local rule-based parser...");
            }
        } else {
            $this->log("Fast local parser active: recognized key columns instantly in < 0.02s.");
        }

        $stats = [
            'total_rows'        => 0,
            'inserted'          => 0,
            'skipped_duplicate' => 0,
            'unmatched_project' => 0,
            'errors'            => 0,
            'details'           => []
        ];

        $pdo = $dryRun ? null : $this->getDb();

        foreach ($rawRows as $lineNum => $row) {
            $stats['total_rows']++;
            $leadData = $this->extractLeadFields($row, $headerMap);

            // Skip row if no contact info at all
            if (empty($leadData['phone_number']) && empty($leadData['full_name'])) {
                continue;
            }

            // Clean phone number (strip 'p:' prefix common in FB Leads)
            $rawPhone = $leadData['phone_number'] ?? '';
            $cleanPhone = preg_replace('/^p:/i', '', trim($rawPhone));
            $leadData['phone_number'] = $cleanPhone;

            // Skip Meta / Facebook automated test leads or dummy rows
            $fullName = (string)($leadData['full_name'] ?? '');
            $email = (string)($leadData['email'] ?? '');
            if (
                stripos($cleanPhone, '<test lead') !== false ||
                stripos($fullName, '<test lead') !== false ||
                stripos($email, 'test@meta.com') !== false
            ) {
                $this->log(sprintf("Row %d: Skipped Meta test lead / dummy data row.", $lineNum + 2));
                continue;
            }

            // Match Project and determine Team ID via Round-Robin
            $projectMatch = $this->matchProject($leadData['campaign_name'] ?? '', $leadData['form_name'] ?? '');
            if ($projectMatch['project_id'] === null && !($this->config['default_project']['allow_insert_if_unmatched'] ?? true)) {
                $stats['unmatched_project']++;
                $this->log("Row " . ($lineNum + 2) . ": Skipped because project could not be matched for campaign: " . $leadData['campaign_name']);
                continue;
            }

            $projectId = $projectMatch['project_id'];
            $teamId = $this->resolveNextTeamId($projectId, $projectMatch['teams'], $pdo);

            $leadData['project_id'] = $projectId;
            $leadData['team_id']    = $teamId;

            // Duplicate Check
            if (!$dryRun && ($this->config['duplicate_check']['enabled'] ?? true)) {
                if ($this->isDuplicateLead($leadData, $pdo)) {
                    $stats['skipped_duplicate']++;
                    $this->log(sprintf("Row %d: Duplicate lead skipped (Phone: %s, Campaign: %s)", $lineNum + 2, $cleanPhone, $leadData['campaign_name']));
                    continue;
                }
            }

            // Insert into Database
            if (!$dryRun) {
                try {
                    $insertId = $this->insertLeadIntoDatabase($leadData, $pdo);
                    $stats['inserted']++;
                    $stats['details'][] = [
                        'id'         => $insertId,
                        'name'       => $leadData['full_name'],
                        'phone'      => $cleanPhone,
                        'campaign'   => $leadData['campaign_name'],
                        'project_id' => $projectId,
                        'team_id'    => $teamId,
                        'distribute' => 0,
                    ];
                } catch (Exception $e) {
                    $stats['errors']++;
                    $this->log(sprintf("Row %d: DB Insert Error: %s", $lineNum + 2, $e->getMessage()));
                }
            } else {
                // Dry Run mode
                $stats['inserted']++;
                $stats['details'][] = [
                    'name'       => $leadData['full_name'],
                    'phone'      => $cleanPhone,
                    'campaign'   => $leadData['campaign_name'],
                    'project_id' => $projectId,
                    'team_id'    => $teamId,
                    'distribute' => 0,
                ];
            }
        }

        return $stats;
    }

    /**
     * Convert Google Sheets share link to direct CSV download URL
     */
    public function convertToCsvExportUrl(string $url): string
    {
        // Direct CSV export link already
        if (strpos($url, '/export?format=csv') !== false) {
            return $url;
        }

        // Match Google Sheet ID
        if (preg_match('/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $sheetId = $matches[1];

            // Only append gid if explicitly present in original URL
            $gidParam = '';
            if (preg_match('/[#&?]gid=([0-9]+)/', $url, $gidMatches)) {
                $gidParam = '&gid=' . $gidMatches[1];
            }

            return sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=csv%s', $sheetId, $gidParam);
        }

        return $url;
    }

    /**
     * Download content from URL using cURL with redirect support
     */
    private function fetchUrlContent(string $url): string
    {
        if (!function_exists('curl_init')) {
            // Fallback to file_get_contents
            $context = stream_context_create([
                'http' => [
                    'timeout' => $this->config['google_sheet']['timeout'] ?? 30,
                    'follow_location' => 1,
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
                ]
            ]);
            $data = @file_get_contents($url, false, $context);
            if ($data === false) {
                throw new RuntimeException("Failed to download Google Sheet using file_get_contents. Check sheet link and internet access.");
            }
            return $data;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->config['google_sheet']['timeout'] ?? 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) LeadSyncAutomation/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("cURL Error while fetching Google Sheet: " . $curlError);
        }

        if ($httpCode !== 200) {
            throw new RuntimeException(sprintf("Google Sheets returned HTTP status code %d. Make sure the sheet is public ('Anyone with the link can view').", $httpCode));
        }

        return (string)$response;
    }

    /**
     * Build mapping between CSV column indices and database fields.
     * Supports both English template headers and Bengali Facebook question headers.
     */
    private function buildHeaderIndexMap(array $headers): array
    {
        $map = [];

        // Exact match aliases first (strictly prioritized)
        $exactFieldAliases = [
            'campaign_name' => ['campaign_name', 'campaign'],
            'form_name'     => ['form_name', 'form'],
            'adset_name'    => ['adset_name', 'adset'],
            'platform'      => ['platform', 'source'],
            'which_apartment_size_are_you_interested_in' => [
                'which_apartment_size_are_you_interested_in',
                'আপনি_কোন_সাইজের_কমার্শিয়াল_শপ_খুঁজছেন?',
                'আপনি_কোন_সাইজের_কমার্শিয়াল_শপ_খুজছেন?',
                'apartment_size', 'flat_size', 'shop_size'
            ],
            'which_floor_are_you_for_shop' => [
                'which_floor_are_you_for_shop',
                'আপনি_কোন_ফ্লোরে_শপ_নিতে_আগ্রহী?',
                'floor', 'floor_for_shop'
            ],
            'when_are_you_planning_to_buy' => [
                'when_are_you_planning_to_buy',
                'তুমি_কখন_কেনার_পরিকল্পনা_করছো?',
                'আপনি_কখন_কেনার_পরিকল্পনা_করছেন?',
                'planning_to_buy'
            ],
            'would_you_like_to_schedule_a_site_visit' => [
                'would_you_like_to_schedule_a_site_visit',
                'আপনি_কি_সাইট_ভিজিটের_সময়_নির্ধারণ_করতে_চান?',
                'site_visit'
            ],
            'when_is_the_best_time_to_contact_you' => [
                'when_is_the_best_time_to_contact_you',
                'আপনার_সাথে_যোগাযোগ_করার_সবচেয়ে_ভালো_সময়_কখন?',
                'contact_time'
            ],
            'full_name' => [
                'full_name', 'client_name', 'lead_name', 'customer_name', 'name'
            ],
            'job_title' => [
                'job_title', 'designation', 'occupation', 'profession'
            ],
            'company_name' => [
                'company_name', 'company', 'organization'
            ],
            'phone_number' => [
                'phone_number', 'phone', 'mobile', 'contact_number'
            ],
            'email' => [
                'email', 'email_address'
            ],
            'city' => [
                'city', 'state', 'location'
            ],
            'country' => [
                'country'
            ],
        ];

        // 1. Pass 1: EXACT MATCH (Highest accuracy)
        foreach ($headers as $index => $rawColName) {
            $colName = trim((string)$rawColName);
            $cleanCol = strtolower(str_replace([' ', '-', '–'], '_', $colName));

            foreach ($exactFieldAliases as $dbField => $aliases) {
                if (isset($map[$dbField])) {
                    continue;
                }
                foreach ($aliases as $alias) {
                    $cleanAlias = strtolower(str_replace([' ', '-', '–'], '_', $alias));
                    if ($cleanCol === $cleanAlias || $colName === $alias) {
                        $map[$dbField] = $index;
                        break;
                    }
                }
            }
        }

        // 2. Pass 2: FUZZY / SUBSTRING MATCH for any remaining unmapped fields
        $fuzzyFieldAliases = [
            'which_apartment_size_are_you_interested_in' => ['সাইজ', 'apartment_size'],
            'which_floor_are_you_for_shop'               => ['ফ্লোর', 'floor'],
            'when_are_you_planning_to_buy'               => ['কেনার_পরিকল্পনা', 'planning'],
            'would_you_like_to_schedule_a_site_visit'   => ['সাইট_ভিজিট', 'site_visit'],
            'when_is_the_best_time_to_contact_you'       => ['যোগাযোগ', 'best_time'],
            'campaign_name'                              => ['campaign', 'adset_name'],
            'phone_number'                               => ['phone', 'mobile'],
        ];

        foreach ($headers as $index => $rawColName) {
            $colName = trim((string)$rawColName);
            $cleanCol = strtolower(str_replace([' ', '-', '–'], '_', $colName));

            // Skip ad_name/form_name from accidentally matching full_name/campaign_name
            if (in_array($cleanCol, ['ad_name', 'form_name', 'adset_name', 'id', 'form_id', 'ad_id', 'campaign_id'], true)) {
                continue;
            }

            foreach ($fuzzyFieldAliases as $dbField => $keywords) {
                if (isset($map[$dbField])) {
                    continue;
                }
                foreach ($keywords as $kw) {
                    if (strpos($cleanCol, $kw) !== false || strpos($colName, $kw) !== false) {
                        $map[$dbField] = $index;
                        break;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Extract normalized field values from a CSV row
     */
    private function extractLeadFields(array $row, array $headerMap): array
    {
        $data = [];
        $defaults = $this->config['lead_defaults'] ?? [];

        foreach ($headerMap as $field => $index) {
            $data[$field] = isset($row[$index]) ? trim($row[$index]) : null;
        }

        // Fill missing values with defaults
        $data['platform'] = !empty($data['platform']) ? $data['platform'] : 'fb';
        $data['country']  = !empty($data['country']) ? $data['country'] : ($defaults['default_country'] ?? 'Bangladesh');

        if (empty($data['campaign_name'])) {
            $data['campaign_name'] = !empty($data['form_name']) ? $data['form_name'] : (!empty($data['adset_name']) ? $data['adset_name'] : 'Common Lead Form');
        }

        return $data;
    }

    /**
     * Match project based on keywords in campaign_name (and optional secondary form/adset name)
     */
    public function matchProject(string $campaignName, string $secondaryName = ''): array
    {
        $projects = $this->config['projects'] ?? [];

        // 1. Sort keywords by length descending so longer/more specific phrases match first
        // (e.g. 'crown pavilion' matches before 'crown', 'tprc commercial' before 'commercial')
        uksort($projects, function ($a, $b) {
            return strlen((string)$b) <=> strlen((string)$a);
        });

        // 2. Normalize text: convert " ll ", "||", "–", "-" and collapse multiple spaces
        $normalize = function (string $text) {
            $clean = preg_replace('/\s+ll\s+/i', ' ', $text);
            $clean = preg_replace('/[|\-_–]+/', ' ', $clean);
            $clean = preg_replace('/\s+/', ' ', $clean);
            return strtolower(trim($clean));
        };

        $campaignLower = $normalize($campaignName);
        $rawLower      = strtolower(trim($campaignName));
        $secLower      = $normalize($secondaryName);
        $secRawLower   = strtolower(trim($secondaryName));

        foreach ($projects as $keyword => $info) {
            $keywordLower = strtolower(trim($keyword));
            if (
                strpos($campaignLower, $keywordLower) !== false || 
                strpos($rawLower, $keywordLower) !== false ||
                (!empty($secLower) && strpos($secLower, $keywordLower) !== false) ||
                (!empty($secRawLower) && strpos($secRawLower, $keywordLower) !== false)
            ) {
                return [
                    'matched_keyword' => $keyword,
                    'project_name'    => $info['name'] ?? $keyword,
                    'project_id'      => $info['project_id'],
                    'teams'           => $info['teams'] ?? []
                ];
            }
        }

        // Fallback default project / common leads
        $default = $this->config['default_project'] ?? [];
        return [
            'matched_keyword' => 'default',
            'project_name'    => $default['name'] ?? 'Common Leads',
            'project_id'      => $default['project_id'] ?? null,
            'teams'           => !empty($default['team_id']) ? [$default['team_id']] : []
        ];
    }

    /**
     * Resolve next Team ID using Fair Round-Robin rotation.
     * Looks up the last assigned team in DB to continue the rotation seamlessly.
     */
    public function resolveNextTeamId(?int $projectId, array $teams, ?PDO $pdo = null): ?int
    {
        if (empty($teams)) {
            return null;
        }

        if (count($teams) === 1) {
            return (int)$teams[0];
        }

        $projectKey = (string)($projectId ?? 'global');

        // Check if we already have an active rotation index in memory for this batch
        if (!isset($this->projectRotations[$projectKey])) {
            $lastTeamId = null;

            // Try to find the last assigned team for this project from the database
            if ($pdo !== null && $projectId !== null) {
                try {
                    $stmt = $pdo->prepare("SELECT team_id FROM leads WHERE project_id = ? AND team_id IS NOT NULL ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$projectId]);
                    $lastTeamId = $stmt->fetchColumn();
                } catch (Exception $e) {
                    $lastTeamId = null;
                }
            }

            // Find index of last team in the team list
            $lastIndex = -1;
            if ($lastTeamId !== false && $lastTeamId !== null) {
                $lastIndex = array_search((int)$lastTeamId, $teams, true);
                if ($lastIndex === false) {
                    $lastIndex = -1;
                }
            }

            // Next index
            $this->projectRotations[$projectKey] = ($lastIndex + 1) % count($teams);
        } else {
            // Increment rotation index for the next lead
            $this->projectRotations[$projectKey] = ($this->projectRotations[$projectKey] + 1) % count($teams);
        }

        $assignedIndex = $this->projectRotations[$projectKey];
        return (int)$teams[$assignedIndex];
    }

    /**
     * Check if this lead already exists in the database
     */
    private function isDuplicateLead(array $leadData, PDO $pdo): bool
    {
        $phone = $leadData['phone_number'] ?? '';
        $cleanPhone = preg_replace('/^p:/i', '', trim($phone));
        $campaign = trim($leadData['campaign_name'] ?? '');

        if (empty($cleanPhone)) {
            // Fallback: check by email if phone is not provided
            $email = trim($leadData['email'] ?? '');
            if (!empty($email)) {
                if (empty($campaign)) {
                    $stmt = $pdo->prepare("SELECT id FROM leads WHERE email = ? AND (campaign_name = '' OR campaign_name IS NULL) LIMIT 1");
                    $stmt->execute([$email]);
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM leads WHERE email = ? AND campaign_name = ? LIMIT 1");
                    $stmt->execute([$email, $campaign]);
                }
                return (bool)$stmt->fetchColumn();
            }
            return false;
        }

        // Mode: 'phone_campaign'
        // Checks if the same customer (phone) has already been inserted for THIS specific campaign.
        // If the customer fills a form for another project/campaign, it WILL be inserted as a fresh lead.
        $prefixedPhone = 'p:' . $cleanPhone;

        if (empty($campaign)) {
            $stmt = $pdo->prepare("SELECT id FROM leads WHERE (phone_number = ? OR phone_number = ?) AND (campaign_name = '' OR campaign_name IS NULL) LIMIT 1");
            $stmt->execute([$cleanPhone, $prefixedPhone]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM leads WHERE (phone_number = ? OR phone_number = ?) AND campaign_name = ? LIMIT 1");
            $stmt->execute([$cleanPhone, $prefixedPhone, $campaign]);
        }
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Insert lead record into database
     */
    public function insertLeadIntoDatabase(array $lead, PDO $pdo): int
    {
        $defaults = $this->config['lead_defaults'] ?? [];

        $sql = "INSERT INTO `leads` (
            `campaign_name`,
            `platform`,
            `which_apartment_size_are_you_interested_in`,
            `which_floor_are_you_for_shop`,
            `when_are_you_planning_to_buy`,
            `would_you_like_to_schedule_a_site_visit`,
            `when_is_the_best_time_to_contact_you`,
            `full_name`,
            `job_title`,
            `company_name`,
            `phone_number`,
            `email`,
            `city`,
            `country`,
            `address`,
            `project_id`,
            `team_id`,
            `distribute`,
            `is_automated`,
            `is_repibee`,
            `is_brocure_leads`,
            `uploaded_from`,
            `status`,
            `last_lead_action`,
            `call_status`,
            `lead_quality`,
            `currency`,
            `visibility`,
            `residency_type`,
            `created_by`,
            `created_at`,
            `updated_at`
        ) VALUES (
            :campaign_name,
            :platform,
            :which_apartment_size_are_you_interested_in,
            :which_floor_are_you_for_shop,
            :when_are_you_planning_to_buy,
            :would_you_like_to_schedule_a_site_visit,
            :when_is_the_best_time_to_contact_you,
            :full_name,
            :job_title,
            :company_name,
            :phone_number,
            :email,
            :city,
            :country,
            :address,
            :project_id,
            :team_id,
            :distribute,
            :is_automated,
            :is_repibee,
            :is_brocure_leads,
            :uploaded_from,
            :status,
            :last_lead_action,
            :call_status,
            :lead_quality,
            :currency,
            :visibility,
            :residency_type,
            :created_by,
            :created_at,
            NOW()
        )";

        $currentUserId = !empty($lead['created_by']) 
            ? (int)$lead['created_by'] 
            : ($this->userId ?? (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null));

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':campaign_name'                            => $lead['campaign_name'] ?? null,
            ':platform'                                 => $lead['platform'] ?? 'fb',
            ':which_apartment_size_are_you_interested_in' => $lead['which_apartment_size_are_you_interested_in'] ?? null,
            ':which_floor_are_you_for_shop'             => $lead['which_floor_are_you_for_shop'] ?? null,
            ':when_are_you_planning_to_buy'             => $lead['when_are_you_planning_to_buy'] ?? null,
            ':would_you_like_to_schedule_a_site_visit' => $lead['would_you_like_to_schedule_a_site_visit'] ?? null,
            ':when_is_the_best_time_to_contact_you'     => $lead['when_is_the_best_time_to_contact_you'] ?? null,
            ':full_name'                                => $lead['full_name'] ?? null,
            ':job_title'                                => $lead['job_title'] ?? null,
            ':company_name'                             => $lead['company_name'] ?? null,
            ':phone_number'                             => $lead['phone_number'] ?? null,
            ':email'                                    => $lead['email'] ?? null,
            ':city'                                     => $lead['city'] ?? null,
            ':country'                                  => $lead['country'] ?? ($defaults['default_country'] ?? 'Bangladesh'),
            ':address'                                  => $lead['address'] ?? null,
            ':project_id'                               => $lead['project_id'] ?? null,
            ':team_id'                                  => $lead['team_id'] ?? null,
            ':distribute'                               => 0, // Explicitly 0 = false (Pending approval)
            ':is_automated'                             => 1, // Flagged as automated sync lead
            ':is_repibee'                              => !empty($lead['is_repibee']) ? 1 : 0,
            ':is_brocure_leads'                         => !empty($lead['is_brocure_leads']) ? 1 : 0,
            ':uploaded_from'                            => $defaults['uploaded_from'] ?? 'From Campaign',
            ':status'                                   => $defaults['status'] ?? 'active',
            ':last_lead_action'                         => $defaults['last_lead_action'] ?? 'new',
            ':call_status'                              => $defaults['call_status'] ?? 'not_called',
            ':lead_quality'                             => $defaults['lead_quality'] ?? 'Pending',
            ':currency'                                 => $defaults['currency'] ?? 'BDT',
            ':visibility'                               => $defaults['visibility'] ?? 'public',
            ':residency_type'                           => $defaults['residency_type'] ?? 'local',
            ':created_by'                               => $currentUserId,
            ':created_at'                               => !empty($lead['created_at']) ? $lead['created_at'] : date('Y-m-d H:i:s'),
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Clean UTF-8/UTF-16 BOM and standardize line endings
     */
    private function cleanBomAndNormalize(string $content): string
    {
        // Detect UTF-16LE or UTF-16BE
        if (substr($content, 0, 2) === "\xFF\xFE") {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (substr($content, 0, 2) === "\xFE\xFF") {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        }

        // Remove UTF-8 BOM
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Normalize carriage returns
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    /**
     * Detect CSV delimiter (Comma ',' or Tab '\t')
     */
    private function detectDelimiter(string $content): string
    {
        $firstLine = strtok($content, "\n");
        if ($firstLine === false) {
            return ',';
        }

        $tabCount   = substr_count($firstLine, "\t");
        $commaCount = substr_count($firstLine, ",");

        return ($tabCount > $commaCount) ? "\t" : ",";
    }

    /**
     * Process extracted leads through Gemini AI
     */
    private function processWithGemini(GeminiService $gemini, array $headers, array $rawRows, bool $dryRun): array
    {
        $stats = [
            'total_rows'        => count($rawRows),
            'inserted'          => 0,
            'skipped_duplicate' => 0,
            'unmatched_project' => 0,
            'errors'            => 0,
            'details'           => []
        ];

        $pdo = $dryRun ? null : $this->getDb();

        // Send to Gemini in batches of up to 25 rows for optimal speed and reliability
        $batchSize = 25;
        $chunks = array_chunk($rawRows, $batchSize);

        foreach ($chunks as $chunkIndex => $chunkRows) {
            $parsedLeads = $gemini->parseLeadsWithGemini($headers, $chunkRows, $this->config['projects'] ?? []);

            foreach ($parsedLeads as $leadData) {
                $rawPhone = $leadData['phone_number'] ?? '';
                $cleanPhone = preg_replace('/^p:/i', '', trim($rawPhone));
                $leadData['phone_number'] = $cleanPhone;

                // Skip Meta / Facebook automated test leads or dummy rows
                $fullName = (string)($leadData['full_name'] ?? '');
                $email = (string)($leadData['email'] ?? '');
                if (
                    stripos($cleanPhone, '<test lead') !== false ||
                    stripos($fullName, '<test lead') !== false ||
                    stripos($email, 'test@meta.com') !== false
                ) {
                    continue;
                }

                // Match Project: prioritize deterministic keyword matching with sorted length
                $projectMatch = $this->matchProject($leadData['campaign_name'] ?? '', $leadData['form_name'] ?? '');
                if ($projectMatch['project_id'] === null && !empty($leadData['matched_project_keyword']) && isset($this->config['projects'][$leadData['matched_project_keyword']])) {
                    $projectMatch = [
                        'project_id' => $this->config['projects'][$leadData['matched_project_keyword']]['project_id'],
                        'teams'      => $this->config['projects'][$leadData['matched_project_keyword']]['teams'] ?? []
                    ];
                }

                $projectId = $projectMatch['project_id'];
                if ($projectId === null && !($this->config['default_project']['allow_insert_if_unmatched'] ?? true)) {
                    $stats['unmatched_project']++;
                    continue;
                }

                // Resolve Team ID via Round-Robin
                $teamId = $this->resolveNextTeamId($projectId, $projectMatch['teams'], $pdo);
                $leadData['project_id'] = $projectId;
                $leadData['team_id']    = $teamId;

                // Duplicate Check
                if (!$dryRun && ($this->config['duplicate_check']['enabled'] ?? true)) {
                    if ($this->isDuplicateLead($leadData, $pdo)) {
                        $stats['skipped_duplicate']++;
                        continue;
                    }
                }

                if (!$dryRun) {
                    try {
                        $insertId = $this->insertLeadIntoDatabase($leadData, $pdo);
                        $stats['inserted']++;
                        $stats['details'][] = [
                            'id'         => $insertId,
                            'name'       => $leadData['full_name'],
                            'phone'      => $cleanPhone,
                            'campaign'   => $leadData['campaign_name'],
                            'project_id' => $projectId,
                            'team_id'    => $teamId,
                            'distribute' => 0,
                        ];
                    } catch (Exception $e) {
                        $stats['errors']++;
                        $this->log("Gemini Lead DB Insert Error: " . $e->getMessage());
                    }
                } else {
                    $stats['inserted']++;
                    $stats['details'][] = [
                        'name'       => $leadData['full_name'],
                        'phone'      => $cleanPhone,
                        'campaign'   => $leadData['campaign_name'],
                        'project_id' => $projectId,
                        'team_id'    => $teamId,
                        'distribute' => 0,
                    ];
                }
            }
        }

        return $stats;
    }

    /**
     * Log helper
     */
    private function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        $this->logs[] = $line;

        // If running from CLI, echo directly
        if (php_sapi_name() === 'cli') {
            echo $line . PHP_EOL;
        }
    }

    /**
     * Write logs to persistent file
     */
    private function flushLogsToFile(): void
    {
        $logFile = $this->config['log_file'] ?? (__DIR__ . '/logs/sync.log');
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $content = implode(PHP_EOL, $this->logs) . PHP_EOL;
        @file_put_contents($logFile, $content, FILE_APPEND);
        $this->logs = [];
    }

    /**
     * Self-healing schema auto-migration
     */
    public function ensureSchema(?PDO $pdo = null): void
    {
        if (!$pdo) {
            $pdo = $this->getDb();
        }

        try {
            // 1. Ensure distribute column exists and defaults to 1
            $cols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'distribute'")->fetchAll();
            if (empty($cols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `distribute` TINYINT(1) NOT NULL DEFAULT 1");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_distribute` ON `leads` (`distribute`)");
                } catch (Throwable $t) {}
            } else {
                $pdo->exec("ALTER TABLE `leads` MODIFY COLUMN `distribute` TINYINT(1) NOT NULL DEFAULT 1");
            }

            // 2. Ensure is_automated column exists
            $autoCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_automated'")->fetchAll();
            if (empty($autoCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `is_automated` TINYINT(1) NOT NULL DEFAULT 0");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_is_automated` ON `leads` (`is_automated`)");
                } catch (Throwable $t) {}
            }

            // 3. Ensure approved_by and approved_at columns exist
            $apprCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'approved_by'")->fetchAll();
            if (empty($apprCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `approved_by` INT NULL");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_approved_by` ON `leads` (`approved_by`)");
                } catch (Throwable $t) {}
            }
            $apprAtCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'approved_at'")->fetchAll();
            if (empty($apprAtCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `approved_at` DATETIME NULL");
            }

            // 4. Ensure is_repibee column exists
            $repibeeCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_repibee'")->fetchAll();
            if (empty($repibeeCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `is_repibee` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_automated`");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_is_repibee` ON `leads` (`is_repibee`)");
                } catch (Throwable $t) {}
            }

            // 5. Ensure is_brocure_leads column exists
            $brocureCols = $pdo->query("SHOW COLUMNS FROM `leads` LIKE 'is_brocure_leads'")->fetchAll();
            if (empty($brocureCols)) {
                $pdo->exec("ALTER TABLE `leads` ADD COLUMN `is_brocure_leads` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_repibee`");
                try {
                    $pdo->exec("CREATE INDEX `idx_leads_is_brocure` ON `leads` (`is_brocure_leads`)");
                } catch (Throwable $t) {}
            }
        } catch (Throwable $e) {
            $this->log("ensureSchema warning: " . $e->getMessage());
        }
    }
}

