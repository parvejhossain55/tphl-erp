<?php

require_once __DIR__ . '/../services/IncentiveCalculationEngine.php';

class IncentiveModel extends Model
{
    protected $engine;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->engine = new IncentiveCalculationEngine($db);
    }

    /**
     * Get all sales bookings assigned to or created by a Sales Executive
     */
    public function getMySalesBookings($userId)
    {
        $sql = "SELECT 
                    lb.id AS booking_id,
                    lb.project_id,
                    lb.flat_id,
                    lb.lead_id,
                    lb.created_at AS booking_date,
                    lb.sales_by_id,
                    l.full_name AS customer_name,
                    l.phone AS phone,
                    p.name AS project_name,
                    p.category_id,
                    pc.name AS category_name,
                    CASE 
                        WHEN IFNULL(p.incentive_percentage, 0) > 0 THEN p.incentive_percentage 
                        WHEN IFNULL(pc.incentive_percentage, 0) > 0 THEN pc.incentive_percentage 
                        ELSE 1.00 
                    END AS incentive_percentage,
                    f.flat_name,
                    f.sqft,
                    IFNULL(f.price_value, 0.00) AS flat_price,
                    IFNULL(f.land_share_value, 0.00) AS land_share_value,
                    (SELECT IFNULL(SUM(lp.amount), 0.00) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                       AND lp.verification_status = 'verified' 
                       AND lp.payment_status IN ('paid', 'partially_paid')) AS total_collected_amount,
                    (SELECT IFNULL(SUM(lp.amount), 0.00) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                       AND lp.payment_type = 'booking_money' 
                       AND lp.verification_status = 'verified' 
                       AND lp.payment_status IN ('paid', 'partially_paid')) AS booking_money_amount,
                    c.id AS claim_id,
                    c.claim_status,
                    c.claimed_at,
                    c.approved_at,
                    c.remarks
                FROM lead_bookings lb
                LEFT JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN projects p ON lb.project_id = p.id
                LEFT JOIN project_categories pc ON p.category_id = pc.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN project_incentive_claims c ON (c.booking_id = lb.id AND c.is_external = 0)
                WHERE (lb.sales_by_id = ? OR l.assigned_id = ?)
                  AND lb.deleted_at IS NULL
                ORDER BY lb.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $bookings = [];
        $monthlyStatsCache = [];

        while ($row = $result->fetch_assoc()) {
            $bookingId = (int)$row['booking_id'];
            $bookingDate = $row['booking_date'];
            $yearMonth = $this->getCycleForDate($bookingDate);

            // Cache monthly stats to avoid redundant DB calls
            if (!isset($monthlyStatsCache[$yearMonth])) {
                $monthlyStatsCache[$yearMonth] = $this->engine->getExecutiveMonthlyStats($userId, $yearMonth);
            }
            $stats = $monthlyStatsCache[$yearMonth];

            // Check if Booking Money is paid & verified
            $bookingMoneyVerified = $this->isPaymentVerified($bookingId, 'booking_money');

            // Check if Registration is paid & verified
            $registrationVerified = $this->isPaymentVerified($bookingId, 'registration');

            // Calculate incentive amount based on new rules
            $flatPrice = (float)$row['flat_price'];
            $landShareValue = (float)$row['land_share_value'];
            
            $isAssigned = ($stats['assigned_category_id'] > 0 && $row['category_id'] == $stats['assigned_category_id']);
            $applicablePct = $isAssigned ? $stats['final_assigned_pct'] : $stats['final_unassigned_pct'];
            
            $calculatedIncentive = ($flatPrice * $applicablePct) / 100.0;
            
            // Claim status logic
            $claimStatus = $row['claim_status'];
            if (empty($claimStatus)) {
                if ($bookingMoneyVerified && $registrationVerified) {
                    $claimStatus = 'eligible';
                } else {
                    $claimStatus = 'ineligible';
                }
            }

            $bookings[] = [
                'id'                     => $bookingId,
                'booking_no'             => 'BK-' . str_pad($bookingId, 5, '0', STR_PAD_LEFT),
                'customer_name'          => $row['customer_name'] ?? 'N/A',
                'phone'                  => $row['phone'] ?? 'N/A',
                'project_name'           => $row['project_name'] ?? 'N/A',
                'flat_name'              => $row['flat_name'] ?? 'N/A',
                'sqft'                   => (int)($row['sqft'] ?? 0),
                'flat_price'             => $flatPrice,
                'land_share_value'       => $landShareValue,
                'total_collected'        => (float)$row['total_collected_amount'],
                'booking_money_collected'=> (float)$row['booking_money_amount'],
                'category_name'          => $row['category_name'] ?? 'Standard',
                'incentive_percentage'   => $applicablePct,
                'calculated_incentive'   => $calculatedIncentive,
                'booking_completed'      => $bookingMoneyVerified ? 1 : 0,
                'registration_completed' => $registrationVerified ? 1 : 0,
                'claim_status'           => $claimStatus,
                'claimed_at'             => $row['claimed_at'],
                'approved_at'            => $row['approved_at'],
                'remarks'                => $row['remarks'] ?? ''
            ];
        }

        $stmt->close();
        return $bookings;
    }

    /**
     * Helper: Check if specific payment type is verified in lead_payments
     */
    private function isPaymentVerified($bookingId, $paymentType)
    {
        $sql = "SELECT COUNT(*) AS total 
                FROM lead_payments 
                WHERE booking_id = ? 
                  AND payment_type = ? 
                  AND verification_status = 'verified' 
                  AND payment_status IN ('paid', 'partially_paid')";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("is", $bookingId, $paymentType);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($res['total'] ?? 0) > 0;
    }

    /**
     * Get Wallet & Sales Collection Summary for a User
     */
    public function getWalletSummary($userId)
    {
        // Total Money Collected across all verified payments for bookings by this sales person
        $sqlColl = "SELECT IFNULL(SUM(lp.amount), 0.00) AS total_sales_collected
                    FROM lead_payments lp
                    JOIN lead_bookings lb ON lp.booking_id = lb.id
                    LEFT JOIN leads l ON lb.lead_id = l.id
                    WHERE (lb.sales_by_id = ? OR l.assigned_id = ?)
                      AND lp.verification_status = 'verified'
                      AND lp.payment_status IN ('paid', 'partially_paid')
                      AND lb.deleted_at IS NULL";

        $stmtColl = $this->db->prepare($sqlColl);
        $totalSalesCollected = 0.00;
        if ($stmtColl) {
            $stmtColl->bind_param("ii", $userId, $userId);
            $stmtColl->execute();
            $rowColl = $stmtColl->get_result()->fetch_assoc();
            $totalSalesCollected = (float)($rowColl['total_sales_collected'] ?? 0.00);
            $stmtColl->close();
        }

        $sql = "SELECT 
                    SUM(CASE WHEN claim_status IN ('claimed', 'approved', 'partially_paid', 'paid') THEN incentive_amount ELSE 0 END) AS total_earned,
                    SUM(CASE WHEN claim_status = 'claimed' THEN incentive_amount ELSE 0 END) AS pending_claims,
                    SUM(CASE WHEN claim_status IN ('approved', 'partially_paid', 'paid') THEN incentive_amount ELSE 0 END) AS approved_amount
                FROM project_incentive_claims
                WHERE user_id = ?";

        $stmt = $this->db->prepare($sql);
        $totalEarned = 0.00;
        $pendingClaims = 0.00;
        $approvedAmount = 0.00;

        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $totalEarned = (float)($row['total_earned'] ?? 0.00);
            $pendingClaims = (float)($row['pending_claims'] ?? 0.00);
            $approvedAmount = (float)($row['approved_amount'] ?? 0.00);
            $stmt->close();
        }

        // Get total disbursed / paid
        $sqlPaid = "SELECT SUM(payment_amount) AS total_paid FROM project_incentive_payments WHERE user_id = ?";
        $stmtPaid = $this->db->prepare($sqlPaid);
        $disbursedPaid = 0.00;

        if ($stmtPaid) {
            $stmtPaid->bind_param("i", $userId);
            $stmtPaid->execute();
            $rowPaid = $stmtPaid->get_result()->fetch_assoc();
            $disbursedPaid = (float)($rowPaid['total_paid'] ?? 0.00);
            $stmtPaid->close();
        }

        $availableBalance = max(0.00, $approvedAmount - $disbursedPaid);

        return [
            'total_sales_collected' => $totalSalesCollected,
            'total_earned'          => $totalEarned,
            'pending_claims'        => $pendingClaims,
            'approved_amount'       => $approvedAmount,
            'disbursed_paid'        => $disbursedPaid,
            'available_balance'     => $availableBalance
        ];
    }

    /**
     * Submit an Incentive Claim for a Sales Booking
     */
    public function claimIncentive($userId, $bookingId, $remarks = '')
    {
        // Fetch booking details
        $sql = "SELECT 
                    lb.id, lb.project_id, lb.flat_id,
                    p.name AS project_name,
                    CASE 
                        WHEN IFNULL(p.incentive_percentage, 0) > 0 THEN p.incentive_percentage 
                        WHEN IFNULL(pc.incentive_percentage, 0) > 0 THEN pc.incentive_percentage 
                        ELSE 1.00 
                    END AS incentive_percentage,
                    IFNULL(f.price_value, 0.00) AS flat_price
                FROM lead_bookings lb
                LEFT JOIN projects p ON lb.project_id = p.id
                LEFT JOIN project_categories pc ON p.category_id = pc.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                WHERE lb.id = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error preparing query'];
        }

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }

        // Verify eligibility
        $bMoney = $this->isPaymentVerified($bookingId, 'booking_money');
        $reg = $this->isPaymentVerified($bookingId, 'registration');

        if (!$bMoney || !$reg) {
            return ['success' => false, 'message' => 'Claim ineligible: Booking Money and Registration must both be completed and verified.'];
        }

        $saleAmount = (float)$booking['flat_price'];
        $incentivePct = (float)$booking['incentive_percentage'];
        if ($incentivePct <= 0) $incentivePct = 1.00;
        $incentiveAmount = ($saleAmount * $incentivePct) / 100.0;

        // Check if claim exists
        $chkSql = "SELECT id FROM project_incentive_claims WHERE booking_id = ? AND is_external = 0 LIMIT 1";
        $chkStmt = $this->db->prepare($chkSql);
        $chkStmt->bind_param("i", $bookingId);
        $chkStmt->execute();
        $existing = $chkStmt->get_result()->fetch_assoc();
        $chkStmt->close();

        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $claimId = (int)$existing['id'];
            $updSql = "UPDATE project_incentive_claims 
                       SET claim_status = 'claimed', claimed_at = ?, remarks = ?, sale_amount = ?, incentive_percentage = ?, incentive_amount = ? 
                       WHERE id = ?";
            $updStmt = $this->db->prepare($updSql);
            $updStmt->bind_param("ssdddi", $now, $remarks, $saleAmount, $incentivePct, $incentiveAmount, $claimId);
            $updStmt->execute();
            $updStmt->close();
        } else {
            $insSql = "INSERT INTO project_incentive_claims 
                       (booking_id, project_id, flat_id, user_id, is_external, sale_amount, incentive_percentage, incentive_amount, booking_completed, registration_completed, claim_status, claimed_at, remarks) 
                       VALUES (?, ?, ?, ?, 0, ?, ?, ?, 1, 1, 'claimed', ?, ?)";
            $insStmt = $this->db->prepare($insSql);
            $insStmt->bind_param("iiiidddss", $bookingId, $booking['project_id'], $booking['flat_id'], $userId, $saleAmount, $incentivePct, $incentiveAmount, $now, $remarks);
            $insStmt->execute();
            $claimId = $insStmt->insert_id;
            $insStmt->close();
        }

        // Add Approval Request entry for Home Page & approval_requests queue
        $this->createApprovalRequest($userId, $claimId, $bookingId, $incentiveAmount);

        return ['success' => true, 'message' => 'Incentive claim submitted successfully and sent for Management approval.'];
    }

    /**
     * Create approval request in approval_requests table
     */
    private function createApprovalRequest($userId, $claimId, $bookingId, $amount)
    {
        $bookingCode = 'BK-' . str_pad($bookingId, 5, '0', STR_PAD_LEFT);
        $title = "Sales Incentive Claim for Booking #{$bookingCode}";

        $sql = "INSERT INTO approval_requests 
                (department, request_type, title, source_table, source_id, request_amount, status, requested_by, approve_role, created_at)
                VALUES ('Sales', 'Incentive Claim', ?, 'project_incentive_claims', ?, ?, 'pending', ?, 'management', NOW())";

        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sidi", $title, $claimId, $amount, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Get Company Overall Incentive Metrics
     */
    public function getCompanyMetrics($month = null)
    {
        $sql = "SELECT 
                    SUM(incentive_amount) AS total_pool,
                    SUM(CASE WHEN claim_status = 'claimed' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN claim_status = 'claimed' THEN incentive_amount ELSE 0 END) AS pending_amount,
                    SUM(CASE WHEN claim_status IN ('approved', 'partially_paid', 'paid') THEN incentive_amount ELSE 0 END) AS total_approved,
                    SUM(CASE WHEN is_external = 1 THEN 1 ELSE 0 END) AS external_count
                FROM project_incentive_claims";
        
        if ($month) {
            $sql .= " WHERE DATE_FORMAT(created_at, '%Y-%m') = '" . $this->db->real_escape_string($month) . "'";
        }

        $res = $this->db->query($sql);
        $row = $res ? $res->fetch_assoc() : [];

        $sqlDisbursed = "SELECT SUM(payment_amount) AS total_disbursed FROM project_incentive_payments";
        $resD = $this->db->query($sqlDisbursed);
        $totalDisbursed = $resD ? (float)($resD->fetch_assoc()['total_disbursed'] ?? 0) : 0.00;

        return [
            'total_incentive_pool'  => (float)($row['total_pool'] ?? 0.00),
            'pending_claims_count'  => (int)($row['pending_count'] ?? 0),
            'pending_claims_amount' => (float)($row['pending_amount'] ?? 0.00),
            'total_approved'        => (float)($row['total_approved'] ?? 0.00),
            'total_disbursed'       => $totalDisbursed,
            'external_count'        => (int)($row['external_count'] ?? 0)
        ];
    }

    /**
     * Get Sales Persons Claims Queue for Accounts
     * ONLY returns claims that have actually been submitted by Sales Executives!
     */
    public function getSalesClaimsQueue($month = null)
    {
        $sql = "SELECT 
                    c.*,
                    u.full_name AS user_name,
                    IFNULL(e.employee_id, CONCAT('EMP-', u.id)) AS employee_id,
                    IFNULL(d.name, 'Sales') AS department,
                    IFNULL(des.name, 'Executive') AS designation,
                    p.name AS project_name,
                    f.flat_name,
                    IFNULL(f.land_share_value, 0.00) AS land_share_value,
                    (SELECT SUM(payment_amount) FROM project_incentive_payments WHERE claim_id = c.id) AS total_paid
                FROM project_incentive_claims c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN projects p ON c.project_id = p.id
                LEFT JOIN flats f ON c.flat_id = f.id
                WHERE c.is_external = 0";

        if ($month) {
            $sql .= " AND DATE_FORMAT(c.created_at, '%Y-%m') = '" . $this->db->real_escape_string($month) . "'";
        }
        
        $sql .= " ORDER BY c.id DESC";

        $res = $this->db->query($sql);
        $queue = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $incAmt = (float)$row['incentive_amount'];
                $paidAmt = (float)($row['total_paid'] ?? 0.00);
                $dueAmt = max(0.00, $incAmt - $paidAmt);

                $claimId = (int)$row['id'];
                $claimCode = 'INC-' . date('Y', strtotime($row['created_at'])) . '-' . str_pad($claimId, 3, '0', STR_PAD_LEFT);

                $queue[] = [
                    'id'                    => $claimId,
                    'claim_code'            => $claimCode,
                    'user_id'               => (int)$row['user_id'],
                    'user_name'             => $row['user_name'] ?? 'Sales Executive',
                    'employee_id'           => $row['employee_id'] ?? ('EMP-' . $row['user_id']),
                    'department'            => $row['department'] ?? 'Sales',
                    'designation'           => $row['designation'] ?? 'Executive',
                    'is_external'           => 0,
                    'project_name'          => $row['project_name'] ?? 'N/A',
                    'flat_name'             => $row['flat_name'] ?? 'N/A',
                    'sale_amount'           => (float)$row['sale_amount'],
                    'land_share_value'      => (float)$row['land_share_value'],
                    'incentive_percentage' => (float)$row['incentive_percentage'],
                    'incentive_amount'      => $incAmt,
                    'paid_amount'           => $paidAmt,
                    'due_amount'            => $dueAmt,
                    'booking_completed'     => (int)$row['booking_completed'],
                    'registration_completed'=> (int)$row['registration_completed'],
                    'claim_status'          => $row['claim_status'],
                    'claimed_at'            => $row['claimed_at'] ?? $row['created_at'],
                    'remarks'               => $row['remarks'] ?? ''
                ];
            }
        }

        return $queue;
    }

    /**
     * Get Real-Time Sales Incentive Stats for the Month (Target vs Achievement)
     */
    public function getRealTimeIncentivesByMonth($month)
    {
        $stats = [];
        
        // Find all users who have a target this month OR who are team leaders/coordinators
        $sql = "SELECT DISTINCT u.id, u.full_name, e.employee_id, d.name AS department, des.name AS designation, e.team_id 
                FROM users u
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE u.id IN (
                    SELECT sales_user_id FROM sales_targets WHERE DATE_FORMAT(month, '%Y-%m') = ?
                ) OR u.role_id IN (5,6,7,9) OR e.is_sales_co_leader = 1";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $month);
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($users as $u) {
            $userId = (int)$u['id'];
            $ind = $this->engine->getExecutiveMonthlyStats($userId, $month);
            $team = $this->engine->getTeamMonthlyStats($userId, $month);
            
            // Only add to list if they have some target or achievement
            if ($ind['target_amount'] > 0 || $ind['assigned_achieved'] > 0 || $ind['unassigned_achieved'] > 0 || ($team['eligible'] && $team['team_target'] > 0)) {
                $stats[] = [
                    'user'       => $u,
                    'individual' => $ind,
                    'team'       => $team
                ];
            }
        }
        
        return $stats;
    }

    /**
     * Get Team Sales Breakdown for a specific manager and month
     */
    public function getTeamSalesBreakdown($managerId, $month, $filterTeamId = null)
    {
        $managerId = (int)$managerId;
        
        // Find if this user is a manager/head and get their team_id or logic
        $sqlRole = "SELECT role_id FROM users WHERE id = ?";
        $stmtRole = $this->db->prepare($sqlRole);
        $stmtRole->bind_param("i", $managerId);
        $stmtRole->execute();
        $resRole = $stmtRole->get_result()->fetch_assoc();
        $stmtRole->close();
        
        if (!$resRole) return [];
        $roleId = (int)$resRole['role_id'];
        
        // Find team_id for this user
        $sqlTeam = "SELECT team_id FROM employees WHERE user_id = ?";
        $stmtTeam = $this->db->prepare($sqlTeam);
        $stmtTeam->bind_param("i", $managerId);
        $stmtTeam->execute();
        $resTeam = $stmtTeam->get_result()->fetch_assoc();
        $stmtTeam->close();
        
        $teamId = $resTeam ? (int)$resTeam['team_id'] : 0;
        
        if ($roleId != 1 && $roleId != 3 && $roleId != 6 && $roleId != 9 && $teamId == 0 && empty($filterTeamId)) {
            // Not sales head, not admin, and no team ID
            return [];
        }

        // If sales head (role 9) or admin (1, 3) or management (6)
        if ($roleId == 1 || $roleId == 3 || $roleId == 6 || $roleId == 9) {
            if (!empty($filterTeamId) && (int)$filterTeamId > 0) {
                $sql = "SELECT 
                            lb.id, lb.booking_date, 
                            u.full_name as sales_person, 
                            p.name as project_name, 
                            f.flat_name, 
                            IFNULL(f.land_share_value, 0.00) as land_share_value,
                            lb.booking_status
                        FROM lead_bookings lb
                        JOIN users u ON lb.sales_by_id = u.id
                        JOIN projects p ON lb.project_id = p.id
                        JOIN flats f ON lb.flat_id = f.id
                        WHERE u.id IN (SELECT user_id FROM employees WHERE team_id = ?)
                        AND DATE_FORMAT(lb.booking_date, '%Y-%m') = ?
                        AND lb.deleted_at IS NULL
                        AND lb.booking_status != 'Cancelled'
                        ORDER BY lb.booking_date DESC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("is", $filterTeamId, $month);
            } else {
                $sql = "SELECT 
                            lb.id, lb.booking_date, 
                            u.full_name as sales_person, 
                            p.name as project_name, 
                            f.flat_name, 
                            IFNULL(f.land_share_value, 0.00) as land_share_value,
                            lb.booking_status
                        FROM lead_bookings lb
                        JOIN users u ON lb.sales_by_id = u.id
                        JOIN projects p ON lb.project_id = p.id
                        JOIN flats f ON lb.flat_id = f.id
                        WHERE DATE_FORMAT(lb.booking_date, '%Y-%m') = ?
                        AND lb.deleted_at IS NULL
                        AND lb.booking_status != 'Cancelled'
                        ORDER BY lb.booking_date DESC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("s", $month);
            }
        } else {
            $effectiveTeamId = (!empty($filterTeamId) && (int)$filterTeamId > 0) ? (int)$filterTeamId : $teamId;
            // Normal Team Leader sees their team members
            $sql = "SELECT 
                        lb.id, lb.booking_date, 
                        u.full_name as sales_person, 
                        p.name as project_name, 
                        f.flat_name, 
                        IFNULL(f.land_share_value, 0.00) as land_share_value,
                        lb.booking_status
                    FROM lead_bookings lb
                    JOIN users u ON lb.sales_by_id = u.id
                    JOIN projects p ON lb.project_id = p.id
                    JOIN flats f ON lb.flat_id = f.id
                    WHERE u.id IN (SELECT user_id FROM employees WHERE team_id = ?)
                    AND DATE_FORMAT(lb.booking_date, '%Y-%m') = ?
                    AND lb.deleted_at IS NULL
                    AND lb.booking_status != 'Cancelled'
                    ORDER BY lb.booking_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("is", $effectiveTeamId, $month);
        }
        
        $stmt->execute();
        $breakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $breakdown;
    }

    /**
     * Get External / Non-Sales Member Claims List
     */
    public function getExternalClaimsList()
    {
        $sql = "SELECT 
                    c.*,
                    u.full_name AS user_name,
                    e.employee_id,
                    d.name AS department,
                    p.name AS project_name,
                    f.flat_name,
                    IFNULL(f.land_share_value, 0.00) AS land_share_value,
                    mgr.full_name AS assigned_by_name,
                    (SELECT SUM(payment_amount) FROM project_incentive_payments WHERE claim_id = c.id) AS total_paid
                FROM project_incentive_claims c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN projects p ON c.project_id = p.id
                LEFT JOIN flats f ON c.flat_id = f.id
                LEFT JOIN users mgr ON c.assigned_by = mgr.id
                WHERE c.is_external = 1
                ORDER BY c.id DESC";

        $res = $this->db->query($sql);
        $list = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $incAmt = (float)$row['incentive_amount'];
                $paidAmt = (float)($row['total_paid'] ?? 0.00);

                $list[] = [
                    'id'                    => (int)$row['id'],
                    'claim_code'            => 'EXT-' . date('Y', strtotime($row['created_at'])) . '-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
                    'user_name'             => $row['user_name'] ?? 'N/A',
                    'employee_id'           => $row['employee_id'] ?? ('EMP-' . $row['user_id']),
                    'department'            => $row['department'] ?? 'Other Dept',
                    'project_name'          => $row['project_name'] ?? 'N/A',
                    'flat_name'             => $row['flat_name'] ?? 'N/A',
                    'sale_amount'           => (float)$row['sale_amount'],
                    'land_share_value'      => (float)$row['land_share_value'],
                    'incentive_percentage' => (float)$row['incentive_percentage'],
                    'incentive_amount'      => $incAmt,
                    'paid_amount'           => $paidAmt,
                    'assigned_by_name'      => $row['assigned_by_name'] ?? 'Management',
                    'assigned_at'           => $row['created_at'],
                    'claim_status'          => $row['claim_status'],
                    'remarks'               => $row['remarks'] ?? ''
                ];
            }
        }

        return $list;
    }

    /**
     * Get Non-Sales Employees for Manual Incentive Assignment Dropdown
     */
    public function getNonSalesEmployees()
    {
        $sql = "SELECT 
                    u.id AS user_id,
                    u.full_name AS name,
                    IFNULL(e.employee_id, CONCAT('EMP-', u.id)) AS employee_id,
                    IFNULL(d.name, 'General') AS department,
                    IFNULL(des.name, 'Staff') AS designation
                FROM users u
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                ORDER BY u.full_name ASC";

        $res = $this->db->query($sql);
        $employees = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $employees[] = $row;
            }
        }

        return $employees;
    }

    /**
     * Get Available Projects & Flats for Selector
     */
    public function getAvailableProjectsFlats()
    {
        $sql = "SELECT 
                    f.id AS flat_id,
                    f.flat_name,
                    f.sqft,
                    IFNULL(f.price_value, 0.00) AS flat_price,
                    IFNULL(f.land_share_value, 0.00) AS land_share_value,
                    p.id AS project_id,
                    p.name AS project_name
                FROM flats f
                JOIN projects p ON f.project_id = p.id
                WHERE f.deleted_at IS NULL
                ORDER BY p.name ASC, f.flat_name ASC";

        $res = $this->db->query($sql);
        $flats = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $flats[] = [
                    'project_id'       => (int)$row['project_id'],
                    'project_name'     => $row['project_name'],
                    'flat_id'          => (int)$row['flat_id'],
                    'flat_name'        => $row['flat_name'],
                    'sqft'             => (int)$row['sqft'],
                    'flat_price'       => (float)$row['flat_price'],
                    'land_share_value' => (float)$row['land_share_value']
                ];
            }
        }

        return $flats;
    }

    /**
     * Assign External Incentive to a Non-Sales Employee
     */
    public function assignExternalIncentive($assignedBy, $userId, $flatId, $incentivePct, $customAmount, $remarks)
    {
        $sql = "SELECT f.id AS flat_id, f.project_id, IFNULL(f.price_value, 0.00) AS flat_price
                FROM flats f WHERE f.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'DB Query failed'];
        }

        $stmt->bind_param("i", $flatId);
        $stmt->execute();
        $flat = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$flat) {
            return ['success' => false, 'message' => 'Flat unit not found'];
        }

        $saleAmount = (float)$flat['flat_price'];
        $pct = (float)$incentivePct;
        $incentiveAmount = $customAmount > 0 ? (float)$customAmount : (($saleAmount * $pct) / 100.0);

        $now = date('Y-m-d H:i:s');
        $insSql = "INSERT INTO project_incentive_claims 
                   (project_id, flat_id, user_id, is_external, assigned_by, sale_amount, incentive_percentage, incentive_amount, booking_completed, registration_completed, claim_status, approved_by, approved_at, remarks)
                   VALUES (?, ?, ?, 1, ?, ?, ?, ?, 1, 1, 'approved', ?, ?, ?)";

        $insStmt = $this->db->prepare($insSql);
        if (!$insStmt) {
            return ['success' => false, 'message' => 'DB insert preparation error: ' . $this->db->error];
        }

        $insStmt->bind_param("iiiidddiss", $flat['project_id'], $flatId, $userId, $assignedBy, $saleAmount, $pct, $incentiveAmount, $assignedBy, $now, $remarks);
        
        if ($insStmt->execute()) {
            $insStmt->close();
            return ['success' => true, 'message' => 'External employee sales incentive assigned successfully!'];
        } else {
            $error = $insStmt->error;
            $insStmt->close();
            return ['success' => false, 'message' => 'Failed to assign incentive: ' . $error];
        }
    }

    /**
     * Approve Claim (Accounts / Management)
     */
    public function approveClaim($approvedBy, $claimId)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE project_incentive_claims SET claim_status = 'approved', approved_by = ?, approved_at = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isi", $approvedBy, $now, $claimId);
            $stmt->execute();
            $stmt->close();
        }

        // Update approval_requests table
        $sqlApp = "UPDATE approval_requests SET status = 'approved', approved_by = ?, approved_at = ? WHERE source_table = 'project_incentive_claims' AND source_id = ?";
        $stmtApp = $this->db->prepare($sqlApp);
        if ($stmtApp) {
            $stmtApp->bind_param("isi", $approvedBy, $now, $claimId);
            $stmtApp->execute();
            $stmtApp->close();
        }

        return ['success' => true, 'message' => 'Claim approved successfully!'];
    }

    /**
     * Reject Claim
     */
    public function rejectClaim($rejectedBy, $claimId)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE project_incentive_claims SET claim_status = 'rejected' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $claimId);
            $stmt->execute();
            $stmt->close();
        }

        $sqlApp = "UPDATE approval_requests SET status = 'rejected', approved_by = ?, approved_at = ? WHERE source_table = 'project_incentive_claims' AND source_id = ?";
        $stmtApp = $this->db->prepare($sqlApp);
        if ($stmtApp) {
            $stmtApp->bind_param("isi", $rejectedBy, $now, $claimId);
            $stmtApp->execute();
            $stmtApp->close();
        }

        return ['success' => true, 'message' => 'Claim rejected.'];
    }

    /**
     * Disburse Payment
     */
    public function disbursePayment($disbursedBy, $claimId, $paymentAmount, $paymentMethod, $bankName, $transactionRef, $paymentDate, $notes)
    {
        // Fetch claim user_id and total incentive
        $sql = "SELECT user_id, incentive_amount FROM project_incentive_claims WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'DB Query error'];
        }

        $stmt->bind_param("i", $claimId);
        $stmt->execute();
        $claim = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$claim) {
            return ['success' => false, 'message' => 'Claim record not found'];
        }

        $userId = (int)$claim['user_id'];
        $totalIncentive = (float)$claim['incentive_amount'];
        $pAmount = (float)$paymentAmount;

        // Insert into project_incentive_payments
        $insSql = "INSERT INTO project_incentive_payments (claim_id, user_id, payment_amount, payment_method, transaction_ref, payment_date, bank_name, disbursed_by, notes)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insStmt = $this->db->prepare($insSql);
        if (!$insStmt) {
            return ['success' => false, 'message' => 'DB insert payment error'];
        }

        $insStmt->bind_param("iidssssis", $claimId, $userId, $pAmount, $paymentMethod, $transactionRef, $paymentDate, $bankName, $disbursedBy, $notes);
        $insStmt->execute();
        $insStmt->close();

        // Check total paid for this claim
        $sumSql = "SELECT SUM(payment_amount) AS total_paid FROM project_incentive_payments WHERE claim_id = ?";
        $sumStmt = $this->db->prepare($sumSql);
        $sumStmt->bind_param("i", $claimId);
        $sumStmt->execute();
        $paidResult = $sumStmt->get_result()->fetch_assoc();
        $sumStmt->close();

        $totalPaid = (float)($paidResult['total_paid'] ?? 0.00);

        $newStatus = ($totalPaid >= $totalIncentive) ? 'paid' : 'partially_paid';

        $updSql = "UPDATE project_incentive_claims SET claim_status = ? WHERE id = ?";
        $updStmt = $this->db->prepare($updSql);
        $updStmt->bind_param("si", $newStatus, $claimId);
        $updStmt->execute();
        $updStmt->close();

        return ['success' => true, 'message' => 'Payment disbursement recorded successfully into employee wallet!'];
    }

    /**
     * Get Employee Wallets for Accounts
     */
    public function getEmployeeWallets()
    {
        $sql = "SELECT 
                    u.id AS user_id,
                    u.full_name AS user_name,
                    IFNULL(e.employee_id, CONCAT('EMP-', u.id)) AS employee_id,
                    IFNULL(d.name, 'Sales') AS department,
                    CASE WHEN c.is_external = 1 THEN 'External Member' ELSE 'Sales Executive' END AS role_type,
                    SUM(CASE WHEN c.claim_status IN ('claimed', 'approved', 'partially_paid', 'paid') THEN c.incentive_amount ELSE 0 END) AS total_earned,
                    SUM(CASE WHEN c.claim_status IN ('approved', 'partially_paid', 'paid') THEN c.incentive_amount ELSE 0 END) AS approved_amt,
                    IFNULL((SELECT SUM(payment_amount) FROM project_incentive_payments WHERE user_id = u.id), 0.00) AS paid_amt
                FROM project_incentive_claims c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN departments d ON e.department_id = d.id
                GROUP BY u.id
                ORDER BY total_earned DESC";

        $res = $this->db->query($sql);
        $wallets = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $approved = (float)$row['approved_amt'];
                $paid = (float)$row['paid_amt'];
                $balance = max(0.00, $approved - $paid);

                $wallets[] = [
                    'employee_id'   => $row['employee_id'],
                    'user_name'     => $row['user_name'],
                    'department'    => $row['department'],
                    'role_type'     => $row['role_type'],
                    'total_earned'  => (float)$row['total_earned'],
                    'approved_amt'  => $approved,
                    'paid_amt'      => $paid,
                    'wallet_balance'=> $balance
                ];
            }
        }

        return $wallets;
    }

    /**
     * Get All Disbursement Logs
     */
    public function getDisbursementLogs()
    {
        $sql = "SELECT 
                    p.*,
                    u.full_name AS recipient_name,
                    IFNULL(e.employee_id, CONCAT('EMP-', u.id)) AS employee_id,
                    mgr.full_name AS disbursed_by_name
                FROM project_incentive_payments p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN users mgr ON p.disbursed_by = mgr.id
                ORDER BY p.id DESC";

        $res = $this->db->query($sql);
        $logs = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $logs[] = [
                    'id'              => (int)$row['id'],
                    'disbursement_no' => 'PAY-' . date('Y', strtotime($row['payment_date'])) . '-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
                    'recipient_name'  => $row['recipient_name'] . ' (' . $row['employee_id'] . ')',
                    'payment_amount'  => (float)$row['payment_amount'],
                    'payment_method'  => $row['payment_method'],
                    'bank_name'       => $row['bank_name'] ?? 'N/A',
                    'transaction_ref' => $row['transaction_ref'] ?? 'N/A',
                    'payment_date'    => $row['payment_date'],
                    'disbursed_by'    => $row['disbursed_by_name'] ?? 'Accounts Admin',
                    'notes'           => $row['notes'] ?? ''
                ];
            }
        }

        return $logs;
    }
}
