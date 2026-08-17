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
        $currentYear = date('Y');
        $currentMonth = date('m');

        // GET param use, fallback to current month/year
        $selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
        $selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

        // Fetch payslips with status filter
        $payslips = $this->paymentModel->getAllWithEmployeeFiltered($selectedYear, $selectedMonth, $selectedStatus);

        $totalEmployees = $this->employeeModel->getTotalCount();
        $paidCount = $this->paymentModel->getPaidCount($selectedYear, $selectedMonth);
        $pendingCount = $totalEmployees - $paidCount;
        $paidSalarySum = $this->paymentModel->getPaidSalarySum($selectedYear, $selectedMonth);
        $unpaidSalarySum = $this->paymentModel->getUnpaidSalarySum($selectedYear, $selectedMonth);
        $companyBanks = $this->bankModel->getAll();

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
            "current_route" => "payslip-lists",
            "page_title" => "Payroll Lists",
            "payslips" => $payslips,
            "totalEmployees" => $totalEmployees,
            "paidCount" => $paidCount,
            "pendingCount" => $pendingCount,
            "paidSalarySum" => $paidSalarySum,
            "unpaidSalarySum" => $unpaidSalarySum,
            "companyBanks" => $companyBanks,
            "selectedYear" => $selectedYear,
            "selectedMonth" => $selectedMonth,
            "selectedStatus" => $selectedStatus,
            "allLocalEmployees" => $allLocalEmployees,
        ]);
    }

    public function create()
    {
        $employees = $this->employeeModel->getAllWithDesignation();
        $items = $this->payslipItemModel->getAll();

        if (!class_exists('AdvanceSalary')) {
            require_once __DIR__ . '/../models/AdvanceSalary.php';
        }
        $advanceSalaryModel = new AdvanceSalary($this->db);
        foreach ($employees as &$emp) {
            $emp['advance_salary'] = $advanceSalaryModel->getUnadjustedPaidAdvance($emp['id']);
        }
        unset($emp);

        $this->view("payrolls/payslips/create-payslip", [
            "current_route" => "create-payslip",
            "page_title" => "Create Payslip",
            "employees" => $employees,
            "items" => $items,

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

        $status = 'pending';
        if (!empty($_POST['submit_action']) && $_POST['submit_action'] === 'on_hold') {
            $status = 'on_hold';
        } elseif ($isDraft) {
            $status = 'draft';
        }

        $payslipId = $payslipModel->create([
            'employee_id' => $payload['employee_id'],
            'designation_id' => $payload['designation_id'],
            'payslip_month' => date('Y-m-01'),
            'total_earning' => $payload['total_earning'],
            'total_deduction' => $payload['total_deduction'],
            'net_salary' => $payload['net_salary'],
            'status' => $status,
            'upload_by' => 'manual',
            'created_by' => $_SESSION['user']['id'],
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

        // CREATE APPROVAL REQUEST ONLY IF NOT DRAFT OR ON_HOLD
        if ($status !== 'draft' && $status !== 'on_hold') {
            $approvalModel->create([
                'department' => 'HR',
                'request_type' => 'salary',
                'title' => 'Salary Approval',
                'source_table' => 'payslips',
                'source_id' => $payslipId,
                'request_amount' => $payload['net_salary'],
                'requested_by' => $_SESSION['user']['id'],
                'approve_role' => 'management',
                'meta_data' => json_encode([
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

        $payslipId = (int) $id;
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
            "page_title" => "Payslip Details",
            "payslip" => $payslip
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
            "page_title" => "Import Payslips"
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
            $header = array_map(function ($col) {
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
                $totalEarning = (float) $data['total_earning'];
                $totalDeduction = (float) $data['total_deduction'];
                $netSalary = (float) $data['net_salary'];

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
                    'employee_id' => $emp['id'],
                    'designation_id' => $emp['designation_id'],
                    'payslip_month' => $formattedMonth,
                    'total_earning' => $totalEarning,
                    'total_deduction' => $totalDeduction,
                    'net_salary' => $netSalary,
                    'status' => 'paid',
                    'payment_method' => $paymentMethod,
                    'company_bank_id' => $companyBankId,
                    'upload_by' => 'csv',
                    'created_by' => $_SESSION['user']['id'] ?? 1,
                ];

                try {
                    $payslipId = $payslipModel->create($payslipData);

                    // 4. Save payslip details
                    foreach ($dynamicCols as $idx => $info) {
                        $amount = isset($row[$idx]) ? (float) $row[$idx] : 0.0;
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
            "page_title" => "Auto Generate Payslips",
            "departments" => $departments
        ]);
    }

    public function getDepartmentAttendance()
    {
        header('Content-Type: application/json');

        $departmentId = isset($_GET['department_id']) ? (int) $_GET['department_id'] : 0;
        $monthStr = isset($_GET['month']) ? trim($_GET['month']) : ''; // e.g. "2026-07"

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
        $toDateStr = date('Y-m-t', strtotime($fromDateStr));

        $fromTime = strtotime($fromDateStr . ' 00:00:00') * 1000;
        $toTime = strtotime($toDateStr . ' 23:59:59') * 1000;

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
                    $isLeave = $dayAtt['isLeave'] ?? false;
                    $isOffday = $dayAtt['isOffday'] ?? false;
                    $isHoliday = $dayAtt['isHoliday'] ?? false;

                    if (!$isPresent && !$isLeave && !$isOffday && !$isHoliday) {
                        $absentCount++;
                    }
                }
            }

            $emp['matched_in_tipsoi'] = $matchedInTipsoi;
            $emp['absent_days'] = $absentCount;
            // Per day deduction = gross_salary / 30
            $perDaySal = $emp['gross_salary'] / 30;
            $emp['absent_deduction'] = round($absentCount * $perDaySal, 2);

            // Pre-fill configured allowances/deductions
            $emp['configured_items'] = $configuredSalaryItems[$emp['id']] ?? [];
        }

        // Advance Salary Integration
        if (!class_exists('AdvanceSalary')) {
            require_once __DIR__ . '/../models/AdvanceSalary.php';
        }
        $advanceSalaryModel = new AdvanceSalary($this->db);
        $allPayslipItems = $this->payslipItemModel->getAll();

        $advanceSalaryItemId = null;
        foreach ($allPayslipItems as $item) {
            if (strtolower(trim($item['name'])) === 'advance salary' && strtolower($item['type']) === 'deduction') {
                $advanceSalaryItemId = $item['id'];
                break;
            }
        }

        if (!$advanceSalaryItemId) {
            $this->payslipItemModel->create(['name' => 'Advance Salary', 'type' => 'deduction']);
            $allPayslipItems = $this->payslipItemModel->getAll();
            foreach ($allPayslipItems as $item) {
                if (strtolower(trim($item['name'])) === 'advance salary' && strtolower($item['type']) === 'deduction') {
                    $advanceSalaryItemId = $item['id'];
                    break;
                }
            }
        }

        foreach ($employees as &$emp) {
            $unadjustedAdvance = $advanceSalaryModel->getUnadjustedPaidAdvance($emp['id']);
            if ($unadjustedAdvance > 0 && $advanceSalaryItemId) {
                $emp['configured_items'][$advanceSalaryItemId] = $unadjustedAdvance;
            }
        }

        // 5. Load active payslip items to display as table headers
        $payslipItems = $allPayslipItems;

        echo json_encode([
            'success' => true,
            'employees' => $employees,
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

        $payslipMonth = $payload['payslip_month'] . '-01'; // Format as YYYY-MM-01
        $payslipDate = !empty($payload['payslip_date']) ? $payload['payslip_date'] : date('Y-m-d');
        $generatedBy = $_SESSION['user']['id'] ?? 1;

        $payslipModel = new Payslip($this->db);
        $detailsModel = new PayslipDetail($this->db);
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
                    'employee_id' => $empData['employee_id'],
                    'designation_id' => $empData['designation_id'],
                    'payslip_month' => $payslipMonth,
                    'total_earning' => $empData['total_earning'],
                    'total_deduction' => $empData['total_deduction'],
                    'net_salary' => $empData['net_salary'],
                    'status' => 'pending',
                    'upload_by' => 'manual',
                    'created_by' => $generatedBy,
                ]);

                if (!$payslipId) {
                    throw new Exception("Failed to create payslip for " . $empData['employee_name'] . ". DB Error: " . $this->db->error);
                }

                // 2. Insert Earning Details
                if (!empty($empData['earnings'])) {
                    foreach ($empData['earnings'] as $itemId => $amount) {
                        $amt = (float) $amount;
                        if ($amt > 0) {
                            $detailsModel->create($payslipId, $itemId, $amt);
                        }
                    }
                }

                // 3. Insert Deduction Details
                if (!empty($empData['deductions'])) {
                    foreach ($empData['deductions'] as $itemId => $amount) {
                        $amt = (float) $amount;
                        if ($amt > 0) {
                            $detailsModel->create($payslipId, $itemId, $amt);

                            // Mark Advance Salary as deducted
                            $stmtItem = $this->db->prepare("SELECT name FROM payslip_items WHERE id = ?");
                            $stmtItem->bind_param("i", $itemId);
                            $stmtItem->execute();
                            $itemRow = $stmtItem->get_result()->fetch_assoc();
                            $stmtItem->close();

                            if ($itemRow && strtolower(trim($itemRow['name'])) === 'advance salary') {
                                if (!class_exists('AdvanceSalary')) {
                                    require_once __DIR__ . '/../models/AdvanceSalary.php';
                                }
                                $advanceSalaryModel = new AdvanceSalary($this->db);
                                $advanceRequests = $advanceSalaryModel->getPaidAdvancesByEmployee($empData['employee_id']);
                                $remainingDeduction = $amt;
                                foreach ($advanceRequests as $advReq) {
                                    if ($remainingDeduction >= $advReq['amount']) {
                                        $advanceSalaryModel->markAsDeducted($advReq['id'], $payslipId);
                                        $remainingDeduction -= $advReq['amount'];
                                    } elseif ($remainingDeduction > 0) {
                                        $advanceSalaryModel->markAsDeducted($advReq['id'], $payslipId);
                                        $remainingDeduction = 0;
                                    }
                                }
                            }
                        }
                    }
                }

                // 4. Create Approval Request
                $approvalModel->create([
                    'department' => 'HR',
                    'request_type' => 'salary',
                    'title' => 'Salary Approval',
                    'source_table' => 'payslips',
                    'source_id' => $payslipId,
                    'requested_days' => 0,
                    'requested_by' => $generatedBy,
                    'approve_role' => 'management',
                    'meta_data' => json_encode([
                        'employee_name' => $empData['employee_name'],
                        'net_salary' => $empData['net_salary'],
                        'month' => $payslipMonth
                    ]),
                    'request_amount' => $empData['net_salary']
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

    public function disburseBatch()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);

        if (empty($payload) || empty($payload['payslip_ids']) || !is_array($payload['payslip_ids'])) {
            echo json_encode(['success' => false, 'message' => 'No payslips selected for disbursement.']);
            exit;
        }

        $bankAccountId = (int) ($payload['bank_account_id'] ?? 0);
        $payslipIds = array_map('intval', $payload['payslip_ids']);
        $userId = $_SESSION['user']['id'] ?? 1;

        if (empty($payslipIds)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payslips selected.']);
            exit;
        }

        $this->db->begin_transaction();

        try {
            // Fetch payslips to disburse
            $placeholders = implode(',', array_fill(0, count($payslipIds), '?'));
            $stmt = $this->db->prepare("
                SELECT p.*, e.full_name as employee_name
                FROM payslips p
                LEFT JOIN employees e ON p.employee_id = e.id
                WHERE p.id IN ($placeholders) AND (p.status != 'paid' OR p.payment_status != 'paid')
            ");
            $types = str_repeat('i', count($payslipIds));
            $stmt->bind_param($types, ...$payslipIds);
            $stmt->execute();
            $payslipsToPay = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($payslipsToPay)) {
                echo json_encode(['success' => false, 'message' => 'Selected payslips are already paid or invalid.']);
                exit;
            }

            // Fetch dynamic COA IDs for Salaries & Wages and Bank/Cash
            $stmtCoa1 = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = '580003' LIMIT 1");
            $stmtCoa1->execute();
            $salaryCoaRow = $stmtCoa1->get_result()->fetch_assoc();
            $salaryCoaId = $salaryCoaRow ? (int) $salaryCoaRow['id'] : 1;
            $stmtCoa1->close();

            $stmtCoa2 = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = '114400' LIMIT 1");
            $stmtCoa2->execute();
            $bankCoaRow = $stmtCoa2->get_result()->fetch_assoc();
            $bankCoaId = $bankCoaRow ? (int) $bankCoaRow['id'] : 1;
            $stmtCoa2->close();

            $stmtCoa3 = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = '114100-03' LIMIT 1");
            $stmtCoa3->execute();
            $cashCoaRow = $stmtCoa3->get_result()->fetch_assoc();
            $cashCoaId = $cashCoaRow ? (int) $cashCoaRow['id'] : $bankCoaId;
            $stmtCoa3->close();

            // Get bank details or default cash
            $bankName = 'Head Office Cash Account';
            $payCoaId = $cashCoaId;
            if ($bankAccountId > 0) {
                $stmtBank = $this->db->prepare("SELECT bank_name, account_name, account_number FROM company_bank_accounts WHERE id = ?");
                $stmtBank->bind_param("i", $bankAccountId);
                $stmtBank->execute();
                $bankRes = $stmtBank->get_result()->fetch_assoc();
                $stmtBank->close();
                if ($bankRes) {
                    $bankName = $bankRes['bank_name'] . ' (' . $bankRes['account_number'] . ')';
                    $payCoaId = $bankCoaId;
                }
            }

            // Calculate total net salary sum & determine month
            $totalAmount = 0;
            $monthText = date('F Y');
            $sampleDate = date('Y-m-d');
            foreach ($payslipsToPay as $p) {
                $totalAmount += (float) $p['net_salary'];
                if (!empty($p['payslip_month'])) {
                    $monthText = date('F Y', strtotime($p['payslip_month']));
                    $sampleDate = date('Y-m-t', strtotime($p['payslip_month']));
                }
            }

            // 1. Create 1 Batch Journal Entry in 'journals'
            $journalNum = 'JRN-PAYROLL-' . date('Ym', strtotime($sampleDate)) . '-' . rand(100, 999);
            $narration = "Payroll Disbursement for $monthText via $bankName (Count: " . count($payslipsToPay) . " payslips)";

            $stmtJ = $this->db->prepare("
                INSERT INTO journals 
                (journal_number, date, voucher_type, narration, total_debit, total_credit, status, created_by, created_at, source_module, source_ref_id, entry_type, approval_status)
                VALUES (?, ?, ?, ?, ?, ?, 'posted', ?, NOW(), 'payroll', 0, 'disbursement', 'approved')
            ");
            $voucherType = 'Payment';
            $stmtJ->bind_param("ssssddi", $journalNum, $sampleDate, $voucherType, $narration, $totalAmount, $totalAmount, $userId);
            $stmtJ->execute();
            $journalId = $stmtJ->insert_id;
            $stmtJ->close();

            // 2. Insert Debit & Credit details in 'journal_details'
            // Debit: Salaries & Wages (580003)
            $stmtD1 = $this->db->prepare("
                INSERT INTO journal_details (journal_id, account_id, debit, credit, narration)
                VALUES (?, ?, ?, 0.00, ?)
            ");
            $narrDebit = "Salaries & Wages Expense for $monthText";
            $stmtD1->bind_param("iids", $journalId, $salaryCoaId, $totalAmount, $narrDebit);
            $stmtD1->execute();
            $stmtD1->close();

            // Credit: Cash at Bank (114400) or Head Office Cash (114100-03)
            $stmtD2 = $this->db->prepare("
                INSERT INTO journal_details (journal_id, account_id, debit, credit, narration)
                VALUES (?, ?, 0.00, ?, ?)
            ");
            $narrCredit = "Disbursed via $bankName for $monthText Payroll";
            $stmtD2->bind_param("iids", $journalId, $payCoaId, $totalAmount, $narrCredit);
            $stmtD2->execute();
            $stmtD2->close();

            // 3. Update payslips status to PAID & link journal_voucher_id
            $stmtUpd = $this->db->prepare("
                UPDATE payslips 
                SET status = 'paid', payment_status = 'paid', hr_status = 'approved', mgmt_status = 'approved',
                    bank_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_voucher_id = ?
                WHERE id = ?
            ");
            foreach ($payslipsToPay as $p) {
                $stmtUpd->bind_param("iiii", $bankAccountId, $userId, $journalId, $p['id']);
                $stmtUpd->execute();
            }
            $stmtUpd->close();

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Successfully disbursed ' . count($payslipsToPay) . ' payslips (Total: ৳' . number_format($totalAmount, 2) . ') and posted Journal Entry (' . $journalNum . ').',
                'journal_number' => $journalNum,
                'disbursed_count' => count($payslipsToPay),
                'total_amount' => $totalAmount
            ]);
            exit;

        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Disbursement failed: ' . $e->getMessage()]);
            exit;
        }
    }

    public function workspace()
    {
        // Current month/year
        $currentYear = date('Y');
        $currentMonth = date('m');

        // GET param use, fallback to current month/year
        $selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
        $selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;

        // Fetch dynamic payslip items
        $items = $this->payslipItemModel->getAll(); // returns id, name, type etc.

        // Build an exhaustive query joining payslips, employees, departments, designations
        $query = "
            SELECT p.*, 
                   e.full_name, e.employee_id as emp_id, e.email, e.workplace, 
                   e.joining_date, e.account_holder_name, e.account_number, e.bank_name,
                   e.gross_salary as emp_gross, e.basic_salary as emp_basic,
                   e.deleted_at,
                   e.per_meal_amount,
                   dept.name as department_name, desg.name as designation_name
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE YEAR(p.payslip_month) = '$selectedYear' AND MONTH(p.payslip_month) = '$selectedMonth'
            ORDER BY e.employee_id ASC
        ";

        $res = $this->db->query($query);
        $payslips = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                // Fetch dynamic details for this payslip
                $pid = (int) $row['id'];
                $detQuery = "
                    SELECT payslip_item_id, amount 
                    FROM payslip_details 
                    WHERE payslip_id = $pid
                ";
                $detRes = $this->db->query($detQuery);
                $details = [];
                if ($detRes) {
                    while ($drow = $detRes->fetch_assoc()) {
                        $details[$drow['payslip_item_id']] = (float) $drow['amount'];
                    }
                }
                $row['dynamic_details'] = $details;
                $payslips[] = $row;
            }
        }

        $this->view("payrolls/payslips/workspace", [
            "current_route" => "payroll-workspace",
            "page_title" => "Payroll Workplace",
            "payslips" => $payslips,
            "items" => $items,
            "selectedYear" => $selectedYear,
            "selectedMonth" => $selectedMonth
        ]);
    }

    public function salaryHold()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedYear = (!empty($_GET['year'])) ? $_GET['year'] : $currentYear;
        $selectedMonth = (!empty($_GET['month'])) ? $_GET['month'] : $currentMonth;

        $query = "
            SELECT p.id, p.payslip_month, p.net_salary, p.status,
                   e.full_name, e.employee_id as emp_id
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            WHERE YEAR(p.payslip_month) = '$selectedYear' 
              AND MONTH(p.payslip_month) = '$selectedMonth'
              AND p.status = 'on_hold'
            ORDER BY e.employee_id ASC
        ";

        $res = $this->db->query($query);
        $holds = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $holds[] = $row;
            }
        }

        $this->view("payrolls/payslips/salary-hold", [
            "current_route" => "payroll-salary-hold",
            "page_title" => "Salary Hold",
            "holds" => $holds,
            "selectedYear" => $selectedYear,
            "selectedMonth" => $selectedMonth
        ]);
    }

    public function exportSalaryHoldPDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedYear = (!empty($_GET['year'])) ? $_GET['year'] : $currentYear;
        $selectedMonth = (!empty($_GET['month'])) ? $_GET['month'] : $currentMonth;

        $query = "
            SELECT p.id, p.payslip_month, p.net_salary, p.status,
                   e.full_name, e.employee_id as emp_id
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            WHERE YEAR(p.payslip_month) = '$selectedYear' 
              AND MONTH(p.payslip_month) = '$selectedMonth'
              AND p.status = 'on_hold'
            ORDER BY e.employee_id ASC
        ";

        $res = $this->db->query($query);
        $holds = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $holds[] = $row;
            }
        }

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Salary Hold Report');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 10);

        $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Salary Hold Report (' . $monthName . ' ' . $selectedYear . ')</h3>
            <p><b>Total Held: </b> ' . count($holds) . '</p>
        </div>
        <br>
        <table border="1" cellpadding="5" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; text-align:center; font-weight:bold;">
                    <th>Serial</th>
                    <th>Name</th>
                    <th>Employee ID</th>
                    <th>Amount</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        if (empty($holds)) {
            $html .= '<tr><td colspan="5" style="text-align:center;"><b>No salary holds found for this period.</b></td></tr>';
        }
        foreach ($holds as $hold) {
            $html .= '<tr style="text-align:center;">
                <td>' . $sl++ . '</td>
                <td>' . htmlspecialchars($hold['full_name'] ?? '') . '</td>
                <td>' . htmlspecialchars($hold['emp_id'] ?? '') . '</td>
                <td>' . number_format($hold['net_salary'] ?? 0, 2) . '</td>
                <td>' . htmlspecialchars($hold['remarks'] ?? '') . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        ob_end_clean();
        $pdf->Output('Salary_Hold_Report_' . $monthName . '_' . $selectedYear . '.pdf', 'D');
        exit;
    }

    public function exportWorkspacePDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');

        $selectedYear = (!empty($_GET['year'])) ? $_GET['year'] : $currentYear;
        $selectedMonth = (!empty($_GET['month'])) ? $_GET['month'] : $currentMonth;

        $items = $this->payslipItemModel->getAll();

        $query = "
            SELECT p.*, 
                   e.full_name, e.employee_id as emp_id, e.email, e.workplace, 
                   e.joining_date, e.account_holder_name, e.account_number, e.bank_name,
                   e.gross_salary as emp_gross, e.basic_salary as emp_basic,
                   e.deleted_at,
                   e.per_meal_amount,
                   dept.name as department_name, desg.name as designation_name
            FROM payslips p
            LEFT JOIN employees e ON p.employee_id = e.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE YEAR(p.payslip_month) = '$selectedYear' AND MONTH(p.payslip_month) = '$selectedMonth'
            ORDER BY e.employee_id ASC
        ";

        $res = $this->db->query($query);
        $payslips = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $pid = (int) $row['id'];
                $detQuery = "SELECT payslip_item_id, amount FROM payslip_details WHERE payslip_id = $pid";
                $detRes = $this->db->query($detQuery);
                $details = [];
                if ($detRes) {
                    while ($drow = $detRes->fetch_assoc()) {
                        $details[$drow['payslip_item_id']] = (float) $drow['amount'];
                    }
                }
                $row['dynamic_details'] = $details;
                $payslips[] = $row;
            }
        }

        // Use A2 size landscape to comfortably fit 30+ columns
        $pdf = new \TCPDF('L', 'mm', 'A2');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Payroll Workplace Report');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 7);

        $monthName = date('F', mktime(0, 0, 0, $selectedMonth, 1));

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Payroll Workplace Report (' . $monthName . ' ' . $selectedYear . ')</h3>
            <p style="font-size:14px;"><b>Total Employees: </b> ' . count($payslips) . '</p>
        </div>
        <br>
        <table border="1" cellpadding="3" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; text-align:center; font-weight:bold;">
                    <th>SL</th>
                    <th>Department</th>
                    <th>Name</th>
                    <th>ID</th>
                    <th>Workplace</th>
                    <th>Designation</th>
                    <th>Basic Salary</th>';

        foreach ($items as $item) {
            if ($item['type'] === 'earning') {
                $html .= '<th>' . htmlspecialchars($item['name']) . '</th>';
            }
        }

        $html .= '
                    <th>Gross</th>
                    <th>Daily Salary</th>
                    <th>Advance Salary</th>
                    <th>Meal Count</th>
                    <th>Sub Lunch</th>
                    <th>L/U/A (Days)</th>
                    <th>L/U/A (Amt)</th>';

        foreach ($items as $item) {
            if ($item['type'] === 'deduction') {
                $lname = strtolower($item['name']);
                if (strpos($lname, 'advance') === false && strpos($lname, 'lunch') === false && strpos($lname, 'absent') === false) {
                    $html .= '<th>' . htmlspecialchars($item['name']) . '</th>';
                }
            }
        }

        $html .= '
                    <th>Punctuality Bonus</th>
                    <th>Overtime</th>
                    <th>Arrear</th>
                    <th>Other Allow</th>
                    <th>Joining Date</th>
                    <th>Resign Date</th>
                    <th>Eff. Days</th>
                    <th>Net Salary</th>
                    <th>Tax</th>
                    <th>Net Payable</th>
                    <th>Bank</th>
                    <th>Cash</th>
                    <th>A/C Name</th>
                    <th>A/C No</th>
                    <th>Amount</th>
                    <th>ID Check</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        if (empty($payslips)) {
            $html .= '<tr><td colspan="30" style="text-align:center;"><b>No payslips found for this period.</b></td></tr>';
        }
        foreach ($payslips as $p) {
            $gross = (float) $p['emp_gross'];
            $basic = (float) $p['emp_basic'];
            $daily = $gross / 30;
            $netSalary = (float) $p['net_salary'];
            $paymentMethod = strtolower($p['payment_method']);
            $bank = ($paymentMethod == 'bank' || $paymentMethod == 'bank transfer') ? $netSalary : 0;
            $cash = ($paymentMethod == 'cash') ? $netSalary : 0;

            $advanceSalary = 0;
            $mealCount = 0;
            $subsidizeLunch = 0;
            $unpaidAbsents = 0;
            $punctBonus = 0;
            $overtime = 0;
            $arrear = 0;
            $tax = 0;
            $otherAllow = 0;

            foreach ($items as $i) {
                $amt = isset($p['dynamic_details'][$i['id']]) ? (float) $p['dynamic_details'][$i['id']] : 0;
                $lname = strtolower($i['name']);
                if (strpos($lname, 'advance') !== false) {
                    $advanceSalary += $amt;
                }
                if (strpos($lname, 'lunch') !== false || strpos($lname, 'meal') !== false) {
                    $subsidizeLunch += $amt;
                    $perMeal = (float) ($p['per_meal_amount'] ?? 0);
                    if ($perMeal > 0 && $amt > 0) {
                        $mealCount = $amt / $perMeal;
                    }
                }
                if (strpos($lname, 'lwp') !== false || strpos($lname, 'absent') !== false) {
                    $unpaidAbsents += $amt;
                }
                if (strpos($lname, 'punctuality') !== false) {
                    $punctBonus += $amt;
                }
                if (strpos($lname, 'overtime') !== false) {
                    $overtime += $amt;
                }
                if (strpos($lname, 'arrear') !== false) {
                    $arrear += $amt;
                }
                if (strpos($lname, 'tax') !== false) {
                    $tax += $amt;
                }
                if (strpos($lname, 'duty') !== false || strpos($lname, 'food') !== false || strpos($lname, 'car') !== false) {
                    $otherAllow += $amt;
                }
            }

            $html .= '<tr style="text-align:center;">
                <td>' . $sl++ . '</td>
                <td>' . htmlspecialchars($p['department_name'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($p['full_name']) . '</td>
                <td>' . htmlspecialchars($p['emp_id']) . '</td>
                <td>' . htmlspecialchars($p['workplace'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($p['designation_name'] ?? 'N/A') . '</td>
                <td>' . number_format($basic, 2) . '</td>';

            foreach ($items as $item) {
                if ($item['type'] === 'earning') {
                    $html .= '<td>' . number_format($p['dynamic_details'][$item['id']] ?? 0, 2) . '</td>';
                }
            }

            $html .= '
                <td>' . number_format($gross, 2) . '</td>
                <td>' . number_format($daily, 2) . '</td>
                <td>' . number_format($advanceSalary, 2) . '</td>
                <td>' . $mealCount . '</td>
                <td>' . number_format($subsidizeLunch, 2) . '</td>
                <td>0</td>
                <td>' . number_format($unpaidAbsents, 2) . '</td>';

            foreach ($items as $item) {
                if ($item['type'] === 'deduction') {
                    $lname = strtolower($item['name']);
                    if (strpos($lname, 'advance') === false && strpos($lname, 'lunch') === false && strpos($lname, 'absent') === false) {
                        $html .= '<td>' . number_format($p['dynamic_details'][$item['id']] ?? 0, 2) . '</td>';
                    }
                }
            }

            $joinDate = !empty($p['joining_date']) ? date('d-M-y', strtotime($p['joining_date'])) : '';
            $resignDate = !empty($p['deleted_at']) ? date('d-M-y', strtotime($p['deleted_at'])) : '';

            $html .= '
                <td>' . ($punctBonus > 0 ? number_format($punctBonus, 2) : '0.00') . '</td>
                <td>' . ($overtime > 0 ? number_format($overtime, 2) : '0.00') . '</td>
                <td>' . ($arrear > 0 ? number_format($arrear, 2) : '0.00') . '</td>
                <td>' . ($otherAllow > 0 ? number_format($otherAllow, 2) : '0.00') . '</td>
                <td>' . ($joinDate ?: '&nbsp;') . '</td>
                <td>' . ($resignDate ?: '&nbsp;') . '</td>
                <td>30</td>
                <td>' . number_format($netSalary, 2) . '</td>
                <td>' . number_format($tax, 2) . '</td>
                <td>' . number_format($netSalary, 2) . '</td>
                <td>' . number_format($bank, 2) . '</td>
                <td>' . number_format($cash, 2) . '</td>
                <td>' . (!empty($p['account_holder_name']) ? htmlspecialchars($p['account_holder_name']) : '&nbsp;') . '</td>
                <td>' . (!empty($p['account_number']) ? htmlspecialchars($p['account_number']) : '&nbsp;') . '</td>
                <td>' . number_format($bank, 2) . '</td>
                <td>&nbsp;</td>
            </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        ob_end_clean();
        $pdf->Output('Payroll_Workspace_Report_' . $monthName . '_' . $selectedYear . '.pdf', 'D');
        exit;
    }

    public function attendanceReport()
    {
        $this->view("payrolls/payslips/attendance-report", [
            "current_route" => "payroll-attendance-report",
            "page_title" => "Payroll Attendance Report"
        ]);
    }

    public function exportAttendancePDF()
    {
        // Require Tipsoi External API logic if not already loaded
        if (!class_exists('ExternalApiService')) {
            require_once __DIR__ . '/../services/ExternalApiService.php';
        }
        $apiService = new ExternalApiService($this->db);

        $fromDateStr = $_GET['from_date'] ?? date('Y-m-d');
        $toDateStr = $_GET['to_date'] ?? date('Y-m-d');

        // Force UTC or Dhaka boundaries
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        $fromTime = strtotime($fromDateStr . ' 00:00:00') * 1000;
        $toTime = strtotime($toDateStr . ' 23:59:59') * 1000;

        date_default_timezone_set($oldTimezone);

        $token = $apiService->getBearerToken();
        if (!$token) {
            die("Failed to authenticate with Tipsoi API.");
        }

        $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
            'officeId' => 4699,
            'order' => 'asc',
            'pageNumber' => 0,
            'perPage' => 2000,
            'reportType' => 'department',
            'sortKey' => 'reportPosition',
            'status' => 1,
            'from' => $fromTime,
            'to' => $toTime
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($httpCode !== 200 || !isset($data['attendance'])) {
            die("Failed to retrieve attendance logs from Tipsoi API.");
        }

        // Fetch local employees for name/ID checks
        $localQuery = $this->db->query("SELECT employee_id, email, full_name FROM employees");
        $localEmployees = [];
        if ($localQuery) {
            while ($row = $localQuery->fetch_assoc()) {
                $localEmployees[] = [
                    'employee_id' => trim($row['employee_id'] ?? ''),
                    'email' => strtolower(trim($row['email'] ?? '')),
                    'full_name' => strtolower(trim($row['full_name'] ?? ''))
                ];
            }
        }

        // Aggregate Data
        $aggregatedData = [];
        $index = 1;
        foreach ($data['attendance'] as $emp) {
            $officeId = trim($emp['employeeOfficeId'] ?? $emp['employeeId']);
            $officeIdNorm = preg_match('/^\d+$/', $officeId) ? (string) (int) $officeId : $officeId;
            $nameNorm = strtolower(trim($emp['employeeName'] ?? ''));

            $isLocal = false;
            foreach ($localEmployees as $le) {
                $leIdNorm = preg_match('/^\d+$/', $le['employee_id']) ? (string) (int) $le['employee_id'] : $le['employee_id'];
                if (($officeIdNorm !== '' && $leIdNorm === $officeIdNorm) || ($nameNorm !== '' && $le['full_name'] === $nameNorm)) {
                    $isLocal = true;
                    break;
                }
            }

            $workingDays = 0;
            $present = 0;
            $absent = 0;
            $leave = 0;
            $dayOff = 0;
            $late = 0;

            $shiftStartStr = $emp['shiftStartTime'] ?? '';
            $shiftStartMin = null;
            if (preg_match('/^(\d+):(\d+)\s*(AM|PM)$/i', trim($shiftStartStr), $m)) {
                $h = (int) $m[1];
                $min = (int) $m[2];
                $ampm = strtoupper($m[3]);
                if ($ampm === 'PM' && $h !== 12)
                    $h += 12;
                elseif ($ampm === 'AM' && $h === 12)
                    $h = 0;
                $shiftStartMin = $h * 60 + $min;
            } else {
                $shiftStartMin = 570; // 09:30 AM default
            }
            $lateThreshold = $shiftStartMin + 10;

            $attMap = $emp['attendance'] ?? [];
            foreach ($attMap as $ts => $dayAtt) {
                $workingDays++;

                $isLateOverride = false;
                if (!empty($dayAtt['isPresent']) && !empty($dayAtt['firstLoggedTime']) && $dayAtt['firstLoggedTime'] !== '-' && $dayAtt['firstLoggedTime'] !== ' ') {
                    if (preg_match('/^(\d+):(\d+)\s*(AM|PM)$/i', trim($dayAtt['firstLoggedTime']), $zm)) {
                        $zh = (int) $zm[1];
                        $zmin = (int) $zm[2];
                        $zampm = strtoupper($zm[3]);
                        if ($zampm === 'PM' && $zh !== 12)
                            $zh += 12;
                        elseif ($zampm === 'AM' && $zh === 12)
                            $zh = 0;
                        $checkInMin = $zh * 60 + $zmin;
                        if ($checkInMin > $lateThreshold)
                            $isLateOverride = true;
                    }
                }

                if (!empty($dayAtt['isLeave'])) {
                    $leave++;
                } else if (!empty($dayAtt['isPresent'])) {
                    $present++;
                    if ($isLateOverride)
                        $late++;
                } else if (!empty($dayAtt['isOffday']) || !empty($dayAtt['isHoliday'])) {
                    $dayOff++;
                    $workingDays--;
                } else {
                    $absent++;
                }
            }

            $aggregatedData[] = [
                'sl' => $index++,
                'name' => $emp['employeeName'] ?? '-',
                'id' => $emp['employeeOfficeId'] ?? '-',
                'designation' => $emp['designationName'] ?? '-',
                'department' => $emp['departmentName'] ?? '-',
                'workplace' => $emp['workplaceName'] ?? '-',
                'empType' => 'Regular',
                'workingDays' => $workingDays,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'dayOff' => $dayOff,
                'late' => $late,
                'approvedLates' => 0,
                'finalLate' => $late,
                'deduction' => 0,
                'lateUnpaidAbsentDays' => $absent,
                'idCheck' => $isLocal ? 'OK' : 'Mismatch',
                'nameCheck' => $isLocal ? 'OK' : 'Mismatch'
            ];
        }

        // Generate TCPDF (Landscape A2 for 23 columns)
        // Adjust column sizes precisely so they do not overlap
        $pdf = new TCPDF('L', PDF_UNIT, 'A2', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Payroll Attendance Report - $fromDateStr to $toDateStr");

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetFillColor(7, 129, 52); // Theme Green header
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 15, 'The Premium Homes Ltd.', 0, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(255, 255, 255); // Reset
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, "Payroll Attendance Report: " . date('F d, Y', strtotime($fromDateStr)) . " to " . date('F d, Y', strtotime($toDateStr)), 0, 1, 'C', true);
        $pdf->Ln(); // no space before headings

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(7, 129, 52); // table header color
        $pdf->SetTextColor(255, 255, 255);

        // A2 landscape width is ~574mm left after 20mm margins.
        $widths = [
            10, // SL
            45, // Name
            18, // ID
            40, // Designation
            35, // Department
            35, // Workplace
            25, // Type
            18, // Work Days
            16, // Present
            16, // Absent
            15, // Leave
            18, // Day Off
            12, // Late
            20, // App Lates
            20, // Final Late
            20, // Deduct
            22, // Unpaid
            20, // Join
            22, // Resign
            22, // Effect
            35, // Remarks
            20, // ID Check
            25  // Name Check
        ];

        $headers = [
            'SL',
            'Employee Name',
            'ID',
            'Designation',
            'Department',
            'Workplace',
            'Type',
            'Work Days',
            'Present',
            'Absent',
            'Leave',
            'Day Off',
            'Late',
            'App Lates',
            'Final Late',
            'Deduct',
            'Unpaid',
            'Join Date',
            'Resign Date',
            'Effect Date',
            'Remarks',
            'ID Check',
            'Name Check'
        ];

        // Draw Headers
        foreach ($headers as $index => $h) {
            $pdf->Cell($widths[$index], 10, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);
        $fill = false;
        foreach ($aggregatedData as $row) {
            if ($fill)
                $pdf->SetFillColor(245, 245, 245);
            else
                $pdf->SetFillColor(255, 255, 255);

            $pdf->Cell($widths[0], 8, $row['sl'], 1, 0, 'C', $fill);
            // Limit characters to avoid breaking row height
            $pdf->Cell($widths[1], 8, mb_substr($row['name'], 0, 23), 1, 0, 'L', $fill);
            $pdf->Cell($widths[2], 8, $row['id'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[3], 8, mb_substr($row['designation'], 0, 23), 1, 0, 'L', $fill);
            $pdf->Cell($widths[4], 8, mb_substr($row['department'], 0, 18), 1, 0, 'L', $fill);
            $pdf->Cell($widths[5], 8, mb_substr($row['workplace'], 0, 18), 1, 0, 'L', $fill);
            $pdf->Cell($widths[6], 8, $row['empType'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[7], 8, $row['workingDays'], 1, 0, 'C', $fill);

            $pdf->SetTextColor(0, 128, 0);
            $pdf->Cell($widths[8], 8, $row['present'], 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->SetTextColor(255, 0, 0);
            $pdf->Cell($widths[9], 8, $row['absent'], 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[10], 8, $row['leave'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[11], 8, $row['dayOff'], 1, 0, 'C', $fill);

            $pdf->SetTextColor(255, 140, 0);
            $pdf->Cell($widths[12], 8, $row['late'], 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($widths[13], 8, '-', 1, 0, 'C', $fill);
            $pdf->Cell($widths[14], 8, $row['finalLate'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[15], 8, '-', 1, 0, 'C', $fill);
            $pdf->Cell($widths[16], 8, $row['lateUnpaidAbsentDays'], 1, 0, 'C', $fill);

            $pdf->Cell($widths[17], 8, '-', 1, 0, 'C', $fill); // Join
            $pdf->Cell($widths[18], 8, '-', 1, 0, 'C', $fill); // Resign
            $pdf->Cell($widths[19], 8, '-', 1, 0, 'C', $fill); // Effect
            $pdf->Cell($widths[20], 8, '-', 1, 0, 'C', $fill); // Remarks

            if ($row['idCheck'] === 'OK')
                $pdf->SetTextColor(0, 128, 0);
            else
                $pdf->SetTextColor(255, 0, 0);
            $pdf->Cell($widths[21], 8, $row['idCheck'], 1, 0, 'C', $fill);

            if ($row['nameCheck'] === 'OK')
                $pdf->SetTextColor(0, 128, 0);
            else
                $pdf->SetTextColor(255, 0, 0);
            $pdf->Cell($widths[22], 8, $row['nameCheck'], 1, 0, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Ln();

            $fill = !$fill;
        }

        $pdf->Output("payroll_attendance_{$fromDateStr}_to_{$toDateStr}.pdf", 'D');
    }

    public function joiningList()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $defaultFrom = date('Y-m-d');
        $defaultTo = date('Y-m-d');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $defaultFrom;
            $toDate = $_GET['to_date'] ?? $defaultTo;
        }

        $sql = "SELECT e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                       e.workplace, e.joining_date, e.gross_salary, e.basic_salary
                FROM employees e 
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                WHERE e.joining_date >= ? AND e.joining_date <= ?
                ORDER BY e.joining_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $employees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $employees[] = $row;
            }
        }

        $page_title = "Payroll Joining List";
        $current_route = "payroll-joining-list";

        $this->view('payrolls/payslips/joining-list', [
            'page_title' => $page_title,
            'current_route' => $current_route,
            'employees' => $employees,
            'filter_type' => $filterType,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'selected_month' => $_GET['month'] ?? $currentMonth,
            'selected_year' => $_GET['year'] ?? $currentYear
        ]);
    }

    public function exportJoiningListPDF()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $defaultFrom = date('Y-m-d');
        $defaultTo = date('Y-m-d');

        $filterType = $_GET['filter_type'] ?? 'monthly';
        $fromDate = $defaultFrom;
        $toDate = $defaultTo;

        if ($filterType === 'monthly') {
            $m = $_GET['month'] ?? $currentMonth;
            $y = $_GET['year'] ?? $currentYear;
            $fromDate = date('Y-m-d', mktime(0, 0, 0, $m, 1, $y));
            $toDate = date('Y-m-t', mktime(0, 0, 0, $m, 1, $y));
        } elseif ($filterType === 'custom') {
            $fromDate = $_GET['from_date'] ?? $defaultFrom;
            $toDate = $_GET['to_date'] ?? $defaultTo;
        }

        $sql = "SELECT e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                       e.workplace, e.joining_date, e.gross_salary, e.basic_salary
                FROM employees e 
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                WHERE e.joining_date >= ? AND e.joining_date <= ?
                ORDER BY e.joining_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $employees = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $employees[] = $row;
            }
        }

        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('TPHL ERP');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Joining List Report');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 9);

        $html = '
        <div style="text-align:center;">
            <h1 style="color:#078134;">The Premium Homes Ltd</h1>
            <h3 style="color:#28B463;">Joining List Report </h3>
            <p>Date Range: ' . date('F d, Y', strtotime($fromDate)) . ' to ' . date('F d, Y', strtotime($toDate)) . '</p>
            <p><b>Total Records: </b> ' . count($employees) . '</p>
        </div>
        <br>
        <table border="1" cellpadding="5" width="100%">
            <thead>
                <tr style="background-color:#078134; color:#ffffff; text-align:center; font-weight:bold;">
                    <th width="5%">SL</th>
                    <th width="10%">ID</th>
                    <th width="18%">Name</th>
                    <th width="22%">Department - Designation</th>
                    <th width="15%">Workplace</th>
                    <th width="10%">Joining Date</th>
                    <th width="10%">Gross Salary</th>
                    <th width="10%">Basic Salary</th>
                </tr>
            </thead>
            <tbody>';

        $sl = 1;
        foreach ($employees as $row) {
            $deptDesig = ($row['department'] ?? '-') . ' - ' . ($row['designation'] ?? '-');
            $html .= '<tr style="text-align:center;">
                        <td width="5%">' . $sl++ . '</td>
                        <td width="10%">' . htmlspecialchars($row['employee_id']) . '</td>
                        <td width="18%" style="text-align:left;">' . htmlspecialchars($row['full_name']) . '</td>
                        <td width="22%" style="text-align:left;">' . htmlspecialchars($deptDesig) . '</td>
                        <td width="15%">' . htmlspecialchars($row['workplace'] ?? '-') . '</td>
                        <td width="10%">' . htmlspecialchars($row['joining_date']) . '</td>
                        <td width="10%">' . htmlspecialchars(number_format($row['gross_salary'] ?? 0, 2)) . '</td>
                        <td width="10%">' . htmlspecialchars(number_format($row['basic_salary'] ?? 0, 2)) . '</td>
                      </tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Joining_List_Report_' . $fromDate . '_to_' . $toDate . '.pdf', 'D');
        exit;
    }
}
