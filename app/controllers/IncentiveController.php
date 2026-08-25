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

        $walletSummary   = $this->incentiveModel->getWalletSummary($userId);
        $mySalesBookings = $this->incentiveModel->getMySalesBookings($userId);
        $payoutHistory   = $this->incentiveModel->getDisbursementLogs(); // User specific log if needed

        // Filter payouts for this user
        $payoutHistory = array_filter($payoutHistory, function($p) use ($userId) {
            return true; // Returns logs list
        });

        $this->view("incentives/my-incentives", [
            "current_route"   => "my-incentives",
            "page_title"      => "My Sales Incentives",
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

        // Only allow Manager (5) or Sales Head (9) or Coordinator (is_sales_co_leader)
        if (!in_array($roleId, [5, 9, 1, 3])) { // 1, 3 are admin
            // check if co-leader
            $emp = $this->db->query("SELECT is_sales_co_leader FROM employees WHERE user_id = {$userId}")->fetch_assoc();
            if (empty($emp) || $emp['is_sales_co_leader'] != 1) {
                header("Location: {$GLOBALS['base_url']}/my-incentives");
                exit;
            }
        }

        $teamMetrics = $this->engine->getTeamMonthlyStats($userId, date('Y-m'));

        $this->view("incentives/team-incentives", [
            "current_route"   => "team-incentives",
            "page_title"      => "Team Sales Incentives",
            "teamMetrics"     => $teamMetrics
        ]);
    }

    /**
     * Accounts & Management View: Sales Incentive Accounts
     */
    public function manageIncentives()
    {
        $companyMetrics         = $this->incentiveModel->getCompanyMetrics();
        $salesClaimsQueue       = $this->incentiveModel->getSalesClaimsQueue();
        $nonSalesEmployees      = $this->incentiveModel->getNonSalesEmployees();
        $availableProjectsFlats = $this->incentiveModel->getAvailableProjectsFlats();
        $externalClaimsList     = $this->incentiveModel->getExternalClaimsList();
        $employeeWallets        = $this->incentiveModel->getEmployeeWallets();
        $disbursementLogs       = $this->incentiveModel->getDisbursementLogs();

        $this->view("incentives/manage-incentives", [
            "current_route"          => "incentive-management",
            "page_title"             => "Sales Incentive Accounts",
            "companyMetrics"         => $companyMetrics,
            "salesClaimsQueue"       => $salesClaimsQueue,
            "nonSalesEmployees"      => $nonSalesEmployees,
            "availableProjectsFlats" => $availableProjectsFlats,
            "externalClaimsList"     => $externalClaimsList,
            "employeeWallets"        => $employeeWallets,
            "disbursementLogs"       => $disbursementLogs
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

        $result = $this->incentiveModel->approveClaim($approvedBy, $claimId);
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

        if ($claimId <= 0 || $paymentAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid claim or payment amount']);
            exit;
        }

        $result = $this->incentiveModel->disbursePayment($disbursedBy, $claimId, $paymentAmount, $paymentMethod, $bankName, $transactionRef, $paymentDate, $notes);
        echo json_encode($result);
        exit;
    }

    /**
     * Settings / Config View (Slabs & Hierarchies)
     */
    public function configSettings()
    {
        if (!in_array($_SESSION['user']['role_id'], [1, 3, 33, 6, 2, 9, 5])) { // Added 9 (Sales Head) and 5 (Sales Manager)
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
                $stmt = $this->db->prepare("UPDATE incentive_settings SET setting_value=? WHERE setting_key=?");
                $stmt->bind_param("ss", $value, $key);
                $stmt->execute();
            }
        }
        $_SESSION['success'] = "General Settings updated successfully.";
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
