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
    public function getMySalesBookings($userId, $month = null)
    {
        $dateCondition = "";
        $params = [$userId, $userId, $userId, $userId];
        $types = "iiii";

        if (!empty($month)) {
            list($y, $m) = explode('-', $month);
            $cycle = $this->getCycleDateRange((int)$y, (int)$m);
            $startDate = substr($cycle['start'], 0, 10);
            $endDate = substr($cycle['end'], 0, 10);

            $tQ = $this->db->query("SELECT from_date, to_date FROM sales_targets WHERE sales_user_id = {$userId} AND DATE_FORMAT(month, '%Y-%m') = '{$month}' LIMIT 1");
            if ($tQ && $tRow = $tQ->fetch_assoc()) {
                if (!empty($tRow['from_date']) && !empty($tRow['to_date'])) {
                    $startDate = $tRow['from_date'];
                    $endDate = $tRow['to_date'];
                }
            }

            $dateCondition = " AND DATE(COALESCE(lb.booking_date, lb.created_at)) BETWEEN ? AND ? ";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        $sql = "SELECT 
                    lb.id AS booking_id,
                    lb.project_id,
                    lb.flat_id,
                    lb.lead_id,
                    COALESCE(lb.booking_date, lb.created_at) AS booking_date,
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
                       AND lp.payment_status IN ('paid', 'partially_paid')
                       AND COALESCE(lp.is_trashed, 0) = 0) AS total_collected_amount,
                    (SELECT IFNULL(SUM(lp.amount), 0.00) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                       AND lp.payment_type = 'booking_money' 
                       AND lp.verification_status = 'verified' 
                       AND lp.payment_status IN ('paid', 'partially_paid')
                       AND COALESCE(lp.is_trashed, 0) = 0) AS booking_money_amount,
                    c.id AS claim_id,
                    c.claim_status,
                    c.claimed_at,
                    c.approved_at,
                    c.approved_amount,
                    (SELECT IFNULL(SUM(pip.payment_amount), 0.00) FROM project_incentive_payments pip WHERE pip.claim_id = c.id) AS paid_amount,
                    c.rejected_by,
                    c.rejected_at,
                    c.rejection_note,
                    c.remarks,
                    c.is_received,
                    c.received_at,
                    c.received_by
                FROM lead_bookings lb
                LEFT JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN projects p ON lb.project_id = p.id
                LEFT JOIN project_categories pc ON p.category_id = pc.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                LEFT JOIN project_incentive_claims c ON (c.booking_id = lb.id AND c.user_id = ? AND c.is_external = 0)
                WHERE (CASE WHEN lb.sales_by_id IS NOT NULL AND lb.sales_by_id > 0 THEN lb.sales_by_id = ? ELSE (lb.created_by = ? OR l.assigned_id = ?) END)
                  AND lb.deleted_at IS NULL
                  AND lb.booking_status NOT IN ('cancelled', 'refunded')
                  {$dateCondition}
                ORDER BY lb.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $bookings = [];
        $monthlyStatsCache = [];

        while ($row = $result->fetch_assoc()) {
            $bookingId = (int)$row['booking_id'];
            $flatId = (int)$row['flat_id'];
            $bookingDate = $row['booking_date'];
            $yearMonth = $this->getCycleForDate($bookingDate);

            // Cache monthly stats to avoid redundant DB calls
            if (!isset($monthlyStatsCache[$yearMonth])) {
                $monthlyStatsCache[$yearMonth] = $this->engine->getExecutiveMonthlyStats($userId, $yearMonth);
            }
            $stats = $monthlyStatsCache[$yearMonth];

            // Look up detailed booking from engine stats
            $detail = null;
            if (!empty($stats['detailed_bookings'])) {
                foreach ($stats['detailed_bookings'] as $dbk) {
                    if ((int)$dbk['booking_id'] === $bookingId) {
                        $detail = $dbk;
                        break;
                    }
                }
            }

            // Check if Booking Money is paid & verified
            $bookingMoneyVerified = $this->isPaymentVerified($bookingId, 'booking_money');

            // Check if Registration is paid & verified
            $registrationVerified = $this->isPaymentVerified($bookingId, 'registration');

            // Values
            $flatPrice = (float)$row['flat_price'];
            $landShareValue = (float)$row['land_share_value'];

            $applicablePct = $detail ? (float)$detail['applicable_rate'] : 0.70;
            $calculatedIncentive = $detail ? (float)$detail['calculated_incentive'] : 0.00;
            $rateType = $detail ? $detail['rate_type'] : 'Standard Flat (0.70%)';
            $isOnHold = $detail ? (int)($detail['is_on_hold'] ?? 0) : 0;
            $alreadyClaimed = $detail ? !empty($detail['already_claimed']) : $this->engine->isFlatIncentiveClaimed($flatId, $bookingId);
            $isAssigned = $detail ? !empty($detail['is_assigned']) : true;

            // Claim status logic
            $claimStatus = $row['claim_status'];
            $statusRemarks = $row['remarks'] ?? '';

            if (empty($claimStatus)) {
                if ($alreadyClaimed) {
                    $claimStatus = 'ineligible';
                    $statusRemarks = 'Duplicate flat: Incentive already claimed for this flat unit.';
                    $calculatedIncentive = 0.00;
                    $applicablePct = 0.00;
                } elseif (!$stats['is_eligible']) {
                    $claimStatus = 'ineligible';
                    $statusRemarks = 'Target or minimum 25 Lakh sales not achieved.';
                } else {
                    $claimStatus = 'eligible';
                    if (!$registrationVerified) {
                        $statusRemarks = 'Booking Money verified. Registration pending.';
                    }
                }
            } elseif ($claimStatus === 'rejected') {
                // Keep rejected
            } elseif ($alreadyClaimed && empty($row['claim_id'])) {
                $claimStatus = 'ineligible';
                $statusRemarks = 'Duplicate flat: Incentive already claimed for this flat unit.';
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
                'category_name'          => $rateType,
                'incentive_percentage'   => $applicablePct,
                'calculated_incentive'   => $calculatedIncentive,
                'is_assigned'            => $isAssigned,
                'project_scope'          => $detail['project_scope'] ?? ($isAssigned ? 'assigned' : 'unassigned'),
                'floor_name'             => $detail['floor_name'] ?? 'General Flat',
                'base_unit_rate'         => $detail['base_unit_rate'] ?? $applicablePct,
                'seller_share_pct'       => $detail['seller_share_pct'] ?? ($isAssigned ? 100.0 : 70.0),
                'seller_share_rate'      => $detail['seller_share_rate'] ?? ($isAssigned ? $applicablePct : round($applicablePct, 4)),
                'designated_share_rate'  => $detail['designated_share_rate'] ?? ($isAssigned ? 0.0 : round(($detail['base_unit_rate'] ?? 0.80) * 0.30, 4)),
                'seller_incentive'       => $detail['seller_incentive'] ?? $calculatedIncentive,
                'designated_incentive'   => $detail['designated_incentive'] ?? 0.0,
                'designated_user_name'   => $detail['designated_user_name'] ?? null,
                'has_designated_holder'  => !empty($detail['has_designated_holder']) || (!empty($detail['designated_user_name']) && $detail['designated_user_name'] !== 'Unassigned / Open'),
                'booking_completed'      => $bookingMoneyVerified ? 1 : 0,
                'registration_completed' => $registrationVerified ? 1 : 0,
                'claim_status'           => $claimStatus,
                'is_on_hold'             => $isOnHold,
                'already_claimed'        => $alreadyClaimed ? 1 : 0,
                'claim_id'               => (int)($row['claim_id'] ?? 0),
                'claimed_at'             => $row['claimed_at'],
                'approved_at'            => $row['approved_at'],
                'approved_amount'        => isset($row['approved_amount']) && $row['approved_amount'] !== null ? (float)$row['approved_amount'] : null,
                'paid_amount'            => (float)($row['paid_amount'] ?? 0.0),
                'rejected_by'            => (int)($row['rejected_by'] ?? 0),
                'rejected_at'            => $row['rejected_at'] ?? null,
                'rejection_note'         => $row['rejection_note'] ?? null,
                'remarks'                => !empty($statusRemarks) ? $statusRemarks : ($row['remarks'] ?? ''),
                'is_received'            => (int)($row['is_received'] ?? 0),
                'received_at'            => $row['received_at'] ?? null,
                'received_by'            => $row['received_by'] ?? null
            ];
        }

        // Note: 30% designated credits & 70% outside sales are managed in Tab 4 to avoid duplicate claiming confusion

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
                    WHERE (lb.sales_by_id = ? OR lb.created_by = ? OR l.assigned_id = ?)
                      AND lp.verification_status = 'verified'
                      AND lp.payment_status IN ('paid', 'partially_paid')
                      AND lb.deleted_at IS NULL";

        $stmtColl = $this->db->prepare($sqlColl);
        $totalSalesCollected = 0.00;
        if ($stmtColl) {
            $stmtColl->bind_param("iii", $userId, $userId, $userId);
            $stmtColl->execute();
            $rowColl = $stmtColl->get_result()->fetch_assoc();
            $totalSalesCollected = (float)($rowColl['total_sales_collected'] ?? 0.00);
            $stmtColl->close();
        }

        $sql = "SELECT 
                    SUM(CASE WHEN claim_status IN ('claimed', 'approved', 'partially_paid', 'paid') THEN COALESCE(approved_amount, incentive_amount) ELSE 0 END) AS total_earned,
                    SUM(CASE WHEN claim_status = 'claimed' THEN incentive_amount ELSE 0 END) AS pending_claims,
                    SUM(CASE WHEN claim_status IN ('approved', 'partially_paid', 'paid') THEN COALESCE(approved_amount, incentive_amount) ELSE 0 END) AS approved_amount,
                    SUM(CASE WHEN claim_status = 'rejected' THEN incentive_amount ELSE 0 END) AS rejected_amount,
                    SUM(CASE WHEN claim_status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
                FROM project_incentive_claims
                WHERE user_id = ?";

        $stmt = $this->db->prepare($sql);
        $totalEarned = 0.00;
        $pendingClaims = 0.00;
        $approvedAmount = 0.00;
        $rejectedAmount = 0.00;
        $rejectedCount = 0;

        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $totalEarned = (float)($row['total_earned'] ?? 0.00);
            $pendingClaims = (float)($row['pending_claims'] ?? 0.00);
            $approvedAmount = (float)($row['approved_amount'] ?? 0.00);
            $rejectedAmount = (float)($row['rejected_amount'] ?? 0.00);
            $rejectedCount = (int)($row['rejected_count'] ?? 0);
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
            'total_approved'        => $approvedAmount,
            'rejected_amount'       => $rejectedAmount,
            'rejected_count'        => $rejectedCount,
            'disbursed_paid'        => $disbursedPaid,
            'total_paid'            => $disbursedPaid,
            'available_balance'     => $availableBalance
        ];
    }

    /**
     * Submit an Incentive Claim for a Sales Booking
     */
    public function claimIncentive($userId, $bookingId, $remarks = '')
    {
        // 1. Fetch booking details
        $sql = "SELECT 
                    lb.id, lb.project_id, lb.flat_id, lb.booking_date,
                    p.name AS project_name,
                    p.building_type,
                    f.flat_name,
                    f.floor_no,
                    IFNULL(f.land_share_value, 0.00) AS land_share_value,
                    IFNULL(f.price_value, 0.00) AS flat_price
                FROM lead_bookings lb
                LEFT JOIN projects p ON lb.project_id = p.id
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

        $flatId = (int)$booking['flat_id'];

        // 2. ONE-TIME FLAT INCENTIVE RULE: Check if flat was already claimed/approved in another booking
        $existingClaim = $this->engine->getFlatClaimDetails($flatId, $bookingId);
        if ($existingClaim) {
            return [
                'success' => false, 
                'message' => 'Claim ineligible: This flat unit (' . ($booking['flat_name'] ?? 'Flat') . ') has already received/claimed an incentive (Claim #' . $existingClaim['id'] . '). Each flat can only receive an incentive once.'
            ];
        }

                // 3. Payment conditions: Allow claim if booking money is submitted or verified
        $bMoney = $this->isPaymentVerified($bookingId, 'booking_money');
        $reg = $this->isPaymentVerified($bookingId, 'registration');

        $pCheck = $this->db->query("SELECT COUNT(*) AS total FROM lead_payments WHERE booking_id = {$bookingId} AND COALESCE(is_trashed, 0) = 0");
        $hasPaymentSubmitted = ($pCheck && ($pCheck->fetch_assoc()['total'] ?? 0) > 0);

        if (!$bMoney && !$hasPaymentSubmitted && (float)$booking['land_share_value'] < 2500000) {
            return ['success' => false, 'message' => 'Claim ineligible: Booking payment must be submitted to submit an incentive claim.'];
        }

        // 4. Calculate incentive from Engine using Month Cycle
        $yearMonth = $this->getCycleForDate($booking['booking_date']);
        $stats = $this->engine->getExecutiveMonthlyStats($userId, $yearMonth);

        $detail = null;
        if (!empty($stats['detailed_bookings'])) {
            foreach ($stats['detailed_bookings'] as $dbk) {
                if ((int)$dbk['booking_id'] === $bookingId) {
                    $detail = $dbk;
                    break;
                }
            }
        }

        if (!$detail) {
            return ['success' => false, 'message' => 'Calculation error: Booking not found in current monthly cycle.'];
        }

        if (!empty($detail['already_claimed'])) {
            return ['success' => false, 'message' => 'Claim denied: This flat unit has already received or claimed sales incentive.'];
        }

        $isAssigned = !empty($detail['is_assigned']);
        if ($isAssigned && empty($stats['is_eligible'])) {
            return [
                'success' => false, 
                'message' => 'Claim ineligible: You must achieve your assigned target or minimum eligible sales of 25 Lakh before claiming incentives.'
            ];
        }

        $saleAmount = (float)$booking['flat_price'];
        $landShareValue = (float)$booking['land_share_value'];
        $applicablePct = (float)$detail['applicable_rate'];
        $incentiveAmount = (float)$detail['calculated_incentive'];
        $isOnHold = (int)($detail['is_on_hold'] ?? 0);
        $splitType = $isAssigned ? 'direct' : 'seller_70';

        // Check if claim already exists for this booking by this user
        $chkSql = "SELECT id FROM project_incentive_claims WHERE booking_id = ? AND user_id = ? AND is_external = 0 LIMIT 1";
        $chkStmt = $this->db->prepare($chkSql);
        $chkStmt->bind_param("ii", $bookingId, $userId);
        $chkStmt->execute();
        $existing = $chkStmt->get_result()->fetch_assoc();
        $chkStmt->close();

        $now = date('Y-m-d H:i:s');
        $bComp = $bMoney ? 1 : 0;
        $rComp = $reg ? 1 : 0;
        if ($existing) {
            $claimId = (int)$existing['id'];
            $updSql = "UPDATE project_incentive_claims 
                       SET flat_id = ?, claim_status = 'claimed', claimed_at = ?, remarks = ?, 
                           sale_amount = ?, land_share_value = ?, incentive_percentage = ?, incentive_amount = ?,
                           split_type = ?, is_on_hold = ?, booking_completed = ?, registration_completed = ?
                       WHERE id = ?";
            $updStmt = $this->db->prepare($updSql);
            $updStmt->bind_param("issddddsiiii", $flatId, $now, $remarks, $saleAmount, $landShareValue, $applicablePct, $incentiveAmount, $splitType, $isOnHold, $bComp, $rComp, $claimId);
            $updStmt->execute();
            $updStmt->close();
        } else {
            $insSql = "INSERT INTO project_incentive_claims 
                       (booking_id, project_id, flat_id, user_id, is_external, split_type, is_on_hold, sale_amount, land_share_value, incentive_percentage, incentive_amount, booking_completed, registration_completed, claim_status, claimed_at, remarks) 
                       VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, 'claimed', ?, ?)";
            $insStmt = $this->db->prepare($insSql);
            $insStmt->bind_param("iiiisdddddiiss", $bookingId, $booking['project_id'], $flatId, $userId, $splitType, $isOnHold, $saleAmount, $landShareValue, $applicablePct, $incentiveAmount, $bComp, $rComp, $now, $remarks);
            $insStmt->execute();
            $claimId = $insStmt->insert_id;
            $insStmt->close();
        }

        // If outside designated flat (seller_70) and designated agent exists:
        if ($splitType === 'seller_70' && !empty($detail['designated_user_id']) && (float)$detail['designated_incentive'] > 0) {
            $desUserId = (int)$detail['designated_user_id'];
            $desIncentive = (float)$detail['designated_incentive'];
            $desPct = $applicablePct * 0.30;

            // Check if designated claim already exists
            $chkDes = $this->db->query("SELECT id FROM project_incentive_claims WHERE booking_id = {$bookingId} AND user_id = {$desUserId} AND split_type = 'designated_30'");
            if ($chkDes && $chkDes->num_rows == 0) {
                $insDes = "INSERT INTO project_incentive_claims 
                           (booking_id, project_id, flat_id, user_id, is_external, split_type, is_on_hold, sale_amount, land_share_value, incentive_percentage, incentive_amount, booking_completed, registration_completed, claim_status, claimed_at, remarks)
                           VALUES (?, ?, ?, ?, 0, 'designated_30', 0, ?, ?, ?, ?, ?, ?, 'claimed', ?, 'Designated agent 30% share')";
                $desStmt = $this->db->prepare($insDes);
                if ($desStmt) {
                    $desStmt->bind_param("iiiiddddiis", $bookingId, $booking['project_id'], $flatId, $desUserId, $saleAmount, $landShareValue, $desPct, $desIncentive, $bComp, $rComp, $now);
                    $desStmt->execute();
                    $desStmt->close();
                }
            }
        }

        // Add Approval Request entry for Home Page & approval_requests queue
        $this->createApprovalRequest($userId, $claimId, $bookingId, $incentiveAmount);

        $msg = 'Incentive claim submitted successfully and sent for Management approval.';
        if ($isOnHold) {
            $msg .= ' Note: Because this was outside your assigned flats, your 70% share is ON HOLD until you achieve your assigned target or minimum 25 Lakh sales.';
        }
        return ['success' => true, 'message' => $msg];
    }

    /**
     * Create approval request in approval_requests table
     */
    private function createApprovalRequest($userId, $claimId, $bookingId, $amount)
    {
        $bookingCode = 'BK-' . str_pad($bookingId, 5, '0', STR_PAD_LEFT);
        $title = "Sales Incentive Claim for Booking #{$bookingCode}";

        $sql = "INSERT INTO approval_requests 
                (department, request_type, title, source_table, source_id, request_amount, status, requested_by, approve_role, sales_head_status, accounts_head_status, created_at)
                VALUES ('Sales', 'Incentive Claim', ?, 'project_incentive_claims', ?, ?, 'pending', ?, 'sales_head', 'pending', 'pending', NOW())";

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
                    SUM(CASE WHEN claim_status IN ('approved', 'partially_paid', 'paid') THEN COALESCE(approved_amount, incentive_amount) ELSE 0 END) AS total_approved,
                    SUM(CASE WHEN claim_status = 'rejected' THEN incentive_amount ELSE 0 END) AS total_rejected,
                    SUM(CASE WHEN claim_status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                    SUM(CASE WHEN is_external = 1 THEN 1 ELSE 0 END) AS external_count
                FROM project_incentive_claims";
        
        if ($month) {
            list($cy, $cm) = explode('-', $month);
            $cycle = $this->getCycleDateRange((int)$cy, (int)$cm);
            $sql .= " WHERE created_at BETWEEN '{$cycle['start']}' AND '{$cycle['end']}'";
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
            'total_rejected'        => (float)($row['total_rejected'] ?? 0.00),
            'rejected_count'        => (int)($row['rejected_count'] ?? 0),
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
                    lb.booking_date,
                    c.*,
                    u.full_name AS user_name,
                    IFNULL(e.employee_id, CONCAT('EMP-', u.id)) AS employee_id,
                    IFNULL(d.name, 'Sales') AS department,
                    IFNULL(des.name, 'Executive') AS designation,
                    CASE 
                        WHEN c.split_type = 'tl_override' THEN 'TL 0.05% Override'
                        ELSE IFNULL(p.name, 'N/A')
                    END AS project_name,
                    CASE 
                        WHEN c.split_type = 'tl_override' THEN CONCAT('From: ', IFNULL(u_orig.full_name, 'Team Member'))
                        WHEN c.split_type = 'designated_30' THEN CONCAT(IFNULL(f.flat_name, 'N/A'), ' (30% Designated)')
                        ELSE IFNULL(f.flat_name, 'N/A')
                    END AS flat_name,
                    COALESCE(c.land_share_value, f.land_share_value, 0.00) AS land_share_value,
                    IFNULL(e.bank_name, '') AS emp_bank_name,
                    IFNULL(e.account_number, '') AS emp_account_number,
                    (SELECT SUM(payment_amount) FROM project_incentive_payments WHERE claim_id = c.id) AS total_paid,
                    ar.id AS ar_id,
                    ar.status AS ar_status,
                    ar.accounts_head_status,
                    ar.accounts_head_at,
                    ar.sales_head_status,
                    ar.sales_head_at,
                    ar.approve_role,
                    COALESCE(u_sh.full_name, 'Sales Team Head') AS sh_approver_name,
                    e_sh.employee_id AS sh_approver_code,
                    c.sales_head_status AS claim_sh_status,
                    c.accounts_head_status AS claim_ah_status,
                    c.mgmt_status AS claim_mgmt_status,
                    COALESCE(ar.op_head_at, ar.approved_at, c.approved_at) AS mgmt_approved_at,
                    COALESCE(u_mgmt.full_name, 'Management') AS mgmt_approver_name,
                    e_mgmt.employee_id AS mgmt_approver_code,
                    COALESCE(r_mgmt.name, 'Management') AS mgmt_approver_role,
                    u_ah.full_name AS ah_approver_name,
                    e_ah.employee_id AS ah_approver_code,
                    u_rej.full_name AS rejected_by_name,
                    e_rej.employee_id AS rejected_by_code,
                    (
                        SELECT lp.verification_status 
                        FROM lead_payments lp 
                        WHERE lp.booking_id = c.booking_id 
                          AND lp.payment_type = 'booking_money' 
                          AND COALESCE(lp.is_trashed, 0) = 0 
                        ORDER BY CASE WHEN lp.verification_status = 'verified' THEN 1 WHEN lp.verification_status = 'pending' THEN 2 ELSE 3 END, lp.id DESC 
                        LIMIT 1
                    ) AS booking_verification_status,
                    (
                        SELECT lp.verification_status 
                        FROM lead_payments lp 
                        WHERE lp.booking_id = c.booking_id 
                          AND lp.payment_type = 'registration' 
                          AND COALESCE(lp.is_trashed, 0) = 0 
                        ORDER BY CASE WHEN lp.verification_status = 'verified' THEN 1 WHEN lp.verification_status = 'pending' THEN 2 ELSE 3 END, lp.id DESC 
                        LIMIT 1
                    ) AS reg_verification_status
                FROM project_incentive_claims c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users u_orig ON c.original_sales_by = u_orig.id
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN projects p ON c.project_id = p.id
                LEFT JOIN flats f ON c.flat_id = f.id
                LEFT JOIN lead_bookings lb ON c.booking_id = lb.id
                LEFT JOIN approval_requests ar ON ar.source_table = 'project_incentive_claims' AND ar.source_id = c.id
                LEFT JOIN users u_sh ON COALESCE(ar.sales_head_by, c.sales_head_by) = u_sh.id
                LEFT JOIN employees e_sh ON u_sh.id = e_sh.user_id
                LEFT JOIN users u_mgmt ON COALESCE(ar.op_head_by, ar.approved_by, c.approved_by) = u_mgmt.id
                LEFT JOIN employees e_mgmt ON u_mgmt.id = e_mgmt.user_id
                LEFT JOIN roles r_mgmt ON u_mgmt.role_id = r_mgmt.id
                LEFT JOIN users u_ah ON ar.accounts_head_by = u_ah.id
                LEFT JOIN employees e_ah ON u_ah.id = e_ah.user_id
                LEFT JOIN users u_rej ON c.rejected_by = u_rej.id
                LEFT JOIN employees e_rej ON u_rej.id = e_rej.user_id
                WHERE c.is_external = 0";

        if ($month) {
            list($y, $m) = explode('-', $month);
            $cycle = $this->getCycleDateRange($y, $m);
            $sql .= " AND c.created_at BETWEEN '{$cycle['start']}' AND '{$cycle['end']}'";
        }
        
        $sql .= " ORDER BY c.id DESC";

        $res = $this->db->query($sql);
        $queue = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $incAmt = (float)$row['incentive_amount'];
                $appAmt = (!empty($row['approved_amount']) && (float)$row['approved_amount'] > 0) ? (float)$row['approved_amount'] : null;
                $effectiveAmt = ($appAmt !== null) ? $appAmt : $incAmt;
                $paidAmt = (float)($row['total_paid'] ?? 0.00);
                $dueAmt = max(0.00, $effectiveAmt - $paidAmt);

                $claimId = (int)$row['id'];
                $claimCode = 'INC-' . date('Y', strtotime($row['created_at'])) . '-' . str_pad($claimId, 3, '0', STR_PAD_LEFT);

                $bVerifStatus = $row['booking_verification_status'] ?? null;
                $rVerifStatus = $row['reg_verification_status'] ?? null;

                $mgmtApprovedAt = !empty($row['mgmt_approved_at']) ? $row['mgmt_approved_at'] : null;
                $mgmtApproverName = $mgmtApprovedAt ? ($row['mgmt_approver_name'] ?? 'Management') : null;
                $mgmtApproverCode = $mgmtApprovedAt ? ($row['mgmt_approver_code'] ?? null) : null;
                $mgmtApproverRole = $mgmtApprovedAt ? ($row['mgmt_approver_role'] ?? null) : null;

                $ahApprovedAt = !empty($row['accounts_head_at']) ? $row['accounts_head_at'] : null;
                $ahApproverName = $ahApprovedAt ? ($row['ah_approver_name'] ?? null) : null;
                $ahApproverCode = $ahApprovedAt ? ($row['ah_approver_code'] ?? null) : null;

                $queue[] = [
                    'id'                        => $claimId,
                    'claim_code'                => $claimCode,
                    'user_id'                   => (int)$row['user_id'],
                    'user_name'                 => $row['user_name'] ?? 'Sales Executive',
                    'employee_id'               => $row['employee_id'] ?? ('EMP-' . $row['user_id']),
                    'department'                => $row['department'] ?? 'Sales',
                    'designation'               => $row['designation'] ?? 'Executive',
                    'is_external'               => 0,
                    'split_type'                => $row['split_type'] ?? 'direct',
                    'is_on_hold'                => (int)($row['is_on_hold'] ?? 0),
                    'project_name'              => $row['project_name'] ?? 'N/A',
                    'flat_name'                 => $row['flat_name'] ?? 'N/A',
                    'sale_amount'               => (float)$row['sale_amount'],
                    'land_share_value'          => (float)$row['land_share_value'],
                    'incentive_percentage'      => (float)$row['incentive_percentage'],
                    'incentive_amount'          => $incAmt,
                    'approved_amount'           => $appAmt,
                    'effective_amount'          => $effectiveAmt,
                    'paid_amount'               => $paidAmt,
                    'due_amount'                => $dueAmt,
                    'booking_completed'         => ($bVerifStatus === 'verified') ? 1 : (int)($row['booking_completed'] ?? 0),
                    'registration_completed'    => ($rVerifStatus === 'verified') ? 1 : (int)($row['registration_completed'] ?? 0),
                    'booking_verification_status' => $bVerifStatus,
                    'reg_verification_status'   => $rVerifStatus,
                    'sales_head_status'         => !empty($row['claim_sh_status']) && $row['claim_sh_status'] !== 'pending' ? $row['claim_sh_status'] : (!empty($row['sales_head_status']) ? $row['sales_head_status'] : 'pending'),
                    'sales_head_at'             => !empty($row['sales_head_at']) ? $row['sales_head_at'] : null,
                    'sh_approver_name'          => !empty($row['sales_head_at']) ? ($row['sh_approver_name'] ?? 'Sales Team Head') : null,
                    'sh_approver_code'          => !empty($row['sales_head_at']) ? ($row['sh_approver_code'] ?? null) : null,
                    'accounts_head_status'      => !empty($row['claim_ah_status']) && $row['claim_ah_status'] !== 'pending' ? $row['claim_ah_status'] : (!empty($row['accounts_head_status']) ? $row['accounts_head_status'] : 'pending'),
                    'approve_role'              => $row['approve_role'] ?? 'sales_head',
                    'mgmt_approved_at'          => $mgmtApprovedAt,
                    'mgmt_approver_name'        => $mgmtApproverName,
                    'mgmt_approver_code'        => $mgmtApproverCode,
                    'mgmt_approver_role'        => $mgmtApproverRole,
                    'ah_approved_at'            => $ahApprovedAt,
                    'ah_approver_name'          => $ahApproverName,
                    'ah_approver_code'          => $ahApproverCode,
                    'rejected_by_name'          => !empty($row['rejected_at']) ? ($row['rejected_by_name'] ?? 'Approver') : null,
                    'rejected_by_code'          => !empty($row['rejected_at']) ? ($row['rejected_by_code'] ?? null) : null,
                    'rejected_at'               => $row['rejected_at'] ?? null,
                    'rejection_note'            => $row['rejection_note'] ?? null,
                    'claim_status'              => $row['claim_status'],
                    'claimed_at'                => $row['claimed_at'] ?? $row['created_at'],
                    'remarks'                   => $row['remarks'] ?? '',
                    'account_id'                => (int)($row['account_id'] ?? 0),
                    'emp_bank_name'             => $row['emp_bank_name'] ?? '',
                    'emp_account_number'        => $row['emp_account_number'] ?? '',
                    'is_received'               => (int)($row['is_received'] ?? 0),
                    'received_at'               => $row['received_at'] ?? null,
                    'received_by'               => $row['received_by'] ?? null,
                    'booking_date'              => $row['booking_date'] ?? null,
                    'cycle_month'               => $row['cycle_month'] ?? null
                ];
            }
        }

        return $queue;
    }

    /**
     * Get Real-Time Sales Incentive Stats for the Month (Target vs Achievement)
     */
    public function getRealTimeIncentivesByMonth($month, $fromDate = null, $toDate = null)
    {
        $stats = [];
        
        // Find all users who have an active or inherited target on record
        $sql = "SELECT DISTINCT u.id, u.full_name, e.employee_id, d.name AS department, des.name AS designation, 
                        e.team_id, t.name as team_name, e.is_team_leader 
                FROM users u
                JOIN employees e ON u.id = e.user_id
                LEFT JOIN teams t ON e.team_id = t.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE u.id IN (
                    SELECT sales_user_id FROM sales_targets WHERE DATE_FORMAT(month, '%Y-%m') <= ? AND booking_value_target > 0
                )
                ORDER BY e.team_id ASC, u.full_name ASC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $month);
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        list($cy, $cm) = explode('-', $month);
        $cycleRange = $this->getCycleDateRange($cy, $cm);
        $cStart = substr($cycleRange['start'], 0, 10);
        $cEnd   = substr($cycleRange['end'], 0, 10);
        
        foreach ($users as $u) {
            $userId = (int)$u['id'];
            $ind = $this->engine->getExecutiveMonthlyStats($userId, $month, $fromDate, $toDate);
            
            // Only fetch team stats if this user is a Team Leader with their explicit team_id
            $team = null;
            if (!empty($u['is_team_leader']) && !empty($u['team_id'])) {
                $team = $this->engine->getTeamMonthlyStats($userId, $month, $u['team_id'], $fromDate, $toDate);
            }

            // Cumulative all-time and cycle claims breakdown
            $claimsQ = $this->db->query("
                SELECT 
                    COUNT(*) AS total_claims_count,
                    IFNULL(SUM(incentive_amount), 0.00) AS total_claimed_amount,
                    IFNULL(SUM(CASE WHEN claim_status IN ('approved', 'partially_paid', 'paid') THEN COALESCE(approved_amount, incentive_amount) ELSE 0 END), 0.00) AS total_approved_amount,
                    IFNULL(SUM(CASE WHEN claim_status = 'claimed' THEN incentive_amount ELSE 0 END), 0.00) AS pending_claim_amount,
                    IFNULL(SUM(CASE WHEN claim_status = 'claimed' THEN 1 ELSE 0 END), 0) AS pending_claims_count,
                    IFNULL(SUM(CASE WHEN claim_status = 'rejected' THEN incentive_amount ELSE 0 END), 0.00) AS rejected_claim_amount,
                    IFNULL(SUM(CASE WHEN claim_status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_claims_count
                FROM project_incentive_claims
                WHERE user_id = {$userId}
            ");
            $claimsSum = $claimsQ ? $claimsQ->fetch_assoc() : [];

            $paidQ = $this->db->query("
                SELECT IFNULL(SUM(payment_amount), 0.00) AS total_paid_amount
                FROM project_incentive_payments
                WHERE user_id = {$userId}
            ");
            $paidSum = $paidQ ? $paidQ->fetch_assoc() : [];
            $totalPaid = (float)($paidSum['total_paid_amount'] ?? 0.00);
            $totalApproved = (float)($claimsSum['total_approved_amount'] ?? 0.00);
            $walletBalance = max(0.00, $totalApproved - $totalPaid);

            // Cycle-specific claims
            $cycleClaimsQ = $this->db->query("
                SELECT 
                    COUNT(*) AS cycle_claims_count,
                    IFNULL(SUM(incentive_amount), 0.00) AS cycle_claimed_amount
                FROM project_incentive_claims
                WHERE user_id = {$userId}
                  AND claimed_at BETWEEN '{$cStart} 00:00:00' AND '{$cEnd} 23:59:59'
            ");
            $cycleClaims = $cycleClaimsQ ? $cycleClaimsQ->fetch_assoc() : [];
            
            $stats[] = [
                'user'          => $u,
                'individual'    => $ind,
                'team'          => $team,
                'claims_summary' => [
                    'cycle_claims_count'    => (int)($cycleClaims['cycle_claims_count'] ?? 0),
                    'cycle_claimed_amount'   => (float)($cycleClaims['cycle_claimed_amount'] ?? 0.00),
                    'total_claims_count'    => (int)($claimsSum['total_claims_count'] ?? 0),
                    'total_claimed_amount'  => (float)($claimsSum['total_claimed_amount'] ?? 0.00),
                    'total_approved_amount' => $totalApproved,
                    'total_paid_amount'     => $totalPaid,
                    'pending_claim_amount'  => (float)($claimsSum['pending_claim_amount'] ?? 0.00),
                    'wallet_balance'        => $walletBalance,
                ]
            ];
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

        list($y, $m) = explode('-', $month);
        $cycle = $this->getCycleDateRange($y, $m);
        $startDate = substr($cycle['start'], 0, 10);
        $endDate   = substr($cycle['end'], 0, 10);

        // If sales head (role 9) or admin (1, 3) or management (6)
        if ($roleId == 1 || $roleId == 3 || $roleId == 6 || $roleId == 9) {
            if (!empty($filterTeamId) && (int)$filterTeamId > 0) {
                $sql = "SELECT 
                            lb.id, lb.booking_date, 
                            u.full_name as sales_person, 
                            p.name as project_name, 
                            f.flat_name, 
                            IFNULL(f.land_share_value, 0.00) as land_share_value,
                            (SELECT IFNULL(SUM(lp.amount), 0.00) 
                             FROM lead_payments lp 
                             WHERE lp.booking_id = lb.id 
                               AND lp.verification_status = 'verified' 
                               AND lp.payment_status IN ('paid', 'partially_paid')
                               AND COALESCE(lp.is_trashed, 0) = 0) AS total_collected,
                            lb.booking_status
                        FROM lead_bookings lb
                        JOIN users u ON lb.sales_by_id = u.id
                        JOIN projects p ON lb.project_id = p.id
                        JOIN flats f ON lb.flat_id = f.id
                        WHERE u.id IN (SELECT user_id FROM employees WHERE team_id = ?)
                        AND lb.booking_date BETWEEN ? AND ?
                        AND lb.deleted_at IS NULL
                        AND lb.booking_status NOT IN ('cancelled', 'refunded')
                        ORDER BY lb.booking_date DESC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("iss", $filterTeamId, $startDate, $endDate);
            } else {
                $sql = "SELECT 
                            lb.id, lb.booking_date, 
                            u.full_name as sales_person, 
                            p.name as project_name, 
                            f.flat_name, 
                            IFNULL(f.land_share_value, 0.00) as land_share_value,
                            (SELECT IFNULL(SUM(lp.amount), 0.00) 
                             FROM lead_payments lp 
                             WHERE lp.booking_id = lb.id 
                               AND lp.verification_status = 'verified' 
                               AND lp.payment_status IN ('paid', 'partially_paid')
                               AND COALESCE(lp.is_trashed, 0) = 0) AS total_collected,
                            lb.booking_status
                        FROM lead_bookings lb
                        JOIN users u ON lb.sales_by_id = u.id
                        JOIN projects p ON lb.project_id = p.id
                        JOIN flats f ON lb.flat_id = f.id
                        WHERE lb.booking_date BETWEEN ? AND ?
                        AND lb.deleted_at IS NULL
                        AND lb.booking_status NOT IN ('cancelled', 'refunded')
                        ORDER BY lb.booking_date DESC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ss", $startDate, $endDate);
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
                        (SELECT IFNULL(SUM(lp.amount), 0.00) 
                         FROM lead_payments lp 
                         WHERE lp.booking_id = lb.id 
                           AND lp.verification_status = 'verified' 
                           AND lp.payment_status IN ('paid', 'partially_paid')
                           AND COALESCE(lp.is_trashed, 0) = 0) AS total_collected,
                        lb.booking_status
                    FROM lead_bookings lb
                    JOIN users u ON lb.sales_by_id = u.id
                    JOIN projects p ON lb.project_id = p.id
                    JOIN flats f ON lb.flat_id = f.id
                    WHERE u.id IN (SELECT user_id FROM employees WHERE team_id = ?)
                    AND lb.booking_date BETWEEN ? AND ?
                    AND lb.deleted_at IS NULL
                    AND lb.booking_status NOT IN ('cancelled', 'refunded')
                    ORDER BY lb.booking_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iss", $effectiveTeamId, $startDate, $endDate);
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
                    lb.booking_date,
                    c.*,
                    u.full_name AS user_name,
                    e.employee_id,
                    d.name AS department,
                    CASE 
                        WHEN c.split_type = 'tl_override' THEN 'TL 0.05% Override'
                        ELSE IFNULL(p.name, 'N/A')
                    END AS project_name,
                    CASE 
                        WHEN c.split_type = 'tl_override' THEN CONCAT('From: ', IFNULL(u_orig.full_name, 'Team Member'))
                        WHEN c.split_type = 'designated_30' THEN CONCAT(IFNULL(f.flat_name, 'N/A'), ' (30% Designated)')
                        ELSE IFNULL(f.flat_name, 'N/A')
                    END AS flat_name,
                    COALESCE(c.land_share_value, f.land_share_value, 0.00) AS land_share_value,
                    IFNULL(e.bank_name, '') AS emp_bank_name,
                    IFNULL(e.account_number, '') AS emp_account_number,
                    mgr.full_name AS assigned_by_name,
                    (SELECT SUM(payment_amount) FROM project_incentive_payments WHERE claim_id = c.id) AS total_paid
                FROM project_incentive_claims c
                LEFT JOIN lead_bookings lb ON c.booking_id = lb.id
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users u_orig ON c.original_sales_by = u_orig.id
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
                    'due_amount'            => max(0.00, $incAmt - $paidAmt),
                    'claim_status'          => $row['claim_status'],
                    'remarks'               => $row['remarks'] ?? '',
                    'account_id'            => (int)($row['account_id'] ?? 0),
                    'emp_bank_name'         => $row['emp_bank_name'] ?? '',
                    'emp_account_number'    => $row['emp_account_number'] ?? ''
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
                    'sqft'             => (float)$row['sqft'],
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
     * Approve Sales Team Head Stage (Stage 1)
     */
    public function approveSalesHead($approvedBy, $claimId, $note = 'Approved by Sales Team Head', $approvedAmount = null)
    {
        $claimId = (int)$claimId;
        $approvedBy = (int)$approvedBy;
        $now = date('Y-m-d H:i:s');
        $escNote = $this->db->real_escape_string($note);

        $appAmtSql = "";
        $appAmtReqSql = "";
        if ($approvedAmount !== null && (float)$approvedAmount > 0) {
            $fAmt = (float)$approvedAmount;
            $appAmtSql = ", approved_amount = {$fAmt}";
            $appAmtReqSql = ", approved_amount = {$fAmt}";
        } else {
            $appAmtSql = ", approved_amount = COALESCE(approved_amount, incentive_amount)";
        }

        $this->db->query("UPDATE project_incentive_claims 
                          SET claim_status = 'claimed', sales_head_status = 'approved', sales_head_by = {$approvedBy}, sales_head_at = '{$now}', sales_head_note = '{$escNote}' {$appAmtSql}
                          WHERE id = {$claimId}");

        $this->db->query("UPDATE approval_requests 
                          SET sales_head_status = 'approved', sales_head_by = {$approvedBy}, sales_head_at = '{$now}', sales_head_note = '{$escNote}', approve_role = 'accounts_head', accounts_head_status = 'pending' {$appAmtReqSql}
                          WHERE source_table = 'project_incentive_claims' AND source_id = {$claimId}");

        return ['success' => true, 'message' => 'Claim approved by Sales Team Head and forwarded to Accounts Head.'];
    }

    /**
     * Approve Accounts Head Stage (Stage 2)
     */
    public function approveAccountsHead($approvedBy, $claimId, $note = 'Approved by Accounts Head', $approvedAmount = null)
    {
        $claimId = (int)$claimId;
        $approvedBy = (int)$approvedBy;
        $now = date('Y-m-d H:i:s');
        $escNote = $this->db->real_escape_string($note);

        $appAmtSql = "";
        $appAmtReqSql = "";
        if ($approvedAmount !== null && (float)$approvedAmount > 0) {
            $fAmt = (float)$approvedAmount;
            $appAmtSql = ", approved_amount = {$fAmt}";
            $appAmtReqSql = ", approved_amount = {$fAmt}";
        }

        $this->db->query("UPDATE project_incentive_claims 
                          SET claim_status = 'claimed', accounts_head_status = 'approved', accounts_head_by = {$approvedBy}, accounts_head_at = '{$now}', accounts_head_note = '{$escNote}' {$appAmtSql}
                          WHERE id = {$claimId}");

        $this->db->query("UPDATE approval_requests 
                          SET accounts_head_status = 'approved', accounts_head_by = {$approvedBy}, accounts_head_at = '{$now}', accounts_head_note = '{$escNote}', approve_role = 'management', status = 'pending' {$appAmtReqSql}
                          WHERE source_table = 'project_incentive_claims' AND source_id = {$claimId}");

        return ['success' => true, 'message' => 'Claim approved by Accounts Head and forwarded to Management.'];
    }

    /**
     * Approve Claim - Management Final Approval (Stage 3)
     */
    public function approveClaim($approvedBy, $claimId, $note = 'Approved by Management', $approvedAmount = null)
    {
        $claimId = (int)$claimId;
        $approvedBy = (int)$approvedBy;

        // Check if flat already has an approved claim (ignore splits and overrides)
        $q = $this->db->query("SELECT flat_id, booking_id, split_type FROM project_incentive_claims WHERE id = " . $claimId);
        $claim = $q ? $q->fetch_assoc() : null;
        if ($claim && !empty($claim['flat_id']) && !in_array($claim['split_type'], ['designated_30', 'seller_70', 'tl_override'])) {
            $flatId = (int)$claim['flat_id'];
            $dupClaim = $this->engine->getFlatClaimDetails($flatId, $claim['booking_id']);
            if ($dupClaim && in_array($dupClaim['claim_status'], ['approved', 'partially_paid', 'paid'])) {
                return [
                    'success' => false, 
                    'message' => 'Cannot approve claim: This flat unit has already been approved for sales incentive under Claim #' . $dupClaim['id'] . '.'
                ];
            }
        }

        $now = date('Y-m-d H:i:s');
        $escNote = $this->db->real_escape_string($note);

        $appAmtSql = "";
        $appAmtReqSql = "";
        if ($approvedAmount !== null && (float)$approvedAmount > 0) {
            $fAmt = (float)$approvedAmount;
            $appAmtSql = ", approved_amount = {$fAmt}";
            $appAmtReqSql = ", approved_amount = {$fAmt}";
        } else {
            $appAmtSql = ", approved_amount = COALESCE(approved_amount, incentive_amount)";
        }

        $sql = "UPDATE project_incentive_claims 
                SET claim_status = 'approved', mgmt_status = 'approved', mgmt_by = ?, mgmt_at = ?, mgmt_note = ?, approved_by = ?, approved_at = ? {$appAmtSql}
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issisi", $approvedBy, $now, $escNote, $approvedBy, $now, $claimId);
            $stmt->execute();
            $stmt->close();
        }

        // Update approval_requests table: marks approved and moves to accounts for payout!
        $sqlApp = "UPDATE approval_requests 
                   SET status = 'approved', approve_role = 'accounts', approved_by = ?, approved_at = ?, approval_note = ? {$appAmtReqSql}
                   WHERE source_table = 'project_incentive_claims' AND source_id = ?";
        $stmtApp = $this->db->prepare($sqlApp);
        if ($stmtApp) {
            $stmtApp->bind_param("issi", $approvedBy, $now, $escNote, $claimId);
            $stmtApp->execute();
            $stmtApp->close();
        }

        return ['success' => true, 'message' => 'Claim approved by Management successfully! Ready for Accounts payout.'];
    }

    /**
     * Reject Claim
     */
    public function rejectClaim($rejectedBy, $claimId, $note = 'Rejected')
    {
        $now = date('Y-m-d H:i:s');
        $escNote = $this->db->real_escape_string($note ?: 'Claim rejected');
        $rejectedBy = (int)$rejectedBy;
        $claimId = (int)$claimId;

        $this->db->query("UPDATE project_incentive_claims 
                          SET claim_status = 'rejected', mgmt_status = 'rejected', rejected_by = {$rejectedBy}, rejected_at = '{$now}', rejection_note = '{$escNote}' 
                          WHERE id = {$claimId}");

        $this->db->query("UPDATE approval_requests 
                          SET status = 'rejected', approve_role = 'rejected', approved_by = {$rejectedBy}, approved_at = '{$now}', approval_note = '{$escNote}' 
                          WHERE source_table = 'project_incentive_claims' AND source_id = {$claimId}");

        return ['success' => true, 'message' => 'Claim rejected successfully.'];
    }

    /**
     * Disburse Payment
     */
    public function disbursePayment($disbursedBy, $claimId, $paymentAmount, $paymentMethod, $bankName, $transactionRef, $paymentDate, $notes, $accountId = null, $companyBankAccountId = null, $toBankName = null)
    {
        if ($accountId !== null) {
            $this->db->query("UPDATE project_incentive_claims SET account_id = " . (int)$accountId . " WHERE id = " . (int)$claimId);
        }
        
        // Fetch claim user_id, incentive amount, and approved amount
        $sql = "SELECT user_id, incentive_amount, approved_amount, claim_status FROM project_incentive_claims WHERE id = ? LIMIT 1";
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

        if ($claim['claim_status'] === 'rejected') {
            return ['success' => false, 'message' => 'Cannot disburse: This claim has been rejected.'];
        }

        $userId = (int)$claim['user_id'];
        $effectiveIncentive = (!empty($claim['approved_amount']) && (float)$claim['approved_amount'] > 0) 
            ? (float)$claim['approved_amount'] 
            : (float)$claim['incentive_amount'];
        $pAmount = (float)$paymentAmount;

        // Insert into project_incentive_payments
        $insSql = "INSERT INTO project_incentive_payments (claim_id, user_id, payment_amount, payment_method, transaction_ref, payment_date, bank_name, company_bank_account_id, to_bank_name, disbursed_by, notes)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insStmt = $this->db->prepare($insSql);
        if (!$insStmt) {
            return ['success' => false, 'message' => 'DB insert payment error: ' . $this->db->error];
        }

        $insStmt->bind_param("iidssssisis", $claimId, $userId, $pAmount, $paymentMethod, $transactionRef, $paymentDate, $bankName, $companyBankAccountId, $toBankName, $disbursedBy, $notes);
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

        $newStatus = ($totalPaid >= $effectiveIncentive) ? 'paid' : 'partially_paid';

        $updSql = "UPDATE project_incentive_claims SET claim_status = ? WHERE id = ?";
        $updStmt = $this->db->prepare($updSql);
        $updStmt->bind_param("si", $newStatus, $claimId);
        $updStmt->execute();
        $updStmt->close();

        if ($newStatus === 'paid') {
            $this->db->query("UPDATE approval_requests SET approve_role = 'completed' WHERE source_table = 'project_incentive_claims' AND source_id = " . (int)$claimId);
        }

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
                    SUM(CASE WHEN c.claim_status IN ('claimed', 'approved', 'partially_paid', 'paid') THEN COALESCE(c.approved_amount, c.incentive_amount) ELSE 0 END) AS total_earned,
                    SUM(CASE WHEN c.claim_status IN ('approved', 'partially_paid', 'paid') THEN COALESCE(c.approved_amount, c.incentive_amount) ELSE 0 END) AS approved_amt,
                    SUM(CASE WHEN c.claim_status = 'rejected' THEN c.incentive_amount ELSE 0 END) AS rejected_amt,
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
                    'rejected_amt'  => (float)$row['rejected_amt'],
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
    public function getDisbursementLogs($userId = null)
    {
        $whereSql = "";
        if ($userId !== null) {
            $uId = (int)$userId;
            $whereSql = " WHERE p.user_id = {$uId} ";
        }

        $sql = "SELECT 
                    p.*,
                    u.full_name AS recipient_name,
                    IFNULL(e.employee_id, CONCAT('EMP-', u.id)) AS employee_id,
                    mgr.full_name AS disbursed_by_name,
                    cb.bank_name AS company_bank_name,
                    cb.account_name AS company_account_name,
                    cb.account_number AS company_account_number
                FROM project_incentive_payments p
                LEFT JOIN users u ON p.user_id = u.id
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN users mgr ON p.disbursed_by = mgr.id
                LEFT JOIN company_bank_accounts cb ON p.company_bank_account_id = cb.id
                {$whereSql}
                ORDER BY p.id DESC";

        $res = $this->db->query($sql);
        $logs = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $logs[] = [
                    'id'              => (int)$row['id'],
                    'claim_id'        => (int)$row['claim_id'],
                    'disbursement_no' => 'PAY-' . date('Y', strtotime($row['payment_date'])) . '-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
                    'recipient_name'  => $row['recipient_name'] . ' (' . $row['employee_id'] . ')',
                    'payment_amount'  => (float)$row['payment_amount'],
                    'payment_method'  => $row['payment_method'],
                    'bank_name'       => $row['bank_name'] ?? 'N/A',
                    'company_bank_name' => $row['company_bank_name'] ?? '',
                    'to_bank_name'    => $row['to_bank_name'] ?? '',
                    'transaction_ref' => $row['transaction_ref'] ?? 'N/A',
                    'payment_date'    => $row['payment_date'],
                    'disbursed_by'    => $row['disbursed_by_name'] ?? 'Accounts Admin',
                    'notes'           => $row['notes'] ?? '',
                    'is_received'     => (int)($row['is_received'] ?? 0),
                    'received_at'     => $row['received_at'] ?? null,
                    'received_by'     => $row['received_by'] ?? null
                ];
            }
        }

        return $logs;
    }

    /**
     * Confirm receipt of incentive payout by Sales Person
     */
    public function confirmReceipt($userId, $claimId, $paymentId = null, $note = '')
    {
        $userId = (int)$userId;
        $claimId = (int)$claimId;
        $paymentId = $paymentId ? (int)$paymentId : null;
        $now = date('Y-m-d H:i:s');

        if ($claimId <= 0 && $paymentId <= 0) {
            return ['success' => false, 'message' => 'Invalid claim or payout ID.'];
        }

        // If claimId provided
        if ($claimId > 0) {
            $chk = $this->db->query("SELECT id, user_id, claim_status, is_received FROM project_incentive_claims WHERE id = {$claimId}");
            if (!$chk || $chk->num_rows == 0) {
                return ['success' => false, 'message' => 'Incentive claim record not found.'];
            }
            $claim = $chk->fetch_assoc();
            
            // Check authorization: must be recipient or management
            $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
            if ((int)$claim['user_id'] !== $userId && !in_array($userRole, [1, 3, 20], true)) {
                return ['success' => false, 'message' => 'Unauthorized: You can only confirm receipt for your own incentive payout.'];
            }

            if (!empty($claim['is_received'])) {
                return ['success' => true, 'message' => 'This incentive payout has already been marked as received.'];
            }

            $this->db->query("UPDATE project_incentive_claims 
                              SET is_received = 1, received_at = '{$now}', received_by = {$userId} 
                              WHERE id = {$claimId}");

            $this->db->query("UPDATE project_incentive_payments 
                              SET is_received = 1, received_at = '{$now}', received_by = {$userId} 
                              WHERE claim_id = {$claimId}");
        }

        // If paymentId provided
        if ($paymentId > 0) {
            $this->db->query("UPDATE project_incentive_payments 
                              SET is_received = 1, received_at = '{$now}', received_by = {$userId} 
                              WHERE id = {$paymentId}");

            $pChk = $this->db->query("SELECT claim_id FROM project_incentive_payments WHERE id = {$paymentId}");
            if ($pChk && $pRow = $pChk->fetch_assoc()) {
                $pClaimId = (int)$pRow['claim_id'];
                if ($pClaimId > 0) {
                    $this->db->query("UPDATE project_incentive_claims 
                                      SET is_received = 1, received_at = '{$now}', received_by = {$userId} 
                                      WHERE id = {$pClaimId}");
                }
            }
        }

        return ['success' => true, 'message' => 'Thank you! Incentive payout receipt confirmed successfully.'];
    }



    /**
     * Claim Team Leader 0.05% Override on Qualified Member Sales
     */
    public function claimTlOverride($userId, $memberUserId, $month = null, $remarks = '')
    {
        $userId = (int)$userId;
        $memberUserId = (int)$memberUserId;
        $month = $month ?: $this->getCycleForDate(date('Y-m-d'));
        $remarks = trim($remarks);

        if ($userId <= 0 || $memberUserId <= 0) {
            return ['success' => false, 'message' => 'Invalid Team Leader or Member ID'];
        }

        // 1. Verify TL role & team affiliation
        $chkTL = $this->db->query("
            SELECT e.team_id, e.is_team_leader, u.role_id, t.name as team_name
            FROM employees e
            JOIN users u ON e.user_id = u.id
            LEFT JOIN teams t ON e.team_id = t.id
            WHERE e.user_id = {$userId}
        ");
        $tlRow = $chkTL ? $chkTL->fetch_assoc() : null;
        if (!$tlRow) {
            return ['success' => false, 'message' => 'Team leader employee record not found'];
        }

        $isSalesHead = in_array((int)$tlRow['role_id'], [1, 3, 9]);
        $isTeamLeader = !empty($tlRow['is_team_leader']) || in_array((int)$tlRow['role_id'], [5, 6, 7]);
        if (!$isSalesHead && !$isTeamLeader) {
            return ['success' => false, 'message' => 'You are not authorized as a Team Leader.'];
        }

        // Verify member is in the same team
        $chkMember = $this->db->query("
            SELECT e.team_id, e.full_name, e.employee_id
            FROM employees e
            WHERE e.user_id = {$memberUserId} AND e.deleted_at IS NULL
        ");
        $memRow = $chkMember ? $chkMember->fetch_assoc() : null;
        if (!$memRow) {
            return ['success' => false, 'message' => 'Team member record not found'];
        }

        if (!$isSalesHead && (int)$memRow['team_id'] !== (int)$tlRow['team_id']) {
            return ['success' => false, 'message' => 'This employee is not a member of your assigned sales team.'];
        }

        // 2. Fetch member performance for the cycle
        $memStats = $this->engine->getExecutiveMonthlyStats($memberUserId, $month);
        $achievedLandShare = (float)($memStats['total_achieved_land_share'] ?? 0.00);
        $isQualified = ($achievedLandShare >= 2500000.00 || !empty($memStats['is_eligible']));

        if (!$isQualified || $achievedLandShare <= 0) {
            return ['success' => false, 'message' => 'Incentive ineligible: Team member must achieve at least ৳25 Lakh in land share sales to qualify for TL override.'];
        }

        // 3. Compute 0.05% override
        $settings = $this->engine->getSettings();
        $tlRate = (float)($settings['team_leader_rate'] ?? 0.05); // 0.05%
        $overrideAmount = round(($achievedLandShare * $tlRate) / 100.0, 2);

        if ($overrideAmount <= 0) {
            return ['success' => false, 'message' => 'No override incentive earned for this member in this cycle.'];
        }

        // 4. Duplicate claim prevention
        $cStart = $memStats['cycle_start'];
        $cEnd = $memStats['cycle_end'];
        $dupCheck = $this->db->query("
            SELECT id, claim_status 
            FROM project_incentive_claims 
            WHERE user_id = {$userId} 
              AND original_sales_by = {$memberUserId} 
              AND split_type = 'tl_override' 
              AND (cycle_month = '{$month}' OR (created_at BETWEEN '{$cStart}' AND '{$cEnd}'))
              AND claim_status != 'rejected'
            LIMIT 1
        ");
        if ($dupCheck && $existClaim = $dupCheck->fetch_assoc()) {
            return ['success' => false, 'message' => 'This TL override incentive has already been claimed (Status: ' . ucfirst($existClaim['claim_status']) . '). It cannot be claimed again.'];
        }

        // 5. Insert claim record
        $now = date('Y-m-d H:i:s');
        $insSql = "INSERT INTO project_incentive_claims 
                   (user_id, original_sales_by, claim_tier, split_type, is_external, sale_amount, land_share_value, incentive_percentage, incentive_amount, booking_completed, registration_completed, claim_status, cycle_month, claimed_at, remarks)
                   VALUES (?, ?, 'team_lead', 'tl_override', 0, ?, ?, ?, ?, 1, 1, 'claimed', ?, ?, ?)";
        $insStmt = $this->db->prepare($insSql);
        if (!$insStmt) {
            return ['success' => false, 'message' => 'Database prepare failed: ' . $this->db->error];
        }
        $insStmt->bind_param("iiddddsss", $userId, $memberUserId, $achievedLandShare, $achievedLandShare, $tlRate, $overrideAmount, $month, $now, $remarks);
        if (!$insStmt->execute()) {
            $err = $insStmt->error;
            $insStmt->close();
            return ['success' => false, 'message' => 'Failed to save claim: ' . $err];
        }
        $claimId = $insStmt->insert_id;
        $insStmt->close();

        // 6. Create Approval Request
        $title = "TL 0.05% Override: {$memRow['full_name']} [{$memRow['employee_id']}] (Achieved: ৳" . number_format($achievedLandShare, 2) . " - {$month})";
        $sqlReq = "INSERT INTO approval_requests 
                   (department, request_type, title, source_table, source_id, request_amount, status, requested_by, approve_role, sales_head_status, accounts_head_status, created_at)
                   VALUES ('Sales', 'Incentive Claim', ?, 'project_incentive_claims', ?, ?, 'pending', ?, 'sales_head', 'pending', 'pending', NOW())";
        $stmtReq = $this->db->prepare($sqlReq);
        if ($stmtReq) {
            $stmtReq->bind_param("sidi", $title, $claimId, $overrideAmount, $userId);
            $stmtReq->execute();
            $stmtReq->close();
        }

        return [
            'success' => true, 
            'message' => 'TL Override claim (৳' . number_format($overrideAmount, 2) . ') submitted successfully! Sent to Management for approval.',
            'claim_id' => $claimId
        ];
    }

    /**
     * Claim 30% Designated Project Incentive Share
     */
    public function claimDesignatedIncentive($userId, $bookingId, $remarks = '')
    {
        $userId = (int)$userId;
        $bookingId = (int)$bookingId;
        $remarks = trim($remarks);

        if ($userId <= 0 || $bookingId <= 0) {
            return ['success' => false, 'message' => 'Invalid user or booking ID'];
        }

        // 1. Fetch booking & flat details
        $q = $this->db->query("
            SELECT lb.*, p.name AS project_name, p.building_type, f.flat_name, f.floor_no, IFNULL(f.land_share_value, 0.00) as land_share_value, u.full_name AS seller_name
            FROM lead_bookings lb
            JOIN projects p ON lb.project_id = p.id
            JOIN flats f ON lb.flat_id = f.id
            JOIN users u ON lb.sales_by_id = u.id
            WHERE lb.id = {$bookingId} AND lb.deleted_at IS NULL
        ");
        $booking = $q ? $q->fetch_assoc() : null;
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking record not found'];
        }

        // 2. Check if user is eligible (>= 25L achieved in month cycle)
        $month = $this->getCycleForDate($booking['booking_date']);
        $userStats = $this->engine->getExecutiveMonthlyStats($userId, $month);
        if (empty($userStats['is_eligible'])) {
            return ['success' => false, 'message' => 'Incentive ineligible: You must achieve minimum ৳25 Lakh in land share sales to unlock designated project credits.'];
        }

        // 3. Verify that user was indeed the designated salesperson for this flat
        $desInfo = $this->engine->getDesignatedSalesperson($booking['flat_id'], $booking['project_id'], $month, $booking['sales_by_id']);
        $desUserId = is_array($desInfo) ? ($desInfo['user_id'] ?? null) : $desInfo;
        if ((int)$desUserId !== $userId) {
            return ['success' => false, 'message' => 'You are not the designated sales executive for this project/flat.'];
        }

        // 4. Calculate 30% designated share
        $settings = $this->engine->getSettings();
        $isFloorSpecial = $this->engine->isFloorSpecial($booking['floor_no'], $booking['building_type']);
        $bRate = $isFloorSpecial ? (float)$settings['floor_special_rate'] : (float)$settings['flat_base_rate'];
        $lsValue = (float)$booking['land_share_value'];
        $designatedAmount = round(($lsValue * $bRate / 100.0) * 0.30, 2);
        $desPct = $bRate * 0.30;

        if ($designatedAmount <= 0) {
            return ['success' => false, 'message' => 'Designated incentive amount is 0.'];
        }

        // 5. Prevent duplicate claims
        $dupCheck = $this->db->query("
            SELECT id, claim_status 
            FROM project_incentive_claims 
            WHERE booking_id = {$bookingId} AND user_id = {$userId} AND split_type = 'designated_30' AND claim_status != 'rejected'
            LIMIT 1
        ");
        if ($dupCheck && $existClaim = $dupCheck->fetch_assoc()) {
            return ['success' => false, 'message' => 'This 30% designated share has already been claimed (Status: ' . ucfirst($existClaim['claim_status']) . ').'];
        }

        // 6. Insert claim
        $now = date('Y-m-d H:i:s');
        $saleAmount = (float)$booking['flat_price'];
        $bMoney = $this->isPaymentVerified($bookingId, 'booking_money');
        $reg = $this->isPaymentVerified($bookingId, 'registration');
        $bComp = $bMoney ? 1 : 0;
        $rComp = $reg ? 1 : 0;
        $insSql = "INSERT INTO project_incentive_claims 
                   (booking_id, project_id, flat_id, user_id, is_external, split_type, is_on_hold, sale_amount, land_share_value, incentive_percentage, incentive_amount, booking_completed, registration_completed, claim_status, cycle_month, claimed_at, remarks)
                   VALUES (?, ?, ?, ?, 0, 'designated_30', 0, ?, ?, ?, ?, ?, ?, 'claimed', ?, ?, ?)";
        $insStmt = $this->db->prepare($insSql);
        if (!$insStmt) {
            return ['success' => false, 'message' => 'Database prepare failed: ' . $this->db->error];
        }
        $insStmt->bind_param("iiiiddddiisss", $bookingId, $booking['project_id'], $booking['flat_id'], $userId, $saleAmount, $lsValue, $desPct, $designatedAmount, $bComp, $rComp, $month, $now, $remarks);
        if (!$insStmt->execute()) {
            $err = $insStmt->error;
            $insStmt->close();
            return ['success' => false, 'message' => 'Failed to save claim: ' . $err];
        }
        $claimId = $insStmt->insert_id;
        $insStmt->close();

        // 7. Create approval request
        $bkRef = 'BK-' . str_pad($bookingId, 5, '0', STR_PAD_LEFT);
        $title = "30% Designated Sales Incentive: {$bkRef} ({$booking['project_name']} - {$booking['flat_name']})";
        $sqlReq = "INSERT INTO approval_requests 
                   (department, request_type, title, source_table, source_id, request_amount, status, requested_by, approve_role, sales_head_status, accounts_head_status, created_at)
                   VALUES ('Sales', 'Incentive Claim', ?, 'project_incentive_claims', ?, ?, 'pending', ?, 'sales_head', 'pending', 'pending', NOW())";
        $stmtReq = $this->db->prepare($sqlReq);
        if ($stmtReq) {
            $stmtReq->bind_param("sidi", $title, $claimId, $designatedAmount, $userId);
            $stmtReq->execute();
            $stmtReq->close();
        }

        return [
            'success' => true, 
            'message' => '30% Designated share claim (৳' . number_format($designatedAmount, 2) . ') submitted successfully! Sent to Management for approval.',
            'claim_id' => $claimId
        ];
    }

    /**
     * Batch Claim all eligible Team Leader 0.05% Overrides for a month
     */
    public function claimAllTlOverrides($userId, $month = null, $remarks = '')
    {
        $userId = (int)$userId;
        $month = $month ?: $this->getCycleForDate(date('Y-m-d'));
        
        $teamStats = $this->engine->getTeamMonthlyStats($userId, $month);
        if (empty($teamStats['eligible']) || empty($teamStats['member_breakdown'])) {
            return ['success' => false, 'message' => 'No eligible team or members found for TL override.'];
        }

        list($y, $m) = explode('-', $month);
        $cycleRange = $this->getCycleDateRange($y, $m);
        $cStart = substr($cycleRange['start'], 0, 10);
        $cEnd   = substr($cycleRange['end'], 0, 10);

        $claimedCount = 0;
        $totalClaimedAmount = 0.00;
        $errors = [];

        foreach ($teamStats['member_breakdown'] as $mb) {
            if (!empty($mb['is_self'])) continue;
            if (empty($mb['is_qualified']) || (float)$mb['tl_contribution'] <= 0) continue;

            $memUid = (int)$mb['user_id'];
            
            // Check if already claimed
            $chk = $this->db->query("
                SELECT id FROM project_incentive_claims 
                WHERE user_id = {$userId} 
                  AND original_sales_by = {$memUid} 
                  AND split_type = 'tl_override' 
                  AND (cycle_month = '{$month}' OR (created_at BETWEEN '{$cStart} 00:00:00' AND '{$cEnd} 23:59:59'))
                  AND claim_status != 'rejected'
                LIMIT 1
            ");
            if ($chk && $chk->num_rows > 0) {
                continue; // Already claimed
            }

            $res = $this->claimTlOverride($userId, $memUid, $month, $remarks ?: 'Batch Claimed TL Override');
            if (!empty($res['success'])) {
                $claimedCount++;
                $totalClaimedAmount += (float)$mb['tl_contribution'];
            } else {
                $errors[] = $mb['full_name'] . ': ' . ($res['message'] ?? 'Failed');
            }
        }

        if ($claimedCount === 0) {
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode('; ', $errors)];
            }
            return ['success' => false, 'message' => 'All qualified team member overrides for this cycle have already been claimed!'];
        }

        $msg = "Successfully claimed 0.05% override for {$claimedCount} qualified team member(s) (Total: ৳" . number_format($totalClaimedAmount, 2) . ")!";
        return ['success' => true, 'message' => $msg, 'claimed_count' => $claimedCount, 'total_amount' => $totalClaimedAmount];
    }
}
