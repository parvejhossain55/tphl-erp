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

            $agreementValue = (float)$booking['agreement_value'];
            $discountAmount = (float)$booking['discount_amount'];
            $totalPayable = $agreementValue - $discountAmount;

            $totalPaid = 0;
            $bookingMoneyPaid = 0;
            $registrationPaid = 0;
            $mutationCostPaid = 0;
            $devChargePaid = 0;
            $emiPaid = 0;
            $totalDueEmi = 0;
            $totalServiceFee = 0;
            $additionalLandSharePaid = 0;
            $bulkEmiDiscount = 0;
            $penaltyCharged = 0;
            $penaltyPaid = 0;
            $penaltyWaived = 0;

            $pRes = $this->db->query("SELECT * FROM lead_payments WHERE booking_id = $bookingId AND payment_status IN ('paid', 'partially_paid')");
            if ($pRes) {
                while ($p = $pRes->fetch_assoc()) {
                    if ($p['verification_status'] === 'verified') {
                        $amt = (float)$p['amount'];
                        $totalPaid += $amt;
                        $ptype = $p['payment_type'];

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
                    }
                    $penaltyPaid += (float)$p['penalty_paid'];
                    $penaltyWaived += (float)$p['penalty_waived'];
                }
            }

            $totalDue = $totalPayable - $totalPaid;

            $landShareTarget = $flat ? (float)$flat['land_share_value'] : 0;
            $devChargeTarget = $flat ? (float)$flat['development_charges'] : 0;
            $totalLandSharePaid = $bookingMoneyPaid + $registrationPaid + $mutationCostPaid + $additionalLandSharePaid;

            $totalPrice = $flat ? (float)$flat['price_value'] : 0;
            $constructionFee = max(0, $totalPrice - ($landShareTarget + $devChargeTarget));

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

            $responseData['bookings'][] = [
                "booking_id" => $bookingId,
                "agent_info" => ["agent_name" => $agentName],
                "project_info" => [
                    "project_id" => $project ? $project['id'] : null,
                    "project_name" => $project ? $project['name'] : 'N/A',
                    "flat_id" => $flat ? $flat['id'] : null,
                    "flat_name" => $flat ? $flat['flat_name'] : 'N/A',
                    "floor_no" => $flat ? $flat['floor_no'] : 'N/A',
                    "sqft" => $flat ? $flat['sqft'] : 0
                ],
                "payment_info" => [
                    "total_agreement_value" => $agreementValue,
                    "approved_discount_amount" => $discountAmount,
                    "total_payable" => $totalPayable,
                    "total_paid" => $totalPaid,
                    "total_due" => $totalDue,
                    "booking_and_registration" => [
                        "booking_money_paid" => $bookingMoneyPaid,
                        "registration_paid" => $registrationPaid,
                        "mutation_cost_paid" => $mutationCostPaid
                    ],
                    "land_share" => [
                        "total_land_share_target" => $landShareTarget,
                        "total_land_share_paid" => $totalLandSharePaid,
                        "is_land_share_completed" => ($landShareTarget > 0) ? ($totalLandSharePaid >= $landShareTarget) : false
                    ],
                    "development_charge" => [
                        "total_development_charge_target" => $devChargeTarget,
                        "development_charge_paid" => $devChargePaid,
                        "is_dev_charge_completed" => ($devChargeTarget > 0) ? ($devChargePaid >= $devChargeTarget) : false
                    ],
                    "emi_data" => [
                        "per_emi_amount" => $perEmiAmount,
                        "total_emi_paid" => $emiPaid,
                        "total_service_fee_paid" => $totalServiceFee,
                        "completed_emi_count" => $completedEmiCount,
                        "due_emi_amount" => $totalDueEmi,
                        "has_due_emi" => ($totalDueEmi > 0),
                        "bulk_emi_discount_received" => $bulkEmiDiscount,
                        "penalty_info" => [
                            "total_penalty_charged" => $penaltyCharged,
                            "total_penalty_paid" => $penaltyPaid,
                            "total_penalty_waived" => $penaltyWaived,
                            "has_unpaid_penalty" => ($penaltyCharged > ($penaltyPaid + $penaltyWaived))
                        ]
                    ]
                ]
            ];
        }

        echo json_encode([
            "success" => true,
            "message" => count($responseData['bookings']) > 0 ? "Dynamic payment data loaded." : "Static user data loaded.",
            "data" => [$responseData]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
