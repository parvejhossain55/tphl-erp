<?php

class ApiController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * GET /api/pending-approvals
     */
    public function pendingApprovals()
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getPendingApprovals($this->db));
        exit;
    }

    /**
     * GET /api/project-timeline-progress/(\d+)
     */
    public function projectTimelineProgress($projectId)
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getProjectTimelineAndProgress($this->db, (int)$projectId));
        exit;
    }

    /**
     * GET /api/site-engineer-performance
     */
    public function siteEngineerPerformance()
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getSiteEngineerPerformance($this->db));
        exit;
    }

    /**
     * GET /api/project-stock-levels/(\d+)
     */
    public function projectStockLevels($projectId)
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getProjectStockMaterials($this->db, (int)$projectId));
        exit;
    }

    /**
     * GET /api/sales-executive-performance
     */
    public function salesExecutivePerformance()
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getSalesExecutivePerformance($this->db));
        exit;
    }

    /**
     * GET /api/flat-inventory-summary
     * GET /api/flat-inventory-summary/(\d+)
     */
    public function flatInventorySummary($projectId = null)
    {
        header('Content-Type: application/json');
        $pid = $projectId !== null ? (int)$projectId : null;
        echo json_encode(AiDataService::getFlatSalesInventorySummary($this->db, $pid));
        exit;
    }

    /**
     * GET /api/financial-summary
     */
    public function financialSummary()
    {
        header('Content-Type: application/json');
        echo json_encode(AiDataService::getCompanyFinancialSummary($this->db));
        exit;
    }
    /**
     * GET /api/project-expenses
     * Accepts project_name via GET or POST parameters
     * Authenticates with x-api-key header
     */
    public function projectExpenses()
    {
        header('Content-Type: application/json');
        
        // 1. Authenticate with API Key
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['x-api-key'] ?? $headers['X-Api-Key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        global $config;
        $validKey = $config['premium_homes_api_key'] ?? 'tphl_erp_key_9f8e7d6c5b4a392817065';
        
        if ($apiKey !== $validKey) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized API Key"]);
            exit;
        }

        $projectId = isset($_REQUEST['project_id']) && is_numeric($_REQUEST['project_id']) ? (int)$_REQUEST['project_id'] : null;
        $projectName = $_REQUEST['project_name'] ?? null;
        
        if (!$projectId && !$projectName) {
            echo json_encode(["success" => false, "message" => "Missing project_name or project_id parameter"]);
            exit;
        }

        // Find Project
        if ($projectId) {
            $stmt = $this->db->prepare("SELECT id, name FROM projects WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $projectId);
        } else {
            $stmt = $this->db->prepare("SELECT id, name FROM projects WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $projectName);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!$res) {
            echo json_encode(["success" => false, "message" => "Project not found"]);
            exit;
        }

        $projectId = (int)$res['id'];
        
        require_once __DIR__ . '/../ai/AiDataService.php';
        $expenseDetails = AiDataService::getProjectExpenseDetails($this->db, $projectId);
        
        $expenseDetails['project_id'] = $projectId;
        $expenseDetails['project_name'] = $res['name'];

        echo json_encode(["success" => true, "data" => $expenseDetails], JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * GET /api/client-payment-info
     * Accepts phone, project_name, flat_name via GET or POST parameters
     * Authenticates with x-api-key header
     */
    public function clientPaymentInfo()
    {
        header('Content-Type: application/json');
        
        // 1. Authenticate with API Key
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['x-api-key'] ?? $headers['X-Api-Key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        global $config;
        $validKey = $config['premium_homes_api_key'] ?? 'tphl_erp_key_9f8e7d6c5b4a392817065';
        
        if ($apiKey !== $validKey) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized API Key"]);
            exit;
        }

        $phone = $_REQUEST['phone'] ?? null;
        $projectName = $_REQUEST['project_name'] ?? null;
        $flatName = $_REQUEST['flat_name'] ?? null;

        $bookingsToProcess = [];
        $processedBookingIds = [];
        $erpClient = null;

        // A. Match by phone
        if ($phone) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            $cleanPhone = $this->db->real_escape_string($cleanPhone);
            
            $res = $this->db->query("SELECT * FROM leads WHERE phone LIKE '%$cleanPhone%' OR phone_number LIKE '%$cleanPhone%' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $erpClient = $res->fetch_assoc();
                
                $bRes = $this->db->query("SELECT * FROM lead_bookings WHERE lead_id = " . (int)$erpClient['id'] . " AND booking_status != 'cancelled'");
                if ($bRes) {
                    while ($b = $bRes->fetch_assoc()) {
                        $bookingsToProcess[] = $b;
                        $processedBookingIds[] = $b['id'];
                    }
                }
            }
        }

        // B. Match by project/flat
        if ($projectName && $flatName) {
            $cleanProject = $this->db->real_escape_string($projectName);
            $res = $this->db->query("SELECT * FROM projects WHERE name LIKE '%$cleanProject%' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $erpProject = $res->fetch_assoc();
                
                $cleanPropFlatNo = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $flatName));
                
                $fRes = $this->db->query("SELECT * FROM flats WHERE project_id = " . (int)$erpProject['id']);
                $matchedFlat = null;
                if ($fRes) {
                    while ($f = $fRes->fetch_assoc()) {
                        $cleanErpFlatNo = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $f['flat_name']));
                        if ($cleanErpFlatNo === $cleanPropFlatNo) {
                            $matchedFlat = $f;
                            break;
                        }
                    }
                }
                
                if ($matchedFlat) {
                    $bRes = $this->db->query("SELECT * FROM lead_bookings WHERE project_id = " . (int)$erpProject['id'] . " AND flat_id = " . (int)$matchedFlat['id'] . " AND booking_status != 'cancelled' LIMIT 1");
                    if ($bRes && $bRes->num_rows > 0) {
                        $booking = $bRes->fetch_assoc();
                        if (!in_array($booking['id'], $processedBookingIds)) {
                            $bookingsToProcess[] = $booking;
                            $processedBookingIds[] = $booking['id'];
                            
                            if (!$erpClient) {
                                $cRes = $this->db->query("SELECT * FROM leads WHERE id = " . (int)$booking['lead_id']);
                                if ($cRes && $cRes->num_rows > 0) {
                                    $erpClient = $cRes->fetch_assoc();
                                }
                            }
                        }
                    }
                }
            }
        }

        $responseData = [
            "client_info" => [],
            "bookings" => []
        ];

        if ($erpClient) {
            $responseData['client_info'] = [
                'lead_id' => $erpClient['id'],
                'client_name' => $erpClient['full_name'],
                'phone' => $erpClient['phone'] ?: $erpClient['phone_number'],
                'email' => $erpClient['email'] ?: '',
                'company' => $erpClient['company'] ?: '',
                'designation' => $erpClient['designation'] ?: ''
            ];
        } else {
            echo json_encode(["success" => false, "message" => "User not found in ERP Database!"]);
            exit;
        }

        $finalDataArray = [];
        if (empty($bookingsToProcess) && $erpClient) {
            $finalDataArray[] = [
                "client_info" => $responseData['client_info'],
                "bookings" => []
            ];
        }

        foreach ($bookingsToProcess as $booking) {
            $projectId = (int)$booking['project_id'];
            $flatId = (int)$booking['flat_id'];
            $bookingId = (int)$booking['id'];

            $project = null;
            if ($projectId) {
                $pRes = $this->db->query("SELECT * FROM projects WHERE id = $projectId");
                if ($pRes && $pRes->num_rows > 0) $project = $pRes->fetch_assoc();
            }

            $flat = null;
            if ($flatId) {
                $fRes = $this->db->query("SELECT * FROM flats WHERE id = $flatId");
                if ($fRes && $fRes->num_rows > 0) $flat = $fRes->fetch_assoc();
            }

            $agentName = "System";
            $aRes = $this->db->query("SELECT created_by FROM lead_payments WHERE booking_id = $bookingId AND payment_type = 'booking_money' LIMIT 1");
            if ($aRes && $aRes->num_rows > 0) {
                $agentPayment = $aRes->fetch_assoc();
                if (!empty($agentPayment['created_by'])) {
                    $uRes = $this->db->query("SELECT full_name FROM users WHERE id = " . (int)$agentPayment['created_by']);
                    if ($uRes && $uRes->num_rows > 0) {
                        $userRow = $uRes->fetch_assoc();
                        $agentName = $userRow['full_name'];
                    }
                }
            }

            // Updated Price Logic: Use Flats table price
            $flatPrice = $flat ? (float)$flat['price_value'] : 0;
            if ($flatPrice <= 0) {
                $flatPrice = (float)$booking['agreement_value'];
            }
            
            $discountAmount = (float)$booking['discount_amount'];
            $totalPayable = $flatPrice - $discountAmount;

            $totalPaid = 0;
            $bookingMoneyPaid = 0;
            $registrationPaid = 0;
            $mutationCostPaid = 0;
            $devChargePaid = 0;
            $emiPaid = 0;
            $unverifiedEmiPaid = 0; 
            $totalDueEmi = 0;
            $totalServiceFee = 0;
            $additionalLandSharePaid = 0;
            $bulkEmiDiscount = 0;
            $penaltyCharged = 0;
            $penaltyPaid = 0;
            $penaltyWaived = 0;

            // Arrays for breakdowns
            $landShareHistory = [];
            $devChargeHistory = [];

            // Query updated to order by date for correct history timeline
            $pRes = $this->db->query("SELECT * FROM lead_payments WHERE booking_id = $bookingId AND payment_status IN ('paid', 'partially_paid', 'pending') AND (verification_status != 'rejected' OR verification_status IS NULL) ORDER BY payment_date ASC, id ASC");
            if ($pRes) {
                while ($p = $pRes->fetch_assoc()) {
                    $amt = (float)$p['amount'];
                    $ptype = $p['payment_type'];
                    $vStatus = $p['verification_status'];

                    // Capture Land Share and Registration History
                    if (in_array($ptype, ['booking_money', 'registration', 'mutation_cost', 'land_share'])) {
                        $landShareHistory[] = [
                            "payment_id" => $p['id'],
                            "payment_type" => $ptype,
                            "amount_paid" => $amt,
                            "payment_date" => $p['payment_date'] ?: 'N/A',
                            "verification_status" => $vStatus
                        ];
                    }

                    // Capture Development Charge History
                    if ($ptype === 'development_charge') {
                        $devChargeHistory[] = [
                            "payment_id" => $p['id'],
                            "payment_type" => $ptype,
                            "amount_paid" => $amt,
                            "payment_date" => $p['payment_date'] ?: 'N/A',
                            "verification_status" => $vStatus
                        ];
                    }

                    if ($vStatus !== 'rejected') {
                        $totalPaid += $amt;

                        if ($ptype === 'booking_money') $bookingMoneyPaid += $amt;
                        else if ($ptype === 'registration') $registrationPaid += $amt;
                        else if ($ptype === 'mutation_cost') $mutationCostPaid += $amt;
                        else if ($ptype === 'development_charge') $devChargePaid += $amt;
                        else if (in_array($ptype, ['emi', 'emi_due_settlement'])) {
                            $emiPaid += $amt;
                            $totalServiceFee += (float)$p['service_fee'];
                        }
                        else if ($ptype === 'land_share') $additionalLandSharePaid += $amt;

                        $totalDueEmi += (float)$p['due_emi_amount'];
                        $bulkEmiDiscount += (float)$p['bulk_emi_discount'];
                        $penaltyCharged += (float)$p['penalty_amount'];

                        if ($vStatus !== 'verified') {
                            if (in_array($ptype, ['emi', 'emi_due_settlement'])) {
                                $unverifiedEmiPaid += $amt;
                            }
                        }
                    }
                    $penaltyPaid += (float)$p['penalty_paid'];
                    $penaltyWaived += (float)$p['penalty_waived'];
                }
            }

            $totalDue = $totalPayable - $totalPaid;

            $landShareTarget = $flat ? (float)$flat['land_share_value'] : 0;
            $devChargeTarget = $flat ? (float)$flat['development_charges'] : 0;
            $totalLandSharePaid = $bookingMoneyPaid + $registrationPaid + $mutationCostPaid + $additionalLandSharePaid;

            $constructionFee = max(0, $flatPrice - ($landShareTarget + $devChargeTarget));

            $emiStatsRes = $this->db->query("SELECT total_emi_no FROM lead_payments WHERE booking_id = $bookingId AND total_emi_no IS NOT NULL LIMIT 1");
            $totalEmiCount = 0;
            if ($emiStatsRes && $emiStatsRes->num_rows > 0) {
                $emiStats = $emiStatsRes->fetch_assoc();
                $totalEmiCount = (int)$emiStats['total_emi_no'];
            }
            $perEmiAmount = ($totalEmiCount > 0) ? round($constructionFee / $totalEmiCount) : 0;

            $completedEmiRes = $this->db->query("SELECT COUNT(*) as cnt FROM emi_schedules es JOIN lead_payments lp ON es.lead_payment_id = lp.id WHERE lp.booking_id = $bookingId AND lp.verification_status = 'verified'");
            $completedEmiCount = 0;
            if ($completedEmiRes && $completedEmiRes->num_rows > 0) {
                $completedEmiCount = (int)$completedEmiRes->fetch_assoc()['cnt'];
            }

            $unverifiedEmiRes = $this->db->query("SELECT COUNT(*) as cnt FROM emi_schedules es JOIN lead_payments lp ON es.lead_payment_id = lp.id WHERE lp.booking_id = $bookingId AND lp.verification_status != 'verified'");
            $unverifiedEmiCount = 0;
            if ($unverifiedEmiRes && $unverifiedEmiRes->num_rows > 0) {
                $unverifiedEmiCount = (int)$unverifiedEmiRes->fetch_assoc()['cnt'];
            }
            
            // EMI History Breakdown Query
            $emiHistory = [];
            $emiQuery = $this->db->query("
                SELECT lp.id, lp.amount, lp.payment_date, lp.payment_type, lp.verification_status, 
                       es.emi_no, es.due_date, es.emi_amount as target_amount
                FROM lead_payments lp 
                LEFT JOIN emi_schedules es ON lp.id = es.lead_payment_id 
                WHERE lp.booking_id = $bookingId 
                  AND lp.payment_type IN ('emi', 'emi_due_settlement')
                  AND lp.payment_status IN ('paid', 'partially_paid', 'pending') AND (lp.verification_status != 'rejected' OR lp.verification_status IS NULL)
                ORDER BY lp.payment_date ASC, lp.id ASC
            ");
            
            if ($emiQuery) {
                while ($row = $emiQuery->fetch_assoc()) {
                    $paid = (float)$row['amount'];
                    $target = (float)$row['target_amount'];
                    
                    if ($row['emi_no'] && $target <= 0) {
                        $target = $perEmiAmount;
                    }
                    
                    $due_for_this_emi = 0;
                    if ($row['emi_no']) {
                        $due_for_this_emi = max(0, $target - $paid);
                    }

                    $emiHistory[] = [
                        "payment_id" => $row['id'],
                        "payment_type" => $row['payment_type'], 
                        "target_amount" => $row['emi_no'] ? $target : 0, 
                        "amount_paid" => $paid, 
                        "due_amount" => $due_for_this_emi, 
                        "payment_date" => $row['payment_date'] ?: 'N/A',
                        "emi_number" => $row['emi_no'] ? (int)$row['emi_no'] : null,
                        "due_date" => $row['due_date'] ?: 'N/A',
                        "verification_status" => $row['verification_status']
                    ];
                }
            }

            $bookingBlock = [
                "booking_id" => $bookingId,
                "agent_info" => ["agent_name" => $agentName],
                "project_info" => [
                    "project_id" => $project ? $project['id'] : null,
                    "project_name" => $project ? $project['name'] : 'N/A',
                    "flat_id" => $flat ? $flat['id'] : null,
                    "flat_name" => $flat ? $flat['flat_name'] : 'N/A',
                    "floor_no" => $flat ? $flat['floor_no'] : 'N/A',
                    "sqft" => $flat ? $flat['sqft'] : 0,
                    
                    // আপডেট করা কলামগুলো (total_share এখন project থেকে নিবে)
                    "building_type" => $project ? ($project['building_type'] ?? 'N/A') : 'N/A',
                    "unit_per_floor" => $project ? ($project['unit_per_floor'] ?? 'N/A') : 'N/A',
                    "total_share" => $project ? ($project['total_share'] ?? 'N/A') : 'N/A'
                ],


                "payment_info" => [
                    "total_price" => $totalPayable,
                    "total_paid" => $totalPaid,
                    "total_due" => $totalDue,
                    "land_share_info" => [
                        "total_land_share_amount" => $landShareTarget,
                        "booking_money_paid" => $bookingMoneyPaid,
                        "registration_money_paid" => $registrationPaid,
                        "mutation_cost_paid" => $mutationCostPaid,
                        "additional_land_share_paid" => $additionalLandSharePaid,
                        "total_land_share_paid" => $totalLandSharePaid,
                        "land_share_due" => max(0, $landShareTarget - $totalLandSharePaid),
                        // Land Share Breakdown
                        "land_share_history" => $landShareHistory
                    ],
                    "development_charge_info" => [
                        "total_development_charge_target" => $devChargeTarget,
                        "development_charge_paid" => $devChargePaid,
                        "development_charge_due" => max(0, $devChargeTarget - $devChargePaid),
                        // Development Charge Breakdown
                        "development_charge_history" => $devChargeHistory
                    ],
                    "emi_info" => [
                        "number_of_emis_paid" => ($completedEmiCount + $unverifiedEmiCount),
                        "per_emi_amount" => $perEmiAmount,
                        "current_running_emi_number" => ($completedEmiCount + $unverifiedEmiCount) + 1,
                        "total_emi_amount_paid" => ($emiPaid + $unverifiedEmiPaid),

                        "total_verified_emi_count" => $completedEmiCount,
                        "total_unverified_emi_count" => $unverifiedEmiCount,
                        "verified_emi_amount_paid" => $emiPaid,
                        "unverified_emi_amount_paid" => $unverifiedEmiPaid,
                        "due_emi_amount" => $totalDueEmi,
                        "total_service_fee_paid" => $totalServiceFee,
                        
                        "penalty_info" => [
                            "total_penalty_charged" => $penaltyCharged,
                            "total_penalty_paid" => $penaltyPaid,
                            "total_penalty_waived" => $penaltyWaived,
                            "has_unpaid_penalty" => ($penaltyCharged > ($penaltyPaid + $penaltyWaived))
                        ],
                        
                        "emi_history" => $emiHistory
                    ]
                ]
            ];  

            $finalDataArray[] = [
                "client_info" => $responseData['client_info'],
                "bookings" => [$bookingBlock]
            ];
        }

        echo json_encode([
            "success" => true,
            "message" => count($finalDataArray) > 0 && !empty($finalDataArray[0]['bookings']) ? "Dynamic payment data loaded." : "Static user data loaded.",
            "data" => $finalDataArray
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }


    /**
     * POST /api/client-authenticate
     * Authenticates client from onboard_clients and returns full profile + savedProperties
     */
    public function clientAuthenticate()
    {
        header('Content-Type: application/json');

        // 1. Authenticate API Key
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['x-api-key'] ?? $headers['X-Api-Key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        global $config;
        $validKey = $config['premium_homes_api_key'] ?? 'tphl_erp_key_9f8e7d6c5b4a392817065';
        if ($apiKey !== $validKey) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized API Key"]);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $inputData = json_decode($rawInput, true) ?: [];
        $email = trim($_POST['email'] ?? $inputData['email'] ?? $_REQUEST['email'] ?? '');
        $password = trim($_POST['password'] ?? $inputData['password'] ?? $_REQUEST['password'] ?? '');

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Email and password are required!"]);
            exit;
        }

        $cleanEmail = strtolower($email);
        $escapedEmail = $this->db->real_escape_string($cleanEmail);

        $query = "SELECT * FROM onboard_clients WHERE LOWER(login_email) = '$escapedEmail' OR LOWER(email) = '$escapedEmail' LIMIT 1";
        $res = $this->db->query($query);
        if (!$res || $res->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Client not found in ERP."]);
            exit;
        }

        $onboard = $res->fetch_assoc();

        $isPasswordValid = false;
        if (password_verify($password, $onboard['password'])) {
            $isPasswordValid = true;
        } elseif ($onboard['password'] === $password) {
            $isPasswordValid = true;
        }

        if (!$isPasswordValid) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid credentials!"]);
            exit;
        }

        $leadId = (int)$onboard['lead_id'];
        if (!$leadId && !empty($onboard['lead_booking_id'])) {
            $bRes = $this->db->query("SELECT lead_id FROM lead_bookings WHERE id = " . (int)$onboard['lead_booking_id'] . " LIMIT 1");
            if ($bRes && $bRes->num_rows > 0) {
                $bRow = $bRes->fetch_assoc();
                $leadId = (int)$bRow['lead_id'];
            }
        }

        $clientData = $this->buildClientData($leadId, $onboard);

        echo json_encode([
            "success" => true,
            "message" => "Authentication successful.",
            "client"  => $clientData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * GET /api/client-details
     * Returns full profile + savedProperties for identifier (lead_id, email, phone)
     */
    public function clientDetails()
    {
        header('Content-Type: application/json');

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $apiKey = $headers['x-api-key'] ?? $headers['X-Api-Key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        global $config;
        $validKey = $config['premium_homes_api_key'] ?? 'tphl_erp_key_9f8e7d6c5b4a392817065';
        if ($apiKey !== $validKey) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Unauthorized API Key"]);
            exit;
        }

        $identifier = trim($_GET['identifier'] ?? $_REQUEST['identifier'] ?? '');
        if (empty($identifier)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Identifier required!"]);
            exit;
        }

        $cleanId = is_numeric($identifier) ? (int)$identifier : null;
        $escaped = $this->db->real_escape_string($identifier);

        $onboard = null;
        $q = "SELECT * FROM onboard_clients WHERE LOWER(login_email) = LOWER('$escaped') OR LOWER(email) = LOWER('$escaped')";
        if ($cleanId) {
            $q .= " OR lead_id = $cleanId OR id = $cleanId";
        }
        $q .= " LIMIT 1";

        $res = $this->db->query($q);
        if ($res && $res->num_rows > 0) {
            $onboard = $res->fetch_assoc();
        }

        $leadId = $onboard ? (int)$onboard['lead_id'] : ($cleanId ?: 0);
        if (!$leadId && !$onboard) {
            $lRes = $this->db->query("SELECT * FROM leads WHERE email = '$escaped' OR phone LIKE '%$escaped%' OR phone_number LIKE '%$escaped%' LIMIT 1");
            if ($lRes && $lRes->num_rows > 0) {
                $lead = $lRes->fetch_assoc();
                $leadId = (int)$lead['id'];
            }
        }

        if (!$leadId && !$onboard) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Client not found."]);
            exit;
        }

        $clientData = $this->buildClientData($leadId, $onboard);
        echo json_encode([
            "success" => true,
            "client"  => $clientData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function buildClientData($leadId, $onboard = null)
    {
        $lead = null;
        if ($leadId) {
            $lRes = $this->db->query("SELECT * FROM leads WHERE id = " . (int)$leadId . " LIMIT 1");
            if ($lRes && $lRes->num_rows > 0) {
                $lead = $lRes->fetch_assoc();
            }
        }

        $name = $lead ? $lead['full_name'] : ($onboard['login_email'] ?? $onboard['email'] ?? 'Client');
        $phone = $lead ? ($lead['phone'] ?: ($lead['phone_number'] ?? '')) : '';
        $email = $onboard ? ($onboard['login_email'] ?: $onboard['email']) : ($lead['email'] ?? '');

        // Fetch bookings for this lead (by lead_id, lead_booking_id, or phone match)
        $bookings = [];
        $bookingIds = [];

        if ($leadId) {
            $bRes = $this->db->query("SELECT * FROM lead_bookings WHERE lead_id = " . (int)$leadId . " AND booking_status != 'cancelled'");
            if ($bRes) {
                while ($b = $bRes->fetch_assoc()) {
                    if (!in_array($b['id'], $bookingIds)) {
                        $bookings[] = $b;
                        $bookingIds[] = $b['id'];
                    }
                }
            }
        }

        if (!empty($onboard['lead_booking_id'])) {
            $bRes = $this->db->query("SELECT * FROM lead_bookings WHERE id = " . (int)$onboard['lead_booking_id'] . " AND booking_status != 'cancelled' LIMIT 1");
            if ($bRes && $bRes->num_rows > 0) {
                $b = $bRes->fetch_assoc();
                if (!in_array($b['id'], $bookingIds)) {
                    $bookings[] = $b;
                    $bookingIds[] = $b['id'];
                }
            }
        }

        if (empty($bookings) && !empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanPhone) >= 8) {
                $last8 = substr($cleanPhone, -8);
                $bRes = $this->db->query("SELECT lb.* FROM lead_bookings lb INNER JOIN leads l ON lb.lead_id = l.id WHERE (l.phone LIKE '%$last8%' OR l.phone_number LIKE '%$last8%') AND lb.booking_status != 'cancelled'");
                if ($bRes) {
                    while ($b = $bRes->fetch_assoc()) {
                        if (!in_array($b['id'], $bookingIds)) {
                            $bookings[] = $b;
                            $bookingIds[] = $b['id'];
                        }
                    }
                }
            }
        }

        $savedProperties = [];
        foreach ($bookings as $b) {
            $projectId = (int)$b['project_id'];
            $flatId = (int)$b['flat_id'];
            $bookingId = (int)$b['id'];

            $project = null;
            if ($projectId) {
                $pRes = $this->db->query("SELECT * FROM projects WHERE id = $projectId LIMIT 1");
                if ($pRes && $pRes->num_rows > 0) $project = $pRes->fetch_assoc();
            }

            $flat = null;
            if ($flatId) {
                $fRes = $this->db->query("SELECT * FROM flats WHERE id = $flatId LIMIT 1");
                if ($fRes && $fRes->num_rows > 0) $flat = $fRes->fetch_assoc();
            }

            $flatPrice = $flat ? (float)$flat['price_value'] : 0;
            if ($flatPrice <= 0) {
                $flatPrice = (float)$b['agreement_value'];
            }
            $discountAmount = (float)$b['discount_amount'];
            $totalPayable = $flatPrice - $discountAmount;

            $totalPaid = 0;
            $bookingMoneyPaid = 0;
            $landSharePaid = 0;
            $devChargePaid = 0;
            $emiPaid = 0;
            $totalServiceFee = 0;

            $pRes = $this->db->query("SELECT * FROM lead_payments WHERE booking_id = $bookingId AND payment_status IN ('paid', 'partially_paid', 'pending') AND (verification_status != 'rejected' OR verification_status IS NULL)");
            if ($pRes) {
                while ($p = $pRes->fetch_assoc()) {
                    $amt = (float)$p['amount'];
                    $totalPaid += $amt;
                    if ($p['payment_type'] === 'booking_money') $bookingMoneyPaid += $amt;
                    if (in_array($p['payment_type'], ['booking_money', 'registration', 'mutation_cost', 'land_share'])) {
                        $landSharePaid += $amt;
                    }
                    if ($p['payment_type'] === 'development_charge') $devChargePaid += $amt;
                    if (in_array($p['payment_type'], ['emi', 'emi_due_settlement'])) {
                        $emiPaid += $amt;
                        $totalServiceFee += (float)($p['service_fee'] ?? 0);
                    }
                }
            }

            $totalDue = max(0, $totalPayable - $totalPaid);

            // Project-wise camera resolution from cameras table
            $projectVideoLink1 = '';
            $projectVideoLink2 = '';
            if ($projectId) {
                $camRes = $this->db->query("SELECT * FROM cameras WHERE project_id = " . (int)$projectId . " AND status = 'active' ORDER BY id ASC LIMIT 2");
                if ($camRes && $camRes->num_rows > 0) {
                    $cams = [];
                    while ($camRow = $camRes->fetch_assoc()) {
                        $rawCam = trim($camRow['hls_path'] ?: ($camRow['rtsp_url'] ?? ''));
                        if ($rawCam) {
                            $cams[] = str_starts_with($rawCam, 'http') ? $rawCam : ('https://tphlcamera.xyz/' . ltrim($rawCam, '/'));
                        }
                    }
                    if (!empty($cams[0])) $projectVideoLink1 = $cams[0];
                    if (!empty($cams[1])) $projectVideoLink2 = $cams[1];
                }
            }

            // Priority: cameras table (Project-wise) -> onboard_clients -> empty
            $videoLink = !empty($projectVideoLink1) ? $projectVideoLink1 : (!empty($onboard['cameraLink1']) ? $onboard['cameraLink1'] : '');
            $videoLink2 = !empty($projectVideoLink2) ? $projectVideoLink2 : (!empty($onboard['cameraLink2']) ? $onboard['cameraLink2'] : '');
            $moneyReceiptLink = $onboard['moneyReceiptLink'] ?? '';
            $purchaseDetailsLink = $onboard['purchaseDetailsLink'] ?? '';

            $image = '';
            if ($project && !empty($project['image'])) {
                $image = str_starts_with($project['image'], 'http')
                    ? $project['image']
                    : "https://api.dpremiumhomes.com/assets/Property/" . $project['image'];
            }

            $savedProperties[] = [
                "id"                   => (int)($project['id'] ?? $b['project_id']),
                "slug"                 => $project['slug'] ?? '',
                "name"                 => $project['name'] ?? '',
                "location"             => $project['location'] ?? '',
                "image"                => $image,
                "flatNo"               => $flat['flat_name'] ?? '',
                "flatSize"             => $flat && !empty($flat['sqft']) ? $flat['sqft'] . ' sqft' : '',
                "price"                => number_format($totalPayable),
                "partial_payment"      => number_format($totalPaid),
                "due_payment"          => number_format($totalDue),
                "discount"             => $discountAmount ? number_format($discountAmount) : '',
                "emi_per_month"        => '',
                "total_emi_paid"       => $emiPaid ? number_format($emiPaid) : '',
                "remaining_emi"        => '',
                "emi_duration"         => '',
                "emi_completed"        => '',
                "emi_remaining"        => '',
                "next_emi_date"        => '',
                "booking_money"        => $bookingMoneyPaid ? number_format($bookingMoneyPaid) : '',
                "land_share"           => $landSharePaid ? number_format($landSharePaid) : '',
                "development_charges"  => $devChargePaid ? number_format($devChargePaid) : '',
                "construction_cost"    => '',
                "service_charges"      => $totalServiceFee ? number_format($totalServiceFee) : '',
                "videoLink"            => $videoLink,
                "videoLink2"           => $videoLink2,
                "moneyReceiptLink"     => $moneyReceiptLink,
                "purchaseDetailsLink"  => $purchaseDetailsLink,
                "estimatedDate"        => '2028',
            ];
        }

        return [
            'id'              => (int)($leadId ?: ($onboard['id'] ?? 0)),
            'lead_id'         => (int)$leadId,
            'email'           => $email,
            'name'            => $name,
            'phone'           => $phone,
            'savedProperties' => $savedProperties,
            'lastUpdated'     => date('Y-m-d')
        ];
    }
}
