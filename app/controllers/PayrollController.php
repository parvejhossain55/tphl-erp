<?php

class PayrollController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $payslipItemModel;
    protected $paymentModel;
    protected $bankModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->payslipItemModel = new PayslipItem($this->db);
        $this->paymentModel = new Payslip($this->db);
        $this->bankModel = new CompanyBankAccount($this->db);
    }

    public function index()
    {
        // Current month/year
        $currentYear  = date('Y');
        $currentMonth = date('m');

        // GET param use, fallback to current month/year
        $selectedYear   = isset($_GET['year']) ? $_GET['year'] : $currentYear;
        $selectedMonth  = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
        $selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

        // Fetch payslips with status filter
        $payslips = $this->paymentModel->getAllWithEmployeeFiltered($selectedYear, $selectedMonth, $selectedStatus);

        $totalEmployees = $this->employeeModel->getTotalCount();
        $paidCount      = $this->paymentModel->getPaidCount($selectedYear, $selectedMonth);
        $pendingCount   = $totalEmployees - $paidCount;
        $paidSalarySum  = $this->paymentModel->getPaidSalarySum($selectedYear, $selectedMonth);
        $unpaidSalarySum = $this->paymentModel->getUnpaidSalarySum($selectedYear, $selectedMonth);
        $companyBanks   = $this->bankModel->getAll();

        $this->view("payrolls/payslips/payslip-lists", [
            "current_route"  => "payslip-lists",
            "page_title"     => "Payroll Lists",
            "payslips"       => $payslips,
            "totalEmployees" => $totalEmployees,
            "paidCount"      => $paidCount,
            "pendingCount"   => $pendingCount,
            "paidSalarySum"  => $paidSalarySum,
            "unpaidSalarySum" => $unpaidSalarySum,
            "companyBanks"   => $companyBanks,
            "selectedYear"   => $selectedYear,
            "selectedMonth"  => $selectedMonth,
            "selectedStatus" => $selectedStatus,
        ]);
    }

    public function create()
    {
        $employees = $this->employeeModel->getAllWithDesignation();
        $items = $this->payslipItemModel->getAll();
        $this->view("payrolls/payslips/create-payslip", [
            "current_route" => "create-payslip",
            "page_title"    => "Create Payslip",
            "employees"     => $employees,
            "items"         => $items,

        ]);
    }
   


    public function store()
    {
        if (empty($_POST['payslip_payload'])) {
            die('Invalid Payslip Data');
        }

        $payload = json_decode($_POST['payslip_payload'], true);

        $payslipModel = new Payslip($this->db);
        $detailsModel = new PayslipDetail($this->db);
        $approvalModel = new ApprovalRequest($this->db);

        $payslipId = $payslipModel->create([
            'employee_id'     => $payload['employee_id'],
            'designation_id'  => $payload['designation_id'],
            'payslip_month'   => date('Y-m-01'),
            'total_earning'   => $payload['total_earning'],
            'total_deduction' => $payload['total_deduction'],
            'net_salary'      => $payload['net_salary'],
            'status'          => 'pending',
            'upload_by'       => 'manual',
            'created_by'      => $_SESSION['user']['id'],
        ]);

        foreach ($payload['earnings'] as $itemId => $amount) {
            if ($amount > 0) {
                $detailsModel->create($payslipId, $itemId, $amount);
            }
        }

        foreach ($payload['deductions'] as $itemId => $amount) {
            if ($amount > 0) {
                $detailsModel->create($payslipId, $itemId, $amount);
            }
        }

        //  CREATE APPROVAL REQUEST
        $approvalModel->create([
            'department'      => 'HR',
            'request_type'    => 'salary',
            'title'           => 'Salary Approval',
            'source_table'    => 'payslips',
            'source_id'       => $payslipId,
            'request_amount'  => $payload['net_salary'],
            'requested_by'    => $_SESSION['user']['id'],
            'approve_role'    => 'management',
            'meta_data'       => json_encode([
                'employee_id' => $payload['employee_id'],
                'month' => date('d F Y')
            ])
        ]);


        echo "<script>
        localStorage.removeItem('payslip_data');
        window.location.href = '{$GLOBALS['base_url']}/payslip-lists';
    </script>";
    }



    public function updatePay()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $payslipId = $_POST['payslip_id'] ?? null;
        $paymentMethod = $_POST['payment_method'] ?? null;
        $companyBankId = $_POST['company_bank_id'] ?? null;

        if (!$payslipId || !$paymentMethod) {
            $_SESSION['error'] = "Payslip ID or payment method missing.";
            header("Location: " . $GLOBALS['base_url'] . "/payslip-lists");
            exit;
        }

        $companyBankId = $companyBankId ?: null; // nullable

        $success = $this->paymentModel->payPayslip($payslipId, $paymentMethod, $companyBankId);

        if ($success) {
            $_SESSION['success'] = "Payslip paid successfully.";
        } else {
            $_SESSION['error'] = "Failed to update payslip. Please try again.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/payslip-lists");
        exit;
    }


    public function show($id)
    {

        $payslip = $this->paymentModel->getByIdWithItems($id);

        $this->view("payrolls/payslips/show-payslip", [

            "current_route" => "show-payslip",
            "page_title"    => "Payslip Details",
            "payslip"       => $payslip
        ], false);
    }

    public function dueEmployeePayslip()
    {
        header('Content-Type: application/json');

        $month = $_GET['month'] ?? null;
        if (!$month) {
            echo json_encode([]);
            exit;
        }

        $dueEmployees = $this->paymentModel->getDueEmployeePayslip($month);
        echo json_encode($dueEmployees);
        exit;
    }

    public function showImportForm()
    {
        $this->view("payrolls/payslips/import-payslips", [
            "current_route" => "payslip-lists",
            "page_title"    => "Import Payslips"
        ]);
    }

    public function importCSV()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = "Please select a valid CSV file.";
            header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            if (!$header) {
                $_SESSION['error'] = "Empty CSV file.";
                header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
                exit;
            }

            // Clean header column names (trim and remove BOM)
            $header = array_map(function($col) {
                return trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $col));
            }, $header);

            // Required standard columns
            $required = ['employee_email', 'payslip_month', 'payment_method', 'company_bank_name', 'total_earning', 'total_deduction', 'net_salary'];
            
            // Validate headers
            foreach ($required as $req) {
                if (!in_array($req, $header)) {
                    $_SESSION['error'] = "Missing required column in CSV: " . $req;
                    header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
                    exit;
                }
            }

            // Find dynamic item columns
            $dynamicCols = [];
            foreach ($header as $idx => $colName) {
                if (!in_array($colName, $required)) {
                    if (preg_match('/^(.*?)\s*\((earning|deduction)\)$/i', $colName, $matches)) {
                        $itemName = trim($matches[1]);
                        $itemType = strtolower($matches[2]);
                        $dynamicCols[$idx] = [
                            'name' => $itemName,
                            'type' => $itemType
                        ];
                    } else {
                        $dynamicCols[$idx] = [
                            'name' => $colName,
                            'type' => 'earning'
                        ];
                    }
                }
            }

            // Begin DB Transaction
            $this->db->begin_transaction();

            $payslipModel = new Payslip($this->db);
            $detailsModel = new PayslipDetail($this->db);

            // Pre-process payslip items to get/create their ids
            $itemIds = [];
            foreach ($dynamicCols as $idx => $info) {
                $stmt = $this->db->prepare("SELECT id FROM payslip_items WHERE name = ? LIMIT 1");
                $stmt->bind_param("s", $info['name']);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($res) {
                    $itemIds[$idx] = $res['id'];
                } else {
                    $stmtCreate = $this->db->prepare("INSERT INTO payslip_items (name, type, created_by, created_at) VALUES (?, ?, ?, NOW())");
                    $createdBy = $_SESSION['user']['id'] ?? 1;
                    $stmtCreate->bind_param("ssi", $info['name'], $info['type'], $createdBy);
                    $stmtCreate->execute();
                    $itemIds[$idx] = $this->db->insert_id;
                    $stmtCreate->close();
                }
            }

            $rowNum = 1;
            $errors = [];

            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNum++;

                if (empty(array_filter($row))) {
                    continue;
                }

                // Map row to header
                $data = [];
                foreach ($header as $i => $col) {
                    $val = isset($row[$i]) ? trim($row[$i]) : '';
                    // Clean value from hidden characters/BOM
                    $val = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $val);
                    $data[$col] = $val;
                }

                $email = $data['employee_email'];
                $month = $data['payslip_month'];
                $paymentMethod = strtolower($data['payment_method']);
                $companyBankName = $data['company_bank_name'];
                $totalEarning = (float)$data['total_earning'];
                $totalDeduction = (float)$data['total_deduction'];
                $netSalary = (float)$data['net_salary'];

                // Parse and format payslip_month to YYYY-MM-01
                $monthCleaned = trim($month);
                if (preg_match('/^(\d{1,2})[-\/](\d{4})$/', $monthCleaned, $matches)) {
                    $monthCleaned = $matches[2] . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT) . '-01';
                } else {
                    $monthCleaned = str_replace('/', '-', $monthCleaned);
                }

                $time = strtotime($monthCleaned);
                if ($time) {
                    $formattedMonth = date('Y-m-01', $time);
                } else {
                    $errors[] = "Row {$rowNum}: Invalid date format for payslip_month: '{$month}'. Please use YYYY-MM-DD (e.g. 2026-06-01).";
                    continue;
                }

                // 1. Match Employee by email in employees table
                $stmt = $this->db->prepare("SELECT id, designation_id FROM employees WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $emp = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$emp) {
                    $errors[] = "Row {$rowNum}: Employee not found with email '{$email}'.";
                    continue; 
                }

                // 2. Match Bank if payment method is bank
                $companyBankId = null;
                if ($paymentMethod === 'bank') {
                    if (empty($companyBankName)) {
                        $errors[] = "Row {$rowNum}: Company bank name is required when payment method is 'bank'.";
                        continue;
                    }

                    $bankSearch = "%" . $companyBankName . "%";
                    $stmt = $this->db->prepare("SELECT id FROM company_bank_accounts WHERE bank_name LIKE ? AND status = 'active' LIMIT 1");
                    $stmt->bind_param("s", $bankSearch);
                    $stmt->execute();
                    $bank = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$bank) {
                        $errors[] = "Row {$rowNum}: Active company bank account not found matching name '{$companyBankName}'.";
                        continue;
                    }
                    $companyBankId = $bank['id'];
                }

                if (!empty($errors)) {
                    continue;
                }

                // 3. Create Payslip (status 'paid')
                $payslipData = [
                    'employee_id'     => $emp['id'],
                    'designation_id'  => $emp['designation_id'],
                    'payslip_month'   => $formattedMonth,
                    'total_earning'   => $totalEarning,
                    'total_deduction' => $totalDeduction,
                    'net_salary'      => $netSalary,
                    'status'          => 'paid',
                    'payment_method'  => $paymentMethod,
                    'company_bank_id' => $companyBankId,
                    'upload_by'       => 'csv',
                    'created_by'      => $_SESSION['user']['id'] ?? 1,
                ];

                try {
                    $payslipId = $payslipModel->create($payslipData);
                    
                    // 4. Save payslip details
                    foreach ($dynamicCols as $idx => $info) {
                        $amount = isset($row[$idx]) ? (float)$row[$idx] : 0.0;
                        if ($amount > 0) {
                            $itemId = $itemIds[$idx];
                            $detailsModel->create($payslipId, $itemId, $amount);
                        }
                    }
                } catch (\Throwable $th) {
                    $errors[] = "Row {$rowNum}: Database insertion failed. Reason: " . $th->getMessage();
                }
            }

            fclose($handle);

            if (!empty($errors)) {
                $this->db->rollback();
                $_SESSION['error'] = implode("<br>", $errors);
                header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
                exit;
            } else {
                $this->db->commit();
                $_SESSION['success'] = "All payslips imported and paid successfully!";
                header("Location: " . $GLOBALS['base_url'] . "/payslip-lists");
                exit;
            }
        } else {
            $_SESSION['error'] = "Failed to open CSV file.";
            header("Location: " . $GLOBALS['base_url'] . "/import-payslips");
            exit;
        }
    }

    public function downloadSampleCSV()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sample_payslips.csv');
        
        $output = fopen('php://output', 'w');

        // Load active payslip items to include them as headers
        $items = $this->payslipItemModel->getAll();
        
        $headers = [
            'employee_email',
            'payslip_month',
            'payment_method',
            'company_bank_name',
            'total_earning',
            'total_deduction',
            'net_salary'
        ];

        foreach ($items as $item) {
            $headers[] = $item['name'] . " (" . $item['type'] . ")";
        }

        fputcsv($output, $headers);

        // Add a sample row
        $sampleRow = [
            'fahim@gmail.com',
            date('Y-m-01'),
            'bank',
            'City Bank',
            '25000.00',
            '1000.00',
            '24000.00'
        ];

        // Fill dynamic columns with sample values matching headers
        foreach ($items as $item) {
            if ($item['type'] === 'earning') {
                $sampleRow[] = '25000.00';
            } else {
                $sampleRow[] = '1000.00';
            }
        }

        fputcsv($output, $sampleRow);
        fclose($output);
        exit;
    }
}
