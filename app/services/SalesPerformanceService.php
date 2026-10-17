<?php

/**
 * SalesPerformanceService
 * -------------------------------------------------------------------------
 * Single source of truth for the Management / Chief Executive
 * "Sales Performance" dashboard (Overview, Team, Executive tabs).
 *
 * Design rules (to avoid gaps / double counting):
 *  1. Lead ownership  = leads.assigned_id.
 *  2. Team            = owner's users.team_id (fallback employees.team_id).
 *                       leads.team_id is NOT used – it is out of sync with the
 *                       owner's team on ~45% of rows.
 *  3. Funnel (cohort) = leads received in the period
 *                       (COALESCE(assigned_at, created_at) inside the period),
 *                       followed to their furthest stage up to today. Every
 *                       percentage in the funnel uses the same denominator.
 *  4. Activity        = what happened inside the period (follow-ups, visits,
 *                       bookings, confirmed sales, collections, onboarding).
 *  5. Booking credit  = lead_bookings.sales_by_id, fallback to lead owner.
 *  6. Everything is aggregated from the same row-sets in PHP, so
 *     Executives -> Teams -> Overall always reconcile.
 */
class SalesPerformanceService
{
    /** @var mysqli */
    protected $db;

    public const SALES_ROLE_IDS = [5, 8, 9]; // Sales Manager, Sales Executive, Sales Team Head

    public const QUALIFIED_STATUSES = ['Prospect', 'High Prospect', 'Hot', 'Warm', 'Will Visit', 'Visited', 'Sold'];
    public const DROPPED_STATUSES = ['Not Interested', "Don't Call Again", 'Irrelevant', 'Refund'];
    public const TRANSFERRED_STATUSES = ['Lead Transferred', 'Project Transferred'];
    public const INVALID_CONTACT_TYPES = ['Number Off', 'Wrong Number', 'Invalid Number'];

    public const STAGES = [
        1 => 'Received',
        2 => 'Contacted',
        3 => 'Will Visit',
        4 => 'Visited',
        5 => 'Booked',
    ];

    protected array $users = [];
    protected array $teams = [];

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =====================================================================
    // Period helpers
    // =====================================================================

    /**
     * Resolve date period.
     * 
     * Modes supported:
     * - all / lifetime: Entire database (All Leads view, matching lead-lists)
     * - year:YYYY: Full calendar year (01 Jan to 31 Dec)
     * - custom: Custom start and end dates from $from and $to
     * - YYYY-MM / default: Monthly sales cycle (26th of previous month to 25th of current month)
     */
    public function resolvePeriod(?string $cycle = null, ?int $year = null, ?int $month = null, ?string $from = null, ?string $to = null): array
    {
        $tracker = new TrackerCalculationService($this->db);

        // 1. Custom date range (from and to)
        if ($from && $to && strtotime($from) && strtotime($to) && strtotime($from) <= strtotime($to)) {
            return [
                'type' => 'custom',
                'is_all_time' => false,
                'is_yearly' => false,
                'is_custom' => true,
                'year' => (int) date('Y', strtotime($from)),
                'month' => (int) date('n', strtotime($from)),
                'start' => $from . ' 00:00:00',
                'end' => $to . ' 23:59:59',
                'start_date' => $from,
                'end_date' => $to,
                'label' => date('d M Y', strtotime($from)) . ' – ' . date('d M Y', strtotime($to)),
                'cycle_label' => 'Custom Range',
                'cycle_key' => 'custom',
                'target_month' => substr($from, 0, 7) . '-01',
            ];
        }

        // 2. All Time / Lifetime
        if ($cycle === 'all' || $cycle === 'lifetime') {
            return [
                'type' => 'all',
                'is_all_time' => true,
                'is_yearly' => false,
                'is_custom' => false,
                'year' => null,
                'month' => null,
                'start' => '2000-01-01 00:00:00',
                'end' => '2099-12-31 23:59:59',
                'start_date' => '2000-01-01',
                'end_date' => date('Y-m-d'),
                'label' => 'All Leads (Lifetime)',
                'cycle_label' => 'All Time',
                'cycle_key' => 'all',
                'target_month' => date('Y-m-01'),
            ];
        }

        // 3. Full Year (e.g. year:2026 or year:2025)
        if (preg_match('/^year:(\d{4})$/', (string) $cycle, $ym)) {
            $y = (int) $ym[1];
            return [
                'type' => 'year',
                'is_all_time' => false,
                'is_yearly' => true,
                'is_custom' => false,
                'year' => $y,
                'month' => null,
                'start' => "$y-01-01 00:00:00",
                'end' => "$y-12-31 23:59:59",
                'start_date' => "$y-01-01",
                'end_date' => "$y-12-31",
                'label' => "01 Jan $y – 31 Dec $y",
                'cycle_label' => "Year $y (Full Year)",
                'cycle_key' => "year:$y",
                'target_month' => "$y-01-01",
            ];
        }

        // 4. Specific Month cycle (e.g. 2026-10)
        if (preg_match('/^(\d{4})-(\d{1,2})$/', (string) $cycle, $mm)) {
            $year = (int) $mm[1];
            $month = (int) $mm[2];
        }

        // 5. Default: Current sales cycle (26th of last month to 25th of current month)
        if (!$year || !$month) {
            $year = (int) date('Y');
            $month = (int) date('n');
            if ((int) date('j') >= 26) {
                $month++;
                if ($month > 12) {
                    $month = 1;
                    $year++;
                }
            }
        }

        $range = $tracker->getDateRange($year, $month);
        $prevM = $month - 1;
        $prevY = $year;
        if ($prevM < 1) {
            $prevM = 12;
            $prevY--;
        }
        $cycleKey = sprintf('%04d-%02d', $year, $month);

        return [
            'type' => 'month',
            'is_all_time' => false,
            'is_yearly' => false,
            'is_custom' => false,
            'year' => $year,
            'month' => $month,
            'start' => $range['start'],
            'end' => $range['end'],
            'start_date' => substr($range['start'], 0, 10),
            'end_date' => substr($range['end'], 0, 10),
            'label' => date('d M', strtotime($range['start'])) . ' – ' . date('d M Y', strtotime($range['end'])),
            'cycle_label' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'cycle_key' => $cycleKey,
            'target_month' => sprintf('%04d-%02d-01', $year, $month),
        ];
    }

    public function cycleOptions(int $count = 12): array
    {
        $opts = [];
        // 1. All Time Option
        $opts[] = [
            'value' => 'all',
            'label' => 'All Leads (Lifetime)',
            'type' => 'all',
        ];

        // 2. Yearly Options (Current year & previous year)
        $curY = (int) date('Y');
        $opts[] = [
            'value' => 'year:' . $curY,
            'label' => 'Year ' . $curY . ' (Full Year)',
            'type' => 'year',
        ];
        $opts[] = [
            'value' => 'year:' . ($curY - 1),
            'label' => 'Year ' . ($curY - 1) . ' (Full Year)',
            'type' => 'year',
        ];

        // 3. Monthly sales cycles (26th of last month to 25th of current month)
        $y = (int) date('Y');
        $m = (int) date('n');
        if ((int) date('j') >= 26) {
            $m++;
            if ($m > 12) {
                $m = 1;
                $y++;
            }
        }
        for ($i = 0; $i < $count; $i++) {
            $val = sprintf('%04d-%02d', $y, $m);
            $prevM = $m - 1;
            $prevY = $y;
            if ($prevM < 1) {
                $prevM = 12;
                $prevY--;
            }
            $cycleDesc = date('F Y', mktime(0, 0, 0, $m, 1, $y)) . ' (26 ' . date('M', mktime(0, 0, 0, $prevM, 1, $prevY)) . ' – 25 ' . date('M', mktime(0, 0, 0, $m, 1, $y)) . ')';
            $opts[] = [
                'value' => $val,
                'label' => $cycleDesc,
                'year' => $y,
                'month' => $m,
                'type' => 'month',
            ];
            $m--;
            if ($m < 1) {
                $m = 12;
                $y--;
            }
        }
        return $opts;
    }

    // =====================================================================
    // Reference data
    // =====================================================================

    public function loadReference(): void
    {
        if (!empty($this->users)) {
            return;
        }

        $teams = [];
        $res = $this->db->query("SELECT id, name, status FROM teams ORDER BY name");
        while ($res && $r = $res->fetch_assoc()) {
            $teams[(int) $r['id']] = ['id' => (int) $r['id'], 'name' => trim($r['name']), 'active' => (int) $r['status'] === 1];
        }
        $this->teams = $teams;

        $sql = "SELECT u.id, u.full_name, u.role_id, u.secondary_role_id, u.photo, r.name AS role_name,
                       COALESCE(NULLIF(u.team_id, 0), NULLIF(e.team_id, 0)) AS team_id,
                       e.employee_status,
                       e.department_id,
                       e.has_emp_record
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN (
                    SELECT user_id, 
                           MAX(team_id) AS team_id, 
                           MAX(employee_status) AS employee_status,
                           MAX(department_id) AS department_id,
                           1 AS has_emp_record
                    FROM employees WHERE user_id IS NOT NULL GROUP BY user_id
                ) e ON e.user_id = u.id";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            $roleId = (int) $r['role_id'];
            $isSales = in_array($roleId, self::SALES_ROLE_IDS, true) 
                || in_array((int) $r['secondary_role_id'], self::SALES_ROLE_IDS, true)
                || (int) ($r['department_id'] ?? 0) === 1;

            $hasEmp = !empty($r['has_emp_record']);
            $empStatus = strtolower(trim((string) $r['employee_status']));

            // Accurate HR status:
            // 1. If employee record exists, an active employee must strictly have employee_status === 'active'.
            // 2. If NO employee record exists (or ID 195 Unknown Sales employee), they are not active HR staff,
            //    they are legacy/unlinked user accounts.
            if ($hasEmp) {
                $isActive = ($empStatus === 'active');
                $statusLabel = $empStatus ?: 'inactive';
            } else {
                $isActive = false;
                $statusLabel = ((int) $r['id'] === 195) ? 'legacy' : 'unlinked';
            }

            $this->users[(int) $r['id']] = [
                'id' => (int) $r['id'],
                'name' => trim((string) $r['full_name']) ?: ('User #' . $r['id']),
                'role_id' => $roleId,
                'role_name' => trim((string) $r['role_name']) ?: ($isSales ? 'Sales Executive' : ''),
                'team_id' => $r['team_id'] ? (int) $r['team_id'] : 0,
                'is_sales' => $isSales,
                'is_leader' => in_array($roleId, [5, 9], true),
                'active' => $isActive,
                'employee_status' => $statusLabel,
                'photo' => $r['photo'],
            ];
        }
    }

    public function getTeams(): array
    {
        $this->loadReference();
        return $this->teams;
    }

    public function getSalesUsers(): array
    {
        $this->loadReference();
        $list = array_filter($this->users, fn($u) => $u['is_sales']);
        usort($list, fn($a, $b) => strcmp($a['name'], $b['name']));
        return array_values($list);
    }

    public function getProjects(): array
    {
        $res = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Bucket key for a given owner (user id) – used to group everything.
     * Returns [teamKey, execKey].
     */
    protected function bucket(?int $userId): array
    {
        if (!$userId) {
            return ['unassigned', null];
        }
        $u = $this->users[$userId] ?? null;
        if (!$u) {
            return ['unknown_user', null];
        }
        if (!$u['is_sales']) {
            return ['non_sales', $userId];
        }
        if (!$u['team_id'] || !isset($this->teams[$u['team_id']])) {
            return ['no_team', $userId];
        }
        return ['t' . $u['team_id'], $userId];
    }

    protected function teamLabel(string $key): string
    {
        switch ($key) {
            case 'unassigned':
                return 'Unassigned leads';
            case 'unknown_user':
                return 'Owner account missing';
            case 'non_sales':
                return 'Owned by non-sales users';
            case 'no_team':
                return 'Sales users without team';
        }
        $id = (int) substr($key, 1);
        return $this->teams[$id]['name'] ?? ('Team #' . $id);
    }

    // =====================================================================
    // Main builder
    // =====================================================================

    /**
     * @param array $period  from resolvePeriod()
     * @param array $filters ['project_id' => ?int, 'team_id' => ?int, 'executive_id' => ?int]
     */
    public function build(array $period, array $filters): array
    {
        $this->loadReference();

        $projectId = !empty($filters['project_id']) ? (int) $filters['project_id'] : null;
        $teamFilter = !empty($filters['team_id']) ? (int) $filters['team_id'] : null;
        $execFilter = !empty($filters['executive_id']) ? (int) $filters['executive_id'] : null;

        $start = $period['start'];
        $end = $period['end'];
        $startD = $period['start_date'];
        $endD = $period['end_date'];
        $isAllTime = !empty($period['is_all_time']);
        $today = date('Y-m-d');

        // Bucket filter: does this owner pass team/executive filters?
        $passes = function (?int $uid) use ($teamFilter, $execFilter): bool {
            if ($execFilter) {
                return $uid === $execFilter;
            }
            if ($teamFilter) {
                return $uid && isset($this->users[$uid]) && $this->users[$uid]['team_id'] === $teamFilter && $this->users[$uid]['is_sales'];
            }
            return true;
        };

        $execs = [];   // uid => metrics
        $teamsAgg = []; // teamKey => metrics
        $overall = $this->emptyMetrics();

        $touch = function (string $teamKey, ?int $uid) use (&$execs, &$teamsAgg) {
            if (!isset($teamsAgg[$teamKey])) {
                $teamsAgg[$teamKey] = $this->emptyMetrics();
            }
            if ($uid && !isset($execs[$uid])) {
                $execs[$uid] = $this->emptyMetrics();
            }
        };

        $add = function (?int $uid, string $field, $value = 1) use (&$execs, &$teamsAgg, &$overall, $touch) {
            [$teamKey, $execKey] = $this->bucket($uid);
            $touch($teamKey, $execKey);
            $overall[$field] += $value;
            $teamsAgg[$teamKey][$field] += $value;
            if ($execKey) {
                $execs[$execKey][$field] += $value;
            }
        };

        $responseMinutes = ['__all' => []];

        // -----------------------------------------------------------------
        // 1. Cohort funnel: leads received in period (or all active leads if all-time)
        // -----------------------------------------------------------------
        $qual = $this->inList(self::QUALIFIED_STATUSES);
        $invalid = $this->inList(self::INVALID_CONTACT_TYPES);
        $projSqlL = $projectId ? " AND l.project_id = " . $projectId : "";
        $dateSqlL = $isAllTime ? "" : " AND COALESCE(l.assigned_at, l.created_at) BETWEEN '$start' AND '$end'";

        $sql = "
            SELECT l.id, l.assigned_id, l.assigned_at, l.created_at, l.first_contacted_at, l.call_status,
                   l.communication_type, l.lead_type, l.last_lead_action,
                   COALESCE(fu.fu_count, 0) AS fu_count,
                   COALESCE(fu.f_valid, 0) AS f_valid,
                   COALESCE(fu.f_qual, 0) AS f_qual,
                   COALESCE(fu.f_vsched, 0) AS f_vsched,
                   COALESCE(fu.f_vdone, 0) AS f_vdone,
                   COALESCE(fu.f_nego, 0) AS f_nego,
                   fu.first_fu_at,
                   COALESCE(bk.booked, 0) AS booked,
                   COALESCE(bk.confirmed, 0) AS confirmed
            FROM leads l
            LEFT JOIN (
                SELECT lead_id,
                       COUNT(*) AS fu_count,
                       MIN(created_at) AS first_fu_at,
                       MAX(follow_up_type NOT IN ($invalid)) AS f_valid,
                       MAX(lead_status IN ($qual)) AS f_qual,
                       MAX(next_action = 'site visit' OR lead_status IN ('Will Visit','Visited')) AS f_vsched,
                       MAX(lead_status = 'Visited' OR follow_up_type IN ('visit','Face To Face')) AS f_vdone,
                       MAX(next_action = 'booking') AS f_nego
                FROM lead_follow_ups
                WHERE deleted_at IS NULL
                GROUP BY lead_id
            ) fu ON fu.lead_id = l.id
            LEFT JOIN (
                SELECT b.lead_id, 1 AS booked,
                       MAX(EXISTS (SELECT 1 FROM lead_payments p
                                   WHERE p.booking_id = b.id AND COALESCE(p.is_trashed, 0) = 0
                                     AND p.verification_status = 'verified')) AS confirmed
                FROM lead_bookings b
                WHERE b.deleted_at IS NULL AND b.booking_status IN ('booked','completed')
                GROUP BY b.lead_id
            ) bk ON bk.lead_id = l.id
            WHERE l.deleted_at IS NULL
              $dateSqlL
              $projSqlL
        ";
        $res = $this->db->query($sql);
        $cohortGaps = ['booked_without_visit' => 0, 'contacted_no_timestamp' => 0, 'no_assigned_at' => 0];
        while ($res && $r = $res->fetch_assoc()) {
            $uid = $r['assigned_id'] ? (int) $r['assigned_id'] : null;
            if (!$passes($uid)) {
                continue;
            }

            $leadType = (string) $r['lead_type'];
            $isBooked = ($r['last_lead_action'] === 'booked') || ((int) $r['booked'] === 1);
            $isVisited = $isBooked || ($leadType === 'Visited') || ($r['last_lead_action'] === 'visited') || ((int) $r['f_vdone'] === 1);
            $isWillVisit = $isVisited || in_array($leadType, ['Will Visit', 'Visited', 'Sold'], true) || ((int) $r['f_vsched'] === 1);
            $isContacted = $isWillVisit || !empty($r['first_contacted_at']) || ((int) $r['fu_count'] > 0)
                || in_array($r['call_status'], ['called', 'received'], true)
                || in_array(strtolower((string) $r['last_lead_action']), ['contacted', 'follow up', 'visited', 'booked'], true)
                || (!empty($r['communication_type']) && strtolower((string) $r['communication_type']) !== 'not select');

            $vdone = $isVisited;
            $booked = $isBooked;

            // 5 Funnel stages: 1. Received -> 2. Contacted -> 3. Will Visit -> 4. Visited -> 5. Booked
            $stage = 1;
            if ($isContacted) $stage = 2;
            if ($isWillVisit) $stage = 3;
            if ($isVisited) $stage = 4;
            if ($isBooked) $stage = 5;

            for ($s = 1; $s <= $stage; $s++) {
                $add($uid, 'stage_' . $s);
            }
            if (!$isContacted) {
                $add($uid, 'uncontacted');
            }
            if ($uid) {
                $add($uid, 'assigned_leads');
            }
            if ((int) $r['fu_count'] > 0 && (int) $r['f_valid'] === 0) {
                $add($uid, 'invalid_numbers');
            }
            if ($booked && !$vdone) {
                $cohortGaps['booked_without_visit']++;
            }
            if ($isContacted && empty($r['first_contacted_at']) && (int) $r['fu_count'] === 0) {
                $cohortGaps['contacted_no_timestamp']++;
            }
            if ($uid && empty($r['assigned_at'])) {
                $cohortGaps['no_assigned_at']++;
            }

            // First response time (only reliable when both timestamps exist & ordered)
            $firstTouch = $r['first_contacted_at'] ?: $r['first_fu_at'];
            if ($uid && $r['assigned_at'] && $firstTouch && strtotime($firstTouch) >= strtotime($r['assigned_at'])) {
                $mins = (strtotime($firstTouch) - strtotime($r['assigned_at'])) / 60;
                $responseMinutes['__all'][] = $mins;
                $responseMinutes[$uid][] = $mins;
                [$tk] = $this->bucket($uid);
                $responseMinutes['team:' . $tk][] = $mins;
                if ($mins <= 1440) {
                    $add($uid, 'contacted_24h');
                }
                $add($uid, 'response_samples');
            }
        }

        // -----------------------------------------------------------------
        // 2. Follow-up activity inside period (credited to who logged it)
        // -----------------------------------------------------------------
        $projJoin = $projectId ? " AND l.project_id = " . $projectId : "";
        $dateSqlF = $isAllTime ? "" : " AND f.created_at BETWEEN '$start' AND '$end'";
        $sql = "
            SELECT f.created_by AS uid,
                   COUNT(*) AS calls,
                   COUNT(DISTINCT f.lead_id) AS leads_followed,
                   COUNT(DISTINCT CASE WHEN f.lead_status = 'Visited' OR f.follow_up_type IN ('visit','Face To Face') THEN f.lead_id END) AS visits_done,
                   COUNT(DISTINCT CASE WHEN f.next_action = 'site visit' THEN f.lead_id END) AS visits_planned
            FROM lead_follow_ups f
            JOIN leads l ON l.id = f.lead_id AND l.deleted_at IS NULL
            WHERE f.deleted_at IS NULL $dateSqlF $projJoin
            GROUP BY f.created_by
        ";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            $uid = (int) $r['uid'] ?: null;
            if (!$passes($uid)) {
                continue;
            }
            $add($uid, 'follow_ups', (int) $r['calls']);
            $add($uid, 'leads_followed', (int) $r['leads_followed']);
            $add($uid, 'visits_done', (int) $r['visits_done']);
            $add($uid, 'visits_planned', (int) $r['visits_planned']);
        }

        // Daily follow-up trend (for overview sparkline)
        $trend = [];
        $trendDateSql = $isAllTime ? " AND f.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" : " AND f.created_at BETWEEN '$start' AND '$end'";
        $sql = "
            SELECT DATE(f.created_at) d, f.created_by uid, COUNT(*) c
            FROM lead_follow_ups f
            JOIN leads l ON l.id = f.lead_id AND l.deleted_at IS NULL
            WHERE f.deleted_at IS NULL $trendDateSql $projJoin
            GROUP BY d, uid
        ";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            if (!$passes((int) $r['uid'] ?: null)) {
                continue;
            }
            $trend[$r['d']] = ($trend[$r['d']] ?? 0) + (int) $r['c'];
        }

        // -----------------------------------------------------------------
        // 3. Bookings in period (credited to sales_by_id, fallback owner)
        // -----------------------------------------------------------------
        $projB = $projectId ? " AND b.project_id = " . $projectId : "";
        $dateSqlB = $isAllTime ? "" : " AND b.booking_date BETWEEN '$startD' AND '$endD'";
        $sql = "
            SELECT b.id, b.lead_id, b.project_id, b.agreement_value, b.booking_date, b.uploaded_from,
                   COALESCE(NULLIF(b.sales_by_id, 0), l.assigned_id) AS uid,
                   EXISTS (SELECT 1 FROM lead_payments p WHERE p.booking_id = b.id AND COALESCE(p.is_trashed,0) = 0
                           AND p.verification_status = 'verified') AS confirmed,
                   p.name AS project_name
            FROM lead_bookings b
            JOIN leads l ON l.id = b.lead_id
            LEFT JOIN projects p ON p.id = b.project_id
            WHERE b.deleted_at IS NULL AND b.booking_status IN ('booked','completed')
              $dateSqlB $projB
        ";
        $projectsAgg = [];
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            $uid = $r['uid'] ? (int) $r['uid'] : null;
            if (!$passes($uid)) {
                continue;
            }
            $add($uid, 'bookings');
            $add($uid, 'booking_value', (float) $r['agreement_value']);
            if ((int) $r['confirmed'] === 1) {
                $add($uid, 'confirmed_sales');
                $add($uid, 'confirmed_value', (float) $r['agreement_value']);
            }
            $pid = (int) $r['project_id'];
            if (!isset($projectsAgg[$pid])) {
                $projectsAgg[$pid] = ['id' => $pid, 'name' => $r['project_name'] ?: 'Unknown project', 'leads' => 0, 'bookings' => 0, 'confirmed' => 0, 'value' => 0.0];
            }
            $projectsAgg[$pid]['bookings']++;
            $projectsAgg[$pid]['confirmed'] += (int) $r['confirmed'];
            $projectsAgg[$pid]['value'] += (float) $r['agreement_value'];
        }

        // Leads per project in cohort (for project table)
        $sql = "
            SELECT l.project_id, l.assigned_id, COUNT(*) c, p.name
            FROM leads l LEFT JOIN projects p ON p.id = l.project_id
            WHERE l.deleted_at IS NULL $dateSqlL $projSqlL
            GROUP BY l.project_id, l.assigned_id
        ";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            if (!$passes($r['assigned_id'] ? (int) $r['assigned_id'] : null)) {
                continue;
            }
            $pid = (int) $r['project_id'];
            if (!isset($projectsAgg[$pid])) {
                $projectsAgg[$pid] = ['id' => $pid, 'name' => $r['name'] ?: 'No project selected', 'leads' => 0, 'bookings' => 0, 'confirmed' => 0, 'value' => 0.0];
            }
            $projectsAgg[$pid]['leads'] += (int) $r['c'];
        }

        // -----------------------------------------------------------------
        // 4. Collections in period (verified money, by payment_date)
        // -----------------------------------------------------------------
        $dateSqlP = $isAllTime ? "" : " AND p.payment_date BETWEEN '$startD' AND '$endD'";
        $sql = "
            SELECT COALESCE(NULLIF(b.sales_by_id, 0), l.assigned_id) AS uid, SUM(p.amount) AS amt
            FROM lead_payments p
            JOIN lead_bookings b ON b.id = p.booking_id AND b.deleted_at IS NULL AND b.booking_status IN ('booked','completed')
            JOIN leads l ON l.id = b.lead_id
            WHERE COALESCE(p.is_trashed, 0) = 0
              AND p.verification_status = 'verified'
              AND p.payment_status IN ('paid','partially_paid')
              $dateSqlP $projB
            GROUP BY uid
        ";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            $uid = $r['uid'] ? (int) $r['uid'] : null;
            if (!$passes($uid)) {
                continue;
            }
            $add($uid, 'collection', (float) $r['amt']);
        }

        // -----------------------------------------------------------------
        // 5. Onboarded clients in period
        // -----------------------------------------------------------------
        $projO = $projectId ? " AND COALESCE(o.project_id, l.project_id) = " . $projectId : "";
        $dateSqlO = $isAllTime ? "" : " AND o.created_at BETWEEN '$start' AND '$end'";
        $sql = "
            SELECT COALESCE(
                       NULLIF((SELECT b.sales_by_id FROM lead_bookings b
                               WHERE b.id = o.lead_booking_id
                                  OR (o.lead_booking_id IS NULL AND b.lead_id = o.lead_id AND b.deleted_at IS NULL)
                               ORDER BY b.id LIMIT 1), 0),
                       l.assigned_id) AS uid,
                   COUNT(*) AS c
            FROM onboard_clients o
            JOIN leads l ON l.id = o.lead_id
            WHERE 1=1 $dateSqlO $projO
            GROUP BY uid
        ";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            $uid = $r['uid'] ? (int) $r['uid'] : null;
            if (!$passes($uid)) {
                continue;
            }
            $add($uid, 'onboarded', (int) $r['c']);
        }

        // -----------------------------------------------------------------
        // 6. Live work queue (as of today, all owned open leads)
        // -----------------------------------------------------------------
        $closed = $this->inList(array_merge(self::DROPPED_STATUSES, self::TRANSFERRED_STATUSES, ['Sold']));
        $sql = "
            SELECT l.assigned_id AS uid,
                   SUM(lf.next_follow_up_date < '$today') AS overdue,
                   SUM(lf.next_follow_up_date = '$today') AS due_today,
                   SUM(lf.next_action = 'site visit' AND lf.next_follow_up_date BETWEEN '$today' AND DATE_ADD('$today', INTERVAL 7 DAY)) AS upcoming_visits
            FROM leads l
            JOIN (
                SELECT f.lead_id, f.next_follow_up_date, f.next_action
                FROM lead_follow_ups f
                JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = f.id
            ) lf ON lf.lead_id = l.id
            WHERE l.deleted_at IS NULL AND l.assigned_id > 0
              AND COALESCE(l.lead_type, '') NOT IN ($closed)
              AND NOT EXISTS (SELECT 1 FROM lead_bookings b WHERE b.lead_id = l.id AND b.deleted_at IS NULL AND b.booking_status IN ('booked','completed'))
              $projSqlL
            GROUP BY l.assigned_id
        ";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            $uid = (int) $r['uid'];
            if (!$passes($uid)) {
                continue;
            }
            $add($uid, 'overdue', (int) $r['overdue']);
            $add($uid, 'due_today', (int) $r['due_today']);
            $add($uid, 'upcoming_visits', (int) $r['upcoming_visits']);
        }

        // Uncontacted backlog older than 24h (all time, open leads)
        $sql = "
            SELECT l.assigned_id AS uid, COUNT(*) AS c
            FROM leads l
            WHERE l.deleted_at IS NULL AND l.assigned_id > 0
              AND l.first_contacted_at IS NULL
              AND COALESCE(l.call_status, 'not_called') = 'not_called'
              AND COALESCE(l.lead_type, '') NOT IN ($closed)
              AND COALESCE(l.assigned_at, l.created_at) < DATE_SUB(NOW(), INTERVAL 24 HOUR)
              AND NOT EXISTS (SELECT 1 FROM lead_follow_ups f WHERE f.lead_id = l.id AND f.deleted_at IS NULL)
              $projSqlL
            GROUP BY l.assigned_id
        ";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            $uid = (int) $r['uid'];
            if (!$passes($uid)) {
                continue;
            }
            $add($uid, 'backlog_uncontacted', (int) $r['c']);
        }

        // -----------------------------------------------------------------
        // 7. Targets (sales_targets for the cycle month)
        // -----------------------------------------------------------------
        $tm = $this->db->real_escape_string($period['target_month']);
        $res = $this->db->query("SELECT sales_user_id, booking_target, site_visit_target, booking_value_target FROM sales_targets WHERE month = '$tm'");
        while ($res && $r = $res->fetch_assoc()) {
            $uid = (int) $r['sales_user_id'];
            if (!$passes($uid)) {
                continue;
            }
            $add($uid, 'target_bookings', (int) $r['booking_target']);
            $add($uid, 'target_visits', (int) $r['site_visit_target']);
            $add($uid, 'target_value', (float) $r['booking_value_target']);
        }

        // -----------------------------------------------------------------
        // 8. Make sure every in-scope sales user appears (even with zero)
        // -----------------------------------------------------------------
        foreach ($this->users as $u) {
            if (!$u['is_sales']) {
                continue;
            }
            if (!$passes($u['id'])) {
                continue;
            }
            [$tk, $ek] = $this->bucket($u['id']);
            $touch($tk, $ek);
        }

        // -----------------------------------------------------------------
        // Finalise
        // -----------------------------------------------------------------
        $overall = $this->derive($overall, $responseMinutes['__all']);

        $execRows = [];
        $activeExecRows = [];
        $inactiveExecRows = [];

        foreach ($execs as $uid => $m) {
            $u = $this->users[$uid] ?? null;
            if (!$u || !$u['is_sales']) {
                continue;
            }
            [$tk] = $this->bucket($uid);
            $row = $this->derive($m, $responseMinutes[$uid] ?? []);
            $row['id'] = $uid;
            $row['name'] = $u['name'] ?? ('User #' . $uid);
            $row['role'] = $u['role_name'] ?? '';
            $row['is_leader'] = $u['is_leader'] ?? false;
            $row['active'] = $u['active'] ?? false;
            $row['employee_status'] = $u['employee_status'] ?? 'active';
            $row['is_sales'] = $u['is_sales'] ?? false;
            $row['photo'] = $u['photo'] ?? null;
            $row['team_key'] = $tk;
            $row['team_name'] = $this->teamLabel($tk);

            if ($row['active']) {
                $activeExecRows[] = $row;
            } else {
                $inactiveExecRows[] = $row;
            }
            $execRows[] = $row;
        }

        $sortFn = fn($a, $b) => [$b['confirmed_sales'], $b['bookings'], $b['stage_1']] <=> [$a['confirmed_sales'], $a['bookings'], $a['stage_1']];
        usort($activeExecRows, $sortFn);
        usort($inactiveExecRows, $sortFn);
        usort($execRows, $sortFn);

        $teamRows = [];
        foreach ($teamsAgg as $key => $m) {
            $row = $this->derive($m, $responseMinutes['team:' . $key] ?? []);
            $row['key'] = $key;
            $row['name'] = $this->teamLabel($key);
            $row['is_real_team'] = str_starts_with($key, 't');
            $row['team_id'] = $row['is_real_team'] ? (int) substr($key, 1) : null;
            $members = array_values(array_filter($execRows, fn($e) => $e['team_key'] === $key));
            $row['member_count'] = count(array_filter($members, fn($e) => $e['active'] && $e['is_sales']));
            $row['leaders'] = array_values(array_map(fn($e) => $e['name'], array_filter($members, fn($e) => $e['is_leader'] && $e['active'])));
            $row['members'] = array_map(fn($e) => $e['id'], $members);
            $teamRows[] = $row;
        }
        usort($teamRows, function ($a, $b) {
            if ($a['is_real_team'] !== $b['is_real_team']) {
                return $a['is_real_team'] ? -1 : 1;
            }
            return [$b['confirmed_sales'], $b['bookings'], $b['stage_1']] <=> [$a['confirmed_sales'], $a['bookings'], $a['stage_1']];
        });

        $projectRows = array_values($projectsAgg);
        usort($projectRows, fn($a, $b) => [$b['bookings'], $b['leads']] <=> [$a['bookings'], $a['leads']]);

        ksort($trend);
        $trendSeries = [];
        $tFrom = $isAllTime ? strtotime('-29 days') : strtotime($startD);
        $tTo = $isAllTime ? strtotime($today) : min(strtotime($endD), strtotime($today));
        for ($d = $tFrom; $d <= $tTo; $d += 86400) {
            $k = date('Y-m-d', $d);
            $trendSeries[] = ['date' => $k, 'count' => $trend[$k] ?? 0];
        }

        return [
            'overall' => $overall,
            'teams' => $teamRows,
            'executives' => $activeExecRows,
            'inactive_executives' => $inactiveExecRows,
            'all_executives' => $execRows,
            'projects' => array_slice($projectRows, 0, 12),
            'trend' => $trendSeries,
            'cohort_gaps' => $cohortGaps,
            'stages' => self::STAGES,
        ];
    }

    protected function emptyMetrics(): array
    {
        $m = [
            'assigned_leads' => 0, 'uncontacted' => 0, 'invalid_numbers' => 0,
            'contacted_24h' => 0, 'response_samples' => 0,
            'follow_ups' => 0, 'leads_followed' => 0, 'visits_done' => 0, 'visits_planned' => 0,
            'bookings' => 0, 'booking_value' => 0.0, 'confirmed_sales' => 0, 'confirmed_value' => 0.0,
            'collection' => 0.0, 'onboarded' => 0,
            'overdue' => 0, 'due_today' => 0, 'upcoming_visits' => 0, 'backlog_uncontacted' => 0,
            'target_bookings' => 0, 'target_visits' => 0, 'target_value' => 0.0,
        ];
        foreach (array_keys(self::STAGES) as $s) {
            $m['stage_' . $s] = 0;
        }
        return $m;
    }

    protected function derive(array $m, array $responseMinutes): array
    {
        $recv = $m['stage_1'];
        $m['contact_rate'] = $recv ? round($m['stage_2'] / $recv * 100, 1) : 0;
        $m['will_visit_rate'] = $recv ? round($m['stage_3'] / $recv * 100, 1) : 0;
        $m['visit_rate'] = $recv ? round($m['stage_4'] / $recv * 100, 1) : 0;
        $m['lead_to_booking'] = $recv ? round($m['stage_5'] / $recv * 100, 2) : 0;
        $m['conversion_rate'] = $recv ? round($m['bookings'] / max(1, $recv) * 100, 2) : 0;
        $m['lead_to_sale'] = $recv ? round($m['confirmed_sales'] / max(1, $recv) * 100, 2) : 0;
        $m['visit_to_sale'] = $m['visits_done'] ? round($m['bookings'] / $m['visits_done'] * 100, 1) : 0;
        $m['contacted_24h_rate'] = $m['response_samples'] ? round($m['contacted_24h'] / $m['response_samples'] * 100, 1) : null;
        $m['median_response_hours'] = $this->median($responseMinutes);
        $m['target_achievement'] = $m['target_bookings'] > 0 ? round($m['bookings'] / $m['target_bookings'] * 100, 1) : null;
        $m['visit_target_achievement'] = $m['target_visits'] > 0 ? round($m['visits_done'] / $m['target_visits'] * 100, 1) : null;
        return $m;
    }

    protected function median(array $minutes): ?float
    {
        if (empty($minutes)) {
            return null;
        }
        sort($minutes);
        $n = count($minutes);
        $mid = intdiv($n, 2);
        $v = $n % 2 ? $minutes[$mid] : ($minutes[$mid - 1] + $minutes[$mid]) / 2;
        return round($v / 60, 1);
    }

    protected function inList(array $values): string
    {
        return implode(',', array_map(fn($v) => "'" . $this->db->real_escape_string($v) . "'", $values));
    }

    // =====================================================================
    // Data quality (all-time, live)
    // =====================================================================

    /**
     * Each check: key, label, description, severity, count, impact (which metric it distorts).
     */
    public function dataQuality(): array
    {
        $this->loadReference();
        $salesRoles = implode(',', self::SALES_ROLE_IDS);
        $checks = $this->gapDefinitions();
        $out = [];
        foreach ($checks as $key => $c) {
            $res = $this->db->query("SELECT COUNT(*) AS c FROM (" . $c['sql'] . ") x");
            $count = ($res && $row = $res->fetch_assoc()) ? (int) $row['c'] : 0;
            $out[] = [
                'key' => $key,
                'label' => $c['label'],
                'description' => $c['description'],
                'impact' => $c['impact'],
                'severity' => $c['severity'],
                'group' => $c['group'],
                'count' => $count,
            ];
        }

        // Lifetime coverage numbers
        $cov = $this->db->query("
            SELECT COUNT(*) AS total,
                   SUM(assigned_id > 0) AS assigned,
                   SUM(first_contacted_at IS NOT NULL OR COALESCE(call_status,'not_called') <> 'not_called') AS contacted
            FROM leads WHERE deleted_at IS NULL
        ")->fetch_assoc();

        return [
            'checks' => $out,
            'coverage' => [
                'total' => (int) $cov['total'],
                'assigned' => (int) $cov['assigned'],
                'contacted' => (int) $cov['contacted'],
            ],
        ];
    }

    /**
     * SQL for each gap. Every query returns lead/booking-level rows with the
     * same column shape so it can be reused for the drill-down modal.
     * Columns: ref_id, lead_id, lead_name, phone, owner_id, info, event_date
     */
    protected function gapDefinitions(): array
    {
        $salesRoles = implode(',', self::SALES_ROLE_IDS);
        $leadCols = "l.id AS ref_id, l.id AS lead_id, COALESCE(NULLIF(l.full_name,''), NULLIF(l.lead_name,''), CONCAT('Lead #', l.id)) AS lead_name,
                     COALESCE(NULLIF(l.phone,''), l.phone_number) AS phone, l.assigned_id AS owner_id";
        $bkCols = "b.id AS ref_id, b.lead_id, COALESCE(NULLIF(l.full_name,''), NULLIF(l.lead_name,''), CONCAT('Lead #', l.id)) AS lead_name,
                   COALESCE(NULLIF(l.phone,''), l.phone_number) AS phone, COALESCE(NULLIF(b.sales_by_id,0), l.assigned_id) AS owner_id";
        $activeBk = "b.deleted_at IS NULL AND b.booking_status IN ('booked','completed')";
        $closed = $this->inList(array_merge(self::DROPPED_STATUSES, self::TRANSFERRED_STATUSES, ['Sold']));

        return [
            // ---------------- Lead ownership ----------------
            'unassigned' => [
                'group' => 'Lead ownership', 'severity' => 'high',
                'label' => 'Unassigned leads',
                'description' => 'Active leads with no sales owner. They are counted in Overview but no team or executive gets credit.',
                'impact' => 'Leads received, contact rate',
                'sql' => "SELECT $leadCols, l.lead_type AS info, l.created_at AS event_date FROM leads l
                          WHERE l.deleted_at IS NULL AND (l.assigned_id IS NULL OR l.assigned_id = 0)",
            ],
            'owner_inactive' => [
                'group' => 'Lead ownership', 'severity' => 'high',
                'label' => 'Open leads owned by inactive staff',
                'description' => 'Leads still assigned to resigned/inactive employees. Nobody is following them up – reassign.',
                'impact' => 'Uncontacted, overdue follow-ups',
                'sql' => "SELECT $leadCols, COALESCE(l.lead_type, 'No status') AS info, COALESCE(l.assigned_at, l.created_at) AS event_date
                          FROM leads l JOIN employees e ON e.user_id = l.assigned_id
                          WHERE l.deleted_at IS NULL AND LOWER(TRIM(e.employee_status)) <> 'active'
                            AND COALESCE(l.lead_type,'') NOT IN ($closed)
                            AND NOT EXISTS (SELECT 1 FROM lead_bookings b WHERE b.lead_id = l.id AND $activeBk)",
            ],
            'owner_non_sales' => [
                'group' => 'Lead ownership', 'severity' => 'medium',
                'label' => 'Leads owned by non-sales roles',
                'description' => 'Owner exists but is not a Sales Executive / Manager / Head. Shown under "Owned by non-sales users" in the Team tab.',
                'impact' => 'Team totals',
                'sql' => "SELECT $leadCols, r.name AS info, COALESCE(l.assigned_at, l.created_at) AS event_date
                          FROM leads l JOIN users u ON u.id = l.assigned_id LEFT JOIN roles r ON r.id = u.role_id
                          WHERE l.deleted_at IS NULL AND u.role_id NOT IN ($salesRoles) AND COALESCE(u.secondary_role_id,0) NOT IN ($salesRoles)",
            ],
            'owner_missing' => [
                'group' => 'Lead ownership', 'severity' => 'high',
                'label' => 'Owner account does not exist',
                'description' => 'assigned_id points to a deleted user account.',
                'impact' => 'Team & executive totals',
                'sql' => "SELECT $leadCols, CONCAT('assigned_id = ', l.assigned_id) AS info, COALESCE(l.assigned_at, l.created_at) AS event_date
                          FROM leads l LEFT JOIN users u ON u.id = l.assigned_id
                          WHERE l.deleted_at IS NULL AND l.assigned_id > 0 AND u.id IS NULL",
            ],
            'sales_user_no_team' => [
                'group' => 'Lead ownership', 'severity' => 'medium',
                'label' => 'Sales users without a team',
                'description' => 'Sales Executive / Manager / Head with no team_id. Their work cannot roll up to a team.',
                'impact' => 'Team performance',
                'sql' => "SELECT u.id AS ref_id, NULL AS lead_id, u.full_name AS lead_name, u.phone, u.id AS owner_id, r.name AS info, u.created_at AS event_date
                          FROM users u LEFT JOIN roles r ON r.id = u.role_id
                          LEFT JOIN employees e ON e.user_id = u.id
                          WHERE u.role_id IN ($salesRoles) AND COALESCE(u.team_id,0) = 0 AND COALESCE(e.team_id,0) = 0
                            AND COALESCE(LOWER(e.employee_status),'active') = 'active'",
            ],
            'no_assigned_at' => [
                'group' => 'Lead ownership', 'severity' => 'low',
                'label' => 'Assigned leads without assignment time',
                'description' => 'assigned_at is empty, so the lead is dated by created_at and first-response time cannot be measured.',
                'impact' => 'Period allocation, response time',
                'sql' => "SELECT $leadCols, 'assigned_at missing' AS info, l.created_at AS event_date
                          FROM leads l WHERE l.deleted_at IS NULL AND l.assigned_id > 0 AND l.assigned_at IS NULL",
            ],

            // ---------------- Contact & follow-up ----------------
            'contact_before_assign' => [
                'group' => 'Contact & follow-up', 'severity' => 'low',
                'label' => 'First contact earlier than assignment',
                'description' => 'Lead was contacted before it was (re)assigned – usually a reassignment. Excluded from response-time.',
                'impact' => 'Response time',
                'sql' => "SELECT $leadCols, CONCAT('Contacted ', DATE_FORMAT(l.first_contacted_at,'%d %b %Y')) AS info, l.assigned_at AS event_date
                          FROM leads l WHERE l.deleted_at IS NULL AND l.first_contacted_at < l.assigned_at",
            ],
            'call_status_no_log' => [
                'group' => 'Contact & follow-up', 'severity' => 'medium',
                'label' => 'Call status set but no follow-up logged',
                'description' => 'Executive changed call status on the dashboard but never logged a follow-up note. Counted as contacted, but no conversation history.',
                'impact' => 'Follow-up quality',
                'sql' => "SELECT $leadCols, l.call_status AS info, COALESCE(l.assigned_at, l.created_at) AS event_date
                          FROM leads l WHERE l.deleted_at IS NULL AND l.call_status IN ('called','received','not_received')
                            AND NOT EXISTS (SELECT 1 FROM lead_follow_ups f WHERE f.lead_id = l.id AND f.deleted_at IS NULL)",
            ],
            'fu_by_non_owner' => [
                'group' => 'Contact & follow-up', 'severity' => 'low',
                'label' => 'Follow-ups logged by someone other than the owner',
                'description' => 'Follow-up credit goes to the person who logged it; lead credit goes to the owner.',
                'impact' => 'Executive follow-up count',
                'sql' => "SELECT f.id AS ref_id, l.id AS lead_id, COALESCE(NULLIF(l.full_name,''), NULLIF(l.lead_name,''), CONCAT('Lead #', l.id)) AS lead_name,
                                 COALESCE(NULLIF(l.phone,''), l.phone_number) AS phone, l.assigned_id AS owner_id,
                                 CONCAT('Logged by user #', f.created_by) AS info, f.created_at AS event_date
                          FROM lead_follow_ups f JOIN leads l ON l.id = f.lead_id
                          WHERE f.deleted_at IS NULL AND l.deleted_at IS NULL AND f.created_by <> l.assigned_id",
            ],
            'duplicate_phone' => [
                'group' => 'Contact & follow-up', 'severity' => 'medium',
                'label' => 'Duplicate leads (same phone)',
                'description' => 'More than one active lead shares the same last-10-digit phone number. Inflates leads received.',
                'impact' => 'Leads received, conversion',
                'sql' => "SELECT $leadCols, CONCAT(d.cnt, ' leads share this number') AS info, l.created_at AS event_date
                          FROM leads l JOIN (
                              SELECT RIGHT(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(phone,''), phone_number),' ',''),'-',''),'+',''),10) AS ph, COUNT(*) AS cnt
                              FROM leads WHERE deleted_at IS NULL AND COALESCE(NULLIF(phone,''), phone_number) IS NOT NULL
                                AND LENGTH(COALESCE(NULLIF(phone,''), phone_number)) >= 8
                              GROUP BY ph HAVING cnt > 1
                          ) d ON d.ph = RIGHT(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(l.phone,''), l.phone_number),' ',''),'-',''),'+',''),10)
                          WHERE l.deleted_at IS NULL",
            ],

            // ---------------- Booking & onboarding ----------------
            'booking_no_seller' => [
                'group' => 'Booking & onboarding', 'severity' => 'high',
                'label' => 'Bookings without "Sales by"',
                'description' => 'sales_by_id is empty. Credit falls back to the lead owner.',
                'impact' => 'Bookings per executive',
                'sql' => "SELECT $bkCols, CONCAT('Booking #', b.id) AS info, b.booking_date AS event_date
                          FROM lead_bookings b JOIN leads l ON l.id = b.lead_id
                          WHERE $activeBk AND COALESCE(b.sales_by_id,0) = 0",
            ],
            'booking_seller_ne_owner' => [
                'group' => 'Booking & onboarding', 'severity' => 'low',
                'label' => 'Booking seller differs from lead owner',
                'description' => 'Booking credited to a different executive than the lead owner (e.g. transfer). Confirm the right person gets credit.',
                'impact' => 'Executive bookings',
                'sql' => "SELECT $bkCols, CONCAT('Lead owner #', l.assigned_id) AS info, b.booking_date AS event_date
                          FROM lead_bookings b JOIN leads l ON l.id = b.lead_id
                          WHERE $activeBk AND b.sales_by_id > 0 AND b.sales_by_id <> l.assigned_id",
            ],
            'booking_unverified' => [
                'group' => 'Booking & onboarding', 'severity' => 'medium',
                'label' => 'Bookings without verified payment',
                'description' => 'Counted as Booking but NOT as Confirmed Sale until accounts verifies a payment.',
                'impact' => 'Confirmed sales',
                'sql' => "SELECT $bkCols, b.payment_status AS info, b.booking_date AS event_date
                          FROM lead_bookings b JOIN leads l ON l.id = b.lead_id
                          WHERE $activeBk AND NOT EXISTS (SELECT 1 FROM lead_payments p WHERE p.booking_id = b.id
                                AND COALESCE(p.is_trashed,0) = 0 AND p.verification_status = 'verified')",
            ],
            'booking_duplicate_flat' => [
                'group' => 'Booking & onboarding', 'severity' => 'high',
                'label' => 'Same unit booked twice',
                'description' => 'More than one active booking on the same flat/unit.',
                'impact' => 'Bookings, booking value',
                'sql' => "SELECT $bkCols, CONCAT('Flat #', b.flat_id) AS info, b.booking_date AS event_date
                          FROM lead_bookings b JOIN leads l ON l.id = b.lead_id
                          WHERE $activeBk AND b.flat_id IN (
                              SELECT flat_id FROM lead_bookings WHERE deleted_at IS NULL AND booking_status IN ('booked','completed') AND flat_id > 0
                              GROUP BY flat_id HAVING COUNT(*) > 1)",
            ],
            'booked_status_not_sold' => [
                'group' => 'Booking & onboarding', 'severity' => 'low',
                'label' => 'Booked lead whose status is not "Sold"',
                'description' => 'Lead has an active booking but lead status was never updated. Dashboard uses the booking, not the status.',
                'impact' => 'Lead status reports',
                'sql' => "SELECT $bkCols, COALESCE(l.lead_type, 'No status') AS info, b.booking_date AS event_date
                          FROM lead_bookings b JOIN leads l ON l.id = b.lead_id
                          WHERE $activeBk AND COALESCE(l.lead_type,'') <> 'Sold'",
            ],
            'onboard_unlinked' => [
                'group' => 'Booking & onboarding', 'severity' => 'medium',
                'label' => 'Onboarded clients not linked to a booking',
                'description' => 'onboard_clients.lead_booking_id is empty; matched by lead instead.',
                'impact' => 'Onboarding credit',
                'sql' => "SELECT o.id AS ref_id, o.lead_id, COALESCE(NULLIF(l.full_name,''), NULLIF(l.lead_name,''), CONCAT('Lead #', l.id)) AS lead_name,
                                 COALESCE(NULLIF(l.phone,''), l.phone_number) AS phone, l.assigned_id AS owner_id,
                                 'lead_booking_id missing' AS info, o.created_at AS event_date
                          FROM onboard_clients o LEFT JOIN leads l ON l.id = o.lead_id
                          WHERE o.lead_booking_id IS NULL",
            ],
            'booked_not_onboarded' => [
                'group' => 'Booking & onboarding', 'severity' => 'medium',
                'label' => 'Confirmed bookings not onboarded',
                'description' => 'Booking has verified payment but the client has no onboarding record.',
                'impact' => 'Onboarding rate',
                'sql' => "SELECT $bkCols, p2.name AS info, b.booking_date AS event_date
                          FROM lead_bookings b JOIN leads l ON l.id = b.lead_id LEFT JOIN projects p2 ON p2.id = b.project_id
                          WHERE $activeBk
                            AND EXISTS (SELECT 1 FROM lead_payments p WHERE p.booking_id = b.id AND COALESCE(p.is_trashed,0) = 0 AND p.verification_status = 'verified')
                            AND NOT EXISTS (SELECT 1 FROM onboard_clients o WHERE o.lead_booking_id = b.id OR (o.lead_booking_id IS NULL AND o.lead_id = b.lead_id))",
            ],
            'payment_pending_verification' => [
                'group' => 'Booking & onboarding', 'severity' => 'medium',
                'label' => 'Payments waiting for verification',
                'description' => 'Not included in Collection until verified by accounts.',
                'impact' => 'Collection',
                'sql' => "SELECT p.id AS ref_id, b.lead_id, COALESCE(NULLIF(l.full_name,''), NULLIF(l.lead_name,''), CONCAT('Lead #', l.id)) AS lead_name,
                                 COALESCE(NULLIF(l.phone,''), l.phone_number) AS phone, COALESCE(NULLIF(b.sales_by_id,0), l.assigned_id) AS owner_id,
                                 CONCAT('৳', FORMAT(p.amount, 0)) AS info, p.payment_date AS event_date
                          FROM lead_payments p JOIN lead_bookings b ON b.id = p.booking_id JOIN leads l ON l.id = b.lead_id
                          WHERE COALESCE(p.is_trashed,0) = 0 AND p.verification_status = 'pending' AND b.deleted_at IS NULL",
            ],
        ];
    }

    /** Drill-down rows for one data-quality check. */
    public function gapDetails(string $key, int $limit = 300): array
    {
        $this->loadReference();
        $defs = $this->gapDefinitions();
        if (!isset($defs[$key])) {
            return ['label' => 'Unknown check', 'rows' => [], 'total' => 0];
        }
        $sql = "SELECT * FROM (" . $defs[$key]['sql'] . ") x ORDER BY event_date DESC LIMIT " . (int) $limit;
        $res = $this->db->query($sql);
        $rows = [];
        while ($res && $r = $res->fetch_assoc()) {
            $oid = $r['owner_id'] ? (int) $r['owner_id'] : null;
            [$tk] = $this->bucket($oid);
            $r['owner_name'] = $oid ? ($this->users[$oid]['name'] ?? ('User #' . $oid)) : '—';
            $r['team_name'] = $oid ? $this->teamLabel($tk) : '—';
            $rows[] = $r;
        }
        $cnt = $this->db->query("SELECT COUNT(*) c FROM (" . $defs[$key]['sql'] . ") x");
        return [
            'label' => $defs[$key]['label'],
            'description' => $defs[$key]['description'],
            'rows' => $rows,
            'total' => $cnt ? (int) $cnt->fetch_assoc()['c'] : count($rows),
        ];
    }
}
