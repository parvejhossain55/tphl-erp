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

        // Get selected month (default: current month)
        $selectedMonth = $_GET['month'] ?? date('Y-m');
        
        $startDate = $selectedMonth . '-01 00:00:00';
        $endDate   = date('Y-m-t 23:59:59', strtotime($startDate));

        $records = [];
        $roleName = '';

        if ($roleId === 18) {
            $roleName = 'Architect';
            // Query architect engineering records
            $stmt = $this->db->prepare("
                SELECT ae.*, p.name AS project_name, u.full_name AS user_name
                FROM architect_engineering ae
                LEFT JOIN projects p ON ae.project_id = p.id
                LEFT JOIN users u ON ae.user_id = u.id
                WHERE (ae.user_id = ? OR ae.created_by = ?)
                  AND ae.created_at BETWEEN ? AND ?
                ORDER BY ae.id DESC
            ");
            $stmt->bind_param('iiss', $userId, $userId, $startDate, $endDate);
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } elseif ($roleId === 16) {
            $roleName = 'Draftsman';
            // Query draftsman drawings
            $stmt = $this->db->prepare("
                SELECT d.*, p.name AS project_name, dt.name AS design_type_name, u.full_name AS internal_designer_name
                FROM project_drawings d
                LEFT JOIN projects p ON d.project_id = p.id
                LEFT JOIN design_types dt ON d.design_type_id = dt.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE (d.user_id = ? OR d.created_by = ?)
                  AND d.created_at BETWEEN ? AND ?
                ORDER BY d.id DESC
            ");
            $stmt->bind_param('iiss', $userId, $userId, $startDate, $endDate);
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } elseif ($roleId === 21) {
            $roleName = 'Structural Designer';
            // Query structural designs
            $stmt = $this->db->prepare("
                SELECT sd.*, p.name AS project_name, u.full_name AS creator_name
                FROM project_structural_designs sd
                LEFT JOIN projects p ON sd.project_id = p.id
                LEFT JOIN users u ON sd.created_by = u.id
                WHERE sd.created_by = ?
                  AND sd.created_at BETWEEN ? AND ?
                ORDER BY sd.id DESC
            ");
            $stmt->bind_param('iss', $userId, $startDate, $endDate);
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/monthly_report/monthly-work-report', [
            'current_route'  => 'con-monthly-work-report',
            'records'        => $records,
            'roleName'       => $roleName,
            'roleId'         => $roleId,
            'selectedMonth'  => $selectedMonth,
            'page_title'     => 'Monthly Work Report - ' . date('F Y', strtotime($startDate)),
        ]);
    }
}
