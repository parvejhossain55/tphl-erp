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

        // Fetch all local employee IDs, names, departments, and designations for Tipsoi matching
        $localQuery = $this->db->query("
            SELECT e.employee_id, e.full_name, dept.name AS department_name, desg.name AS designation_name 
            FROM employees AS e
            LEFT JOIN departments AS dept ON e.department_id = dept.id
            LEFT JOIN designations AS desg ON e.designation_id = desg.id
        ");
        $allLocalEmployees = [];
        if ($localQuery) {
            while ($row = $localQuery->fetch_assoc()) {
                $allLocalEmployees[] = [
                    'employee_id' => trim($row['employee_id'] ?? ''),
                    'full_name' => trim($row['full_name'] ?? ''),
                    'department_name' => trim($row['department_name'] ?? 'N/A'),
                    'designation_name' => trim($row['designation_name'] ?? 'N/A')
                ];
            }
        }

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
            "allLocalEmployees" => $allLocalEmployees,
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

        $isDraft = (!empty($payload['is_draft']) && $payload['is_draft'] == 1)
            || (!empty($_POST['is_draft']) && $_POST['is_draft'] == 1)
            || (!empty($_POST['submit_action']) && $_POST['submit_action'] === 'draft');
        $status  = $isDraft ? 'draft' : 'pending';

        $payslipId = $payslipModel->create([
            'employee_id'     => $payload['employee_id'],
            'designation_id'  => $payload['designation_id'],
            'payslip_month'   => date('Y-m-01'),
            'total_earning'   => $payload['total_earning'],
            'total_deduction' => $payload['total_deduction'],
            'net_salary'      => $payload['net_salary'],
            'status'          => $status,
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

        // CREATE APPROVAL REQUEST ONLY IF NOT DRAFT
        if (!$isDraft) {
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
        }


        echo "<script>
        localStorage.removeItem('payslip_data');
        window.location.href = '{$GLOBALS['base_url']}/payslip-lists';
    </script>";
    }

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $payslipId = (int)$id;
        if ($payslipId) {
            $this->paymentModel->delete($payslipId);
            $_SESSION['success'] = "Payslip deleted successfully.";
        }

        header("Location: " . $GLOBALS['base_url'] . "/payslip-lists");
        exit;
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

    public function showAutoGenerateForm()
    {
        $departmentModel = new Department($this->db);
        $departments = $departmentModel->getAll();
        $this->view("payrolls/payslips/auto-create-payslip", [
            "current_route" => "auto-generate-payslip",
            "page_title"    => "Auto Generate Payslips",
            "departments"   => $departments
        ]);
    }

    public function getDepartmentAttendance()
    {
        header('Content-Type: application/json');

        $departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
        $monthStr     = isset($_GET['month']) ? trim($_GET['month']) : ''; // e.g. "2026-07"

        if (!$departmentId || !$monthStr) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }

        // 1. Fetch active employees in this department
        $stmt = $this->db->prepare("
            SELECT e.id, e.full_name, e.employee_id, e.email, e.gross_salary, e.basic_salary, e.designation_id, desg.name AS designation_name
            FROM employees e
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE e.department_id = ? AND e.employee_status = 'active'
        ");
        $stmt->bind_param("i", $departmentId);
        $stmt->execute();
        $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // 2. Fetch all configured salary items for these employees
        $configuredSalaryItems = [];
        if (!empty($employees)) {
            $employeeIds = array_column($employees, 'id');
            $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
            $stmt = $this->db->prepare("
                SELECT * FROM employee_salary_items WHERE employee_id IN ($placeholders)
            ");
            $types = str_repeat('i', count($employeeIds));
            $stmt->bind_param($types, ...$employeeIds);
            $stmt->execute();
            $salaryItemsRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($salaryItemsRaw as $sir) {
                $configuredSalaryItems[$sir['employee_id']][$sir['payslip_item_id']] = $sir['amount'];
            }
        }

        // 3. Fetch attendance logs from Tipsoi
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        $fromDateStr = $monthStr . '-01';
        $toDateStr   = date('Y-m-t', strtotime($fromDateStr));

        $fromTime = strtotime($fromDateStr . ' 00:00:00') * 1000;
        $toTime   = strtotime($toDateStr . ' 23:59:59') * 1000;

        date_default_timezone_set($oldTimezone);

        $externalApiService = new ExternalApiService($this->db);
        $attendanceLogs = $externalApiService->getAttendanceLogs($fromTime, $toTime) ?? [];

        // Map Tipsoi attendance records by employee ID
        $tipsoiMap = [];
        foreach ($attendanceLogs as $log) {
            $officeId = trim($log['employeeOfficeId'] ?? $log['employeeId'] ?? '');
            if ($officeId !== '') {
                $tipsoiMap[strtolower($officeId)] = $log['attendance'] ?? [];
            }
        }

        // 4. Calculate absent days for each employee
        foreach ($employees as &$emp) {
            $empIdLower = strtolower(trim($emp['employee_id']));
            $absentCount = 0;
            $matchedInTipsoi = false;

            if (isset($tipsoiMap[$empIdLower])) {
                $matchedInTipsoi = true;
                $attMap = $tipsoiMap[$empIdLower];
                foreach ($attMap as $timestampMs => $dayAtt) {
                    $isPresent = $dayAtt['isPresent'] ?? false;
                    $isLeave   = $dayAtt['isLeave'] ?? false;
                    $isOffday  = $dayAtt['isOffday'] ?? false;
                    $isHoliday = $dayAtt['isHoliday'] ?? false;

                    if (!$isPresent && !$isLeave && !$isOffday && !$isHoliday) {
                        $absentCount++;
                    }
                }
            }

            $emp['matched_in_tipsoi'] = $matchedInTipsoi;
            $emp['absent_days']       = $absentCount;
            // Per day deduction = gross_salary / 30
            $perDaySal = $emp['gross_salary'] / 30;
            $emp['absent_deduction']  = round($absentCount * $perDaySal, 2);

            // Pre-fill configured allowances/deductions
            $emp['configured_items']  = $configuredSalaryItems[$emp['id']] ?? [];
        }

        // 5. Load active payslip items to display as table headers
        $payslipItems = $this->payslipItemModel->getAll();

        echo json_encode([
            'success'      => true,
            'employees'    => $employees,
            'payslip_items' => $payslipItems
        ]);
        exit;
    }

    public function storeBulkPayslips()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);

        if (empty($payload) || empty($payload['employees']) || empty($payload['payslip_month'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid payslip data.']);
            exit;
        }

        $payslipMonth  = $payload['payslip_month'] . '-01'; // Format as YYYY-MM-01
        $payslipDate   = !empty($payload['payslip_date']) ? $payload['payslip_date'] : date('Y-m-d');
        $generatedBy   = $_SESSION['user']['id'] ?? 1;

        $payslipModel  = new Payslip($this->db);
        $detailsModel  = new PayslipDetail($this->db);
        $approvalModel = new ApprovalRequest($this->db);

        // Begin Transaction
        $this->db->begin_transaction();

        $generatedCount = 0;
        $skippedCount = 0;
        $skippedNames = [];

        try {
            foreach ($payload['employees'] as $empData) {
                // Double check if employee has already generated payslip for this month
                $stmtCheck = $this->db->prepare("
                    SELECT id FROM payslips WHERE employee_id = ? AND DATE_FORMAT(payslip_month, '%Y-%m') = ? LIMIT 1
                ");
                $monthStr = date('Y-m', strtotime($payslipMonth));
                $stmtCheck->bind_param("is", $empData['employee_id'], $monthStr);
                $stmtCheck->execute();
                $exists = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();

                if ($exists) {
                    $skippedCount++;
                    $skippedNames[] = $empData['employee_name'];
                    continue;
                }

                // 1. Create Payslip
                $payslipId = $payslipModel->create([
                    'employee_id'     => $empData['employee_id'],
                    'designation_id'  => $empData['designation_id'],
                    'payslip_month'   => $payslipMonth,
                    'total_earning'   => $empData['total_earning'],
                    'total_deduction' => $empData['total_deduction'],
                    'net_salary'      => $empData['net_salary'],
                    'status'          => 'pending',
                    'upload_by'       => 'manual',
                    'created_by'      => $generatedBy,
                ]);

                if (!$payslipId) {
                    throw new Exception("Failed to create payslip for " . $empData['employee_name'] . ". DB Error: " . $this->db->error);
                }

                // 2. Insert Earning Details
                if (!empty($empData['earnings'])) {
                    foreach ($empData['earnings'] as $itemId => $amount) {
                        $amt = (float)$amount;
                        if ($amt > 0) {
                            $detailsModel->create($payslipId, $itemId, $amt);
                        }
                    }
                }

                // 3. Insert Deduction Details
                if (!empty($empData['deductions'])) {
                    foreach ($empData['deductions'] as $itemId => $amount) {
                        $amt = (float)$amount;
                        if ($amt > 0) {
                            $detailsModel->create($payslipId, $itemId, $amt);
                        }
                    }
                }

                // 4. Create Approval Request
                $approvalModel->create([
                    'department'      => 'HR',
                    'request_type'    => 'salary',
                    'title'           => 'Salary Approval',
                    'source_table'    => 'payslips',
                    'source_id'       => $payslipId,
                    'requested_days'  => 0,
                    'requested_by'    => $generatedBy,
                    'approve_role'    => 'management',
                    'meta_data'       => json_encode([
                        'employee_name' => $empData['employee_name'],
                        'net_salary'    => $empData['net_salary'],
                        'month'         => $payslipMonth
                    ]),
                    'request_amount'  => $empData['net_salary']
                ]);

                $generatedCount++;
            }

            // Commit Transaction
            $this->db->commit();

            if ($generatedCount === 0 && $skippedCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No payslips were generated. All selected employees already have payslips generated for this month: ' . implode(', ', $skippedNames)
                ]);
            } else {
                $msg = "Generated $generatedCount payslips successfully and submitted for approval.";
                if ($skippedCount > 0) {
                    $msg .= " Skipped $skippedCount employees who already had payslips for this month: " . implode(', ', $skippedNames);
                }
                echo json_encode(['success' => true, 'message' => $msg]);
            }
            exit;

        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to generate payslips: ' . $e->getMessage()]);
            exit;
        }
    }
}
