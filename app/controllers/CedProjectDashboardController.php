<?php
require_once __DIR__ . '/CedCollectionStatementController.php';

/**
 * CED Project-wise Dashboard Controller (Module 2: Under Due Tracking & Management)
 *
 * Provides the full-page project-wise dashboard with dynamic filtering (Project & CED Agent)
 * and AJAX client-level drill-down, aligned with Due Tracking & Management calculations
 * and Module 1 Regular vs. Advance EMI split logic.
 */
class CedProjectDashboardController extends CedCollectionStatementController
{
    /**
     * Override parent __construct to align authorization with Due Tracking & Management
     * (Accounts, Cash-In, Management, and CED roles).
     */
    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRoleId    = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userPriRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $userId        = (int)($_SESSION['user']['id'] ?? 0);
        $roleName      = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $allowedRoles = [1, 3, 6, 7, 30, 33, 34, 35, 36, 37, 39];
        $isAuthorized = in_array($userRoleId, $allowedRoles, true)
            || in_array($userSecRoleId, $allowedRoles, true)
            || in_array($userPriRoleId, $allowedRoles, true)
            || $userId === 94
            || strpos($roleName, 'account') !== false
            || strpos($roleName, 'client experience') !== false
            || strpos($roleName, 'clients experiment') !== false
            || strpos($roleName, 'communicat') !== false
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'admin') !== false
            || (function_exists('hasPermission') && (hasPermission('CED Manage') || hasPermission('Cash In Verify')))
            || (function_exists('isClientsCommunicated') && isClientsCommunicated())
            || (function_exists('isCreditRecovery') && isCreditRecovery())
            || (function_exists('isAccountsHead') && isAccountsHead())
            || (function_exists('isAccountsExecutive') && isAccountsExecutive());

        if (!$isAuthorized) {
            http_response_code(403);
            die('Unauthorized access to Project-wise Dashboard.');
        }
    }

    /**
     * Mirror AccountClientController::getEffectiveCedProjectFilter() for CED agent scoping.
     *
     * @return int[] Empty array when user is unrestricted; otherwise list of active assigned project IDs.
     */
    protected function resolveScopedProjectIds(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $userRoleId    = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userPriRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $roleName      = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $unrestrictedRoles = [1, 3, 6, 30, 33, 34, 35, 36, 38, 39];
        $isUnrestricted = $currentUserId <= 0
            || $currentUserId === 94
            || in_array($userRoleId, $unrestrictedRoles, true)
            || in_array($userSecRoleId, $unrestrictedRoles, true)
            || in_array($userPriRoleId, $unrestrictedRoles, true)
            || strpos($roleName, 'head of client experience') !== false
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'admin') !== false
            || strpos($roleName, 'account') !== false
            || (function_exists('isHeadOfClientExperience') && isHeadOfClientExperience())
            || (function_exists('isAccountsHead') && isAccountsHead())
            || (function_exists('isAccountsExecutive') && isAccountsExecutive());

        if ($isUnrestricted) {
            return [];
        }

        $scopedIds = [];
        $res = $this->db->query("
            SELECT DISTINCT project_id
            FROM ced_project_assignments
            WHERE user_id = {$currentUserId}
              AND status = 'active'
        ");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $pId = (int)$r['project_id'];
                if ($pId > 0) {
                    $scopedIds[] = $pId;
                }
            }
        }

        return $scopedIds;
    }

    /**
     * Render the Project-wise Dashboard page under Due Tracking & Management.
     */
    public function index()
    {
        $scopedProjectIds = $this->resolveScopedProjectIds();

        $filters = [
            'project_id'         => isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : 0,
            'agent_id'           => isset($_GET['agent_id']) && $_GET['agent_id'] !== '' ? (int)$_GET['agent_id'] : 0,
            'search'             => trim($_GET['search'] ?? ''),
            'scoped_project_ids' => $scopedProjectIds,
        ];

        $data = $this->fetchProjectSummary($filters);

        $projectsList = array_values(array_filter(
            $this->fetchProjects(),
            fn($p) => trim((string)($p['name'] ?? '')) !== ''
        ));
        if (!empty($scopedProjectIds)) {
            $projectsList = array_values(array_filter($projectsList, fn($p) => in_array((int)$p['id'], $scopedProjectIds, true)));
        }

        $rawAgents = $this->fetchCedCollectors('ced_only');
        $agentsList = [];
        $seenAgentIds = [];
        foreach ($rawAgents as $ag) {
            $uId = (int)($ag['user_id'] ?? 0);
            $name = trim((string)($ag['full_name'] ?? ''));
            if ($uId <= 0 || $name === '' || isset($seenAgentIds[$uId])) {
                continue;
            }
            $seenAgentIds[$uId] = true;
            $agentsList[] = $ag;
        }

        $projectAgentMap = [];
        $resMap = $this->db->query("
            SELECT project_id, user_id
            FROM ced_project_assignments
            WHERE status = 'active'
            ORDER BY id ASC
        ");
        if ($resMap) {
            while ($mr = $resMap->fetch_assoc()) {
                $pId = (int)$mr['project_id'];
                $uId = (int)$mr['user_id'];
                if ($pId > 0 && $uId > 0 && !isset($projectAgentMap[$pId])) {
                    $projectAgentMap[$pId] = $uId;
                }
            }
        }

        $selectedAgentId = $filters['agent_id'] > 0
            ? $filters['agent_id']
            : (!isset($_GET['agent_id']) && $filters['project_id'] > 0 && isset($projectAgentMap[$filters['project_id']])
                ? (int)$projectAgentMap[$filters['project_id']]
                : 0);

        $viewData = [
            'current_route'     => 'client-wise-due-tracking',
            'current_page'      => 'client-wise-due-tracking',
            'page_title'        => 'Project-wise Dashboard — Due Tracking & Management',
            'filters'           => $filters,
            'project_rows'      => $data['rows'],
            'grand_totals'      => $data['grand'],
            'agents_list'       => $agentsList,
            'projects_list'     => $projectsList,
            'project_agent_map' => $projectAgentMap,
            'selected_agent_id' => $selectedAgentId,
        ];

        $this->view('ced/ced-project-dashboard', $viewData);
    }

    /**
     * AJAX endpoint: Client-level drill-down for a specific project.
     */
    public function clientDrilldown()
    {
        header('Content-Type: application/json; charset=utf-8');

        $projectId = (int)($_GET['project_id'] ?? 0);
        if ($projectId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid project ID.']);
            return;
        }

        $scopedProjectIds = $this->resolveScopedProjectIds();
        if (!empty($scopedProjectIds) && !in_array($projectId, $scopedProjectIds, true)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'You are not assigned to this project.']);
            return;
        }

        $result = $this->fetchClientLevelRows($projectId);
        echo json_encode([
            'status'     => 'success',
            'project_id' => $projectId,
            'rows'       => $result['rows'],
            'totals'     => $result['totals'],
        ]);
    }

    /**
     * Shared SQL predicate for valid active bookings (matches CedCollectionStatementController).
     */
    private function activeBookingSql(string $alias = 'lb'): string
    {
        return "{$alias}.deleted_at IS NULL
            AND {$alias}.booking_status NOT IN ('refunded', 'cancelled')
            AND ({$alias}.landshare_status IS NULL OR {$alias}.landshare_status != 'cancelled')
            AND {$alias}.id NOT IN (
                SELECT crr.booking_id
                FROM client_refund_requests crr
                WHERE crr.booking_id IS NOT NULL
                  AND crr.status IN ('pending', 'approved', 'refunded')
            )";
    }

    /**
     * Shared SQL predicate for valid non-rejected, non-trashed payments.
     */
    private function validPaymentSql(string $alias = 'lp'): string
    {
        return "{$alias}.is_trashed = 0
            AND COALESCE({$alias}.is_emi_discount, 0) = 0
            AND ({$alias}.verification_status IS NULL OR {$alias}.verification_status != 'rejected')";
    }

    /**
     * Shared per-booking query returning Land Share & Development Charge financials
     * aligned with Due Tracking & Management (booking_money + registration + land_share),
     * ensuring project-level totals and client-level drill-down rows always reconcile.
     */
    private function fetchActiveBookingsFinancials(string $inProjectIds, bool $includeClientMeta = false): array
    {
        $activeBookingCond = $this->activeBookingSql('lb');
        $validPaymentCond  = $this->validPaymentSql('lp');

        $metaSelect = $includeClientMeta
            ? ",
                lb.lead_id,
                COALESCE(NULLIF(TRIM(l.lead_name), ''), NULLIF(TRIM(l.full_name), ''), CONCAT('Client #', lb.lead_id)) AS client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), '') AS phone,
                COALESCE(f.flat_name, CONCAT('Flat #', lb.flat_id)) AS flat_name,
                lb.booking_status,
                lb.booking_date"
            : '';

        $metaJoin = $includeClientMeta ? "LEFT JOIN leads l ON l.id = lb.lead_id" : '';
        $orderBy  = $includeClientMeta ? "ORDER BY f.flat_name ASC, lb.id ASC" : "ORDER BY lb.project_id ASC, lb.id ASC";

        $sql = "
            SELECT
                lb.id AS booking_id,
                lb.project_id,
                COALESCE(f.land_share_value, 0) AS total_land_share,
                COALESCE(lb.discount_amount, 0) AS discount_amount,
                COALESCE(f.development_charges, 0) AS total_dc_ask,
                COALESCE(pay.land_share_paid, 0) AS land_share_paid,
                COALESCE(pay.total_dc_received, 0) AS total_dc_received
                {$metaSelect}
            FROM lead_bookings lb
            LEFT JOIN flats f ON f.id = lb.flat_id
            {$metaJoin}
            LEFT JOIN (
                SELECT
                    lp.booking_id,
                    COALESCE(SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') THEN lp.amount ELSE 0 END), 0) AS land_share_paid,
                    COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' THEN lp.amount ELSE 0 END), 0) AS total_dc_received
                FROM lead_payments lp
                INNER JOIN lead_bookings lb_p ON lb_p.id = lp.booking_id
                WHERE lb_p.project_id IN ({$inProjectIds})
                  AND {$validPaymentCond}
                  AND lp.payment_type IN ('booking_money', 'registration', 'land_share', 'development_charge')
                GROUP BY lp.booking_id
            ) pay ON pay.booking_id = lb.id
            WHERE lb.project_id IN ({$inProjectIds})
              AND {$activeBookingCond}
            {$orderBy}
        ";

        $rows = [];
        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $totalLs  = round((float)$r['total_land_share'], 2);
                $discount = round((float)$r['discount_amount'], 2);
                $lsPaid   = round((float)$r['land_share_paid'], 2);
                $dcAsk    = round((float)$r['total_dc_ask'], 2);
                $dcRcvd   = round((float)$r['total_dc_received'], 2);

                $row = [
                    'booking_id'        => (int)$r['booking_id'],
                    'project_id'        => (int)$r['project_id'],
                    'total_land_share'  => $totalLs,
                    'discount_amount'   => $discount,
                    'land_share_paid'   => $lsPaid,
                    'land_share_due'    => round($totalLs - $lsPaid, 2),
                    'total_dc_ask'      => $dcAsk,
                    'total_dc_received' => $dcRcvd,
                    'dc_due'            => round($dcAsk - $dcRcvd, 2),
                    'is_registered'     => ($totalLs > 0 && round($totalLs - $discount - $lsPaid, 2) <= 0),
                ];

                if ($includeClientMeta) {
                    $row['lead_id']        = (int)$r['lead_id'];
                    $row['client_name']    = $r['client_name'];
                    $row['phone']          = $r['phone'];
                    $row['flat_name']      = $r['flat_name'];
                    $row['booking_status'] = $r['booking_status'];
                    $row['booking_date']   = $r['booking_date'];
                }

                $rows[(int)$r['booking_id']] = $row;
            }
        }

        return $rows;
    }

    /**
     * Build project-wise summary (all 18 columns) without multi-join fan-out.
     */
    protected function fetchProjectSummary(array $filters): array
    {
        $where = ["p.deleted_at IS NULL"];

        if (!empty($filters['scoped_project_ids'])) {
            $scoped = array_values(array_filter(array_map('intval', $filters['scoped_project_ids'])));
            if (!empty($scoped)) {
                $where[] = "p.id IN (" . implode(',', $scoped) . ")";
            }
        }
        if (!empty($filters['project_id'])) {
            $where[] = "p.id = " . (int)$filters['project_id'];
        }
        if (!empty($filters['agent_id'])) {
            $aId = (int)$filters['agent_id'];
            $where[] = "EXISTS (
                SELECT 1 FROM ced_project_assignments cpa_f
                WHERE cpa_f.project_id = p.id
                  AND cpa_f.status = 'active'
                  AND cpa_f.user_id = {$aId}
            )";
        }
        if (!empty($filters['search'])) {
            $q = $this->db->real_escape_string($filters['search']);
            $where[] = "(p.name LIKE '%{$q}%' OR EXISTS (
                SELECT 1
                FROM ced_project_assignments cpa_s
                INNER JOIN users u_s ON u_s.id = cpa_s.user_id
                LEFT JOIN employees e_s ON (u_s.employee_id = e_s.id OR e_s.user_id = u_s.id)
                WHERE cpa_s.project_id = p.id
                  AND cpa_s.status = 'active'
                  AND COALESCE(e_s.full_name, u_s.full_name, u_s.user_name) LIKE '%{$q}%'
            ))";
        }

        $whereSql = implode(' AND ', $where);

        // 1. Base projects + active assigned CED agent(s)
        $projects = [];
        $sqlProjects = "
            SELECT
                p.id AS project_id,
                p.name AS project_name,
                COALESCE(p.total_share, 0) AS total_share,
                COALESCE(ag.assigned_person, 'Unassigned') AS assigned_person
            FROM projects p
            LEFT JOIN (
                SELECT
                    cpa.project_id,
                    GROUP_CONCAT(DISTINCT COALESCE(e.full_name, u.full_name, u.user_name) ORDER BY u.id SEPARATOR ', ') AS assigned_person
                FROM ced_project_assignments cpa
                INNER JOIN users u ON u.id = cpa.user_id
                LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
                WHERE cpa.status = 'active'
                GROUP BY cpa.project_id
            ) ag ON ag.project_id = p.id
            WHERE {$whereSql}
            ORDER BY p.name ASC
        ";
        $resP = $this->db->query($sqlProjects);
        if ($resP) {
            while ($r = $resP->fetch_assoc()) {
                $pId = (int)$r['project_id'];
                $projects[$pId] = [
                    'project_id'                => $pId,
                    'assigned_person'           => $r['assigned_person'],
                    'project_name'              => $r['project_name'],
                    'total_share'               => (int)$r['total_share'],
                    'total_client'              => 0,
                    'active_bookings'           => 0,
                    'registered_client'         => 0,
                    'remaining_share'           => 0,
                    'total_land_share'          => 0.0,
                    'land_share_paid'           => 0.0,
                    'land_share_due'            => 0.0,
                    'total_dc_ask'              => 0.0,
                    'total_dc_received'         => 0.0,
                    'dc_due'                    => 0.0,
                    'regular_emi_demand_count'  => 0,
                    'regular_emi_amount'        => 0.0,
                    'regular_emi_received'      => 0.0,
                    'advanced_received'         => 0.0,
                    'net_regular_emi_collected' => 0.0,
                    'regular_emi_due'           => 0.0,
                    'project_expenditure'       => 0.0,
                ];
            }
        }

        $grand = [
            'project_count'             => count($projects),
            'total_client'              => 0,
            'registered_client'         => 0,
            'remaining_share'           => 0,
            'total_land_share'          => 0.0,
            'land_share_paid'           => 0.0,
            'land_share_due'            => 0.0,
            'total_dc_ask'              => 0.0,
            'total_dc_received'         => 0.0,
            'dc_due'                    => 0.0,
            'regular_emi_demand_count'  => 0,
            'regular_emi_amount'        => 0.0,
            'regular_emi_received'      => 0.0,
            'advanced_received'         => 0.0,
            'net_regular_emi_collected' => 0.0,
            'regular_emi_due'           => 0.0,
            'project_expenditure'       => 0.0,
        ];

        if (empty($projects)) {
            return ['rows' => [], 'grand' => $grand];
        }

        $inProjectIds = implode(',', array_keys($projects));

        // 2. Total units/clients per project (flats)
        $resFlats = $this->db->query("
            SELECT project_id, COUNT(id) AS total_units
            FROM flats
            WHERE deleted_at IS NULL AND project_id IN ({$inProjectIds})
            GROUP BY project_id
        ");
        if ($resFlats) {
            while ($r = $resFlats->fetch_assoc()) {
                $pId = (int)$r['project_id'];
                if (isset($projects[$pId])) {
                    $projects[$pId]['total_client'] = (int)$r['total_units'];
                }
            }
        }

        // 3. Aggregate per-booking Land Share, Dev Charge, and Registered Client count
        $bookingRows = $this->fetchActiveBookingsFinancials($inProjectIds, false);
        foreach ($bookingRows as $b) {
            $pId = $b['project_id'];
            if (!isset($projects[$pId])) {
                continue;
            }
            $projects[$pId]['active_bookings']++;
            if ($b['is_registered']) {
                $projects[$pId]['registered_client']++;
            }
            $projects[$pId]['total_land_share']  += $b['total_land_share'];
            $projects[$pId]['land_share_paid']   += $b['land_share_paid'];
            $projects[$pId]['total_dc_ask']      += $b['total_dc_ask'];
            $projects[$pId]['total_dc_received'] += $b['total_dc_received'];
        }

        // 4. Per-booking EMI demand & payments (Module 1 Regular vs Advance split)
        $emiByBooking = $this->computeBookingEmiMetrics($inProjectIds);
        foreach ($emiByBooking as $em) {
            $pId = $em['project_id'];
            if (!isset($projects[$pId])) {
                continue;
            }
            $projects[$pId]['regular_emi_demand_count']  += $em['demand_count'];
            $projects[$pId]['regular_emi_amount']        += $em['asked_amount'];
            $projects[$pId]['regular_emi_received']      += $em['total_emi_paid'];
            $projects[$pId]['advanced_received']         += $em['advance_paid'];
            $projects[$pId]['net_regular_emi_collected'] += $em['net_regular_collected'];
            $projects[$pId]['regular_emi_due']           += $em['emi_due'];
        }

        // 5. Project Expenditure (paid rows in project_other_costs)
        $resExp = $this->db->query("
            SELECT
                project_id,
                COALESCE(SUM(COALESCE(payment_amount, 0)), 0) AS project_expenditure
            FROM project_other_costs
            WHERE status = 'paid'
              AND project_id IN ({$inProjectIds})
            GROUP BY project_id
        ");
        if ($resExp) {
            while ($r = $resExp->fetch_assoc()) {
                $pId = (int)$r['project_id'];
                if (isset($projects[$pId])) {
                    $projects[$pId]['project_expenditure'] = round((float)$r['project_expenditure'], 2);
                }
            }
        }

        // 6. Final derived columns & grand totals
        $rows = [];
        foreach ($projects as $row) {
            $baseShares = $row['total_share'] > 0 ? $row['total_share'] : $row['total_client'];
            $row['remaining_share']           = max(0, $baseShares - $row['active_bookings']);
            $row['total_land_share']          = round($row['total_land_share'], 2);
            $row['land_share_paid']           = round($row['land_share_paid'], 2);
            $row['land_share_due']            = round($row['total_land_share'] - $row['land_share_paid'], 2);
            $row['total_dc_ask']              = round($row['total_dc_ask'], 2);
            $row['total_dc_received']         = round($row['total_dc_received'], 2);
            $row['dc_due']                    = round($row['total_dc_ask'] - $row['total_dc_received'], 2);
            $row['regular_emi_amount']        = round($row['regular_emi_amount'], 2);
            $row['regular_emi_received']      = round($row['regular_emi_received'], 2);
            $row['advanced_received']         = round($row['advanced_received'], 2);
            $row['net_regular_emi_collected'] = round($row['net_regular_emi_collected'], 2);
            $row['regular_emi_due']           = round($row['regular_emi_due'], 2);

            $grand['total_client']              += $row['total_client'];
            $grand['registered_client']         += $row['registered_client'];
            $grand['remaining_share']           += $row['remaining_share'];
            $grand['total_land_share']          += $row['total_land_share'];
            $grand['land_share_paid']           += $row['land_share_paid'];
            $grand['land_share_due']            += $row['land_share_due'];
            $grand['total_dc_ask']              += $row['total_dc_ask'];
            $grand['total_dc_received']         += $row['total_dc_received'];
            $grand['dc_due']                    += $row['dc_due'];
            $grand['regular_emi_demand_count']  += $row['regular_emi_demand_count'];
            $grand['regular_emi_amount']        += $row['regular_emi_amount'];
            $grand['regular_emi_received']      += $row['regular_emi_received'];
            $grand['advanced_received']         += $row['advanced_received'];
            $grand['net_regular_emi_collected'] += $row['net_regular_emi_collected'];
            $grand['regular_emi_due']           += $row['regular_emi_due'];
            $grand['project_expenditure']       += $row['project_expenditure'];

            $rows[] = $row;
        }

        return ['rows' => $rows, 'grand' => $grand];
    }

    /**
     * Compute per-booking EMI metrics using the exact Module 1 logic
     * (respecting project_emi_schedule_items overrides and Regular vs Advance split).
     *
     * @param string $inProjectIds Comma-separated list of integer project IDs
     * @return array Keyed by booking_id
     */
    private function computeBookingEmiMetrics(string $inProjectIds): array
    {
        $today = $this->db->real_escape_string(date('Y-m-d'));
        $activeBookingCond = $this->activeBookingSql('lb');
        $validPaymentCond  = $this->validPaymentSql('lp');

        $metrics = [];

        // Asked EMIs up to TODAY per active booking
        $qSched = $this->db->query("
            SELECT
                es.lead_booking_id AS booking_id,
                lb.project_id,
                COUNT(*) AS total_sched_rows,
                SUM(
                    CASE
                        WHEN es.status != 'cancelled'
                             AND (
                                 (EXISTS (SELECT 1 FROM project_emi_schedule_items px WHERE px.project_id = lb.project_id) AND pesi.is_active = 1)
                                 OR (NOT EXISTS (SELECT 1 FROM project_emi_schedule_items px WHERE px.project_id = lb.project_id) AND es.due_date <= '{$today}')
                             )
                        THEN 1
                        ELSE 0
                    END
                ) AS demand_count,
                COALESCE(SUM(
                    CASE
                        WHEN es.status != 'cancelled'
                             AND (
                                 (EXISTS (SELECT 1 FROM project_emi_schedule_items px WHERE px.project_id = lb.project_id) AND pesi.is_active = 1)
                                 OR (NOT EXISTS (SELECT 1 FROM project_emi_schedule_items px WHERE px.project_id = lb.project_id) AND es.due_date <= '{$today}')
                             )
                        THEN es.emi_amount
                        ELSE 0
                    END
                ), 0) AS asked_amount
            FROM emi_schedules es
            INNER JOIN lead_bookings lb ON lb.id = es.lead_booking_id
            LEFT JOIN project_emi_schedule_items pesi ON pesi.project_id = lb.project_id AND pesi.emi_no = es.emi_no
            WHERE lb.project_id IN ({$inProjectIds})
              AND {$activeBookingCond}
            GROUP BY es.lead_booking_id, lb.project_id
        ");
        if ($qSched) {
            while ($sr = $qSched->fetch_assoc()) {
                $bId = (int)$sr['booking_id'];
                $metrics[$bId] = [
                    'project_id'            => (int)$sr['project_id'],
                    'total_sched_rows'      => (int)$sr['total_sched_rows'],
                    'demand_count'          => (int)$sr['demand_count'],
                    'asked_amount'          => round((float)$sr['asked_amount'], 2),
                    'total_emi_paid'        => 0.0,
                    'advance_paid'          => 0.0,
                    'net_regular_collected' => 0.0,
                    'emi_due'               => 0.0,
                ];
            }
        }

        // Valid EMI payments per active booking
        $qPaid = $this->db->query("
            SELECT
                lp.booking_id,
                lb.project_id,
                COALESCE(SUM(lp.amount), 0) AS total_emi_paid
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lb.id = lp.booking_id
            WHERE lb.project_id IN ({$inProjectIds})
              AND {$activeBookingCond}
              AND {$validPaymentCond}
              AND lp.payment_type = 'emi'
            GROUP BY lp.booking_id, lb.project_id
        ");
        if ($qPaid) {
            while ($pr = $qPaid->fetch_assoc()) {
                $bId = (int)$pr['booking_id'];
                if (!isset($metrics[$bId])) {
                    $metrics[$bId] = [
                        'project_id'            => (int)$pr['project_id'],
                        'total_sched_rows'      => 0,
                        'demand_count'          => 0,
                        'asked_amount'          => 0.0,
                        'total_emi_paid'        => 0.0,
                        'advance_paid'          => 0.0,
                        'net_regular_collected' => 0.0,
                        'emi_due'               => 0.0,
                    ];
                }
                $metrics[$bId]['total_emi_paid'] = round((float)$pr['total_emi_paid'], 2);
            }
        }

        // Apply Module 1 split per booking:
        // Regular EMI Paid = min(paid, asked); Advance = max(0, paid - asked)
        // Net Regular EMI Collected = total_emi_paid - advance_paid (= Regular EMI Paid)
        // Regular EMI Due = asked_amount - net_regular_collected
        foreach ($metrics as $bId => &$m) {
            if ($m['total_sched_rows'] === 0) {
                $m['net_regular_collected'] = $m['total_emi_paid'];
                $m['advance_paid']          = 0.0;
                $m['emi_due']               = 0.0;
            } else {
                $netReg = round(min($m['total_emi_paid'], $m['asked_amount']), 2);
                $adv    = round(max(0.0, $m['total_emi_paid'] - $netReg), 2);
                $m['net_regular_collected'] = $netReg;
                $m['advance_paid']          = $adv;
                $m['emi_due']               = round(max(0.0, $m['asked_amount'] - $netReg), 2);
            }
        }
        unset($m);

        return $metrics;
    }

    /**
     * Build client-level rows for a single project (matching the project-level columns).
     */
    private function fetchClientLevelRows(int $projectId): array
    {
        $clients = $this->fetchActiveBookingsFinancials((string)$projectId, true);

        $totals = [
            'client_count'              => count($clients),
            'registered_count'          => 0,
            'total_land_share'          => 0.0,
            'land_share_paid'           => 0.0,
            'land_share_due'            => 0.0,
            'total_dc_ask'              => 0.0,
            'total_dc_received'         => 0.0,
            'dc_due'                    => 0.0,
            'regular_emi_demand_count'  => 0,
            'regular_emi_amount'        => 0.0,
            'regular_emi_received'      => 0.0,
            'advanced_received'         => 0.0,
            'net_regular_emi_collected' => 0.0,
            'regular_emi_due'           => 0.0,
        ];

        if (empty($clients)) {
            return ['rows' => [], 'totals' => $totals];
        }

        // Per-booking EMI metrics (same Module 1 logic)
        $emiByBooking = $this->computeBookingEmiMetrics((string)$projectId);

        $rows = [];
        foreach ($clients as $bId => $c) {
            $em = $emiByBooking[$bId] ?? null;
            $c['regular_emi_demand_count']  = $em ? $em['demand_count'] : 0;
            $c['regular_emi_amount']        = $em ? $em['asked_amount'] : 0.0;
            $c['regular_emi_received']      = $em ? $em['total_emi_paid'] : 0.0;
            $c['advanced_received']         = $em ? $em['advance_paid'] : 0.0;
            $c['net_regular_emi_collected'] = $em ? $em['net_regular_collected'] : 0.0;
            $c['regular_emi_due']           = $em ? $em['emi_due'] : 0.0;

            if ($c['is_registered']) {
                $totals['registered_count']++;
            }
            $totals['total_land_share']          += $c['total_land_share'];
            $totals['land_share_paid']           += $c['land_share_paid'];
            $totals['land_share_due']            += $c['land_share_due'];
            $totals['total_dc_ask']              += $c['total_dc_ask'];
            $totals['total_dc_received']         += $c['total_dc_received'];
            $totals['dc_due']                    += $c['dc_due'];
            $totals['regular_emi_demand_count']  += $c['regular_emi_demand_count'];
            $totals['regular_emi_amount']        += $c['regular_emi_amount'];
            $totals['regular_emi_received']      += $c['regular_emi_received'];
            $totals['advanced_received']         += $c['advanced_received'];
            $totals['net_regular_emi_collected'] += $c['net_regular_emi_collected'];
            $totals['regular_emi_due']           += $c['regular_emi_due'];

            $rows[] = $c;
        }

        $totals['total_land_share']          = round($totals['total_land_share'], 2);
        $totals['land_share_paid']           = round($totals['land_share_paid'], 2);
        $totals['land_share_due']            = round($totals['total_land_share'] - $totals['land_share_paid'], 2);
        $totals['total_dc_ask']              = round($totals['total_dc_ask'], 2);
        $totals['total_dc_received']         = round($totals['total_dc_received'], 2);
        $totals['dc_due']                    = round($totals['total_dc_ask'] - $totals['total_dc_received'], 2);
        $totals['regular_emi_amount']        = round($totals['regular_emi_amount'], 2);
        $totals['regular_emi_received']      = round($totals['regular_emi_received'], 2);
        $totals['advanced_received']         = round($totals['advanced_received'], 2);
        $totals['net_regular_emi_collected'] = round($totals['net_regular_emi_collected'], 2);
        $totals['regular_emi_due']           = round($totals['regular_emi_due'], 2);

        return ['rows' => $rows, 'totals' => $totals];
    }
}
