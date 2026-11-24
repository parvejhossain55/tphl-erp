<?php

class TrackerCalculationService
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Determine the date range for a given month/year or custom range
     * Default: 26th of previous month to 25th of current month
     */
    public function getDateRange($year, $month, $customStart = null, $customEnd = null)
    {
        if (!empty($customStart) && !empty($customEnd)) {
            return [
                'start' => date('Y-m-d 00:00:00', strtotime($customStart)),
                'end'   => date('Y-m-d 23:59:59', strtotime($customEnd)),
                'is_custom' => true
            ];
        }

        $year = (int)$year;
        $month = (int)$month;
        
        // Special historical transition rules for 2026:
        // May 2026: 1 May - 31 May
        // June 2026: 1 June - 30 June
        // July 2026: 1 July - 25 July
        // August 2026 onward: 26th of previous month to 25th of month (e.g. 26 July - 25 August)
        if ($year === 2026) {
            if ($month === 5) {
                return ['start' => '2026-05-01 00:00:00', 'end' => '2026-05-31 23:59:59', 'is_custom' => false];
            }
            if ($month === 6) {
                return ['start' => '2026-06-01 00:00:00', 'end' => '2026-06-30 23:59:59', 'is_custom' => false];
            }
            if ($month === 7) {
                return ['start' => '2026-07-01 00:00:00', 'end' => '2026-07-25 23:59:59', 'is_custom' => false];
            }
        }

        $prevMonth = $month - 1;
        $prevYear = $year;
        
        if ($prevMonth == 0) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $startDate = sprintf('%04d-%02d-26 00:00:00', $prevYear, $prevMonth);
        $endDate   = sprintf('%04d-%02d-25 23:59:59', $year, $month);

        return [
            'start' => $startDate, 
            'end'   => $endDate,
            'is_custom' => false
        ];
    }

    /**
     * Consolidate assigned project IDs from both explicitly chosen projects
     * AND all projects belonging to chosen categories
     */
    public function resolveAssignedProjectIds($projectIdsRaw, $categoryIdRaw, $categoryIdsRaw, &$categoryProjectsCache = [])
    {
        $assigned = [];

        // 1. Explicitly selected project IDs
        if (!empty($projectIdsRaw)) {
            $decoded = json_decode($projectIdsRaw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $pid) {
                    if (!empty($pid)) $assigned[] = (int)$pid;
                }
            } else {
                $parts = explode(',', (string)$projectIdsRaw);
                foreach ($parts as $pid) {
                    $pid = trim($pid, "[]\"' ");
                    if ($pid !== '' && is_numeric($pid)) $assigned[] = (int)$pid;
                }
            }
        }

        // 2. Selected Category IDs
        $catIds = [];
        if (!empty($categoryIdsRaw)) {
            $decodedCats = json_decode($categoryIdsRaw, true);
            if (is_array($decodedCats)) {
                foreach ($decodedCats as $cid) {
                    if (!empty($cid)) $catIds[] = (int)$cid;
                }
            } else {
                $parts = explode(',', (string)$categoryIdsRaw);
                foreach ($parts as $cid) {
                    $cid = trim($cid, "[]\"' ");
                    if ($cid !== '' && is_numeric($cid)) $catIds[] = (int)$cid;
                }
            }
        }
        if (!empty($categoryIdRaw) && !in_array((int)$categoryIdRaw, $catIds)) {
            $catIds[] = (int)$categoryIdRaw;
        }

        // 3. Expand Category IDs to all member project IDs
        if (!empty($catIds)) {
            foreach ($catIds as $cid) {
                if (!isset($categoryProjectsCache[$cid])) {
                    $categoryProjectsCache[$cid] = [];
                    $pRes = $this->db->query("SELECT id FROM projects WHERE category_id = " . (int)$cid . " AND deleted_at IS NULL");
                    if ($pRes) {
                        while ($pr = $pRes->fetch_assoc()) {
                            $categoryProjectsCache[$cid][] = (int)$pr['id'];
                        }
                    }
                }
                foreach ($categoryProjectsCache[$cid] as $pid) {
                    $assigned[] = (int)$pid;
                }
            }
        }

        return array_values(array_unique($assigned));
    }

    /**
     * Get the visibility scope for a given user
     */
    public function getVisibilityScope($user, $filterTeamId = null)
    {
        $roleId = (int)($user['role_id'] ?? 0);
        $teamId = !empty($user['team_id']) ? (int)$user['team_id'] : null;
        $roleName = strtolower($user['role_name'] ?? '');
        $userName = strtolower($user['full_name'] ?? ($user['user_name'] ?? ''));

        // Check if employee record has team_id and department_id
        $empId = null;
        $empDeptId = null;
        if (!empty($user['id'])) {
            $empCheck = $this->db->query("SELECT id, team_id, department_id FROM employees WHERE user_id = " . (int)$user['id'] . " LIMIT 1");
            if ($empCheck && $row = $empCheck->fetch_assoc()) {
                $empId = (int)$row['id'];
                $empDeptId = !empty($row['department_id']) ? (int)$row['department_id'] : null;
                if (empty($teamId) && !empty($row['team_id'])) {
                    $teamId = (int)$row['team_id'];
                }
            }
        }
        
        // 1. Check Sales Manager (multi-team via sales_manager_teams) BEFORE global admin
        //    so a Sales Manager whose role_id happens to be in any list is still scoped to assigned teams.
        $isSalesMgr = (function_exists('isSalesManager') && isSalesManager()) || $roleName === 'sales manager';
        if ($isSalesMgr) {
            $managedIds = function_exists('getManagedTeamIds')
                ? getManagedTeamIds($this->db, (int)($user['id'] ?? 0))
                : [];
            if (!empty($filterTeamId) && in_array((int)$filterTeamId, $managedIds, true)) {
                return ['type' => 'TEAM', 'team_id' => (int)$filterTeamId];
            }
            return ['type' => 'TEAMS', 'team_ids' => $managedIds];
        }

        // 1b. Sales Team Leader (Role 5) is ALWAYS scoped strictly to their own team
        if ($roleId === 5 || str_contains($roleName, 'team leader')) {
            return ['type' => 'TEAM', 'team_id' => (int)($teamId ?? 0)];
        }

        // 1c. Sales Executive (Role 8) is scoped to their own team or self (never global admin)
        if ($roleId === 8) {
            if (!empty($teamId)) {
                return ['type' => 'TEAM', 'team_id' => (int)$teamId];
            }
            return ['type' => 'SELF', 'user_id' => (int)($user['id'] ?? 0)];
        }

        // 2. Management, Directors, Super Admins, System Admins, HR, Accounts, Purchase, etc.
        // Also: Any non-sales employee (department_id != 1) who has permission to view the report
        // should view the full Company-Wide report rather than restricting to a non-existent personal sales record.
        $isGlobalAdmin = (
            in_array($roleId, [1, 3, 4, 6, 14, 15, 17, 20, 22, 23, 24, 25, 30, 31, 32, 33, 34, 35, 36])
            || str_contains($roleName, 'admin') 
            || str_contains($roleName, 'chief') 
            || str_contains($roleName, 'director')
            || str_contains($roleName, 'management')
            || str_contains($roleName, 'ceo')
            || str_contains($userName, 'admin')
            || ($empDeptId !== null && $empDeptId !== 1)
        );

        // 3. Head of Sales (Role 9) oversees all sales teams across the company.
        // Sales Team Leader (Role 5) is restricted to their own team (handled in step 4).
        $isSalesHead = (
            $roleId === 9
            || str_contains($roleName, 'sales team head') 
            || str_contains($roleName, 'head of sales')
            || str_contains($roleName, 'sales head')
            || (str_contains($roleName, 'head') && str_contains($roleName, 'sales'))
        );

        if ($isGlobalAdmin || $isSalesHead) {
            if (is_array($filterTeamId)) {
                $ids = array_values(array_unique(array_filter(array_map('intval', $filterTeamId), fn($id) => $id > 0)));
                return ['type' => 'TEAMS', 'team_ids' => !empty($ids) ? $ids : [-1]];
            }
            if (!empty($filterTeamId) && (int)$filterTeamId > 0) {
                return ['type' => 'TEAM', 'team_id' => (int)$filterTeamId];
            }
            return ['type' => 'ALL', 'team_id' => null];
        }

        // 3. Fallback: If user has no employee record or is not in sales department, show ALL
        if (empty($empId) || ($empDeptId !== null && $empDeptId !== 1)) {
            if (is_array($filterTeamId)) {
                $ids = array_values(array_unique(array_filter(array_map('intval', $filterTeamId), fn($id) => $id > 0)));
                return ['type' => 'TEAMS', 'team_ids' => !empty($ids) ? $ids : [-1]];
            }
            if (!empty($filterTeamId) && (int)$filterTeamId > 0) {
                return ['type' => 'TEAM', 'team_id' => (int)$filterTeamId];
            }
            return ['type' => 'ALL', 'team_id' => null];
        }

        // 4. Team-level scope: Team Leader or Member assigned to a specific team
        if (!empty($teamId)) {
            return ['type' => 'TEAM', 'team_id' => $teamId];
        }

        // 5. Individual Sales Executive without team
        return ['type' => 'SELF', 'user_id' => (int)($user['id'] ?? 0)];
    }

    /**
     * Get Tracker Data for the specific month/year or custom range
     */
    public function getTrackerReport($year, $month, $loggedInUser, $customStart = null, $customEnd = null, $filterTeamId = null)
    {
        $dateRange = $this->getDateRange($year, $month, $customStart, $customEnd);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $scope = $this->getVisibilityScope($loggedInUser, $filterTeamId);

        // Build the base condition for employees: only active sales department employees
        $empCondition = "e.department_id = 1 AND LOWER(e.employee_status) = 'active'";
        if ($scope['type'] === 'TEAM') {
            $empCondition .= " AND COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0), 0) = " . (int)$scope['team_id'];
        } elseif ($scope['type'] === 'TEAMS') {
            $tIds = !empty($scope['team_ids']) ? implode(',', array_map('intval', $scope['team_ids'])) : '0';
            $empCondition .= " AND COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0), 0) IN ($tIds)";
        } elseif ($scope['type'] === 'SELF') {
            $empCondition .= " AND e.user_id = " . (int)$scope['user_id'];
        }

        $monthString = sprintf('%04d-%02d-01', $year, $month);

        $query = "
            SELECT 
                e.user_id,
                e.full_name as member_name,
                e.office_branch_id,
                COALESCE(ob.name, 'Other') as branch_name,
                COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0), 0) as team_id,
                COALESCE(t.name, 'General Team') as team_name,
                e.reporting_user_id,
                r.name as role_name,
                u.role_id,
                st.sales_role,
                CASE 
                    WHEN LOWER(COALESCE(r.name, '')) LIKE '%manager%' OR LOWER(COALESCE(r.name, '')) LIKE '%head%' OR u.role_id IN (5, 9) THEN
                        CASE 
                            WHEN st.sales_role IS NOT NULL AND st.sales_role != '' THEN CONCAT('Sales Team Leader (', st.sales_role, ')')
                            ELSE 'Sales Team Leader'
                        END
                    ELSE
                        CASE 
                            WHEN st.sales_role IS NOT NULL AND st.sales_role != '' THEN CONCAT('Sales Executive (', st.sales_role, ')')
                            ELSE 'Sales Executive'
                        END
                END as level_designation,
                COALESCE(st.booking_target, 0) as unit_target,
                COALESCE(st.booking_value_target, 0) as volume_target,
                COALESCE(st.site_visit_target, 0) as visit_target,
                st.project_ids as assigned_project_ids,
                st.category_id,
                st.category_ids,
                st.from_date,
                st.to_date
            FROM employees e
            INNER JOIN users u ON e.user_id = u.id
            LEFT JOIN teams t ON COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0)) = t.id
            LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN sales_targets st ON e.user_id = st.sales_user_id AND st.month = '$monthString'
            WHERE $empCondition
        ";

        try {
            $employeesResult = $this->db->query($query);
        } catch (Exception $ex) {
            $employeesResult = false;
        }

        $employees = [];
        $categoryProjectsCache = [];
        
        // Fallback without teams table if needed
        if (!$employeesResult) {
            $query = "
                SELECT 
                    e.user_id,
                    e.full_name as member_name,
                    e.office_branch_id,
                    COALESCE(ob.name, 'Other') as branch_name,
                    e.team_id,
                    'Team ' as team_name,
                    e.reporting_user_id,
                    r.name as role_name,
                    u.role_id,
                    st.sales_role,
                    CASE 
                        WHEN LOWER(COALESCE(r.name, '')) LIKE '%manager%' OR LOWER(COALESCE(r.name, '')) LIKE '%head%' OR u.role_id IN (5, 9) THEN
                            CASE 
                                WHEN st.sales_role IS NOT NULL AND st.sales_role != '' THEN CONCAT('Sales Team Leader (', st.sales_role, ')')
                                ELSE 'Sales Team Leader'
                            END
                        ELSE
                            CASE 
                                WHEN st.sales_role IS NOT NULL AND st.sales_role != '' THEN CONCAT('Sales Executive (', st.sales_role, ')')
                                ELSE 'Sales Executive'
                            END
                    END as level_designation,
                    COALESCE(st.booking_target, 0) as unit_target,
                    COALESCE(st.booking_value_target, 0) as volume_target,
                    COALESCE(st.site_visit_target, 0) as visit_target,
                    st.project_ids as assigned_project_ids,
                    st.category_id,
                    st.category_ids,
                    st.from_date,
                    st.to_date
                FROM employees e
                INNER JOIN users u ON e.user_id = u.id
                LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN sales_targets st ON e.user_id = st.sales_user_id AND st.month = '$monthString'
                WHERE $empCondition
            ";
            try {
                $employeesResult = $this->db->query($query);
            } catch (Exception $ex) {
                $employeesResult = false;
            }
        }

        if ($employeesResult) {
            while ($row = $employeesResult->fetch_assoc()) {
                if (empty($row['team_name']) && !empty($row['team_id'])) {
                    $row['team_name'] = 'Team ' . $row['team_id'];
                }

                $assignedProjects = $this->resolveAssignedProjectIds(
                    $row['assigned_project_ids'] ?? null,
                    $row['category_id'] ?? null,
                    $row['category_ids'] ?? null,
                    $categoryProjectsCache
                );

                // Format level designation with Sales Team Leader / Sales Executive and (KAM)/(KAE)
                $isLeader = (
                    (!empty($row['role_name']) && (stripos($row['role_name'], 'manager') !== false || stripos($row['role_name'], 'head') !== false || stripos($row['role_name'], 'lead') !== false))
                    || (!empty($row['role_id']) && in_array((int)$row['role_id'], [5, 9]))
                );
                $baseRole = $isLeader ? 'Sales Team Leader' : 'Sales Executive';
                $targetRole = !empty($row['sales_role']) ? strtoupper(trim($row['sales_role'])) : '';
                $levelDesignation = !empty($targetRole) ? "{$baseRole} ({$targetRole})" : $baseRole;

                $employees[$row['user_id']] = [
                    'user_id' => (int)$row['user_id'],
                    'member_name' => $row['member_name'],
                    'branch_name' => $row['branch_name'] ?? 'Other',
                    'team_name' => $row['team_name'] ?: 'General Team',
                    'team_id' => !empty($row['team_id']) ? (int)$row['team_id'] : 0,
                    'level_designation' => $levelDesignation,
                    'unit_target' => (int)$row['unit_target'],
                    'volume_target' => (float)$row['volume_target'],
                    'visit_target' => (int)$row['visit_target'],
                    'target_from_date' => $row['from_date'] ?? null,
                    'target_to_date' => $row['to_date'] ?? null,
                    'assigned_project_ids' => $assignedProjects,
                    'assigned_leads' => 0,
                    'total_follow_up_calls' => 0,
                    'unique_leads_followed' => 0,
                    'total_contacted' => 0,
                    'total_not_contacted' => 0,
                    'interested' => 0,
                    'not_interested' => 0,
                    'will_visit' => 0,
                    'visited' => 0,
                    'units_sold' => 0,
                    'volume_assigned' => 0,
                    'volume_unassigned' => 0,
                    'total_volume_achieved' => 0,
                    'total_collected' => 0.0,
                    'verified_collection' => 0.0,
                    'pending_collection' => 0.0,
                    'collection_details' => [],
                    'performance_score' => 0,
                    'total_collected' => 0.0,
                    'verified_collection' => 0.0,
                    'pending_collection' => 0.0,
                    'collection_details' => [],
                    'sold_units_details' => []
                ];
            }
        }

        if (empty($employees)) {
            return [
                'period' => [
                    'start' => date('d M, Y', strtotime($start)),
                    'end'   => date('d M, Y', strtotime($end)),
                    'is_custom' => $dateRange['is_custom']
                ],
                'individuals' => [],
                'teams' => [],
                'kpis' => []
            ];
        }

        $userIdsArr = array_filter(array_keys($employees));
        if (empty($userIdsArr)) {
            return [
                'period' => [
                    'start' => date('d M, Y', strtotime($start)),
                    'end'   => date('d M, Y', strtotime($end)),
                    'is_custom' => $dateRange['is_custom']
                ],
                'individuals' => [],
                'teams' => [],
                'kpis' => []
            ];
        }
        $userIds = implode(',', $userIdsArr);

        // 1. Assigned Leads: includes assigned_at within period, created_at within period, or actively worked leads
        $leadsQ = "
            SELECT assigned_id, COUNT(DISTINCT id) as cnt 
            FROM leads 
            WHERE assigned_id IN ($userIds)
              AND (
                  (assigned_at IS NOT NULL AND assigned_at BETWEEN '$start' AND '$end')
                  OR (assigned_at IS NULL AND created_at BETWEEN '$start' AND '$end')
                  OR (id IN (
                      SELECT DISTINCT lead_id 
                      FROM lead_follow_ups 
                      WHERE created_by IN ($userIds) AND created_at BETWEEN '$start' AND '$end'
                  ))
              )
            GROUP BY assigned_id
        ";
        $leadsRes = $this->db->query($leadsQ);
        if ($leadsRes) {
            while ($row = $leadsRes->fetch_assoc()) {
                if (isset($employees[$row['assigned_id']])) {
                    $employees[$row['assigned_id']]['assigned_leads'] = (int)$row['cnt'];
                }
            }
        }

        // 2. Follow Ups, Contacted, Interested, Visits
        $fuQ = "
            SELECT lead_id, created_by, follow_up_type, lead_status, next_action, next_follow_up_date, created_at 
            FROM lead_follow_ups 
            WHERE created_at BETWEEN '$start' AND '$end' AND created_by IN ($userIds)
            ORDER BY created_at ASC
        ";
        $fuRes = $this->db->query($fuQ);
        
        $leadLatestState = []; // user_id => [lead_id => state]

        if ($fuRes) {
            while ($row = $fuRes->fetch_assoc()) {
                $uid = (int)$row['created_by'];
                $lid = (int)$row['lead_id'];

                if (!isset($employees[$uid])) continue;

                $employees[$uid]['total_follow_up_calls']++;

                if (!isset($leadLatestState[$uid])) {
                    $leadLatestState[$uid] = [];
                }
                
                if (!isset($leadLatestState[$uid][$lid])) {
                    $leadLatestState[$uid][$lid] = [
                        'contacted' => false,
                        'latest_status' => '',
                        'is_visited' => false,
                        'is_will_visit' => false,
                        'will_visit_date' => null
                    ];
                }

                $invalidTypes = ['Number Off', 'Wrong Number', 'Invalid Number'];
                if (!in_array($row['follow_up_type'], $invalidTypes)) {
                    $leadLatestState[$uid][$lid]['contacted'] = true;
                }

                // Overwrite with latest status
                $leadLatestState[$uid][$lid]['latest_status'] = $row['lead_status'];
                
                $statusLower = strtolower(trim($row['lead_status'] ?? ''));
                $actionLower = strtolower(trim($row['next_action'] ?? ''));

                if (in_array($statusLower, ['visited', 'visit completed']) || in_array($actionLower, ['visited', 'visit completed'])) {
                    $leadLatestState[$uid][$lid]['is_visited'] = true;
                    $leadLatestState[$uid][$lid]['is_will_visit'] = false;
                } elseif (in_array($actionLower, ['site visit', 'will visit', 'visit scheduled']) || in_array($statusLower, ['will visit', 'visit scheduled'])) {
                    if (!$leadLatestState[$uid][$lid]['is_visited']) {
                        $leadLatestState[$uid][$lid]['is_will_visit'] = true;
                        $leadLatestState[$uid][$lid]['will_visit_date'] = $row['next_follow_up_date'];
                    }
                }
            }
        }

        // Process latest states per distinct lead
        $interestedStatuses = ['potential', 'high potential', 'qualification in progress', 'prospect', 'high prospect', 'future', 'cold', 'hot', 'warm', 'visit scheduled', 'visit completed', 'will visit', 'visited'];
        $notInterestedStatuses = ['not interested', "don't call again", 'invalid lead', 'irrelevant', 'refund'];

        foreach ($leadLatestState as $uid => $leadsData) {
            if (!isset($employees[$uid])) continue;

            foreach ($leadsData as $lid => $data) {
                $employees[$uid]['unique_leads_followed']++;
                if ($data['contacted']) {
                    $employees[$uid]['total_contacted']++;
                }

                $curStatus = strtolower(trim($data['latest_status']));
                if (in_array($curStatus, $interestedStatuses)) {
                    $employees[$uid]['interested']++;
                } elseif (in_array($curStatus, $notInterestedStatuses)) {
                    $employees[$uid]['not_interested']++;
                }

                if ($data['is_visited']) {
                    $employees[$uid]['visited']++;
                } elseif ($data['is_will_visit']) {
                    $employees[$uid]['will_visit']++;
                }
            }
        }

        // 3. Bookings and Sales Volume
        $bkQ = "
            SELECT b.id as booking_id, b.sales_by_id, b.project_id, b.flat_id, b.agreement_value, b.booking_date, b.booking_status,
                   p.name as project_name, f.flat_name, f.floor_no, f.sqft,
                   COALESCE(NULLIF(f.land_share_value, 0), b.agreement_value, 0) as land_share_val,
                   COALESCE(NULLIF(l.full_name, ''), NULLIF(l.lead_name, ''), 'N/A') as lead_name,
                   COALESCE(NULLIF(l.phone, ''), NULLIF(l.phone_number, '')) as lead_phone
            FROM lead_bookings b
            LEFT JOIN flats f ON b.flat_id = f.id
            LEFT JOIN projects p ON b.project_id = p.id
            LEFT JOIN leads l ON b.lead_id = l.id
            WHERE b.booking_status IN ('booked', 'completed') 
              AND b.booking_date BETWEEN DATE('$start') AND DATE('$end')
              AND b.sales_by_id IN ($userIds)
        ";
        $bkRes = $this->db->query($bkQ);
        if ($bkRes) {
            while ($row = $bkRes->fetch_assoc()) {
                $uid = (int)$row['sales_by_id'];
                if (isset($employees[$uid])) {
                    $employees[$uid]['units_sold']++;
                    
                    $assignedProjects = $employees[$uid]['assigned_project_ids'];
                    $isAssigned = (!empty($assignedProjects) && in_array($row['project_id'], $assignedProjects));
                    $effVal = $isAssigned ? (float)$row['agreement_value'] : (float)$row['land_share_val'];

                    if ($isAssigned) {
                        $employees[$uid]['volume_assigned'] += (float)$row['agreement_value'];
                    } else {
                        $employees[$uid]['volume_unassigned'] += (float)$row['land_share_val'];
                    }

                    $unitLabel = trim(($row['floor_no'] ? 'Floor ' . $row['floor_no'] . ', ' : '') . ($row['flat_name'] ?: 'Unit #' . $row['flat_id']));
                    if (empty($unitLabel)) {
                        $unitLabel = 'Unit #' . $row['flat_id'];
                    }

                    $employees[$uid]['sold_units_details'][] = [
                        'booking_id' => (int)$row['booking_id'],
                        'booking_date' => $row['booking_date'],
                        'booking_status' => ucfirst($row['booking_status']),
                        'project_name' => $row['project_name'] ?: 'Project #' . $row['project_id'],
                        'unit_label' => $unitLabel,
                        'sqft' => $row['sqft'] ?: '-',
                        'client_name' => $row['lead_name'],
                        'client_phone' => $row['lead_phone'] ?: '-',
                        'agreement_value' => (float)$row['agreement_value'],
                        'land_share_value' => (float)$row['land_share_val'],
                        'effective_volume' => $effVal,
                        'is_assigned' => $isAssigned
                    ];
                }
            }
        }

        // 3.1 Collections (Payments Received in Period)
        $collQ = "
            SELECT pay.id as payment_id, pay.booking_id, pay.amount, 
                   COALESCE(pay.payment_date, DATE(pay.created_at)) as payment_date,
                   pay.payment_method, pay.payment_type, pay.verification_status, pay.transaction_ref,
                   COALESCE(NULLIF(b.sales_by_id, 0), NULLIF(b.created_by, 0), NULLIF(l.assigned_id, 0), pay.created_by) AS sales_user_id,
                   b.project_id, p.name AS project_name, f.flat_name, f.floor_no,
                   COALESCE(NULLIF(l.full_name, ''), NULLIF(l.lead_name, ''), 'N/A') AS client_name,
                   COALESCE(NULLIF(l.phone, ''), NULLIF(l.phone_number, ''), '-') AS client_phone
            FROM lead_payments pay
            JOIN lead_bookings b ON b.id = pay.booking_id AND b.deleted_at IS NULL AND b.booking_status IN ('booked', 'completed')
            LEFT JOIN leads l ON b.lead_id = l.id
            LEFT JOIN projects p ON b.project_id = p.id
            LEFT JOIN flats f ON b.flat_id = f.id
            WHERE (
                b.sales_by_id IN ($userIds) 
                OR ( (b.sales_by_id IS NULL OR b.sales_by_id = 0) AND (b.created_by IN ($userIds) OR pay.created_by IN ($userIds)) )
            )
              AND COALESCE(pay.payment_date, DATE(pay.created_at)) BETWEEN DATE('$start') AND DATE('$end')
              AND (pay.is_trashed = 0 OR pay.is_trashed IS NULL)
              AND (pay.is_emi_discount = 0 OR pay.is_emi_discount IS NULL)
              AND (pay.verification_status != 'rejected' OR pay.verification_status IS NULL)
            ORDER BY pay.payment_date DESC, pay.id DESC
        ";
        $collRes = $this->db->query($collQ);
        if ($collRes) {
            while ($row = $collRes->fetch_assoc()) {
                $uid = (int)$row['sales_user_id'];
                if (isset($employees[$uid])) {
                    $amt = (float)$row['amount'];
                    $isVer = (strtolower(trim($row['verification_status'] ?? '')) === 'verified');
                    
                    $employees[$uid]['total_collected'] += $amt;
                    if ($isVer) {
                        $employees[$uid]['verified_collection'] += $amt;
                    } else {
                        $employees[$uid]['pending_collection'] += $amt;
                    }

                    $unitLabel = trim(($row['floor_no'] ? 'Floor ' . $row['floor_no'] . ', ' : '') . ($row['flat_name'] ?: 'Unit #' . $row['flat_id']));
                    if (empty($unitLabel)) {
                        $unitLabel = 'Unit #' . ($row['flat_id'] ?? '-');
                    }

                    $employees[$uid]['collection_details'][] = [
                        'payment_id' => (int)$row['payment_id'],
                        'receipt_no' => 'REC-' . str_pad($row['payment_id'], 6, '0', STR_PAD_LEFT),
                        'booking_id' => (int)$row['booking_id'],
                        'payment_date' => $row['payment_date'],
                        'amount' => $amt,
                        'payment_method' => ucfirst($row['payment_method'] ?? 'Cash'),
                        'payment_type' => ucwords(str_replace('_', ' ', $row['payment_type'] ?? 'Payment')),
                        'verification_status' => $row['verification_status'] ?: 'pending',
                        'is_verified' => $isVer,
                        'client_name' => $row['client_name'],
                        'client_phone' => $row['client_phone'],
                        'project_name' => $row['project_name'] ?: 'Project #' . $row['project_id'],
                        'unit_label' => $unitLabel
                    ];
                }
            }
        }

        // 4. Data Quality: Hot and High Prospect Leads
        $qLeadQ = "
            SELECT assigned_id, COUNT(DISTINCT id) as cnt 
            FROM leads 
            WHERE assigned_id IN ($userIds)
              AND (lead_type IN ('High Potential', 'Hot', 'High Prospect') OR lead_quality = 'High Quality')
              AND deleted_at IS NULL
              AND (
                  (assigned_at IS NOT NULL AND assigned_at BETWEEN '$start' AND '$end')
                  OR (assigned_at IS NULL AND created_at BETWEEN '$start' AND '$end')
                  OR (id IN (
                      SELECT DISTINCT lead_id 
                      FROM lead_follow_ups 
                      WHERE created_by IN ($userIds) AND created_at BETWEEN '$start' AND '$end'
                  ))
              )
            GROUP BY assigned_id
        ";
        $qLeadRes = $this->db->query($qLeadQ);
        if ($qLeadRes) {
            while ($row = $qLeadRes->fetch_assoc()) {
                $uid = (int)$row['assigned_id'];
                if (isset($employees[$uid])) {
                    $employees[$uid]['quality_leads'] = (int)$row['cnt'];
                }
            }
        }

        // 5. Pre-calculate Maximums for Relative Scoring (When Target is 0)
        $maxUnitsSold = 0;
        $maxVisits = 0;
        $maxVolume = 0;
        foreach ($employees as $empTemp) {
            $vol = $empTemp['volume_assigned'] + $empTemp['volume_unassigned'];
            if ($vol > $maxVolume) $maxVolume = $vol;
            if ($empTemp['units_sold'] > $maxUnitsSold) $maxUnitsSold = $empTemp['units_sold'];
            if ($empTemp['visited'] > $maxVisits) $maxVisits = $empTemp['visited'];
        }

        // Calculate Percentages, Weighted Ranking Score, and Target Vs Achievement 8-Pillar Scorecard
        foreach ($employees as $uid => &$emp) {
            $emp['total_volume_achieved'] = $emp['volume_assigned'] + $emp['volume_unassigned'];
            
            // Normalize assigned leads so coverage cannot be less than unique leads touched
            if ($emp['assigned_leads'] < $emp['unique_leads_followed']) {
                $emp['assigned_leads'] = $emp['unique_leads_followed'];
            }

            $emp['total_not_contacted'] = max(0, $emp['assigned_leads'] - $emp['total_contacted']);

            $emp['follow_up_pct'] = $emp['assigned_leads'] > 0 
                ? min(100, round(($emp['unique_leads_followed'] / $emp['assigned_leads']) * 100, 2)) 
                : 0;

            $emp['contacted_pct'] = $emp['unique_leads_followed'] > 0 
                ? min(100, round(($emp['total_contacted'] / $emp['unique_leads_followed']) * 100, 2)) 
                : 0;

            $emp['interested_pct'] = $emp['total_contacted'] > 0 
                ? min(100, round(($emp['interested'] / $emp['total_contacted']) * 100, 2)) 
                : 0;

            // Backwards compatibility alias for older views
            $emp['total_follow_up'] = $emp['unique_leads_followed'];

            $hasTarget = ($emp['volume_target'] > 0 || $emp['unit_target'] > 0);

            if ($hasTarget) {
                $emp['unit_ach_pct'] = $emp['unit_target'] > 0 
                    ? round(($emp['units_sold'] / $emp['unit_target']) * 100, 2) 
                    : 0;

                $emp['visit_ach_pct'] = $emp['visit_target'] > 0 
                    ? round(($emp['visited'] / $emp['visit_target']) * 100, 2) 
                    : 0;

                $emp['volume_ach_pct'] = $emp['volume_target'] > 0 
                    ? round(($emp['total_volume_achieved'] / $emp['volume_target']) * 100, 2) 
                    : 0;

                // Weighted Analytical Performance Score:
                // 45% Volume Achievement + 20% Unit Achievement + 15% Site Visits + 10% Contacted Rate + 10% Lead Coverage
                $emp['performance_score'] = round(
                    ($emp['volume_ach_pct'] * 0.45) + 
                    ($emp['unit_ach_pct'] * 0.20) + 
                    ($emp['visit_ach_pct'] * 0.15) + 
                    ($emp['contacted_pct'] * 0.10) + 
                    ($emp['follow_up_pct'] * 0.10),
                    2
                );
            } else {
                // If NO target is set, score is purely based on relative performance compared to the top performer
                $emp['unit_ach_pct'] = ($maxUnitsSold > 0) ? round(($emp['units_sold'] / $maxUnitsSold) * 100, 2) : 0;
                $emp['visit_ach_pct'] = ($maxVisits > 0) ? round(($emp['visited'] / $maxVisits) * 100, 2) : 0;
                $emp['volume_ach_pct'] = ($maxVolume > 0) ? round(($emp['total_volume_achieved'] / $maxVolume) * 100, 2) : 0;
                
                // Adjusted Weights: Since target is missing, if no bookings exist yet, heavily weight their active visits, contacted leads, and follow-ups
                if ($maxVolume == 0 && $maxUnitsSold == 0) {
                    $emp['performance_score'] = round(
                        ($emp['visit_ach_pct'] * 0.40) + 
                        ($emp['contacted_pct'] * 0.30) + 
                        ($emp['follow_up_pct'] * 0.30),
                        2
                    );
                } else {
                    $emp['performance_score'] = round(
                        ($emp['volume_ach_pct'] * 0.45) + 
                        ($emp['unit_ach_pct'] * 0.35) + 
                        ($emp['visit_ach_pct'] * 0.10) + 
                        ($emp['contacted_pct'] * 0.05) + 
                        ($emp['follow_up_pct'] * 0.05),
                        2
                    );
                }
            }

            // -------------------------------------------------------------
            // Target Vs Achievement 8-Pillar 100% Scorecard Calculation
            // -------------------------------------------------------------
            // 1. Target Volume / Land Share Revenue Generation (30%)
            $volTarget = (float)$emp['volume_target'];
            $volAch = (float)$emp['total_volume_achieved'];
            $pillar1_score = $volTarget > 0 
                ? min(30.00, round(($volAch / $volTarget) * 30.00, 2)) 
                : ($volAch > 0 ? 30.00 : 0.00);

            // 2. Target vs Achievement Value Realization (20%)
            $min20Met = $emp['volume_ach_pct'] >= 20.00;
            $pillar2_score = $volTarget > 0 
                ? min(20.00, round(($volAch / $volTarget) * 20.00, 2)) 
                : ($volAch > 0 ? 20.00 : 0.00);

            // 3. No. of Unit Sold (10%)
            $unitTarget = (int)$emp['unit_target'];
            $unitsSold = (int)$emp['units_sold'];
            $pillar3_score = $unitTarget > 0 
                ? min(10.00, round(($unitsSold / $unitTarget) * 10.00, 2)) 
                : ($unitsSold > 0 ? 10.00 : 0.00);

            // 4. Highest Visit / Site Visits (10%)
            $visitTarget = (int)$emp['visit_target'];
            $visitsDone = (int)$emp['visited'];
            $pillar4_score = $visitTarget > 0 
                ? min(10.00, round(($visitsDone / $visitTarget) * 10.00, 2)) 
                : ($visitsDone > 0 ? 10.00 : 0.00);

            // 5. Sales CVR Conversion Rate (Min 1%) (10%)
            $assignedLeads = (int)$emp['assigned_leads'];
            $cvrPct = $assignedLeads > 0 ? round(($unitsSold / $assignedLeads) * 100, 2) : 0.00;
            $cvrMin1Met = $cvrPct >= 1.00;
            if ($cvrPct >= 1.00) {
                // Baseline 5.00 for reaching 1.0% min threshold + scaled up to 10.00 for 2.0% CVR
                $pillar5_score = min(10.00, round(5.00 + (($cvrPct - 1.00) / 1.00) * 5.00, 2));
            } elseif ($cvrPct > 0.00) {
                $pillar5_score = round(($cvrPct / 1.00) * 5.00, 2);
            } else {
                $pillar5_score = 0.00;
            }

            // 6. Leads Connected and Follow-up (10%)
            $contacted = (int)$emp['total_contacted'];
            $followed = (int)$emp['unique_leads_followed'];
            $contactedRatioPct = $assignedLeads > 0 ? round(($contacted / $assignedLeads) * 100, 2) : 0.00;
            $followedRatioPct = $assignedLeads > 0 ? round(($followed / $assignedLeads) * 100, 2) : 0.00;
            $pillar6_score = $assignedLeads > 0 
                ? min(10.00, round((($contacted / $assignedLeads) * 5.00) + (($followed / $assignedLeads) * 5.00), 2)) 
                : 0.00;

            // 7. Data Quality & Timeliness (Hot / High Prospect Leads) (5%)
            $qualityLeads = (int)($emp['quality_leads'] ?? 0);
            $qualityPct = $assignedLeads > 0 ? round(($qualityLeads / $assignedLeads) * 100, 2) : 0.00;
            // 10% benchmark of leads being hot/high prospect yields full 5.00 points
            $pillar7_score = $assignedLeads > 0 
                ? min(5.00, round(($qualityLeads / max(1, $assignedLeads * 0.10)) * 5.00, 2)) 
                : 0.00;

            // 8. HR Compliance & Discipline (5%)
            $hasActivity = ($assignedLeads > 0 || $contacted > 0 || $visitsDone > 0 || $unitsSold > 0);
            $pillar8_score = $hasActivity ? 5.00 : 0.00;

            $totalScorecard = round($pillar1_score + $pillar2_score + $pillar3_score + $pillar4_score + $pillar5_score + $pillar6_score + $pillar7_score + $pillar8_score, 2);

            // Performance Tier Grade
            if ($totalScorecard >= 85.00) {
                $grade = 'Platinum';
                $gradeClass = 'success';
            } elseif ($totalScorecard >= 70.00) {
                $grade = 'Gold';
                $gradeClass = 'info';
            } elseif ($totalScorecard >= 50.00) {
                $grade = 'Silver';
                $gradeClass = 'primary';
            } elseif ($totalScorecard >= 20.00) {
                $grade = 'Bronze';
                $gradeClass = 'warning';
            } else {
                $grade = 'Needs Improvement';
                $gradeClass = 'danger';
            }

            $emp['scorecard'] = [
                'pillar1_score' => $pillar1_score,
                'pillar1_max'   => 30.00,
                'pillar2_score' => $pillar2_score,
                'pillar2_max'   => 20.00,
                'min_20_met'    => $min20Met,
                'pillar3_score' => $pillar3_score,
                'pillar3_max'   => 10.00,
                'pillar4_score' => $pillar4_score,
                'pillar4_max'   => 10.00,
                'pillar5_score' => $pillar5_score,
                'pillar5_max'   => 10.00,
                'cvr_pct'       => $cvrPct,
                'cvr_min_1_met' => $cvrMin1Met,
                'pillar6_score' => $pillar6_score,
                'pillar6_max'   => 10.00,
                'contacted_ratio_pct' => $contactedRatioPct,
                'followed_ratio_pct'  => $followedRatioPct,
                'pillar7_score' => $pillar7_score,
                'pillar7_max'   => 5.00,
                'quality_leads' => $qualityLeads,
                'quality_pct'   => $qualityPct,
                'pillar8_score' => $pillar8_score,
                'pillar8_max'   => 5.00,
                'total_score'   => $totalScorecard,
                'grade'         => $grade,
                'grade_class'   => $gradeClass
            ];
        }
        unset($emp);

        // Sort for Individual Ranking
        uasort($employees, function($a, $b) {
            // 1. Sort by Units Sold
            if ($a['units_sold'] != $b['units_sold']) {
                return $b['units_sold'] <=> $a['units_sold'];
            }
            // 2. Sort by total_follow_up_calls
            if ($a['total_follow_up_calls'] != $b['total_follow_up_calls']) {
                return $b['total_follow_up_calls'] <=> $a['total_follow_up_calls'];
            }
            // 3. Sort by Visits Done
            if ($a['visited'] != $b['visited']) {
                return $b['visited'] <=> $a['visited'];
            }
            // 4. Sort by Achieved Volume
            if (abs($a['total_volume_achieved'] - $b['total_volume_achieved']) > 0.01) {
                return $b['total_volume_achieved'] <=> $a['total_volume_achieved'];
            }
            // 5. Performance Score
            if (abs($a['performance_score'] - $b['performance_score']) > 0.01) {
                return $b['performance_score'] <=> $a['performance_score'];
            }
            // 6. Assigned leads
            if ($a['assigned_leads'] != $b['assigned_leads']) {
                return $b['assigned_leads'] <=> $a['assigned_leads'];
            }
            return strcmp($a['member_name'], $b['member_name']);
        });

        $rank = 1;
        foreach ($employees as &$emp) {
            $emp['rank'] = $rank++;
        }
        unset($emp);

        // Create Scorecard-ranked list (sorted by 8-Pillar Total Score)
        $scorecardIndividuals = $employees;
        uasort($scorecardIndividuals, function($a, $b) {
            return $b['scorecard']['total_score'] <=> $a['scorecard']['total_score'];
        });
        $scRank = 1;
        foreach ($scorecardIndividuals as &$scEmp) {
            $scEmp['scorecard']['rank'] = $scRank++;
        }
        unset($scEmp);

        // Aggregate Team Summaries
        $teams = [];
        foreach ($employees as $emp) {
            $tid = $emp['team_id'] ?: 0;
            if (!isset($teams[$tid])) {
                $teams[$tid] = [
                    'team_id' => $tid,
                    'team_name' => $emp['team_name'] ?: ('Team ' . ($tid ?: 'Unassigned')),
                    'branch_name' => $emp['branch_name'] ?? 'Other',
                    'member_count' => 0,
                    'members' => [],
                    'total_leads' => 0,
                    'total_follow_up_calls' => 0,
                    'unique_leads_followed' => 0,
                    'total_contacted' => 0,
                    'total_not_contacted' => 0,
                    'visit_target' => 0,
                    'visited' => 0,
                    'will_visit' => 0,
                    'unit_target' => 0,
                    'units_sold' => 0,
                    'interested' => 0,
                    'not_interested' => 0,
                    'volume_target' => 0,
                    'volume_assigned' => 0,
                    'volume_unassigned' => 0,
                    'total_volume_achieved' => 0,
                    'total_collected' => 0.0,
                    'verified_collection' => 0.0,
                    'pending_collection' => 0.0,
                    'collection_details' => [],
                    'performance_score' => 0,
                    'total_collected' => 0.0,
                    'verified_collection' => 0.0,
                    'pending_collection' => 0.0,
                    'collection_details' => [],
                    'sold_units_details' => []
                ];
            }

            $teams[$tid]['member_count']++;
            $teams[$tid]['members'][] = $emp;
            if (!empty($emp['sold_units_details'])) {
                foreach ($emp['sold_units_details'] as $unit) {
                    $teams[$tid]['sold_units_details'][] = array_merge($unit, [
                        'member_name' => $emp['member_name'],
                        'level_designation' => $emp['level_designation']
                    ]);
                }
            }
            $teams[$tid]['total_leads'] += $emp['assigned_leads'];
            $teams[$tid]['total_follow_up_calls'] += $emp['total_follow_up_calls'];
            $teams[$tid]['unique_leads_followed'] += $emp['unique_leads_followed'];
            $teams[$tid]['total_contacted'] += $emp['total_contacted'];
            $teams[$tid]['visit_target'] += $emp['visit_target'];
            $teams[$tid]['visited'] += $emp['visited'];
            $teams[$tid]['will_visit'] += $emp['will_visit'];
            $teams[$tid]['unit_target'] += $emp['unit_target'];
            $teams[$tid]['units_sold'] += $emp['units_sold'];
            $teams[$tid]['interested'] += $emp['interested'];
            $teams[$tid]['not_interested'] += $emp['not_interested'];
            $teams[$tid]['volume_target'] += $emp['volume_target'];
            $teams[$tid]['volume_assigned'] += $emp['volume_assigned'];
            $teams[$tid]['volume_unassigned'] += $emp['volume_unassigned'];
            $teams[$tid]['total_volume_achieved'] += $emp['total_volume_achieved'];
            $teams[$tid]['total_collected'] += $emp['total_collected'];
            $teams[$tid]['verified_collection'] += $emp['verified_collection'];
            $teams[$tid]['pending_collection'] += $emp['pending_collection'];
            if (!empty($emp['collection_details'])) {
                foreach ($emp['collection_details'] as $cItem) {
                    $teams[$tid]['collection_details'][] = array_merge($cItem, [
                        'member_name' => $emp['member_name'],
                        'level_designation' => $emp['level_designation']
                    ]);
                }
            }
        }

        foreach ($teams as &$t) {
            $t['total_not_contacted'] = max(0, $t['total_leads'] - $t['total_contacted']);
            
            $t['follow_up_pct'] = $t['total_leads'] > 0 
                ? min(100, round(($t['unique_leads_followed'] / $t['total_leads']) * 100, 2)) 
                : 0;

            $t['contacted_pct'] = $t['unique_leads_followed'] > 0 
                ? min(100, round(($t['total_contacted'] / $t['unique_leads_followed']) * 100, 2)) 
                : 0;

            $t['interested_pct'] = $t['total_contacted'] > 0 
                ? min(100, round(($t['interested'] / $t['total_contacted']) * 100, 2)) 
                : 0;

            $t['visit_ach_pct'] = $t['visit_target'] > 0 
                ? round(($t['visited'] / $t['visit_target']) * 100, 2) 
                : ($t['visited'] > 0 ? 100 : 0);

            $t['unit_ach_pct'] = $t['unit_target'] > 0 
                ? round(($t['units_sold'] / $t['unit_target']) * 100, 2) 
                : ($t['units_sold'] > 0 ? 100 : 0);

            $t['volume_ach_pct'] = $t['volume_target'] > 0 
                ? round(($t['total_volume_achieved'] / $t['volume_target']) * 100, 2) 
                : ($t['total_volume_achieved'] > 0 ? 100 : 0);

            // Backward compatibility alias
            $t['total_follow_up'] = $t['unique_leads_followed'];

            // Weighted analytical performance score for teams
            $t['performance_score'] = round(
                ($t['volume_ach_pct'] * 0.45) + 
                ($t['unit_ach_pct'] * 0.20) + 
                ($t['visit_ach_pct'] * 0.15) + 
                ($t['contacted_pct'] * 0.10) + 
                ($t['follow_up_pct'] * 0.10),
                2
            );
        }
        unset($t);

        // Sort Teams for Ranking
        uasort($teams, function($a, $b) {
            if ($a['units_sold'] != $b['units_sold']) {
                return $b['units_sold'] <=> $a['units_sold'];
            }
            if ($a['total_follow_up_calls'] != $b['total_follow_up_calls']) {
                return $b['total_follow_up_calls'] <=> $a['total_follow_up_calls'];
            }
            if ($a['visited'] != $b['visited']) {
                return $b['visited'] <=> $a['visited'];
            }
            if (abs($a['total_volume_achieved'] - $b['total_volume_achieved']) > 0.01) {
                return $b['total_volume_achieved'] <=> $a['total_volume_achieved'];
            }
            if (abs($a['performance_score'] - $b['performance_score']) > 0.01) {
                return $b['performance_score'] <=> $a['performance_score'];
            }
            return strcmp($a['team_name'], $b['team_name']);
        });

        $trank = 1;
        foreach ($teams as &$t) {
            $t['rank'] = $trank++;
        }
        unset($t);

        // Overall KPIs Summary
        $kpis = [
            'total_teams' => count($teams),
            'total_members' => count($employees),
            'total_leads' => array_sum(array_column($teams, 'total_leads')),
            'total_follow_up_calls' => array_sum(array_column($teams, 'total_follow_up_calls')),
            'total_contacted' => array_sum(array_column($teams, 'total_contacted')),
            'total_volume_achieved' => array_sum(array_column($teams, 'total_volume_achieved')),
            'total_volume_target' => array_sum(array_column($teams, 'volume_target')),
            'total_units_sold' => array_sum(array_column($teams, 'units_sold')),
            'total_units_target' => array_sum(array_column($teams, 'unit_target')),
            'total_visited' => array_sum(array_column($teams, 'visited')),
            'total_collected' => array_sum(array_column($teams, 'total_collected')),
            'total_verified_collection' => array_sum(array_column($teams, 'verified_collection')),
            'total_pending_collection' => array_sum(array_column($teams, 'pending_collection')),
            'total_visit_target' => array_sum(array_column($teams, 'visit_target')),
            'top_team' => !empty($teams) ? reset($teams) : null
        ];

        // Scorecard KPIs Summary
        $scVolTarget = array_sum(array_column($scorecardIndividuals, 'volume_target'));
        $scVolAch = array_sum(array_column($scorecardIndividuals, 'total_volume_achieved'));
        $scUnitTarget = array_sum(array_column($scorecardIndividuals, 'unit_target'));
        $scUnitsSold = array_sum(array_column($scorecardIndividuals, 'units_sold'));
        $scVisitTarget = array_sum(array_column($scorecardIndividuals, 'visit_target'));
        $scVisited = array_sum(array_column($scorecardIndividuals, 'visited'));
        $scAssignedLeads = array_sum(array_column($scorecardIndividuals, 'assigned_leads'));
        $scQualityLeads = array_sum(array_column($scorecardIndividuals, 'quality_leads'));
        $scScores = array_map(function($e) { return $e['scorecard']['total_score'] ?? 0; }, $scorecardIndividuals);
        $avgScore = !empty($scScores) ? round(array_sum($scScores) / count($scScores), 2) : 0;
        $topScorer = !empty($scorecardIndividuals) ? reset($scorecardIndividuals) : null;

        $scorecardKpis = [
            'total_volume_target' => $scVolTarget,
            'total_volume_achieved' => $scVolAch,
            'volume_ach_pct' => $scVolTarget > 0 ? round(($scVolAch / $scVolTarget) * 100, 2) : 0,
            'total_unit_target' => $scUnitTarget,
            'total_units_sold' => $scUnitsSold,
            'unit_ach_pct' => $scUnitTarget > 0 ? round(($scUnitsSold / $scUnitTarget) * 100, 2) : 0,
            'total_visit_target' => $scVisitTarget,
            'total_visited' => $scVisited,
            'visit_ach_pct' => $scVisitTarget > 0 ? round(($scVisited / $scVisitTarget) * 100, 2) : 0,
            'total_assigned_leads' => $scAssignedLeads,
            'avg_cvr_pct' => $scAssignedLeads > 0 ? round(($scUnitsSold / $scAssignedLeads) * 100, 2) : 0,
            'total_quality_leads' => $scQualityLeads,
            'avg_quality_pct' => $scAssignedLeads > 0 ? round(($scQualityLeads / $scAssignedLeads) * 100, 2) : 0,
            'avg_score' => $avgScore,
            'top_scorer' => $topScorer
        ];

        return [
            'period' => [
                'start' => date('d M, Y', strtotime($start)),
                'end'   => date('d M, Y', strtotime($end)),
                'is_custom' => $dateRange['is_custom']
            ],
            'individuals' => $employees,
            'scorecard_individuals' => array_values($scorecardIndividuals),
            'teams' => $teams,
            'kpis' => $kpis,
            'scorecard_kpis' => $scorecardKpis
        ];
    }

    /**
     * Dedicated Target Vs Achievement Report Service Method
     */
    public function getTargetVsAchievementReport($year, $month, $loggedInUser, $filterTeamId = null)
    {
        return $this->getTrackerReport($year, $month, $loggedInUser, null, null, $filterTeamId);
    }

    /**
     * Dedicated Team Performance Report Service Method
     */
    public function getTeamPerformanceReport($year, $month, $loggedInUser, $customStart = null, $customEnd = null, $filterTeamId = null)
    {
        return $this->getTrackerReport($year, $month, $loggedInUser, $customStart, $customEnd, $filterTeamId);
    }
    
    /**
     * Daily Report (Dynamic date aggregation)
     */
    public function getDailyReport($date, $loggedInUser, $filterTeamId = null) 
    {
        $ts = strtotime($date);
        $day = (int)date('d', $ts);
        $month = (int)date('m', $ts);
        $year = (int)date('Y', $ts);
        
        // Special historical transition rules for 2026:
        if ($year === 2026) {
            if ($month === 5) {
                // 1-31 May is May 2026
            } elseif ($month === 6) {
                // 1-30 June is June 2026
            } elseif ($month === 7 && $day <= 25) {
                // 1-25 July is July 2026
            } elseif ($month === 7 && $day >= 26) {
                $month = 8; // starts August 2026 cycle
            } elseif ($day >= 26) {
                $month++;
                if ($month > 12) {
                    $month = 1;
                    $year++;
                }
            }
        } elseif ($day >= 26) {
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }
        
        $scope = $this->getVisibilityScope($loggedInUser, $filterTeamId);
        
        $start = date('Y-m-d 00:00:00', strtotime($date));
        $end   = date('Y-m-d 23:59:59', strtotime($date));

        // Build the base condition for employees: only active sales department employees
        $empCondition = "e.department_id = 1 AND LOWER(e.employee_status) = 'active'";
        if ($scope['type'] === 'TEAM') {
            $empCondition .= " AND COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0), 0) = " . (int)$scope['team_id'];
        } elseif ($scope['type'] === 'TEAMS') {
            $tIds = !empty($scope['team_ids']) ? implode(',', array_map('intval', $scope['team_ids'])) : '0';
            $empCondition .= " AND COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0), 0) IN ($tIds)";
        } elseif ($scope['type'] === 'SELF') {
            $empCondition .= " AND e.user_id = " . (int)$scope['user_id'];
        }

        $monthString = sprintf('%04d-%02d-01', $year, $month);

        $query = "
            SELECT 
                e.user_id, e.full_name as member_name, 
                e.office_branch_id, COALESCE(ob.name, 'Other') as branch_name,
                COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0), 0) as team_id,
                COALESCE(t.name, 'General Team') as team_name,
                r.name as role_name,
                u.role_id,
                st.sales_role,
                CASE 
                    WHEN LOWER(COALESCE(r.name, '')) LIKE '%manager%' OR LOWER(COALESCE(r.name, '')) LIKE '%head%' OR u.role_id IN (5, 9) THEN
                        CASE 
                            WHEN st.sales_role IS NOT NULL AND st.sales_role != '' THEN CONCAT('Sales Team Leader (', st.sales_role, ')')
                            ELSE 'Sales Team Leader'
                        END
                    ELSE
                        CASE 
                            WHEN st.sales_role IS NOT NULL AND st.sales_role != '' THEN CONCAT('Sales Executive (', st.sales_role, ')')
                            ELSE 'Sales Executive'
                        END
                END as level_designation,
                COALESCE(st.booking_target, 0) as unit_target,
                COALESCE(st.booking_value_target, 0) as volume_target,
                COALESCE(st.site_visit_target, 0) as visit_target,
                st.project_ids as assigned_project_ids,
                st.category_id,
                st.category_ids
            FROM employees e
            INNER JOIN users u ON e.user_id = u.id
            LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
            LEFT JOIN teams t ON COALESCE(NULLIF(e.team_id, 0), NULLIF(u.team_id, 0)) = t.id
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN sales_targets st ON e.user_id = st.sales_user_id AND st.month = '$monthString'
            WHERE $empCondition
        ";
        
        try {
            $employeesResult = $this->db->query($query);
        } catch (Exception $e) {
            $employeesResult = false;
        }
        $employees = [];
        $categoryProjectsCache = [];
        
        if (!$employeesResult) {
            $query = "
                SELECT 
                    e.user_id, e.full_name as member_name, 
                    e.office_branch_id, COALESCE(ob.name, 'Other') as branch_name,
                    e.team_id, 'Team ' as team_name,
                    r.name as role_name,
                    u.role_id,
                    st.sales_role,
                    CASE 
                        WHEN LOWER(COALESCE(r.name, '')) LIKE '%manager%' OR LOWER(COALESCE(r.name, '')) LIKE '%head%' OR u.role_id IN (5, 9) THEN
                            CASE 
                                WHEN st.sales_role IS NOT NULL AND st.sales_role != '' THEN CONCAT('Sales Team Leader (', st.sales_role, ')')
                                ELSE 'Sales Team Leader'
                            END
                        ELSE
                            CASE 
                                WHEN st.sales_role IS NOT NULL AND st.sales_role != '' THEN CONCAT('Sales Executive (', st.sales_role, ')')
                                ELSE 'Sales Executive'
                            END
                    END as level_designation,
                    COALESCE(st.booking_target, 0) as unit_target,
                    COALESCE(st.booking_value_target, 0) as volume_target,
                    COALESCE(st.site_visit_target, 0) as visit_target,
                    st.project_ids as assigned_project_ids,
                    st.category_id,
                    st.category_ids
                FROM employees e
                INNER JOIN users u ON e.user_id = u.id
                LEFT JOIN office_branches ob ON e.office_branch_id = ob.id
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN sales_targets st ON e.user_id = st.sales_user_id AND st.month = '$monthString'
                WHERE $empCondition
            ";
            try {
                $employeesResult = $this->db->query($query);
            } catch (Exception $e) {
                $employeesResult = false;
            }
        }

        if ($employeesResult) {
            while ($row = $employeesResult->fetch_assoc()) {
                if (empty($row['team_name']) && !empty($row['team_id'])) {
                    $row['team_name'] = 'Team ' . $row['team_id'];
                }

                $assignedProjects = $this->resolveAssignedProjectIds(
                    $row['assigned_project_ids'] ?? null,
                    $row['category_id'] ?? null,
                    $row['category_ids'] ?? null,
                    $categoryProjectsCache
                );

                // Format level designation consistently
                $isLeader = (
                    (!empty($row['role_name']) && (stripos($row['role_name'], 'manager') !== false || stripos($row['role_name'], 'head') !== false || stripos($row['role_name'], 'lead') !== false))
                    || (!empty($row['role_id']) && in_array((int)$row['role_id'], [5, 9]))
                );
                $baseRole = $isLeader ? 'Sales Team Leader' : 'Sales Executive';
                $targetRole = !empty($row['sales_role']) ? strtoupper(trim($row['sales_role'])) : '';
                $levelDesignation = !empty($targetRole) ? "{$baseRole} ({$targetRole})" : $baseRole;

                $employees[$row['user_id']] = [
                    'user_id' => (int)$row['user_id'],
                    'member_name' => $row['member_name'],
                    'branch_name' => $row['branch_name'] ?? 'Other',
                    'team_name' => $row['team_name'] ?: 'General Team',
                    'team_id' => !empty($row['team_id']) ? (int)$row['team_id'] : 0,
                    'level_designation' => $levelDesignation,
                    'unit_target' => (int)$row['unit_target'],
                    'volume_target' => (float)$row['volume_target'],
                    'visit_target' => (int)$row['visit_target'],
                    'assigned_project_ids' => $assignedProjects,
                    'assigned_leads' => 0,
                    'total_follow_up_calls' => 0,
                    'unique_leads_followed' => 0,
                    'total_follow_up' => 0,
                    'total_contacted' => 0,
                    'total_not_contacted' => 0,
                    'interested' => 0,
                    'not_interested' => 0,
                    'will_visit' => 0,
                    'visited' => 0,
                    'units_sold' => 0,
                    'volume_assigned' => 0,
                    'volume_unassigned' => 0,
                    'total_volume_achieved' => 0,
                    'performance_score' => 0,
                    'daily_follow_ups' => [],
                    'daily_visits' => [],
                    'sold_units_details' => []
                ];
            }
        }

        if (empty($employees)) return [];
        $userIdsArr = array_filter(array_keys($employees));
        if (empty($userIdsArr)) return [];
        $userIds = implode(',', $userIdsArr);

        // Daily Assigned Leads
        $leadsQ = "
            SELECT assigned_id, COUNT(DISTINCT id) as cnt 
            FROM leads 
            WHERE assigned_id IN ($userIds)
              AND (
                  (assigned_at IS NOT NULL AND assigned_at BETWEEN '$start' AND '$end')
                  OR (assigned_at IS NULL AND created_at BETWEEN '$start' AND '$end')
                  OR (id IN (
                      SELECT DISTINCT lead_id 
                      FROM lead_follow_ups 
                      WHERE created_by IN ($userIds) AND created_at BETWEEN '$start' AND '$end'
                  ))
              )
            GROUP BY assigned_id
        ";
        $leadsRes = $this->db->query($leadsQ);
        if ($leadsRes) {
            while ($row = $leadsRes->fetch_assoc()) {
                if (isset($employees[$row['assigned_id']])) {
                    $employees[$row['assigned_id']]['assigned_leads'] = (int)$row['cnt'];
                }
            }
        }

        // Daily Follow up logic
        $fuQ = "
            SELECT fu.id, fu.lead_id, fu.created_by, fu.follow_up_type, fu.lead_status, fu.next_action, fu.next_follow_up_date,
                   fu.discussion_note, fu.created_at,
                   COALESCE(NULLIF(l.full_name, ''), NULLIF(l.lead_name, ''), 'N/A') as lead_name,
                   COALESCE(NULLIF(l.phone, ''), NULLIF(l.phone_number, '')) as lead_phone,
                   p.name as project_name
            FROM lead_follow_ups fu
            LEFT JOIN leads l ON fu.lead_id = l.id
            LEFT JOIN projects p ON fu.project_id = p.id
            WHERE fu.created_at BETWEEN '$start' AND '$end' AND fu.created_by IN ($userIds)
            ORDER BY fu.created_at ASC
        ";
        $fuRes = $this->db->query($fuQ);
        
        $leadLatestState = [];
        if ($fuRes) {
            while ($row = $fuRes->fetch_assoc()) {
                $uid = (int)$row['created_by'];
                $lid = (int)$row['lead_id'];

                if (!isset($employees[$uid])) continue;

                $employees[$uid]['total_follow_up_calls']++;

                if (!isset($leadLatestState[$uid])) $leadLatestState[$uid] = [];
                if (!isset($leadLatestState[$uid][$lid])) {
                    $leadLatestState[$uid][$lid] = [
                        'contacted' => false, 
                        'latest_status' => '', 
                        'is_visited' => false, 
                        'is_will_visit' => false
                    ];
                }
                
                $invalidTypes = ['Number Off', 'Wrong Number', 'Invalid Number'];
                $isContacted = !in_array($row['follow_up_type'], $invalidTypes);
                if ($isContacted) {
                    $leadLatestState[$uid][$lid]['contacted'] = true;
                }
                $leadLatestState[$uid][$lid]['latest_status'] = $row['lead_status'];
                
                $statusLower = strtolower(trim($row['lead_status'] ?? ''));
                $actionLower = strtolower(trim($row['next_action'] ?? ''));

                $isVisited = (in_array($statusLower, ['visited', 'visit completed']) || in_array($actionLower, ['visited', 'visit completed']));
                $isWillVisit = (in_array($actionLower, ['site visit', 'will visit', 'visit scheduled']) || in_array($statusLower, ['will visit', 'visit scheduled']));

                if ($isVisited) {
                    $leadLatestState[$uid][$lid]['is_visited'] = true;
                    $leadLatestState[$uid][$lid]['is_will_visit'] = false;
                } elseif ($isWillVisit) {
                    if (!$leadLatestState[$uid][$lid]['is_visited']) {
                        $leadLatestState[$uid][$lid]['is_will_visit'] = true;
                    }
                }

                // Activity breakdown detail for modal drilldown
                $employees[$uid]['daily_follow_ups'][] = [
                    'id' => (int)$row['id'],
                    'lead_id' => (int)$row['lead_id'],
                    'lead_name' => $row['lead_name'],
                    'lead_phone' => $row['lead_phone'] ?: '-',
                    'project_name' => $row['project_name'] ?: '-',
                    'follow_up_type' => $row['follow_up_type'],
                    'lead_status' => $row['lead_status'] ?: 'Pending',
                    'next_action' => $row['next_action'] ?: '-',
                    'next_follow_up_date' => $row['next_follow_up_date'] ?: '-',
                    'discussion_note' => $row['discussion_note'] ?: '-',
                    'is_contacted' => $isContacted,
                    'created_time' => date('h:i A', strtotime($row['created_at']))
                ];

                if ($isVisited || $isWillVisit) {
                    $employees[$uid]['daily_visits'][] = [
                        'lead_name' => $row['lead_name'],
                        'lead_phone' => $row['lead_phone'] ?: '-',
                        'project_name' => $row['project_name'] ?: 'Site Visit',
                        'visit_status' => $isVisited ? 'Visited (Completed)' : 'Will Visit (Scheduled)',
                        'visit_badge' => $isVisited ? 'bg-success' : 'bg-warning',
                        'next_follow_up_date' => $row['next_follow_up_date'] ?: '-',
                        'notes' => $row['discussion_note'] ?: '-'
                    ];
                }
            }
        }

        $interestedStatuses = ['potential', 'high potential', 'qualification in progress', 'prospect', 'high prospect', 'future', 'cold', 'hot', 'warm', 'visit scheduled', 'visit completed', 'will visit', 'visited'];
        $notInterestedStatuses = ['not interested', "don't call again", 'invalid lead', 'irrelevant', 'refund'];

        foreach ($leadLatestState as $uid => $leadsData) {
            if (!isset($employees[$uid])) continue;

            foreach ($leadsData as $lid => $data) {
                $employees[$uid]['unique_leads_followed']++;
                if ($data['contacted']) $employees[$uid]['total_contacted']++;

                $curStatus = strtolower(trim($data['latest_status']));
                if (in_array($curStatus, $interestedStatuses)) $employees[$uid]['interested']++;
                elseif (in_array($curStatus, $notInterestedStatuses)) $employees[$uid]['not_interested']++;
                
                if ($data['is_visited']) $employees[$uid]['visited']++;
                elseif ($data['is_will_visit']) $employees[$uid]['will_visit']++;
            }
        }

        // Daily Bookings
        $bkQ = "
            SELECT b.id as booking_id, b.sales_by_id, b.project_id, b.flat_id, b.agreement_value, b.booking_date, b.booking_status,
                   p.name as project_name, f.flat_name, f.floor_no, f.sqft,
                   COALESCE(NULLIF(f.land_share_value, 0), b.agreement_value, 0) as land_share_val,
                   COALESCE(NULLIF(l.full_name, ''), NULLIF(l.lead_name, ''), 'N/A') as lead_name,
                   COALESCE(NULLIF(l.phone, ''), NULLIF(l.phone_number, '')) as lead_phone
            FROM lead_bookings b
            LEFT JOIN flats f ON b.flat_id = f.id
            LEFT JOIN projects p ON b.project_id = p.id
            LEFT JOIN leads l ON b.lead_id = l.id
            WHERE b.booking_status IN ('booked', 'completed') 
              AND b.booking_date BETWEEN DATE('$start') AND DATE('$end')
              AND b.sales_by_id IN ($userIds)
        ";
        $bkRes = $this->db->query($bkQ);
        if ($bkRes) {
            while ($row = $bkRes->fetch_assoc()) {
                $uid = (int)$row['sales_by_id'];
                if (isset($employees[$uid])) {
                    $employees[$uid]['units_sold']++;
                    $assignedProjects = $employees[$uid]['assigned_project_ids'];
                    $isAssigned = (!empty($assignedProjects) && in_array($row['project_id'], $assignedProjects));
                    $effVal = $isAssigned ? (float)$row['agreement_value'] : (float)$row['land_share_val'];

                    if ($isAssigned) {
                        $employees[$uid]['volume_assigned'] += (float)$row['agreement_value'];
                    } else {
                        $employees[$uid]['volume_unassigned'] += (float)$row['land_share_val'];
                    }

                    $unitLabel = trim(($row['floor_no'] ? 'Floor ' . $row['floor_no'] . ', ' : '') . ($row['flat_name'] ?: 'Unit #' . $row['flat_id']));
                    if (empty($unitLabel)) {
                        $unitLabel = 'Unit #' . $row['flat_id'];
                    }

                    $employees[$uid]['sold_units_details'][] = [
                        'booking_id' => (int)$row['booking_id'],
                        'booking_date' => $row['booking_date'],
                        'booking_status' => ucfirst($row['booking_status']),
                        'project_name' => $row['project_name'] ?: 'Project #' . $row['project_id'],
                        'unit_label' => $unitLabel,
                        'sqft' => $row['sqft'] ?: '-',
                        'client_name' => $row['lead_name'],
                        'client_phone' => $row['lead_phone'] ?: '-',
                        'agreement_value' => (float)$row['agreement_value'],
                        'land_share_value' => (float)$row['land_share_val'],
                        'effective_volume' => $effVal,
                        'is_assigned' => $isAssigned
                    ];
                }
            }
        }

        // Daily Percentages and Score
        foreach ($employees as $uid => &$emp) {
            $emp['total_volume_achieved'] = $emp['volume_assigned'] + $emp['volume_unassigned'];
            $emp['total_follow_up'] = $emp['unique_leads_followed'];
            $emp['total_not_contacted'] = max(0, $emp['assigned_leads'] - $emp['total_contacted']);

            $emp['performance_score'] = round(
                ($emp['units_sold'] * 40) + 
                ($emp['visited'] * 20) + 
                ($emp['total_contacted'] * 2) + 
                ($emp['unique_leads_followed'] * 1), 
                2
            );
        }
        unset($emp);

        uasort($employees, function($a, $b) {
            if ($a['units_sold'] != $b['units_sold']) {
                return $b['units_sold'] <=> $a['units_sold'];
            }
            if ($a['total_follow_up_calls'] != $b['total_follow_up_calls']) {
                return $b['total_follow_up_calls'] <=> $a['total_follow_up_calls'];
            }
            if ($a['visited'] != $b['visited']) {
                return $b['visited'] <=> $a['visited'];
            }
            if (abs($a['performance_score'] - $b['performance_score']) > 0.01) {
                return $b['performance_score'] <=> $a['performance_score'];
            }
            return strcmp($a['member_name'], $b['member_name']);
        });

        $rank = 1;
        foreach ($employees as &$emp) { $emp['rank'] = $rank++; }
        unset($emp);

        return $employees;
    }
}
