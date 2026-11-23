<?php

/**
 * TeamFollowUpComparisonService
 * -------------------------------------------------------------------------
 * Service for calculating 1st to 5th follow-up performance rates,
 * on-time vs overdue vs completed late statistics, weighted team comparisons,
 * and salesperson breakdown per lead.
 */
class TeamFollowUpComparisonService
{
    /** @var mysqli */
    protected $db;

    public const SALES_ROLE_IDS = [5, 8, 9]; // Sales Manager, Sales Executive, Sales Team Head
    public const CLOSED_LEAD_STATUSES = ['Sold', 'Refund', 'Not Interested', "Don't Call Again", 'Invalid Lead', 'Irrelevant', 'Lead Transferred', 'Project Transferred', 'Cold'];

    protected array $teams = [];
    protected array $users = [];
    protected array $projects = [];

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Resolve date period / cycle filter.
     */
    public function resolvePeriod(?string $cycle = null, ?int $year = null, ?int $month = null, ?string $from = null, ?string $to = null, ?string $preset = null): array
    {
        $today = date('Y-m-d');

        if ($cycle && str_starts_with($cycle, 'preset:')) {
            $preset = substr($cycle, 7);
        }

        // Quick Presets
        if ($preset === 'today' || $preset === 'daily' || $cycle === 'today') {
            return [
                'type' => 'preset',
                'preset' => 'today',
                'start' => $today . ' 00:00:00',
                'end' => $today . ' 23:59:59',
                'start_date' => $today,
                'end_date' => $today,
                'label' => 'Today (' . date('d M Y') . ')',
                'cycle_label' => 'Daily (Today)',
                'cycle_key' => 'preset:today',
            ];
        }

        if ($preset === 'yesterday' || $cycle === 'yesterday') {
            $yest = date('Y-m-d', strtotime('-1 day'));
            return [
                'type' => 'preset',
                'preset' => 'yesterday',
                'start' => $yest . ' 00:00:00',
                'end' => $yest . ' 23:59:59',
                'start_date' => $yest,
                'end_date' => $yest,
                'label' => 'Yesterday (' . date('d M Y', strtotime($yest)) . ')',
                'cycle_label' => 'Yesterday',
                'cycle_key' => 'preset:yesterday',
            ];
        }

        if ($preset === 'weekly' || $preset === 'week' || $cycle === 'weekly') {
            $startW = date('Y-m-d', strtotime('-6 days'));
            return [
                'type' => 'preset',
                'preset' => 'weekly',
                'start' => $startW . ' 00:00:00',
                'end' => $today . ' 23:59:59',
                'start_date' => $startW,
                'end_date' => $today,
                'label' => date('d M', strtotime($startW)) . ' – ' . date('d M Y'),
                'cycle_label' => 'Weekly (Last 7 Days)',
                'cycle_key' => 'preset:weekly',
            ];
        }

        // Custom Range
        if ($from && $to && strtotime($from) && strtotime($to) && strtotime($from) <= strtotime($to)) {
            $cycleLabel = 'Custom Range';
            if ($from === $today && $to === $today) {
                $cycleLabel = 'Daily (Today)';
            }
            return [
                'type' => 'custom',
                'preset' => 'custom',
                'start' => $from . ' 00:00:00',
                'end' => $to . ' 23:59:59',
                'start_date' => $from,
                'end_date' => $to,
                'label' => date('d M Y', strtotime($from)) . ' – ' . date('d M Y', strtotime($to)),
                'cycle_label' => $cycleLabel,
                'cycle_key' => 'custom',
            ];
        }

        // Monthly sales cycle (26th of last month to 25th of current month)
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

        $prevM = $month - 1;
        $prevY = $year;
        if ($prevM < 1) {
            $prevM = 12;
            $prevY--;
        }
        $startDate = sprintf('%04d-%02d-26 00:00:00', $prevY, $prevM);
        $endDate   = sprintf('%04d-%02d-25 23:59:59', $year, $month);
        $cycleKey  = sprintf('%04d-%02d', $year, $month);

        return [
            'type' => 'month',
            'preset' => 'cycle',
            'year' => $year,
            'month' => $month,
            'start' => $startDate,
            'end' => $endDate,
            'start_date' => substr($startDate, 0, 10),
            'end_date' => substr($endDate, 0, 10),
            'label' => date('d M', strtotime($startDate)) . ' – ' . date('d M Y', strtotime($endDate)),
            'cycle_label' => date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ' Cycle',
            'cycle_key' => $cycleKey,
        ];
    }

    public function cycleOptions(int $count = 6): array
    {
        $opts = [];
        $opts[] = ['value' => 'preset:today', 'label' => '⚡ Today (Daily)'];
        $opts[] = ['value' => 'preset:weekly', 'label' => '⚡ Last 7 Days (Weekly)'];

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
            $key = sprintf('%04d-%02d', $y, $m);
            $label = date('F Y', mktime(0, 0, 0, $m, 1, $y)) . ' (26th-25th)';
            $opts[] = ['value' => $key, 'label' => $label];
            $m--;
            if ($m < 1) {
                $m = 12;
                $y--;
            }
        }

        return $opts;
    }

    /**
     * Load reference cache: teams, sales users, projects.
     */
    protected function loadReference(): void
    {
        if (!empty($this->teams)) {
            return;
        }

        // Teams
        $teams = [];
        $res = $this->db->query("SELECT id, name, status FROM teams ORDER BY name");
        while ($res && $r = $res->fetch_assoc()) {
            $teams[(int) $r['id']] = [
                'id' => (int) $r['id'],
                'name' => trim($r['name']),
                'active' => (int) $r['status'] === 1,
            ];
        }
        $this->teams = $teams;

        // Sales Users - strictly require a valid employee record with non-empty employee_id
        $users = [];
        $sql = "SELECT u.id, u.full_name, u.role_id, u.secondary_role_id, u.photo, r.name AS role_name,
                       COALESCE(NULLIF(u.team_id, 0), NULLIF(e.team_id, 0), 0) AS team_id,
                       e.employee_id,
                       e.employee_status
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                INNER JOIN (
                    SELECT user_id, 
                           MAX(team_id) AS team_id, 
                           MAX(employee_status) AS employee_status,
                           MAX(employee_id) AS employee_id
                    FROM employees 
                    WHERE user_id IS NOT NULL 
                      AND deleted_at IS NULL 
                      AND employee_id IS NOT NULL 
                      AND TRIM(employee_id) != ''
                    GROUP BY user_id
                ) e ON e.user_id = u.id";
        $res = $this->db->query($sql);
        while ($res && $r = $res->fetch_assoc()) {
            $roleId = (int) $r['role_id'];
            $isSales = in_array($roleId, self::SALES_ROLE_IDS, true) 
                || in_array((int) $r['secondary_role_id'], self::SALES_ROLE_IDS, true)
                || strtolower(trim((string) ($r['role_name'] ?? ''))) === 'sales manager';

            $users[(int) $r['id']] = [
                'id' => (int) $r['id'],
                'name' => trim((string) $r['full_name']) ?: ('Executive #' . $r['id']),
                'role_id' => $roleId,
                'role_name' => trim((string) $r['role_name']) ?: 'Sales Executive',
                'team_id' => (int) $r['team_id'],
                'is_sales' => $isSales,
                'employee_id' => trim((string) $r['employee_id']),
                'photo' => $r['photo'],
            ];
        }
        $this->users = $users;

        // Projects
        $projects = [];
        $res = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
        while ($res && $r = $res->fetch_assoc()) {
            $projects[(int) $r['id']] = [
                'id' => (int) $r['id'],
                'name' => trim($r['name']),
            ];
        }
        $this->projects = $projects;
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
        $this->loadReference();
        return array_values($this->projects);
    }

    /**
     * Initialize metric counters for steps 1..5 and overall.
     */
    protected function emptyMetrics(): array
    {
        $m = [
            'overall' => ['ontime' => 0, 'due' => 0, 'overdue' => 0, 'late' => 0, 'rate' => null],
            'steps' => [],
        ];
        for ($s = 1; $s <= 5; $s++) {
            $m['steps'][$s] = ['ontime' => 0, 'due' => 0, 'overdue' => 0, 'late' => 0, 'rate' => null];
        }
        return $m;
    }

    /**
     * Compute percentages for metrics array.
     */
    protected function computeRates(array &$m): void
    {
        for ($s = 1; $s <= 5; $s++) {
            $due = $m['steps'][$s]['due'];
            $ot = $m['steps'][$s]['ontime'];
            $m['steps'][$s]['rate'] = $due > 0 ? round(($ot / $due) * 100, 1) : null;
        }
        $totDue = $m['overall']['due'];
        $totOt  = $m['overall']['ontime'];
        $m['overall']['rate'] = $totDue > 0 ? round(($totOt / $totDue) * 100, 1) : null;
    }

    /**
     * Resolve effective allowed team IDs from team_id and sales_manager_id filters.
     */
    protected function resolveAllowedTeamIds(?int $teamId, ?int $salesManagerId): ?array
    {
        if ($salesManagerId) {
            $mgrTeamIds = function_exists('getManagedTeamIds') ? getManagedTeamIds($this->db, $salesManagerId) : [];
            if ($teamId) {
                return in_array($teamId, $mgrTeamIds, true) ? [$teamId] : [];
            }
            return $mgrTeamIds;
        }
        if ($teamId) {
            return [$teamId];
        }
        return null;
    }

    /**
     * Build the entire comparison dataset.
     */
    public function build(array $period, array $filters = []): array
    {
        $this->loadReference();

        $start = $this->db->real_escape_string($period['start']);
        $end   = $this->db->real_escape_string($period['end']);
        $now   = time();

        $whereClause = "WHERE l.deleted_at IS NULL AND (l.created_at BETWEEN '$start' AND '$end')";

        // Strictly restrict to leads assigned to verified sales staff with valid employee_id
        $validSalesUserIds = array_keys(array_filter($this->users, fn($u) => $u['is_sales']));
        if (!empty($validSalesUserIds)) {
            $validUserList = implode(',', $validSalesUserIds);
            $whereClause .= " AND l.assigned_id IN ($validUserList)";
        } else {
            $whereClause .= " AND 1=0";
        }

        if (!empty($filters['project_id'])) {
            $pid = (int) $filters['project_id'];
            $whereClause .= " AND l.project_id = $pid";
        }
        $allowedTeamIds = $this->resolveAllowedTeamIds(
            !empty($filters['team_id']) ? (int) $filters['team_id'] : null,
            !empty($filters['sales_manager_id']) ? (int) $filters['sales_manager_id'] : null
        );
        if ($allowedTeamIds !== null) {
            $inTeams = !empty($allowedTeamIds) ? implode(',', array_map('intval', $allowedTeamIds)) : '-1';
            // Filter by lead team or assigned user's team
            $whereClause .= " AND (COALESCE(NULLIF(u.team_id, 0), NULLIF(e.team_id, 0), l.team_id) IN ($inTeams))";
        }
        if (!empty($filters['executive_id'])) {
            $eid = (int) $filters['executive_id'];
            $whereClause .= " AND l.assigned_id = $eid";
        }

        // 1. Fetch leads
        $sql = "
            SELECT 
                l.id AS lead_id,
                l.lead_name,
                l.full_name,
                COALESCE(l.phone, l.phone_number) AS phone,
                l.project_id,
                l.assigned_id,
                COALESCE(l.assigned_at, l.created_at) AS assigned_time,
                COALESCE(NULLIF(u.team_id, 0), NULLIF(e.team_id, 0), l.team_id, 0) AS team_id,
                u.full_name AS exec_name,
                u.photo AS exec_photo,
                r.name AS role_name,
                p.name AS project_name
            FROM leads l
            LEFT JOIN users u ON u.id = l.assigned_id
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN (
                SELECT user_id, MAX(team_id) AS team_id 
                FROM employees 
                WHERE user_id IS NOT NULL 
                GROUP BY user_id
            ) e ON e.user_id = l.assigned_id
            LEFT JOIN projects p ON p.id = l.project_id
            $whereClause
            ORDER BY l.id DESC
        ";

        $leadsRes = $this->db->query($sql);
        $leads = [];
        $leadIds = [];
        while ($leadsRes && $row = $leadsRes->fetch_assoc()) {
            $leads[$row['lead_id']] = $row;
            $leadIds[] = (int) $row['lead_id'];
        }

        // 2. Fetch all follow-ups for in-scope leads
        $leadFUs = [];
        if (!empty($leadIds)) {
            $fuSql = "
                SELECT 
                    fu.id,
                    fu.lead_id,
                    fu.follow_up_date,
                    fu.follow_up_time,
                    fu.created_at,
                    fu.next_follow_up_date,
                    fu.status,
                    fu.follow_up_type,
                    fu.discussion_note,
                    fu.lead_status
                FROM lead_follow_ups fu
                JOIN leads l ON l.id = fu.lead_id
                LEFT JOIN users u ON u.id = l.assigned_id
                LEFT JOIN (
                    SELECT user_id, MAX(team_id) AS team_id 
                    FROM employees 
                    WHERE user_id IS NOT NULL 
                    GROUP BY user_id
                ) e ON e.user_id = l.assigned_id
                $whereClause AND fu.deleted_at IS NULL
                ORDER BY fu.lead_id ASC, COALESCE(fu.follow_up_date, fu.created_at) ASC, fu.id ASC
            ";
            $fuRes = $this->db->query($fuSql);
            while ($fuRes && $row = $fuRes->fetch_assoc()) {
                $leadFUs[$row['lead_id']][] = $row;
            }
        }

        // 3. Initialize structures
        $grandStats = $this->emptyMetrics();

        $teamData = [];
        foreach ($this->teams as $tid => $t) {
            $teamData[$tid] = [
                'id' => $tid,
                'name' => $t['name'],
                'exec_count' => 0,
                'exec_ids' => [],
                'total_leads' => 0,
                'metrics' => $this->emptyMetrics(),
            ];
        }

        $execData = [];
        foreach ($this->users as $uid => $u) {
            if (!$u['is_sales']) continue;
            $tid = $u['team_id'];
            if ($tid && isset($teamData[$tid])) {
                $teamData[$tid]['exec_count']++;
                $teamData[$tid]['exec_ids'][] = $uid;
            }
            $execData[$uid] = [
                'id' => $uid,
                'name' => $u['name'],
                'role_name' => $u['role_name'],
                'team_id' => $tid,
                'team_name' => ($tid && isset($this->teams[$tid])) ? $this->teams[$tid]['name'] : 'No Team',
                'photo' => $u['photo'],
                'total_leads' => 0,
                'metrics' => $this->emptyMetrics(),
            ];
        }

        // 4. Iterate over leads and calculate 1st to 5th follow-up steps
        foreach ($leads as $lid => $lead) {
            $uid = (int) $lead['assigned_id'];
            $tid = (int) $lead['team_id'];

            if ($uid > 0 && isset($execData[$uid])) {
                $execData[$uid]['total_leads']++;
            }

            if ($tid > 0 && isset($teamData[$tid])) {
                $teamData[$tid]['total_leads']++;
            }

            $fus = $leadFUs[$lid] ?? [];
            $tAssigned = strtotime($lead['assigned_time']);
            
            // Step 1: 24h SLA from assignment
            $step1Due = $tAssigned + 86400;
            $resStep = [];

            if (!empty($fus[0])) {
                $fu1 = $fus[0];
                $fu1Time = strtotime($fu1['follow_up_date'] ? ($fu1['follow_up_date'] . ' ' . ($fu1['follow_up_time'] ?: '23:59:59')) : $fu1['created_at']);
                $isOnTime = ($fu1Time <= $step1Due || date('Y-m-d', $fu1Time) <= date('Y-m-d', $step1Due));
                $resStep[1] = $isOnTime ? 'ontime' : 'late';
            } else {
                if ($now > $step1Due) {
                    $resStep[1] = 'overdue';
                }
            }

            // Steps 2 to 5:
            for ($s = 2; $s <= 5; $s++) {
                $prevFu = $fus[$s - 2] ?? null;
                if (!$prevFu) {
                    break; // Cannot reach step s if step s-1 was never done
                }

                $curFu = $fus[$s - 1] ?? null;
                $nextDate = $prevFu['next_follow_up_date'];

                if ($curFu) {
                    $curTime = strtotime($curFu['follow_up_date'] ? ($curFu['follow_up_date'] . ' 23:59:59') : $curFu['created_at']);
                    if ($nextDate) {
                        $dueTime = strtotime($nextDate . ' 23:59:59');
                        $resStep[$s] = ($curTime <= $dueTime) ? 'ontime' : 'late';
                    } else {
                        $resStep[$s] = 'ontime';
                    }
                } else {
                    if ($nextDate && strtotime($nextDate . ' 23:59:59') < $now) {
                        $resStep[$s] = 'overdue';
                    }
                }
            }

            // Accumulate metrics
            foreach ($resStep as $s => $statusType) {
                // Grand stats
                $grandStats['steps'][$s][$statusType]++;
                $grandStats['steps'][$s]['due']++;
                $grandStats['overall'][$statusType]++;
                $grandStats['overall']['due']++;

                // Team stats
                if ($tid > 0 && isset($teamData[$tid])) {
                    $teamData[$tid]['metrics']['steps'][$s][$statusType]++;
                    $teamData[$tid]['metrics']['steps'][$s]['due']++;
                    $teamData[$tid]['metrics']['overall'][$statusType]++;
                    $teamData[$tid]['metrics']['overall']['due']++;
                }

                // Executive stats (only for valid employees with employee_id)
                if ($uid > 0 && isset($execData[$uid])) {
                    $execData[$uid]['metrics']['steps'][$s][$statusType]++;
                    $execData[$uid]['metrics']['steps'][$s]['due']++;
                    $execData[$uid]['metrics']['overall'][$statusType]++;
                    $execData[$uid]['metrics']['overall']['due']++;
                }
            }
        }

        // 5. Calculate Rates
        $this->computeRates($grandStats);

        foreach ($teamData as &$t) {
            $this->computeRates($t['metrics']);
        }
        unset($t);

        foreach ($execData as &$e) {
            $this->computeRates($e['metrics']);
        }
        unset($e);

        // 6. Find Top Team
        $activeTeams = array_filter($teamData, fn($t) => $t['metrics']['overall']['due'] > 0);
        $topTeam = null;
        if (!empty($activeTeams)) {
            $sortedTeams = $activeTeams;
            usort($sortedTeams, function($a, $b) {
                $rA = $a['metrics']['overall']['rate'] ?? 0;
                $rB = $b['metrics']['overall']['rate'] ?? 0;
                if ($rA === $rB) {
                    return $b['metrics']['overall']['due'] <=> $a['metrics']['overall']['due'];
                }
                return $rB <=> $rA;
            });
            $topTeam = $sortedTeams[0];
        }

        // 7. Find Top 3 Executives (1st, 2nd, 3rd place)
        $volExecs = array_filter($execData, fn($e) => ($e['metrics']['overall']['due'] ?? 0) >= 5);
        if (empty($volExecs)) {
            $volExecs = array_filter($execData, fn($e) => ($e['metrics']['overall']['due'] ?? 0) > 0);
        }
        usort($volExecs, function($a, $b) {
            $rA = $a['metrics']['overall']['rate'] ?? 0;
            $rB = $b['metrics']['overall']['rate'] ?? 0;
            if ($rA === $rB) {
                if ($a['metrics']['overall']['ontime'] !== $b['metrics']['overall']['ontime']) {
                    return $b['metrics']['overall']['ontime'] <=> $a['metrics']['overall']['ontime'];
                }
                return $b['metrics']['overall']['due'] <=> $a['metrics']['overall']['due'];
            }
            return $rB <=> $rA;
        });
        $topExecutives = array_slice(array_values($volExecs), 0, 3);

        // Sort teams: Most executives first, 0/1 executive teams at the bottom
        $teamsList = array_values($teamData);
        usort($teamsList, function($a, $b) {
            if ($a['exec_count'] !== $b['exec_count']) {
                return $b['exec_count'] <=> $a['exec_count'];
            }
            if ($a['total_leads'] !== $b['total_leads']) {
                return $b['total_leads'] <=> $a['total_leads'];
            }
            return strcmp($a['name'], $b['name']);
        });

        $execsList = array_values(array_filter($execData, fn($e) => $e['total_leads'] > 0 || $e['metrics']['overall']['due'] > 0));

        return [
            'period' => $period,
            'filters' => $filters,
            'grand' => $grandStats,
            'top_team' => $topTeam,
            'top_executives' => $topExecutives,
            'teams' => $teamsList,
            'executives' => $execsList,
            'total_leads_analyzed' => count($leads),
        ];
    }

    /**
     * Get detailed leads list for drilldown modal / AJAX.
     * Returns unique leads with follow-up counts, first follow-up date,
     * next scheduled date, whether next follow-up was given or not,
     * and full step-by-step history cards.
     */
    public function getDrilldownLeads(array $period, array $filters = [], int $limit = 300): array
    {
        $this->loadReference();

        $start = $this->db->real_escape_string($period['start']);
        $end   = $this->db->real_escape_string($period['end']);
        $now   = time();

        $whereClause = "WHERE l.deleted_at IS NULL AND (l.created_at BETWEEN '$start' AND '$end')";

        // Strictly restrict to leads assigned to verified sales staff with valid employee_id
        $validSalesUserIds = array_keys(array_filter($this->users, fn($u) => $u['is_sales']));
        if (!empty($validSalesUserIds)) {
            $validUserList = implode(',', $validSalesUserIds);
            $whereClause .= " AND l.assigned_id IN ($validUserList)";
        } else {
            $whereClause .= " AND 1=0";
        }

        $filterTeamId = !empty($filters['team_id']) ? (int) $filters['team_id'] : 0;
        $filterManagerId = !empty($filters['sales_manager_id']) ? (int) $filters['sales_manager_id'] : 0;
        $filterExecId = !empty($filters['executive_id']) ? (int) $filters['executive_id'] : 0;
        $filterStep   = !empty($filters['step']) ? (int) $filters['step'] : 0; // 1..5
        $filterStatus = !empty($filters['status']) ? trim($filters['status']) : ''; // 'overdue', 'late', 'ontime'

        if (!empty($filters['project_id'])) {
            $pid = (int) $filters['project_id'];
            $whereClause .= " AND l.project_id = $pid";
        }
        $allowedTeamIds = $this->resolveAllowedTeamIds($filterTeamId ?: null, $filterManagerId ?: null);
        if ($filterExecId > 0) {
            $whereClause .= " AND l.assigned_id = $filterExecId";
        } elseif ($allowedTeamIds !== null) {
            $inTeams = !empty($allowedTeamIds) ? implode(',', array_map('intval', $allowedTeamIds)) : '-1';
            $whereClause .= " AND (COALESCE(NULLIF(u.team_id, 0), NULLIF(e.team_id, 0), l.team_id) IN ($inTeams))";
        }

        $sql = "
            SELECT 
                l.id AS lead_id,
                COALESCE(NULLIF(l.lead_name, ''), NULLIF(l.full_name, ''), CONCAT('Lead #', l.id)) AS lead_name,
                COALESCE(NULLIF(l.phone, ''), l.phone_number, '') AS phone,
                l.project_id,
                l.assigned_id,
                COALESCE(l.assigned_at, l.created_at) AS assigned_time,
                COALESCE(NULLIF(u.team_id, 0), NULLIF(e.team_id, 0), l.team_id, 0) AS team_id,
                u.full_name AS exec_name,
                p.name AS project_name
            FROM leads l
            LEFT JOIN users u ON u.id = l.assigned_id
            LEFT JOIN (
                SELECT user_id, MAX(team_id) AS team_id 
                FROM employees 
                WHERE user_id IS NOT NULL 
                GROUP BY user_id
            ) e ON e.user_id = l.assigned_id
            LEFT JOIN projects p ON p.id = l.project_id
            $whereClause
            ORDER BY l.id DESC
            LIMIT 1500
        ";

        $res = $this->db->query($sql);
        $leads = [];
        $leadIds = [];
        while ($res && $r = $res->fetch_assoc()) {
            $leads[$r['lead_id']] = $r;
            $leadIds[] = (int) $r['lead_id'];
        }

        if (empty($leadIds)) {
            return ['total' => 0, 'returned' => 0, 'rows' => []];
        }

        // Fetch follow ups
        $idStr = implode(',', $leadIds);
        $fuSql = "
            SELECT id, lead_id, follow_up_date, follow_up_time, created_at, next_follow_up_date, status, follow_up_type, discussion_note, lead_status
            FROM lead_follow_ups
            WHERE lead_id IN ($idStr) AND deleted_at IS NULL
            ORDER BY lead_id ASC, COALESCE(follow_up_date, created_at) ASC, id ASC
        ";
        $fuRes = $this->db->query($fuSql);
        $leadFUs = [];
        while ($fuRes && $r = $fuRes->fetch_assoc()) {
            $leadFUs[$r['lead_id']][] = $r;
        }

        $matchingRows = [];

        foreach ($leads as $lid => $lead) {
            $fus = $leadFUs[$lid] ?? [];
            $totalFollowups = count($fus);

            $statusUpdates = [];
            foreach ($fus as $fu) {
                $st = trim((string)($fu['lead_status'] ?? ''));
                if ($st !== '') {
                    $statusUpdates[] = $st;
                }
            }
            $latestLeadStatus = !empty($statusUpdates) ? end($statusUpdates) : null;

            $tAssigned = strtotime($lead['assigned_time']);
            $tid = (int) $lead['team_id'];
            $teamName = ($tid && isset($this->teams[$tid])) ? $this->teams[$tid]['name'] : 'No Team';

            $step1Due = $tAssigned + 86400;
            $steps = [];
            $hasOverdue = false;
            $hasLate = false;
            $activeStep = 1;

            // Step 1
            if (!empty($fus[0])) {
                $fu1 = $fus[0];
                $fu1Time = strtotime($fu1['follow_up_date'] ? ($fu1['follow_up_date'] . ' ' . ($fu1['follow_up_time'] ?: '23:59:59')) : $fu1['created_at']);
                $isOnTime = ($fu1Time <= $step1Due || date('Y-m-d', $fu1Time) <= date('Y-m-d', $step1Due));
                if (!$isOnTime) $hasLate = true;

                $nextDate = $fu1['next_follow_up_date'];
                $nextFu = $fus[1] ?? null;
                $nextStatus = 'none';
                $nextLabel = 'None scheduled';
                $nextActual = null;
                $nextOverdueDays = 0;

                if ($nextDate) {
                    if ($nextFu) {
                        $nextTime = strtotime($nextFu['follow_up_date'] ? ($nextFu['follow_up_date'] . ' 23:59:59') : $nextFu['created_at']);
                        $dueNext = strtotime($nextDate . ' 23:59:59');
                        $nextStatus = ($nextTime <= $dueNext) ? 'given_ontime' : 'given_late';
                        $nextActual = $nextFu['follow_up_date'] ?: substr($nextFu['created_at'], 0, 10);
                        $nextLabel = 'Given on ' . $nextActual . ($nextStatus === 'given_ontime' ? ' (On-Time)' : ' (Late)');
                        if ($nextStatus === 'given_late') $hasLate = true;
                    } else {
                        if ($now > strtotime($nextDate . ' 23:59:59')) {
                            $nextOverdueDays = max(1, (int) round(($now - strtotime($nextDate . ' 23:59:59')) / 86400));
                            $nextStatus = 'overdue';
                            $nextLabel = "Not given ({$nextOverdueDays}d overdue)";
                            $hasOverdue = true;
                        } else {
                            $nextStatus = 'scheduled';
                            $nextLabel = 'Scheduled for ' . $nextDate;
                        }
                    }
                }

                $steps[1] = [
                    'step' => 1,
                    'done' => true,
                    'logged_date' => $fu1['follow_up_date'] ?: substr($fu1['created_at'], 0, 10),
                    'logged_time' => $fu1['follow_up_time'] ?: substr($fu1['created_at'], 11, 5),
                    'type' => $fu1['follow_up_type'] ?: 'Follow-up',
                    'lead_status' => trim((string)($fu1['lead_status'] ?? '')) ?: null,
                    'note' => $fu1['discussion_note'] ?: '',
                    'timing_status' => $isOnTime ? 'ontime' : 'late',
                    'scheduled_next_date' => $nextDate,
                    'next_given_status' => $nextStatus,
                    'next_given_label' => $nextLabel,
                    'next_actual_date' => $nextActual,
                    'next_overdue_days' => $nextOverdueDays,
                ];
            } else {
                $isOverdue = ($now > $step1Due);
                if ($isOverdue) $hasOverdue = true;
                $steps[1] = [
                    'step' => 1,
                    'done' => false,
                    'logged_date' => null,
                    'logged_time' => null,
                    'type' => 'Not contacted yet',
                    'note' => '',
                    'timing_status' => $isOverdue ? 'overdue' : 'pending',
                    'overdue_days' => $isOverdue ? max(1, (int) round(($now - $step1Due) / 86400)) : 0,
                    'scheduled_next_date' => null,
                    'next_given_status' => 'none',
                    'next_given_label' => '1st follow-up pending',
                    'next_actual_date' => null,
                    'next_overdue_days' => 0,
                ];
            }

            // Steps 2 to 5
            for ($s = 2; $s <= 5; $s++) {
                $prevFu = $fus[$s - 2] ?? null;
                if (!$prevFu) break;

                $activeStep = $s;
                $prevNextDate = $prevFu['next_follow_up_date'];
                $curFu = $fus[$s - 1] ?? null;

                if ($curFu) {
                    $curTime = strtotime($curFu['follow_up_date'] ? ($curFu['follow_up_date'] . ' 23:59:59') : $curFu['created_at']);
                    $isOnTime = true;
                    if ($prevNextDate) {
                        $isOnTime = ($curTime <= strtotime($prevNextDate . ' 23:59:59'));
                    }
                    if (!$isOnTime) $hasLate = true;

                    $nextDate = $curFu['next_follow_up_date'];
                    $nextFu = $fus[$s] ?? null;
                    $nextStatus = 'none';
                    $nextLabel = 'None scheduled';
                    $nextActual = null;
                    $nextOverdueDays = 0;

                    if ($nextDate) {
                        if ($nextFu) {
                            $nextTime = strtotime($nextFu['follow_up_date'] ? ($nextFu['follow_up_date'] . ' 23:59:59') : $nextFu['created_at']);
                            $dueNext = strtotime($nextDate . ' 23:59:59');
                            $nextStatus = ($nextTime <= $dueNext) ? 'given_ontime' : 'given_late';
                            $nextActual = $nextFu['follow_up_date'] ?: substr($nextFu['created_at'], 0, 10);
                            $nextLabel = 'Given on ' . $nextActual . ($nextStatus === 'given_ontime' ? ' (On-Time)' : ' (Late)');
                            if ($nextStatus === 'given_late') $hasLate = true;
                        } else {
                            if ($now > strtotime($nextDate . ' 23:59:59')) {
                                $nextOverdueDays = max(1, (int) round(($now - strtotime($nextDate . ' 23:59:59')) / 86400));
                                $nextStatus = 'overdue';
                                $nextLabel = "Not given ({$nextOverdueDays}d overdue)";
                                $hasOverdue = true;
                            } else {
                                $nextStatus = 'scheduled';
                                $nextLabel = 'Scheduled for ' . $nextDate;
                            }
                        }
                    }

                    $steps[$s] = [
                        'step' => $s,
                        'done' => true,
                        'logged_date' => $curFu['follow_up_date'] ?: substr($curFu['created_at'], 0, 10),
                        'logged_time' => $curFu['follow_up_time'] ?: substr($curFu['created_at'], 11, 5),
                        'type' => $curFu['follow_up_type'] ?: 'Follow-up',
                        'lead_status' => trim((string)($curFu['lead_status'] ?? '')) ?: null,
                        'note' => $curFu['discussion_note'] ?: '',
                        'timing_status' => $isOnTime ? 'ontime' : 'late',
                        'scheduled_next_date' => $nextDate,
                        'next_given_status' => $nextStatus,
                        'next_given_label' => $nextLabel,
                        'next_actual_date' => $nextActual,
                        'next_overdue_days' => $nextOverdueDays,
                    ];
                } else {
                    if ($prevNextDate && $now > strtotime($prevNextDate . ' 23:59:59')) {
                        $overdueDays = max(1, (int) round(($now - strtotime($prevNextDate . ' 23:59:59')) / 86400));
                        $hasOverdue = true;
                        $steps[$s] = [
                            'step' => $s,
                            'done' => false,
                            'logged_date' => null,
                            'logged_time' => null,
                            'type' => 'Pending schedule',
                            'note' => '',
                            'timing_status' => 'overdue',
                            'overdue_days' => $overdueDays,
                            'scheduled_due_date' => $prevNextDate,
                            'scheduled_next_date' => null,
                            'next_given_status' => 'overdue',
                            'next_given_label' => "Not given ({$overdueDays}d overdue)",
                            'next_actual_date' => null,
                            'next_overdue_days' => $overdueDays,
                        ];
                    }
                }
            }

            $overallLeadStatus = $hasOverdue ? 'overdue' : ($hasLate ? 'late' : 'ontime');

            // Find most recent logged follow up to get scheduled next date
            $lastLoggedFu = !empty($fus) ? end($fus) : null;
            $scheduledNextDate = $lastLoggedFu ? $lastLoggedFu['next_follow_up_date'] : null;

            // Get next given status from the step that has the scheduled next date
            $latestStepWithNext = null;
            foreach ($steps as $st) {
                if (!empty($st['scheduled_next_date'])) {
                    $latestStepWithNext = $st;
                }
            }
            $nextGivenStatus = $latestStepWithNext ? $latestStepWithNext['next_given_status'] : 'none';
            $nextGivenLabel  = $latestStepWithNext ? $latestStepWithNext['next_given_label'] : 'None scheduled';
            $nextOverdueDays = $latestStepWithNext ? ($latestStepWithNext['next_overdue_days'] ?? 0) : 0;

            // Step filter check
            if ($filterStep > 0 && !isset($steps[$filterStep])) {
                continue;
            }

            // Status filter check
            if ($filterStatus) {
                if ($filterStep > 0) {
                    if (($steps[$filterStep]['timing_status'] ?? '') !== $filterStatus) {
                        continue;
                    }
                } else {
                    $statusMatched = false;
                    foreach ($steps as $st) {
                        if (($st['timing_status'] ?? '') === $filterStatus) {
                            $statusMatched = true;
                            break;
                        }
                    }
                    if (!$statusMatched) {
                        continue;
                    }
                }
            }

            $matchingRows[] = [
                'lead_id' => (int) $lead['lead_id'],
                'lead_name' => $lead['lead_name'],
                'phone' => $lead['phone'],
                'project_name' => $lead['project_name'] ?: 'Not Assigned',
                'exec_name' => $lead['exec_name'] ?: 'Unassigned',
                'assigned_id' => (int) $lead['assigned_id'],
                'team_name' => $teamName,
                'total_followups' => $totalFollowups,
                'status_updates' => $statusUpdates,
                'status_update_count' => count($statusUpdates),
                'latest_lead_status' => $latestLeadStatus,
                'first_followup' => [
                    'date' => $steps[1]['logged_date'] ?? null,
                    'time' => $steps[1]['logged_time'] ?? null,
                    'status' => $steps[1]['timing_status'] ?? 'pending',
                    'type' => $steps[1]['type'] ?? '',
                    'note' => $steps[1]['note'] ?? '',
                ],
                'next_followup' => [
                    'scheduled_date' => $scheduledNextDate,
                    'status' => $nextGivenStatus,
                    'label' => $nextGivenLabel,
                    'overdue_days' => $nextOverdueDays,
                ],
                'active_step' => $activeStep,
                'overall_status' => $overallLeadStatus,
                'steps' => $steps,
            ];
        }

        $totalMatches = count($matchingRows);
        $sliced = array_slice($matchingRows, 0, $limit);

        return [
            'total' => $totalMatches,
            'returned' => count($sliced),
            'rows' => $sliced,
        ];
    }
}
