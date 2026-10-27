<?php

class IncentiveCalculationEngine extends Model
{
    protected static $settingsCache = null;
    protected static $claimsCache = null;

    public function __construct($db)
    {
        parent::__construct($db);
    }

    /**
     * Retrieve all active incentive settings from database with standard defaults
     */
    public function getSettings()
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        $defaults = [
            'min_eligible_sales' => 2500000.00, // 25 Lakh
            'flat_base_rate' => 0.70,           // 0.70%
            'floor_special_rate' => 0.80,       // 0.80% (2nd floor & top/below top floor)
            'slab_200_299_rate' => 1.00,        // 1.00% (200%-299% achievement)
            'slab_300_plus_rate' => 1.20,       // 1.20% (300%+ achievement)
            'speed_bonus_rate' => 0.20,         // +0.20% deadline bonus
            'speed_bonus_months' => 2,          // 2 months deadline
            'team_leader_rate' => 0.05,         // 0.05% on eligible members
            'unassigned_seller_share' => 70.0,  // 70% to seller (on hold)
            'unassigned_designated_share' => 30.0 // 30% to designated agent
        ];

        $res = $this->db->query("SELECT setting_key, setting_value FROM incentive_settings");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if (isset($defaults[$row['setting_key']])) {
                    $defaults[$row['setting_key']] = (float)$row['setting_value'];
                }
            }
        }

        self::$settingsCache = $defaults;
        return self::$settingsCache;
    }

    /**
     * Extract top floor number from building_type (e.g. B2+B1+G+14 => 14)
     */
    public function getTopFloorFromBuildingType($buildingType)
    {
        if (empty($buildingType)) {
            return 0;
        }
        if (preg_match('/(\d+)$/', trim($buildingType), $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }

    /**
     * Check if a flat is special floor: 1st or 2nd floor OR Top Floor / Below Top Floor
     */
    public function isFloorSpecial($floorNo, $buildingType)
    {
        $fn = (int)preg_replace('/[^0-9]/', '', (string)$floorNo);
        if ($fn === 1 || $fn === 2) {
            return true; // 1st & 2nd Floor
        }

        $topFloor = $this->getTopFloorFromBuildingType($buildingType);
        if ($topFloor > 0 && ($fn === $topFloor || $fn === ($topFloor - 1))) {
            return true; // Top floor or below top floor
        }
        return false;
    }

    /**
     * Check if a flat has already claimed or received an incentive previously (One-time incentive rule)
     */
    public function isFlatIncentiveClaimed($flatId, $excludeBookingId = null)
    {
        $info = $this->getFlatClaimDetails($flatId, $excludeBookingId);
        return !empty($info);
    }

    /**
     * Get detailed record of an existing claim for a flat if one exists
     */
    public function getFlatClaimDetails($flatId, $excludeBookingId = null)
    {
        if (empty($flatId)) return null;

        $flatId = (int)$flatId;

        if (self::$claimsCache === null) {
            self::$claimsCache = [];
            $res = $this->db->query("
                SELECT c.id, c.claim_status, c.booking_id, c.user_id, c.incentive_amount, c.created_at, 
                       c.flat_id, lb.flat_id AS booking_flat_id, u.full_name AS claimant_name
                FROM project_incentive_claims c
                LEFT JOIN lead_bookings lb ON c.booking_id = lb.id
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.claim_status NOT IN ('rejected')
            ");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $f1 = (int)($r['flat_id'] ?? 0);
                    $f2 = (int)($r['booking_flat_id'] ?? 0);
                    if ($f1 > 0 && !isset(self::$claimsCache[$f1])) {
                        self::$claimsCache[$f1] = $r;
                    }
                    if ($f2 > 0 && !isset(self::$claimsCache[$f2])) {
                        self::$claimsCache[$f2] = $r;
                    }
                }
            }
        }

        if (isset(self::$claimsCache[$flatId])) {
            $claim = self::$claimsCache[$flatId];
            if (!empty($excludeBookingId) && (int)$claim['booking_id'] === (int)$excludeBookingId) {
                return null;
            }
            return $claim;
        }

        return null;
    }

    /**
     * Find originally designated salesperson for a flat / project in this cycle
     */
    public function getDesignatedSalesperson($flatId, $projectId, $yearMonth, $excludeUserId = 0)
    {
        $flatIdStr = (string)$flatId;
        $projIdStr = (string)$projectId;

        $sql = "SELECT sales_user_id, booking_value_target, flat_ids, project_ids 
                FROM sales_targets 
                WHERE DATE_FORMAT(month, '%Y-%m') = ? 
                  AND sales_user_id != ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("si", $yearMonth, $excludeUserId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // 1. First priority: Specific flat_id match
        foreach ($rows as $r) {
            if (!empty($r['flat_ids'])) {
                $fIds = json_decode($r['flat_ids'], true) ?: [];
                if (in_array($flatIdStr, array_map('strval', $fIds), true)) {
                    return (int)$r['sales_user_id'];
                }
            }
        }

        // 2. Second priority: Project match
        foreach ($rows as $r) {
            if (!empty($r['project_ids'])) {
                $pIds = json_decode($r['project_ids'], true) ?: [];
                if (in_array('all', $pIds, true) || in_array($projIdStr, array_map('strval', $pIds), true)) {
                    return (int)$r['sales_user_id'];
                }
            }
        }

        return null;
    }

    /**
     * Calculates the incentive breakdown and stats for an individual executive
     * Follows the official October 2026 Sales Incentive Modality
     */
    public function getExecutiveMonthlyStats($userId, $yearMonth, $fromDate = null, $toDate = null)
    {
        $settings = $this->getSettings();
        $minEligibleSales = (float)$settings['min_eligible_sales']; // Default 25 Lakh

        // 1. Cycle Date Range (26th of previous month to 25th of current month)
        list($y, $m) = explode('-', $yearMonth);
        $cycle = $this->getCycleDateRange($y, $m);
        $start = $cycle['start'];
        $end   = $cycle['end'];

        // 2. Get Sales Targets for this month (aggregating if multiple targets exist in the same month/cycle)
        $stmt = $this->db->prepare("SELECT booking_value_target, category_id, category_ids, project_ids, flat_ids, from_date, to_date 
                                    FROM sales_targets 
                                    WHERE sales_user_id = ? AND DATE_FORMAT(month, '%Y-%m') = ?");
        $stmt->bind_param("is", $userId, $yearMonth);
        $stmt->execute();
        $targetRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $targetAmount = 0.00;
        $projIds = [];
        $flatIds = [];
        $hasCustomRange = false;
        $minFromDate = null;
        $maxToDate = null;

        if (!empty($targetRows)) {
            foreach ($targetRows as $tr) {
                $targetAmount += (float)$tr['booking_value_target'];

                if (!empty($tr['from_date']) && !empty($tr['to_date'])) {
                    $hasCustomRange = true;
                    if ($minFromDate === null || $tr['from_date'] < $minFromDate) {
                        $minFromDate = $tr['from_date'];
                    }
                    if ($maxToDate === null || $tr['to_date'] > $maxToDate) {
                        $maxToDate = $tr['to_date'];
                    }
                }

                if (!empty($tr['project_ids'])) {
                    $pArr = json_decode($tr['project_ids'], true) ?: [];
                    $projIds = array_merge($projIds, $pArr);
                }
                if (!empty($tr['flat_ids'])) {
                    $fArr = json_decode($tr['flat_ids'], true) ?: [];
                    $flatIds = array_merge($flatIds, $fArr);
                }
            }

            if ($hasCustomRange && $minFromDate && $maxToDate) {
                $start = $minFromDate . ' 00:00:00';
                $end   = $maxToDate . ' 23:59:59';
            }
        }

        // UI filter override if provided
        if (!empty($fromDate) && !empty($toDate)) {
            $start = $fromDate . ' 00:00:00';
            $end   = $toDate . ' 23:59:59';
        }

        $targetToDate = ($maxToDate) ? $maxToDate : substr($end, 0, 10);
        $projIds = array_values(array_unique(array_map('strval', $projIds)));
        $flatIds = array_values(array_unique(array_map('strval', $flatIds)));
        $hasAllProjects = in_array('all', $projIds, true);

        // 3. Fetch Bookings completed by this executive in the cycle
        $sql = "SELECT 
                    lb.id AS booking_id, 
                    lb.project_id, 
                    lb.flat_id, 
                    lb.booking_date,
                    lb.created_at AS booking_created_at,
                    l.created_at AS lead_created_at,
                    p.name AS project_name,
                    p.building_type,
                    f.flat_name,
                    f.floor_no,
                    IFNULL(f.land_share_value, 0.00) AS land_share_value,
                    IFNULL(f.price_value, 0.00) AS flat_price,
                    lb.sales_by_id
                FROM lead_bookings lb
                JOIN projects p ON lb.project_id = p.id
                JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN leads l ON lb.lead_id = l.id
                WHERE lb.sales_by_id = ? 
                  AND lb.booking_date BETWEEN ? AND ?
                  AND lb.deleted_at IS NULL
                  AND lb.booking_status NOT IN ('cancelled', 'refunded')
                ORDER BY lb.booking_date ASC, lb.id ASC";

        $startDate = substr($start, 0, 10);
        $endDate = substr($end, 0, 10);

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $startDate, $endDate);
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // 4. Categorize bookings and calculate volumes
        $assignedAchievedLandShare   = 0.00;
        $unassignedAchievedLandShare = 0.00;
        $processedBookings = [];
        $seenFlatsInCycle = [];

        foreach ($bookings as $b) {
            $bId = (int)$b['booking_id'];
            $fId = (int)$b['flat_id'];
            $pId = (int)$b['project_id'];
            $landShare = (float)$b['land_share_value'];

            // Check if flat is designated/assigned
            $isAssigned = false;
            if (!empty($flatIds)) {
                $isAssigned = in_array((string)$fId, array_map('strval', $flatIds), true);
            } elseif ($hasAllProjects || (!empty($projIds) && in_array((string)$pId, array_map('strval', $projIds), true))) {
                $isAssigned = true;
            } elseif (empty($flatIds) && empty($projIds)) {
                // If no specific restriction configured in target, treat as assigned
                $isAssigned = true;
            }

            // Check if incentive already claimed for this flat in another booking or prior cycle (one-time rule)
            $existingClaim = $this->getFlatClaimDetails($fId, $bId);
            $alreadyClaimed = !empty($existingClaim) || isset($seenFlatsInCycle[$fId]);
            $seenFlatsInCycle[$fId] = $bId;

            // Only non-duplicate sales count towards target achievement & qualification
            if (!$alreadyClaimed) {
                if ($isAssigned) {
                    $assignedAchievedLandShare += $landShare;
                } else {
                    $unassignedAchievedLandShare += $landShare;
                }
            }

            $b['is_assigned'] = $isAssigned;
            $b['already_claimed'] = $alreadyClaimed;
            $b['existing_claim'] = $existingClaim;
            $processedBookings[] = $b;
        }

        $totalAchievedLandShare = $assignedAchievedLandShare + $unassignedAchievedLandShare;

        // 5. Target Achievement & Minimum Qualification Check
        $effectiveTarget = ($targetAmount > 0) ? $targetAmount : $minEligibleSales;
        $achievementPct = ($effectiveTarget > 0) ? ($totalAchievedLandShare / $effectiveTarget) * 100.0 : 0.00;

        // Condition: Must achieve assigned target OR minimum eligible sales (BDT 25 Lakh) across total sales
        $isEligible = ($totalAchievedLandShare >= $minEligibleSales) || ($targetAmount > 0 && $assignedAchievedLandShare >= $targetAmount);

        // 6. Base Modality Rate determination based on Target Achievement Slab
        // Base rate: 0.70%
        // 200% - 299% achievement: 1.00%
        // 300%+ achievement: 1.20%
        $slabRate = (float)$settings['flat_base_rate']; // 0.70%
        if ($achievementPct >= 300.0) {
            $slabRate = (float)$settings['slab_300_plus_rate']; // 1.20%
        } elseif ($achievementPct >= 200.0) {
            $slabRate = (float)$settings['slab_200_299_rate'];  // 1.00%
        }

        // 7. Calculate individual booking incentive breakdown
        $detailedBookings = [];
        $totalEarnedIncentive = 0.00;
        $totalOnHoldIncentive = 0.00;

        foreach ($processedBookings as $b) {
            $landShare = (float)$b['land_share_value'];
            $isAssigned = $b['is_assigned'];
            $alreadyClaimed = $b['already_claimed'];

            // Duplicate flat check: If already claimed previously, rate is 0
            if ($alreadyClaimed) {
                $detailedBookings[] = array_merge($b, [
                    'rate_type' => 'Duplicate Claim Ineligible',
                    'applicable_rate' => 0.00,
                    'calculated_incentive' => 0.00,
                    'seller_incentive' => 0.00,
                    'designated_incentive' => 0.00,
                    'designated_user_id' => null,
                    'is_on_hold' => 0,
                    'status_note' => 'Incentive already claimed for this flat unit.'
                ]);
                continue;
            }

            // Check floor special: 2nd floor OR Top Floor / Below Top Floor
            $isFloorSpecial = $this->isFloorSpecial($b['floor_no'], $b['building_type']);

            // Determine unit base rate:
            // If super achievement slab (1.00% or 1.20%) is hit, slab rate applies.
            // Otherwise, floor special gives 0.80%, general flat gives 0.70%.
            $unitRate = $slabRate;
            if ($achievementPct < 200.0) {
                $unitRate = $isFloorSpecial ? (float)$settings['floor_special_rate'] : (float)$settings['flat_base_rate'];
            }

            // Check Deadline / Speed Bonus (+0.20%):
            // Deal completed within defined deadline (2 months / 60 days of lead creation OR before target to_date)
            $isWithinDeadline = false;
            $bDate = $b['booking_date'];
            $refDate = !empty($b['lead_created_at']) ? $b['lead_created_at'] : $b['booking_created_at'];
            $daysToClose = round((strtotime($bDate) - strtotime($refDate)) / 86400);

            if (($daysToClose <= 60 && $daysToClose >= 0) || ($bDate <= $targetToDate)) {
                $isWithinDeadline = true;
            }

            $bonusRate = 0.00;
            if ($isWithinDeadline) {
                $bonusRate = (float)$settings['speed_bonus_rate']; // +0.20%
            }

            $finalApplicableRate = $unitRate + $bonusRate;

            // Full incentive for this booking
            $fullIncentive = round(($landShare * $finalApplicableRate) / 100.0, 2);

            if ($isAssigned) {
                // ASSIGNED PROJECT / FLAT:
                // If executive is eligible (achieved target or >= 25L), earns 100% of calculated incentive
                $earned = $isEligible ? $fullIncentive : 0.00;
                $rateLabel = ($isFloorSpecial ? 'Floor (0.80%)' : 'Flat (0.70%)');
                if ($achievementPct >= 300.0) $rateLabel = '300%+ Tier (1.20%)';
                elseif ($achievementPct >= 200.0) $rateLabel = '200%-299% Tier (1.00%)';
                if ($isWithinDeadline) $rateLabel .= ' + Speed Bonus (+0.20%)';

                $detailedBookings[] = array_merge($b, [
                    'rate_type' => $rateLabel,
                    'applicable_rate' => $finalApplicableRate,
                    'calculated_incentive' => $earned,
                    'seller_incentive' => $earned,
                    'designated_incentive' => 0.00,
                    'designated_user_id' => null,
                    'is_on_hold' => 0,
                    'status_note' => $isEligible ? 'Eligible for claim' : 'Target/Minimum 25L not yet achieved'
                ]);

                if ($isEligible) {
                    $totalEarnedIncentive += $earned;
                }
            } else {
                // UNASSIGNED / OUTSIDE DESIGNATED FLAT:
                // Policy: 70% to Seller (on hold until seller achieves own target), 30% to Designated Person
                $sellerShare = round($fullIncentive * 0.70, 2);
                $designatedShare = round($fullIncentive * 0.30, 2);
                $designatedUserId = $this->getDesignatedSalesperson($b['flat_id'], $b['project_id'], $yearMonth, $userId);

                // Hold condition: 70% remains ON HOLD until seller achieves own assigned target/25L
                $isOnHold = !$isEligible;
                if ($isEligible) {
                    $totalEarnedIncentive += $sellerShare;
                } else {
                    $totalOnHoldIncentive += $sellerShare;
                }

                $detailedBookings[] = array_merge($b, [
                    'rate_type' => 'Outside Designated (70% Seller Share)',
                    'applicable_rate' => $finalApplicableRate * 0.70,
                    'calculated_incentive' => $sellerShare,
                    'seller_incentive' => $sellerShare,
                    'designated_incentive' => $designatedShare,
                    'designated_user_id' => $designatedUserId,
                    'is_on_hold' => $isOnHold ? 1 : 0,
                    'status_note' => $isOnHold 
                        ? 'On Hold (70%): Achieve your own assigned target/25L to unlock' 
                        : 'Unlocked: Assigned target fulfilled'
                ]);
            }
        }

        // 8. Also find if this executive is the DESIGNATED person receiving 30% from another agent's outside sale
        $thirdPartySql = "SELECT 
                            lb.id AS booking_id, 
                            lb.project_id, 
                            lb.flat_id, 
                            lb.booking_date,
                            p.name AS project_name,
                            p.building_type,
                            f.flat_name,
                            f.floor_no,
                            IFNULL(f.land_share_value, 0.00) AS land_share_value,
                            lb.sales_by_id,
                            u.full_name AS seller_name
                          FROM lead_bookings lb
                          JOIN projects p ON lb.project_id = p.id
                          JOIN flats f ON lb.flat_id = f.id
                          JOIN users u ON lb.sales_by_id = u.id
                          WHERE lb.sales_by_id != ? 
                            AND lb.booking_date BETWEEN ? AND ?
                            AND lb.deleted_at IS NULL
                            AND lb.booking_status NOT IN ('cancelled', 'refunded')";

        $stmt3 = $this->db->prepare($thirdPartySql);
        $stmt3->bind_param("iss", $userId, $startDate, $endDate);
        $stmt3->execute();
        $otherBookings = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt3->close();

        $designatedCredits = [];
        foreach ($otherBookings as $ob) {
            $fId = (int)$ob['flat_id'];
            $pId = (int)$ob['project_id'];

            // Do not grant designated credit if flat already claimed elsewhere
            if ($this->isFlatIncentiveClaimed($fId, $ob['booking_id'])) {
                continue;
            }

            $isDesignatedToMe = false;

            if (!empty($flatIds) && in_array((string)$fId, array_map('strval', $flatIds), true)) {
                $isDesignatedToMe = true;
            } elseif ($hasAllProjects || (!empty($projIds) && in_array((string)$pId, array_map('strval', $projIds), true))) {
                $isDesignatedToMe = true;
            }

            if ($isDesignatedToMe) {
                $ls = (float)$ob['land_share_value'];
                $isFloorSpecial = $this->isFloorSpecial($ob['floor_no'], $ob['building_type']);
                $bRate = $isFloorSpecial ? (float)$settings['floor_special_rate'] : (float)$settings['flat_base_rate'];
                $creditAmount = round(($ls * $bRate / 100.0) * 0.30, 2); // 30% share

                if ($isEligible) {
                    $totalEarnedIncentive += $creditAmount;
                }

                $designatedCredits[] = [
                    'booking_id' => $ob['booking_id'],
                    'seller_name' => $ob['seller_name'],
                    'project_name' => $ob['project_name'],
                    'flat_name' => $ob['flat_name'],
                    'land_share_value' => $ls,
                    'credit_amount' => $creditAmount,
                    'is_eligible' => $isEligible
                ];
            }
        }

        return [
            'target_amount' => $targetAmount,
            'effective_target' => $effectiveTarget,
            'min_eligible_sales' => $minEligibleSales,
            'assigned_achieved' => $assignedAchievedLandShare,
            'unassigned_achieved' => $unassignedAchievedLandShare,
            'total_achieved_land_share' => $totalAchievedLandShare,
            'achievement_pct' => $achievementPct,
            'is_eligible' => $isEligible,
            'base_rate' => $slabRate,
            'total_earned_incentive' => $totalEarnedIncentive,
            'total_on_hold_incentive' => $totalOnHoldIncentive,
            'detailed_bookings' => $detailedBookings,
            'designated_credits' => $designatedCredits,
            'cycle_start' => $start,
            'cycle_end' => $end
        ];
    }

    /**
     * Calculates the Team Leader commission for a month
     * Applies 0.05% on total land share sales of eligible team members (who achieved target or >= 25L)
     */
    public function getTeamMonthlyStats($userId, $yearMonth, $filterTeamId = null, $fromDate = null, $toDate = null)
    {
        $settings = $this->getSettings();
        $tlRate = (float)$settings['team_leader_rate']; // 0.05%

        // 1. Identify User Role and Team scope
        $empStmt = $this->db->prepare("
            SELECT u.role_id, e.team_id, e.is_team_leader, e.is_sales_co_leader, t.name as team_name
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
        $isTeamLeader = $emp ? (int)$emp['is_team_leader'] : 0;

        $isSalesHead = ($roleId == 9 || in_array($roleId, [1, 3]));
        $isEligibleRole = ($isSalesHead || $isTeamLeader == 1 || in_array($roleId, [5, 6, 7]));

        if (!$isEligibleRole) {
            return ['eligible' => false, 'message' => 'Not a team leader role.'];
        }

        if (!$isSalesHead && $teamId == 0 && empty($filterTeamId)) {
            return ['eligible' => false, 'message' => 'No team assigned.'];
        }

        $targetTeamId = (!empty($filterTeamId) && (int)$filterTeamId > 0) ? (int)$filterTeamId : $teamId;
        $isFiltered = (!empty($filterTeamId) && (int)$filterTeamId > 0);

        // 2. Fetch Team Members
        $memberSql = "SELECT e.user_id, e.full_name, e.employee_id, e.designation_id, des.name as designation_name
                      FROM employees e
                      LEFT JOIN designations des ON e.designation_id = des.id
                      WHERE e.deleted_at IS NULL ";
        if (!$isSalesHead || $isFiltered) {
            $memberSql .= " AND e.team_id = " . (int)$targetTeamId;
        }
        $memberSql .= " ORDER BY e.full_name ASC";

        $memberRes = $this->db->query($memberSql);
        $members = $memberRes ? $memberRes->fetch_all(MYSQLI_ASSOC) : [];

        $totalTeamTarget = 0.00;
        $totalTeamAchieved = 0.00;
        $totalTeamCollected = 0.00;
        $qualifiedSalesVolume = 0.00;
        $qualifiedMembersCount = 0;
        $memberBreakdown = [];

        foreach ($members as $m) {
            $mUserId = (int)$m['user_id'];
            $mStats = $this->getExecutiveMonthlyStats($mUserId, $yearMonth, $fromDate, $toDate);

            $totalTeamTarget += (float)$mStats['effective_target'];
            $totalTeamAchieved += (float)$mStats['total_achieved_land_share'];

            $isMemberQualified = !empty($mStats['is_eligible']);
            $qualifiedVolumeForMember = $isMemberQualified ? (float)$mStats['assigned_achieved'] : 0.00;

            if ($isMemberQualified && $qualifiedVolumeForMember > 0) {
                $qualifiedSalesVolume += $qualifiedVolumeForMember;
                $qualifiedMembersCount++;
            }

            // Calculate money collected by this member for bookings in this cycle
            $mStart = substr($mStats['cycle_start'], 0, 10);
            $mEnd   = substr($mStats['cycle_end'], 0, 10);
            $collStmt = $this->db->prepare("
                SELECT IFNULL(SUM(lp.amount), 0.00) as collected
                FROM lead_payments lp
                JOIN lead_bookings lb ON lp.booking_id = lb.id
                WHERE lb.sales_by_id = ?
                  AND lb.booking_date BETWEEN ? AND ?
                  AND lp.verification_status = 'verified'
                  AND lp.payment_status IN ('paid', 'partially_paid')
                  AND COALESCE(lp.is_trashed, 0) = 0
            ");
            $mCollected = 0.00;
            if ($collStmt) {
                $collStmt->bind_param("iss", $mUserId, $mStart, $mEnd);
                $collStmt->execute();
                $mCollected = (float)($collStmt->get_result()->fetch_assoc()['collected'] ?? 0.00);
                $collStmt->close();
            }

            $totalTeamCollected += $mCollected;

            $memberBreakdown[] = [
                'user_id' => $mUserId,
                'full_name' => $m['full_name'],
                'employee_id' => $m['employee_id'],
                'designation' => $m['designation_name'] ?? 'Executive',
                'target' => (float)$mStats['effective_target'],
                'achieved_land_share' => (float)$mStats['total_achieved_land_share'],
                'assigned_achieved' => (float)$mStats['assigned_achieved'],
                'achievement_pct' => (float)$mStats['achievement_pct'],
                'collected_amount' => $mCollected,
                'is_qualified' => $isMemberQualified,
                'tl_contribution' => round(($qualifiedVolumeForMember * $tlRate) / 100.0, 2)
            ];
        }

        $teamAchievementPct = ($totalTeamTarget > 0) ? ($totalTeamAchieved / $totalTeamTarget) * 100.0 : 0.00;

        // 3. Team Leader Commission: 0.05% on qualified members' land share sales
        $incentiveEarned = round(($qualifiedSalesVolume * $tlRate) / 100.0, 2);

        // Display team name
        $displayTeamName = $emp ? $emp['team_name'] : 'Sales Team';
        if ($isFiltered) {
            $tQ = $this->db->query("SELECT name FROM teams WHERE id = " . $targetTeamId);
            if ($tQ && $tRow = $tQ->fetch_assoc()) {
                $displayTeamName = $tRow['name'];
            }
        } elseif ($isSalesHead) {
            $displayTeamName = 'Company-Wide (All Teams)';
        }

        return [
            'eligible' => true,
            'role_type' => $isSalesHead ? 'sales_head' : 'team_leader',
            'team_id' => $targetTeamId,
            'team_name' => $displayTeamName,
            'is_filtered' => $isFiltered,
            'team_target' => $totalTeamTarget,
            'team_achieved' => $totalTeamAchieved,
            'team_collected' => $totalTeamCollected,
            'team_achievement_pct' => $teamAchievementPct,
            'eligible_sales_volume' => $qualifiedSalesVolume,
            'qualified_members_count' => $qualifiedMembersCount,
            'total_members_count' => count($members),
            'tl_rate' => $tlRate,
            'incentive_earned' => $incentiveEarned,
            'member_breakdown' => $memberBreakdown,
            'month' => $yearMonth
        ];
    }
}
