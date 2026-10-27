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
        $month = $_GET['month'] ?? date('Y-m'); // Default current month (e.g. 2026-10)

        $walletSummary   = $this->incentiveModel->getWalletSummary($userId);
        $monthlyStats    = $this->engine->getExecutiveMonthlyStats($userId, $month);
        $mySalesBookings = $this->incentiveModel->getMySalesBookings($userId, $month);
        $payoutHistory   = $this->incentiveModel->getDisbursementLogs();

        // Calculate money collected by this user in this cycle
        $mStart = substr($monthlyStats['cycle_start'], 0, 10);
        $mEnd   = substr($monthlyStats['cycle_end'], 0, 10);
        $collStmt = $this->db->prepare("
            SELECT IFNULL(SUM(lp.amount), 0.00) as collected
            FROM lead_payments lp
            JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lb.sales_by_id = ?
              AND lb.booking_date BETWEEN ? AND ?
              AND lp.verification_status = 'verified'
              AND lp.payment_status IN ('paid', 'partially_paid')
              AND COALESCE(lp.is_trashed, 0) = 0
        ");
        $cycleCollected = 0.00;
        if ($collStmt) {
            $collStmt->bind_param("iss", $userId, $mStart, $mEnd);
            $collStmt->execute();
            $cycleCollected = (float)($collStmt->get_result()->fetch_assoc()['collected'] ?? 0.00);
            $collStmt->close();
        }

        $this->view("incentives/my-incentives", [
            "current_route"   => "my-incentives",
            "page_title"      => "My Sales Incentives",
            "selectedMonth"   => $month,
            "monthlyStats"    => $monthlyStats,
            "cycleCollected"  => $cycleCollected,
            "walletSummary"   => $walletSummary,
            "mySalesBookings" => $mySalesBookings,
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

        $month = $_GET['month'] ?? date('Y-m'); // Default current month (e.g. 2026-10)
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
        $allowedRoles = [1, 3, 6, 30, 32, 33, 35, 36];
        if (!in_array($roleId, $allowedRoles)) {
            header("Location: {$GLOBALS['base_url']}/my-incentives");
            exit;
        }

        $month = $_GET['month'] ?? date('Y-m');
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $companyMetrics         = $this->incentiveModel->getCompanyMetrics($month);
        $salesClaimsQueue       = $this->incentiveModel->getSalesClaimsQueue($month);
        $nonSalesEmployees      = $this->incentiveModel->getNonSalesEmployees();
        $availableProjectsFlats = $this->incentiveModel->getAvailableProjectsFlats();
        $externalClaimsList     = $this->incentiveModel->getExternalClaimsList();
        $employeeWallets        = $this->incentiveModel->getEmployeeWallets();
        $disbursementLogs       = $this->incentiveModel->getDisbursementLogs();

        // Calculate dynamic real-time team stats and individual stats to show on the dashboard for this month
        $realTimeStats = $this->incentiveModel->getRealTimeIncentivesByMonth($month, $fromDate, $toDate);

        $this->view("incentives/manage-incentives", [
            "current_route"          => "incentive-management",
            "page_title"             => "Sales Incentive Accounts",
            "selected_month"         => $month,
            "selected_from_date"     => $fromDate,
            "selected_to_date"       => $toDate,
            "companyMetrics"         => $companyMetrics,
            "salesClaimsQueue"       => $salesClaimsQueue,
            "realTimeStats"          => $realTimeStats,
            "nonSalesEmployees"      => $nonSalesEmployees,
            "availableProjectsFlats" => $availableProjectsFlats,
            "externalClaimsList"     => $externalClaimsList,
            "employeeWallets"        => $employeeWallets,
            "disbursementLogs"       => $disbursementLogs,
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
        $isOver1Lac = ($amount > 100000);
        $isOpHeadUser = (in_array($userRole, [20, 32], true) || str_contains($roleName, 'operation head'));
        $isMgmtUser = in_array($userRole, [1, 3], true);

        // Fetch approval request record
        $arCheck = $this->db->query("SELECT id, op_head_status FROM approval_requests WHERE source_table = 'project_incentive_claims' AND source_id = " . (int)$claimId . " ORDER BY id DESC LIMIT 1");
        $arRow = $arCheck ? $arCheck->fetch_assoc() : null;
        $opHeadStatus = strtolower($arRow['op_head_status'] ?? 'pending');

        if ($isOver1Lac) {
            if ($isOpHeadUser && !$isMgmtUser) {
                $arId = (int)($arRow['id'] ?? 0);
                $escNote = $this->db->real_escape_string('Verified and approved by Admin & Operation Head');
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/../models/ApprovalRequest.php';
                }
                $approvalModel = new \ApprovalRequest($this->db);
                if ($arId > 0) {
                    $approvalModel->approveOperationHead($arId, $approvedBy, $escNote);
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$approvedBy}, op_head_at = NOW(), op_head_note = '{$escNote}', approve_role = 'management' WHERE source_table = 'project_incentive_claims' AND source_id = " . (int)$claimId);
                }
                echo json_encode([
                    'success' => true,
                    'message' => 'Incentive claim verified & approved by Admin & Operation Head (Role 20). Forwarded to Management for sign-off.'
                ]);
                exit;
            }

            if ($isMgmtUser) {
                if ($opHeadStatus !== 'approved') {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Access Denied: Incentive claims exceeding ৳1,00,000 must be approved by Admin & Operation Head (Role 20) before Management can approve.'
                    ]);
                    exit;
                }
            }
        }

        $result = $this->incentiveModel->approveClaim($approvedBy, $claimId);
        if ($result && !empty($result['success'])) {
            $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', approve_role = 'accounts_head', accounts_head_status = 'pending' WHERE source_table = 'project_incentive_claims' AND source_id = " . (int)$claimId);
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

        $disbursedBy    = (int)$_SESSION['user']['id'];
        $claimId        = (int)($_POST['claim_id'] ?? 0);
        $paymentAmount  = (float)($_POST['payment_amount'] ?? 0);
        $paymentMethod  = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $bankName       = trim($_POST['bank_name'] ?? '');
        $transactionRef = trim($_POST['transaction_ref'] ?? '');
        $paymentDate    = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $notes          = trim($_POST['notes'] ?? '');
        $accountId      = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;

        if ($claimId <= 0 || $paymentAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid claim or payment amount']);
            exit;
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

        $result = $this->incentiveModel->disbursePayment($disbursedBy, $claimId, $paymentAmount, $paymentMethod, $bankName, $transactionRef, $paymentDate, $notes, $accountId);
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
}
