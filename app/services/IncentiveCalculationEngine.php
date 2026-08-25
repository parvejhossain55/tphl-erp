<?php

class IncentiveCalculationEngine
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Calculates the incentive percentage for a specific user and month
     * Returns an array with details:
     * - target_amount
     * - assigned_achieved
     * - unassigned_achieved
     * - achievement_pct
     * - eligible_payout_pct
     * - assigned_slab_pct
     * - final_assigned_pct
     * - final_unassigned_pct
     */
    public function getExecutiveMonthlyStats($userId, $yearMonth)
    {
        // 1. Get Target for this month
        $stmt = $this->db->prepare("SELECT booking_value_target, category_id 
                                    FROM sales_targets 
                                    WHERE sales_user_id = ? AND DATE_FORMAT(month, '%Y-%m') = ? LIMIT 1");
        $stmt->bind_param("is", $userId, $yearMonth);
        $stmt->execute();
        $targetRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $targetAmount = $targetRow ? (float)$targetRow['booking_value_target'] : 0.00;
        $assignedCategoryId = $targetRow ? (int)$targetRow['category_id'] : 0;

        // 2. Get Bookings for this month
        // We only count bookings that are valid (not cancelled)
        $sql = "SELECT lb.id, p.category_id, IFNULL(f.land_share_value, 0) as land_share
                FROM lead_bookings lb
                JOIN projects p ON lb.project_id = p.id
                JOIN flats f ON lb.flat_id = f.id
                WHERE lb.sales_by_id = ? 
                AND DATE_FORMAT(lb.booking_date, '%Y-%m') = ?
                AND lb.deleted_at IS NULL
                AND lb.booking_status != 'cancelled'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $userId, $yearMonth);
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

        $eligiblePayoutPct = $ruleRow ? (float)$ruleRow['payout_pct'] : 0.00; // e.g. 20%, 50%, 100%

        // 5. Determine Slab Percentage
        // If eligiblePayoutPct > 0 (meaning they hit at least the first criteria), we combine assigned + unassigned
        $volumeForSlab = $assignedAchieved;
        if ($eligiblePayoutPct > 0) {
            $volumeForSlab += $unassignedAchieved;
        }

        $baseSlabPct = 0.00;
        if ($assignedCategoryId > 0) {
            $stmt = $this->db->prepare("SELECT percentage FROM incentive_slabs 
                                        WHERE category_id = ? 
                                        AND ? >= min_volume 
                                        AND (? <= max_volume OR max_volume IS NULL) LIMIT 1");
            $stmt->bind_param("idd", $assignedCategoryId, $volumeForSlab, $volumeForSlab);
            $stmt->execute();
            $slabRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $baseSlabPct = $slabRow ? (float)$slabRow['percentage'] : 0.00;
        }

        // 6. Final Percentages
        // They get a percentage of the base slab (e.g. 50% of 1%)
        $finalAssignedPct = $baseSlabPct * ($eligiblePayoutPct / 100.0);
        
        // Unassigned only yields 70% of the assigned percentage according to policy (as per original image rule)
        // Wait, the image says "Unassigned prokolep er khetry 70% proyojjo hobe". 
        // So unassigned gets 70% of finalAssignedPct, BUT ONLY if eligiblePayoutPct > 0.
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
     * Calculates the Team Leader / Manager / Sales Head incentive for a month
     */
    public function getTeamMonthlyStats($userId, $yearMonth)
    {
        // 1. Identify User Role and Team scope
        $empStmt = $this->db->prepare("
            SELECT e.team_id, e.is_sales_co_leader, u.role_id 
            FROM users u
            LEFT JOIN employees e ON u.id = e.user_id
            WHERE u.id = ?
        ");
        $empStmt->bind_param("i", $userId);
        $empStmt->execute();
        $emp = $empStmt->get_result()->fetch_assoc();
        $empStmt->close();

        $roleId = $emp ? (int)$emp['role_id'] : 0;
        $teamId = $emp ? (int)$emp['team_id'] : 0;
        $isCoordinator = $emp ? (int)$emp['is_sales_co_leader'] : 0;

        $roleType = 'sales_manager';
        if ($roleId == 9) $roleType = 'sales_head';
        if ($isCoordinator == 1 && $roleId != 9 && $roleId != 5) $roleType = 'coordinator';

        // 2. Calculate Total Team Collection for the month
        // Total collection is based on payment dates or booking dates? Usually it's based on payment dates in that month.
        // Let's sum verified payments in that yearMonth.
        $collectionSql = "
            SELECT SUM(lp.amount) as total_collection
            FROM lead_payments lp
            JOIN lead_bookings lb ON lp.booking_id = lb.id
            JOIN leads l ON lb.lead_id = l.id
            WHERE lp.verification_status = 'verified' 
              AND lp.payment_status IN ('paid', 'partially_paid')
              AND DATE_FORMAT(lp.payment_date, '%Y-%m') = ?
        ";

        if ($roleType != 'sales_head') {
            // Filter by team_id
            $collectionSql .= " AND (
                lb.sales_by_id IN (SELECT user_id FROM employees WHERE team_id = ?) 
                OR l.assigned_id IN (SELECT user_id FROM employees WHERE team_id = ?)
            )";
            $stmt = $this->db->prepare($collectionSql);
            $stmt->bind_param("sii", $yearMonth, $teamId, $teamId);
        } else {
            $stmt = $this->db->prepare($collectionSql);
            $stmt->bind_param("s", $yearMonth);
        }

        $stmt->execute();
        $colRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $totalCollection = $colRow ? (float)$colRow['total_collection'] : 0.00;

        // 3. Get Hierarchy Commission rules
        $ruleStmt = $this->db->prepare("SELECT reward_amount, collection_threshold_amount FROM hierarchy_commissions WHERE role_type = ? LIMIT 1");
        $ruleStmt->bind_param("s", $roleType);
        $ruleStmt->execute();
        $rule = $ruleStmt->get_result()->fetch_assoc();
        $ruleStmt->close();

        $rewardAmount = 0.00;
        $thresholdAmount = 5000000;
        $incentiveEarned = 0.00;
        $slabsHit = 0;

        if ($rule) {
            $rewardAmount = (float)$rule['reward_amount'];
            $thresholdAmount = (float)$rule['collection_threshold_amount'];
            
            if ($thresholdAmount > 0) {
                $slabsHit = floor($totalCollection / $thresholdAmount);
                $incentiveEarned = $slabsHit * $rewardAmount;
            }
        }

        return [
            'role_type' => $roleType,
            'team_id' => $teamId,
            'total_collection' => $totalCollection,
            'threshold_amount' => $thresholdAmount,
            'reward_amount' => $rewardAmount,
            'slabs_hit' => $slabsHit,
            'incentive_earned' => $incentiveEarned,
            'month' => $yearMonth
        ];
    }
}
