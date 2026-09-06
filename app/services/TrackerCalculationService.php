<?php

class TrackerCalculationService
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Determine the exact date range for a given month/year (26th of previous month to 25th of current month)
     */
    public function getDateRange($year, $month)
    {
        $year = (int)$year;
        $month = (int)$month;
        
        $prevMonth = $month - 1;
        $prevYear = $year;
        
        if ($prevMonth == 0) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $startDate = sprintf('%04d-%02d-26 00:00:00', $prevYear, $prevMonth);
        $endDate   = sprintf('%04d-%02d-25 23:59:59', $year, $month);

        return ['start' => $startDate, 'end' => $endDate];
    }

    /**
     * Get the visibility scope for a given user
     */
    public function getVisibilityScope($user)
    {
        $roleId = (int)$user['role_id'];
        $teamId = !empty($user['team_id']) ? (int)$user['team_id'] : null;
        
        // Roles: Chief Officer (33), Admin (1, 3), Sales Head (9) - assuming these roles from previous context
        if (in_array($roleId, [1, 3, 33]) || (in_array($roleId, [9, 5, 2, 6]) && empty($teamId))) {
            return ['type' => 'ALL', 'team_id' => null];
        }

        if (!empty($teamId)) {
            return ['type' => 'TEAM', 'team_id' => $teamId];
        }

        return ['type' => 'SELF', 'user_id' => $user['id']];
    }

    /**
     * Get Tracker Data for the specific month/year
     */
    public function getTrackerReport($year, $month, $loggedInUser)
    {
        $dateRange = $this->getDateRange($year, $month);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $scope = $this->getVisibilityScope($loggedInUser);

        // Build the base condition for employees
        $empCondition = "e.employee_status = 'active' AND u.role_id IN (5, 8, 9)";
        if ($scope['type'] === 'TEAM') {
            $empCondition .= " AND e.team_id = " . $scope['team_id'];
        } elseif ($scope['type'] === 'SELF') {
            $empCondition .= " AND e.user_id = " . (int)$scope['user_id'];
        }

        $monthString = sprintf('%04d-%02d-01', $year, $month);

        $query = "
            SELECT 
                e.user_id,
                e.full_name as member_name,
                e.team_id,
                t.name as team_name,
                e.reporting_user_id,
                st.sales_role as level_designation,
                COALESCE(st.booking_target, 0) as unit_target,
                COALESCE(st.booking_value_target, 0) as volume_target,
                COALESCE(st.site_visit_target, 0) as visit_target,
                st.project_ids as assigned_project_ids
            FROM employees e
            INNER JOIN users u ON e.user_id = u.id
            LEFT JOIN (SELECT id, name FROM teams) t ON e.team_id = t.id
            LEFT JOIN sales_targets st ON e.user_id = st.sales_user_id AND st.month = '$monthString'
            WHERE $empCondition
        ";

        $employeesResult = $this->db->query($query);
        $employees = [];
        
        // If teams table doesn't exist, we might get an error. Let's handle it gracefully.
        if (!$employeesResult) {
            // Fallback without teams
            $query = "
                SELECT 
                    e.user_id,
                    e.full_name as member_name,
                    e.team_id,
                    'Team ' as team_name,
                    e.reporting_user_id,
                    st.sales_role as level_designation,
                    COALESCE(st.booking_target, 0) as unit_target,
                    COALESCE(st.booking_value_target, 0) as volume_target,
                    COALESCE(st.site_visit_target, 0) as visit_target,
                    st.project_ids as assigned_project_ids
                FROM employees e
                INNER JOIN users u ON e.user_id = u.id
                LEFT JOIN sales_targets st ON e.user_id = st.sales_user_id AND st.month = '$monthString'
                WHERE $empCondition
            ";
            $employeesResult = $this->db->query($query);
        }

        if ($employeesResult) {
            while ($row = $employeesResult->fetch_assoc()) {
                if(empty($row['team_name']) && !empty($row['team_id'])) $row['team_name'] = 'Team ' . $row['team_id'];
                $employees[$row['user_id']] = [
                    'member_name' => $row['member_name'],
                    'team_name' => $row['team_name'],
                    'team_id' => $row['team_id'],
                    'level_designation' => $row['level_designation'] ?: 'Sales Exec',
                    'unit_target' => (int)$row['unit_target'],
                    'volume_target' => (float)$row['volume_target'],
                    'visit_target' => (int)$row['visit_target'],
                    'assigned_project_ids' => $row['assigned_project_ids'] ? explode(',', $row['assigned_project_ids']) : [],
                    'assigned_leads' => 0,
                    'total_follow_up' => 0,
                    'total_contacted' => 0,
                    'interested' => 0,
                    'not_interested' => 0,
                    'will_visit' => 0,
                    'visited' => 0,
                    'units_sold' => 0,
                    'volume_assigned' => 0,
                    'volume_unassigned' => 0
                ];
            }
        }

        if (empty($employees)) {
            return [];
        }

        $userIdsArr = array_filter(array_keys($employees));
        if (empty($userIdsArr)) {
            return [];
        }
        $userIds = implode(',', $userIdsArr);

        // 1. Assigned Leads
        $leadsQ = "SELECT assigned_id, COUNT(*) as cnt FROM leads WHERE created_at BETWEEN '$start' AND '$end' AND assigned_id IN ($userIds) GROUP BY assigned_id";
        $leadsRes = $this->db->query($leadsQ);
        if ($leadsRes) {
            while($row = $leadsRes->fetch_assoc()) {
                if(isset($employees[$row['assigned_id']])) {
                    $employees[$row['assigned_id']]['assigned_leads'] = (int)$row['cnt'];
                }
            }
        }

        // 2. Total Follow Up & Contacted
        // Get the list of leads followed up in this period
        $fuQ = "SELECT lead_id, created_by, follow_up_type, lead_status, next_action, next_follow_up_date, created_at 
                FROM lead_follow_ups 
                WHERE created_at BETWEEN '$start' AND '$end' AND created_by IN ($userIds)
                ORDER BY created_at ASC"; // Order ASC so we can overwrite to get the LATEST status
        $fuRes = $this->db->query($fuQ);
        
        $leadLatestState = []; // user_id => [lead_id => state_data]

        if ($fuRes) {
            while($row = $fuRes->fetch_assoc()) {
                $uid = $row['created_by'];
                $lid = $row['lead_id'];
                if(!isset($leadLatestState[$uid])) $leadLatestState[$uid] = [];
                
                if(!isset($leadLatestState[$uid][$lid])) {
                    $leadLatestState[$uid][$lid] = [
                        'followed_up' => true,
                        'contacted' => false,
                        'latest_status' => '',
                        'is_visited' => false,
                        'is_will_visit' => false,
                        'will_visit_date' => null
                    ];
                }

                // If any follow up in this period was NOT number off/wrong/invalid, it's considered contacted
                $invalidTypes = ['Number Off', 'Wrong Number', 'Invalid Number'];
                if (!in_array($row['follow_up_type'], $invalidTypes)) {
                    $leadLatestState[$uid][$lid]['contacted'] = true;
                }

                // Overwrite with the latest status
                $leadLatestState[$uid][$lid]['latest_status'] = $row['lead_status'];
                
                // Track Visited vs Will Visit logic based on latest state
                if ($row['lead_status'] === 'Visited') {
                    $leadLatestState[$uid][$lid]['is_visited'] = true;
                    $leadLatestState[$uid][$lid]['is_will_visit'] = false;
                } elseif ($row['next_action'] === 'Will Visit' || $row['lead_status'] === 'Will Visit') {
                    if (!$leadLatestState[$uid][$lid]['is_visited']) { // Only if not already visited
                        $leadLatestState[$uid][$lid]['is_will_visit'] = true;
                        $leadLatestState[$uid][$lid]['will_visit_date'] = $row['next_follow_up_date'];
                    }
                }
            }
        }

        // Process aggregated latest states for Follow Ups, Contacted, Interested, Visits
        $interestedStatuses = ['Prospect', 'High Prospect', 'Future', 'Cold', 'Hot', 'Warm'];
        $notInterestedStatuses = ['Not Interested', 'Don\'t Call Again', 'Refund'];

        foreach ($leadLatestState as $uid => $leadsData) {
            foreach ($leadsData as $lid => $data) {
                $employees[$uid]['total_follow_up']++;
                if ($data['contacted']) $employees[$uid]['total_contacted']++;

                if (in_array($data['latest_status'], $interestedStatuses)) {
                    $employees[$uid]['interested']++;
                } elseif (in_array($data['latest_status'], $notInterestedStatuses)) {
                    $employees[$uid]['not_interested']++;
                }

                if ($data['is_visited']) {
                    $employees[$uid]['visited']++;
                } elseif ($data['is_will_visit']) {
                    // Check if the scheduled date is today (for daily report logic, or within range)
                    // For monthly aggregate, if it's will visit, we count it as scheduled
                    $employees[$uid]['will_visit']++;
                }
            }
        }

        // 3. Bookings and Volumes
        $bkQ = "
            SELECT b.sales_by_id, b.project_id, b.agreement_value, f.land_share_value 
            FROM lead_bookings b
            LEFT JOIN flats f ON b.flat_id = f.id
            WHERE b.booking_status IN ('booked', 'completed') 
            AND b.booking_date BETWEEN DATE('$start') AND DATE('$end')
            AND b.sales_by_id IN ($userIds)
        ";
        $bkRes = $this->db->query($bkQ);
        if ($bkRes) {
            while ($row = $bkRes->fetch_assoc()) {
                $uid = $row['sales_by_id'];
                if(isset($employees[$uid])) {
                    $employees[$uid]['units_sold']++;
                    
                    $assignedProjects = $employees[$uid]['assigned_project_ids'];
                    if (in_array($row['project_id'], $assignedProjects)) {
                        $employees[$uid]['volume_assigned'] += (float)$row['agreement_value'];
                    } else {
                        $employees[$uid]['volume_unassigned'] += (float)$row['land_share_value'];
                    }
                }
            }
        }

        // Calculate Percentages & Metrics
        foreach ($employees as $uid => &$emp) {
            $emp['total_volume_achieved'] = $emp['volume_assigned'] + $emp['volume_unassigned'];
            
            $emp['follow_up_pct'] = $emp['assigned_leads'] > 0 ? round(($emp['total_follow_up'] / $emp['assigned_leads']) * 100, 2) : 0;
            $emp['contacted_pct'] = $emp['total_follow_up'] > 0 ? round(($emp['total_contacted'] / $emp['total_follow_up']) * 100, 2) : 0;
            $emp['interested_pct'] = $emp['total_contacted'] > 0 ? round(($emp['interested'] / $emp['total_contacted']) * 100, 2) : 0;
            
            $emp['unit_ach_pct'] = $emp['unit_target'] > 0 ? round(($emp['units_sold'] / $emp['unit_target']) * 100, 2) : 0;
            $emp['visit_ach_pct'] = $emp['visit_target'] > 0 ? round(($emp['visited'] / $emp['visit_target']) * 100, 2) : 0;
            $emp['volume_ach_pct'] = $emp['volume_target'] > 0 ? round(($emp['total_volume_achieved'] / $emp['volume_target']) * 100, 2) : 0;

            // Simplified ranking score: Weighted sum of percentages
            $emp['performance_score'] = ($emp['follow_up_pct'] * 0.2) + ($emp['contacted_pct'] * 0.2) + ($emp['visit_ach_pct'] * 0.2) + ($emp['volume_ach_pct'] * 0.4);
        }
        unset($emp); // break reference

        // Sort for Individual Ranking
        uasort($employees, function($a, $b) {
            return $b['performance_score'] <=> $a['performance_score'];
        });

        $rank = 1;
        foreach ($employees as &$emp) {
            $emp['rank'] = $rank++;
        }
        unset($emp);

        // Aggregate Team Summaries
        $teams = [];
        foreach ($employees as $emp) {
            $tid = $emp['team_id'] ?: 0;
            if(!isset($teams[$tid])) {
                $teams[$tid] = [
                    'team_id' => $tid,
                    'team_name' => $emp['team_name'],
                    'total_leads' => 0,
                    'total_follow_up' => 0,
                    'total_contacted' => 0,
                    'total_not_contacted' => 0,
                    'visit_target' => 0,
                    'visited' => 0,
                    'units_sold' => 0,
                    'interested' => 0,
                    'not_interested' => 0,
                    'volume_target' => 0,
                    'total_volume_achieved' => 0,
                    'performance_score' => 0
                ];
            }
            $t = &$teams[$tid];
            $t['total_leads'] += $emp['assigned_leads'];
            $t['total_follow_up'] += $emp['total_follow_up'];
            $t['total_contacted'] += $emp['total_contacted'];
            $t['visit_target'] += $emp['visit_target'];
            $t['visited'] += $emp['visited'];
            $t['units_sold'] += $emp['units_sold'];
            $t['interested'] += $emp['interested'];
            $t['not_interested'] += $emp['not_interested'];
            $t['volume_target'] += $emp['volume_target'];
            $t['total_volume_achieved'] += $emp['total_volume_achieved'];
        }

        foreach ($teams as &$t) {
            $t['total_not_contacted'] = $t['total_follow_up'] - $t['total_contacted'];
            
            $t['follow_up_pct'] = $t['total_leads'] > 0 ? round(($t['total_follow_up'] / $t['total_leads']) * 100, 2) : 0;
            $t['contacted_pct'] = $t['total_follow_up'] > 0 ? round(($t['total_contacted'] / $t['total_follow_up']) * 100, 2) : 0;
            $t['visit_ach_pct'] = $t['visit_target'] > 0 ? round(($t['visited'] / $t['visit_target']) * 100, 2) : 0;
            $t['volume_ach_pct'] = $t['volume_target'] > 0 ? round(($t['total_volume_achieved'] / $t['volume_target']) * 100, 2) : 0;

            $t['performance_score'] = ($t['follow_up_pct'] * 0.2) + ($t['contacted_pct'] * 0.2) + ($t['visit_ach_pct'] * 0.2) + ($t['volume_ach_pct'] * 0.4);
        }
        unset($t);

        // Sort Teams for Ranking
        uasort($teams, function($a, $b) {
            return $b['performance_score'] <=> $a['performance_score'];
        });

        $trank = 1;
        foreach ($teams as &$t) {
            $t['rank'] = $trank++;
        }
        unset($t);

        return [
            'period' => [
                'start' => date('d M, Y', strtotime($start)),
                'end' => date('d M, Y', strtotime($end))
            ],
            'individuals' => $employees,
            'teams' => $teams
        ];
    }
    
    /**
     * Daily Report (Dynamic date aggregation)
     */
    public function getDailyReport($date, $loggedInUser) {
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        
        $scope = $this->getVisibilityScope($loggedInUser);
        
        $start = date('Y-m-d 00:00:00', strtotime($date));
        $end = date('Y-m-d 23:59:59', strtotime($date));

        // Use same base condition
        $empCondition = "e.employee_status = 'active' AND u.role_id IN (5, 8, 9)";
        if ($scope['type'] === 'TEAM') {
            $empCondition .= " AND e.team_id = " . $scope['team_id'];
        } elseif ($scope['type'] === 'SELF') {
            $empCondition .= " AND e.user_id = " . (int)$scope['user_id'];
        }

        $monthString = sprintf('%04d-%02d-01', $year, $month);

        $query = "
            SELECT 
                e.user_id, e.full_name as member_name, e.team_id,
                t.name as team_name,
                st.sales_role as level_designation,
                COALESCE(st.booking_target, 0) as unit_target,
                COALESCE(st.booking_value_target, 0) as volume_target,
                COALESCE(st.site_visit_target, 0) as visit_target,
                st.project_ids as assigned_project_ids
            FROM employees e
            INNER JOIN users u ON e.user_id = u.id
            LEFT JOIN (SELECT id, name FROM teams) t ON e.team_id = t.id
            LEFT JOIN sales_targets st ON e.user_id = st.sales_user_id AND st.month = '$monthString'
            WHERE $empCondition
        ";
        
        try {
            $employeesResult = $this->db->query($query);
        } catch (Exception $e) {
            $employeesResult = false;
        }
        $employees = [];
        
        if (!$employeesResult) {
            // Fallback without teams
            $query = "
                SELECT 
                    e.user_id, e.full_name as member_name, e.team_id,
                    'Team ' as team_name,
                    st.sales_role as level_designation,
                    COALESCE(st.booking_target, 0) as unit_target,
                    COALESCE(st.booking_value_target, 0) as volume_target,
                    COALESCE(st.site_visit_target, 0) as visit_target,
                    st.project_ids as assigned_project_ids
                FROM employees e
                INNER JOIN users u ON e.user_id = u.id
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
                if(empty($row['team_name']) && !empty($row['team_id'])) $row['team_name'] = 'Team ' . $row['team_id'];
                $employees[$row['user_id']] = [
                    'member_name' => $row['member_name'],
                    'team_name' => $row['team_name'],
                    'team_id' => $row['team_id'],
                    'level_designation' => $row['level_designation'] ?: 'Sales Exec',
                    'unit_target' => (int)$row['unit_target'],
                    'volume_target' => (float)$row['volume_target'],
                    'visit_target' => (int)$row['visit_target'],
                    'assigned_project_ids' => $row['assigned_project_ids'] ? explode(',', $row['assigned_project_ids']) : [],
                    'assigned_leads' => 0,
                    'total_follow_up' => 0,
                    'total_contacted' => 0,
                    'interested' => 0,
                    'not_interested' => 0,
                    'will_visit' => 0,
                    'visited' => 0,
                    'units_sold' => 0,
                    'volume_assigned' => 0,
                    'volume_unassigned' => 0
                ];
            }
        }

        if (empty($employees)) return [];
        $userIdsArr = array_filter(array_keys($employees));
        if (empty($userIdsArr)) {
            return [];
        }
        $userIds = implode(',', $userIdsArr);

        // 1. Assigned Leads Today
        $leadsQ = "SELECT assigned_id, COUNT(*) as cnt FROM leads WHERE created_at BETWEEN '$start' AND '$end' AND assigned_id IN ($userIds) GROUP BY assigned_id";
        $leadsRes = $this->db->query($leadsQ);
        if ($leadsRes) {
            while($row = $leadsRes->fetch_assoc()) {
                if(isset($employees[$row['assigned_id']])) $employees[$row['assigned_id']]['assigned_leads'] = (int)$row['cnt'];
            }
        }

        // Daily Follow up logic
        $fuQ = "SELECT lead_id, created_by, follow_up_type, lead_status, next_action, next_follow_up_date 
                FROM lead_follow_ups 
                WHERE created_at BETWEEN '$start' AND '$end' AND created_by IN ($userIds)
                ORDER BY created_at ASC";
        $fuRes = $this->db->query($fuQ);
        
        $leadLatestState = [];
        if ($fuRes) {
            while($row = $fuRes->fetch_assoc()) {
                $uid = $row['created_by'];
                $lid = $row['lead_id'];
                if(!isset($leadLatestState[$uid])) $leadLatestState[$uid] = [];
                if(!isset($leadLatestState[$uid][$lid])) {
                    $leadLatestState[$uid][$lid] = [
                        'contacted' => false, 'latest_status' => '', 'is_visited' => false, 'is_will_visit' => false
                    ];
                }
                
                $invalidTypes = ['Number Off', 'Wrong Number', 'Invalid Number'];
                if (!in_array($row['follow_up_type'], $invalidTypes)) {
                    $leadLatestState[$uid][$lid]['contacted'] = true;
                }
                $leadLatestState[$uid][$lid]['latest_status'] = $row['lead_status'];
                
                if ($row['lead_status'] === 'Visited') {
                    $leadLatestState[$uid][$lid]['is_visited'] = true;
                    $leadLatestState[$uid][$lid]['is_will_visit'] = false;
                } elseif ($row['next_action'] === 'Will Visit' || $row['lead_status'] === 'Will Visit') {
                    if (!$leadLatestState[$uid][$lid]['is_visited']) {
                        $leadLatestState[$uid][$lid]['is_will_visit'] = true;
                    }
                }
            }
        }

        $interestedStatuses = ['Prospect', 'High Prospect', 'Future', 'Cold', 'Hot', 'Warm'];
        $notInterestedStatuses = ['Not Interested', 'Don\'t Call Again', 'Refund'];

        foreach ($leadLatestState as $uid => $leadsData) {
            foreach ($leadsData as $lid => $data) {
                $employees[$uid]['total_follow_up']++;
                if ($data['contacted']) $employees[$uid]['total_contacted']++;
                if (in_array($data['latest_status'], $interestedStatuses)) $employees[$uid]['interested']++;
                elseif (in_array($data['latest_status'], $notInterestedStatuses)) $employees[$uid]['not_interested']++;
                
                if ($data['is_visited']) $employees[$uid]['visited']++;
                elseif ($data['is_will_visit']) $employees[$uid]['will_visit']++;
            }
        }

        // Daily Bookings
        $bkQ = "
            SELECT b.sales_by_id, b.project_id, b.agreement_value, f.land_share_value 
            FROM lead_bookings b
            LEFT JOIN flats f ON b.flat_id = f.id
            WHERE b.booking_status IN ('booked', 'completed') 
            AND b.booking_date BETWEEN DATE('$start') AND DATE('$end')
            AND b.sales_by_id IN ($userIds)
        ";
        $bkRes = $this->db->query($bkQ);
        if ($bkRes) {
            while ($row = $bkRes->fetch_assoc()) {
                $uid = $row['sales_by_id'];
                if(isset($employees[$uid])) {
                    $employees[$uid]['units_sold']++;
                    $assignedProjects = $employees[$uid]['assigned_project_ids'];
                    if (in_array($row['project_id'], $assignedProjects)) {
                        $employees[$uid]['volume_assigned'] += (float)$row['agreement_value'];
                    } else {
                        $employees[$uid]['volume_unassigned'] += (float)$row['land_share_value'];
                    }
                }
            }
        }

        // Percentages and Ranking for daily
        foreach ($employees as $uid => &$emp) {
            $emp['total_volume_achieved'] = $emp['volume_assigned'] + $emp['volume_unassigned'];
            $emp['performance_score'] = ($emp['total_follow_up'] * 0.3) + ($emp['total_contacted'] * 0.3) + ($emp['units_sold'] * 0.4);
        }
        unset($emp);

        uasort($employees, function($a, $b) {
            return $b['performance_score'] <=> $a['performance_score'];
        });

        $rank = 1;
        foreach ($employees as &$emp) { $emp['rank'] = $rank++; }
        unset($emp);

        return $employees;
    }
}
