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
            case 'cashout_requests':
                // Main Request
                $stmt = $this->db->prepare("SELECT cr.*, p.name as project_name FROM cashout_requests cr LEFT JOIN projects p ON cr.project_id = p.id WHERE cr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'CSH-' . $id;
                $voucherData['paid_to'] = $req['purpose_paid_to'];
                $voucherData['project'] = $req['project_name'] ?? 'Head Office / Operations';
                $voucherData['purpose'] = 'Cashout Request - ' . ($req['remarks'] ?? 'General Cashout');
                
                $expenseDetails = !empty($req['description']) ? $req['description'] : (!empty($req['remarks']) ? $req['remarks'] : ($req['purpose_paid_to'] ?? 'Cashout Requisition'));
                $voucherData['expense_details'] = $expenseDetails;

                // Transactions
                $tStmt = $this->db->prepare("SELECT amount as amount_paid, payment_method, payment_date as transaction_date, bank_name, bank_account_no, cheque_no, online_type, transaction_ref as reference_number FROM cashout_payments WHERE cashout_request_id = ? ORDER BY created_at ASC");
                if ($tStmt) {
                    $tStmt->bind_param("i", $id);
                    $tStmt->execute();
                    $tRes = $tStmt->get_result();
                    while ($row = $tRes->fetch_assoc()) {
                        $row['description'] = $expenseDetails;
                        $voucherData['transactions'][] = $row;
                        $voucherData['amount'] += (float)$row['amount_paid'];
                        $voucherData['payment_method'] = $row['payment_method'];
                    }
                    $tStmt->close();
                }
                if (empty($voucherData['transactions'])) {
                    $voucherData['amount'] = (float)($req['approved_amount'] ?? $req['amount'] ?? 0);
                    $voucherData['payment_method'] = 'Cash';
                    $voucherData['transactions'][] = [
                        'amount_paid' => $voucherData['amount'],
                        'payment_method' => 'Cash',
                        'transaction_date' => $req['updated_at'] ?? date('Y-m-d'),
                        'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                        'reference_number' => 'CSH-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                        'description' => $expenseDetails
                    ];
                }
                break;

            case 'fund_requisition':
            case 'fund_requisitions':
                $stmt = $this->db->prepare("SELECT fr.*, p.name as project_name, u.full_name FROM fund_requisitions fr LEFT JOIN projects p ON fr.project_id = p.id LEFT JOIN users u ON u.id = fr.requested_by WHERE fr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'REQ-' . $id;
                $voucherData['paid_to'] = $req['full_name'];
                $voucherData['project'] = $req['project_name'] ?? 'General Operations';
                $voucherData['purpose'] = 'Petty Cash / Fund Requisition - ' . $req['purpose'];

                $descParts = [];
                if (!empty($req['category'])) $descParts[] = ucfirst(str_replace('_', ' ', $req['category']));
                if (!empty($req['purpose'])) $descParts[] = $req['purpose'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : ($req['purpose'] ?? 'Fund Requisition');
                $voucherData['expense_details'] = $expenseDetails;

                $voucherData['amount'] = (float)(!empty($req['paid_amount']) && $req['paid_amount'] > 0 ? $req['paid_amount'] : $req['amount']);
                $voucherData['payment_method'] = $req['payment_method'] ?? 'Cash';
                
                $voucherData['transactions'][] = [
                    'amount_paid' => $voucherData['amount'],
                    'payment_method' => $req['payment_method'] ?? 'Cash',
                    'transaction_date' => $req['paid_at'] ?? $req['updated_at'] ?? date('Y-m-d'),
                    'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '', 
                    'reference_number' => 'FR-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                    'description' => $expenseDetails
                ];

                // Approval summary (shows partial approvals clearly on approval / payment voucher)
                $frReqAmt = (float)($req['amount'] ?? 0);
                $frAppAmt = (float)($req['approved_amount'] ?? 0);
                $voucherData['approval_summary'] = [
                    'requested'   => $frReqAmt,
                    'qc_approved' => (float)($req['qc_approved_amount'] ?? 0),
                    'approved'    => $frAppAmt,
                    'remaining'   => max(0, $frReqAmt - $frAppAmt),
                    'paid'        => (float)($req['paid_amount'] ?? 0),
                    'is_partial'  => ($req['status'] === 'partially_approved') || ($frAppAmt > 0 && $frAppAmt + 0.01 < $frReqAmt),
                ];
                break;

            case 'contractor_bill':
            case 'contractor_bill_requests':
                $stmt = $this->db->prepare("SELECT cbr.*, p.name as project_name, c.name as contractor_name FROM contractor_bill_requests cbr LEFT JOIN projects p ON cbr.project_id = p.id LEFT JOIN contractors c ON c.id = cbr.operator_id WHERE cbr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'CB-' . $id;
                $voucherData['paid_to'] = $req['contractor_name'];
                $voucherData['project'] = $req['project_name'] ?? 'Construction Site';
                $voucherData['purpose'] = 'Contractor Bill - ' . $req['work_description'];
                
                $descParts = [];
                if (!empty($req['bill_type'])) $descParts[] = "Bill Type: " . ucfirst(str_replace('_', ' ', $req['bill_type']));
                if (!empty($req['work_description'])) $descParts[] = $req['work_description'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Contractor Work Bill';
                $voucherData['expense_details'] = $expenseDetails;

                $tStmt = $this->db->prepare("SELECT amount_paid, payment_method, transaction_date, reference_number FROM con_financial_transactions WHERE ref_type = 'contractor_bill' AND ref_id = ? ORDER BY created_at ASC");
                $tStmt->bind_param("i", $id);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                while ($row = $tRes->fetch_assoc()) {
                    if (empty($row['reference_number'])) {
                        $row['reference_number'] = 'CB-' . $id;
                    }
                    $row['description'] = $expenseDetails;
                    $voucherData['transactions'][] = $row;
                    $voucherData['amount'] += (float)$row['amount_paid'];
                    $voucherData['payment_method'] = $row['payment_method'];
                }
                $tStmt->close();
                if (empty($voucherData['transactions'])) {
                    $voucherData['amount'] = (float)(!empty($req['approved_amount']) ? $req['approved_amount'] : $req['bill_amount']);
                    $voucherData['payment_method'] = 'Bank / Cash';
                    $voucherData['transactions'][] = [
                        'amount_paid' => $voucherData['amount'],
                        'payment_method' => 'Bank / Cash',
                        'transaction_date' => $req['updated_at'] ?? date('Y-m-d'),
                        'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                        'reference_number' => 'CB-' . $id,
                        'description' => $expenseDetails
                    ];
                }
                break;

            case 'vendor_payment':
            case 'vendor_payment_requests':
                $stmt = $this->db->prepare("SELECT vpr.*, p.name as project_name, v.name as vendor_name, po.id as po_number, po.remarks as po_remarks FROM vendor_payment_requests vpr LEFT JOIN projects p ON vpr.project_id = p.id LEFT JOIN vendors v ON v.id = vpr.vendor_id LEFT JOIN purchase_orders po ON po.id = vpr.purchase_order_id WHERE vpr.id = ?");
                if (!$stmt) die("Error preparing query: " . $this->db->error);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'VPR-' . $id;
                $voucherData['paid_to'] = $req['vendor_name'] ?? 'Vendor';
                $voucherData['project'] = $req['project_name'] ?? 'Construction Site';
                $voucherData['purpose'] = 'Vendor Payment - Invoice ' . ($req['invoice_number'] ?? 'N/A');
                
                $descParts = [];
                if (!empty($req['po_number'])) $descParts[] = "PO: #" . $req['po_number'];
                if (!empty($req['invoice_number'])) $descParts[] = "Invoice: " . $req['invoice_number'];
                if (!empty($req['payment_type'])) $descParts[] = "Payment Type: " . ucfirst(str_replace('_', ' ', $req['payment_type']));
                if (!empty($req['management_note'])) $descParts[] = "Note: " . $req['management_note'];
                if (!empty($req['qc_note'])) $descParts[] = "QC Note: " . $req['qc_note'];
                $expenseDetails = !empty($descParts) ? implode(' | ', $descParts) : 'Vendor Materials / Supplies';
                $voucherData['expense_details'] = $expenseDetails;

                $tStmt = $this->db->prepare("SELECT amount_paid, payment_method, transaction_date, reference_number FROM con_financial_transactions WHERE ref_type = 'vendor_payment' AND ref_id = ? ORDER BY created_at ASC");
                $tStmt->bind_param("i", $id);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                while ($row = $tRes->fetch_assoc()) {
                    if (empty($row['reference_number'])) {
                        $row['reference_number'] = 'CB-' . $id;
                    }
                    $row['description'] = $expenseDetails;
                    $voucherData['transactions'][] = $row;
                    $voucherData['amount'] += (float)$row['amount_paid'];
                    $voucherData['payment_method'] = $row['payment_method'];
                }
                $tStmt->close();
                if (empty($voucherData['transactions'])) {
                    $voucherData['amount'] = (float)(!empty($req['approved_amount']) ? $req['approved_amount'] : ($req['requested_amount'] ?? $req['request_amount'] ?? $req['net_payable'] ?? $req['invoice_amount'] ?? $req['amount'] ?? 0));
                    $voucherData['payment_method'] = 'Bank / Cash';
                    $voucherData['transactions'][] = [
                        'amount_paid' => $voucherData['amount'],
                        'payment_method' => 'Bank / Cash',
                        'transaction_date' => $req['updated_at'] ?? date('Y-m-d'),
                        'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                        'reference_number' => 'VEND-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                        'description' => $expenseDetails
                    ];
                }
                break;

            case 'operational_fund':
            case 'operational_fund_requests':
                $stmt = $this->db->prepare("SELECT ofr.*, p.name as project_name, u.full_name FROM operational_fund_requests ofr LEFT JOIN projects p ON ofr.project_id = p.id LEFT JOIN users u ON u.id = ofr.requester_id WHERE ofr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'REQ-' . $id;
                $voucherData['paid_to'] = $req['full_name'];
                $voucherData['project'] = $req['project_name'] ?? 'Construction Site';
                $voucherData['purpose'] = 'Operational Fund - ' . $req['purpose'];
                
                $expenseDetails = !empty($req['purpose']) ? $req['purpose'] : 'Operational Site Fund';
                $voucherData['expense_details'] = $expenseDetails;

                $tStmt = $this->db->prepare("SELECT amount_paid, payment_method, transaction_date, reference_number FROM con_financial_transactions WHERE ref_type = 'fund_request' AND ref_id = ? ORDER BY created_at ASC");
                $tStmt->bind_param("i", $id);
                $tStmt->execute();
                $tRes = $tStmt->get_result();
                while ($row = $tRes->fetch_assoc()) {
                    if (empty($row['reference_number'])) {
                        $row['reference_number'] = 'CB-' . $id;
                    }
                    $row['description'] = $expenseDetails;
                    $voucherData['transactions'][] = $row;
                    $voucherData['amount'] += (float)$row['amount_paid'];
                    $voucherData['payment_method'] = $row['payment_method'];
                }
                $tStmt->close();
                if (empty($voucherData['transactions'])) {
                    $voucherData['amount'] = (float)(!empty($req['approved_amount']) ? $req['approved_amount'] : ($req['requested_amount'] ?? $req['request_amount'] ?? $req['amount'] ?? 0));
                    $voucherData['payment_method'] = 'Cash';
                    $voucherData['transactions'][] = [
                        'amount_paid' => $voucherData['amount'],
                        'payment_method' => 'Cash',
                        'transaction_date' => $req['updated_at'] ?? date('Y-m-d'),
                        'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                        'reference_number' => 'OPF-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                        'description' => $expenseDetails
                    ];
                }
                break;

            case 'advance_salaries':
            case 'advance_salary':
                $stmt = $this->db->prepare("
                    SELECT a.*, e.full_name as employee_name, e.employee_id as emp_code, d.name as dept_name 
                    FROM advance_salaries a 
                    LEFT JOIN employees e ON e.id = a.employee_id 
                    LEFT JOIN departments d ON d.id = e.department_id 
                    WHERE a.id = ?
                ");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'ADV-' . $id;
                $voucherData['paid_to'] = (!empty($req['employee_name']) ? $req['employee_name'] : 'Employee') . (!empty($req['emp_code']) ? ' (' . $req['emp_code'] . ')' : '');
                $voucherData['project'] = !empty($req['dept_name']) ? $req['dept_name'] : 'Head Office / HR Payroll';
                $voucherData['purpose'] = 'Advance Salary Request - ' . ($req['reason'] ?? 'Staff Advance');
                
                $descParts = [];
                if (!empty($req['reason'])) $descParts[] = "Reason: " . $req['reason'];
                if (!empty($req['deduction_months'])) $descParts[] = "Deduction: " . $req['deduction_months'] . " Month(s)";
                if (!empty($req['deduction_start_month'])) $descParts[] = "Starting: " . date('M Y', strtotime($req['deduction_start_month'] . '-01'));
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Advance Salary Request';
                $voucherData['expense_details'] = $expenseDetails;

                $amt = (float)(!empty($req['paid_amount']) && $req['paid_amount'] > 0 ? $req['paid_amount'] : $req['amount']);
                $voucherData['amount'] = $amt;
                $voucherData['payment_method'] = 'Cash / Bank';

                $voucherData['transactions'][] = [
                    'amount_paid' => $amt,
                    'payment_method' => 'Cash / Bank',
                    'transaction_date' => $req['approved_date'] ?? $req['updated_at'] ?? date('Y-m-d'),
                    'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                    'reference_number' => 'ADV-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                    'description' => $expenseDetails
                ];
                break;

            case 'project_other_costs':
            case 'project_other_cost':
                $stmt = $this->db->prepare("
                    SELECT poc.*, p.name as project_name, u.full_name as requester_name 
                    FROM project_other_costs poc 
                    LEFT JOIN projects p ON p.id = poc.project_id 
                    LEFT JOIN users u ON u.id = poc.created_by_id 
                    WHERE poc.id = ?
                ");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'POC-' . $id;
                $voucherData['paid_to'] = $req['requester_name'] ?? 'Site Operator';
                $voucherData['project'] = $req['project_name'] ?? 'Construction Site';
                $costTypeLabel = !empty($req['rcost_type']) 
                    ? (class_exists('ProjectOtherCost') ? ProjectOtherCost::getCostTypeLabel($req['rcost_type']) : ucwords(str_replace('_', ' ', $req['rcost_type']))) 
                    : 'Site Expense';
                $voucherData['purpose'] = 'Project Other Cost (' . $costTypeLabel . ') - ' . ($req['purpose'] ?? '');

                $descParts = [];
                if (!empty($req['rcost_type'])) $descParts[] = $costTypeLabel;
                if (!empty($req['purpose'])) $descParts[] = $req['purpose'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Project Other Cost';
                $voucherData['expense_details'] = $expenseDetails;

                $amt = (float)(!empty($req['payment_amount']) && $req['payment_amount'] > 0 ? $req['payment_amount'] : (!empty($req['approved_amount']) ? $req['approved_amount'] : $req['request_amount']));
                $voucherData['amount'] = $amt;
                $voucherData['payment_method'] = 'Cash';

                $voucherData['transactions'][] = [
                    'amount_paid' => $amt,
                    'payment_method' => 'Cash',
                    'transaction_date' => $req['payment_at'] ?? $req['updated_at'] ?? date('Y-m-d'),
                    'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                    'reference_number' => 'POC-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                    'description' => $expenseDetails
                ];
                break;

            case 'con_labor_requisitions':
            case 'con_labor_requisition':
                $stmt = $this->db->prepare("
                    SELECT lr.*, p.name as project_name, u.full_name as se_name 
                    FROM con_labor_requisitions lr 
                    LEFT JOIN projects p ON p.id = lr.project_id 
                    LEFT JOIN users u ON u.id = lr.requested_by 
                    WHERE lr.id = ?
                ");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = !empty($req['requisition_no']) ? $req['requisition_no'] : ('LR-' . $id);
                $voucherData['paid_to'] = $req['se_name'] ?? 'Site Engineer / Labor Contractor';
                $voucherData['project'] = $req['project_name'] ?? 'Construction Site';
                $voucherData['purpose'] = 'Labor Bill by SE - ' . ($req['requisition_no'] ?? 'Requisition #' . $id);
                
                $descParts = [];
                if (!empty($req['labor_count'])) {
                    $descParts[] = $req['labor_count'] . " " . ucfirst($req['labor_type'] ?? 'General') . " Labors" . (!empty($req['working_days']) ? " (" . $req['working_days'] . " days)" : "");
                }
                if (!empty($req['purpose'])) $descParts[] = $req['purpose'];
                if (!empty($req['remarks'])) $descParts[] = "Note: " . $req['remarks'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Labor Bill';
                $voucherData['expense_details'] = $expenseDetails;

                $amt = (float)(!empty($req['total_paid']) && $req['total_paid'] > 0 ? $req['total_paid'] : (!empty($req['approved_amount']) ? $req['approved_amount'] : $req['total_amount']));
                $voucherData['amount'] = $amt;
                $voucherData['payment_method'] = 'Cash';

                $tStmt = $this->db->prepare("SELECT amount_paid, payment_method, transaction_date, reference_number FROM con_financial_transactions WHERE ref_type = 'labor_requisition' AND ref_id = ? ORDER BY created_at ASC");
                if ($tStmt) {
                    $tStmt->bind_param("i", $id);
                    $tStmt->execute();
                    $tRes = $tStmt->get_result();
                    while ($row = $tRes->fetch_assoc()) {
                        $row['description'] = $expenseDetails;
                        $voucherData['transactions'][] = $row;
                        $voucherData['payment_method'] = $row['payment_method'];
                    }
                    $tStmt->close();
                }

                if (empty($voucherData['transactions'])) {
                    $voucherData['transactions'][] = [
                        'amount_paid' => $amt,
                        'payment_method' => 'Cash',
                        'transaction_date' => $req['payment_date'] ?? $req['updated_at'] ?? date('Y-m-d'),
                        'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                        'reference_number' => $req['requisition_no'] ?? ('LAB-' . str_pad($id, 4, '0', STR_PAD_LEFT)),
                        'description' => $expenseDetails
                    ];
                }
                break;

            case 'payslips':
            case 'payslip':
            case 'salary':
                $stmt = $this->db->prepare("
                    SELECT ps.*, e.full_name as employee_name, e.employee_id as emp_code, d.name as dept_name, pr.name as project_name 
                    FROM payslips ps 
                    LEFT JOIN employees e ON e.id = ps.employee_id 
                    LEFT JOIN departments d ON d.id = e.department_id 
                    LEFT JOIN payroll_projects pr ON pr.id = ps.payroll_project_id 
                    WHERE ps.id = ?
                ");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'PAY-' . $id;
                $voucherData['paid_to'] = (!empty($req['employee_name']) ? $req['employee_name'] : 'Employee') . (!empty($req['emp_code']) ? ' (' . $req['emp_code'] . ')' : '');
                $voucherData['project'] = !empty($req['project_name']) ? $req['project_name'] : (!empty($req['dept_name']) ? $req['dept_name'] : 'Head Office');
                $voucherData['purpose'] = 'Monthly Staff Salary - ' . ($req['payslip_month'] ?? date('M Y'));
                
                $descParts = [];
                if (!empty($req['payslip_month'])) $descParts[] = "Salary Month: " . $req['payslip_month'];
                if (!empty($req['remarks'])) $descParts[] = $req['remarks'];
                if (!empty($req['attendance_remarks'])) $descParts[] = $req['attendance_remarks'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Monthly Staff Salary';
                $voucherData['expense_details'] = $expenseDetails;

                $amt = (float)($req['net_salary'] ?? 0);
                $voucherData['amount'] = $amt;
                $voucherData['payment_method'] = $req['payment_method'] ?? 'Bank / Cash';

                $voucherData['transactions'][] = [
                    'amount_paid' => $amt,
                    'payment_method' => $req['payment_method'] ?? 'Bank / Cash',
                    'transaction_date' => $req['disbursed_at'] ?? $req['updated_at'] ?? date('Y-m-d'),
                    'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                    'reference_number' => 'SAL-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                    'description' => $expenseDetails
                ];
                break;

            case 'project_extra_assets':
            case 'purchase_requests':
            case 'office_procurement':
                $stmt = $this->db->prepare("
                    SELECT pea.*, p.name as project_name, u.full_name as requester_name 
                    FROM project_extra_assets pea 
                    LEFT JOIN projects p ON p.id = pea.project_id 
                    LEFT JOIN users u ON u.id = pea.requested_by 
                    WHERE pea.id = ?
                ");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'PR-' . $id;
                $voucherData['paid_to'] = !empty($req['vendor_name']) ? $req['vendor_name'] : ($req['requester_name'] ?? 'Procurement Vendor');
                $voucherData['project'] = $req['project_name'] ?? 'Head Office / Operations';
                $voucherData['purpose'] = 'Office Procurement / Extra Asset - ' . ($req['item_name'] ?? 'Procurement');
                
                $descParts = [];
                if (!empty($req['item_name'])) {
                    $descParts[] = $req['item_name'] . (!empty($req['quantity']) ? " (Qty: " . $req['quantity'] . ")" : "");
                }
                if (!empty($req['justification'])) $descParts[] = "Justification: " . $req['justification'];
                if (!empty($req['purchase_note'])) $descParts[] = "Note: " . $req['purchase_note'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Office Procurement Request';
                $voucherData['expense_details'] = $expenseDetails;

                $amt = (float)(!empty($req['paid_amount']) && $req['paid_amount'] > 0 ? $req['paid_amount'] : (!empty($req['total_price']) ? $req['total_price'] : 0));
                $voucherData['amount'] = $amt;
                $voucherData['payment_method'] = $req['payment_method'] ?? 'Cash / Bank';

                $voucherData['transactions'][] = [
                    'amount_paid' => $amt,
                    'payment_method' => $req['payment_method'] ?? 'Cash / Bank',
                    'transaction_date' => $req['paid_at'] ?? $req['updated_at'] ?? date('Y-m-d'),
                    'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                    'reference_number' => 'PROC-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                    'description' => $expenseDetails
                ];
                break;

            case 'land_requisition':
            case 'land_fund_requisitions':
            case 'con_land_requisitions':
                $stmt = $this->db->prepare("SELECT lr.*, u.full_name FROM land_fund_requisitions lr LEFT JOIN users u ON u.id = lr.requested_by WHERE lr.id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'LFR-' . $id;
                $voucherData['paid_to'] = $req['full_name'];
                $voucherData['project'] = 'Land Acquisition';
                $voucherData['purpose'] = 'Land Requisition (' . $req['requisition_type'] . ') - ' . $req['purpose'];
                
                $descParts = [];
                if (!empty($req['requisition_type'])) $descParts[] = ucfirst($req['requisition_type']);
                if (!empty($req['purpose'])) $descParts[] = $req['purpose'];
                if (!empty($req['remarks'])) $descParts[] = "Note: " . $req['remarks'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Land Fund Requisition';
                $voucherData['expense_details'] = $expenseDetails;

                $voucherData['amount'] = (float)($req['requested_amount'] ?? $req['request_amount'] ?? $req['amount'] ?? 0);
                $voucherData['payment_method'] = $req['payment_method'] ?? 'Cash';
                
                $voucherData['transactions'][] = [
                    'amount_paid' => $voucherData['amount'],
                    'payment_method' => $req['payment_method'] ?? 'Cash',
                    'transaction_date' => $req['accounts_paid_at'] ?? $req['updated_at'] ?? date('Y-m-d'),
                    'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '', 
                    'reference_number' => $req['transaction_no'] ?? ('LAND-' . str_pad($id, 4, '0', STR_PAD_LEFT)),
                    'description' => $expenseDetails
                ];
                break;

            case 'client_refund_requests':
            case 'client_refund':
                $stmt = $this->db->prepare("
                    SELECT cr.*, cr.booking_id, l.lead_name, p.name as project_name 
                    FROM client_refund_requests cr 
                    LEFT JOIN lead_bookings lb ON lb.id = cr.booking_id 
                    LEFT JOIN leads l ON l.id = cr.lead_id 
                    LEFT JOIN projects p ON p.id = cr.project_id 
                    WHERE cr.id = ?
                ");
                if (!$stmt) die("Error preparing query: " . $this->db->error);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'REF-' . $id;
                $voucherData['paid_to'] = $req['lead_name'] ?? 'Client / Buyer';
                $voucherData['project'] = $req['project_name'] ?? 'Sales Project';
                $voucherData['purpose'] = 'Client Refund Request (' . (!empty($req['booking_id']) ? 'Booking #' . $req['booking_id'] : '') . ') - ' . ($req['reason'] ?? 'Booking Refund');
                
                $descParts = [];
                if (!empty($req['booking_id'])) $descParts[] = "Booking: #" . $req['booking_id'];
                if (!empty($req['reason'])) $descParts[] = "Reason: " . $req['reason'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Client Refund Request';
                $voucherData['expense_details'] = $expenseDetails;

                $amt = (float)($req['refund_amount'] ?? 0);
                $voucherData['amount'] = $amt;
                $voucherData['payment_method'] = 'Bank Transfer / Cheque';

                $voucherData['transactions'][] = [
                    'amount_paid' => $amt,
                    'payment_method' => 'Bank Transfer / Cheque',
                    'transaction_date' => $req['updated_at'] ?? date('Y-m-d'),
                    'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                    'reference_number' => 'REF-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                    'description' => $expenseDetails
                ];
                break;

            case 'project_incentive_claims':
            case 'incentive_claim':
                $stmt = $this->db->prepare("
                    SELECT pic.*, u.full_name as exec_name, p.name as project_name 
                    FROM project_incentive_claims pic 
                    LEFT JOIN users u ON u.id = pic.user_id 
                    LEFT JOIN projects p ON p.id = pic.project_id 
                    WHERE pic.id = ?
                ");
                if (!$stmt) die("Error preparing query: " . $this->db->error);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'INC-' . $id;
                $voucherData['paid_to'] = $req['exec_name'] ?? 'Sales Executive';
                $voucherData['project'] = $req['project_name'] ?? 'Sales Department';
                $voucherData['purpose'] = 'Sales Incentive Claim - ' . ($req['remarks'] ?? 'Incentive Claim');
                
                $expenseDetails = !empty($req['remarks']) ? $req['remarks'] : 'Sales Incentive Claim';
                $voucherData['expense_details'] = $expenseDetails;

                $pStmt = $this->db->prepare("SELECT payment_amount as amount_paid, payment_method, payment_date as transaction_date, bank_name, transaction_ref as reference_number, notes as description FROM project_incentive_payments WHERE claim_id = ? ORDER BY created_at ASC");
                if ($pStmt) {
                    $pStmt->bind_param("i", $id);
                    $pStmt->execute();
                    $pRes = $pStmt->get_result();
                    while ($pRow = $pRes->fetch_assoc()) {
                        $pRow['cheque_no'] = '';
                        $pRow['bank_account_no'] = '';
                        $pRow['online_type'] = '';
                        if (empty($pRow['description'])) {
                            $pRow['description'] = $expenseDetails;
                        }
                        $voucherData['transactions'][] = $pRow;
                        $voucherData['amount'] += (float)$pRow['amount_paid'];
                        $voucherData['payment_method'] = $pRow['payment_method'];
                    }
                    $pStmt->close();
                }
                if (empty($voucherData['transactions'])) {
                    $voucherData['amount'] = (float)($req['claim_amount'] ?? 0);
                    $voucherData['payment_method'] = 'Bank / Cash';
                    $voucherData['transactions'][] = [
                        'amount_paid' => $voucherData['amount'],
                        'payment_method' => 'Bank / Cash',
                        'transaction_date' => $req['claim_date'] ?? date('Y-m-d'),
                        'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '', 
                        'reference_number' => 'INC-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                        'description' => $expenseDetails
                    ];
                }
                break;

            case 'expense_adjustment':
            case 'expense_adjustments':
                $stmt = $this->db->prepare("SELECT ea.*, u.full_name, p.name as project_name FROM expense_adjustments ea LEFT JOIN users u ON u.id = ea.user_id LEFT JOIN projects p ON p.id = ea.project_id WHERE ea.id = ?");
                if (!$stmt) die("Error preparing query: " . $this->db->error);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Not found");

                $voucherData['voucher_no'] = 'ADJ-' . $id;
                $voucherData['paid_to'] = $req['full_name'];
                $voucherData['project'] = !empty($req['project_name']) ? $req['project_name'] : (!empty($req['office']) ? $req['office'] : 'Head Office / Operations');
                $voucherData['purpose'] = 'Expense Adjustment - ' . $req['title'];
                
                $descParts = [];
                if (!empty($req['title'])) $descParts[] = $req['title'];
                if (!empty($req['description'])) $descParts[] = $req['description'];
                $expenseDetails = !empty($descParts) ? implode(' — ', $descParts) : 'Expense Adjustment';
                $voucherData['expense_details'] = $expenseDetails;

                $tStmt = $this->db->prepare("SELECT amount as amount_paid, payment_method, payment_date as transaction_date, bank_account_no, cheque_no, online_type, transaction_ref as reference_number FROM adjustment_transactions WHERE adjustment_id = ? ORDER BY created_at ASC");
                if ($tStmt) {
                    $tStmt->bind_param("i", $id);
                    $tStmt->execute();
                    $tRes = $tStmt->get_result();
                    while ($row = $tRes->fetch_assoc()) {
                        $row['bank_name'] = '';
                        $row['description'] = $expenseDetails;
                        $voucherData['transactions'][] = $row;
                        $voucherData['amount'] += (float)$row['amount_paid'];
                        $voucherData['payment_method'] = $row['payment_method'];
                    }
                    $tStmt->close();
                }
                break;

            default:
                // Fallback for any unknown module using approval_requests table
                $stmt = $this->db->prepare("SELECT ar.*, u.full_name FROM approval_requests ar LEFT JOIN users u ON ar.requested_by = u.id WHERE ar.source_table = ? AND ar.source_id = ? ORDER BY ar.id DESC LIMIT 1");
                $stmt->bind_param("si", $module, $id);
                $stmt->execute();
                $req = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$req) die("Voucher details not found for this module.");

                $voucherData['paid_to'] = $req['full_name'] ?? 'N/A';
                $voucherData['project'] = $req['department'] ?? 'General / Operations';
                $voucherData['purpose'] = $req['title'] ?? (ucfirst(str_replace('_', ' ', $module)) . ' Request');
                
                $voucherData['expense_details'] = $req['title'] ?? 'System Request';

                $amt = (float)(!empty($req['approved_amount']) && $req['approved_amount'] > 0 ? $req['approved_amount'] : $req['request_amount']);
                $voucherData['amount'] = $amt;
                $voucherData['payment_method'] = 'System Entry';

                $voucherData['transactions'][] = [
                    'amount_paid' => $amt,
                    'payment_method' => 'System Entry',
                    'transaction_date' => $req['updated_at'] ?? date('Y-m-d'),
                    'bank_name' => '', 'bank_account_no' => '', 'cheque_no' => '', 'online_type' => '',
                    'reference_number' => strtoupper(substr($module, 0, 3)) . '-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                    'description' => $voucherData['expense_details']
                ];
                break;
        }
        // Ensure every transaction item has description populated if not already present
        if (!empty($voucherData['transactions'])) {
            foreach ($voucherData['transactions'] as &$tItem) {
                if (empty($tItem['description'])) {
                    $tItem['description'] = $voucherData['expense_details'] ?? '';
                }
            }
            unset($tItem);
        }

        // Fetch audit trail (Created by, Multi-tier Approvals, Paid by with dates)
        $voucherData['audit_trail'] = $this->getAuditTrail($module, $id, $req ?? []);

        // Convert number to words helper
        if (!class_exists('FormatHelper') && file_exists(__DIR__ . '/../helpers/FormatHelper.php')) {
            require_once __DIR__ . '/../helpers/FormatHelper.php';
        }
        $voucherData['amount_words'] = class_exists('FormatHelper') 
            ? FormatHelper::amountInWords($voucherData['amount']) 
            : (function_exists('amountInWords') ? amountInWords($voucherData['amount']) : $this->numberToWords($voucherData['amount']) . " Taka Only");
        
        $voucherData['is_approval_voucher'] = isset($_GET['is_approval']) && $_GET['is_approval'] == 1;

        // Load view
        $this->view('accounts/vouchers/debit-voucher-print', ['voucher' => $voucherData]);
    }

    protected function getAuditTrail($module, $id, $row = [])
    {
        $tableMapping = [
            'cashout' => 'cashout_requests',
            'cashout_requests' => 'cashout_requests',
            'fund_requisition' => 'fund_requisitions',
            'fund_requisitions' => 'fund_requisitions',
            'contractor_bill' => 'contractor_bill_requests',
            'contractor_bills' => 'contractor_bill_requests',
            'contractor_bill_requests' => 'contractor_bill_requests',
            'vendor_payment' => 'vendor_payment_requests',
            'vendor_payments' => 'vendor_payment_requests',
            'vendor_payment_requests' => 'vendor_payment_requests',
            'operational_fund' => 'operational_fund_requests',
            'operational_fund_requests' => 'operational_fund_requests',
            'advance_salary' => 'advance_salaries',
            'advance_salaries' => 'advance_salaries',
            'project_other_cost' => 'project_other_costs',
            'project_other_costs' => 'project_other_costs',
            'con_labor_requisition' => 'con_labor_requisitions',
            'con_labor_requisitions' => 'con_labor_requisitions',
            'payslip' => 'payslips',
            'payslips' => 'payslips',
            'salary' => 'payslips',
            'purchase_requests' => 'project_extra_assets',
            'office_procurement' => 'project_extra_assets',
            'project_extra_assets' => 'project_extra_assets',
            'client_refund' => 'client_refund_requests',
            'client_refund_requests' => 'client_refund_requests',
            'incentive_claim' => 'project_incentive_claims',
            'project_incentive_claims' => 'project_incentive_claims',
            'expense_adjustment' => 'expense_adjustments',
            'expense_adjustments' => 'expense_adjustments',
            'land_requisition' => 'con_land_requisitions',
            'land_fund_requisition' => 'land_fund_requisitions',
            'land_fund_requisitions' => 'land_fund_requisitions',
            'con_land_requisitions' => 'con_land_requisitions'
        ];
        $sourceTable = $tableMapping[$module] ?? $module;

        $possibleTables = [$sourceTable, $module];
        if (in_array($sourceTable, ['con_land_requisitions', 'land_fund_requisitions']) || in_array($module, ['land_requisition', 'con_land_requisitions', 'land_fund_requisitions'])) {
            $possibleTables[] = 'land_fund_requisitions';
            $possibleTables[] = 'con_land_requisitions';
        }
        $possibleTables = array_values(array_unique(array_filter($possibleTables)));
        $inPlaceholders = implode(',', array_fill(0, count($possibleTables), '?'));

        $audit = [
            'creator' => null,
            'approvers' => [],
            'accounts_head' => null,
            'disburser' => null
        ];

        // 1. Fetch matching approval_requests record
        $appReq = null;
        $arSql = "
            SELECT ar.*, 
                   u_req.full_name as req_name, 
                   u_app.full_name as app_name,
                   des_app.name as app_des,
                   u_ah.full_name as ah_name,
                   e_ah.employee_id as ah_code,
                   IFNULL(des_ah.name, 'AGM - Finance & Accounts') as ah_des
            FROM approval_requests ar
            LEFT JOIN users u_req ON ar.requested_by = u_req.id
            LEFT JOIN users u_app ON ar.approved_by = u_app.id
            LEFT JOIN employees e_app ON (e_app.user_id = u_app.id OR e_app.id = u_app.employee_id)
            LEFT JOIN designations des_app ON des_app.id = e_app.designation_id
            LEFT JOIN users u_ah ON ar.accounts_head_by = u_ah.id
            LEFT JOIN employees e_ah ON (e_ah.user_id = u_ah.id OR e_ah.id = u_ah.employee_id)
            LEFT JOIN designations des_ah ON des_ah.id = COALESCE(e_ah.designation_id, u_ah.designation_id)
            WHERE ar.source_table IN ({$inPlaceholders}) AND ar.source_id = ?
            ORDER BY ar.id DESC LIMIT 1
        ";
        $arStmt = $this->db->prepare($arSql);
        if ($arStmt) {
            $types = str_repeat('s', count($possibleTables)) . 'i';
            $params = array_merge($possibleTables, [$id]);
            $arStmt->bind_param($types, ...$params);
            $arStmt->execute();
            $appReq = $arStmt->get_result()->fetch_assoc();
            $arStmt->close();
        }

        // Helper to get user display info
        $getUser = function($uid) {
            if (empty($uid)) return null;
            $st = $this->db->prepare("
                SELECT u.full_name, e.employee_id, IFNULL(des.name, r.name) as designation 
                FROM users u 
                LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id) 
                LEFT JOIN designations des ON des.id = e.designation_id 
                LEFT JOIN roles r ON r.id = u.role_id 
                WHERE u.id = ? LIMIT 1
            ");
            if ($st) {
                $st->bind_param("i", $uid);
                $st->execute();
                $u = $st->get_result()->fetch_assoc();
                $st->close();
                if ($u) {
                    $name = $u['full_name'];
                    if (!empty($u['employee_id'])) $name .= ' [' . $u['employee_id'] . ']';
                    if (!empty($u['designation'])) $name .= ' (' . $u['designation'] . ')';
                    return $name;
                }
            }

            // Fallback check directly in employees table
            $st2 = $this->db->prepare("
                SELECT e.full_name, e.employee_id, des.name as designation 
                FROM employees e 
                LEFT JOIN designations des ON des.id = e.designation_id 
                WHERE e.id = ? LIMIT 1
            ");
            if ($st2) {
                $st2->bind_param("i", $uid);
                $st2->execute();
                $u = $st2->get_result()->fetch_assoc();
                $st2->close();
                if ($u) {
                    $name = $u['full_name'];
                    if (!empty($u['employee_id'])) $name .= ' [' . $u['employee_id'] . ']';
                    if (!empty($u['designation'])) $name .= ' (' . $u['designation'] . ')';
                    return $name;
                }
            }

            return null;
        };

        // Helper to add approver without duplicates
        $addApprover = function($stage, $uid, $dateTime, $note = null) use (&$audit, $getUser) {
            if (empty($uid) && empty($dateTime)) return;
            $name = !empty($uid) ? $getUser($uid) : 'Authorized Official';
            if (empty($name)) return;

            $normalizedIncoming = trim(preg_replace('/\s*\[\d+\].*$/', '', $name));
            $mgmtStages = ['Management Approval', 'Admin & Operation Head', 'Management / Authority', 'Approved & Authorized'];

            foreach ($audit['approvers'] as $k => $appr) {
                $normalizedExisting = trim(preg_replace('/\s*\[\d+\].*$/', '', $appr['name']));
                
                // If exact stage & name matches
                if ($appr['stage'] === $stage && $appr['name'] === $name) {
                    return; // Avoid duplicate
                }
                
                // If same person already approved an overlapping management/admin stage
                if ($normalizedIncoming === $normalizedExisting && in_array($stage, $mgmtStages) && in_array($appr['stage'], $mgmtStages)) {
                    if (!empty($note) && empty($audit['approvers'][$k]['note'])) {
                        $audit['approvers'][$k]['note'] = $note;
                    }
                    if (!empty($dateTime) && empty($audit['approvers'][$k]['date'])) {
                        $audit['approvers'][$k]['date'] = date('d M, Y h:i A', strtotime($dateTime));
                    }
                    return; // Avoid multiple cards for same person
                }
            }

            $audit['approvers'][] = [
                'stage' => $stage,
                'name'  => $name,
                'date'  => !empty($dateTime) ? date('d M, Y h:i A', strtotime($dateTime)) : null,
                'note'  => $note
            ];
        };

        // --- 1. CREATOR ---
        $creatorId = $row['created_by_id'] ?? $row['created_by'] ?? $row['requested_by'] ?? $row['requester_id'] ?? $row['claimed_by'] ?? ($appReq['requested_by'] ?? null);
        $creatorDate = $row['created_at'] ?? $row['request_date'] ?? $row['requisition_date'] ?? $row['bill_date'] ?? ($appReq['created_at'] ?? null);
        $creatorName = null;
        if (!empty($appReq['req_name'])) {
            $creatorName = $appReq['req_name'];
        } elseif (!empty($creatorId)) {
            $creatorName = $getUser($creatorId);
        }
        if (empty($creatorName)) {
            $creatorName = $row['requester_name'] ?? $row['employee_name'] ?? $row['se_name'] ?? $row['exec_name'] ?? $row['full_name'] ?? 'Authorized Requisitioner';
        }
        $audit['creator'] = [
            'name' => $creatorName,
            'date' => !empty($creatorDate) ? date('d M, Y h:i A', strtotime($creatorDate)) : date('d M, Y')
        ];

        // --- 2. MULTI-TIER APPROVERS ---
        // Department Head
        if (!empty($row['dept_head_approved_by'])) {
            $addApprover('Department Head', $row['dept_head_approved_by'], $row['dept_head_approved_at'] ?? null, $row['dept_head_note'] ?? null);
        }
        // Project Manager
        if (!empty($row['pm_approved_by'])) {
            $addApprover('Project Manager', $row['pm_approved_by'], $row['pm_approved_at'] ?? null, $row['pm_note'] ?? null);
        }
        // Site Engineer Verification (Contractor bills)
        if (!empty($row['verified_by'])) {
            $addApprover('Engineer Verification', $row['verified_by'], $row['verified_at'] ?? null);
        }
        // Operations Review (Extra assets / procurement)
        if (!empty($row['operations_by'])) {
            $addApprover('Operations Review', $row['operations_by'], $row['operations_at'] ?? null);
        }
        // Purchase Head
        if (!empty($row['purchase_head_by'])) {
            $addApprover('Purchase Lead', $row['purchase_head_by'], $row['purchase_head_at'] ?? null);
        }
        // PM Payment Approved (Labor requisitions)
        if (!empty($row['pm_payment_approved_by'])) {
            $addApprover('PM Verification', $row['pm_payment_approved_by'], $row['pm_payment_approved_at'] ?? null);
        }
        // QC & Internal Audit
        $qcId = $row['qc_approved_by'] ?? $row['qc_audit_approved_id'] ?? $row['qc_approved_by_id'] ?? $row['qc_audit_by'] ?? null;
        $qcDate = $row['qc_approved_at'] ?? $row['qc_audit_approved_at'] ?? $row['qc_audit_at'] ?? null;
        if (!empty($qcId)) {
            $addApprover('QC & Internal Audit', $qcId, $qcDate, $row['qc_note'] ?? null);
        }
        // Land Requisition Management
        if (!empty($row['mgmt_by'])) {
            $addApprover('Management Approval', $row['mgmt_by'], $row['mgmt_approved_at'] ?? null, $row['mgmt_note'] ?? null);
        }

        // Admin & Operation Head Approval (Mandatory for requests > ৳10,000 or when assigned)
        $voucherAmt = (float)($voucherData['amount'] ?? ($appReq['request_amount'] ?? ($row['bill_amount'] ?? ($row['amount'] ?? 0))));
        $isOver1Lac = ($voucherAmt > 10000.01);
        $opHeadBy = $appReq['op_head_by'] ?? ($row['op_head_by'] ?? null);
        $opHeadAt = $appReq['op_head_at'] ?? ($row['op_head_at'] ?? null);
        $opHeadStatus = strtolower($appReq['op_head_status'] ?? ($row['op_head_status'] ?? 'pending'));
        $opHeadNote = $appReq['op_head_note'] ?? ($row['op_head_note'] ?? null);

        if (!empty($opHeadBy) || $opHeadStatus === 'approved') {
            $addApprover('Admin & Operation Head', $opHeadBy, $opHeadAt, $opHeadNote ?: 'Verified and approved by Admin & Operation Head');
        } elseif ($isOver1Lac && !in_array(strtolower($appReq['status'] ?? ($row['status'] ?? '')), ['rejected', 'cancelled'])) {
            $audit['approvers'][] = [
                'stage' => 'Admin & Operation Head',
                'name'  => 'Pending Verification',
                'date'  => 'Awaiting Op Head decision',
                'note'  => 'Mandatory step for requests > ৳10,000'
            ];
        }

        // Fund Requisition: per-approval Management / Op Head breakdown (supports multiple partial approvals)
        $frMgmtHandled = false;
        if ($sourceTable === 'fund_requisitions' && !empty($id)) {
            require_once __DIR__ . '/../models/FundRequisitionApprovalLog.php';
            $frApprovals = [];
            try {
                $frApprovals = \FundRequisitionApprovalLog::getManagementApprovals($this->db, (int)$id, !empty($row['id']) ? $row : null);
            } catch (\Throwable $t) {
                error_log('Voucher FR approval log error: ' . $t->getMessage());
            }
            if (!empty($frApprovals)) {
                $frMgmtHandled = true;
                foreach ($frApprovals as $fa) {
                    $isPartialStep = (($fa['action'] ?? '') === 'partially_approved');
                    $faName = ($fa['name'] ?? 'Management')
                        . (!empty($fa['emp_code']) ? ' [' . $fa['emp_code'] . ']' : '')
                        . (!empty($fa['designation']) ? ' (' . $fa['designation'] . ')' : '');
                    $faStage = (($fa['stage_label'] ?? 'Management') === 'Management') ? 'Management / Authority' : $fa['stage_label'];
                    $audit['approvers'][] = [
                        'stage'        => $faStage . ($isPartialStep ? ' (Partial)' : ''),
                        'name'         => $faName,
                        'date'         => !empty($fa['created_at']) ? date('d M, Y h:i A', strtotime($fa['created_at'])) : null,
                        'note'         => $fa['note'] ?? null,
                        'amount'       => (float)($fa['amount'] ?? 0),
                        'amount_label' => $isPartialStep ? 'Partially Approved' : 'Approved',
                        'allow_multi'  => true,
                    ];
                }
                $frRemaining = max(0, (float)($row['amount'] ?? 0) - (float)($row['approved_amount'] ?? 0));
                if (strtolower($row['status'] ?? '') === 'partially_approved' && $frRemaining > 0) {
                    $audit['approvers'][] = [
                        'stage'        => 'Management / Authority',
                        'name'         => '⏳ Pending (Remaining Balance)',
                        'date'         => 'Awaiting decision',
                        'note'         => null,
                        'amount'       => $frRemaining,
                        'amount_label' => 'Remaining',
                        'allow_multi'  => true,
                    ];
                }
            }
        }

        // General / Final Management Approval
        $isRowOrReqApproved = in_array(strtolower($row['status'] ?? $appReq['status'] ?? ''), ['approved', 'paid', 'fully_paid', 'partially_paid', 'settled', 'completed']) || (!$isOver1Lac && ($opHeadStatus === 'approved' || !empty($opHeadBy)));
        $mgmtId = $row['management_apporved_by_id'] ?? $row['management_approved_by'] ?? null;
        if (empty($mgmtId) && $isRowOrReqApproved) {
            $mgmtId = $row['approved_by'] ?? ($appReq['approved_by'] ?? null);
        }
        // If mgmtId or ar.approved_by is Accounts Head (Role 30), it belongs to Accounts Head stage, NOT Management stage!
        $ahBy = $appReq['accounts_head_by'] ?? null;
        $isMgmtUserAh = false;
        $checkUserId = !empty($mgmtId) ? $mgmtId : ($appReq['approved_by'] ?? null);
        if (!empty($checkUserId)) {
            if (!empty($ahBy) && (int)$checkUserId === (int)$ahBy) {
                $mgmtId = null;
                $isMgmtUserAh = true;
            } else {
                $checkAhRole = $this->db->query("SELECT role_id, secondary_role_id FROM users WHERE id = " . (int)$checkUserId . " LIMIT 1");
                if ($checkAhRole && ($rAh = $checkAhRole->fetch_assoc())) {
                    if ((int)$rAh['role_id'] === 30 || (int)$rAh['secondary_role_id'] === 30) {
                        $mgmtId = null;
                        $isMgmtUserAh = true;
                    }
                }
            }
        }
        $mgmtDate = $row['management_apporved_at'] ?? $row['management_approved_at'] ?? null;
        if (empty($mgmtDate) && $isRowOrReqApproved && !empty($mgmtId) && !$isMgmtUserAh) {
            $mgmtDate = $row['approved_at'] ?? ($appReq['approved_at'] ?? ($row['approved_date'] ?? null));
        }
        $mgmtStage = 'Management / Authority';
        $mgmtNote = !empty($appReq['approval_note']) ? trim($appReq['approval_note']) : (!empty($row['mgmt_note']) ? trim($row['mgmt_note']) : null);
        
        if ($frMgmtHandled) {
            // Already rendered per-approval entries above
        } elseif (!empty($mgmtId) || (!empty($appReq['app_name']) && $isRowOrReqApproved && !$isMgmtUserAh)) {
            if (!$isOver1Lac && ($opHeadStatus === 'approved' || !empty($opHeadBy)) && !empty($opHeadBy) && $mgmtId == $opHeadBy) {
                // Op Head sign-off already added under 'Admin & Operation Head'; no need for duplicate card
            } elseif (!empty($appReq['app_name']) && empty($mgmtId)) {
                $mgmtName = $appReq['app_name'] . (!empty($appReq['app_des']) ? ' (' . $appReq['app_des'] . ')' : '');
                $addApprover($mgmtStage, null, $mgmtDate, $mgmtNote);
                $foundByName = false;
                foreach ($audit['approvers'] as $appr) {
                    if (strpos($appr['name'], $appReq['app_name']) !== false) {
                        $foundByName = true;
                        break;
                    }
                }
                if (!$foundByName) {
                    $audit['approvers'][] = [
                        'stage' => $mgmtStage,
                        'name'  => $mgmtName,
                        'date'  => !empty($mgmtDate) ? date('d M, Y h:i A', strtotime($mgmtDate)) : null,
                        'note'  => $mgmtNote
                    ];
                }
            } else {
                $addApprover($mgmtStage, $mgmtId, $mgmtDate, $mgmtNote);
            }
        } elseif (!$isRowOrReqApproved || $isMgmtUserAh) {
            if (!$isOver1Lac && ($opHeadStatus === 'approved' || !empty($opHeadBy))) {
                // Op Head approval is sufficient for <= 10k, no pending management authority needed
            } else {
                $ahDoneCheck = (strtolower($appReq['accounts_head_status'] ?? '') === 'approved');
                if (!$ahDoneCheck && ($opHeadStatus === 'approved' || !empty($opHeadBy))) {
                    $audit['approvers'][] = [
                        'stage' => $mgmtStage,
                        'name'  => '⏳ Awaiting Accounts Head Sign-off',
                        'date'  => 'Step 2 verification in progress',
                        'note'  => 'Accounts Head (Role 30) payment verification required before Management final approval'
                    ];
                } else {
                    $audit['approvers'][] = [
                        'stage' => $mgmtStage,
                        'name'  => '⏳ Pending Approval',
                        'date'  => 'Awaiting decision',
                        'note'  => null
                    ];
                }
            }
        }
        // Check con_approval_logs if available
        $cal = $this->db->query("SELECT step_name, actioned_by, actioned_at FROM con_approval_logs WHERE ref_table = '{$sourceTable}' AND ref_id = {$id} AND status = 'approved' ORDER BY id ASC");
        if ($cal) {
            while ($calr = $cal->fetch_assoc()) {
                $addApprover(ucwords(str_replace('_', ' ', $calr['step_name'])), $calr['actioned_by'], $calr['actioned_at']);
            }
        }
        // Extra deduplication pass: collapse multiple entries from the same person across management stages
        $deduped = [];
        $seen = [];
        foreach ($audit['approvers'] as $appr) {
            if (!empty($appr['allow_multi'])) {
                $deduped[] = $appr;
                continue;
            }
            $norm = trim(preg_replace('/\s*\[\d+\].*$/', '', $appr['name']));
            if (!empty($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $deduped[] = $appr;
        }
        $audit['approvers'] = $deduped;

        // If still empty but status is approved/paid
        if (empty($audit['approvers'])) {
            $audit['approvers'][] = [
                'stage' => 'Management / Authority',
                'name'  => 'Approved & Authorized',
                'date'  => !empty($creatorDate) ? date('d M, Y', strtotime($creatorDate)) : date('d M, Y'),
                'note'  => null
            ];
        }

        // --- 3. ACCOUNTS HEAD APPROVAL (Role 30) ---
        $ahName = 'Accounts Head';
        $ahDes = 'AGM - Finance & Accounts';
        $ahEmpId = '';
        $ahDate = null;
        $ahNote = null;
        $isAhApproved = false;

        // Lookup designated Accounts Head dynamically
        $defAhUser = $this->db->query("
            SELECT u.id, u.full_name, COALESCE(e.employee_id, u.employee_id) AS emp_code, IFNULL(d.name, 'AGM - Finance & Accounts') AS designation
            FROM users u
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN designations d ON d.id = COALESCE(e.designation_id, u.designation_id)
            WHERE (u.role_id = 30 OR u.secondary_role_id = 30) AND (e.deleted_at IS NULL OR e.id IS NULL)
            ORDER BY u.id ASC LIMIT 1
        ");
        if ($defAhUser && ($rDefAh = $defAhUser->fetch_assoc())) {
            $ahName = $rDefAh['full_name'];
            $ahEmpId = $rDefAh['emp_code'] ?? '';
            $ahDes = $rDefAh['designation'] ?? 'AGM - Finance & Accounts';
        }

        if (!empty($appReq)) {
            if (!empty($appReq['ah_name'])) {
                $ahName = $appReq['ah_name'];
                if (!empty($appReq['ah_code'])) $ahEmpId = $appReq['ah_code'];
                if (!empty($appReq['ah_des'])) $ahDes = $appReq['ah_des'];
            }
            if (!empty($appReq['accounts_head_at'])) {
                $ahDate = date('d M, Y h:i A', strtotime($appReq['accounts_head_at']));
            }
            if (!empty($appReq['accounts_head_note'])) {
                $ahNote = trim($appReq['accounts_head_note']);
            }
            if (($appReq['accounts_head_status'] ?? '') === 'approved') {
                $isAhApproved = true;
            }
        }

        // If not explicit in approval_requests, check via model helper
        if (!$isAhApproved) {
            if (!class_exists('Model')) {
                require_once __DIR__ . '/../core/Model.php';
            }
            if (!class_exists('ApprovalRequest')) {
                require_once __DIR__ . '/../models/ApprovalRequest.php';
            }
            foreach ($possibleTables as $pt) {
                $ahInfo = \ApprovalRequest::getAccountsHeadApprovalInfo($this->db, $pt, $id);
                if (!empty($ahInfo['has_request'])) {
                    if (!empty($ahInfo['is_accounts_head_approved'])) {
                        $isAhApproved = true;
                        if (!empty($ahInfo['approved_by_name'])) $ahName = $ahInfo['approved_by_name'];
                        if (!empty($ahInfo['approved_by_emp_id'])) $ahEmpId = $ahInfo['approved_by_emp_id'];
                        if (!empty($ahInfo['approved_by_designation'])) $ahDes = $ahInfo['approved_by_designation'];
                        if (!empty($ahInfo['approved_at'])) $ahDate = date('d M, Y h:i A', strtotime($ahInfo['approved_at']));
                        if (!empty($ahInfo['approval_note'])) $ahNote = trim($ahInfo['approval_note']);
                    }
                    break;
                }
            }
        }

        // Check if payment is already disbursed / settled
        $isDisbursed = !empty($row['accounts_status']) && in_array(strtolower($row['accounts_status']), ['paid', 'approved'])
            || !empty($row['status']) && in_array(strtolower($row['status']), ['paid', 'fully_paid', 'disbursed', 'completed'])
            || !empty($appReq['status']) && in_array(strtolower($appReq['status']), ['approved', 'paid']) && in_array($appReq['approve_role'] ?? '', ['completed', 'accounts']);

        if (!$isAhApproved && $isDisbursed) {
            $isAhApproved = true;
        }

        if (empty($ahDate) && $isAhApproved) {
            $fallbackDt = $appReq['accounts_head_at'] ?? ($appReq['approved_at'] ?? ($row['accounts_paid_at'] ?? ($row['mgmt_approved_at'] ?? ($row['updated_at'] ?? null))));
            if (!empty($fallbackDt)) {
                $ahDate = date('d M, Y h:i A', strtotime($fallbackDt));
            }
        }

        if (empty($ahNote)) {
            if (!empty($appReq['accounts_head_note'])) {
                $ahNote = trim($appReq['accounts_head_note']);
            } elseif (!empty($row['accounts_note']) && strpos(strtolower($row['accounts_note']), 'auto-') === false) {
                $ahNote = trim($row['accounts_note']);
            }
        }

        $audit['accounts_head'] = [
            'name'         => $ahName . (!empty($ahEmpId) ? ' [' . $ahEmpId . ']' : ''),
            'designation'  => $ahDes,
            'date'         => $ahDate,
            'note'         => $ahNote,
            'is_approved'  => $isAhApproved,
            'status_label' => $isAhApproved ? 'Approved & Authorized' : 'Pending Verification'
        ];

        // --- 4. DISBURSED / PAID BY ---
        $disbId = $row['paid_by'] ?? $row['payment_by_id'] ?? $row['disbursed_by'] ?? $row['labor_disbursed_by'] ?? $row['accounts_by'] ?? null;
        $disbDate = $row['paid_at'] ?? $row['payment_at'] ?? $row['disbursed_at'] ?? $row['labor_disbursed_at'] ?? $row['accounts_paid_at'] ?? $row['payment_date'] ?? null;
        
        if (empty($disbId)) {
            if ($sourceTable === 'cashout_requests') {
                $cp = $this->db->query("SELECT created_by, payment_date FROM cashout_payments WHERE cashout_request_id = {$id} ORDER BY id DESC LIMIT 1");
                if ($cp && $cpr = $cp->fetch_assoc()) {
                    $disbId = $cpr['created_by'];
                    $disbDate = $cpr['payment_date'];
                }
            } elseif ($sourceTable === 'con_labor_requisitions') {
                $clp = $this->db->query("SELECT paid_by, payment_date FROM con_labor_payment_logs WHERE requisition_id = {$id} ORDER BY id DESC LIMIT 1");
                if ($clp && $clpr = $clp->fetch_assoc()) {
                    $disbId = $clpr['paid_by'];
                    $disbDate = $clpr['payment_date'];
                }
            } elseif (in_array($sourceTable, ['contractor_bill_requests', 'vendor_payment_requests', 'operational_fund_requests'])) {
                $refTypeMap = [
                    'contractor_bill_requests' => 'contractor_bill',
                    'vendor_payment_requests' => 'vendor_payment',
                    'operational_fund_requests' => 'fund_request'
                ];
                $rt = $refTypeMap[$sourceTable] ?? '';
                $tx = $this->db->query("SELECT created_by, transaction_date FROM con_financial_transactions WHERE ref_type = '{$rt}' AND ref_id = {$id} ORDER BY id DESC LIMIT 1");
                if ($tx && $txr = $tx->fetch_assoc()) {
                    $disbId = $txr['created_by'];
                    $disbDate = $txr['transaction_date'];
                }
            } elseif ($sourceTable === 'project_incentive_claims') {
                $pip = $this->db->query("SELECT created_by, payment_date FROM project_incentive_payments WHERE claim_id = {$id} ORDER BY id DESC LIMIT 1");
                if ($pip && $pipr = $pip->fetch_assoc()) {
                    $disbId = $pipr['created_by'];
                    $disbDate = $pipr['payment_date'];
                }
            }
        }

        $disbName = (!empty($disbId) ? $getUser($disbId) : null);
        if (empty($disbName)) {
            $disbName = 'Accounts & Finance Department';
        }
        $audit['disburser'] = [
            'name' => $disbName,
            'date' => !empty($disbDate) ? date('d M, Y h:i A', strtotime($disbDate)) : null,
            'is_disbursed' => !empty($disbDate)
        ];

        return $audit;
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
