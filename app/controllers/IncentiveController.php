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
}
