<?php
require_once __DIR__ . '/../core/Controller.php';

class VoucherController extends Controller
{
    protected $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function printDebitVoucher()
    {
        checkLogin();

        $module = $_GET['module'] ?? '';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (empty($module) || $id === 0) {
            die("Invalid Request");
        }

        $voucherData = [
            'voucher_no' => strtoupper($module) . '-' . str_pad($id, 4, '0', STR_PAD_LEFT),
            'date' => date('Y-m-d'),
            'paid_to' => '',
            'project' => '',
            'purpose' => '',
            'amount' => 0,
            'amount_words' => '',
            'payment_method' => '',
            'transactions' => []
        ];

        switch ($module) {
            case 'cashout':
                // Main Request
                $stmt = $this->db->prepare("SELECT cr.*, p.name as project_name FROM cashout_requests cr LEFT JOIN projects p ON cr.project_id = p.id WHERE cr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['paid_to'] = $req['purpose_paid_to'];
                $voucherData['project'] = $req['project_name'] ?? 'N/A';
                $voucherData['purpose'] = 'Cashout Request';
                
                // Transactions
                $tStmt = $this->db->prepare("SELECT amount as amount_paid, payment_method, payment_date as transaction_date, bank_name, bank_account_no, cheque_no, online_type, transaction_ref as reference_number FROM cashout_payments WHERE cashout_request_id = ? ORDER BY created_at ASC");
                $tStmt->bind_param("i", $id);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                while ($row = $tRes->fetch_assoc()) {
                    $voucherData['transactions'][] = $row;
                    $voucherData['amount'] += (float)$row['amount_paid'];
                    $voucherData['payment_method'] = $row['payment_method']; // Last one
                }
                $tStmt->close();
                break;

            case 'fund_requisition':
                $stmt = $this->db->prepare("SELECT fr.*, p.name as project_name, u.full_name FROM fund_requisitions fr LEFT JOIN projects p ON fr.project_id = p.id LEFT JOIN users u ON u.id = fr.requested_by WHERE fr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['paid_to'] = $req['full_name'];
                $voucherData['project'] = $req['project_name'] ?? 'N/A';
                $voucherData['purpose'] = 'Petty Cash / Fund Requisition - ' . $req['purpose'];
                $voucherData['amount'] = (float)$req['amount'];
                $voucherData['payment_method'] = $req['payment_method'] ?? 'Cash';
                
                if ($req['status'] === 'paid') {
                    $voucherData['transactions'][] = [
                        'amount_paid' => $req['amount'],
                        'payment_method' => $req['payment_method'] ?? 'Cash',
                        'transaction_date' => $req['paid_at'] ?? $req['updated_at'],
                        'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '', 'reference_number' => ''
                    ];
                }
                break;

            case 'contractor_bill':
                $stmt = $this->db->prepare("SELECT cbr.*, p.name as project_name, c.name as contractor_name FROM contractor_bill_requests cbr LEFT JOIN projects p ON cbr.project_id = p.id LEFT JOIN contractors c ON c.id = cbr.operator_id WHERE cbr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['paid_to'] = $req['contractor_name'];
                $voucherData['project'] = $req['project_name'] ?? 'N/A';
                $voucherData['purpose'] = 'Contractor Bill - ' . $req['work_description'];
                
                $tStmt = $this->db->prepare("SELECT amount_paid, payment_method, transaction_date, reference_number FROM con_financial_transactions WHERE ref_type = 'contractor_bill' AND ref_id = ? ORDER BY created_at ASC");
                $tStmt->bind_param("i", $id);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                while ($row = $tRes->fetch_assoc()) {
                    $voucherData['transactions'][] = $row;
                    $voucherData['amount'] += (float)$row['amount_paid'];
                    $voucherData['payment_method'] = $row['payment_method'];
                }
                $tStmt->close();
                break;

            case 'vendor_payment':
                $stmt = $this->db->prepare("SELECT vpr.*, p.name as project_name, v.name as vendor_name FROM vendor_payment_requests vpr LEFT JOIN projects p ON vpr.project_id = p.id LEFT JOIN vendors v ON v.id = vpr.vendor_id WHERE vpr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['paid_to'] = $req['vendor_name'];
                $voucherData['project'] = $req['project_name'] ?? 'N/A';
                $voucherData['purpose'] = 'Vendor Payment - Invoice ' . ($req['invoice_number'] ?? 'N/A');
                
                $tStmt = $this->db->prepare("SELECT amount_paid, payment_method, transaction_date, reference_number FROM con_financial_transactions WHERE ref_type = 'vendor_payment' AND ref_id = ? ORDER BY created_at ASC");
                $tStmt->bind_param("i", $id);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                while ($row = $tRes->fetch_assoc()) {
                    $voucherData['transactions'][] = $row;
                    $voucherData['amount'] += (float)$row['amount_paid'];
                    $voucherData['payment_method'] = $row['payment_method'];
                }
                $tStmt->close();
                break;

            case 'operational_fund':
                $stmt = $this->db->prepare("SELECT ofr.*, p.name as project_name, u.full_name FROM operational_fund_requests ofr LEFT JOIN projects p ON ofr.project_id = p.id LEFT JOIN users u ON u.id = ofr.requester_id WHERE ofr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['paid_to'] = $req['full_name'];
                $voucherData['project'] = $req['project_name'] ?? 'N/A';
                $voucherData['purpose'] = 'Operational Fund - ' . $req['purpose'];
                
                $tStmt = $this->db->prepare("SELECT amount_paid, payment_method, transaction_date, reference_number FROM con_financial_transactions WHERE ref_type = 'fund_request' AND ref_id = ? ORDER BY created_at ASC");
                $tStmt->bind_param("i", $id);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                while ($row = $tRes->fetch_assoc()) {
                    $voucherData['transactions'][] = $row;
                    $voucherData['amount'] += (float)$row['amount_paid'];
                    $voucherData['payment_method'] = $row['payment_method'];
                }
                $tStmt->close();
                break;

            case 'land_requisition':
                $stmt = $this->db->prepare("SELECT lr.*, u.full_name FROM land_fund_requisitions lr LEFT JOIN users u ON u.id = lr.requested_by WHERE lr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['paid_to'] = $req['full_name'];
                $voucherData['project'] = 'Land Acquisition';
                $voucherData['purpose'] = 'Land Requisition (' . $req['requisition_type'] . ') - ' . $req['purpose'];
                $voucherData['amount'] = (float)$req['requested_amount'];
                $voucherData['payment_method'] = $req['payment_method'] ?? 'Cash';
                
                if ($req['accounts_status'] === 'Paid') {
                    $voucherData['transactions'][] = [
                        'amount_paid' => $req['requested_amount'],
                        'payment_method' => $req['payment_method'] ?? 'Cash',
                        'transaction_date' => $req['accounts_paid_at'] ?? $req['updated_at'],
                        'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '', 'reference_number' => $req['transaction_no'] ?? ''
                    ];
                }
                break;

            case 'expense_adjustment':
                $stmt = $this->db->prepare("SELECT ea.*, u.full_name FROM expense_adjustments ea LEFT JOIN users u ON u.id = ea.user_id WHERE ea.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['paid_to'] = $req['full_name'];
                $voucherData['project'] = 'N/A';
                $voucherData['purpose'] = 'Expense Adjustment - ' . $req['title'];
                
                $tStmt = $this->db->prepare("SELECT amount as amount_paid, payment_method, payment_date as transaction_date, bank_name, bank_account_no, cheque_no, online_type, transaction_ref as reference_number FROM adjustment_transactions WHERE adjustment_id = ? ORDER BY created_at ASC");
                $tStmt->bind_param("i", $id);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                while ($row = $tRes->fetch_assoc()) {
                    $voucherData['transactions'][] = $row;
                    if ($req['transaction_type'] === 'cashout') {
                        $voucherData['amount'] += (float)$row['amount_paid'];
                    } else {
                        $voucherData['amount'] += (float)$row['amount_paid']; 
                    }
                    $voucherData['payment_method'] = $row['payment_method'];
                }
                $tStmt->close();
                break;

            default:
                die("Unknown module");
        }

        // Convert number to words helper
        $voucherData['amount_words'] = $this->numberToWords($voucherData['amount']) . " Taka Only";

        // Load view
        $this->view('accounts/vouchers/debit-voucher-print', ['voucher' => $voucherData]);
    }

    private function numberToWords($number)
    {
        $no = round($number);
        $point = round($number - $no, 2) * 100;
        $hundred = null;
        $digits_1 = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            '0' => '', '1' => 'One', '2' => 'Two',
            '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
            '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
            '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
            '13' => 'Thirteen', '14' => 'Fourteen',
            '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
            '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty',
            '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
            '60' => 'Sixty', '70' => 'Seventy',
            '80' => 'Eighty', '90' => 'Ninety'
        );
        $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
        while ($i < $digits_1) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str[] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred :
                    $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
            } else $str[] = null;
        }
        $str = array_reverse($str);
        $result = implode('', $str);
        return $result;
    }
}
