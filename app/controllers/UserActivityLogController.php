<?php

class UserActivityLogController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Only role_id 1 (Admin) and 3 (Super Admin / Management) can access
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3])) {
            header("Location: " . $GLOBALS['base_url'] . "/home");
            exit;
        }

        // ── Filters from GET ──────────────────────────────────────────────────
        $filterDeptId = isset($_GET['department_id']) && $_GET['department_id'] !== ''
            ? (int)$_GET['department_id'] : null;

        $filterDateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== ''
            ? $_GET['date_from'] : null;

        $filterDateTo = isset($_GET['date_to']) && $_GET['date_to'] !== ''
            ? $_GET['date_to'] : null;

        // ── Fetch all departments for filter dropdown ──────────────────────────
        $deptResult = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        $departments = $deptResult ? $deptResult->fetch_all(MYSQLI_ASSOC) : [];

        // ── Build main activity query ─────────────────────────────────────────
        $sql = "
            SELECT
                MIN(ual.id) AS id,
                MIN(ual.login_time) AS login_time,
                MAX(ual.last_active_time) AS last_active_time,
                SUM(TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time)) AS active_minutes,
                COUNT(ual.id) AS session_count,
                u.full_name,
                u.email,
                r.name  AS role_name,
                dept.id   AS department_id,
                dept.name AS department_name,
                des.name  AS designation_name
            FROM user_activity_logs ual
            JOIN users u ON u.id = ual.user_id
            LEFT JOIN roles r   ON r.id  = u.role_id
            LEFT JOIN designations des ON des.id = u.designation_id
            LEFT JOIN departments dept ON dept.id = des.department_id
            WHERE 1 = 1
        ";

        $params      = [];
        $paramTypes  = '';

        if ($filterDeptId !== null) {
            $sql        .= " AND dept.id = ?";
            $params[]    = $filterDeptId;
            $paramTypes .= 'i';
        }

        if ($filterDateFrom !== null) {
            $sql        .= " AND DATE(ual.login_time) >= ?";
            $params[]    = $filterDateFrom;
            $paramTypes .= 's';
        }

        if ($filterDateTo !== null) {
            $sql        .= " AND DATE(ual.login_time) <= ?";
            $params[]    = $filterDateTo;
            $paramTypes .= 's';
        }

        $sql .= " GROUP BY DATE(ual.login_time), ual.user_id, u.full_name, u.email, r.name, dept.id, dept.name, des.name";
        $sql .= " ORDER BY dept.name ASC, DATE(ual.login_time) DESC, MIN(ual.login_time) DESC";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($paramTypes, ...$params);
        }

        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // ── Export Trigger ────────────────────────────────────────────────────
        if (isset($_GET['export'])) {
            $exportType = $_GET['export'];
            if ($exportType === 'excel') {
                $this->exportExcel($logs);
            } elseif ($exportType === 'pdf') {
                $this->exportPdf($logs);
            }
        }

        // ── Build department-wise summary ─────────────────────────────────────
        $deptSummary = [];
        foreach ($logs as $log) {
            $dName = $log['department_name'] ?: 'Unknown';
            if (!isset($deptSummary[$dName])) {
                $deptSummary[$dName] = [
                    'dept_name'         => $dName,
                    'total_sessions'    => 0,
                    'total_minutes'     => 0,
                ];
            }
            $deptSummary[$dName]['total_sessions'] += (int)($log['session_count'] ?? 1);
            $deptSummary[$dName]['total_minutes'] += max(0, (int)$log['active_minutes']);
        }
        // Sort by dept name
        ksort($deptSummary);

        // ── Pagination Logic ──────────────────────────────────────────────────
        $showAll = isset($_GET['show_all']) && $_GET['show_all'] == '1';
        $totalRecords = count($logs);
        $perPage = 15;
        $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $perPage) : 1;
        $currentPage = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;

        $displayLogs = $logs;
        if (!$showAll) {
            $offset = ($currentPage - 1) * $perPage;
            $displayLogs = array_slice($logs, $offset, $perPage);
        }

        $this->view("hrm/user-activity-log", [
            "current_route"    => "user-activity-log",
            "page_title"       => "User Activity Log",
            "departments"      => $departments,
            "logs"             => $displayLogs,
            "rawLogsCount"     => $totalRecords,
            "deptSummary"      => array_values($deptSummary),
            "filterDeptId"     => $filterDeptId,
            "filterDateFrom"   => $filterDateFrom,
            "filterDateTo"     => $filterDateTo,
            "currentPage"      => $currentPage,
            "totalPages"       => $totalPages,
            "showAll"          => $showAll,
            "perPage"          => $perPage
        ]);
    }

    // ── AJAX: Get employees by department ────────────────────────────────────
    public function employeesByDept()
    {
        header('Content-Type: application/json');

        $deptId = isset($_GET['dept_id']) && $_GET['dept_id'] !== ''
            ? (int)$_GET['dept_id'] : null;

        if ($deptId === null) {
            echo json_encode([]);
            exit;
        }

        $sql = "
            SELECT DISTINCT u.id, u.full_name, u.email, e.employee_id
            FROM users u
            LEFT JOIN designations des ON des.id = u.designation_id
            LEFT JOIN departments dept ON dept.id = des.department_id
            LEFT JOIN employees e  ON e.id = u.employee_id
            WHERE des.department_id = ?
            ORDER BY u.full_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $deptId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode($result);
        exit;
    }

    // ── AJAX: Employee time summary ───────────────────────────────────────────
    public function employeeSummary()
    {
        header('Content-Type: application/json');

        $userId = isset($_GET['user_id']) && $_GET['user_id'] !== ''
            ? (int)$_GET['user_id'] : null;

        if ($userId === null) {
            echo json_encode(['error' => 'user_id required']);
            exit;
        }

        $dateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== ''
            ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) && $_GET['date_to'] !== ''
            ? $_GET['date_to'] : null;

        // ── Overall summary ──────────────────────────────────────────────────
        $sqlSummary = "
            SELECT
                u.full_name,
                u.email,
                COALESCE(dept.name, 'Unknown')  AS department_name,
                COALESCE(des.name,  '—')         AS designation_name,
                COUNT(ual.id)                    AS total_sessions,
                COALESCE(SUM(
                    TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time)
                ), 0)                            AS total_minutes
            FROM user_activity_logs ual
            JOIN users u ON u.id = ual.user_id
            LEFT JOIN designations des  ON des.id  = u.designation_id
            LEFT JOIN departments  dept ON dept.id = des.department_id
            WHERE ual.user_id = ?
        ";

        $params     = [$userId];
        $paramTypes = 'i';

        if ($dateFrom) {
            $sqlSummary .= " AND DATE(ual.login_time) >= ?";
            $params[]    = $dateFrom;
            $paramTypes .= 's';
        }
        if ($dateTo) {
            $sqlSummary .= " AND DATE(ual.login_time) <= ?";
            $params[]    = $dateTo;
            $paramTypes .= 's';
        }

        $sqlSummary .= " GROUP BY u.id, u.full_name, u.email, dept.name, des.name";

        $stmt = $this->db->prepare($sqlSummary);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$summary) {
            echo json_encode(['error' => 'No data found']);
            exit;
        }

        // ── Date-wise breakdown ──────────────────────────────────────────────
        $sqlDaily = "
            SELECT
                DATE(ual.login_time)             AS log_date,
                COUNT(ual.id)                    AS sessions,
                MIN(ual.login_time)              AS first_login,
                MAX(ual.last_active_time)        AS last_active,
                COALESCE(SUM(
                    TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time)
                ), 0)                            AS active_minutes
            FROM user_activity_logs ual
            WHERE ual.user_id = ?
        ";

        $params2     = [$userId];
        $paramTypes2 = 'i';

        if ($dateFrom) {
            $sqlDaily   .= " AND DATE(ual.login_time) >= ?";
            $params2[]   = $dateFrom;
            $paramTypes2 .= 's';
        }
        if ($dateTo) {
            $sqlDaily   .= " AND DATE(ual.login_time) <= ?";
            $params2[]   = $dateTo;
            $paramTypes2 .= 's';
        }

        $sqlDaily .= " GROUP BY DATE(ual.login_time) ORDER BY log_date DESC";

        $stmt2 = $this->db->prepare($sqlDaily);
        $stmt2->bind_param($paramTypes2, ...$params2);
        $stmt2->execute();
        $daily = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        $totalMins = (int)$summary['total_minutes'];
        $sessions  = (int)$summary['total_sessions'];

        echo json_encode([
            'employee' => [
                'full_name'        => $summary['full_name'],
                'email'            => $summary['email'],
                'department_name'  => $summary['department_name'],
                'designation_name' => $summary['designation_name'],
            ],
            'total_sessions' => $sessions,
            'total_minutes'  => $totalMins,
            'total_hours'    => intdiv($totalMins, 60),
            'total_mins_rem' => $totalMins % 60,
            'avg_minutes'    => $sessions > 0 ? intdiv($totalMins, $sessions) : 0,
            'daily'          => $daily,
        ]);
        exit;
    }

    private function exportExcel($logs)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];

        $headers = ['SL', 'User Name', 'Email', 'Department', 'Designation', 'Role', 'First Login', 'Last Active', 'Active Duration'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        $rowNum = 2;
        $serial = 1;
        foreach ($logs as $log) {
            $activeMin = max(0, (int)$log['active_minutes']);
            $durationStr = $activeMin > 0 ? intdiv($activeMin, 60) . 'h ' . ($activeMin % 60) . 'm' : '0m';
            
            $loginTime  = date('d M Y, h:i A', strtotime($log['login_time']));
            $lastActive = $log['last_active_time'] ? date('d M Y, h:i A', strtotime($log['last_active_time'])) : '—';

            $sheet->setCellValue('A' . $rowNum, $serial++);
            $sheet->setCellValue('B' . $rowNum, $log['full_name'] ?? '—');
            $sheet->setCellValue('C' . $rowNum, $log['email'] ?? '—');
            $sheet->setCellValue('D' . $rowNum, $log['department_name'] ?? '—');
            $sheet->setCellValue('E' . $rowNum, $log['designation_name'] ?? '—');
            $sheet->setCellValue('F' . $rowNum, $log['role_name'] ?? '—');
            $sheet->setCellValue('G' . $rowNum, $loginTime);
            $sheet->setCellValue('H' . $rowNum, $lastActive);
            $sheet->setCellValue('I' . $rowNum, $durationStr);
            
            $rowNum++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="User_Activity_Log_' . date('Y-m-d_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPdf($logs)
    {
        $dompdf = new \Dompdf\Dompdf([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

        $html = '
        <html>
        <head>
            <style>
                body { font-family: sans-serif; font-size: 11px; color: #333; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h2 { margin: 0; color: #1F4E79; }
                .header p { margin: 5px 0 0 0; font-size: 12px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
                th { background-color: #1F4E79; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .duration { font-weight: bold; color: #2e7d32; }
                .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 9px; color: #999; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>User Activity Log</h2>
                <p>Generated on ' . date('d M Y, h:i A') . '</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">SL</th>
                        <th style="width: 18%;">User Name</th>
                        <th style="width: 15%;">Email</th>
                        <th style="width: 12%;">Department</th>
                        <th style="width: 12%;">Designation</th>
                        <th style="width: 10%;">Role</th>
                        <th style="width: 13%;">First Login</th>
                        <th style="width: 13%;">Last Active</th>
                        <th style="width: 10%;">Duration</th>
                    </tr>
                </thead>
                <tbody>';

        $serial = 1;
        if (empty($logs)) {
            $html .= '<tr><td colspan="9" style="text-align: center;">No activity records found.</td></tr>';
        } else {
            foreach ($logs as $log) {
                $activeMin = max(0, (int)$log['active_minutes']);
                $durationStr = $activeMin > 0 ? intdiv($activeMin, 60) . 'h ' . ($activeMin % 60) . 'm' : '0m';
                
                $loginTime  = date('d M Y, h:i A', strtotime($log['login_time']));
                $lastActive = $log['last_active_time'] ? date('d M Y, h:i A', strtotime($log['last_active_time'])) : '—';
                
                $html .= '
                <tr>
                    <td>' . $serial++ . '</td>
                    <td><b>' . htmlspecialchars($log['full_name'] ?? '—') . '</b></td>
                    <td>' . htmlspecialchars($log['email'] ?? '—') . '</td>
                    <td>' . htmlspecialchars($log['department_name'] ?? '—') . '</td>
                    <td>' . htmlspecialchars($log['designation_name'] ?? '—') . '</td>
                    <td>' . htmlspecialchars($log['role_name'] ?? '—') . '</td>
                    <td>' . $loginTime . '</td>
                    <td>' . $lastActive . '</td>
                    <td class="duration">' . $durationStr . '</td>
                </tr>';
            }
        }

        $html .= '
                </tbody>
            </table>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("User_Activity_Log_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
        exit;
    }
}
