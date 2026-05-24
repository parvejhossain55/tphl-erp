<?php
namespace Phase2;

use Controller;

class LandReportController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $projectFilter = in_array($roleId, [1, 3, 6]) ? "p.landman_id IS NOT NULL" : "p.landman_id = $userId";
        
        $from_date = $_GET['from_date'] ?? date('Y-m-01');
        $to_date = $_GET['to_date'] ?? date('Y-m-t');

        // Escape input parameters to prevent SQL injection
        $from_date_esc = $this->db->real_escape_string($from_date);
        $to_date_esc = $this->db->real_escape_string($to_date);

        // Fetch total active projects assigned to user
        $projectsCountSql = "SELECT COUNT(*) as total FROM projects p WHERE p.deleted_at IS NULL AND $projectFilter";
        $totalProjects = (int)($this->db->query($projectsCountSql)->fetch_assoc()['total'] ?? 0);

        // Fetch chronological activities (visibility study, bayana payments, registrations) within date range
        $activitiesSql = "
            SELECT * FROM (
                SELECT 
                    'visibility' as event_type,
                    lvs.created_at as event_date,
                    p.name as project_name,
                    NULL as landowner_name,
                    NULL as landowner_phone,
                    NULL as area,
                    NULL as area_unit,
                    NULL as amount,
                    CONCAT('Legal status: ', lvs.land_legal_doc_status, ', QC status: ', lvs.qc_approval_status) as details,
                    lvs.id as reference_id,
                    lvs.qc_approval_status as qc_status,
                    lvs.digital_survey_report_link as visibility_survey_link,
                    NULL as status,
                    NULL as status_date,
                    lvs.land_legal_doc_status as land_legal_doc_status
                FROM land_visibility_study lvs
                JOIN projects p ON lvs.project_id = p.id
                WHERE DATE(lvs.created_at) BETWEEN '$from_date_esc' AND '$to_date_esc'
                  AND p.deleted_at IS NULL AND $projectFilter

                UNION ALL

                SELECT 
                    'bayana' as event_type,
                    lb.payment_date as event_date,
                    p.name as project_name,
                    la.land_owner_name as landowner_name,
                    la.land_owner_phone as landowner_phone,
                    la.total_area as area,
                    la.area_unit as area_unit,
                    lb.payment_amount as amount,
                    'Bayana payment completed' as details,
                    la.id as reference_id,
                    NULL as qc_status,
                    NULL as visibility_survey_link,
                    la.bayana_status as status,
                    la.bayana_complete_date as status_date,
                    NULL as land_legal_doc_status
                FROM land_bayana_payments lb
                JOIN land_acquisitions la ON lb.land_acquisition_id = la.id
                JOIN projects p ON la.project_id = p.id
                WHERE DATE(lb.payment_date) BETWEEN '$from_date_esc' AND '$to_date_esc'
                  AND p.deleted_at IS NULL AND $projectFilter

                UNION ALL

                SELECT 
                    'registration' as event_type,
                    lr.registration_date as event_date,
                    p.name as project_name,
                    la.land_owner_name as landowner_name,
                    la.land_owner_phone as landowner_phone,
                    la.total_area as area,
                    la.area_unit as area_unit,
                    lr.registration_cost as amount,
                    'Land registration completed' as details,
                    la.id as reference_id,
                    NULL as qc_status,
                    NULL as visibility_survey_link,
                    la.registration_status as status,
                    la.registration_complete_date as status_date,
                    NULL as land_legal_doc_status
                FROM land_registrations lr
                JOIN land_acquisitions la ON lr.land_acquisition_id = la.id
                JOIN projects p ON la.project_id = p.id
                WHERE DATE(lr.registration_date) BETWEEN '$from_date_esc' AND '$to_date_esc'
                  AND p.deleted_at IS NULL AND $projectFilter
            ) AS activity_log
            ORDER BY event_date DESC
        ";
        $activities = $this->db->query($activitiesSql)->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/land_report/index', [
            'current_route' => 'con-land-monthly-reports',
            'page_title' => 'Land Progress Reports',
            'total_projects' => $totalProjects,
            'activities' => $activities,
            'from_date' => $from_date,
            'to_date' => $to_date
        ]);
    }
}
