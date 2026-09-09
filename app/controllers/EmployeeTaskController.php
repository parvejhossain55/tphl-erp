<?php

class EmployeeTaskController extends Controller
{
    protected $db;
    protected $taskModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->taskModel = new EmployeeTask($this->db);
    }

    protected function getLoggedInEmployee()
    {
        if (empty($_SESSION['user']['id'])) {
            return null;
        }

        $userId = (int)$_SESSION['user']['id'];
        $empDbId = !empty($_SESSION['user']['employee_db_id']) ? (int)$_SESSION['user']['employee_db_id'] : 0;
        $empCode = !empty($_SESSION['user']['employee_id']) ? (string)$_SESSION['user']['employee_id'] : '';

        // Query employees table by user_id, id, or employee_id
        $stmt = $this->db->prepare("
            SELECT id, employee_id, user_id, full_name, department_id, designation_id 
            FROM employees 
            WHERE user_id = ? 
               OR (id > 0 AND id = ?) 
               OR (employee_id != '' AND (employee_id = ? OR employee_id = ?))
            ORDER BY (user_id = ?) DESC, (id = ?) DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("iisssi", $userId, $empDbId, $empCode, $empDbId, $userId, $empDbId);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($employee) {
                $_SESSION['user']['employee_db_id'] = (int)$employee['id'];
                return $employee;
            }
        }

        return null;
    }

    protected function getEmployeeIds($employee)
    {
        $employeeIds = [];
        if ($employee) {
            $employeeIds[] = (int)$employee['id'];
            if (!empty($employee['employee_id'])) {
                $employeeIds[] = $employee['employee_id'];
            }
        }
        if (!empty($_SESSION['user']['employee_db_id'])) {
            $employeeIds[] = (int)$_SESSION['user']['employee_db_id'];
        }
        if (!empty($_SESSION['user']['employee_id'])) {
            $employeeIds[] = $_SESSION['user']['employee_id'];
        }
        return array_values(array_unique(array_filter($employeeIds)));
    }

    protected function getFilterParams()
    {
        $year = isset($_GET['year']) ? trim($_GET['year']) : null;
        $month = isset($_GET['month']) ? trim($_GET['month']) : null;
        $dateFrom = !empty($_GET['date_from']) ? trim($_GET['date_from']) : '';
        $dateTo = !empty($_GET['date_to']) ? trim($_GET['date_to']) : '';
        $all = isset($_GET['all']) && ($_GET['all'] === '1' || $_GET['all'] === 'true');

        $filter = [
            'mode' => 'month_year',
            'year' => date('Y'),
            'month' => date('m'),
            'date_from' => '',
            'date_to' => '',
            'all_months' => false,
            'period_label' => '',
            'file_suffix' => ''
        ];

        if ($all) {
            $filter['mode'] = 'all';
            $filter['year'] = 'all';
            $filter['month'] = 'all';
            $filter['all_months'] = true;
            $filter['period_label'] = 'All Time / সব ডাটা';
            $filter['file_suffix'] = 'All_Time';
        } elseif (!empty($dateFrom) || !empty($dateTo)) {
            $filter['mode'] = 'date_range';
            $filter['date_from'] = $dateFrom;
            $filter['date_to'] = $dateTo;
            $filter['year'] = '';
            $filter['month'] = '';
            
            $fromLabel = $dateFrom ? date('d M Y', strtotime($dateFrom)) : 'Start';
            $toLabel = $dateTo ? date('d M Y', strtotime($dateTo)) : 'Present';
            $filter['period_label'] = "{$fromLabel} to {$toLabel}";
            $filter['file_suffix'] = ($dateFrom ?: 'start') . '_to_' . ($dateTo ?: 'end');
        } else {
            // Handle legacy 'YYYY-MM' format if passed in month
            if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
                $parts = explode('-', $month);
                $year = $parts[0];
                $month = $parts[1];
            }

            // Check if user checked 'all_months'
            if (isset($_GET['all_months']) && ($_GET['all_months'] === '1' || $_GET['all_months'] === 'true' || $_GET['all_months'] === 'on')) {
                $month = 'all';
            }

            // Default on initial load without any query params: current year and current month
            if ($year === null && $month === null) {
                $year = date('Y');
                $month = date('m');
            }

            $filter['year'] = $year ?: 'all';
            $filter['month'] = $month ?: 'all';
            $filter['all_months'] = ($filter['month'] === 'all');

            if ($filter['year'] === 'all' && $filter['month'] === 'all') {
                $filter['mode'] = 'all';
                $filter['period_label'] = 'All Time / সব ডাটা';
                $filter['file_suffix'] = 'All_Time';
            } elseif ($filter['year'] !== 'all' && $filter['month'] === 'all') {
                $filter['mode'] = 'year';
                $filter['period_label'] = "Year {$filter['year']} (All Months / সব মাস)";
                $filter['file_suffix'] = "{$filter['year']}_All_Months";
            } elseif ($filter['year'] === 'all' && $filter['month'] !== 'all') {
                $monthNum = (int)$filter['month'];
                $monthName = date('F', mktime(0, 0, 0, $monthNum, 10));
                $filter['mode'] = 'month_all_years';
                $filter['period_label'] = "All Years — {$monthName}";
                $filter['file_suffix'] = "All_Years_{$monthName}";
            } else {
                $filter['mode'] = 'month_year';
                $monthNum = (int)$filter['month'];
                $monthName = date('F', mktime(0, 0, 0, $monthNum, 10));
                $filter['period_label'] = "{$monthName} {$filter['year']}";
                $filter['file_suffix'] = "{$filter['year']}_{$filter['month']}";
            }
        }

        return $filter;
    }

    public function index()
    {
        $employee = $this->getLoggedInEmployee();
        $employeeIds = $this->getEmployeeIds($employee);

        $filter = $this->getFilterParams();

        $taskSheets = !empty($employeeIds) ? $this->taskModel->getTasksByEmployee($employeeIds, [
            'year' => $filter['year'],
            'month' => $filter['month'],
            'date_from' => $filter['date_from'],
            'date_to' => $filter['date_to'],
        ]) : [];

        // Build query string for export to match current filter
        $exportQueryParams = [];
        if ($filter['mode'] === 'all') {
            $exportQueryParams['all'] = 1;
        } elseif ($filter['mode'] === 'date_range') {
            if ($filter['date_from']) $exportQueryParams['date_from'] = $filter['date_from'];
            if ($filter['date_to']) $exportQueryParams['date_to'] = $filter['date_to'];
        } else {
            $exportQueryParams['year'] = $filter['year'];
            $exportQueryParams['month'] = $filter['month'];
            if ($filter['all_months']) {
                $exportQueryParams['all_months'] = 1;
            }
        }
        $exportQueryString = http_build_query($exportQueryParams);

        $currentMonth = ($filter['year'] !== 'all' && $filter['month'] !== 'all' && is_numeric($filter['year']) && is_numeric($filter['month'])) 
            ? sprintf('%04d-%02d', (int)$filter['year'], (int)$filter['month']) 
            : date('Y-m');

        return $this->view('employee_tasks/task-sheets', [
            'current_route' => 'task-sheets',
            'taskSheets' => $taskSheets,
            'filter' => $filter,
            'exportQueryString' => $exportQueryString,
            'currentMonth' => $currentMonth,
            'employee' => $employee
        ]);
    }

    public function save()
    {
        $employee = $this->getLoggedInEmployee();
        $employee_id = $employee ? (int)$employee['id'] : (!empty($_SESSION['user']['employee_db_id']) ? (int)$_SESSION['user']['employee_db_id'] : (int)($_SESSION['user']['employee_id'] ?? 0)); 

        $data = [
            'employee_id' => $employee_id,
            'work_date' => $_POST['work_date'],
            'task_description' => $_POST['task_description'],
            'hours_spent' => $_POST['hours_spent'],
            'status' => $_POST['status'],
            'remarks' => $_POST['remarks'] ?? null
        ];

        if (!empty($_POST['id'])) {
            $this->taskModel->updateTask($_POST['id'], $data);
        } else {
            $this->taskModel->createTask($data);
        }

        setSuccess("Task Update Successfuly");
        $redirectMonth = !empty($_POST['work_date']) ? '?month=' . substr($_POST['work_date'], 0, 7) : '';
        header("Location: {$GLOBALS['base_url']}/task-sheets" . $redirectMonth);
        exit;
    }

    public function exportExcel()
    {
        $employee = $this->getLoggedInEmployee();
        $employeeIds = $this->getEmployeeIds($employee);

        $filter = $this->getFilterParams();

        // fetch tasks for this employee matching the active filter
        $taskSheets = !empty($employeeIds) ? $this->taskModel->getTasksByEmployee($employeeIds, [
            'year' => $filter['year'],
            'month' => $filter['month'],
            'date_from' => $filter['date_from'],
            'date_to' => $filter['date_to'],
        ]) : [];

        $empName = $_SESSION['user']['full_name'] ?? ($employee['full_name'] ?? 'Employee');
        $empCode = $_SESSION['user']['employee_id'] ?? ($employee['employee_id'] ?? '');
        $designation = $_SESSION['user']['designation_name'] ?? '';
        $department = $_SESSION['user']['department_name'] ?? '';
        $periodLabel = $filter['period_label'];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetTitle = substr(preg_replace('/[^A-Za-z0-9 _\-]/', '', $periodLabel), 0, 31);
        $sheet->setTitle($sheetTitle ?: 'Task Sheet');

        $sheet->setShowGridLines(true);

        // Header Title
        $sheet->setCellValue('A1', 'TASK SHEET REPORT');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        $metaText = "Employee: {$empName}" . ($empCode ? " (ID: {$empCode})" : "");
        if ($designation || $department) {
            $metaText .= " | " . trim("{$designation} - {$department}", " -");
        }
        $metaText .= " | Period: {$periodLabel}";

        $sheet->setCellValue('A2', $metaText);
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        // Table Column Headers at Row 4
        $headers = ['#', 'Date', 'Task Description', 'Hours', 'Status', 'Remarks'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($cols[$idx] . '4', $h);
        }
        $sheet->getStyle('A4:F4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Data Rows
        $row = 5;
        $totalHours = 0;
        $sl = 1;

        foreach ($taskSheets as $task) {
            $workDate = !empty($task['work_date']) ? date('d-m-Y', strtotime($task['work_date'])) : '';
            $hours = (float)($task['hours_spent'] ?? 0);
            $totalHours += $hours;

            $rawStatus = trim($task['status'] ?? '');
            $statusLabel = match (strtolower(str_replace(' ', '_', $rawStatus))) {
                'done' => 'Done',
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                default => ucfirst($rawStatus)
            };

            $sheet->setCellValue("A{$row}", $sl++);
            $sheet->setCellValue("B{$row}", $workDate);
            $sheet->setCellValue("C{$row}", $task['task_description'] ?? '');
            $sheet->setCellValue("D{$row}", $hours);
            $sheet->setCellValue("E{$row}", $statusLabel);
            $sheet->setCellValue("F{$row}", $task['remarks'] ?? '');

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            if ($sl % 2 === 0) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
        }

        if (empty($taskSheets)) {
            $sheet->setCellValue("A{$row}", 'No tasks recorded for this period.');
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
            $row++;
        }

        // Summary Total Row
        $sheet->setCellValue("A{$row}", 'Total Hours');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("D{$row}", number_format($totalHours, 2));
        $sheet->setCellValue("E{$row}", '');
        $sheet->setCellValue("F{$row}", '');

        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEF2FF']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(24);

        // Borders
        $sheet->getStyle("A4:F{$row}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        // Auto column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(45);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(30);

        // Clean filename and headers
        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empName);
        $cleanSuffix = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filter['file_suffix']);
        $fileName = "Task_Sheet_{$cleanName}_{$cleanSuffix}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Check if current user is HR or Management
     */
    protected function checkHrOrManagementAccess()
    {
        if (empty($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');

        $isAllowed = in_array($roleId, [1, 3, 4, 33])
            || hasPermission("Hr Manage")
            || hasPermission("HRM Management")
            || str_contains($roleName, 'hr')
            || str_contains($roleName, 'admin')
            || str_contains($roleName, 'management')
            || str_contains($roleName, 'director');

        if (!$isAllowed) {
            setError("Access Denied. Only HR and Management can view all employees tasks.");
            header("Location: {$GLOBALS['base_url']}/task-sheets");
            exit;
        }
    }

    /**
     * Parse filters for All Employees Task View
     */
    protected function getAllEmployeesFilterParams()
    {
        $departmentId = isset($_GET['department_id']) ? trim($_GET['department_id']) : 'all';
        $employeeId = isset($_GET['employee_id']) ? trim($_GET['employee_id']) : 'all';
        $year = isset($_GET['year']) ? trim($_GET['year']) : null;
        $month = isset($_GET['month']) ? trim($_GET['month']) : null;
        $dateFrom = !empty($_GET['date_from']) ? trim($_GET['date_from']) : '';
        $dateTo = !empty($_GET['date_to']) ? trim($_GET['date_to']) : '';
        $all = isset($_GET['all']) && ($_GET['all'] === '1' || $_GET['all'] === 'true');

        $filter = [
            'mode' => 'month_year',
            'department_id' => $departmentId,
            'employee_id' => $employeeId,
            'year' => date('Y'),
            'month' => date('m'),
            'date_from' => '',
            'date_to' => '',
            'all_months' => false,
            'period_label' => '',
            'file_suffix' => ''
        ];

        if ($all) {
            $filter['mode'] = 'all';
            $filter['year'] = 'all';
            $filter['month'] = 'all';
            $filter['all_months'] = true;
            $filter['period_label'] = 'All Time / সব ডাটা';
            $filter['file_suffix'] = 'All_Time';
        } elseif (!empty($dateFrom) || !empty($dateTo)) {
            $filter['mode'] = 'date_range';
            $filter['date_from'] = $dateFrom;
            $filter['date_to'] = $dateTo;
            $filter['year'] = '';
            $filter['month'] = '';

            $fromLabel = $dateFrom ? date('d M Y', strtotime($dateFrom)) : 'Start';
            $toLabel = $dateTo ? date('d M Y', strtotime($dateTo)) : 'Present';
            $filter['period_label'] = "{$fromLabel} to {$toLabel}";
            $filter['file_suffix'] = ($dateFrom ?: 'start') . '_to_' . ($dateTo ?: 'end');
        } else {
            if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
                $parts = explode('-', $month);
                $year = $parts[0];
                $month = $parts[1];
            }

            if (isset($_GET['all_months']) && ($_GET['all_months'] === '1' || $_GET['all_months'] === 'true' || $_GET['all_months'] === 'on')) {
                $month = 'all';
            }

            if ($year === null && $month === null) {
                $year = date('Y');
                $month = date('m');
            }

            $filter['year'] = $year ?: 'all';
            $filter['month'] = $month ?: 'all';
            $filter['all_months'] = ($filter['month'] === 'all');

            if ($filter['year'] === 'all' && $filter['month'] === 'all') {
                $filter['mode'] = 'all';
                $filter['period_label'] = 'All Time / সব ডাটা';
                $filter['file_suffix'] = 'All_Time';
            } elseif ($filter['year'] !== 'all' && $filter['month'] === 'all') {
                $filter['mode'] = 'year';
                $filter['period_label'] = "Year {$filter['year']} (All Months / সব মাস)";
                $filter['file_suffix'] = "{$filter['year']}_All_Months";
            } elseif ($filter['year'] === 'all' && $filter['month'] !== 'all') {
                $monthNum = (int)$filter['month'];
                $monthName = date('F', mktime(0, 0, 0, $monthNum, 10));
                $filter['mode'] = 'month_all_years';
                $filter['period_label'] = "All Years — {$monthName}";
                $filter['file_suffix'] = "All_Years_{$monthName}";
            } else {
                $filter['mode'] = 'month_year';
                $monthNum = (int)$filter['month'];
                $monthName = date('F', mktime(0, 0, 0, $monthNum, 10));
                $filter['period_label'] = "{$monthName} {$filter['year']}";
                $filter['file_suffix'] = "{$filter['year']}_{$filter['month']}";
            }
        }

        return $filter;
    }

    /**
     * All Employees Task Sheet page (For HR and Management)
     */
    public function allEmployeesTask()
    {
        $this->checkHrOrManagementAccess();

        $departments = $this->taskModel->getAllActiveDepartments();
        $employees = $this->taskModel->getActiveEmployeesForFilter();

        // Check if user has explicitly searched / filtered
        // By default: no rows displayed until user filters!
        $hasSearched = isset($_GET['search']) 
            || isset($_GET['filter']) 
            || (isset($_GET['department_id']) && $_GET['department_id'] !== '' && $_GET['department_id'] !== 'all') 
            || (isset($_GET['employee_id']) && $_GET['employee_id'] !== '' && $_GET['employee_id'] !== 'all') 
            || !empty($_GET['date_from']) 
            || !empty($_GET['date_to']) 
            || isset($_GET['all'])
            || isset($_GET['all_months'])
            || (isset($_GET['department_id']) && $_GET['department_id'] === 'all' && isset($_GET['filter']));

        $filter = $this->getAllEmployeesFilterParams();

        $taskSheets = [];
        if ($hasSearched) {
            $taskSheets = $this->taskModel->getAllEmployeesTasks([
                'department_id' => $filter['department_id'],
                'employee_id'   => $filter['employee_id'],
                'year'          => $filter['year'],
                'month'         => $filter['month'],
                'date_from'     => $filter['date_from'],
                'date_to'       => $filter['date_to'],
            ]);
        }

        // Build export query string matching the search
        $exportQueryParams = [
            'department_id' => $filter['department_id'],
            'employee_id'   => $filter['employee_id']
        ];
        if ($filter['mode'] === 'all') {
            $exportQueryParams['all'] = 1;
        } elseif ($filter['mode'] === 'date_range') {
            if ($filter['date_from']) $exportQueryParams['date_from'] = $filter['date_from'];
            if ($filter['date_to']) $exportQueryParams['date_to'] = $filter['date_to'];
        } else {
            $exportQueryParams['year'] = $filter['year'];
            $exportQueryParams['month'] = $filter['month'];
            if ($filter['all_months']) {
                $exportQueryParams['all_months'] = 1;
            }
        }
        $exportQueryString = http_build_query($exportQueryParams);

        // Find selected employee or dept name for heading if filtered
        $selectedEmpName = '';
        if (!empty($filter['employee_id']) && $filter['employee_id'] !== 'all') {
            foreach ($employees as $e) {
                if ((string)$e['id'] === (string)$filter['employee_id'] || $e['employee_id'] === $filter['employee_id']) {
                    $selectedEmpName = $e['full_name'] . ' (' . $e['employee_id'] . ')';
                    break;
                }
            }
        }
        $selectedDeptName = '';
        if (!empty($filter['department_id']) && $filter['department_id'] !== 'all') {
            foreach ($departments as $d) {
                if ((string)$d['id'] === (string)$filter['department_id']) {
                    $selectedDeptName = $d['name'];
                    break;
                }
            }
        }

        return $this->view('employee_tasks/all-employee-tasks', [
            'current_route'     => 'all-employee-tasks',
            'taskSheets'        => $taskSheets,
            'hasSearched'       => $hasSearched,
            'departments'       => $departments,
            'employees'         => $employees,
            'filter'            => $filter,
            'selectedEmpName'   => $selectedEmpName,
            'selectedDeptName'  => $selectedDeptName,
            'exportQueryString' => $exportQueryString,
        ]);
    }

    /**
     * Export All Employees Tasks to Excel
     */
    public function exportAllEmployeesExcel()
    {
        $this->checkHrOrManagementAccess();

        $departments = $this->taskModel->getAllActiveDepartments();
        $employees = $this->taskModel->getActiveEmployeesForFilter();

        $filter = $this->getAllEmployeesFilterParams();

        $taskSheets = $this->taskModel->getAllEmployeesTasks([
            'department_id' => $filter['department_id'],
            'employee_id'   => $filter['employee_id'],
            'year'          => $filter['year'],
            'month'         => $filter['month'],
            'date_from'     => $filter['date_from'],
            'date_to'       => $filter['date_to'],
        ]);

        $selectedEmpName = 'All Employees';
        if (!empty($filter['employee_id']) && $filter['employee_id'] !== 'all') {
            foreach ($employees as $e) {
                if ((string)$e['id'] === (string)$filter['employee_id'] || $e['employee_id'] === $filter['employee_id']) {
                    $selectedEmpName = $e['full_name'] . ' (' . $e['employee_id'] . ')';
                    break;
                }
            }
        }
        $selectedDeptName = 'All Departments';
        if (!empty($filter['department_id']) && $filter['department_id'] !== 'all') {
            foreach ($departments as $d) {
                if ((string)$d['id'] === (string)$filter['department_id']) {
                    $selectedDeptName = $d['name'];
                    break;
                }
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetTitle = substr(preg_replace('/[^A-Za-z0-9 _\-]/', '', $filter['period_label']), 0, 31);
        $sheet->setTitle($sheetTitle ?: 'All Tasks');
        $sheet->setShowGridLines(true);

        // Header Title
        $sheet->setCellValue('A1', 'ALL EMPLOYEES TASK SHEET REPORT');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        $metaText = "Department: {$selectedDeptName} | Employee: {$selectedEmpName} | Period: {$filter['period_label']}";
        $sheet->setCellValue('A2', $metaText);
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        // Table Column Headers at Row 4
        $headers = ['#', 'Date', 'Emp ID', 'Employee Name', 'Department', 'Task Description', 'Hours', 'Status', 'Remarks'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($cols[$idx] . '4', $h);
        }
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Data Rows
        $row = 5;
        $totalHours = 0;
        $sl = 1;

        foreach ($taskSheets as $task) {
            $workDate = !empty($task['work_date']) ? date('d-m-Y', strtotime($task['work_date'])) : '';
            $hours = (float)($task['hours_spent'] ?? 0);
            $totalHours += $hours;

            $rawStatus = trim($task['status'] ?? '');
            $statusLabel = match (strtolower(str_replace(' ', '_', $rawStatus))) {
                'done' => 'Done',
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                default => ucfirst($rawStatus)
            };

            $sheet->setCellValue("A{$row}", $sl++);
            $sheet->setCellValue("B{$row}", $workDate);
            $sheet->setCellValue("C{$row}", $task['emp_code'] ?? '');
            $sheet->setCellValue("D{$row}", $task['emp_name'] ?? '');
            $sheet->setCellValue("E{$row}", $task['department_name'] ?? '—');
            $sheet->setCellValue("F{$row}", $task['task_description'] ?? '');
            $sheet->setCellValue("G{$row}", $hours);
            $sheet->setCellValue("H{$row}", $statusLabel);
            $sheet->setCellValue("I{$row}", $task['remarks'] ?? '');

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            if ($sl % 2 === 0) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
        }

        if (empty($taskSheets)) {
            $sheet->setCellValue("A{$row}", 'No tasks recorded for this filter.');
            $sheet->mergeCells("A{$row}:I{$row}");
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
            $row++;
        }

        // Summary Total Row
        $sheet->setCellValue("A{$row}", 'Total Hours');
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("G{$row}", number_format($totalHours, 2));
        $sheet->setCellValue("H{$row}", '');
        $sheet->setCellValue("I{$row}", '');

        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEF2FF']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(24);

        // Borders
        $sheet->getStyle("A4:I{$row}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        // Auto column widths
        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(40);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(25);

        $cleanDept = preg_replace('/[^A-Za-z0-9_\-]/', '_', $selectedDeptName);
        $cleanSuffix = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filter['file_suffix']);
        $fileName = "All_Employees_Tasks_{$cleanDept}_{$cleanSuffix}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
