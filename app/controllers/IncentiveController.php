<?php

require_once __DIR__ . '/../models/IncentiveModel.php';

class IncentiveController extends Controller
{
    protected $db;
    protected $incentiveModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->incentiveModel = new IncentiveModel($this->db);

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
        if (!in_array($_SESSION['user']['role_id'], [1, 3, 33, 6, 2])) { // Adjust allowed roles as needed
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        $slabs = $this->db->query("SELECT s.*, l.name as location_name, c.name as category_name 
            FROM incentive_slabs s 
            LEFT JOIN locations l ON s.location_id = l.id 
            LEFT JOIN project_categories c ON s.category_id = c.id
            ORDER BY l.name ASC, s.min_volume ASC")->fetch_all(MYSQLI_ASSOC);

        $hierarchies = $this->db->query("SELECT * FROM hierarchy_commissions")->fetch_all(MYSQLI_ASSOC);
        
        $locations = $this->db->query("SELECT * FROM locations")->fetch_all(MYSQLI_ASSOC);
        $categories = $this->db->query("SELECT id, name AS category_name FROM project_categories")->fetch_all(MYSQLI_ASSOC);
        
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

        if ($id) {
            $stmt = $this->db->prepare("UPDATE incentive_slabs SET location_id=?, category_id=?, min_volume=?, max_volume=?, percentage=? WHERE id=?");
            $stmt->bind_param("iiddii", $location_id, $category_id, $min_volume, $max_volume, $percentage, $id);
        } else {
            $stmt = $this->db->prepare("INSERT INTO incentive_slabs (location_id, category_id, min_volume, max_volume, percentage) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddi", $location_id, $category_id, $min_volume, $max_volume, $percentage);
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
            $_SESSION['success'] = "Settings updated successfully.";
        }
        header("Location: {$GLOBALS['base_url']}/incentive-config");
        exit;
    }

    public function saveHierarchy()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        foreach ($_POST['amounts'] as $id => $amount) {
            $amt = (float)$amount;
            $stmt = $this->db->prepare("UPDATE hierarchy_commissions SET amount_per_50_lakh=? WHERE id=?");
            $stmt->bind_param("di", $amt, (int)$id);
            $stmt->execute();
        }
        $_SESSION['success'] = "Hierarchy rates updated successfully.";
        header("Location: {$GLOBALS['base_url']}/incentive-config");
        exit;
    }
}
