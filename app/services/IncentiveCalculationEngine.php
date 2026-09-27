<?php

class IncentiveCalculationEngine extends Model
{
    public function __construct($db)
    {
        parent::__construct($db);
    }

    /**
     * Calculates the incentive percentage for a specific user and month (Tier 1 - Individual)
     */
    public function getExecutiveMonthlyStats($userId, $yearMonth, $fromDate = null, $toDate = null)
    {
        list($y, $m) = explode('-', $yearMonth);
        $cycle = $this->getCycleDateRange($y, $m);
        $start = $cycle['start'];
        $end = $cycle['end'];

        // 1. Get Target for this month
        $stmt = $this->db->prepare("SELECT booking_value_target, category_id, from_date, to_date 
                                    FROM sales_targets 
                                    WHERE sales_user_id = ? AND DATE_FORMAT(month, '%Y-%m') = ? LIMIT 1");
        $stmt->bind_param("is", $userId, $yearMonth);
        $stmt->execute();
        $targetRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Use custom target date range if configured
        if (!empty($targetRow['from_date']) && !empty($targetRow['to_date'])) {
            $start = $targetRow['from_date'] . ' 00:00:00';
            $end   = $targetRow['to_date'] . ' 23:59:59';
        }
        
        // Override with UI filters if provided
        if (!empty($fromDate) && !empty($toDate)) {
            $start = $fromDate . ' 00:00:00';
            $end   = $toDate . ' 23:59:59';
        }

        $targetAmount = $targetRow ? (float)$targetRow['booking_value_target'] : 0.00;
        $assignedCategoryId = $targetRow ? (int)$targetRow['category_id'] : 0;

        // 2. Get Bookings for this month
        $sql = "SELECT lb.id, p.category_id, IFNULL(f.land_share_value, 0) as land_share
                FROM lead_bookings lb
                JOIN projects p ON lb.project_id = p.id
                JOIN flats f ON lb.flat_id = f.id
                WHERE lb.sales_by_id = ? 
                AND lb.booking_date BETWEEN ? AND ?
                AND lb.deleted_at IS NULL
                AND lb.booking_status != 'cancelled'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $start, $end);
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $assignedAchieved = 0.00;
        $unassignedAchieved = 0.00;

        foreach ($bookings as $b) {
            if ($assignedCategoryId > 0 && $b['category_id'] == $assignedCategoryId) {
                $assignedAchieved += (float)$b['land_share'];
            } else {
                $unassignedAchieved += (float)$b['land_share'];
            }
        }

        // 3. Achievement %
        $achievementPct = ($targetAmount > 0) ? ($assignedAchieved / $targetAmount) * 100 : 0;

        // 4. Get Eligibility Rule
        $stmt = $this->db->prepare("SELECT payout_pct FROM incentive_eligibility_rules 
                                    WHERE rule_type = 'executive' 
                                    AND ? >= min_achievement_pct 
                                    AND (? <= max_achievement_pct OR max_achievement_pct IS NULL) LIMIT 1");
        $stmt->bind_param("dd", $achievementPct, $achievementPct);
        $stmt->execute();
        $ruleRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $eligiblePayoutPct = $ruleRow ? (float)$ruleRow['payout_pct'] : 0.00; 

        // 5. Determine Slab Percentage
        $volumeForSlab = $assignedAchieved;
        if ($eligiblePayoutPct > 0) {
            $volumeForSlab += $unassignedAchieved; // Unassigned is merged if eligible
        }

        $baseSlabPct = 0.00;
        if ($assignedCategoryId > 0) {
            $stmt = $this->db->prepare("SELECT percentage FROM incentive_slabs 
                                        WHERE category_id = ? 
                                        AND ? >= min_volume 
                                        AND (? <= max_volume OR max_volume IS NULL OR max_volume = 0) LIMIT 1");
            $stmt->bind_param("idd", $assignedCategoryId, $volumeForSlab, $volumeForSlab);
            $stmt->execute();
            $slabRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $baseSlabPct = $slabRow ? (float)$slabRow['percentage'] : 0.00;
        }

        // 6. Final Percentages
        $finalAssignedPct = $baseSlabPct * ($eligiblePayoutPct / 100.0);
        $finalUnassignedPct = 0.00;
        if ($eligiblePayoutPct > 0) {
            $finalUnassignedPct = $finalAssignedPct * 0.70;
        }

        return [
            'target_amount' => $targetAmount,
            'assigned_category_id' => $assignedCategoryId,
            'assigned_achieved' => $assignedAchieved,
            'unassigned_achieved' => $unassignedAchieved,
            'total_achieved_for_slab' => $volumeForSlab,
            'achievement_pct' => $achievementPct,
            'eligible_payout_pct' => $eligiblePayoutPct,
            'base_slab_pct' => $baseSlabPct,
            'final_assigned_pct' => $finalAssignedPct,
            'final_unassigned_pct' => $finalUnassignedPct
        ];
    }

    /**
     * Calculates the Team Leader / Manager / Sales Head incentive for a month (Tier 2 - Team)
     */
    public function getTeamMonthlyStats($userId, $yearMonth, $filterTeamId = null, $fromDate = null, $toDate = null)
    {
        // 1. Identify User Role and Team scope
        $empStmt = $this->db->prepare("
            SELECT u.role_id, e.team_id, e.is_sales_co_leader, t.name as team_name
            FROM users u
            LEFT JOIN employees e ON u.id = e.user_id
            LEFT JOIN teams t ON e.team_id = t.id
            WHERE u.id = ?
        ");
        $empStmt->bind_param("i", $userId);
        $empStmt->execute();
        $emp = $empStmt->get_result()->fetch_assoc();
        $empStmt->close();

        $roleId = $emp ? (int)$emp['role_id'] : 0;
        $teamId = $emp ? (int)$emp['team_id'] : 0;
        $isCoordinator = $emp ? (int)$emp['is_sales_co_leader'] : 0;

        // Default role is empty, if not manager/head/coord, they don't get team incentive
        $roleType = '';
        if ($roleId == 9) $roleType = 'sales_head';
        else if ($isCoordinator == 1) $roleType = 'coordinator';
        else if ($roleId == 5 || $roleId == 6 || $roleId == 7) $roleType = 'team_leader'; // Assuming 5,6,7 are manager roles

        if (!$roleType && in_array($roleId, [1, 3])) {
            $roleType = 'sales_head'; // Admin sees same as sales_head
        }

        if (!$roleType) {
            return ['eligible' => false, 'message' => 'Not a team leader role.'];
        }

        // If they are a team leader but have no team assigned, they cannot get team incentives!
        if ($roleType != 'sales_head' && $teamId == 0 && empty($filterTeamId)) {
            return ['eligible' => false, 'message' => 'No team assigned.'];
        }

        $targetTeamId = (!empty($filterTeamId) && (int)$filterTeamId > 0) ? (int)$filterTeamId : $teamId;
        $isFiltered = (!empty($filterTeamId) && (int)$filterTeamId > 0);

        // 2. Get the TEAM Target (Sum of individual targets in the team, or global if Sales Head without filter)
        $targetSql = "SELECT SUM(booking_value_target) as total_team_target 
                      FROM sales_targets st 
                      WHERE DATE_FORMAT(st.month, '%Y-%m') = ?";
        if ($roleType != 'sales_head' || $isFiltered) {
            $targetSql .= " AND st.sales_user_id IN (SELECT user_id FROM employees WHERE team_id = ?)";
            $stmt = $this->db->prepare($targetSql);
            $stmt->bind_param("si", $yearMonth, $targetTeamId);
        } else {
            $stmt = $this->db->prepare($targetSql);
            $stmt->bind_param("s", $yearMonth);
        }
        $stmt->execute();
        $targetRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $teamTarget = $targetRow ? (float)$targetRow['total_team_target'] : 0.00;

        list($y, $m) = explode('-', $yearMonth);
        $cycle = $this->getCycleDateRange($y, $m);
        $start = $cycle['start'];
        $end   = $cycle['end'];
        
        // Override with UI filters if provided
        if (!empty($fromDate) && !empty($toDate)) {
            $start = $fromDate . ' 00:00:00';
            $end   = $toDate . ' 23:59:59';
        }

        // 3. Get the TEAM Achievement (Sum of all land_share sales in the team/global within the cycle)
        $achieveSql = "SELECT SUM(IFNULL(f.land_share_value, 0)) as total_team_achieved
                       FROM lead_bookings lb
                       JOIN flats f ON lb.flat_id = f.id
                       WHERE lb.booking_date BETWEEN ? AND ?
                       AND lb.deleted_at IS NULL
                       AND lb.booking_status != 'cancelled'";
        if ($roleType != 'sales_head' || $isFiltered) {
            $achieveSql .= " AND lb.sales_by_id IN (SELECT user_id FROM employees WHERE team_id = ?)";
            $stmt = $this->db->prepare($achieveSql);
            $stmt->bind_param("ssi", $start, $end, $targetTeamId);
        } else {
            $stmt = $this->db->prepare($achieveSql);
            $stmt->bind_param("ss", $start, $end);
        }
        $stmt->execute();
        $achieveRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $teamAchieved = $achieveRow ? (float)$achieveRow['total_team_achieved'] : 0.00;

        $teamAchievementPct = ($teamTarget > 0) ? ($teamAchieved / $teamTarget) * 100 : 0;

        // 4. Get Eligibility Rule for this Role
        $stmt = $this->db->prepare("SELECT payout_pct FROM incentive_eligibility_rules 
                                    WHERE rule_type = ? 
                                    AND ? >= min_achievement_pct 
                                    AND (? <= max_achievement_pct OR max_achievement_pct IS NULL) LIMIT 1");
        $stmt->bind_param("sdd", $roleType, $teamAchievementPct, $teamAchievementPct);
        $stmt->execute();
        $ruleRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $eligiblePayoutPct = $ruleRow ? (float)$ruleRow['payout_pct'] : 0.00;

        // 5. Get Hierarchy Commission rules for reward amount
        $ruleStmt = $this->db->prepare("SELECT reward_amount, collection_threshold_amount FROM hierarchy_commissions WHERE role_type = ? LIMIT 1");
        // DB uses team_lead and co_leader
        $dbRoleType = $roleType;
        if ($roleType == 'team_leader') $dbRoleType = 'team_lead';
        if ($roleType == 'coordinator') $dbRoleType = 'co_leader';
        
        $ruleStmt->bind_param("s", $dbRoleType);
        $ruleStmt->execute();
        $hRule = $ruleStmt->get_result()->fetch_assoc();
        $ruleStmt->close();

        $rewardAmount = 0.00;
        $thresholdAmount = 5000000;
        $incentiveEarned = 0.00;
        $slabsHit = 0;

        if ($hRule) {
            $rewardAmount = (float)$hRule['reward_amount'];
            $thresholdAmount = (float)$hRule['collection_threshold_amount'];
            
            if ($thresholdAmount > 0 && $eligiblePayoutPct > 0) {
                $slabsHit = floor($teamAchieved / $thresholdAmount);
                // The payout is the reward amount * number of slabs * payout percentage
                $incentiveEarned = $slabsHit * $rewardAmount * ($eligiblePayoutPct / 100.0);
            }
        }

        // Determine Team Name display
        $displayTeamName = $emp ? $emp['team_name'] : 'Sales Team';
        if ($isFiltered) {
            $tQ = $this->db->query("SELECT name FROM teams WHERE id = " . $targetTeamId);
            if ($tQ && $tRow = $tQ->fetch_assoc()) {
                $displayTeamName = $tRow['name'];
            }
        } elseif ($roleType == 'sales_head') {
            $displayTeamName = 'Company-Wide (All Teams)';
        }

        return [
            'eligible' => true,
            'role_type' => $roleType,
            'team_id' => $targetTeamId,
            'team_name' => $displayTeamName,
            'is_filtered' => $isFiltered,
            'team_target' => $teamTarget,
            'team_achieved' => $teamAchieved,
            'team_achievement_pct' => $teamAchievementPct,
            'eligible_payout_pct' => $eligiblePayoutPct,
            'threshold_amount' => $thresholdAmount,
            'reward_amount' => $rewardAmount,
            'slabs_hit' => $slabsHit,
            'incentive_earned' => $incentiveEarned,
            'month' => $yearMonth
        ];
    }
}
