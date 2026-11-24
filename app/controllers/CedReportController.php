<?php
class CedReportController extends Controller
{
    protected $db;
    protected $followUpModel;
    protected $userModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->followUpModel = new FollowUp($db);
        $this->userModel = new User($db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Permission check
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isManagement = in_array($roleId, [1, 3, 33, 34, 35, 38]); // Including Chief Officer, Management Viewer & SEAL CEO
        
        if (function_exists('isClientsCommunicated') && isClientsCommunicated()) {
            header("Location: " . $GLOBALS['base_url'] . "/error-403");
            exit;
        }

        if (!hasPermission("CED Manage") && !$isManagement && !in_array($roleId, [7, 39], true)) {
            header("Location: " . $GLOBALS['base_url'] . "/error-403");
            exit;
        }
    }

    public function index()
    {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $isManagement = in_array($roleId, [1, 3, 33, 34, 35, 38]);

        // Default Agent ID (Self if CED role, otherwise null)
        $defaultAgentId = ($roleId === 7) ? $userId : null;
        
        $agentId = isset($_GET['agent_id']) && $_GET['agent_id'] !== '' ? (int)$_GET['agent_id'] : $defaultAgentId;

        // Report Mode Logic
        $reportMode = $_GET['report_mode'] ?? 'custom';
        $reportMonth = $_GET['report_month'] ?? date('m');
        $reportYear = $_GET['report_year'] ?? date('Y');

        if ($reportMode === 'daily') {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
        } elseif ($reportMode === 'monthly') {
            // first day of selected month/year
            $startDate = date('Y-m-01', strtotime("$reportYear-$reportMonth-01"));
            $endDate = date('Y-m-t', strtotime("$reportYear-$reportMonth-01"));
        } elseif ($reportMode === 'yearly') {
            $startDate = date('Y-01-01', strtotime("$reportYear-01-01"));
            $endDate = date('Y-12-31', strtotime("$reportYear-01-01"));
        } else {
            // custom
            $defaultStartDate = date('Y-m-01');
            $defaultEndDate = date('Y-m-t');
            $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
            $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;
        }

        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(200, (int)($_GET['per_page'] ?? 50)));

        // Fetch Data (paginated + SQL aggregated summary/breakdown)
        $cedEmployees = $this->userModel->getCedEmployees();
        $reportData = $this->followUpModel->getCedActivityReport($agentId, $startDate, $endDate, $page, $perPage, $reportMode);

        $summary = $reportData['summary'];
        $details = $reportData['details'];
        $breakdownCounts = $reportData['breakdown_counts'] ?? [];
        $pagination = $reportData['pagination'] ?? [
            'page' => $page,
            'per_page' => $perPage,
            'total_records' => count($details),
            'total_pages' => 1
        ];

        // Generate Breakdown from lightweight SQL counts
        $breakdownData = [];
        if ($reportMode === 'monthly') {
            $daysInMonth = date('t', strtotime($startDate));
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dayStr = sprintf("%02d", $i);
                $breakdownData[$dayStr] = [
                    'label' => "$dayStr " . date('M', strtotime($startDate)),
                    'count' => (int)($breakdownCounts[$dayStr] ?? 0)
                ];
            }
        } elseif ($reportMode === 'yearly') {
            for ($i = 1; $i <= 12; $i++) {
                $monthNum = sprintf("%02d", $i);
                $monthName = date('F', mktime(0, 0, 0, $i, 10));
                $breakdownData[$monthNum] = [
                    'label' => $monthName,
                    'count' => (int)($breakdownCounts[$monthNum] ?? 0)
                ];
            }
        }

        $agentInfo = null;
        if ($agentId) {
            $agentInfo = $this->db->query("
                SELECT 
                    e.full_name, 
                    e.employee_id, 
                    e.office_branch,
                    dep.name as department_name,
                    des.name as designation_name
                FROM employees e
                LEFT JOIN departments dep ON e.department_id = dep.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE e.user_id = $agentId
            ")->fetch_assoc();
        }

        $this->view("ced/ced-report", [
            "current_route" => "ced-report",
            "page_title" => "CED Activity Report",
            "isManagement" => $isManagement,
            "cedEmployees" => $cedEmployees,
            "agentId" => $agentId,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "summary" => $summary,
            "details" => $details,
            "agentInfo" => $agentInfo,
            "reportMode" => $reportMode,
            "reportMonth" => $reportMonth,
            "reportYear" => $reportYear,
            "breakdownData" => $breakdownData,
            "pagination" => $pagination
        ]);
    }

    public function centralReport()
    {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $isManagement = in_array($roleId, [1, 3, 33, 34, 35, 38]);

        // Filters
        $projectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $statusFilter = $_GET['status_filter'] ?? 'all';
        $agentId = isset($_GET['agent_id']) && $_GET['agent_id'] !== '' ? (int)$_GET['agent_id'] : null;
        $search = trim($_GET['search'] ?? '');
        $startDate = !empty($_GET['start_date']) ? trim($_GET['start_date']) : null;
        $endDate = !empty($_GET['end_date']) ? trim($_GET['end_date']) : null;

        // Fetch Dropdown options
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $cedEmployees = $this->userModel->getCedEmployees();

        // Build WHERE clause
        $whereClauses = ["lb.deleted_at IS NULL"];
        if ($projectId) {
            $whereClauses[] = "lb.project_id = " . (int)$projectId;
        }
        if ($agentId) {
            $whereClauses[] = "fu.last_followup_agent_id = " . (int)$agentId;
        }
        if (!empty($search)) {
            $escapedSearch = $this->db->real_escape_string($search);
            $whereClauses[] = "(l.full_name LIKE '%$escapedSearch%' 
                                OR l.phone LIKE '%$escapedSearch%' 
                                OR l.phone_number LIKE '%$escapedSearch%' 
                                OR f.flat_name LIKE '%$escapedSearch%' 
                                OR p.name LIKE '%$escapedSearch%')";
        }
        if ($startDate && $endDate) {
            $esStartDate = $this->db->real_escape_string($startDate);
            $esEndDate = $this->db->real_escape_string($endDate);
            $whereClauses[] = "(pay.last_payment_date BETWEEN '$esStartDate' AND '$esEndDate' 
                                OR fu.last_followup_date BETWEEN '$esStartDate' AND '$esEndDate' 
                                OR lb.booking_date BETWEEN '$esStartDate' AND '$esEndDate')";
        } elseif ($startDate) {
            $esStartDate = $this->db->real_escape_string($startDate);
            $whereClauses[] = "(pay.last_payment_date >= '$esStartDate' 
                                OR fu.last_followup_date >= '$esStartDate' 
                                OR lb.booking_date >= '$esStartDate')";
        } elseif ($endDate) {
            $esEndDate = $this->db->real_escape_string($endDate);
            $whereClauses[] = "(pay.last_payment_date <= '$esEndDate' 
                                OR fu.last_followup_date <= '$esEndDate' 
                                OR lb.booking_date <= '$esEndDate')";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $sql = "
            SELECT 
                lb.id AS booking_id,
                lb.booking_date,
                p.id AS project_id,
                p.name AS project_name,
                f.id AS flat_id,
                f.flat_name AS unit_name,
                f.sqft AS unit_size,
                f.is_shop,
                f.shop_service_charge,
                f.development_charges,
                f.price_value AS total_price,
                COALESCE(f.land_share_value, 0) AS total_land_share,
                l.id AS lead_id,
                l.full_name AS client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) AS phone,
                l.email,
                COALESCE(pay.dev_charge_paid, 0) AS dev_charge_paid,
                COALESCE(pay.emi_paid, 0) AS emi_paid,
                COALESCE(pay.bulk_emi_discount, 0) AS bulk_emi_discount,
                pay.last_payment_date,
                pay.last_payment_amount,
                COALESCE(pay.total_emi_no, 36) AS total_emi_no,
                COALESCE(sched.matured_emi_count, 0) AS sched_matured_count,
                fu.last_followup_note,
                fu.last_followup_date,
                fu.activity_mode,
                fu.activity_reason,
                fu.last_followup_by
            FROM lead_bookings lb
            INNER JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN (
                SELECT 
                    booking_id,
                    SUM(CASE WHEN payment_type IN ('development_charge', 'shop_service_charge') AND verification_status = 'verified' THEN amount ELSE 0 END) AS dev_charge_paid,
                    SUM(CASE WHEN payment_type IN ('emi', 'emi_due_settlement') AND verification_status = 'verified' THEN amount ELSE 0 END) AS emi_paid,
                    SUM(CASE WHEN verification_status = 'verified' THEN bulk_emi_discount ELSE 0 END) AS bulk_emi_discount,
                    MAX(CASE WHEN verification_status = 'verified' AND payment_status IN ('paid', 'partially_paid') THEN payment_date ELSE NULL END) AS last_payment_date,
                    SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN verification_status = 'verified' AND payment_status IN ('paid', 'partially_paid') THEN amount ELSE NULL END ORDER BY payment_date DESC, id DESC SEPARATOR ','), ',', 1) AS last_payment_amount,
                    MAX(total_emi_no) AS total_emi_no
                FROM lead_payments
                WHERE is_trashed = 0
                GROUP BY booking_id
            ) pay ON pay.booking_id = lb.id
            LEFT JOIN (
                SELECT 
                    project_id,
                    COUNT(*) AS matured_emi_count
                FROM project_emi_schedule_items
                WHERE is_active = 1
                GROUP BY project_id
            ) sched ON sched.project_id = lb.project_id
            LEFT JOIN (
                SELECT 
                    ef.booking_id,
                    ef.note AS last_followup_note,
                    COALESCE(ef.activity_date, DATE(ef.created_at)) AS last_followup_date,
                    ef.activity_mode,
                    ef.activity_reason,
                    ef.added_by AS last_followup_agent_id,
                    u.full_name AS last_followup_by
                FROM emi_followups ef
                INNER JOIN (
                    SELECT booking_id, MAX(id) AS max_id
                    FROM emi_followups
                    GROUP BY booking_id
                ) latest ON ef.id = latest.max_id
                LEFT JOIN users u ON ef.added_by = u.id
            ) fu ON fu.booking_id = lb.id
            WHERE $whereSql
            ORDER BY lb.id DESC
        ";

        $result = $this->db->query($sql);
        $rawRows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $reportRows = [];
        $totalDevChargeSum = 0.0;
        $totalDevChargePaidSum = 0.0;
        $totalDevChargeDueSum = 0.0;
        $totalEmiSum = 0.0;
        $totalEmiPaidSum = 0.0;
        $totalEmiDueSum = 0.0;

        foreach ($rawRows as $row) {
            $totalPrice = (float)($row['total_price'] ?? 0);
            $landShare = (float)($row['total_land_share'] ?? 0);
            
            // Total Dev Charge (For shops: fixed shop_service_charge BDT + flat development_charges)
            if (!empty($row['is_shop'])) {
                $shopSvcAmount  = (float)($row['shop_service_charge'] ?? 0);
                $shopDevCharges = (float)($row['development_charges'] ?? 0);
                $devCharge = round($shopSvcAmount + $shopDevCharges, 2);
            } else {
                $devCharge = (float)($row['development_charges'] ?? 0);
            }
            
            $devPaid = (float)($row['dev_charge_paid'] ?? 0);
            $devDiscount = 0.0; // Default 0.00
            $devDue = max(0, round($devCharge - $devDiscount - $devPaid, 2));

            // Construction Fee / EMI calculations
            $constructionFee = max(0, round($totalPrice - ($landShare + $devCharge), 2));
            $totalEmiNo = (int)($row['total_emi_no'] ?? 0) > 0 ? (int)$row['total_emi_no'] : 36;
            $singleEmiAmount = ($totalEmiNo > 0 && $constructionFee > 0) ? round($constructionFee / $totalEmiNo, 2) : 0.0;
            $totalEmiAmount = $constructionFee;

            // EMI Asked Count strictly from Project-Level Active EMIs (0 if none active)
            $schedMatured = (int)($row['sched_matured_count'] ?? 0);
            $emiPaid = (float)($row['emi_paid'] ?? 0);
            $bulkDiscount = (float)($row['bulk_emi_discount'] ?? 0);

            $emiAskedCount = ($schedMatured > 0) ? min($totalEmiNo, $schedMatured) : 0;

            $totalAskedAmount = round($emiAskedCount * $singleEmiAmount, 2);
            $emiDue = max(0, round($totalAskedAmount - $emiPaid - $bulkDiscount, 2));
            $totalOutstanding = round($devDue + $emiDue, 2);

            // Follow-up status label
            $followupStatus = 'No Follow-up';
            if (!empty($row['activity_mode']) || !empty($row['activity_reason'])) {
                $followupStatus = trim(($row['activity_mode'] ?? '') . ' - ' . ($row['activity_reason'] ?? ''), ' -');
            } elseif (!empty($row['last_followup_note'])) {
                $followupStatus = 'Followed Up';
            }

            $processedRow = [
                'booking_id' => $row['booking_id'],
                'project_name' => $row['project_name'] ?? 'N/A',
                'unit_name' => $row['unit_name'] ?? 'N/A',
                'unit_size' => !empty($row['unit_size']) ? $row['unit_size'] . ' sft' : 'N/A',
                'client_name' => $row['client_name'] ?? 'N/A',
                'phone' => $row['phone'] ?? 'N/A',
                'email' => !empty($row['email']) ? $row['email'] : 'N/A',
                'total_dev_charge' => $devCharge,
                'dev_charge_paid' => $devPaid,
                'dev_charge_due' => $devDue,
                'dev_charge_discount' => $devDiscount,
                'single_emi_amount' => $singleEmiAmount,
                'emi_asked_count' => $emiAskedCount,
                'total_emi_amount' => $totalEmiAmount,
                'emi_paid' => $emiPaid,
                'emi_due' => $emiDue,
                'bulk_emi_discount' => $bulkDiscount,
                'total_outstanding' => $totalOutstanding,
                'last_payment_date' => !empty($row['last_payment_date']) ? date('d M Y', strtotime($row['last_payment_date'])) : 'No Payment',
                'last_payment_amount' => !empty($row['last_payment_amount']) ? (float)$row['last_payment_amount'] : 0.0,
                'last_followup_note' => !empty($row['last_followup_note']) ? $row['last_followup_note'] : 'N/A',
                'last_followup_date' => !empty($row['last_followup_date']) ? date('d M Y', strtotime($row['last_followup_date'])) : 'N/A',
                'last_followup_by' => !empty($row['last_followup_by']) ? $row['last_followup_by'] : 'N/A',
                'followup_status' => $followupStatus,
            ];

            // Filter by Status if specified
            if ($statusFilter === 'has_due' && $totalOutstanding <= 0) {
                continue;
            }
            if ($statusFilter === 'paid' && $totalOutstanding > 0) {
                continue;
            }
            if ($statusFilter === 'due_dev' && $devDue <= 0) {
                continue;
            }
            if ($statusFilter === 'due_emi' && $emiDue <= 0) {
                continue;
            }

            $reportRows[] = $processedRow;

            // Accumulate KPI Card totals for the filtered set
            $totalDevChargeSum += $devCharge;
            $totalDevChargePaidSum += $devPaid;
            $totalDevChargeDueSum += $devDue;
            $totalEmiSum += $totalEmiAmount;
            $totalEmiPaidSum += $emiPaid;
            $totalEmiDueSum += $emiDue;
        }

        $summaryCards = [
            'total_clients' => count($reportRows),
            'total_dev_charge' => $totalDevChargeSum,
            'total_dev_charge_paid' => $totalDevChargePaidSum,
            'total_dev_charge_due' => $totalDevChargeDueSum,
            'total_emi' => $totalEmiSum,
            'total_emi_paid' => $totalEmiPaidSum,
            'total_emi_due' => $totalEmiDueSum,
        ];

        $this->view("ced/ced-central-report", [
            "current_route" => "ced-central-report",
            "page_title" => "CED Central Report",
            "isManagement" => $isManagement,
            "projects" => $projects,
            "cedEmployees" => $cedEmployees,
            "selectedProjectId" => $projectId,
            "selectedStatus" => $statusFilter,
            "selectedAgentId" => $agentId,
            "search" => $search,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "summaryCards" => $summaryCards,
            "reportRows" => $reportRows
        ]);
    }
}
