<?php
namespace Phase2;

use Controller;

class Con_MonthlyWorkReportController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (!in_array($roleId, [16, 18, 21])) {
            setError('Unauthorized. Only Architect, Draftsman, or Structural Designer can access this report.');
            header("Location: " . $GLOBALS['base_url'] . "/home");
            exit;
        }

        // Parse filter inputs
        $filterType = $_GET['filter_type'] ?? 'monthly';
        $selectedMonth = $_GET['month'] ?? date('Y-m');
        $selectedYear = $_GET['year'] ?? date('Y');
        $selectedProjectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

        // Date calculations based on period type
        if ($filterType === 'yearly') {
            $startDate = $selectedYear . '-01-01 00:00:00';
            $endDate   = $selectedYear . '-12-31 23:59:59';
            $reportingPeriodText = $selectedYear;
            $pageTitle = 'Yearly Work Report - ' . $selectedYear;
        } else {
            $startDate = $selectedMonth . '-01 00:00:00';
            $endDate   = date('Y-m-t 23:59:59', strtotime($startDate));
            $reportingPeriodText = date('F Y', strtotime($startDate));
            $pageTitle = 'Monthly Work Report - ' . date('F Y', strtotime($startDate));
        }

        // Fetch assigned projects OR projects with existing user activity records
        $projSql = "
            SELECT DISTINCT p.id, p.name 
            FROM projects p
            LEFT JOIN architect_engineering ae ON ae.project_id = p.id AND ae.user_id = ?
            LEFT JOIN project_drawings pd ON pd.project_id = p.id AND pd.user_id = ?
            LEFT JOIN project_structural_designs psd ON psd.project_id = p.id AND psd.created_by = ?
            WHERE (p.architect_id = ? OR p.draftsman_id = ? OR p.struct_engineer_id = ? OR p.pm_id = ? OR p.created_by = ? 
                   OR ae.id IS NOT NULL OR pd.id IS NOT NULL OR psd.id IS NOT NULL)
              AND p.deleted_at IS NULL
            ORDER BY p.name ASC
        ";
        $projStmt = $this->db->prepare($projSql);
        $projStmt->bind_param('iiiiiiii', $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
        $projStmt->execute();
        $assignedProjects = $projStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $records = [];
        $roleName = '';

        if ($roleId === 18) {
            $roleName = 'Architect';
            // Query architect engineering records
            $sql = "
                SELECT ae.*, p.name AS project_name, u.full_name AS user_name
                FROM architect_engineering ae
                LEFT JOIN projects p ON ae.project_id = p.id
                LEFT JOIN users u ON ae.user_id = u.id
                WHERE (ae.user_id = ? OR ae.created_by = ?)
                  AND ae.created_at BETWEEN ? AND ?
            ";
            if ($selectedProjectId > 0) {
                $sql .= " AND ae.project_id = ?";
            }
            $sql .= " ORDER BY ae.id DESC";

            $stmt = $this->db->prepare($sql);
            if ($selectedProjectId > 0) {
                $stmt->bind_param('iisss', $userId, $userId, $startDate, $endDate, $selectedProjectId);
            } else {
                $stmt->bind_param('iiss', $userId, $userId, $startDate, $endDate);
            }
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } elseif ($roleId === 16) {
            $roleName = 'Draftsman';
            // Query draftsman drawings
            $sql = "
                SELECT d.*, p.name AS project_name, dt.name AS design_type_name, u.full_name AS internal_designer_name
                FROM project_drawings d
                LEFT JOIN projects p ON d.project_id = p.id
                LEFT JOIN design_types dt ON d.design_type_id = dt.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE (d.user_id = ? OR d.created_by = ?)
                  AND d.created_at BETWEEN ? AND ?
            ";
            if ($selectedProjectId > 0) {
                $sql .= " AND d.project_id = ?";
            }
            $sql .= " ORDER BY d.id DESC";

            $stmt = $this->db->prepare($sql);
            if ($selectedProjectId > 0) {
                $stmt->bind_param('iisss', $userId, $userId, $startDate, $endDate, $selectedProjectId);
            } else {
                $stmt->bind_param('iiss', $userId, $userId, $startDate, $endDate);
            }
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } elseif ($roleId === 21) {
            $roleName = 'Structural Designer';
            // Query structural designs
            $sql = "
                SELECT sd.*, p.name AS project_name, u.full_name AS creator_name
                FROM project_structural_designs sd
                LEFT JOIN projects p ON sd.project_id = p.id
                LEFT JOIN users u ON sd.created_by = u.id
                WHERE sd.created_by = ?
                  AND sd.created_at BETWEEN ? AND ?
            ";
            if ($selectedProjectId > 0) {
                $sql .= " AND sd.project_id = ?";
            }
            $sql .= " ORDER BY sd.id DESC";

            $stmt = $this->db->prepare($sql);
            if ($selectedProjectId > 0) {
                $stmt->bind_param('isss', $userId, $startDate, $endDate, $selectedProjectId);
            } else {
                $stmt->bind_param('iss', $userId, $startDate, $endDate);
            }
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/monthly_report/monthly-work-report', [
            'current_route'        => 'con-monthly-work-report',
            'records'              => $records,
            'roleName'             => $roleName,
            'roleId'               => $roleId,
            'filterType'           => $filterType,
            'selectedMonth'        => $selectedMonth,
            'selectedYear'         => $selectedYear,
            'selectedProjectId'    => $selectedProjectId,
            'assignedProjects'     => $assignedProjects,
            'reportingPeriodText'  => $reportingPeriodText,
            'page_title'           => $pageTitle,
        ]);
    }
}
