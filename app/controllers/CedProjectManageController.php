<?php

class CedProjectManageController extends Controller
{
    protected $db;
    protected $assignmentModel;

    public function __construct($db)
    {
        $this->db = $db;
        require_once dirname(__DIR__) . '/models/CedProjectAssignment.php';
        $this->assignmentModel = new CedProjectAssignment($this->db);
    }

    /**
     * Check if the current user has management privileges over CED projects (Head of CED, Admin, Super Admin)
     */
    protected function canManageCed(): bool
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $secRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $priRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        return in_array($roleId, [1, 3, 33, 38, 39], true)
            || in_array($secRoleId, [1, 3, 38, 39], true)
            || in_array($priRoleId, [1, 3, 38, 39], true)
            || strpos($roleName, 'head of client experience') !== false
            || (function_exists('isHeadOfClientExperience') && isHeadOfClientExperience());
    }

    /**
     * View: Manage Projects for CED (Head of CED & Admin)
     */
    public function manageProjects()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$this->canManageCed()) {
            setError("You do not have permission to manage CED project assignments.");
            header("Location: {$GLOBALS['base_url']}/ced-projects-history");
            exit;
        }

        $agents = $this->assignmentModel->getAvailableCedAgents();
        $projects = $this->assignmentModel->getAllProjects();
        $projectOwners = $this->assignmentModel->getActiveProjectOwnerMap();
        $search = trim($_GET['search'] ?? '');
        $summary = $this->assignmentModel->getPortfolioSummary($search);

        $this->view("ced/projects/manage-projects", [
            "current_route" => "manage-projects-for-ced",
            "page_title"    => "Manage Projects for CED",
            "agents"        => $agents,
            "projects"      => $projects,
            "projectOwners" => $projectOwners,
            "summary"       => $summary,
            "search"        => $search
        ]);
    }

    /**
     * AJAX: Get currently assigned project IDs for an agent
     */
    public function getAgentProjectsAjax($agentId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->canManageCed()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $agentId = (int)$agentId;
        $assignedProjectIds = $this->assignmentModel->getAgentAssignedProjectIds($agentId);

        echo json_encode([
            'success' => true,
            'agent_id' => $agentId,
            'assigned_project_ids' => $assignedProjectIds
        ]);
        exit;
    }

    /**
     * AJAX: Save project assignments for an agent
     */
    public function saveAssignmentsAjax()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->canManageCed()) {
            echo json_encode(['success' => false, 'message' => 'Permission denied. Only Head of CED and Admin can assign projects.']);
            exit;
        }

        $agentId = (int)($_POST['user_id'] ?? 0);
        $projectIds = isset($_POST['project_ids']) && is_array($_POST['project_ids']) 
            ? array_map('intval', $_POST['project_ids']) 
            : [];
        $currentUserId = (int)($_SESSION['user']['id'] ?? 1);

        if ($agentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid CED agent selected.']);
            exit;
        }

        $saved = $this->assignmentModel->saveAgentProjectAssignments($agentId, $projectIds, $currentUserId);

        if ($saved) {
            echo json_encode([
                'success' => true,
                'message' => 'Projects successfully assigned to the agent.',
                'assigned_count' => count($projectIds)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save project assignments. Please try again.']);
        }
        exit;
    }

    /**
     * View: My Projects History / CED Projects History
     */
    public function myProjectsHistory()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $canManage = $this->canManageCed();

        // Agents available for filter
        $agents = $this->assignmentModel->getAvailableCedAgents();

        // Target agent logic
        $selectedAgentId = isset($_GET['agent_id']) && $_GET['agent_id'] !== '' ? (int)$_GET['agent_id'] : null;
        $search = trim($_GET['search'] ?? '');
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;

        // If regular agent (Role 7 or 37) and NOT manager, lock to self
        if (!$canManage) {
            $selectedAgentId = $currentUserId;
        }

        // Summary metrics (supports search and project filter)
        $allAgentsSummary = $canManage ? $this->assignmentModel->getPortfolioSummary($search, null, $selectedProjectId) : [];

        // If a specific agent is selected (or regular agent viewing self)
        $targetAgentSummary = null;
        $projectDetails = [];
        $assignedProjects = [];
        $activeAgentId = $selectedAgentId ?: ($canManage ? null : $currentUserId);

        $allProjects = $this->assignmentModel->getAllProjects();
        $companyOverallStats = $this->assignmentModel->getCompanyOverallStats();
        $unassignedProjectsStats = $this->assignmentModel->getUnassignedProjectsStats();

        if ($activeAgentId) {
            $assignedProjectIds = $this->assignmentModel->getAgentAssignedProjectIds($activeAgentId);
            $assignedProjects = array_values(array_filter($allProjects, fn($p) => in_array((int)$p['id'], $assignedProjectIds, true)));
            $agentSum = $this->assignmentModel->getPortfolioSummary(null, $activeAgentId, $selectedProjectId);
            $targetAgentSummary = $agentSum[0] ?? null;
            $projectDetails = $this->assignmentModel->getAgentProjectDetails($activeAgentId, $selectedProjectId);
        }

        $this->view("ced/projects/my-projects-history", [
            "current_route"           => "ced-projects-history",
            "page_title"              => "CED Projects History",
            "canManage"               => $canManage,
            "agents"                  => $agents,
            "selectedAgentId"         => $selectedAgentId,
            "selectedProjectId"       => $selectedProjectId,
            "search"                  => $search,
            "allAgentsSummary"        => $allAgentsSummary,
            "targetAgentSummary"      => $targetAgentSummary,
            "projectDetails"          => $projectDetails,
            "allProjects"             => $allProjects,
            "assignedProjects"        => $assignedProjects,
            "companyOverallStats"     => $companyOverallStats,
            "unassignedProjectsStats" => $unassignedProjectsStats,
            "currentUserId"           => $currentUserId
        ]);
    }

    /**
     * View: Manage Project EMI & Under Construction Status
     */
    public function manageProjectEmi()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$this->canManageCed()) {
            setError("You do not have permission to manage project EMI schedules.");
            header("Location: {$GLOBALS['base_url']}/ced-projects-history");
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $whereSql = "p.deleted_at IS NULL";
        if ($search !== '') {
            $s = $this->db->real_escape_string($search);
            $whereSql .= " AND (p.name LIKE '%{$s}%' OR COALESCE(e.full_name, u.full_name, u.user_name, '') LIKE '%{$s}%')";
        }

        $sql = "
            SELECT
                p.id AS project_id,
                p.name AS project_name,
                COALESCE(p.under_construction, 0) AS under_construction,
                p.project_status,
                pes.id AS schedule_id,
                pes.emi_start_date,
                pes.total_emi_no,
                pes.emi_due_day,
                pes.default_emi_amount,
                pes.remarks,
                pes.updated_at AS schedule_updated_at,
                COALESCE(e.full_name, u.full_name, u.user_name, 'Unassigned') AS ced_officer_name,
                COALESCE(bk.active_bookings, 0) AS active_bookings
            FROM projects p
            LEFT JOIN project_emi_schedules pes ON pes.project_id = p.id
            LEFT JOIN ced_project_assignments cpa ON (cpa.project_id = p.id AND cpa.status = 'active')
            LEFT JOIN users u ON cpa.user_id = u.id
            LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
            LEFT JOIN (
                SELECT project_id, COUNT(DISTINCT id) AS active_bookings
                FROM lead_bookings
                WHERE deleted_at IS NULL
                  AND booking_status NOT IN ('refunded', 'cancelled')
                  AND (landshare_status IS NULL OR landshare_status != 'cancelled')
                GROUP BY project_id
            ) bk ON bk.project_id = p.id
            WHERE {$whereSql}
            ORDER BY p.name ASC
        ";

        $projects = [];
        $res = $this->db->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $projects[] = $row;
            }
        }

        $this->view("ced/projects/manage-project-emi", [
            "current_route" => "manage-project-emi",
            "page_title"    => "Manage Project EMI",
            "projects"      => $projects,
            "search"        => $search
        ]);
    }

    /**
     * AJAX: Save Project EMI Schedule & Under Construction Status
     */
    public function saveProjectEmiAjax()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->canManageCed()) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }

        $projectId         = (int)($_POST['project_id'] ?? 0);
        $underConstruction = isset($_POST['under_construction']) && (int)$_POST['under_construction'] === 1 ? 1 : 0;
        $emiStartDate      = trim($_POST['emi_start_date'] ?? '');
        $totalEmiNo        = isset($_POST['total_emi_no']) && $_POST['total_emi_no'] !== '' ? max(1, (int)$_POST['total_emi_no']) : 36;
        $emiDueDay         = isset($_POST['emi_due_day']) && $_POST['emi_due_day'] !== '' ? min(31, max(1, (int)$_POST['emi_due_day'])) : 10;
        $defaultEmiAmount  = isset($_POST['default_emi_amount']) && $_POST['default_emi_amount'] !== '' ? (float)$_POST['default_emi_amount'] : null;
        $remarks           = trim($_POST['remarks'] ?? '');
        $currentUserId     = (int)($_SESSION['user']['id'] ?? 1);

        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid project selected.']);
            exit;
        }

        if ($emiStartDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $emiStartDate)) {
            echo json_encode(['success' => false, 'message' => 'Invalid EMI start date format.']);
            exit;
        }

        $emiStartDateVal = ($emiStartDate !== '') ? $emiStartDate : null;
        $remarksVal      = ($remarks !== '') ? $remarks : null;

        // Snapshot previous start date & due day before upserting so we know whether saved EMI item dates need recalculation
        $oldStartDate = null;
        $oldDueDay    = null;
        $qOldSched = $this->db->query("
            SELECT emi_start_date, emi_due_day
            FROM project_emi_schedules
            WHERE project_id = {$projectId}
            LIMIT 1
        ");
        if ($qOldSched && ($oldRow = $qOldSched->fetch_assoc())) {
            $oldStartDate = !empty($oldRow['emi_start_date']) ? $oldRow['emi_start_date'] : null;
            $oldDueDay    = $oldRow['emi_due_day'] !== null ? (int)$oldRow['emi_due_day'] : null;
        }

        // 1. Update projects.under_construction
        $stmtProj = $this->db->prepare("UPDATE projects SET under_construction = ? WHERE id = ?");
        if (!$stmtProj) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $this->db->error]);
            exit;
        }
        $stmtProj->bind_param("ii", $underConstruction, $projectId);
        $stmtProj->execute();
        $stmtProj->close();

        // 2. Upsert project_emi_schedules
        $stmtSched = $this->db->prepare("
            INSERT INTO project_emi_schedules
                (project_id, emi_start_date, total_emi_no, emi_due_day, default_emi_amount, remarks, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                emi_start_date = VALUES(emi_start_date),
                total_emi_no = VALUES(total_emi_no),
                emi_due_day = VALUES(emi_due_day),
                default_emi_amount = VALUES(default_emi_amount),
                remarks = VALUES(remarks),
                updated_by = VALUES(updated_by)
        ");
        if (!$stmtSched) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $this->db->error]);
            exit;
        }
        $stmtSched->bind_param(
            "isiidsi",
            $projectId,
            $emiStartDateVal,
            $totalEmiNo,
            $emiDueDay,
            $defaultEmiAmount,
            $remarksVal,
            $currentUserId
        );
        $ok = $stmtSched->execute();
        $stmtSched->close();

        if ($ok && $emiStartDateVal !== null) {
            $startTs              = strtotime($emiStartDateVal);
            $startYear            = (int)date('Y', $startTs);
            $startMonth           = (int)date('n', $startTs);
            $firstMonthTs         = strtotime(sprintf('%04d-%02d-01', $startYear, $startMonth));
            $expectedFirstDueDate = date('Y-m-', $firstMonthTs) . sprintf('%02d', min($emiDueDay, (int)date('t', $firstMonthTs)));

            $existingItems = [];
            $qItems = $this->db->query("
                SELECT id, emi_no, due_date, emi_amount, remarks, is_active
                FROM project_emi_schedule_items
                WHERE project_id = {$projectId}
                ORDER BY emi_no ASC
            ");
            if ($qItems) {
                while ($ir = $qItems->fetch_assoc()) {
                    $existingItems[(int)$ir['emi_no']] = $ir;
                }
            }

            $firstSavedDueDate = isset($existingItems[1]) ? ($existingItems[1]['due_date'] ?? null) : null;
            $datesChanged      = ($oldStartDate !== $emiStartDateVal)
                || ($oldDueDay !== $emiDueDay)
                || ($firstSavedDueDate !== null && $firstSavedDueDate !== $expectedFirstDueDate);

            if ($datesChanged && !empty($existingItems)) {
                $updItem = $this->db->prepare("
                    UPDATE project_emi_schedule_items
                    SET due_date = ?, updated_by = ?
                    WHERE id = ?
                ");
                if ($updItem) {
                    $activeItemsToSync = [];
                    foreach ($existingItems as $emiNo => $item) {
                        if ($emiNo < 1) {
                            continue;
                        }
                        $monthOffset = $emiNo - 1;
                        $targetYm    = strtotime(sprintf('%04d-%02d-01', $startYear, $startMonth) . " +{$monthOffset} months");
                        $day         = min($emiDueDay, (int)date('t', $targetYm));
                        $newDueDate  = date('Y-m-', $targetYm) . sprintf('%02d', $day);
                        $itemId      = (int)$item['id'];

                        $updItem->bind_param("sii", $newDueDate, $currentUserId, $itemId);
                        $updItem->execute();

                        if ((int)($item['is_active'] ?? 0) === 1) {
                            $activeItemsToSync[] = [
                                'emi_no'     => $emiNo,
                                'due_date'   => $newDueDate,
                                'emi_amount' => $item['emi_amount'] !== null ? (float)$item['emi_amount'] : null,
                                'remarks'    => $item['remarks'] ?? null,
                            ];
                        }
                    }
                    $updItem->close();

                    if (!empty($activeItemsToSync)) {
                        $this->syncActiveEmiDatesToProjectClients($projectId, $activeItemsToSync, $currentUserId);
                    }
                }
            }
        }

        if ($ok) {
            echo json_encode([
                'success' => true,
                'message' => 'Project EMI schedule and construction status saved successfully.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save EMI schedule.']);
        }
        exit;
    }

    /**
     * AJAX: Get individual EMI schedule items (1..total_emi_no) for a project
     */
    public function getProjectEmiItemsAjax($projectId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->canManageCed()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $projectId = (int)$projectId;
        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid project.']);
            exit;
        }

        $qProj = $this->db->query("
            SELECT
                p.id,
                p.name,
                pes.emi_start_date,
                COALESCE(pes.total_emi_no, 36) AS total_emi_no,
                COALESCE(pes.emi_due_day, 10) AS emi_due_day,
                pes.default_emi_amount
            FROM projects p
            LEFT JOIN project_emi_schedules pes ON pes.project_id = p.id
            WHERE p.id = {$projectId}
            LIMIT 1
        ");
        $proj = $qProj ? $qProj->fetch_assoc() : null;
        if (!$proj) {
            echo json_encode(['success' => false, 'message' => 'Project not found.']);
            exit;
        }

        $totalEmiNo = max(1, (int)$proj['total_emi_no']);
        $dueDay     = min(31, max(1, (int)$proj['emi_due_day']));
        $startDate  = !empty($proj['emi_start_date']) ? $proj['emi_start_date'] : date('Y-m-01');
        $defAmount  = $proj['default_emi_amount'] !== null ? (float)$proj['default_emi_amount'] : null;

        // Fetch existing saved items
        $savedItems = [];
        $qItems = $this->db->query("
            SELECT emi_no, due_date, emi_amount, remarks, is_active
            FROM project_emi_schedule_items
            WHERE project_id = {$projectId}
            ORDER BY emi_no ASC
        ");
        if ($qItems) {
            while ($ir = $qItems->fetch_assoc()) {
                $savedItems[(int)$ir['emi_no']] = $ir;
            }
        }

        $startTs = strtotime($startDate);
        $startYear  = (int)date('Y', $startTs);
        $startMonth = (int)date('n', $startTs);

        $items = [];
        for ($i = 1; $i <= $totalEmiNo; $i++) {
            if (isset($savedItems[$i])) {
                $items[] = [
                    'emi_no'     => $i,
                    'due_date'   => $savedItems[$i]['due_date'],
                    'emi_amount' => $savedItems[$i]['emi_amount'],
                    'remarks'    => $savedItems[$i]['remarks'] ?? '',
                    'is_active'  => (int)($savedItems[$i]['is_active'] ?? 0),
                    'is_saved'   => true,
                ];
            } else {
                $monthOffset = $i - 1;
                $targetYm = strtotime(sprintf('%04d-%02d-01', $startYear, $startMonth) . " +{$monthOffset} months");
                $daysInMonth = (int)date('t', $targetYm);
                $day = min($dueDay, $daysInMonth);
                $defaultDueDate = date('Y-m-', $targetYm) . sprintf('%02d', $day);

                $items[] = [
                    'emi_no'     => $i,
                    'due_date'   => $defaultDueDate,
                    'emi_amount' => $defAmount,
                    'remarks'    => '',
                    'is_active'  => 0,
                    'is_saved'   => false,
                ];
            }
        }

        echo json_encode([
            'success'      => true,
            'project_id'   => $projectId,
            'project_name' => $proj['name'],
            'total_emi_no' => $totalEmiNo,
            'emi_start_date' => $startDate,
            'emi_due_day'  => $dueDay,
            'default_emi_amount' => $defAmount,
            'items'        => $items
        ]);
        exit;
    }

    /**
     * AJAX: Save individual EMI schedule items (due dates) for a project
     */
    public function saveProjectEmiItemsAjax()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->canManageCed()) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }

        $projectId     = (int)($_POST['project_id'] ?? 0);
        $emiNos        = $_POST['emi_no'] ?? [];
        $dueDates      = $_POST['due_date'] ?? [];
        $emiAmounts    = $_POST['emi_amount'] ?? [];
        $remarksList   = $_POST['remarks'] ?? [];
        $isActiveList  = $_POST['is_active'] ?? [];
        $currentUserId = (int)($_SESSION['user']['id'] ?? 1);

        if ($projectId <= 0 || !is_array($emiNos) || empty($emiNos)) {
            echo json_encode(['success' => false, 'message' => 'Invalid project or empty EMI list.']);
            exit;
        }

        $stmt = $this->db->prepare("
            INSERT INTO project_emi_schedule_items (project_id, emi_no, due_date, emi_amount, remarks, is_active, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                due_date = VALUES(due_date),
                emi_amount = VALUES(emi_amount),
                remarks = VALUES(remarks),
                is_active = VALUES(is_active),
                updated_by = VALUES(updated_by)
        ");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $this->db->error]);
            exit;
        }

        $savedCount = 0;
        $activeItems = [];
        $inactiveEmiNos = [];
        foreach ($emiNos as $idx => $no) {
            $emiNo   = (int)$no;
            $dueDate = trim($dueDates[$idx] ?? '');
            if ($emiNo <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                continue;
            }
            $amtRaw   = $emiAmounts[$idx] ?? '';
            $amt      = ($amtRaw !== '' && $amtRaw !== null) ? (float)$amtRaw : null;
            $remRaw   = trim($remarksList[$idx] ?? '');
            $rem      = ($remRaw !== '') ? $remRaw : null;
            $isActive = isset($isActiveList[$idx]) && (int)$isActiveList[$idx] === 1 ? 1 : 0;

            $stmt->bind_param("iisdsii", $projectId, $emiNo, $dueDate, $amt, $rem, $isActive, $currentUserId);
            if ($stmt->execute()) {
                $savedCount++;
                if ($isActive === 1) {
                    $activeItems[] = [
                        'emi_no'     => $emiNo,
                        'due_date'   => $dueDate,
                        'emi_amount' => $amt,
                        'remarks'    => $rem,
                    ];
                } else {
                    $inactiveEmiNos[] = $emiNo;
                }
            }
        }
        $stmt->close();

        // Sync active EMI due dates to all active bookings (clients) of this project
        $syncedBookings = $this->syncActiveEmiDatesToProjectClients($projectId, $activeItems, $currentUserId, $inactiveEmiNos);

        echo json_encode([
            'success' => true,
            'message' => "{$savedCount} EMI schedule items saved and synced to {$syncedBookings} active client bookings."
        ]);
        exit;
    }

    /**
     * Sync active project EMI due dates into client-wise emi_schedules and client_emi_fixed_dates
     * for all active bookings in the project.
     */
    protected function syncActiveEmiDatesToProjectClients(int $projectId, array $activeItems, int $fixedByUserId = 1, array $inactiveEmiNos = []): int
    {
        if ($projectId <= 0 || (empty($activeItems) && empty($inactiveEmiNos))) {
            return 0;
        }

        $qBookings = $this->db->query("
            SELECT
                lb.id AS booking_id,
                COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) - COALESCE(lb.discount_amount, 0) AS total_price,
                COALESCE(f.land_share_value, 0) AS land_share,
                COALESCE(f.development_charges, 0) + IF(COALESCE(f.is_shop, 0) = 1, COALESCE(f.shop_service_charge, 0), 0) AS dev_charge,
                COALESCE(NULLIF((SELECT MAX(lp.total_emi_no) FROM lead_payments lp WHERE lp.booking_id = lb.id AND lp.is_trashed = 0), 0), pes.total_emi_no, 36) AS total_emi_no,
                pes.default_emi_amount
            FROM lead_bookings lb
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN project_emi_schedules pes ON pes.project_id = lb.project_id
            WHERE lb.project_id = {$projectId}
              AND lb.deleted_at IS NULL
              AND lb.booking_status NOT IN ('refunded', 'cancelled')
              AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
        ");

        if (!$qBookings) {
            return 0;
        }

        $bookings = [];
        $bookingIds = [];
        while ($row = $qBookings->fetch_assoc()) {
            $bId = (int)$row['booking_id'];
            $bookings[] = $row;
            $bookingIds[] = $bId;
        }

        if (empty($bookingIds)) {
            return 0;
        }

        $inIds = implode(',', $bookingIds);

        // Remove stale unpaid fixed dates for EMIs that were deactivated at the project level
        $inactiveEmiNos = array_values(array_unique(array_filter(array_map('intval', $inactiveEmiNos), fn($n) => $n > 0)));
        if (!empty($inactiveEmiNos)) {
            $inInactive = implode(',', $inactiveEmiNos);
            $this->db->query("
                DELETE cef
                FROM client_emi_fixed_dates cef
                WHERE cef.booking_id IN ({$inIds})
                  AND cef.emi_no IN ({$inInactive})
                  AND NOT EXISTS (
                      SELECT 1
                      FROM emi_schedules es
                      LEFT JOIN lead_payments lp ON lp.id = es.lead_payment_id
                      WHERE es.lead_booking_id = cef.booking_id
                        AND es.emi_no = cef.emi_no
                        AND (
                            (lp.id IS NOT NULL AND lp.is_trashed = 0 AND lp.payment_status IN ('paid', 'partially_paid') AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected'))
                            OR (es.lead_payment_id IS NULL AND es.status IN ('paid', 'partially_paid'))
                        )
                  )
            ");
        }

        if (empty($activeItems)) {
            return count($bookings);
        }

        $existingMap = [];
        $qExisting = $this->db->query("
            SELECT id, lead_booking_id, emi_no
            FROM emi_schedules
            WHERE lead_booking_id IN ({$inIds})
        ");
        if ($qExisting) {
            while ($er = $qExisting->fetch_assoc()) {
                $existingMap[(int)$er['lead_booking_id']][(int)$er['emi_no']] = (int)$er['id'];
            }
        }

        $updStmt = $this->db->prepare("UPDATE emi_schedules SET due_date = ? WHERE id = ?");
        $insStmt = $this->db->prepare("INSERT INTO emi_schedules (lead_booking_id, emi_no, emi_amount, due_date, status) VALUES (?, ?, ?, ?, 'pending')");
        $fixStmt = $this->db->prepare("
            INSERT INTO client_emi_fixed_dates (booking_id, emi_no, due_date, notes, fixed_by, fixed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                due_date = VALUES(due_date),
                notes = COALESCE(VALUES(notes), notes),
                fixed_by = VALUES(fixed_by),
                fixed_at = NOW()
        ");
        if (!$updStmt || !$insStmt || !$fixStmt) {
            if ($updStmt) $updStmt->close();
            if ($insStmt) $insStmt->close();
            if ($fixStmt) $fixStmt->close();
            return 0;
        }

        foreach ($bookings as $bk) {
            $bId             = (int)$bk['booking_id'];
            $totalPrice      = (float)$bk['total_price'];
            $landShare       = (float)$bk['land_share'];
            $devCharge       = (float)$bk['dev_charge'];
            $totalEmiNo      = max(1, (int)$bk['total_emi_no']);
            $constructionFee = max(0.0, round($totalPrice - ($landShare + $devCharge), 2));
            $clientEmiAmt    = ($constructionFee > 0) ? round($constructionFee / $totalEmiNo, 2) : (float)($bk['default_emi_amount'] ?? 0);

            foreach ($activeItems as $item) {
                $emiNo   = (int)$item['emi_no'];
                $dueDate = $item['due_date'];
                $note    = $item['remarks'] ?? null;

                if (isset($existingMap[$bId][$emiNo])) {
                    $schedId = $existingMap[$bId][$emiNo];
                    $updStmt->bind_param("si", $dueDate, $schedId);
                    $updStmt->execute();
                } else {
                    $amt = ($clientEmiAmt > 0) ? $clientEmiAmt : (float)($item['emi_amount'] ?? 0);
                    $insStmt->bind_param("iids", $bId, $emiNo, $amt, $dueDate);
                    $insStmt->execute();
                }

                $fixStmt->bind_param("iissi", $bId, $emiNo, $dueDate, $note, $fixedByUserId);
                $fixStmt->execute();
            }
        }

        $updStmt->close();
        $insStmt->close();
        $fixStmt->close();
        return count($bookings);
    }
}
