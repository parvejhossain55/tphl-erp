<?php
namespace Phase2;

use Controller;
use Con_FinancialTransaction;
use Con_VendorPaymentRequest;

class Con_FinancialTransactionController extends Controller
{
    protected $db;
    protected $txnModel;
    protected $vprModel;

    public function __construct($db)
    {
        $this->db       = $db;
        $this->txnModel = new Con_FinancialTransaction($this->db);
        $this->vprModel = new Con_VendorPaymentRequest($this->db);
    }

    /* =====================
       Ledger — All Transactions
    ====================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $transactions = $this->txnModel->getAll();

        $this->view('phase2/financial-transactions/transaction-lists', [
            'current_route' => 'con-transactions',
            'page_title'    => 'Financial Transactions Ledger',
            'transactions'  => $transactions,
        ]);
    }

    /* =====================
       Pending Payments List
       (Approved but not yet paid)
    ====================== */
    public function pendingList()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $pending = $this->txnModel->getPendingApprovals();

        $this->view('phase2/financial-transactions/pending-payments', [
            'current_route' => 'con-pending-payments',
            'page_title'    => 'Pending Payments',
            'pending'       => $pending,
        ]);
    }

    /* =====================
       Process Payment Form
       Handles: contractor_bill | operational_fund | vendor_payment
    ====================== */
    public function process($refType, $refId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $source      = null;
        $advancePaid = 0.00;
        $penalty     = 0.00;
        $netPayable  = 0.00;

        if ($refType === 'contractor_bill') {
          
            $stmt = $this->db->prepare(
                "SELECT cbr.*, p.name AS project_name, oo.name AS operator_name
                 FROM contractor_bill_requests cbr
                 LEFT JOIN projects p ON p.id = cbr.project_id
                 LEFT JOIN operations_operators oo ON oo.id = cbr.operator_id
                 WHERE cbr.id = ?"
            );
            $stmt->bind_param('i', $refId);
            $stmt->execute();
            $source = $stmt->get_result()->fetch_assoc();
            $netPayable = $source['bill_amount'] ?? 0;

        } elseif ($refType === 'operational_fund') {
            $stmt = $this->db->prepare(
                "SELECT ofr.*, p.name AS project_name
                 FROM operational_fund_requests ofr
                 LEFT JOIN projects p ON p.id = ofr.project_id
                 WHERE ofr.id = ?"
            );
            $stmt->bind_param('i', $refId);
            $stmt->execute();
            $source = $stmt->get_result()->fetch_assoc();
            $netPayable = $source['approved_amount'] ?? $source['amount'] ?? 0;

        } elseif ($refType === 'vendor_payment') {
            $source = $this->vprModel->find($refId);

            if ($source) {
                $netPayable = (float)($source['net_payable'] ?? 0);
                $penalty    = (float)($source['penalty_deducted'] ?? 0);

                // For Final/Partial: check how much Advance was already paid for same PO
                if (!empty($source['purchase_order_id']) && $source['payment_type'] !== 'Advance') {
                    $advancePaid = $this->vprModel->getTotalAdvancePaid($source['purchase_order_id']);
                    // Net = net_payable - advance already disbursed
                    $netPayable = max(0, $netPayable - $advancePaid);
                }
            }
        }

        if (!$source) {
            setError('Source record not found.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
            exit;
        }

        $this->view('phase2/financial-transactions/process-payment', [
            'current_route' => 'con-pending-payments',
            'page_title'    => 'Process Payment',
            'source'        => $source,
            'ref_type'      => $refType,
            'ref_id'        => $refId,
            'advance_paid'  => $advancePaid,
            'penalty'       => $penalty,
            'net_payable'   => $netPayable,
        ]);
    }

    /* =====================
       Store Transaction
    ====================== */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $refType     = $_POST['ref_type'] ?? '';
        $paymentType = $_POST['payment_type'] ?? 'Full';

        // For vendor_payment, map VPR payment_type to transaction payment_type
        if ($refType === 'vendor_payment') {
            // Allowed: Advance, Partial, Full — map Final → Full for the ledger
            $paymentType = match($_POST['vpr_payment_type'] ?? 'Final') {
                'Advance' => 'Advance',
                'Partial' => 'Partial',
                default   => 'Full',
            };
        }

        $data = [
            'ref_type'       => $refType,
            'ref_id'         => (int)($_POST['ref_id'] ?? 0),
            'project_id'     => (int)($_POST['project_id'] ?? 0),
            'amount'         => (float)($_POST['amount'] ?? 0),
            'payment_type'   => $paymentType,
            'payment_method' => $_POST['payment_method'] ?? 'Cash',
            'payment_date'   => $_POST['payment_date'] ?? date('Y-m-d'),
            'reference_no'   => trim($_POST['reference_no'] ?? ''),
            'notes'          => trim($_POST['notes'] ?? ''),
            'processed_by'   => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['ref_type']) || empty($data['ref_id']) || $data['amount'] <= 0) {
            setError('Invalid payment data. Please fill all required fields.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
            exit;
        }

        $txnId = $this->txnModel->create($data);

        if ($txnId) {
            setSuccess('Payment processed successfully! Transaction #' . $txnId);
            header("Location: {$GLOBALS['base_url']}/con-transaction/{$txnId}");
        } else {
            setError('Failed to process payment.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
        }
        exit;
    }

    /* =====================
       Show Transaction Detail
    ====================== */
    public function show($id)
    {
        $txn = $this->txnModel->find($id);

        if (!$txn) {
            setError('Transaction not found.');
            header("Location: {$GLOBALS['base_url']}/con-transactions");
            exit;
        }

        $this->view('phase2/financial-transactions/show-transaction', [
            'current_route' => 'con-transactions',
            'page_title'    => 'Transaction Receipt',
            'txn'           => $txn,
        ]);
    }
    
}
