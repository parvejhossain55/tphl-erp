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
        $projectFilter = in_array($roleId, [1, 3, 14, 15, 22, 33, 6, 30]) ? "1=1" : "p.landman_id = $userId";
        
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

    public function ledger() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Role guard: Only Land Officer (14), Accounts (6), Management (3), Admin (1)
        if (!in_array($roleId, [1, 3, 33, 6, 14])) {
            setError('Unauthorized access.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $projectFilter = in_array($roleId, [1, 3, 14, 15, 22, 33, 6, 30]) ? "1=1" : "p.landman_id = $userId";

        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $selectedAcqId = isset($_GET['land_acquisition_id']) && $_GET['land_acquisition_id'] !== '' ? (int)$_GET['land_acquisition_id'] : null;
        $from_date = isset($_GET['from_date']) && $_GET['from_date'] !== '' ? $_GET['from_date'] : null;
        $to_date = isset($_GET['to_date']) && $_GET['to_date'] !== '' ? $_GET['to_date'] : null;

        // 1. Fetch projects for dropdown filter (only projects that have land acquisitions)
        $projects = $this->db->query("
            SELECT DISTINCT p.id, p.name 
            FROM projects p
            JOIN land_acquisitions la ON la.project_id = p.id
            WHERE p.deleted_at IS NULL AND $projectFilter
            ORDER BY p.name ASC
        ")->fetch_all(MYSQLI_ASSOC);

        // 2. Fetch land acquisitions portfolio for dropdown filter based on selected project
        $acquisitionsFilterQuery = "
            SELECT la.id, la.land_owner_name, p.name as project_name
            FROM land_acquisitions la
            JOIN projects p ON la.project_id = p.id
            WHERE la.status != 'cancelled' AND $projectFilter
        ";
        if ($selectedProjectId) {
            $acquisitionsFilterQuery .= " AND la.project_id = $selectedProjectId";
        }
        $acquisitions = $this->db->query($acquisitionsFilterQuery)->fetch_all(MYSQLI_ASSOC);

        // 3. Fetch specific details if acquisitions are found
        $ledgerEntries = [];
        $totalValuation = 0;
        $totalReceived = 0;
        $totalSpent = 0;

        $acqIds = [];
        if ($selectedAcqId) {
            $acqIds[] = $selectedAcqId;
        } else {
            foreach ($acquisitions as $acq) {
                $acqIds[] = (int)$acq['id'];
            }
        }

        if (!empty($acqIds)) {
            $acqIdsStr = implode(',', $acqIds);

            // Fetch total valuation (budget) for matching acquisitions
            $valRes = $this->db->query("SELECT SUM(total_amount) as total FROM land_acquisitions WHERE id IN ($acqIdsStr)");
            $totalValuation = (float)($valRes ? $valRes->fetch_assoc()['total'] ?? 0 : 0);

            // Fetch Requisitions (inflow when status = Paid)
            $requisitions = $this->db->query("
                SELECT lfr.*, la.land_owner_name, p.name as project_name, u.full_name as requester_name
                FROM land_fund_requisitions lfr
                JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
                JOIN projects p ON la.project_id = p.id
                LEFT JOIN users u ON lfr.requested_by = u.id
                WHERE lfr.land_acquisition_id IN ($acqIdsStr)
                ORDER BY lfr.created_at ASC
            ")->fetch_all(MYSQLI_ASSOC);

            // Fetch Bayana Payments (outflow/expense)
            $bayanas = $this->db->query("
                SELECT lb.*, la.land_owner_name, p.name as project_name, u.full_name as creator_name
                FROM land_bayana_payments lb
                JOIN land_acquisitions la ON lb.land_acquisition_id = la.id
                JOIN projects p ON la.project_id = p.id
                LEFT JOIN users u ON lb.created_by = u.id
                WHERE lb.land_acquisition_id IN ($acqIdsStr)
                ORDER BY lb.payment_date ASC
            ")->fetch_all(MYSQLI_ASSOC);

            // Fetch Registrations (outflow/expense)
            $registrations = $this->db->query("
                SELECT lr.*, la.land_owner_name, p.name as project_name, u.full_name as creator_name
                FROM land_registrations lr
                JOIN land_acquisitions la ON lr.land_acquisition_id = la.id
                JOIN projects p ON la.project_id = p.id
                LEFT JOIN users u ON lr.created_by = u.id
                WHERE lr.land_acquisition_id IN ($acqIdsStr)
                ORDER BY lr.registration_date ASC
            ")->fetch_all(MYSQLI_ASSOC);

            // Fetch General Fund Requisitions (department_id = 18 for Legal & Land)
            $generalRequisitions = [];
            $generalExpenses = [];
            if (!$selectedAcqId) {
                $genReqQuery = "
                    SELECT fr.*, p.name as project_name, u.full_name as requester_name
                    FROM fund_requisitions fr
                    LEFT JOIN projects p ON fr.project_id = p.id
                    LEFT JOIN users u ON fr.requested_by = u.id
                    WHERE (fr.department_id = 18 OR u.role_id IN (13, 14)) AND fr.status = 'paid'
                ";
                if ($selectedProjectId) {
                    $genReqQuery .= " AND fr.project_id = $selectedProjectId";
                }
                if (!in_array($roleId, [1, 3, 33, 6, 30])) {
                    $genReqQuery .= " AND (fr.requested_by = $userId OR (fr.project_id IS NOT NULL AND fr.project_id IN (SELECT id FROM projects WHERE landman_id = $userId)))";
                }
                $genReqQuery .= " ORDER BY fr.created_at ASC";
                $generalRequisitions = $this->db->query($genReqQuery)->fetch_all(MYSQLI_ASSOC);

                // Fetch General Expenses (only for petty cash to avoid double counting direct general expenses)
                $genExpQuery = "
                    SELECT fre.*, fr.project_id as req_project_id, p.name as project_name, u.full_name as creator_name
                    FROM fund_requisition_expenses fre
                    JOIN fund_requisitions fr ON fre.fund_requisition_id = fr.id
                    LEFT JOIN projects p ON fre.project_id = p.id
                    LEFT JOIN users u ON fre.created_by = u.id
                    LEFT JOIN users u_req ON fr.requested_by = u_req.id
                    WHERE (fr.department_id = 18 OR u_req.role_id IN (13, 14)) AND fr.requisition_type = 'petty_cash'
                ";
                if ($selectedProjectId) {
                    $genExpQuery .= " AND fre.project_id = $selectedProjectId";
                }
                if (!in_array($roleId, [1, 3, 33, 6, 30])) {
                    $genExpQuery .= " AND (fre.created_by = $userId OR (fre.project_id IS NOT NULL AND fre.project_id IN (SELECT id FROM projects WHERE landman_id = $userId)))";
                }
                $genExpQuery .= " ORDER BY fre.created_at ASC";
                $generalExpenses = $this->db->query($genExpQuery)->fetch_all(MYSQLI_ASSOC);
            }

            // Combine into single ledger
            foreach ($generalRequisitions as $fr) {
                // fr.status is guaranteed to be 'paid' due to SQL filter
                $date = $fr['paid_at'];
                $isPetty = ($fr['requisition_type'] === 'petty_cash');

                if ($isPetty) {
                    // Petty cash is inflow when paid to department
                    $inflow = (float)$fr['amount'];
                    $outflow = 0;
                } else {
                    // General direct requisition is both inflow and outflow on the same date (net balance change = 0)
                    $inflow = (float)$fr['amount'];
                    $outflow = (float)$fr['amount'];
                }

                $ledgerEntries[] = [
                    'date' => $date,
                    'type' => 'requisition',
                    'requisition_type' => 'General (' . htmlspecialchars($fr['category']) . ')',
                    'ref_id' => $fr['id'],
                    'landowner' => 'N/A',
                    'project_name' => $fr['project_name'] ?? 'Office General',
                    'description' => "General Fund Requisition - Category: " . htmlspecialchars($fr['category']) . " - Purpose: " . htmlspecialchars($fr['purpose']),
                    'status' => 'Paid',
                    'inflow' => $inflow,
                    'outflow' => $outflow,
                    'req_amount' => (float)$fr['amount'],
                    'details' => [
                        'pay_method' => $fr['payment_method'] ?? 'N/A',
                        'txn_no' => 'N/A',
                        'requester' => $fr['requester_name'] ?? 'N/A'
                    ]
                ];

                if ($isPetty) {
                    $totalReceived += (float)$fr['amount'];
                } else {
                    $totalReceived += (float)$fr['amount'];
                    $totalSpent += (float)$fr['amount'];
                }
            }

            foreach ($generalExpenses as $fe) {
                $ledgerEntries[] = [
                    'date' => $fe['created_at'],
                    'type' => 'general_expense',
                    'requisition_type' => 'General Expense',
                    'ref_id' => $fe['id'],
                    'landowner' => 'N/A',
                    'project_name' => $fe['project_name'] ?? 'Office General',
                    'description' => "General Expense spent for " . htmlspecialchars($fe['purpose']) . " (REQ-" . htmlspecialchars($fe['fund_requisition_id']) . ")",
                    'status' => 'Paid',
                    'inflow' => 0,
                    'outflow' => (float)$fe['amount'],
                    'req_amount' => 0,
                    'details' => [
                        'pay_method' => 'N/A',
                        'txn_no' => 'REQ-' . htmlspecialchars($fe['fund_requisition_id']),
                        'requester' => $fe['creator_name'] ?? 'N/A'
                    ]
                ];
                $totalSpent += (float)$fe['amount'];
            }

            foreach ($requisitions as $r) {
                $isPaid = ($r['accounts_status'] === 'Paid');
                $date = $isPaid ? $r['accounts_paid_at'] : $r['created_at'];

                $ledgerEntries[] = [
                    'date' => $date,
                    'type' => 'requisition',
                    'requisition_type' => $r['requisition_type'],
                    'ref_id' => $r['id'],
                    'landowner' => $r['land_owner_name'],
                    'project_name' => $r['project_name'],
                    'description' => "Requisition for " . htmlspecialchars($r['requisition_type']) . " - Purpose: " . htmlspecialchars($r['purpose']),
                    'status' => $r['accounts_status'] ?? ($r['mgmt_status'] ?? 'Pending'),
                    'inflow' => $isPaid ? (float)$r['requested_amount'] : 0,
                    'outflow' => 0,
                    'req_amount' => (float)$r['requested_amount'],
                    'details' => [
                        'pay_method' => $r['payment_method'] ?? 'N/A',
                        'txn_no' => $r['transaction_no'] ?? 'N/A',
                        'requester' => $r['requester_name'] ?? 'N/A'
                    ]
                ];

                if ($isPaid) {
                    $totalReceived += (float)$r['requested_amount'];
                }
            }

            foreach ($bayanas as $b) {
                $ledgerEntries[] = [
                    'date' => $b['payment_date'] . ' 12:00:00',
                    'type' => 'bayana_payment',
                    'requisition_type' => 'Bayana',
                    'ref_id' => $b['id'],
                    'landowner' => $b['land_owner_name'],
                    'project_name' => $b['project_name'],
                    'description' => "Bayana Paid to " . htmlspecialchars($b['land_owner_name']) . ($b['remarks'] ? " (" . htmlspecialchars($b['remarks']) . ")" : ""),
                    'status' => 'Paid',
                    'inflow' => 0,
                    'outflow' => (float)$b['payment_amount'],
                    'req_amount' => 0,
                    'details' => [
                        'pay_method' => $b['payment_method'] ?? 'N/A',
                        'txn_no' => $b['transaction_no'] ?? 'N/A',
                        'requester' => $b['creator_name'] ?? 'N/A'
                    ]
                ];
                $totalSpent += (float)$b['payment_amount'];
            }

            foreach ($registrations as $reg) {
                $ledgerEntries[] = [
                    'date' => $reg['registration_date'] . ' 12:00:00',
                    'type' => 'registration_cost',
                    'requisition_type' => 'Registration',
                    'ref_id' => $reg['id'],
                    'landowner' => $reg['land_owner_name'],
                    'project_name' => $reg['project_name'],
                    'description' => "Registration Deed #" . htmlspecialchars($reg['registration_no']) . " at " . htmlspecialchars($reg['sub_registry_office'] ?? '') . ($reg['remarks'] ? " (" . htmlspecialchars($reg['remarks']) . ")" : ""),
                    'status' => 'Paid',
                    'inflow' => 0,
                    'outflow' => (float)$reg['registration_cost'],
                    'req_amount' => 0,
                    'details' => [
                        'pay_method' => 'N/A',
                        'txn_no' => $reg['registration_no'],
                        'requester' => $reg['creator_name'] ?? 'N/A'
                    ]
                ];
                $totalSpent += (float)$reg['registration_cost'];
            }

            // Sort by date chronologically
            usort($ledgerEntries, function($a, $b) {
                return strtotime($a['date']) <=> strtotime($b['date']);
            });

            // Date filtering & running balance calculation
            $openingBalance = 0;
            $rangeReceived = 0;
            $rangeSpent = 0;
            $filteredEntries = [];

            foreach ($ledgerEntries as $entry) {
                $entryDate = date('Y-m-d', strtotime($entry['date']));
                if ($from_date && $entryDate < $from_date) {
                    if ($entry['status'] === 'Paid' || $entry['type'] !== 'requisition') {
                        $openingBalance += ($entry['inflow'] - $entry['outflow']);
                    }
                } elseif ((!$from_date || $entryDate >= $from_date) && (!$to_date || $entryDate <= $to_date)) {
                    $filteredEntries[] = $entry;
                    if ($entry['status'] === 'Paid' || $entry['type'] !== 'requisition') {
                        $rangeReceived += $entry['inflow'];
                        $rangeSpent += $entry['outflow'];
                    }
                }
            }

            // Recalculate running balance starting from opening balance
            $runningBalance = $openingBalance;
            foreach ($filteredEntries as &$entry) {
                if ($entry['status'] === 'Paid' || $entry['type'] !== 'requisition') {
                    $runningBalance += ($entry['inflow'] - $entry['outflow']);
                }
                $entry['running_balance'] = $runningBalance;
            }
            unset($entry);

            $ledgerEntries = $filteredEntries;
            $totalReceived = $rangeReceived;
            $totalSpent = $rangeSpent;
        }

        $balanceCash = $openingBalance + $totalReceived - $totalSpent;

        if (isset($_GET['download_pdf']) && $_GET['download_pdf'] == 1) {
            ob_start();
            $this->view('phase2/land_report/ledger_pdf', [
                'page_title' => 'Land Ledger Statement',
                'projects' => $projects,
                'acquisitions' => $acquisitions,
                'ledgerEntries' => $ledgerEntries,
                'totalValuation' => $totalValuation,
                'totalReceived' => $totalReceived,
                'totalSpent' => $totalSpent,
                'balanceCash' => $balanceCash,
                'openingBalance' => $openingBalance,
                'selectedProjectId' => $selectedProjectId,
                'selectedAcqId' => $selectedAcqId,
                'from_date' => $from_date,
                'to_date' => $to_date
            ], false);
            $html = ob_get_clean();

            require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream("land_ledger_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
            exit;
        }

        $this->view('phase2/land_report/ledger', [
            'current_route' => 'con-land-ledger',
            'page_title' => 'Land Ledger Statement',
            'projects' => $projects,
            'acquisitions' => $acquisitions,
            'ledgerEntries' => $ledgerEntries,
            'totalValuation' => $totalValuation,
            'totalReceived' => $totalReceived,
            'totalSpent' => $totalSpent,
            'balanceCash' => $balanceCash,
            'openingBalance' => $openingBalance,
            'selectedProjectId' => $selectedProjectId,
            'selectedAcqId' => $selectedAcqId,
            'from_date' => $from_date,
            'to_date' => $to_date
        ]);
    }
}
