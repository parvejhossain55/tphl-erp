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
                ual.id,
                ual.login_time,
                ual.last_active_time,
                TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time) AS active_minutes,
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

        $sql .= " ORDER BY dept.name ASC, ual.login_time DESC";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($paramTypes, ...$params);
        }

        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

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
            $deptSummary[$dName]['total_sessions']++;
            $deptSummary[$dName]['total_minutes'] += max(0, (int)$log['active_minutes']);
        }
        // Sort by dept name
        ksort($deptSummary);

        $this->view("hrm/user-activity-log", [
            "current_route"    => "user-activity-log",
            "page_title"       => "User Activity Log",
            "departments"      => $departments,
            "logs"             => $logs,
            "deptSummary"      => array_values($deptSummary),
            "filterDeptId"     => $filterDeptId,
            "filterDateFrom"   => $filterDateFrom,
            "filterDateTo"     => $filterDateTo,
        ]);
    }
}
