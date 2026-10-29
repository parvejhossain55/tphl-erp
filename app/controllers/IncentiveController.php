<?php

require_once __DIR__ . '/../models/IncentiveModel.php';

class IncentiveController extends Controller
{
    protected $db;
    protected $incentiveModel;
    protected $engine;

    public function __construct($db)
    {
        $this->db = $db;
        $this->incentiveModel = new IncentiveModel($this->db);
        require_once __DIR__ . '/../services/IncentiveCalculationEngine.php';
        $this->engine = new IncentiveCalculationEngine($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Sales Executive View: My Incentives
     */
    public function myIncentives()
    {
        $userId = (int)$_SESSION['user']['id'];
        $activeCycle = $this->incentiveModel->getCycleForDate(date('Y-m-d'));
        $month = $_GET['month'] ?? $activeCycle;

        $walletSummary   = $this->incentiveModel->getWalletSummary($userId);
        $monthlyStats    = $this->engine->getExecutiveMonthlyStats($userId, $month);
        $mySalesBookings = $this->incentiveModel->getMySalesBookings($userId, $month);
        $payoutHistory   = $this->incentiveModel->getDisbursementLogs($userId);

        // Fetch all assigned targets for this user (history & current)
        require_once __DIR__ . '/../models/SalesTarget.php';
        $salesTargetModel = new \SalesTarget($this->db);
        $myTargets = $salesTargetModel->getAllTargets($userId);

        // Calculate money collected by this user in this cycle
        $mStart = substr($monthlyStats['cycle_start'], 0, 10);
        $mEnd   = substr($monthlyStats['cycle_end'], 0, 10);
        $collStmt = $this->db->prepare("
            SELECT IFNULL(SUM(lp.amount), 0.00) as collected
            FROM lead_payments lp
            JOIN lead_bookings lb ON lp.booking_id = lb.id
            LEFT JOIN leads l ON lb.lead_id = l.id
            WHERE (lb.sales_by_id = ? OR lb.created_by = ? OR l.assigned_id = ?)
              AND lb.booking_date BETWEEN ? AND ?
              AND lp.verification_status = 'verified'
              AND lp.payment_status IN ('paid', 'partially_paid')
              AND COALESCE(lp.is_trashed, 0) = 0
        ");
        $cycleCollected = 0.00;
        if ($collStmt) {
            $collStmt->bind_param("iiiss", $userId, $userId, $userId, $mStart, $mEnd);
            $collStmt->execute();
            $cycleCollected = (float)($collStmt->get_result()->fetch_assoc()['collected'] ?? 0.00);
            $collStmt->close();
        }

        // Team Leader Metrics & Override (0.05%) check
        $isTeamLeader = false;
        $teamMetrics = null;
        $tlEmpStmt = $this->db->prepare("
            SELECT e.is_team_leader, e.team_id, t.name as team_name 
            FROM employees e 
            LEFT JOIN teams t ON e.team_id = t.id 
            WHERE e.user_id = ?
        ");
        if ($tlEmpStmt) {
            $tlEmpStmt->bind_param("i", $userId);
            $tlEmpStmt->execute();
            $empRow = $tlEmpStmt->get_result()->fetch_assoc();
            $tlEmpStmt->close();
            if (!empty($empRow['is_team_leader']) || !empty($empRow['team_id'])) {
                $teamMetrics = $this->engine->getTeamMonthlyStats($userId, $month);
                if (!empty($teamMetrics['eligible']) || !empty($empRow['is_team_leader'])) {
                    $isTeamLeader = true;
                }
            }
        }

        // Enrich TL override claim statuses
        if (!empty($teamMetrics['member_breakdown'])) {
            $cStart = $monthlyStats['cycle_start'];
            $cEnd   = $monthlyStats['cycle_end'];
            foreach ($teamMetrics['member_breakdown'] as &$mb) {
                $memUid = (int)$mb['user_id'];
                $chk = $this->db->query("
                    SELECT id, claim_status, incentive_amount, claimed_at, is_received, received_at 
                    FROM project_incentive_claims 
                    WHERE user_id = {$userId} 
                      AND original_sales_by = {$memUid} 
                      AND split_type = 'tl_override' 
                      AND (cycle_month = '{$month}' OR (created_at BETWEEN '{$cStart}' AND '{$cEnd}'))
                      AND claim_status != 'rejected'
                    LIMIT 1
                ");
                if ($chk && $cRow = $chk->fetch_assoc()) {
                    $mb['claim_id'] = (int)$cRow['id'];
                    $mb['claim_status'] = $cRow['claim_status'];
                    $mb['is_received'] = (int)($cRow['is_received'] ?? 0);
                    $mb['received_at'] = $cRow['received_at'] ?? null;
                } else {
                    $mb['claim_id'] = null;
                    $mb['claim_status'] = null;
                    $mb['is_received'] = 0;
                    $mb['received_at'] = null;
                }
            }
            unset($mb);
        }

        // Enrich 30% designated credits claim statuses
        if (!empty($monthlyStats['designated_credits'])) {
            foreach ($monthlyStats['designated_credits'] as &$dc) {
                $bId = (int)$dc['booking_id'];
                $chkDes = $this->db->query("
                    SELECT id, claim_status, incentive_amount, is_received, received_at 
                    FROM project_incentive_claims 
                    WHERE booking_id = {$bId} AND user_id = {$userId} AND split_type = 'designated_30' AND claim_status != 'rejected'
                    LIMIT 1
                ");
                if ($chkDes && $dRow = $chkDes->fetch_assoc()) {
                    $dc['claim_id'] = (int)$dRow['id'];
                    $dc['claim_status'] = $dRow['claim_status'];
                    $dc['is_received'] = (int)($dRow['is_received'] ?? 0);
                    $dc['received_at'] = $dRow['received_at'] ?? null;
                } else {
                    $dc['claim_id'] = null;
                    $dc['claim_status'] = null;
                    $dc['is_received'] = 0;
                    $dc['received_at'] = null;
                }
            }
            unset($dc);
        }

        // Enrich outside 70% bookings claim statuses
        if (!empty($monthlyStats['detailed_bookings'])) {
            foreach ($monthlyStats['detailed_bookings'] as &$dbk) {
                $bId = (int)$dbk['booking_id'];
                $chkOut = $this->db->query("
                    SELECT id, claim_status, incentive_amount, is_received, received_at 
                    FROM project_incentive_claims 
                    WHERE booking_id = {$bId} AND user_id = {$userId} AND claim_status != 'rejected'
                    LIMIT 1
                ");
                if ($chkOut && $oRow = $chkOut->fetch_assoc()) {
                    $dbk['claim_id'] = (int)$oRow['id'];
                    $dbk['claim_status'] = $oRow['claim_status'];
                    $dbk['is_received'] = (int)($oRow['is_received'] ?? 0);
                    $dbk['received_at'] = $oRow['received_at'] ?? null;
                } else {
                    $dbk['claim_id'] = null;
                    $dbk['claim_status'] = null;
                    $dbk['is_received'] = 0;
                    $dbk['received_at'] = null;
                }
            }
            unset($dbk);
        }

        $this->view("incentives/my-incentives", [
            "current_route"   => "my-incentives",
            "page_title"      => "My Sales Incentives",
            "selectedMonth"   => $month,
            "monthlyStats"    => $monthlyStats,
            "cycleCollected"  => $cycleCollected,
            "walletSummary"   => $walletSummary,
            "mySalesBookings" => $mySalesBookings,
            "myTargets"       => $myTargets,
            "isTeamLeader"    => $isTeamLeader,
            "teamMetrics"     => $teamMetrics,
            "payoutHistory"   => array_values($payoutHistory)
        ]);
    }

    /**
     * Team Leader / Manager / Head View: Team Incentives
     */
    public function teamIncentives()
    {
        $userId = (int)$_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        // Allow Manager (5), Sales Head (9), Admin (1, 3), Team Leader (is_team_leader = 1), or Coordinator (is_sales_co_leader = 1)
        if (!in_array($roleId, [5, 9, 1, 3])) {
            $emp = $this->db->query("SELECT is_team_leader, is_sales_co_leader FROM employees WHERE user_id = {$userId}")->fetch_assoc();
            if (empty($emp) || ($emp['is_team_leader'] != 1 && $emp['is_sales_co_leader'] != 1)) {
                header("Location: {$GLOBALS['base_url']}/my-incentives");
                exit;
            }
        }

        $activeCycle = $this->incentiveModel->getCycleForDate(date('Y-m-d'));
        $month = $_GET['month'] ?? $activeCycle;
        $filterTeamId = isset($_GET['team_id']) && $_GET['team_id'] !== '' && $_GET['team_id'] !== 'all' ? (int)$_GET['team_id'] : null;

        $teamMetrics = $this->engine->getTeamMonthlyStats($userId, $month, $filterTeamId);
        $teamSalesBreakdown = $this->incentiveModel->getTeamSalesBreakdown($userId, $month, $filterTeamId);

        $teamsRes = $this->db->query("SELECT id, name FROM teams ORDER BY name ASC");
        $teams = $teamsRes ? $teamsRes->fetch_all(MYSQLI_ASSOC) : [];

        $this->view("incentives/team-incentives", [
            "current_route"      => "my-team-incentives",
            "page_title"         => "Team Sales Incentives",
            "teamMetrics"        => $teamMetrics,
            "teamSalesBreakdown" => $teamSalesBreakdown,
            "teams"              => $teams,
            "selectedTeamId"     => $filterTeamId,
            "selectedMonth"      => $month,
            "isSalesHead"        => ($roleId == 9 || in_array($roleId, [1, 3]))
        ]);
    }

    /**
     * Accounts & Management View: Sales Incentive Accounts
     */
    public function manageIncentives()
    {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $allowedRoles = [1, 3, 6, 20, 30, 32, 33, 34, 35, 36];
        if (!in_array($roleId, $allowedRoles)) {
            header("Location: {$GLOBALS['base_url']}/my-incentives");
            exit;
        }

        $activeCycle = $this->incentiveModel->getCycleForDate(date('Y-m-d'));
        $month = $_GET['month'] ?? $activeCycle;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $companyMetrics         = $this->incentiveModel->getCompanyMetrics($month);
        $salesClaimsQueue       = $this->incentiveModel->getSalesClaimsQueue($month);
        $nonSalesEmployees      = $this->incentiveModel->getNonSalesEmployees();
        $availableProjectsFlats = $this->incentiveModel->getAvailableProjectsFlats();
        $externalClaimsList     = $this->incentiveModel->getExternalClaimsList();
        $employeeWallets        = $this->incentiveModel->getEmployeeWallets();
        $disbursementLogs       = $this->incentiveModel->getDisbursementLogs();
        $companyBanksRes        = $this->db->query("SELECT id, bank_name, account_name, account_number, is_default FROM company_bank_accounts WHERE status = 'active' ORDER BY is_default DESC, bank_name ASC");
        $companyBanks           = $companyBanksRes ? $companyBanksRes->fetch_all(MYSQLI_ASSOC) : [];

        // Calculate dynamic real-time team stats and individual stats to show on the dashboard for this month
        $realTimeStats = $this->incentiveModel->getRealTimeIncentivesByMonth($month, $fromDate, $toDate);

        // 1. Team Leader Overrides (0.05%) across all teams
        $allTeamOverrides = [];
        foreach ($realTimeStats as $st) {
            if (!empty($st['team']) && !empty($st['team']['member_breakdown'])) {
                $tlUser = $st['user'];
                $teamData = $st['team'];
                foreach ($teamData['member_breakdown'] as $mb) {
                    if (empty($mb['is_self']) && $mb['achieved_land_share'] > 0) {
                        $allTeamOverrides[] = [
                            'team_name'          => $teamData['team_name'] ?? ($tlUser['team_name'] ?? 'Team'),
                            'tl_name'            => $tlUser['full_name'],
                            'tl_code'            => $tlUser['employee_id'],
                            'tl_user_id'         => $tlUser['id'],
                            'member_user_id'     => $mb['user_id'],
                            'member_name'        => $mb['full_name'],
                            'member_code'        => $mb['employee_id'],
                            'member_designation' => $mb['designation'],
                            'member_target'      => $mb['target'],
                            'member_achieved'    => $mb['achieved_land_share'],
                            'is_qualified'       => $mb['is_qualified'],
                            'tl_rate'            => $teamData['tl_rate'] ?? 0.05,
                            'tl_gain'            => $mb['tl_contribution']
                        ];
                    }
                }
            }
        }

        // Enrich TL Overrides with claim status for Management / Accounts
        list($cYear, $cMonth) = explode('-', $month);
        $activeCycle = $this->incentiveModel->getCycleDateRange($cYear, $cMonth);
        foreach ($allTeamOverrides as &$to) {
            $tlUid = (int)$to['tl_user_id'];
            $memUid = (int)$to['member_user_id'];
            $chk = $this->db->query("
                SELECT c.id, c.claim_status, c.incentive_amount, c.created_at,
                       ar.id as approval_request_id, ar.approve_role, ar.accounts_head_status
                FROM project_incentive_claims c
                LEFT JOIN approval_requests ar ON ar.source_table = 'project_incentive_claims' AND ar.source_id = c.id
                WHERE c.user_id = {$tlUid} 
                  AND c.original_sales_by = {$memUid} 
                  AND c.split_type = 'tl_override'
                  AND (c.cycle_month = '{$month}' OR (c.created_at BETWEEN '{$activeCycle['start']}' AND '{$activeCycle['end']}'))
                  AND c.claim_status != 'rejected'
                LIMIT 1
            ");
            if ($chk && $cRow = $chk->fetch_assoc()) {
                $to['claim_id'] = (int)$cRow['id'];
                $to['claim_status'] = $cRow['claim_status'];
                $to['approval_request_id'] = $cRow['approval_request_id'];
                $to['approve_role'] = $cRow['approve_role'];
                $to['accounts_head_status'] = $cRow['accounts_head_status'];
            } else {
                $to['claim_id'] = null;
                $to['claim_status'] = null;
                $to['approval_request_id'] = null;
                $to['approve_role'] = null;
                $to['accounts_head_status'] = null;
            }
        }
        unset($to);

        // 2. Cross-Project (70/30) Splits
        $allCrossProjectSplits = [];
        $seenBookingIds = [];
        foreach ($realTimeStats as $st) {
            $seller = $st['user'];
            $ind = $st['individual'] ?? [];
            if (!empty($ind['detailed_bookings'])) {
                foreach ($ind['detailed_bookings'] as $b) {
                    if (empty($b['is_assigned']) || (isset($b['rate_type']) && strpos($b['rate_type'], 'Outside') !== false)) {
                        $bId = (int)$b['booking_id'];
                        if (!isset($seenBookingIds[$bId])) {
                            $seenBookingIds[$bId] = true;
                            $allCrossProjectSplits[] = [
                                'booking_id'           => $bId,
                                'booking_date'         => $b['booking_date'] ?? '',
                                'project_name'         => $b['project_name'] ?? '',
                                'flat_name'            => $b['flat_name'] ?? '',
                                'land_share_value'     => (float)($b['land_share_value'] ?? 0),
                                'seller_id'            => $seller['id'],
                                'seller_name'          => $seller['full_name'],
                                'seller_code'          => $seller['employee_id'],
                                'seller_share'         => (float)($b['seller_incentive'] ?? 0),
                                'is_on_hold'           => !empty($b['is_on_hold']),
                                'designated_user_id'   => $b['designated_user_id'] ?? null,
                                'designated_user_name' => $b['designated_user_name'] ?? 'Unassigned / Open',
                                'designated_share'     => (float)($b['designated_incentive'] ?? 0)
                            ];
                        }
                    }
                }
            }
        }

        $this->view("incentives/manage-incentives", [
            "current_route"          => "incentive-management",
            "page_title"             => "Sales Incentive Accounts",
            "selected_month"         => $month,
            "selected_from_date"     => $fromDate,
            "selected_to_date"       => $toDate,
            "companyMetrics"         => $companyMetrics,
            "salesClaimsQueue"       => $salesClaimsQueue,
            "realTimeStats"          => $realTimeStats,
            "allTeamOverrides"       => $allTeamOverrides,
            "allCrossProjectSplits"  => $allCrossProjectSplits,
            "nonSalesEmployees"      => $nonSalesEmployees,
            "availableProjectsFlats" => $availableProjectsFlats,
            "externalClaimsList"     => $externalClaimsList,
            "employeeWallets"        => $employeeWallets,
            "disbursementLogs"       => $disbursementLogs,
            "companyBanks"           => $companyBanks,
            "coaList"                => getCOAExpenseList($this->db)
        ]);
    }

    /**
     * AJAX Endpoint: Claim Incentive (Sales Executive)
     */
    public function claimIncentive()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        if ($bookingId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
            exit;
        }

        // Prevent duplicate claims if already claimed or settled
        $chkExist = $this->db->query("SELECT id, claim_status FROM project_incentive_claims WHERE booking_id = {$bookingId} AND is_external = 0 LIMIT 1");
        if ($chkExist && $existRow = $chkExist->fetch_assoc()) {
            if (in_array($existRow['claim_status'], ['claimed', 'approved', 'partially_paid', 'paid'])) {
                echo json_encode(['success' => false, 'message' => 'This booking incentive has already been submitted (Status: ' . ucfirst($existRow['claim_status']) . '). It cannot be claimed again.']);
                exit;
            }
        }

        $result = $this->incentiveModel->claimIncentive($userId, $bookingId, $remarks);
        echo json_encode($result);
        exit;
    }

    /**
     * AJAX Endpoint: Assign External Incentive (Management)
     */
    public function assignExternalIncentive()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $assignedBy   = (int)$_SESSION['user']['id'];
        $userId       = (int)($_POST['user_id'] ?? 0);
        $flatId       = (int)($_POST['flat_id'] ?? 0);
        $incentivePct = (float)($_POST['incentive_percentage'] ?? 0);
        $customAmount = (float)($_POST['custom_amount'] ?? 0);
        $remarks      = trim($_POST['remarks'] ?? '');

        if ($userId <= 0 || $flatId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select employee and project flat.']);
            exit;
        }

        if (empty($remarks)) {
            echo json_encode(['success' => false, 'message' => 'Assignment remarks / reason is required.']);
            exit;
        }

        $result = $this->incentiveModel->assignExternalIncentive($assignedBy, $userId, $flatId, $incentivePct, $customAmount, $remarks);
        echo json_encode($result);
        exit;
    }

    /**
     * AJAX Endpoint: Approve Claim
     */
    public function approveClaim()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $approvedBy = (int)$_SESSION['user']['id'];
        $claimId    = (int)($_POST['claim_id'] ?? 0);

        if ($claimId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid claim ID']);
            exit;
        }

        $chkClaim = $this->db->query("SELECT id, incentive_amount FROM project_incentive_claims WHERE id = " . (int)$claimId . " LIMIT 1");
        $claimRow = $chkClaim ? $chkClaim->fetch_assoc() : null;
        if (!$claimRow) {
            echo json_encode(['success' => false, 'message' => 'Claim not found']);
            exit;
        }

        $amount = (float)($claimRow['incentive_amount'] ?? 0);
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isOpHeadUser = (in_array($userRole, [20, 32], true) || str_contains($roleName, 'operation head'));
        $isMgmtUser = in_array($userRole, [1, 3], true);

        if (!$isOpHeadUser && !$isMgmtUser) {
            echo json_encode([
                'success' => false,
                'message' => 'Access Denied: Only Admin & Operation Head (Role 20) or Management (Role 3) can approve incentive claims.'
            ]);
            exit;
        }

        $result = $this->incentiveModel->approveClaim($approvedBy, $claimId);
        if ($result && !empty($result['success'])) {
            $approverTitle = $isOpHeadUser ? 'Admin & Operation Head (Role 20)' : 'Management (Role ' . $userRole . ')';
            $escNote = $this->db->real_escape_string('Approved by ' . $approverTitle);
            $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$approvedBy}, op_head_at = NOW(), op_head_note = '{$escNote}', approve_role = 'accounts_head', accounts_head_status = 'pending' WHERE source_table = 'project_incentive_claims' AND source_id = " . (int)$claimId);
        }
        echo json_encode($result);
        exit;
    }

    /**
     * AJAX Endpoint: Reject Claim
     */
    public function rejectClaim()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $rejectedBy = (int)$_SESSION['user']['id'];
        $claimId    = (int)($_POST['claim_id'] ?? 0);

        if ($claimId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid claim ID']);
            exit;
        }

        $result = $this->incentiveModel->rejectClaim($rejectedBy, $claimId);
        echo json_encode($result);
        exit;
    }

    /**
     * AJAX Endpoint: Disburse Payment (Accounts)
     */
    public function disbursePayment()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $disbursedBy          = (int)$_SESSION['user']['id'];
        $claimId              = (int)($_POST['claim_id'] ?? 0);
        $paymentAmount        = (float)($_POST['payment_amount'] ?? 0);
        $paymentMethod        = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $companyBankAccountId = !empty($_POST['company_bank_id']) ? (int)$_POST['company_bank_id'] : null;
        $toBankName           = trim($_POST['to_bank_name'] ?? '');
        $bankName             = trim($_POST['bank_name'] ?? '');
        $transactionRef       = trim($_POST['transaction_ref'] ?? '');
        $paymentDate          = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $notes                = trim($_POST['notes'] ?? '');
        $accountId            = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;

        if ($claimId <= 0 || $paymentAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid claim or payment amount']);
            exit;
        }

        // Auto-resolve company bank and to-bank names
        if ($paymentMethod === 'Cash') {
            $companyBankAccountId = null;
            $toBankName = null;
            $bankName = 'Cash';
        } else {
            $compBankTitle = '';
            if ($companyBankAccountId > 0) {
                $cbq = $this->db->query("SELECT bank_name, account_name, account_number FROM company_bank_accounts WHERE id = {$companyBankAccountId} LIMIT 1");
                if ($cbq && $cbr = $cbq->fetch_assoc()) {
                    $compBankTitle = $cbr['bank_name'] . (!empty($cbr['account_number']) ? ' (' . $cbr['account_number'] . ')' : '');
                }
            }
            if (!empty($compBankTitle)) {
                $bankName = $compBankTitle;
            } elseif (empty($bankName)) {
                $bankName = $toBankName;
            }
        }

        if (!class_exists('ApprovalRequest')) {
            require_once __DIR__ . '/../models/ApprovalRequest.php';
        }
        $ahCheck = \ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'project_incentive_claims', $claimId);
        if (!empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Payment locked. Mandatory approval by Accounts Head (Md. Mamunoor Rashid [1309]) is required before disbursement.'
            ]);
            exit;
        }

        $result = $this->incentiveModel->disbursePayment($disbursedBy, $claimId, $paymentAmount, $paymentMethod, $bankName, $transactionRef, $paymentDate, $notes, $accountId, $companyBankAccountId, $toBankName);
        echo json_encode($result);
        exit;
    }

    /**
     * Settings / Config View (Slabs & Hierarchies)
     */
    public function configSettings()
    {
        if (!in_array($_SESSION['user']['role_id'], [1, 3, 33, 6, 30, 2, 9, 5])) { // Added 9 (Sales Head) and 5 (Sales Manager)
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        $slabs = $this->db->query("SELECT s.*, l.name as location_name, c.name as category_name, c.main_category 
            FROM incentive_slabs s 
            LEFT JOIN locations l ON s.location_id = l.id 
            LEFT JOIN project_categories c ON s.category_id = c.id
            ORDER BY l.name ASC, s.min_volume ASC")->fetch_all(MYSQLI_ASSOC);

        $hierarchies = $this->db->query("SELECT * FROM hierarchy_commissions")->fetch_all(MYSQLI_ASSOC);
        
        $locations = $this->db->query("SELECT * FROM locations")->fetch_all(MYSQLI_ASSOC);
        $categories = $this->db->query("SELECT id, name AS category_name, main_category FROM project_categories")->fetch_all(MYSQLI_ASSOC);
        
        $eligibility_rules = $this->db->query("SELECT * FROM incentive_eligibility_rules ORDER BY rule_type ASC, min_achievement_pct ASC")->fetch_all(MYSQLI_ASSOC);
        
        $settingsRes = $this->db->query("SELECT * FROM incentive_settings")->fetch_all(MYSQLI_ASSOC);
        $settings = [];
        foreach($settingsRes as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $this->view("incentives/config-settings", [
            "current_route" => "incentive-config",
            "page_title" => "Incentive Configurations",
            "slabs" => $slabs,
            "hierarchies" => $hierarchies,
            "locations" => $locations,
            "categories" => $categories,
            "eligibility_rules" => $eligibility_rules,
            "settings" => $settings
        ]);
    }

    public function saveSlab()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $location_id = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $min_volume = (float)$_POST['min_volume'];
        $max_volume = !empty($_POST['max_volume']) ? (float)$_POST['max_volume'] : null;
        $percentage = (float)$_POST['percentage'];
        $lowest_incentive_cap = (float)($_POST['lowest_incentive_cap'] ?? 0);
        $highest_incentive_cap = (float)($_POST['highest_incentive_cap'] ?? 0);

        if ($id) {
            $stmt = $this->db->prepare("UPDATE incentive_slabs SET location_id=?, category_id=?, min_volume=?, max_volume=?, percentage=?, lowest_incentive_cap=?, highest_incentive_cap=? WHERE id=?");
            $stmt->bind_param("iidddddi", $location_id, $category_id, $min_volume, $max_volume, $percentage, $lowest_incentive_cap, $highest_incentive_cap, $id);
        } else {
            $stmt = $this->db->prepare("INSERT INTO incentive_slabs (location_id, category_id, min_volume, max_volume, percentage, lowest_incentive_cap, highest_incentive_cap) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddddd", $location_id, $category_id, $min_volume, $max_volume, $percentage, $lowest_incentive_cap, $highest_incentive_cap);
        }
        $stmt->execute();
        $_SESSION['success'] = "Slab saved successfully.";
        header("Location: {$GLOBALS['base_url']}/incentive-config");
        exit;
    }

    public function saveSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $value) {
                $stmt = $this->db->prepare("INSERT INTO incentive_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $key, $value, $value);
                $stmt->execute();
            }
        }
        $_SESSION['success'] = "Sales Incentive Modality Settings updated successfully.";
        header("Location: {$GLOBALS['base_url']}/incentive-config");
        exit;
    }

    public function saveEligibilityRules()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        if (!empty($_POST['rules']) && is_array($_POST['rules'])) {
            foreach ($_POST['rules'] as $id => $ruleData) {
                $min = (float)$ruleData['min_achievement_pct'];
                $max = ($ruleData['max_achievement_pct'] !== '') ? (float)$ruleData['max_achievement_pct'] : null;
                $payout = (float)$ruleData['payout_pct'];
                
                $stmt = $this->db->prepare("UPDATE incentive_eligibility_rules SET min_achievement_pct=?, max_achievement_pct=?, payout_pct=? WHERE id=?");
                $stmt->bind_param("dddi", $min, $max, $payout, $id);
                $stmt->execute();
            }
        }
        
        $_SESSION['success'] = "Eligibility Criteria updated successfully.";
        header("Location: {$GLOBALS['base_url']}/incentive-config");
        exit;
    }

    public function saveHierarchy()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        foreach ($_POST['amounts'] as $id => $amount) {
            $amt = (float)$amount;
            $threshold = (float)($_POST['thresholds'][$id] ?? 5000000);
            $stmt = $this->db->prepare("UPDATE hierarchy_commissions SET reward_amount=?, collection_threshold_amount=? WHERE id=?");
            $stmt->bind_param("ddi", $amt, $threshold, (int)$id);
            $stmt->execute();
        }
        $_SESSION['success'] = "Hierarchy rates updated successfully.";
        header("Location: {$GLOBALS['base_url']}/incentive-config");
        exit;
    }


    /**
     * AJAX Endpoint: Claim Team Leader 0.05% Override
     */
    public function claimTlOverride()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $memberUserId = (int)($_POST['member_user_id'] ?? 0);
        $month = trim($_POST['month'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($memberUserId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid team member ID']);
            exit;
        }

        $result = $this->incentiveModel->claimTlOverride($userId, $memberUserId, $month, $remarks);
        echo json_encode($result);
        exit;
    }

    /**
     * AJAX Endpoint: Claim 30% Designated Share
     */
    public function claimDesignatedIncentive()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        if ($bookingId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
            exit;
        }

        $result = $this->incentiveModel->claimDesignatedIncentive($userId, $bookingId, $remarks);
        echo json_encode($result);
        exit;
    }

    /**
     * AJAX Endpoint: Confirm Receipt of Incentive Payout by Sales Person
     */
    public function confirmReceipt()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized session. Please login.']);
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $claimId = (int)($_POST['claim_id'] ?? 0);
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        if ($claimId <= 0 && $paymentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing claim or payout reference.']);
            exit;
        }

        $result = $this->incentiveModel->confirmReceipt($userId, $claimId, $paymentId, $note);
        echo json_encode($result);
        exit;
    }
}
