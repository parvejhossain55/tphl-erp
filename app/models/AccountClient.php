<?php

class AccountClient extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    /**
     * Get all clients (booked leads) with full details, optionally filtered by project
     */
    public function getAllClients($projectId = null)
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = '';

        if ($projectId) {
            $whereClause .= " AND lb.project_id = ?";
            $params[] = (int) $projectId;
            $types .= 'i';
        }

        $sql = $this->db->prepare("
        SELECT
            l.id AS lead_id,
            l.full_name AS client_name,
            l.phone,
            l.email,
            l.designation,
            l.company,
            (SELECT GROUP_CONCAT(DISTINCT u_agent.full_name SEPARATOR ', ')
             FROM lead_bookings lb_sub
             INNER JOIN lead_payments lp_sub ON lp_sub.booking_id = lb_sub.id AND lp_sub.payment_type = 'booking_money'
             INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
             WHERE lb_sub.lead_id = l.id
            ) AS agent_name,
            COUNT(lb.id) AS total_bookings,
            SUM(lb.agreement_value) AS total_agreement_value,
            -- First booking's project and flat for quick context
            (SELECT p2.name FROM lead_bookings lb2
             INNER JOIN projects p2 ON lb2.project_id = p2.id
             WHERE lb2.lead_id = l.id
             ORDER BY lb2.created_at DESC LIMIT 1) AS project_name,
            (SELECT CONCAT(f2.flat_name, ' - Floor ', f2.floor_no)
             FROM lead_bookings lb2
             INNER JOIN flats f2 ON lb2.flat_id = f2.id
             WHERE lb2.lead_id = l.id
             ORDER BY lb2.created_at DESC LIMIT 1) AS flat_no,
            -- Latest verification status across all this client's payments
            (SELECT lp.verification_status
             FROM lead_payments lp
             INNER JOIN lead_bookings lb3 ON lb3.id = lp.booking_id
             WHERE lb3.lead_id = l.id
             ORDER BY lp.created_at DESC LIMIT 1) AS latest_verification_status,
            COALESCE(
                (SELECT SUM(lp.amount)
                 FROM lead_payments lp
                 INNER JOIN lead_bookings lb2 ON lb2.id = lp.booking_id
                 WHERE lb2.lead_id = l.id
                 AND lp.payment_status IN ('paid','partially_paid')),
                0
            ) AS total_paid,
            (
                SELECT MAX(lp.payment_date)
                FROM lead_payments lp
                INNER JOIN lead_bookings lb2 ON lb2.id = lp.booking_id
                WHERE lb2.lead_id = l.id
                AND lp.payment_status IN ('paid','partially_paid')
            ) AS last_payment_date,
            MAX(lb.id) AS booking_id
        FROM leads l
        INNER JOIN lead_bookings lb ON lb.lead_id = l.id
        $whereClause
        GROUP BY l.id
        ORDER BY l.full_name
    ");

        if (!$sql) {
            error_log("getAllClients prepare failed: " . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $sql->bind_param($types, ...$params);
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get all bookings with client and location info, optionally filtered by project
     */
    public function getAllBookings($projectId = null)
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = '';

        if ($projectId) {
            $whereClause .= " AND lb.project_id = ?";
            $params[] = (int) $projectId;
            $types .= 'i';
        }

        $sql = $this->db->prepare("
        SELECT
            l.id AS lead_id,
            l.full_name AS client_name,
            l.phone,
            l.email,
            l.designation,
            lb.id AS booking_id,
            lb.project_id,
            p.name AS project_name,
            f.flat_name AS unit_name,
            f.floor_no AS flat_floor_no,
            lb.agreement_value AS total_price,
            lb.agreement_value AS total_agreement_value,
            (SELECT GROUP_CONCAT(DISTINCT u_agent.full_name SEPARATOR ', ')
             FROM lead_payments lp_sub
             INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
             WHERE lp_sub.booking_id = lb.id AND lp_sub.payment_type = 'booking_money'
            ) AS agent_name,
            COALESCE(
                (SELECT SUM(lp.amount)
                 FROM lead_payments lp
                 WHERE lp.booking_id = lb.id
                 AND lp.payment_status IN ('paid','partially_paid')),
                0
            ) AS total_paid,
            (
                SELECT MAX(lp.payment_date)
                FROM lead_payments lp
                WHERE lp.booking_id = lb.id
                AND lp.payment_status IN ('paid','partially_paid')
            ) AS last_payment_date
        FROM leads l
        INNER JOIN lead_bookings lb ON lb.lead_id = l.id
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        $whereClause
        ORDER BY l.full_name
        ");

        if (!$sql) {
            error_log("getAllBookings prepare failed: " . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $sql->bind_param($types, ...$params);
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get lands by lead ID
     */
    public function getLandsByLeadId($leadId)
    {
        $sql = $this->db->prepare("
        SELECT 
            lb.id as booking_id,
            lb.booking_date,
            lb.agreement_value,
            lb.booking_status,
            lb.landshare_status,
            lb.service_fee_balance,
            lb.transfer_status,
            
            p.id as project_id,
            p.name as project_name,
            p.types as project_type,
            
            f.id as flat_id,
            f.floor_no as flat_no,
            f.sqft as flat_size,
            f.price_value as total_price,
            f.land_share_value as total_land_share,
            f.flat_name as unit_name,
            
            (SELECT u_agent.full_name
             FROM lead_payments lp_sub
             INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
             WHERE lp_sub.booking_id = lb.id AND lp_sub.payment_type = 'booking_money'
             LIMIT 1
            ) AS booking_agent_name,
            
            COALESCE(
                (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_paid,
            COALESCE(
                (SELECT SUM(lp.land_share_amount) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_land_share_paid,
            COALESCE(
                (SELECT SUM(lp.service_fee) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_service_fee,
            COALESCE(
                (SELECT MAX(lp.payment_date) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                NULL
            ) as last_payment_date
        FROM lead_bookings lb
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
 WHERE lb.lead_id = ?
AND lb.booking_status != 'cancelled'
ORDER BY lb.booking_date DESC
    ");

        $sql->bind_param("i", $leadId);
        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    /**
     * Get payment statistics
     */
    public function getPaymentStatistics($projectId = null)
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        $types = "";

        if ($projectId) {
            $whereClause .= " AND lb.project_id = ?";
            $params[] = (int) $projectId;
            $types .= "i";
        }

        $sql = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT l.id) as total_clients,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT SUM(lp.amount) 
                          FROM lead_payments lp 
                          WHERE lp.booking_id = lb.id 
                          AND lp.payment_status IN ('paid','partially_paid')) >= lb.agreement_value 
                    THEN lb.id 
                END) as full_payment_completed,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT COUNT(*) 
                          FROM lead_payments lp2 
                          WHERE lp2.booking_id = lb.id 
                          AND lp2.payment_status = 'paid') > 0
                    THEN lb.id 
                END) as approved_bookings,
                COALESCE(SUM(lp.service_fee), 0) as total_service_fee_collected
            FROM leads l
            INNER JOIN lead_bookings lb ON l.id = lb.lead_id
            LEFT JOIN lead_payments lp ON lb.id = lp.booking_id AND lp.payment_status IN ('paid','partially_paid')
            $whereClause
        ");

        if (!$sql) {
            error_log("getPaymentStatistics prepare failed: " . $this->db->error);
            return [];
        }

        if (!empty($params)) {
            $sql->bind_param($types, ...$params);
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_assoc() : [];
    }

    /**
     * Get booking money transaction from database
     */
    public function getBookingMoneyTransaction($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lp.*,
                l.full_name as client_name,
                p.name as project_name,
                f.id as flat_id,
                f.floor_no,
                f.flat_name as unit_name,
                CONCAT(f.flat_name, ' - FLOOR ', f.floor_no) as flat_no,
                u.full_name as created_by_name
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN users u ON u.id = lp.created_by
            WHERE lp.booking_id = ? 
            AND lp.payment_type = 'booking_money'
            ORDER BY lp.payment_date ASC
            LIMIT 1
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result ? $result->fetch_assoc() : null;

        if ($transaction) {
            $transaction['installment_label'] = 'BOOKING';
            $transaction['payment_details'] = 'Booking Agreement';
        }

        return $transaction;
    }

    /**
     * Get client details by booking ID with correct payment calculations
     * FIXED: Now correctly calculates service fee from emi_schedules table
     */
    public function getClientByBookingId($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lb.id as booking_id,
                lb.lead_id,
                lb.booking_date,
                lb.agreement_value,
                -- booking_money_total: all NON-REJECTED entries (pending + verified) — for remaining balance in dropdown
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.verification_status != 'rejected'), 
                 0) AS booking_money_total,
                -- booking_money: only VERIFIED entries (for stat cards & land share calc)
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.verification_status = 'verified'), 
                 0) AS booking_money,
                lb.discount_amount,
                lb.booking_status,
                lb.landshare_status,
                lb.service_fee_balance,
                l.id as lead_id,
                l.full_name as client_name,
                l.phone,
                l.email,
                l.designation,
                p.id as project_id,
                p.name as project_name,
                p.types as project_type,
                COALESCE(p.service_charge_per_emi, 0) as service_charge_per_emi,
                f.id as flat_id,
                f.floor_no as flat_no,
                f.sqft as flat_size,
                f.price_value as total_price,
                f.land_share_value as total_land_share,
                COALESCE(f.development_charges, 0) as flat_development_charges,
                f.flat_name as unit_name,
                f.is_shop,
                f.unit_type,
                COALESCE(f.shop_service_charge, 0) as shop_service_charge,
                u.full_name as agent_name,
                -- Payment calculations
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as total_paid,
                COALESCE(
                    (SELECT SUM(lp.land_share_amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as additional_land_share_paid,
                -- Service fee: sum from verified EMI/settlement payments only
                COALESCE(
                    (SELECT SUM(lp.service_fee) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as total_service_fee,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'registration'), 
                    0
                ) as registration_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_type = 'booking_money'
                     AND lp.verification_status = 'verified'), 
                    0
                ) as booking_money_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')), 
                    0
                ) as emi_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')), 
                    0
                ) as emi_paid_total,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'development_charge'), 
                    0
                ) as development_charge_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'development_charge'), 
                    0
                ) as development_charge_paid_total,
                COALESCE(
                    (SELECT SUM(lp.due_emi_amount)
                     FROM lead_payments lp
                     WHERE lp.booking_id = lb.id
                     AND lp.payment_type = 'emi'
                     AND lp.due_emi_amount > 0
                     AND lp.verification_status = 'verified'
                     AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                     AND NOT EXISTS (
                         SELECT 1 FROM lead_payments child
                         WHERE child.parent_payment_id = lp.id
                         AND child.payment_type = 'emi_due_settlement'
                         AND child.verification_status IN ('pending', 'verified')
                     )),
                    0
                ) as total_emi_due,
                COALESCE(
                    (SELECT SUM(lp.bulk_emi_discount)
                     FROM lead_payments lp
                     WHERE lp.booking_id = lb.id
                     AND lp.verification_status = 'verified'
                     AND lp.bulk_emi_discount > 0),
                    0
                ) as total_bulk_emi_discount,
                COALESCE(
                    (SELECT MAX(lp.payment_date) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    NULL
                ) as last_payment_date,
                lb.transfer_date,
                lb.transfer_note,
                lb.transfer_status,
                transfer_u.full_name as transfer_updated_by_name
            FROM lead_bookings lb
            INNER JOIN leads l ON lb.lead_id = l.id
            INNER JOIN projects p ON lb.project_id = p.id
            INNER JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON l.assigned_id = u.id
            LEFT JOIN users transfer_u ON lb.transfer_updated_by = transfer_u.id
            WHERE lb.id = ?
        ");

        if (!$stmt) {
            error_log("Prepare statement failed: " . $this->db->error);
            return null;
        }

        $stmt->bind_param("i", $bookingId);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();
        $clientDetails = $result ? $result->fetch_assoc() : null;

        if ($clientDetails) {
            // Cast all amounts to float for calculations
            $totalPrice            = (float) $clientDetails['total_price'];
            $totalLandShare        = (float) $clientDetails['total_land_share'];   // flat.land_share_value (fixed target)
            $flatDevCharges        = (float) ($clientDetails['flat_development_charges'] ?? 0); // flat.development_charges (fixed)
            $serviceChargePerEmi   = (float) ($clientDetails['service_charge_per_emi'] ?? 0); // project %
            $bookingMoney          = (float) $clientDetails['booking_money'];      // verified booking money
            $discountAmount        = (float) $clientDetails['discount_amount'];

            // Paid amounts
            $totalPaid             = (float) $clientDetails['total_paid'];
            $bookingMoneyPaid      = (float) $clientDetails['booking_money_paid'];
            $additionalLandSharePaid = (float) $clientDetails['additional_land_share_paid'];
            $registrationPaid      = (float) $clientDetails['registration_paid'];
            $developmentChargePaid = (float) $clientDetails['development_charge_paid'];
            $emiPaid               = (float) $clientDetails['emi_paid'];
            $totalServiceFee       = (float) $clientDetails['total_service_fee'];

            // booking_money_total: all recorded entries (used for remaining in dropdown)
            $bookingMoneyTotal = (float) ($clientDetails['booking_money_total'] ?? 0);

            // NEW: Landshare Paid = booking money + land share payments + registration payments
            // (registration is treated as part of the landshare component)
            $totalLandSharePaid = $bookingMoney + $additionalLandSharePaid + $registrationPaid;

            // Remaining land share = effective target - all land share group payments
            // Land share group = booking money + additional land share payments + registration
            // Discount from sales reduces the effective land share target
            $landSharePaymentsOnly    = round($bookingMoney + $additionalLandSharePaid + $registrationPaid);
            $effectiveLandShareTarget = max(0, round($totalLandShare - $discountAmount));
            $remainingLandShare       = max(0, round($effectiveLandShareTarget - $landSharePaymentsOnly));
            $isLandShareCompleted     = ($remainingLandShare <= 0);

            // Total Construction Fee = Total Price - (Land Share + Dev Charges)
            // Discount only affects land share remaining, NOT the construction fee
            $constructionFee = max(0, round($totalPrice - ($totalLandShare + $flatDevCharges)));

            // Fixed Per-EMI Amount = Construction Fee / Total EMI count
            $emiStats = $this->getEMIStatistics($bookingId);
            $totalEmiCount = $emiStats['total_emi'];
            $perEmiAmount  = ($totalEmiCount > 0) ? round($constructionFee / $totalEmiCount) : 0;

            // Dev charge: completed when paid amount >= flat fixed development_charges
            $isDevChargeCompleted = ($flatDevCharges > 0 && $developmentChargePaid >= $flatDevCharges);
            // If there's no dev charge defined on the flat, treat it as complete (not blocking EMI)
            if ($flatDevCharges <= 0) {
                $isDevChargeCompleted = true;
            }

            // Construction fee paid = total EMI amounts paid (amount IS the construction portion;
            // service fee is charged ON TOP as company revenue, not deducted from construction)
            $constructionFeePaid         = $emiPaid;
            $totalBulkEmiDiscount        = (float) ($clientDetails['total_bulk_emi_discount'] ?? 0);
            $remainingConstructionFee    = max(0, round($constructionFee - $constructionFeePaid - $totalBulkEmiDiscount));

            // Registration paid status
            $isRegistrationPaid = ($registrationPaid > 0);

            // Total due and net received
            // Booking discount + Bulk EMI discount both reduce the client's total obligation
            $totalDue     = max(0, round($totalPrice - $discountAmount - $totalPaid - $totalBulkEmiDiscount));
            $netReceived  = round($totalPaid - $totalServiceFee);
            $totalEmiDue  = (float) ($clientDetails['total_emi_due'] ?? 0);

            // Safety net: if construction fee is fully paid, there can be NO outstanding EMI due
            // This handles legacy data where due_emi_amount was set on the last installment
            if ($remainingConstructionFee <= 0) {
                $totalEmiDue = 0;
            }

            // Add calculated fields to response
            $clientDetails['total_land_share_paid']                = $totalLandSharePaid;
            $clientDetails['landshare_paid']                       = $totalLandSharePaid;
            $clientDetails['flat_development_charges']             = $flatDevCharges;
            $clientDetails['development_charge_paid']              = $developmentChargePaid;
            $clientDetails['remaining_land_share']                 = $remainingLandShare;
            $clientDetails['is_land_share_completed']              = $isLandShareCompleted;
            $clientDetails['is_dev_charge_completed']              = $isDevChargeCompleted;
            $clientDetails['is_registration_paid']                 = $isRegistrationPaid;
            $clientDetails['construction_fee']                     = $constructionFee;
            $clientDetails['per_emi_amount']                       = $perEmiAmount;
            $clientDetails['service_charge_per_emi']               = $serviceChargePerEmi;
            $clientDetails['construction_fee_paid']                = $constructionFeePaid;
            $clientDetails['construction_fee_paid_after_service']  = $constructionFeePaid;
            $clientDetails['remaining_construction_fee']           = $remainingConstructionFee;
            $clientDetails['total_due']                            = $totalDue;
            $clientDetails['net_received']                         = $netReceived;
            $clientDetails['total_emi_due']                        = $totalEmiDue;
            $clientDetails['total_bulk_emi_discount']               = $totalBulkEmiDiscount;

            // Add EMI statistics
            $clientDetails['total_emi']           = $emiStats['total_emi'];
            $clientDetails['completed_emi']        = $emiStats['completed_emi'];
            $clientDetails['pending_emi']          = $emiStats['pending_emi'];
            $clientDetails['paid_emi_numbers']     = $emiStats['paid_emi_numbers'];
            $clientDetails['pending_emi_numbers']  = $emiStats['pending_emi_numbers'];
        }

        return $clientDetails;
    }

    /**
     * Get EMI statistics for a booking
     */
    private function getEMIStatistics($bookingId)
    {
        $totalEmiStmt = $this->db->prepare("
            SELECT total_emi_no 
            FROM lead_payments 
            WHERE booking_id = ? 
            AND total_emi_no IS NOT NULL
            LIMIT 1
        ");
        $totalEmiStmt->bind_param("i", $bookingId);
        $totalEmiStmt->execute();
        $totalEmiResult = $totalEmiStmt->get_result();
        $totalEmiRow = $totalEmiResult ? $totalEmiResult->fetch_assoc() : null;
        $totalEmi = $totalEmiRow && $totalEmiRow['total_emi_no'] ? (int) $totalEmiRow['total_emi_no'] : 0;

        $stats = [
            'total_emi' => $totalEmi,
            'completed_emi' => 0,
            'pending_emi' => 0,
            'paid_emi_numbers' => [],
            'pending_emi_numbers' => []
        ];

        // Get paid EMI numbers
        $paidEmiStmt = $this->db->prepare("
            SELECT es.emi_no
            FROM emi_schedules es
            INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE lp.booking_id = ? 
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            ORDER BY es.emi_no
        ");
        $paidEmiStmt->bind_param("i", $bookingId);
        $paidEmiStmt->execute();
        $paidEmiResult = $paidEmiStmt->get_result();

        $paidEmiNumbers = [];
        if ($paidEmiResult) {
            while ($row = $paidEmiResult->fetch_assoc()) {
                $paidEmiNumbers[] = (int) $row['emi_no'];
            }
        }

        $stats['completed_emi'] = count($paidEmiNumbers);
        $stats['paid_emi_numbers'] = $paidEmiNumbers;

        // Pending = total plan - completed (simple subtraction, not sequence gap check)
        $stats['pending_emi'] = max(0, $totalEmi - $stats['completed_emi']);

        // Build pending numbers list as (completed+1) .. totalEmi
        $stats['pending_emi_numbers'] = [];
        if ($stats['pending_emi'] > 0) {
            $nextStart = $stats['completed_emi'] + 1;
            for ($i = $nextStart; $i <= $totalEmi; $i++) {
                $stats['pending_emi_numbers'][] = $i;
            }
        }

        return $stats;
    }

    /**
     * Get Virtual EMI Schedule (Yield to Maturity Logic)
     * Calculates the maturity status of all EMIs based on the current date.
     */
    public function getVirtualEmiSchedule($bookingId)
    {
        $clientDetails = $this->getClientByBookingId($bookingId);
        if (!$clientDetails) return [];

        $totalEmi = $clientDetails['total_emi'] ?? 0;
        if ($totalEmi <= 0) return [];

        // Fetch all verified paid/partially paid EMIs
        $paidEmiStmt = $this->db->prepare("
            SELECT es.emi_no, es.emi_amount, es.due_emi_amount, es.due_date, es.status, lp.payment_date, lp.payment_status
            FROM emi_schedules es
            INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE lp.booking_id = ? 
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            ORDER BY es.emi_no ASC
        ");
        $paidEmiStmt->bind_param("i", $bookingId);
        $paidEmiStmt->execute();
        $paidResult = $paidEmiStmt->get_result();
        
        $paidEmis = [];
        $lastPaidDueDate = null;
        if ($paidResult) {
            while ($row = $paidResult->fetch_assoc()) {
                $paidEmis[(int)$row['emi_no']] = $row;
                if (!empty($row['due_date'])) {
                    $lastPaidDueDate = $row['due_date'];
                }
            }
        }

        $schedule = [];
        $currentDate = new \DateTime();
        $currentMonth = (int)$currentDate->format('Ym'); // e.g., 202606
        
        // Determine the anchor date for the very first EMI
        $anchorDate = null;
        if (!empty($lastPaidDueDate)) {
            $anchorDate = new \DateTime($lastPaidDueDate);
        } else {
            // No EMIs paid yet, anchor to the date of the last completed pre-EMI payment
            $lastPaymentStmt = $this->db->prepare("
                SELECT MAX(payment_date) as last_payment_date 
                FROM lead_payments 
                WHERE booking_id = ? 
                AND payment_status IN ('paid','partially_paid') 
                AND verification_status = 'verified'
            ");
            $lastPaymentStmt->bind_param("i", $bookingId);
            $lastPaymentStmt->execute();
            $lastPaymentResult = $lastPaymentStmt->get_result();
            $lastPaymentRow = $lastPaymentResult ? $lastPaymentResult->fetch_assoc() : null;
            
            $anchorDateStr = !empty($lastPaymentRow['last_payment_date']) 
                                ? $lastPaymentRow['last_payment_date'] 
                                : (!empty($clientDetails['booking_date']) ? $clientDetails['booking_date'] : date('Y-m-d'));
            
            $anchorDate = new \DateTime($anchorDateStr);
            $anchorDate->modify('+1 month');
        }

        $runnerDate = clone $anchorDate;
        
        for ($i = 1; $i <= $totalEmi; $i++) {
            $isPaid = isset($paidEmis[$i]);
            
            if ($isPaid) {
                $record = $paidEmis[$i];
                $dueDateObj = new \DateTime($record['due_date']);
                $dueMonth = (int)$dueDateObj->format('Ym');
                
                $isPartiallyPaid = ($record['payment_status'] === 'partially_paid' && $record['due_emi_amount'] > 0);
                
                if ($isPartiallyPaid) {
                    if ($dueMonth < $currentMonth) {
                        $maturityStatus = 'due';
                    } else {
                        $maturityStatus = 'pending';
                    }
                } else {
                    if ($dueMonth < $currentMonth) {
                        $maturityStatus = 'matured';
                    } else {
                        $maturityStatus = 'paid';
                    }
                }
                
                $schedule[] = [
                    'emi_no' => $i,
                    'due_date' => $record['due_date'],
                    'amount' => $clientDetails['per_emi_amount'],
                    'paid_amount' => $record['emi_amount'],
                    'due_amount' => $record['due_emi_amount'],
                    'status' => $record['payment_status'],
                    'maturity_status' => $maturityStatus,
                    'is_virtual' => false
                ];
                
                $runnerDate = clone $dueDateObj;
                
            } else {
                if (count($paidEmis) > 0 || $i > 1) {
                    $runnerDate->modify('+1 month');
                }
                $projectedDueDate = $runnerDate->format('Y-m-d');
                $dueMonth = (int)$runnerDate->format('Ym');
                
                if ($dueMonth < $currentMonth) {
                    $maturityStatus = 'due';
                } elseif ($dueMonth == $currentMonth) {
                    $maturityStatus = 'pending';
                } else {
                    $maturityStatus = 'future';
                }
                
                $schedule[] = [
                    'emi_no' => $i,
                    'due_date' => $projectedDueDate,
                    'amount' => $clientDetails['per_emi_amount'],
                    'paid_amount' => 0,
                    'due_amount' => $clientDetails['per_emi_amount'],
                    'status' => 'unpaid',
                    'maturity_status' => $maturityStatus,
                    'is_virtual' => true
                ];
            }
        }
        
        return $schedule;
    }

    /**
     * Get client by lead ID
     */
    public function getClientByLeadId($leadId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.id as lead_id,
                l.full_name as client_name,
                l.phone,
                l.email,
                l.designation,
                u.full_name as agent_name
            FROM leads l
            LEFT JOIN users u ON l.assigned_id = u.id
            WHERE l.id = ?
        ");

        if (!$stmt) {
            error_log("Prepare statement failed: " . $this->db->error);
            return null;
        }

        $stmt->bind_param("i", $leadId);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get payment summary by booking ID
     */
    public function getPaymentSummaryByBookingId($bookingId)
    {
        $clientDetails = $this->getClientByBookingId($bookingId);

        if (!$clientDetails) {
            return [
                'total_price'                          => 0,
                'agreement_value'                      => 0,
                'total_land_share'                     => 0,
                'flat_development_charges'             => 0,
                'service_charge_per_emi'               => 0,
                'booking_money'                        => 0,
                'booking_money_paid'                   => 0,
                'is_booking_fully_paid'                => false,
                'additional_land_share_paid'           => 0,
                'total_land_share_paid'                => 0,
                'landshare_paid'                       => 0,
                'remaining_land_share'                 => 0,
                'discount_amount'                      => 0,
                'registration_paid'                    => 0,
                'registration_money'                   => 0,
                'is_registration_paid'                 => false,
                'is_land_share_completed'              => false,
                'is_dev_charge_completed'              => false,
                'development_charge_paid'              => 0,
                'construction_fee'                     => 0,
                'per_emi_amount'                       => 0,
                'construction_fee_paid'                => 0,
                'construction_fee_paid_after_service'  => 0,
                'remaining_construction_fee'           => 0,
                'total_paid'                           => 0,
                'total_due'                            => 0,
                'total_emi_due'                        => 0,
                'total_emi'                            => 0,
                'completed_emi'                        => 0,
                'pending_emi'                          => 0,
                'last_payment_date'                    => null,
                'total_service_fee'                    => 0,
                'net_received'                         => 0,
                'paid_emi_numbers'                     => [],
                'pending_emi_numbers'                  => []
            ];
        }

        // agreement_value is the contractual booking target from lead_bookings
        $agreementValue       = (float) ($clientDetails['agreement_value']      ?? 0);
        $discountForBooking   = (float) ($clientDetails['discount_amount']       ?? 0);
        // booking_money_total: non-rejected entries (pending + verified) — used to compute remaining balance shown in form
        $bookingMoneyTotal   = (float) ($clientDetails['booking_money_total']  ?? 0);
        // booking_money_paid: VERIFIED-ONLY entries — the real "confirmed received" amount
        $bookingMoneyPaid    = (float) ($clientDetails['booking_money_paid']   ?? 0);
        // Effective booking target: discount reduces how much the client must physically pay
        $effectiveBookingTarget = max(0, $agreementValue - $discountForBooking);
        // Booking is fully paid when verified-only payments cover the effective target (after discount)
        // We add a 1.0 TK tolerance to avoid 0.01 float issues locking the system
        $isBookingFullyPaid  = ($effectiveBookingTarget > 0 && $bookingMoneyPaid >= ($effectiveBookingTarget - 1.0));
        // Also consider fully paid if no agreement set at all
        if ($effectiveBookingTarget <= 0 && $agreementValue <= 0) {
            $isBookingFullyPaid = false; // no agreement set, assume not paid
        }

        return [
            'total_price'                          => $clientDetails['total_price'],
            'agreement_value'                      => $agreementValue,
            'total_land_share'                     => $clientDetails['total_land_share'],
            'flat_development_charges'             => $clientDetails['flat_development_charges'] ?? 0,
            'service_charge_per_emi'               => $clientDetails['service_charge_per_emi'] ?? 0,
            'booking_money'                        => $clientDetails['booking_money'],       // verified only (stat cards)
            'booking_money_total'                  => $bookingMoneyTotal,                    // all entries (dropdown remaining)
            'booking_money_paid'                   => $bookingMoneyPaid,                     // verified only
            'effective_booking_target'             => $effectiveBookingTarget,               // agreement - discount
            'is_booking_fully_paid'                => $isBookingFullyPaid,
            'additional_land_share_paid'           => $clientDetails['additional_land_share_paid'],
            'total_land_share_paid'                => $clientDetails['total_land_share_paid'],  // booking+landshare+registration
            'landshare_paid'                       => $clientDetails['landshare_paid'],          // alias
            'remaining_land_share'                 => $clientDetails['remaining_land_share'],
            'discount_amount'                      => $clientDetails['discount_amount'],
            'registration_paid'                    => $clientDetails['registration_paid'],
            'registration_money'                   => $clientDetails['registration_paid'],
            'is_registration_paid'                 => $clientDetails['is_registration_paid'] ?? false,
            'is_land_share_completed'              => $clientDetails['is_land_share_completed'],
            'is_dev_charge_completed'              => $clientDetails['is_dev_charge_completed'] ?? false,
            'development_charge_paid'              => $clientDetails['development_charge_paid'] ?? 0,
            'development_charge_paid_total'        => $clientDetails['development_charge_paid_total'] ?? 0,
            'construction_fee'                     => $clientDetails['construction_fee'],
            'per_emi_amount'                       => $clientDetails['per_emi_amount'] ?? 0,
            'construction_fee_paid'                => $clientDetails['construction_fee_paid'],
            'emi_paid_total'                       => $clientDetails['emi_paid_total'] ?? 0,
            'construction_fee_paid_after_service'  => $clientDetails['construction_fee_paid_after_service'],
            'remaining_construction_fee'           => $clientDetails['remaining_construction_fee'],
            'total_paid'                           => $clientDetails['total_paid'],
            'total_due'                            => $clientDetails['total_due'],
            'total_emi_due'                        => $clientDetails['total_emi_due'] ?? 0,
            'total_bulk_emi_discount'              => $clientDetails['total_bulk_emi_discount'] ?? 0,
            'total_emi'                            => $clientDetails['total_emi'],
            'completed_emi'                        => $clientDetails['completed_emi'],
            'pending_emi'                          => $clientDetails['pending_emi'],
            'last_payment_date'                    => $clientDetails['last_payment_date'],
            'total_service_fee'                    => $clientDetails['total_service_fee'],
            'net_received'                         => $clientDetails['net_received'],
            'paid_emi_numbers'                     => $clientDetails['paid_emi_numbers'],
            'pending_emi_numbers'                  => $clientDetails['pending_emi_numbers']
        ];
    }

    /**
     * Add new transaction with proper validations
     */
    public function addTransaction($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            // Validate required fields
            if (
                empty($transactionData['booking_id']) || empty($transactionData['payment_type']) ||
                empty($transactionData['amount']) || empty($transactionData['payment_status'])
            ) {
                throw new Exception("Missing required transaction data");
            }

            $bookingId = (int) $transactionData['booking_id'];
            $paymentType = $transactionData['payment_type'];
            $amount = (float) $transactionData['amount'];
            $landShareAmount = 0;
            $serviceFee = 0;

            // Get current payment summary for validations
            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            // Validate based on payment type
            switch ($paymentType) {
                case 'booking_money':
                    if ($summary['is_booking_fully_paid']) {
                        throw new Exception("Booking money has already been fully paid for this booking.");
                    }
                    $remainingBooking = $summary['agreement_value'] - $summary['booking_money_paid'];
                    if ($amount > $remainingBooking) {
                        throw new Exception("Amount exceeds outstanding booking balance: ৳" . number_format($remainingBooking, 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'land_share':
                    // Block land_share until the full agreed booking money has been received
                    if (empty($summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before land share payments can be recorded.");
                    }
                    if ($summary['remaining_land_share'] <= 0) {
                        throw new Exception("Land share payment is already completed!");
                    }
                    if ($amount > $summary['remaining_land_share']) {
                        throw new Exception("Land share amount cannot exceed remaining land share: ৳" . number_format($summary['remaining_land_share'], 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'registration':
                    // Registration unlocks AND locks at the same time as Land Share
                    if (empty($summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before registration payment can be recorded.");
                    }
                    if (!empty($summary['is_land_share_completed'])) {
                        throw new Exception("Registration is already completed (land share completion marks registration as complete).");
                    }
                    break;

                case 'development_charge':
                    // Dev charge requires land share to be fully completed (which also means registration is done)
                    if (empty($summary['is_land_share_completed'])) {
                        $remaining = number_format($summary['remaining_land_share'] ?? 0, 2);
                        throw new Exception("Land share (and registration) must be fully completed before development charge can be recorded. Remaining land share: ৳{$remaining}");
                    }
                    $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                    $devPaid   = (float) ($summary['development_charge_paid_total'] ?? 0);
                    $devRemaining = max(0, $devTarget - $devPaid);
                    if ($devRemaining <= 0) {
                        throw new Exception("Development charge is already fully paid!");
                    }
                    if ($amount > $devRemaining) {
                        throw new Exception("Amount exceeds remaining development charge: ৳" . number_format($devRemaining, 2));
                    }
                    break;

                case 'emi':
                    // EMI requires development charge to be fully paid (or no dev charge defined on flat)
                    if (empty($summary['is_dev_charge_completed'])) {
                        $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                        $devPaid   = (float) ($summary['development_charge_paid_total'] ?? 0);
                        throw new Exception("Development charge must be fully paid before EMI can be recorded. Paid: ৳" . number_format($devPaid, 2) . " / Required: ৳" . number_format($devTarget, 2));
                    }
                    // Service fee = amount_paid × (service_charge_per_emi% / 100)
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;

                    // Compute due: shortfall from the suggested per-EMI amount
                    // BUT if this payment covers the entire remaining CF, there is NO real due
                    $perEmiSuggested   = (float) ($summary['per_emi_amount'] ?? 0);
                    $emiPaidSoFar      = (float) ($summary['emi_paid_total'] ?? 0);
                    $cfTotal           = (float) ($summary['construction_fee'] ?? 0);
                    $bulkDisc          = (float) ($summary['total_bulk_emi_discount'] ?? 0);
                    $effectiveCFRemaining = max(0, $cfTotal - $emiPaidSoFar - $bulkDisc);

                    if ($effectiveCFRemaining <= 0) {
                        throw new Exception("Construction fee is already fully paid! No more EMIs required.");
                    }
                    if ($amount > $effectiveCFRemaining) {
                        throw new Exception("Amount exceeds remaining construction fee: ৳" . number_format($effectiveCFRemaining, 2));
                    }

                    $remainingAfterThis   = max(0, $effectiveCFRemaining - $amount);

                    if ($remainingAfterThis <= 0) {
                        // This payment completes or exceeds the construction fee — NO due
                        $dueEmiAmount = 0;
                    } else {
                        // The "expected" amount for this installment is capped at actual remaining
                        $expectedForThisEmi = ($perEmiSuggested > 0) ? min($perEmiSuggested, $effectiveCFRemaining) : 0;
                        $dueEmiAmount = ($expectedForThisEmi > 0 && $amount < $expectedForThisEmi)
                                        ? round($expectedForThisEmi - $amount)
                                        : 0;
                    }

                    // Override payment_status if partial
                    if ($dueEmiAmount > 0) {
                        $transactionData['payment_status'] = 'partially_paid';
                    }
                    if (($emiPaidSoFar + $amount) > $effectiveCFRemaining + $emiPaidSoFar) {
                        throw new Exception("EMI payment cannot exceed total construction fee: ৳" . number_format($cfTotal, 2));
                    }
                    break;

                case 'emi_due_settlement':
                    // Validate parent EMI payment
                    if (empty($transactionData['parent_payment_id'])) {
                        throw new Exception("Parent EMI payment ID is required for EMI due settlement.");
                    }
                    $parentId  = (int) $transactionData['parent_payment_id'];
                    $parentRow = $this->db->query("
                        SELECT id, booking_id, due_emi_amount FROM lead_payments
                        WHERE id = {$parentId} AND payment_type = 'emi' AND booking_id = {$bookingId}
                    ");
                    if (!$parentRow || !($parentData = $parentRow->fetch_assoc())) {
                        throw new Exception("Invalid parent EMI payment reference.");
                    }
                    $remainingDue = (float) $parentData['due_emi_amount'];
                    if ($remainingDue <= 0) {
                        throw new Exception("This EMI has no outstanding due balance.");
                    }
                    if ($amount > $remainingDue) {
                        throw new Exception("Settle amount (৳" . number_format($amount, 2) . ") cannot exceed outstanding due (৳" . number_format($remainingDue, 2) . ").");
                    }
                    // Service fee applies on the settled amount at same rate
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;
                    $dueEmiAmount      = 0; // settlement itself has no new due
                    // Change $paymentType to 'emi' to satisfy ENUM constraint, settlement_type will track the difference
                    $paymentType = 'emi';
                    break;

                default:
                    break;
            }

            // Insert payment record (verification_status defaults to 'pending' via DB default)
            $bankId            = !empty($transactionData['bank_id']) ? (int)$transactionData['bank_id'] : null;
            $attachmentPaths   = !empty($transactionData['attachment_paths']) ? json_encode($transactionData['attachment_paths']) : null;
            $parentPaymentId   = !empty($transactionData['parent_payment_id']) ? (int)$transactionData['parent_payment_id'] : null;
            $settlementType    = !empty($transactionData['settlement_type']) ? $transactionData['settlement_type'] : null;

            $stmt = $this->db->prepare("
                INSERT INTO lead_payments (
                    booking_id, payment_type, amount, land_share_amount, service_fee,
                    payment_method, payment_status, payment_date, transaction_ref,
                    bank_id, bank_name, bank_account_no, cheque_no, online_type,
                    attachment_paths, due_emi_amount, parent_payment_id, settlement_type, created_by,
                    handover_to, handover_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $bankNameLookup = '';
            if ($bankId) {
                $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', branch_name) as label FROM banks WHERE id = {$bankId}");
                if ($bRow) { $bData = $bRow->fetch_assoc(); $bankNameLookup = $bData['label'] ?? ''; }
            }

            // Extract values — bind_param requires pass-by-reference variables
            $p_method    = $transactionData['payment_method'] ?? 'cash';
            $p_status    = $transactionData['payment_status'];
            $p_date      = $transactionData['payment_date'] ?? date('Y-m-d');
            $p_txRef     = $transactionData['transaction_ref'] ?? '';
            $p_bankAccNo = $transactionData['bank_account_no'] ?? '';
            $p_chequeNo  = $transactionData['cheque_no'] ?? '';
            $p_onlineType= $transactionData['online_type'] ?? '';
            $p_createdBy = (int) ($transactionData['created_by'] ?? 0);

            $handoverToId = !empty($transactionData['handover_to']) ? (int) $transactionData['handover_to'] : null;
            $handoverStatus = $handoverToId ? 'pending' : null;

            $stmt->bind_param(
                "isdddssssisssssdisiis",
                $bookingId,
                $paymentType,
                $amount,
                $landShareAmount,
                $serviceFee,
                $p_method,
                $p_status,
                $p_date,
                $p_txRef,
                $bankId,
                $bankNameLookup,
                $p_bankAccNo,
                $p_chequeNo,
                $p_onlineType,
                $attachmentPaths,
                $dueEmiAmount,
                $parentPaymentId,
                $settlementType,
                $p_createdBy,
                $handoverToId,
                $handoverStatus
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction insert failed: " . $stmt->error);
            }

            $paymentId = $this->db->insert_id;

            // If this is an EMI due settlement, reduce the due on the original payment
            if (!empty($transactionData['settlement_type']) && $transactionData['settlement_type'] === 'emi_due_settlement' && $parentPaymentId) {
                $newDue = max(0, (float)$parentData['due_emi_amount'] - $amount);
                $newStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                $updStmt = $this->db->prepare("
                    UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?
                ");
                $updStmt->bind_param('dsi', $newDue, $newStatus, $parentPaymentId);
                $updStmt->execute();

                // Also sync emi_schedules
                $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                $schedSyncStmt->bind_param('dsi', $newDue, $newStatus, $parentPaymentId);
                $schedSyncStmt->execute();
                $schedSyncStmt->close();
            }

            if ($paymentType === 'emi' && $emiScheduleData) {
                // Ensure the backend-calculated due amount is passed to the schedule!
                $emiScheduleData['due_emi_amount'] = $dueEmiAmount;
                $this->createEMISchedule($bookingId, $paymentId, $amount, $serviceFee, $emiScheduleData);
            }

            if (!empty($handoverToId)) {
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/ApprovalRequest.php';
                }
                $approvalModel = new ApprovalRequest($this->db);
                $approvalModel->create([
                    'department' => 'Accounts',
                    'request_type' => 'Cash In Handover',
                    'title' => 'Handover Approval Request for Payment #' . $paymentId,
                    'source_table' => 'lead_payments',
                    'source_id' => $paymentId,
                    'requested_days' => 0,
                    'requested_by' => $p_createdBy,
                    'approve_role' => 'management',
                    'meta_data' => json_encode([
                        'Amount' => $amount,
                        'Payment Type' => $paymentType
                    ]),
                    'request_amount' => $amount
                ]);
            }

            $this->db->commit();

            return [
                'success'           => true,
                'message'           => 'Transaction added successfully!',
                'payment_id'        => $paymentId,
                'service_fee'       => $serviceFee,
                'land_share_amount' => $landShareAmount,
                'due_emi_amount'    => $dueEmiAmount
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get the next available EMI number for a booking.
     * Finds the first gap in the non-rejected sequence (e.g., if #5 was rejected, returns 5).
     * If no gaps, returns COUNT(non-rejected) + 1.
     */
    protected function getNextAvailableEmiNo($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT es.emi_no 
            FROM emi_schedules es
            INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id
            WHERE es.lead_booking_id = ?
            AND lp.verification_status != 'rejected'
            ORDER BY es.emi_no ASC
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();

        $usedNos = [];
        while ($row = $result->fetch_assoc()) {
            $usedNos[] = (int) $row['emi_no'];
        }

        // Find first gap: check 1, 2, 3... until we find one not in use
        for ($i = 1; $i <= count($usedNos) + 1; $i++) {
            if (!in_array($i, $usedNos)) {
                return $i;
            }
        }

        return count($usedNos) + 1;
    }

    /**
     * Delete a transaction and its associated EMI schedule row.
     * Renumbers remaining EMI schedules to eliminate gaps.
     */
    public function deleteTransactionWithCleanup($paymentId, $bookingId)
    {
        $this->db->begin_transaction();

        try {
            $paymentId = (int) $paymentId;
            $bookingId = (int) $bookingId;

            // Verify the payment exists and belongs to this booking
            $chk = $this->db->prepare("SELECT id, payment_type, parent_payment_id, amount, verification_status FROM lead_payments WHERE id = ? AND booking_id = ?");
            $chk->bind_param("ii", $paymentId, $bookingId);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();

            if (!$row) {
                throw new Exception("Transaction not found or does not belong to this booking.");
            }

            $paymentType = $row['payment_type'];

            // 0. If deleting a settlement, restore the parent EMI's outstanding due
            // Only restore if the settlement was NOT rejected (because rejection already restores it)
            if ($paymentType === 'emi_due_settlement' && !empty($row['parent_payment_id']) && $row['verification_status'] !== 'rejected') {
                $parentId = (int) $row['parent_payment_id'];
                $settlementAmount = (float) $row['amount'];

                // Restore due_emi_amount on parent and set status back to partially_paid
                $restoreStmt = $this->db->prepare("
                    UPDATE lead_payments 
                    SET due_emi_amount = due_emi_amount + ?, 
                        payment_status = 'partially_paid'
                    WHERE id = ? AND booking_id = ?
                ");
                $restoreStmt->bind_param("dii", $settlementAmount, $parentId, $bookingId);
                if (!$restoreStmt->execute()) {
                    throw new Exception("Failed to restore parent EMI due: " . $restoreStmt->error);
                }
            }

            // 1. Delete associated emi_schedule row (if EMI)
            if ($paymentType === 'emi') {
                $delSched = $this->db->prepare("DELETE FROM emi_schedules WHERE lead_payment_id = ? AND lead_booking_id = ?");
                $delSched->bind_param("ii", $paymentId, $bookingId);
                $delSched->execute();
            }

            // 2. Delete any settlement children (emi_due_settlement rows pointing to this payment)
            $delChildren = $this->db->prepare("DELETE FROM lead_payments WHERE parent_payment_id = ? AND booking_id = ?");
            $delChildren->bind_param("ii", $paymentId, $bookingId);
            $delChildren->execute();

            // 3. Delete the payment itself
            $delPay = $this->db->prepare("DELETE FROM lead_payments WHERE id = ? AND booking_id = ?");
            $delPay->bind_param("ii", $paymentId, $bookingId);
            if (!$delPay->execute()) {
                throw new Exception("Failed to delete payment: " . $delPay->error);
            }

            if ($delPay->affected_rows === 0) {
                throw new Exception("No payment was deleted. It may have already been removed.");
            }

            // 4. Renumber EMI schedules to eliminate gaps
            if ($paymentType === 'emi') {
                $allEmis = $this->db->prepare("
                    SELECT id FROM emi_schedules 
                    WHERE lead_booking_id = ? 
                    ORDER BY due_date ASC, id ASC
                ");
                $allEmis->bind_param("i", $bookingId);
                $allEmis->execute();
                $result = $allEmis->get_result();

                $newNo = 1;
                $updEmi = $this->db->prepare("UPDATE emi_schedules SET emi_no = ? WHERE id = ?");
                while ($emiRow = $result->fetch_assoc()) {
                    $emiId = (int) $emiRow['id'];
                    $updEmi->bind_param("ii", $newNo, $emiId);
                    $updEmi->execute();
                    $newNo++;
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction deleted successfully.' . ($paymentType === 'emi' ? ' EMI numbers recalculated.' : '')
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all EMI payments with outstanding due balances for a booking.
     * Used to populate the "Settle EMI Due" dropdown in the transaction form.
     */
    public function getOutstandingEmiDues($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT
                lp.id,
                lp.payment_date,
                lp.amount,
                lp.due_emi_amount,
                lp.payment_status,
                COALESCE(es.emi_no, 0) AS emi_no
            FROM lead_payments lp
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            WHERE lp.booking_id = ?
              AND lp.payment_type = 'emi'
              AND lp.due_emi_amount > 0
              AND lp.verification_status = 'verified'
              AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
              AND NOT EXISTS (
                  SELECT 1 FROM lead_payments child
                  WHERE child.parent_payment_id = lp.id
                  AND child.settlement_type = 'emi_due_settlement'
                  AND child.verification_status = 'pending'
              )
            ORDER BY lp.payment_date ASC
        ");

        if (!$stmt) {
            error_log("getOutstandingEmiDues prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Create EMI schedule
     */
    protected function createEMISchedule($bookingId, $paymentId, $emiAmount, $serviceFee, $emiScheduleData)
    {
        $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);

        $emiStmt = $this->db->prepare("
            INSERT INTO emi_schedules (
                lead_booking_id, emi_no, emi_amount, service_fee,
                due_emi_amount, due_date, status, lead_payment_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $status = 'paid';
        $dueEmiAmount = isset($emiScheduleData['due_emi_amount']) ? (float) $emiScheduleData['due_emi_amount'] : 0;
        $dueDate = isset($emiScheduleData['due_date']) ? $emiScheduleData['due_date'] : date('Y-m-d');

        $emiStmt->bind_param(
            "iidddssi",
            $bookingId,
            $nextEmiNo,
            $emiAmount,
            $serviceFee,
            $dueEmiAmount,
            $dueDate,
            $status,
            $paymentId
        );

        if (!$emiStmt->execute()) {
            throw new Exception("EMI schedule insert failed: " . $emiStmt->error);
        }
    }

    /**
     * Get transactions by booking ID
     */
    public function getTransactionsByBookingId($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lp.*,
                l.full_name as client_name,
                p.name as project_name,
                f.floor_no,
                f.flat_name as unit_name,
                CONCAT(f.flat_name, ' - FLOOR ', f.floor_no) as flat_no,
                u.full_name as created_by_name,
                uv.full_name as verified_by_name,
                uh.full_name as handover_to_name,
                es.emi_no,
                es.emi_amount,
                es.due_emi_amount,
                es.due_date as emi_due_date,
                es.status as emi_status,
                es.service_fee as emi_service_fee,
                CASE 
                    WHEN lp.settlement_type = 'emi_due_settlement' THEN 'EMI SETTLEMENT'
                    WHEN lp.payment_type = 'emi' AND es.emi_no IS NOT NULL 
                    THEN CONCAT(es.emi_no, 
                        CASE 
                            WHEN es.emi_no % 100 IN (11,12,13) THEN 'th'
                            WHEN es.emi_no % 10 = 1 THEN 'st'
                            WHEN es.emi_no % 10 = 2 THEN 'nd'
                            WHEN es.emi_no % 10 = 3 THEN 'rd'
                            ELSE 'th'
                        END, ' Installment'
                    )
                    WHEN lp.payment_type = 'registration' THEN 'REGISTRATION'
                    WHEN lp.payment_type = 'booking_money' THEN 'BOOKING'
                    WHEN lp.payment_type = 'land_share' THEN 'LAND SHARE'
                    WHEN lp.payment_type = 'construction_fee' THEN 'CONSTRUCTION'
                    WHEN lp.payment_type = 'development_charge' THEN 'DEV CHARGE'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'SHOP SVC CHARGE'
                    ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
                END as installment_label,
                CASE 
                    WHEN lp.settlement_type = 'emi_due_settlement' THEN 'EMI Due Settlement'
                    WHEN lp.payment_type = 'emi' THEN 'EMI Payment'
                    WHEN lp.payment_type = 'registration' THEN 'Registration Money'
                    WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                    WHEN lp.payment_type = 'land_share' THEN 'Land Share Payment'
                    WHEN lp.payment_type = 'construction_fee' THEN 'Construction Fee'
                    WHEN lp.payment_type = 'development_charge' THEN 'Development Charge'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'Shop Service Charge'
                    ELSE 'Other Payment'
                END as payment_details,
                b.bank_name as bank_display_name
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN users u ON u.id = lp.created_by
            LEFT JOIN users uv ON uv.id = lp.verified_by
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            LEFT JOIN banks b ON b.id = lp.bank_id
            LEFT JOIN users uh ON uh.id = lp.handover_to
            WHERE lp.booking_id = ?
            ORDER BY lp.created_at DESC, lp.id DESC
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transactions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        foreach ($transactions as &$transaction) {
            if (isset($transaction['payment_date'])) {
                $transaction['formatted_date'] = date('d M, Y', strtotime($transaction['payment_date']));
            }

            if ($transaction['payment_type'] === 'booking_money' && $transaction['land_share_amount'] == 0) {
                $transaction['land_share_amount'] = $transaction['amount'];
            }

            if (isset($transaction['emi_service_fee']) && $transaction['emi_service_fee'] > 0) {
                $transaction['service_fee'] = $transaction['emi_service_fee'];
            }

            if (isset($transaction['payment_status'])) {
                // If verified by Accounts, always show as Paid regardless of raw payment_status
                if (($transaction['verification_status'] ?? '') === 'verified') {
                    $transaction['status_class'] = 'bg-success';
                    $transaction['status_text']  = 'Paid';
                } else {
                    switch ($transaction['payment_status']) {
                        case 'paid':
                            $transaction['status_class'] = 'bg-success';
                            $transaction['status_text'] = 'Paid';
                            break;
                        case 'partially_paid':
                            $transaction['status_class'] = 'bg-info';
                            $transaction['status_text'] = 'Partially Paid';
                            break;
                        case 'pending':
                            $transaction['status_class'] = 'bg-warning';
                            $transaction['status_text'] = 'Pending';
                            break;
                        case 'failed':
                            $transaction['status_class'] = 'bg-danger';
                            $transaction['status_text'] = 'Failed';
                            break;
                        default:
                            $transaction['status_class'] = 'bg-secondary';
                            $transaction['status_text'] = ucfirst(str_replace('_', ' ', $transaction['payment_status']));
                    }
                }
            }
        }

        return $transactions;
    }


    /**
     * Get filtered transactions by booking ID with date filter
     */
    public function getFilteredTransactionsByBookingId($bookingId, $dateFilter = [])
    {
        $sql = "
            SELECT 
                lp.*,
                l.full_name as client_name,
                p.name as project_name,
                f.floor_no,
                f.flat_name as unit_name,
                CONCAT(f.flat_name, ' - FLOOR ', f.floor_no) as flat_no,
                u.full_name as created_by_name,
                es.emi_no,
                es.emi_amount,
                es.due_emi_amount,
                es.due_date as emi_due_date,
                es.status as emi_status,
                lb.discount_amount,
                lb.booking_date as booking_date,
                CASE 
                    WHEN lp.settlement_type = 'emi_due_settlement' THEN 'EMI SETTLEMENT'
                    WHEN lp.payment_type = 'emi' AND es.emi_no IS NOT NULL 
                    THEN CONCAT(es.emi_no, 
                        CASE 
                            WHEN es.emi_no % 100 IN (11,12,13) THEN 'th'
                            WHEN es.emi_no % 10 = 1 THEN 'st'
                            WHEN es.emi_no % 10 = 2 THEN 'nd'
                            WHEN es.emi_no % 10 = 3 THEN 'rd'
                            ELSE 'th'
                        END, ' Installment'
                    )
                    WHEN lp.payment_type = 'registration' THEN 'REGISTRATION'
                    WHEN lp.payment_type = 'booking_money' THEN 'BOOKING'
                    WHEN lp.payment_type = 'land_share' THEN 'LAND SHARE'
                    WHEN lp.payment_type = 'construction_fee' THEN 'CONSTRUCTION'
                    WHEN lp.payment_type = 'development_charge' THEN 'DEV CHARGE'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'SHOP SVC CHARGE'
                    ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
                END as installment_label,
                CASE 
                    WHEN lp.settlement_type = 'emi_due_settlement' THEN 'EMI Due Settlement'
                    WHEN lp.payment_type = 'emi' THEN 'EMI Payment'
                    WHEN lp.payment_type = 'registration' THEN 'Registration Money'
                    WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                    WHEN lp.payment_type = 'land_share' THEN 'Land Share Payment'
                    WHEN lp.payment_type = 'construction_fee' THEN 'Construction Fee'
                    WHEN lp.payment_type = 'development_charge' THEN 'Development Charge'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'Shop Service Charge'
                    ELSE 'Other Payment'
                END as payment_details,
                b.bank_name as bank_display_name
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN users u ON u.id = lp.created_by
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            LEFT JOIN banks b ON b.id = lp.bank_id
            WHERE lp.booking_id = ?
        ";

        $params = [$bookingId];
        $types = "i";

        if (!empty($dateFilter['from']) && !empty($dateFilter['to'])) {
            $sql .= " AND lp.payment_date BETWEEN ? AND ?";
            $params[] = $dateFilter['from'];
            $params[] = $dateFilter['to'];
            $types .= "ss";
        }

        $sql .= " ORDER BY 
            CASE 
                WHEN lp.payment_type = 'booking_money' THEN 1
                WHEN lp.payment_type = 'registration' THEN 2
                WHEN lp.payment_type = 'land_share' THEN 3
                ELSE 4
            END,
            es.emi_no ASC,
            lp.payment_date ASC
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return [];
        }

        $result = $stmt->get_result();
        $transactions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        foreach ($transactions as &$transaction) {
            if (isset($transaction['payment_date'])) {
                $transaction['formatted_date'] = date('d M, Y', strtotime($transaction['payment_date']));
            }

            if ($transaction['payment_type'] === 'booking_money' && $transaction['land_share_amount'] == 0) {
                $transaction['land_share_amount'] = $transaction['amount'];
            }

            if (isset($transaction['payment_status'])) {
                switch ($transaction['payment_status']) {
                    case 'paid':
                        $transaction['status_class'] = 'bg-success';
                        $transaction['status_text'] = 'Paid';
                        break;
                    case 'partially_paid':
                        $transaction['status_class'] = 'bg-info';
                        $transaction['status_text'] = 'Partially Paid';
                        break;
                    case 'pending':
                        $transaction['status_class'] = 'bg-warning';
                        $transaction['status_text'] = 'Pending';
                        break;
                    case 'failed':
                        $transaction['status_class'] = 'bg-danger';
                        $transaction['status_text'] = 'Failed';
                        break;
                    default:
                        $transaction['status_class'] = 'bg-secondary';
                        $transaction['status_text'] = ucfirst(str_replace('_', ' ', $transaction['payment_status']));
                }
            }
        }

        return $transactions;
    }

    /**
     * Update transaction with proper validations
     */
    public function updateTransaction($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            $paymentId = (int) $transactionData['payment_id'];

            $oldTransaction = $this->getTransactionById($paymentId);
            if (!$oldTransaction) {
                throw new Exception("Transaction not found");
            }

            $bookingId = (int) $oldTransaction['booking_id'];
            $newPaymentType = $transactionData['payment_type'];
            $newAmount = (float) $transactionData['amount'];

            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            $landShareAmount = 0;
            $serviceFee = 0;

            if ($newPaymentType === 'land_share') {
                $oldAmount = $oldTransaction['payment_type'] === 'land_share' ?
                    (float) $oldTransaction['amount'] : 0;

                $effectiveAmountChange = $newAmount - $oldAmount;
                $remainingAfterChange = $summary['remaining_land_share'] - $effectiveAmountChange;

                if ($remainingAfterChange < 0) {
                    throw new Exception("Land share amount would exceed remaining land share by: ৳" .
                        number_format(abs($remainingAfterChange), 2));
                }
                $landShareAmount = $newAmount;
            } elseif ($newPaymentType === 'emi') {
                $serviceFee = $newAmount * 0.10;
            }

            // Encode attachment_paths for storage
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }

            $stmt = $this->db->prepare("
                UPDATE lead_payments SET
                    payment_type = ?,
                    amount = ?,
                    land_share_amount = ?,
                    service_fee = ?,
                    payment_method = ?,
                    payment_status = ?,
                    payment_date = ?,
                    transaction_ref = ?,
                    bank_name = ?,
                    bank_account_no = ?,
                    cheque_no = ?,
                    online_type = ?,
                    attachment_paths = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "sdddsssssssssi",  // 14: s d d d s s s s s s s s s i
                $newPaymentType,
                $newAmount,
                $landShareAmount,
                $serviceFee,
                $transactionData['payment_method'],
                $transactionData['payment_status'],
                $transactionData['payment_date'],
                $transactionData['transaction_ref'],
                $transactionData['bank_name'],
                $transactionData['bank_account_no'],
                $transactionData['cheque_no'],
                $transactionData['online_type'],
                $attachmentPathsJson,
                $paymentId
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction update failed: " . $stmt->error);
            }

            if ($newPaymentType === 'emi' && $emiScheduleData) {
                $this->updateOrCreateEMISchedule($bookingId, $paymentId, $newAmount, $serviceFee, $emiScheduleData);
            } elseif ($oldTransaction['payment_type'] === 'emi' && $newPaymentType !== 'emi') {
                $this->deleteEMISchedule($paymentId);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction updated successfully!'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update or create EMI schedule
     */
    private function updateOrCreateEMISchedule($bookingId, $paymentId, $emiAmount, $serviceFee, $emiScheduleData)
    {
        $checkStmt = $this->db->prepare("SELECT id FROM emi_schedules WHERE lead_payment_id = ?");
        $checkStmt->bind_param("i", $paymentId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        $status = 'paid';
        $dueEmiAmount = isset($emiScheduleData['due_emi_amount']) ? (float) $emiScheduleData['due_emi_amount'] : 0;
        $dueDate = isset($emiScheduleData['due_date']) ? $emiScheduleData['due_date'] : date('Y-m-d');

        if ($existing) {
            $emiStmt = $this->db->prepare("
                UPDATE emi_schedules SET
                    emi_amount = ?,
                    service_fee = ?,
                    due_emi_amount = ?,
                    due_date = ?,
                    status = ?
                WHERE lead_payment_id = ?
            ");

            $emiStmt->bind_param(
                "dddssi",
                $emiAmount,
                $serviceFee,
                $dueEmiAmount,
                $dueDate,
                $status,
                $paymentId
            );
        } else {
            $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);

            $emiStmt = $this->db->prepare("
                INSERT INTO emi_schedules (
                    lead_booking_id, emi_no, emi_amount, service_fee,
                    due_emi_amount, due_date, status, lead_payment_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $emiStmt->bind_param(
                "iidddssi",
                $bookingId,
                $nextEmiNo,
                $emiAmount,
                $serviceFee,
                $dueEmiAmount,
                $dueDate,
                $status,
                $paymentId
            );
        }

        if (!$emiStmt->execute()) {
            throw new Exception("EMI schedule operation failed: " . $emiStmt->error);
        }
    }

    /**
     * Delete EMI schedule
     */
    private function deleteEMISchedule($paymentId)
    {
        $deleteStmt = $this->db->prepare("DELETE FROM emi_schedules WHERE lead_payment_id = ?");
        $deleteStmt->bind_param("i", $paymentId);
        $deleteStmt->execute();
    }

    /**
     * Get transaction by ID
     */
    public function getTransactionById($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT lp.*,
                   l.full_name as client_name,
                   p.name as project_name,
                   f.floor_no as flat_no,
                   f.flat_name as unit_name,
                   es.emi_no,
                   es.due_emi_amount,
                   es.service_fee as emi_service_fee,
                   es.emi_amount as emi_schedule_amount
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            WHERE lp.id = ?
        ");

        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result ? $result->fetch_assoc() : null;

        if ($transaction && isset($transaction['emi_service_fee']) && $transaction['emi_service_fee'] > 0) {
            $transaction['service_fee'] = $transaction['emi_service_fee'];
        }

        return $transaction;
    }

    /**
     * Get EMI schedule by payment ID
     */
    public function getEMIScheduleByPaymentId($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM emi_schedules 
            WHERE lead_payment_id = ?
        ");

        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Delete transaction
     */
    public function deleteTransaction($paymentId)
    {
        $this->db->begin_transaction();

        try {
            $this->deleteEMISchedule($paymentId);

            $deleteStmt = $this->db->prepare("DELETE FROM lead_payments WHERE id = ?");
            $deleteStmt->bind_param("i", $paymentId);

            if (!$deleteStmt->execute()) {
                throw new Exception("Failed to delete transaction");
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction deleted successfully!'
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if registration is paid
     */
    public function isRegistrationPaid($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM lead_payments 
            WHERE booking_id = ? 
            AND payment_type = 'registration' 
            AND payment_status = 'paid'
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    /**
     * Check if land share is completed
     */
    public function isLandShareCompleted($bookingId)
    {
        $summary = $this->getPaymentSummaryByBookingId($bookingId);
        return $summary['is_land_share_completed'];
    }

    /**
     * Get remaining land share
     */
    public function getRemainingLandShare($bookingId)
    {
        $summary = $this->getPaymentSummaryByBookingId($bookingId);
        return $summary['remaining_land_share'];
    }

    /**
     * Check if booking money exists
     */
    public function hasBookingMoneyInDatabase($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM lead_payments 
            WHERE booking_id = ? 
            AND payment_type = 'booking_money'
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function addTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            $serviceFee = 0;
            $landShareAmount = 0;
            $bookingId = (int) $transactionData['booking_id'];
            $paymentType = $transactionData['payment_type'];
            $totalAmount = (float) $transactionData['amount'];

            // ─── NON-EMI PAYMENTS: land_share, registration, etc. (unchanged) ───
            if ($paymentType !== 'emi') {
                if ($paymentType === 'land_share') {
                    $landShareAmount = $totalAmount;
                    $clientDetails = $this->getClientByBookingId($bookingId);
                    $remainingLandShare = $clientDetails['remaining_land_share'];
                    if ($remainingLandShare <= 0) {
                        throw new Exception("Land share payment is already completed!");
                    }
                    if ($landShareAmount > $remainingLandShare) {
                        throw new Exception("Land share amount cannot exceed remaining land share: ৳" . number_format($remainingLandShare, 2));
                    }
                } elseif ($paymentType === 'development_charge') {
                    $summary = $this->getPaymentSummaryByBookingId($bookingId);
                    $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                    $devPaid   = (float) ($summary['development_charge_paid_total'] ?? 0);
                    $devRemaining = max(0, $devTarget - $devPaid);
                    if ($devRemaining <= 0) {
                        throw new Exception("Development charge is already fully paid!");
                    }
                    if ($totalAmount > $devRemaining) {
                        throw new Exception("Amount exceeds remaining development charge: ৳" . number_format($devRemaining, 2));
                    }
                }

                // Encode attachment paths
                $attachmentPathsJson = null;
                if (!empty($transactionData['attachment_paths'])) {
                    $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                        ? json_encode($transactionData['attachment_paths'])
                        : $transactionData['attachment_paths'];
                }

                $stmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_name, bank_account_no, cheque_no, online_type,
                        attachment_paths, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$stmt) throw new Exception("Prepare failed: " . $this->db->error);

                $p_bookingId  = $bookingId;
                $p_type       = $paymentType;
                $p_amount     = $totalAmount;
                $p_landShare  = (float) $landShareAmount;
                $p_svcFee     = 0.0;
                $p_method     = $transactionData['payment_method']   ?? 'cash';
                $p_status     = $transactionData['payment_status']   ?? 'paid';
                $p_date       = $transactionData['payment_date']     ?? date('Y-m-d');
                $p_txRef      = $transactionData['transaction_ref']  ?? '';
                $p_bankName   = $transactionData['bank_name']        ?? '';
                $p_bankAccNo  = $transactionData['bank_account_no']  ?? '';
                $p_chequeNo   = $transactionData['cheque_no']        ?? '';
                $p_onlineType = $transactionData['online_type']      ?? '';
                $p_createdBy  = (int) $transactionData['created_by'];

                $stmt->bind_param(
                    "isdddsssssssssi",
                    $p_bookingId, $p_type, $p_amount, $p_landShare, $p_svcFee,
                    $p_method, $p_status, $p_date, $p_txRef,
                    $p_bankName, $p_bankAccNo, $p_chequeNo, $p_onlineType,
                    $attachmentPathsJson, $p_createdBy
                );

                if (!$stmt->execute()) throw new Exception("Insert failed: " . $stmt->error);

                $this->db->commit();
                return [
                    'success' => true,
                    'message' => 'Transaction added successfully!',
                    'payment_id' => $this->db->insert_id,
                    'service_fee' => 0,
                    'land_share_amount' => $landShareAmount
                ];
            }

            // ═══════════════════════════════════════════════════════════════════
            // ─── EMI PAYMENT: Smart FIFO Allocation ──────────────────────────
            // ═══════════════════════════════════════════════════════════════════

            // 0. Get payment summary for validations
            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            if (empty($summary['is_dev_charge_completed'])) {
                $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                $devPaid   = (float) ($summary['development_charge_paid'] ?? 0);
                throw new Exception("Development charge must be fully paid before EMI. Paid: ৳" . number_format($devPaid, 2) . " / Required: ৳" . number_format($devTarget, 2));
            }

            // Service charge on FULL amount (user's answer: YES)
            $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
            $totalServiceFee   = ($serviceChargeRate > 0) ? round($totalAmount * ($serviceChargeRate / 100)) : 0;

            // Construction fee cap check (accounts for existing bulk discounts)
            $constructionFee      = (float) ($summary['construction_fee'] ?? 0);
            $emiPaidSoFar         = (float) ($summary['emi_paid_total'] ?? 0);
            $perEmiAmount         = (float) ($summary['per_emi_amount'] ?? 0);
            $existingBulkDiscount = (float) ($summary['total_bulk_emi_discount'] ?? 0);
            $effectiveCF          = max(0, $constructionFee - $existingBulkDiscount);

            // Bulk discount validation
            $bulkEmiDiscount = (float) ($transactionData['bulk_emi_discount'] ?? 0);

            if ($bulkEmiDiscount > 0) {
                // Discount only allowed when paying >= 2 EMIs worth
                if ($perEmiAmount > 0 && $totalAmount < ($perEmiAmount * 2)) {
                    throw new Exception("Bulk EMI discount requires paying at least 2 EMI installments at once (minimum: ৳" . number_format($perEmiAmount * 2, 2) . ").");
                }
                // Discount cannot exceed remaining construction fee after this payment
                $remainingAfterPayment = $effectiveCF - $emiPaidSoFar - $totalAmount;
                if ($bulkEmiDiscount > $remainingAfterPayment) {
                    throw new Exception("Bulk discount (৳" . number_format($bulkEmiDiscount, 2) . ") exceeds remaining construction fee after payment (৳" . number_format(max(0, $remainingAfterPayment), 2) . "). Max discount: ৳" . number_format(max(0, $remainingAfterPayment), 2));
                }
            }

            if (($emiPaidSoFar + $totalAmount) > $effectiveCF) {
                $maxPayable = max(0, $effectiveCF - $emiPaidSoFar);
                throw new Exception("EMI payment exceeds remaining construction fee. Maximum payable: ৳" . number_format($maxPayable, 2));
            }

            // Common payment metadata
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }
            $paymentMethod  = $transactionData['payment_method']   ?? 'cash';
            $paymentDate    = $transactionData['payment_date']     ?? date('Y-m-d');
            $transactionRef = $transactionData['transaction_ref']  ?? '';
            $bankName       = $transactionData['bank_name']        ?? '';
            $bankAccNo      = $transactionData['bank_account_no']  ?? '';
            $chequeNo       = $transactionData['cheque_no']        ?? '';
            $onlineType     = $transactionData['online_type']      ?? '';
            $createdBy      = (int) ($transactionData['created_by'] ?? 1);

            $pool = $totalAmount;
            $settledDues = 0;
            $newInstallments = 0;
            $settlementDetails = [];

            // ─── PHASE 1: Auto-settle outstanding dues (FIFO — oldest first) ───
            $outstandingDues = $this->getOutstandingEmiDues($bookingId);

            foreach ($outstandingDues as $due) {
                if ($pool <= 0) break;

                $dueAmount = (float) $due['due_emi_amount'];
                $settle = min($pool, $dueAmount);

                // Service fee for this settlement portion
                $settleSvcFee = ($serviceChargeRate > 0) ? round($settle * ($serviceChargeRate / 100)) : 0;

                // Create settlement audit record
                $settleStmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_name, bank_account_no, cheque_no, online_type,
                        attachment_paths, due_emi_amount, parent_payment_id, settlement_type, created_by,
                        handover_to, handover_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$settleStmt) throw new Exception("Settlement prepare failed: " . $this->db->error);

                $stType = 'emi_due_settlement';
                $stPayType = 'emi'; // Use 'emi' for DB ENUM consistency; settlement tracked via settlement_type
                $stStatus = 'paid';
                $stLandShare = 0.0;
                $stDue = 0.0;
                $parentId = (int) $due['id'];

                $handoverToId = !empty($transactionData['handover_to']) ? (int) $transactionData['handover_to'] : null;
                $handoverStatus = $handoverToId ? 'pending' : null;

                $settleStmt->bind_param(
                    "isdddsssssssssdisiis",
                    $bookingId, $stPayType, $settle, $stLandShare, $settleSvcFee,
                    $paymentMethod, $stStatus, $paymentDate, $transactionRef,
                    $bankName, $bankAccNo, $chequeNo, $onlineType,
                    $attachmentPathsJson, $stDue, $parentId, $stType, $createdBy,
                    $handoverToId, $handoverStatus
                );

                if (!$settleStmt->execute()) throw new Exception("Settlement insert failed: " . $settleStmt->error);
                
                $settlePaymentId = $this->db->insert_id;
                
                if (!empty($handoverToId)) {
                    if (!class_exists('ApprovalRequest')) {
                        require_once __DIR__ . '/ApprovalRequest.php';
                    }
                    $approvalModel = new ApprovalRequest($this->db);
                    $approvalModel->create([
                        'department' => 'Accounts',
                        'request_type' => 'Cash In Handover',
                        'title' => 'Handover Approval Request for Payment #' . $settlePaymentId,
                        'source_table' => 'lead_payments',
                        'source_id' => $settlePaymentId,
                        'requested_days' => 0,
                        'requested_by' => $createdBy,
                        'approve_role' => 'management',
                        'meta_data' => json_encode([
                            'Amount' => $settle,
                            'Payment Type' => 'EMI Settlement'
                        ]),
                        'request_amount' => $settle
                    ]);
                }

                // Update parent's due_emi_amount in lead_payments
                $newDue = max(0, $dueAmount - $settle);
                $newParentStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                $updStmt = $this->db->prepare("UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?");
                $updStmt->bind_param('dsi', $newDue, $newParentStatus, $parentId);
                $updStmt->execute();

                // Also sync emi_schedules
                $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                $schedSyncStmt->bind_param('dsi', $newDue, $newParentStatus, $parentId);
                $schedSyncStmt->execute();
                $schedSyncStmt->close();

                $pool -= $settle;
                $settledDues++;
                $settlementDetails[] = [
                    'emi_no' => $due['emi_no'],
                    'settled' => $settle,
                    'remaining_due' => $newDue
                ];
            }

            // ─── PHASE 2: Allocate remaining pool to new installment(s) ───
            $firstPaymentId = null;
            $totalAllocated = 0;  // Track total EMI allocated in this transaction

            while ($pool > 0) {
                // How much goes to this installment
                $thisEmi = ($perEmiAmount > 0) ? min($pool, $perEmiAmount) : $pool;

                // Calculate remaining CF after ALL installments so far (including this one)
                // If this payment fully covers the remaining CF, there's no real due
                $cfRemainingBeforeThis = max(0, $effectiveCF - $emiPaidSoFar - $totalAllocated);
                $cfRemainingAfterThis = max(0, $cfRemainingBeforeThis - $thisEmi);
                if ($cfRemainingAfterThis <= 0) {
                    // Last installment — no due even if less than per EMI
                    $thisDue = 0;
                } else {
                    // Expected for this installment is capped at what's actually remaining
                    $expectedForThis = ($perEmiAmount > 0) ? min($perEmiAmount, $cfRemainingBeforeThis) : 0;
                    $thisDue = ($expectedForThis > 0 && $thisEmi < $expectedForThis)
                               ? max(0, round($expectedForThis - $thisEmi))
                               : 0;
                }
                $thisStatus = ($thisDue > 0) ? 'partially_paid' : 'paid';

                // Service fee for this installment
                $thisSvcFee = ($serviceChargeRate > 0) ? round($thisEmi * ($serviceChargeRate / 100)) : 0;

                // Get next EMI number (fills gaps from rejected entries)
                $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);

                // Insert lead_payments row for this installment
                $emiStmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_name, bank_account_no, cheque_no, online_type,
                        attachment_paths, due_emi_amount, bulk_emi_discount, bulk_pay_amount, created_by,
                        handover_to, handover_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$emiStmt) throw new Exception("EMI payment prepare failed: " . $this->db->error);

                $emiPayType = 'emi';
                $emiLandShare = 0.0;
                // Attach discount + bulk pay amount only to the FIRST installment
                $thisDiscount = (!$firstPaymentId && $bulkEmiDiscount > 0) ? $bulkEmiDiscount : 0.0;
                $thisBulkPayAmount = (!$firstPaymentId && $newInstallments === 0) ? $totalAmount : null;

                $handoverToId = !empty($transactionData['handover_to']) ? (int) $transactionData['handover_to'] : null;
                $handoverStatus = $handoverToId ? 'pending' : null;

                $emiStmt->bind_param(
                    "isdddsssssssssdddiis",
                    $bookingId, $emiPayType, $thisEmi, $emiLandShare, $thisSvcFee,
                    $paymentMethod, $thisStatus, $paymentDate, $transactionRef,
                    $bankName, $bankAccNo, $chequeNo, $onlineType,
                    $attachmentPathsJson, $thisDue, $thisDiscount, $thisBulkPayAmount, $createdBy,
                    $handoverToId, $handoverStatus
                );

                if (!$emiStmt->execute()) throw new Exception("EMI payment insert failed: " . $emiStmt->error);

                $paymentId = $this->db->insert_id;
                
                if (!empty($handoverToId)) {
                    if (!class_exists('ApprovalRequest')) {
                        require_once __DIR__ . '/ApprovalRequest.php';
                    }
                    $approvalModel = new ApprovalRequest($this->db);
                    $approvalModel->create([
                        'department' => 'Accounts',
                        'request_type' => 'Cash In Handover',
                        'title' => 'Handover Approval Request for Payment #' . $paymentId,
                        'source_table' => 'lead_payments',
                        'source_id' => $paymentId,
                        'requested_days' => 0,
                        'requested_by' => $createdBy,
                        'approve_role' => 'management',
                        'meta_data' => json_encode([
                            'Amount' => $thisEmi,
                            'Payment Type' => 'EMI'
                        ]),
                        'request_amount' => $thisEmi
                    ]);
                }
                if (!$firstPaymentId) $firstPaymentId = $paymentId;

                // Insert emi_schedule row
                $schedStmt = $this->db->prepare("
                    INSERT INTO emi_schedules (
                        lead_booking_id, emi_no, emi_amount, service_fee,
                        due_emi_amount, due_date, status, lead_payment_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$schedStmt) throw new Exception("EMI schedule prepare failed: " . $this->db->error);

                $schedStatus = ($thisDue > 0) ? 'paid' : 'paid'; // schedule row always 'paid' (payment was recorded)

                $schedStmt->bind_param(
                    "iidddssi",
                    $bookingId, $nextEmiNo, $thisEmi, $thisSvcFee,
                    $thisDue, $paymentDate, $schedStatus, $paymentId
                );

                if (!$schedStmt->execute()) throw new Exception("EMI schedule insert failed: " . $schedStmt->error);

                $pool -= $thisEmi;
                $totalAllocated += $thisEmi;
                $newInstallments++;
            }

            $this->db->commit();

            // Build result message
            $msgParts = [];
            if ($settledDues > 0) {
                $settledTotal = array_sum(array_column($settlementDetails, 'settled'));
                $msgParts[] = $settledDues . ' outstanding due(s) settled (৳' . number_format($settledTotal, 2) . ')';
            }
            if ($newInstallments > 0) {
                $msgParts[] = $newInstallments . ' new EMI installment(s) recorded';
            }
            if ($totalServiceFee > 0) {
                $msgParts[] = 'Service fee: ৳' . number_format($totalServiceFee, 2);
            }
            if ($bulkEmiDiscount > 0) {
                $msgParts[] = 'Bulk EMI discount: ৳' . number_format($bulkEmiDiscount, 2) . ' (applied after verification)';
            }

            return [
                'success'             => true,
                'message'             => 'EMI payment processed! ' . implode('. ', $msgParts) . '.',
                'payment_id'          => $firstPaymentId,
                'service_fee'         => $totalServiceFee,
                'land_share_amount'   => 0,
                'bulk_emi_discount'   => $bulkEmiDiscount,
                'settled_dues'        => $settledDues,
                'new_installments'    => $newInstallments,
                'settlement_details'  => $settlementDetails
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }

    public function updateTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        error_log("=== MODEL: updateTransactionWithEMI START ===");
        error_log("Transaction Data: " . print_r($transactionData, true));
        error_log("EMI Schedule Data: " . print_r($emiScheduleData, true));

        $this->db->begin_transaction();

        try {
            $oldTransaction = $this->getTransactionById($transactionData['payment_id']);
            if (!$oldTransaction) {
                throw new Exception("Transaction not found");
            }

            $finalAmount = (float) $transactionData['amount'];
            $finalServiceFee = 0;
            $landShareAmount = 0;

            if ($transactionData['payment_type'] === 'land_share') {
                $landShareAmount = $finalAmount;
            } elseif ($transactionData['payment_type'] === 'booking_money') {
                $landShareAmount = $finalAmount;
            }

            if ($transactionData['payment_type'] === 'emi') {
                $finalServiceFee = $finalAmount * 0.10;
            }

            // Encode attachment paths
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }

            $stmt = $this->db->prepare("
                UPDATE lead_payments SET
                    payment_type = ?,
                    amount = ?,
                    land_share_amount = ?,
                    payment_method = ?,
                    payment_status = ?,
                    payment_date = ?,
                    transaction_ref = ?,
                    bank_name = ?,
                    bank_account_no = ?,
                    cheque_no = ?,
                    online_type = ?,
                    attachment_paths = ?
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            // Extract to plain variables — bind_param needs pass-by-reference
            $u_paymentType  =         $transactionData['payment_type'];
            $u_amount       = (float) $finalAmount;
            $u_landShare    = (float) $landShareAmount;
            $u_method       =         $transactionData['payment_method']   ?? 'cash';
            $u_status       =         $transactionData['payment_status']   ?? 'paid';
            $u_date         =         $transactionData['payment_date']     ?? date('Y-m-d');
            $u_txRef        =         $transactionData['transaction_ref']  ?? '';
            $u_bankName     =         $transactionData['bank_name']        ?? '';
            $u_bankAccNo    =         $transactionData['bank_account_no']  ?? '';
            $u_chequeNo     =         $transactionData['cheque_no']        ?? '';
            $u_onlineType   =         $transactionData['online_type']      ?? '';
            $u_paymentId    = (int)   $transactionData['payment_id'];

            $stmt->bind_param(
                "sddsssssssssi",  // 13: s d d s s s s s s s s s i
                $u_paymentType,
                $u_amount,
                $u_landShare,
                $u_method,
                $u_status,
                $u_date,
                $u_txRef,
                $u_bankName,
                $u_bankAccNo,
                $u_chequeNo,
                $u_onlineType,
                $attachmentPathsJson,
                $u_paymentId
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction update failed: " . $stmt->error);
            }

            error_log("Payment updated successfully. Land share amount: " . $landShareAmount);

            if ($transactionData['payment_type'] === 'emi' && $emiScheduleData) {
                $checkStmt = $this->db->prepare("
                    SELECT id FROM emi_schedules WHERE lead_payment_id = ?
                ");
                $checkStmt->bind_param("i", $transactionData['payment_id']);
                $checkStmt->execute();
                $existingSchedule = $checkStmt->get_result()->fetch_assoc();

                $status = $transactionData['payment_status'] === 'paid' ? 'paid' : ($transactionData['payment_status'] === 'partially_paid' ? 'partially_paid' : 'pending');

                if ($existingSchedule) {
                    $emiStmt = $this->db->prepare("
                        UPDATE emi_schedules SET
                            emi_amount = ?,
                            service_fee = ?,
                            due_emi_amount = ?,
                            due_date = ?,
                            status = ?
                        WHERE lead_payment_id = ?
                    ");

                    $emiStmt->bind_param(
                        "dddssi",
                        $finalAmount,
                        $finalServiceFee,
                        $emiScheduleData['due_emi_amount'],
                        $transactionData['payment_date'],
                        $status,
                        $transactionData['payment_id']
                    );

                    if (!$emiStmt->execute()) {
                        throw new Exception("EMI schedule update failed: " . $emiStmt->error);
                    }
                } else {
                    $nextEmiNo = $this->getNextAvailableEmiNo($transactionData['booking_id']);

                    $emiStmt = $this->db->prepare("
                        INSERT INTO emi_schedules (
                            lead_booking_id, emi_no, emi_amount, service_fee,
                            due_emi_amount, due_date, status, lead_payment_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $emiStmt->bind_param(
                        "iidddssi",
                        $transactionData['booking_id'],
                        $nextEmiNo,
                        $finalAmount,
                        $finalServiceFee,
                        $emiScheduleData['due_emi_amount'],
                        $transactionData['payment_date'],
                        $status,
                        $transactionData['payment_id']
                    );

                    if (!$emiStmt->execute()) {
                        throw new Exception("EMI schedule insert failed: " . $emiStmt->error);
                    }
                }
            } elseif ($transactionData['payment_type'] !== 'emi') {
                $deleteEmiStmt = $this->db->prepare("
                    DELETE FROM emi_schedules WHERE lead_payment_id = ?
                ");
                $deleteEmiStmt->bind_param("i", $transactionData['payment_id']);
                $deleteEmiStmt->execute();
            }

            $this->db->commit();
            error_log("=== MODEL: Transaction committed successfully ===");

            return [
                'success' => true,
                'message' => 'Transaction updated successfully!' .
                    ($finalServiceFee > 0 ? ' Service fee: ৳' . number_format($finalServiceFee, 2) : '')
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("=== MODEL: ERROR - " . $e->getMessage() . " ===");
            return [
                'success' => false,
                'message' => 'Transaction update failed: ' . $e->getMessage()
            ];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CED Dashboard — Project Filter & Flat Statistics
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all projects that have at least one flat (for CED filter dropdown)
     */
    public function getProjectsForCED()
    {
        $sql = $this->db->prepare("
            SELECT p.id, p.name
            FROM projects p
            WHERE p.deleted_at IS NULL
            ORDER BY p.name ASC
        ");

        if (!$sql) {
            error_log("getProjectsForCED prepare failed: " . $this->db->error);
            return [];
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get flat statistics for a specific project
     * Returns: total_flats, booked_flats, available_flats, sold_flats
     */
    public function getProjectFlatStats($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_flats,
                SUM(CASE WHEN f.status = 'booked' OR f.is_booked = 1 THEN 1 ELSE 0 END) as booked_flats,
                SUM(CASE WHEN f.status = 'available' AND f.is_booked = 0 THEN 1 ELSE 0 END) as available_flats,
                SUM(CASE WHEN f.status = 'sold' THEN 1 ELSE 0 END) as sold_flats
            FROM flats f
            WHERE f.project_id = ?
            AND f.deleted_at IS NULL
        ");

        if (!$stmt) {
            error_log("getProjectFlatStats prepare failed: " . $this->db->error);
            return ['total_flats' => 0, 'booked_flats' => 0, 'available_flats' => 0, 'sold_flats' => 0];
        }

        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : ['total_flats' => 0, 'booked_flats' => 0, 'available_flats' => 0, 'sold_flats' => 0];
    }

    /**
     * Get booked flats list for a project (with client info)
     */
    public function getBookedFlatsByProject($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT
                f.id as flat_id,
                f.flat_name,
                f.floor_no,
                f.sqft,
                f.price_value,
                f.status as flat_status,
                lb.id as booking_id,
                lb.booking_date,
                lb.agreement_value,
                lb.booking_status,
                lb.landshare_status,
                l.full_name as client_name,
                l.phone as client_phone,
                COALESCE(
                    (SELECT SUM(lp.amount)
                     FROM lead_payments lp
                     WHERE lp.booking_id = lb.id
                     AND lp.payment_status IN ('paid','partially_paid')), 0
                ) as total_paid,
                (SELECT GROUP_CONCAT(DISTINCT u_agent.full_name SEPARATOR ', ')
                 FROM lead_payments lp_sub
                 INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
                 WHERE lp_sub.booking_id = lb.id AND lp_sub.payment_type = 'booking_money'
                ) AS agent_name
            FROM flats f
            INNER JOIN lead_bookings lb ON lb.flat_id = f.id AND lb.booking_status != 'cancelled'
            INNER JOIN leads l ON l.id = lb.lead_id
            WHERE f.project_id = ?
            AND f.deleted_at IS NULL
            ORDER BY f.floor_no ASC, f.flat_name ASC
        ");

        if (!$stmt) {
            error_log("getBookedFlatsByProject prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get available flats list for a project
     */
    public function getAvailableFlatsByProject($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT
                f.id as flat_id,
                f.flat_name,
                f.floor_no,
                f.sqft,
                f.price_value,
                f.status as flat_status
            FROM flats f
            WHERE f.project_id = ?
            AND f.deleted_at IS NULL
            AND f.status = 'available'
            AND f.is_booked = 0
            ORDER BY f.floor_no ASC, f.flat_name ASC
        ");

        if (!$stmt) {
            error_log("getAvailableFlatsByProject prepare failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get accurate collection statistics for the CED dashboard
     */
    public function getDashboardCollectionStats()
    {
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');

        $stats = [
            'this_month_collection' => 0,
            'monthly_data' => [],
            'weekly_data' => array_fill(0, 7, 0)
        ];

        // This Month Collection
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM lead_payments
            WHERE payment_status = 'paid'
            AND payment_date BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $currentMonthStart, $currentMonthEnd);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $stats['this_month_collection'] = floatval($row['total']);
        }
        $stmt->close();

        // Monthly Data (last 6 months)
        $sixMonthsAgo = date('Y-m-01', strtotime('-5 months'));
        $stmt2 = $this->db->prepare("
            SELECT 
                DATE_FORMAT(payment_date, '%Y-%m') as month,
                SUM(amount) as total
            FROM lead_payments
            WHERE payment_status = 'paid'
            AND payment_date >= ?
            GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ");
        $stmt2->bind_param("s", $sixMonthsAgo);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($res2) {
            while ($row = $res2->fetch_assoc()) {
                $stats['monthly_data'][$row['month']] = floatval($row['total']);
            }
        }
        $stmt2->close();

        // Weekly Data (last 7 days, 0 is today, 6 is 6 days ago)
        $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
        $stmt3 = $this->db->prepare("
            SELECT 
                DATE(payment_date) as day_date,
                SUM(amount) as total
            FROM lead_payments
            WHERE payment_status = 'paid'
            AND payment_date >= ?
            GROUP BY DATE(payment_date)
        ");
        $stmt3->bind_param("s", $sevenDaysAgo);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        if ($res3) {
            while ($row = $res3->fetch_assoc()) {
                $daysSince = (strtotime(date('Y-m-d')) - strtotime($row['day_date'])) / 86400;
                $idx = max(0, min(6, 6 - (int)floor($daysSince)));
                $stats['weekly_data'][$idx] += floatval($row['total']);
            }
        }
        $stmt3->close();

        return $stats;
    }

    public function saveTransferNote($bookingId, $transferDate, $transferNote, $transferStatus, $updatedBy)
    {
        $stmt = $this->db->prepare("
            UPDATE lead_bookings 
            SET transfer_date = ?, 
                transfer_note = ?, 
                transfer_status = ?, 
                transfer_updated_by = ?
            WHERE id = ?
        ");
        
        if (!$stmt) {
            error_log("Prepare statement failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param("ssiii", $transferDate, $transferNote, $transferStatus, $updatedBy, $bookingId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ─── CSV BULK IMPORT METHODS ──────────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Resolve a booking by client phone, project name, and flat name.
     * Returns booking details or null if not found.
     * Handles: phone in both `phone` and `phone_number` columns,
     * flat names with/without floor suffix (e.g. "6 B - FLOOR 6" → "6 B").
     */
    public function resolveBookingByProjectAndFlat($clientPhone, $projectName, $flatName)
    {
        // Normalize phone: strip all non-digits
        $phone = preg_replace('/[^0-9]/', '', $clientPhone);
        
        // Build multiple phone variants for flexible matching
        // Raw: 8801875293008, without 880: 1875293008, with leading 0: 01875293008
        $phoneVariants = [$phone];
        $shortPhone = $phone;
        if (str_starts_with($shortPhone, '880')) {
            $shortPhone = substr($shortPhone, 3);
            $phoneVariants[] = $shortPhone;           // 1875293008
            $phoneVariants[] = '0' . $shortPhone;     // 01875293008
        }
        if (str_starts_with($shortPhone, '0')) {
            $shortPhone = substr($shortPhone, 1);
            $phoneVariants[] = $shortPhone;
        }
        // Use the shortest meaningful digits for LIKE
        $phoneLike = '%' . $shortPhone;

        // Normalize flat name: strip floor suffix like " - FLOOR 6", " - Floor 6"
        $cleanFlatName = trim($flatName);
        $flatNameBase = $cleanFlatName;
        if (preg_match('/^(.+?)\s*[-–]\s*(FLOOR|Floor|floor)\s*\d+/i', $cleanFlatName, $fMatch)) {
            $flatNameBase = trim($fMatch[1]);
        }

        // Try exact flat name first, then base name
        $flatNamesToTry = [$cleanFlatName];
        if ($flatNameBase !== $cleanFlatName) {
            $flatNamesToTry[] = $flatNameBase;
        }

        foreach ($flatNamesToTry as $tryFlatName) {
            $stmt = $this->db->prepare("
                SELECT 
                    lb.id AS booking_id,
                    lb.project_id,
                    lb.flat_id,
                    lb.lead_id,
                    l.full_name AS client_name,
                    COALESCE(l.phone, l.phone_number) AS client_phone,
                    p.name AS project_name,
                    f.flat_name,
                    f.floor_no,
                    lb.booking_status
                FROM lead_bookings lb
                INNER JOIN leads l ON l.id = lb.lead_id
                INNER JOIN projects p ON p.id = lb.project_id
                INNER JOIN flats f ON f.id = lb.flat_id
                WHERE lb.deleted_at IS NULL
                  AND l.deleted_at IS NULL
                  AND LOWER(TRIM(p.name)) = LOWER(TRIM(?))
                  AND LOWER(TRIM(f.flat_name)) = LOWER(TRIM(?))
                  AND (
                      REPLACE(REPLACE(REPLACE(COALESCE(l.phone,''), ' ', ''), '-', ''), '+', '') LIKE ?
                      OR REPLACE(REPLACE(REPLACE(COALESCE(l.phone_number,''), ' ', ''), '-', ''), '+', '') LIKE ?
                  )
                ORDER BY lb.created_at DESC
                LIMIT 1
            ");

            if (!$stmt) {
                error_log("resolveBookingByProjectAndFlat prepare failed: " . $this->db->error);
                return null;
            }

            $stmt->bind_param("ssss", $projectName, $tryFlatName, $phoneLike, $phoneLike);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($row) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Parse and validate a CSV file for bulk payment import.
     * Returns ['valid_rows' => [...], 'errors' => [...], 'summary' => [...]]
     */
    public function parseAndValidateCSV($filePath)
    {
        $validRows = [];
        $errors = [];
        $summary = [
            'total_rows' => 0,
            'valid_count' => 0,
            'error_count' => 0,
            'total_amount' => 0,
            'dev_charge_count' => 0,
            'emi_count' => 0
        ];

        $expectedHeaders = [
            'client_name', 'client_phone', 'project_name', 'flat_name',
            'amount', 'payment_date', 'payment_type', 'payment_method',
            'verification_status', 'transaction_ref',
            'bank_name', 'bank_account_no', 'cheque_no', 'online_type',
            'payment_details', 'bulk_emi_discount', 'emi_due_date', 'emi_no',
            'service_fee'
        ];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $errors[] = ['row' => 0, 'message' => 'CSV file not found or not readable.'];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $errors[] = ['row' => 0, 'message' => 'Failed to open CSV file.'];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        // Read and validate headers (handle BOM)
        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            $errors[] = ['row' => 0, 'message' => 'CSV file is empty.'];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        // Remove BOM if present
        $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headerRow[0]);
        $headers = array_map(function ($h) {
            return strtolower(trim($h));
        }, $headerRow);

        // Check required headers
        $requiredHeaders = ['client_name', 'client_phone', 'project_name', 'flat_name',
                            'amount', 'payment_date', 'payment_type', 'payment_method'];
        $missingHeaders = array_diff($requiredHeaders, $headers);
        if (!empty($missingHeaders)) {
            fclose($handle);
            $errors[] = ['row' => 0, 'message' => 'Missing required columns: ' . implode(', ', $missingHeaders)];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        $rowNum = 1; // 1-based (header is row 0)
        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            $summary['total_rows']++;

            // Skip completely empty rows
            if (count(array_filter($data, fn($v) => trim($v) !== '')) === 0) {
                continue;
            }

            // Map CSV columns to associative array
            $row = [];
            foreach ($headers as $idx => $header) {
                $row[$header] = isset($data[$idx]) ? trim($data[$idx]) : '';
            }

            $rowErrors = [];

            // === REQUIRED FIELD VALIDATION ===
            if (empty($row['client_name'])) $rowErrors[] = 'client_name is required';
            if (empty($row['client_phone'])) $rowErrors[] = 'client_phone is required';
            if (empty($row['project_name'])) $rowErrors[] = 'project_name is required';
            if (empty($row['flat_name'])) $rowErrors[] = 'flat_name is required';

            // Strip thousand separators (commas) from amount before validation
            if (!empty($row['amount'])) {
                $row['amount'] = str_replace(',', '', $row['amount']);
            }
            if (empty($row['amount']) || !is_numeric($row['amount']) || (float)$row['amount'] <= 0) {
                $rowErrors[] = 'amount must be a positive number';
            }

            // Date validation (supports Y-m-d, d/m/Y, d-m-Y)
            $parsedDate = null;
            if (!empty($row['payment_date'])) {
                $parsedDate = $this->parseCSVDate($row['payment_date']);
                if (!$parsedDate) {
                    $rowErrors[] = 'payment_date is invalid (use YYYY-MM-DD or DD/MM/YYYY)';
                }
            } else {
                $rowErrors[] = 'payment_date is required';
            }

            // Payment type validation
            $validPaymentTypes = ['development_charge', 'emi', 'emi_due_settlement'];
            if (empty($row['payment_type']) || !in_array(strtolower($row['payment_type']), $validPaymentTypes)) {
                $rowErrors[] = 'payment_type must be: development_charge, emi, or emi_due_settlement';
            }

            // Payment method validation
            $validMethods = ['cash', 'bank', 'online', 'cheque'];
            if (empty($row['payment_method']) || !in_array(strtolower($row['payment_method']), $validMethods)) {
                $rowErrors[] = 'payment_method must be: cash, bank, online, or cheque';
            }

            // Verification status: optional, defaults to 'pending' if blank
            $verificationStatus = 'pending';
            if (!empty($row['verification_status'])) {
                $vs = strtolower($row['verification_status']);
                if (in_array($vs, ['pending', 'verified', 'rejected'])) {
                    $verificationStatus = $vs;
                }
            }

            // === BOOKING RESOLUTION ===
            $bookingData = null;
            if (empty($rowErrors)) {
                $bookingData = $this->resolveBookingByProjectAndFlat(
                    $row['client_phone'],
                    $row['project_name'],
                    $row['flat_name']
                );
                if (!$bookingData) {
                    $rowErrors[] = "No booking found for phone '{$row['client_phone']}' + project '{$row['project_name']}' + flat '{$row['flat_name']}'";
                }
            }

            // === OPTIONAL FIELD VALIDATION ===
            $bulkDiscount = 0;
            if (!empty($row['bulk_emi_discount'])) {
                if (!is_numeric($row['bulk_emi_discount']) || (float)$row['bulk_emi_discount'] < 0) {
                    $rowErrors[] = 'bulk_emi_discount must be a non-negative number';
                } else {
                    $bulkDiscount = (float)$row['bulk_emi_discount'];
                }
            }

            $emiNo = null;
            if (!empty($row['emi_no'])) {
                if (!is_numeric($row['emi_no']) || (int)$row['emi_no'] <= 0) {
                    $rowErrors[] = 'emi_no must be a positive integer';
                } else {
                    $emiNo = (int)$row['emi_no'];
                }
            }

            $emiDueDate = null;
            if (!empty($row['emi_due_date'])) {
                $emiDueDate = $this->parseCSVDate($row['emi_due_date']);
                if (!$emiDueDate) {
                    $rowErrors[] = 'emi_due_date is invalid (use YYYY-MM-DD or DD/MM/YYYY)';
                }
            }

            // Service fee validation (optional — auto-calc if empty)
            $serviceFee = null;
            if (isset($row['service_fee']) && $row['service_fee'] !== '') {
                if (!is_numeric($row['service_fee']) || (float)$row['service_fee'] < 0) {
                    $rowErrors[] = 'service_fee must be a non-negative number';
                } else {
                    $serviceFee = (float)$row['service_fee'];
                }
            }

            // Bank resolution
            $bankId = null;
            if (!empty($row['bank_name'])) {
                $bankResult = $this->db->query("SELECT id FROM banks WHERE LOWER(bank_name) LIKE '%" . $this->db->real_escape_string(strtolower($row['bank_name'])) . "%' AND is_active = 1 LIMIT 1");
                if ($bankResult && $bankRow = $bankResult->fetch_assoc()) {
                    $bankId = (int)$bankRow['id'];
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = [
                    'row' => $rowNum,
                    'data' => $row,
                    'messages' => $rowErrors
                ];
                $summary['error_count']++;
            } else {
                $paymentType = strtolower($row['payment_type']);
                $validRows[] = [
                    'row_num'             => $rowNum,
                    'booking_id'          => $bookingData['booking_id'],
                    'lead_id'             => $bookingData['lead_id'],
                    'project_id'          => $bookingData['project_id'],
                    'flat_id'             => $bookingData['flat_id'],
                    'client_name'         => $row['client_name'],
                    'client_phone'        => $row['client_phone'],
                    'resolved_client'     => $bookingData['client_name'],
                    'project_name'        => $row['project_name'],
                    'flat_name'           => $row['flat_name'],
                    'amount'              => (float)$row['amount'],
                    'payment_date'        => $parsedDate,
                    'payment_type'        => $paymentType,
                    'payment_method'      => strtolower($row['payment_method']),
                    'verification_status' => $verificationStatus,
                    'transaction_ref'     => $row['transaction_ref'] ?? '',
                    'bank_name'           => $row['bank_name'] ?? '',
                    'bank_id'             => $bankId,
                    'bank_account_no'     => $row['bank_account_no'] ?? '',
                    'cheque_no'           => $row['cheque_no'] ?? '',
                    'online_type'         => $row['online_type'] ?? '',
                    'payment_details'     => $row['payment_details'] ?? '',
                    'bulk_emi_discount'   => $bulkDiscount,
                    'emi_due_date'        => $emiDueDate,
                    'emi_no'              => $emiNo,
                    'service_fee'         => $serviceFee
                ];
                $summary['valid_count']++;
                $summary['total_amount'] += (float)$row['amount'];
                if ($paymentType === 'development_charge') $summary['dev_charge_count']++;
                if ($paymentType === 'emi') $summary['emi_count']++;
                if ($paymentType === 'emi_due_settlement') $summary['emi_count']++;
            }
        }

        fclose($handle);
        return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
    }

    /**
     * Parse various date formats from CSV.
     * Supports: Y-m-d, d/m/Y, d-m-Y, m/d/Y
     */
    private function parseCSVDate($dateStr)
    {
        $dateStr = trim($dateStr);
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];
        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $dateStr);
            if ($dt && $dt->format($fmt) === $dateStr) {
                return $dt->format('Y-m-d');
            }
        }
        // Try strtotime as fallback
        $ts = strtotime($dateStr);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
        return null;
    }

    /**
     * Process a single validated CSV import row.
     * Inserts directly into lead_payments (and emi_schedules for EMI type).
     * Supports custom verification_status, emi_no, and bypasses sequential validation
     * since this is for historical data import.
     */
    public function processCSVImportRow($rowData, $createdBy)
    {
        $this->db->begin_transaction();

        try {
            $bookingId        = (int)$rowData['booking_id'];
            $paymentType      = $rowData['payment_type'];
            $amount           = (float)str_replace(',', '', $rowData['amount']);
            $paymentMethod    = $rowData['payment_method'];
            $paymentStatus    = 'paid'; // Auto-calculated by backend
            $verificationStatus = $rowData['verification_status'];
            $paymentDate      = $rowData['payment_date'];
            $transactionRef   = $rowData['transaction_ref'];
            $bankId           = $rowData['bank_id'];
            $bankName         = $rowData['bank_name'];
            $bankAccountNo    = $rowData['bank_account_no'];
            $chequeNo         = $rowData['cheque_no'];
            $onlineType       = $rowData['online_type'];
            $bulkDiscount     = (float)$rowData['bulk_emi_discount'];

            // Document/attachment from CSV (just filename, stored under uploads/transactions/)
            $attachmentPaths = null;
            $docFilename = trim($rowData['document'] ?? '');
            if (!empty($docFilename)) {
                $attachmentPaths = json_encode(['uploads/transactions/' . $docFilename]);
            }

            $landShareAmount  = 0;
            $dueEmiAmount     = 0;
            $parentPaymentId  = null;
            $settlementType   = null;
            $isSettlement     = ($paymentType === 'emi_due_settlement');

            // Service fee: use CSV value if provided, otherwise auto-calculate
            $serviceFee = 0;
            $csvServiceFee = $rowData['service_fee'] ?? null;

            // === OVERPAYMENT BOUNDARY CHECKS ===
            if ($paymentType === 'development_charge') {
                $summary = $this->getPaymentSummaryByBookingId($bookingId);
                $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                $devPaid   = (float) ($summary['development_charge_paid_total'] ?? 0);
                $devRemaining = max(0, $devTarget - $devPaid);
                if ($devRemaining <= 0) {
                    throw new Exception("Development charge is already fully paid! Cannot accept more payments.");
                }
                if ($amount > $devRemaining) {
                    throw new Exception("Amount exceeds remaining development charge: TK." . number_format($devRemaining, 2));
                }
            } elseif ($paymentType === 'emi') {
                $summary = $this->getPaymentSummaryByBookingId($bookingId);
                $cfTotal           = (float) ($summary['construction_fee'] ?? 0);
                $emiPaidSoFar      = (float) ($summary['emi_paid_total'] ?? 0);
                $bulkDisc          = (float) ($summary['total_bulk_emi_discount'] ?? 0);
                $effectiveCFRemaining = max(0, $cfTotal - $emiPaidSoFar - $bulkDisc);

                if ($effectiveCFRemaining <= 0) {
                    throw new Exception("Construction fee is already fully paid! No more EMIs required.");
                }
                if ($amount > $effectiveCFRemaining) {
                    throw new Exception("Amount exceeds remaining construction fee: TK." . number_format($effectiveCFRemaining, 2));
                }
            }

            // === EMI DUE SETTLEMENT HANDLING ===
            if ($isSettlement) {
                // Find the parent EMI with outstanding due (by emi_no first, then FIFO fallback)
                $emiNoTarget = $rowData['emi_no'] ?? null;
                $parentData = null;

                // Try by emi_no first if specified
                if ($emiNoTarget) {
                    $findStmt = $this->db->prepare("
                        SELECT lp.id, lp.due_emi_amount, es.emi_no
                        FROM lead_payments lp
                        INNER JOIN emi_schedules es ON es.lead_payment_id = lp.id
                        WHERE lp.booking_id = ? AND lp.payment_type = 'emi'
                          AND lp.due_emi_amount > 0 AND es.emi_no = ?
                          AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                        LIMIT 1
                    ");
                    $findStmt->bind_param("ii", $bookingId, $emiNoTarget);
                    $findStmt->execute();
                    $findResult = $findStmt->get_result();
                    $parentData = $findResult ? $findResult->fetch_assoc() : null;
                    $findStmt->close();
                }

                // Fallback to FIFO if emi_no not found or not specified
                if (!$parentData) {
                    $findStmt = $this->db->prepare("
                        SELECT lp.id, lp.due_emi_amount, COALESCE(es.emi_no, 0) AS emi_no
                        FROM lead_payments lp
                        LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
                        WHERE lp.booking_id = ? AND lp.payment_type = 'emi'
                          AND lp.due_emi_amount > 0
                          AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                        ORDER BY lp.payment_date ASC
                        LIMIT 1
                    ");
                    $findStmt->bind_param("i", $bookingId);
                    $findStmt->execute();
                    $findResult = $findStmt->get_result();
                    $parentData = $findResult ? $findResult->fetch_assoc() : null;
                    $findStmt->close();
                }

                if (!$parentData) {
                    throw new Exception("No outstanding EMI due found for this booking to settle.");
                }

                $parentPaymentId = (int)$parentData['id'];
                $remainingDue = (float)$parentData['due_emi_amount'];

                if ($amount > $remainingDue) {
                    throw new Exception("Settlement amount (TK." . number_format($amount, 2) . ") exceeds outstanding due (TK." . number_format($remainingDue, 2) . ") on EMI #{$parentData['emi_no']}");
                }

                // Service fee for settlement
                if ($csvServiceFee !== null) {
                    $serviceFee = (float)$csvServiceFee;
                } else {
                    $summary = $this->getPaymentSummaryByBookingId($bookingId);
                    $serviceChargeRate = (float)($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;
                }

                // Use 'emi' for payment_type to satisfy DB ENUM constraint,
                // track the difference via settlement_type column
                $paymentType = 'emi';
                $settlementType = 'emi_due_settlement';
                $dueEmiAmount = 0; // Settlement itself has no new due

            } elseif ($paymentType === 'emi') {
                $summary = $this->getPaymentSummaryByBookingId($bookingId);
                $serviceChargeRate = (float)($summary['service_charge_per_emi'] ?? 0);
                $perEmiSuggested = (float)($summary['per_emi_amount'] ?? 0);

                // === PHASE 1: Auto-settle outstanding dues from the payment pool ===
                $pool = $amount;
                $outstandingDues = $this->getOutstandingEmiDues($bookingId);
                $csvSettlements = []; // Track settlements to create after main insert

                foreach ($outstandingDues as $due) {
                    if ($pool <= 0) break;

                    $dueAmount = (float)$due['due_emi_amount'];
                    $settle = min($pool, $dueAmount);

                    $csvSettlements[] = [
                        'parent_id' => (int)$due['id'],
                        'settle_amount' => $settle,
                        'old_due' => $dueAmount,
                    ];
                    $pool -= $settle;
                }

                // === PHASE 2: Remaining pool goes to new installment(s) ===
                // Store pool for multi-installment loop (handled after bank lookup)
                $emiPool = $pool;
                $skipMainInsert = true; // We handle inserts in the multi-installment loop below

            } elseif ($csvServiceFee !== null) {
                $serviceFee = (float)$csvServiceFee;
            }

            // Bank name lookup if bank_id provided
            $bankNameLookup = $bankName;
            if ($bankId) {
                $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', branch_name) as label FROM banks WHERE id = {$bankId}");
                if ($bRow) {
                    $bData = $bRow->fetch_assoc();
                    $bankNameLookup = $bData['label'] ?? $bankName;
                }
            }

            // Verified by (set if verification_status is verified)
            $verifiedBy = ($verificationStatus === 'verified') ? $createdBy : null;
            $verifiedAt = ($verificationStatus === 'verified') ? date('Y-m-d H:i:s') : null;

            // === CREATE SETTLEMENT RECORDS (for EMI auto-settle) ===
            $firstPaymentId = null;
            if (!empty($csvSettlements)) {
                foreach ($csvSettlements as $cs) {
                    $csAmount = $cs['settle_amount'];
                    $csParentId = $cs['parent_id'];
                    $csSvcFee = ($serviceChargeRate > 0) ? round($csAmount * ($serviceChargeRate / 100)) : 0;
                    $csPayType = 'emi';
                    $csStType = 'emi_due_settlement';
                    $csDue = 0.0;
                    $csLandShare = 0.0;
                    $csBulkDisc = 0.0;

                    $csBulkPay = (!$firstPaymentId) ? $amount : null;

                    $csStmt = $this->db->prepare("
                        INSERT INTO lead_payments (
                            booking_id, payment_type, amount, land_share_amount, service_fee,
                            payment_method, payment_status, payment_date, transaction_ref,
                            bank_id, bank_name, bank_account_no, cheque_no, online_type,
                            due_emi_amount, bulk_emi_discount, bulk_pay_amount, verification_status,
                            verified_by, verified_at, parent_payment_id, settlement_type, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if (!$csStmt) throw new Exception("Settlement prepare failed: " . $this->db->error);

                    $csStmt->bind_param(
                        "isdddssssissssdddssissi",
                        $bookingId, $csPayType, $csAmount, $csLandShare, $csSvcFee,
                        $paymentMethod, $paymentStatus, $paymentDate, $transactionRef,
                        $bankId, $bankNameLookup, $bankAccountNo, $chequeNo, $onlineType,
                        $csDue, $csBulkDisc, $csBulkPay, $verificationStatus,
                        $verifiedBy, $verifiedAt, $csParentId, $csStType, $createdBy
                    );

                    if (!$csStmt->execute()) throw new Exception("Settlement insert failed: " . $csStmt->error);
                    if (!$firstPaymentId) $firstPaymentId = $this->db->insert_id;
                    $csStmt->close();

                    // Update parent's due_emi_amount
                    $newDue = max(0, $cs['old_due'] - $csAmount);
                    $newParentStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                    $updStmt = $this->db->prepare("UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?");
                    $updStmt->bind_param('dsi', $newDue, $newParentStatus, $csParentId);
                    $updStmt->execute();
                    $updStmt->close();

                    // Sync emi_schedules
                    $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                    $schedSyncStmt->bind_param('dsi', $newDue, $newParentStatus, $csParentId);
                    $schedSyncStmt->execute();
                    $schedSyncStmt->close();
                }
            }

            // === MAIN INSERT: New installment(s) or single payment ===
            $paymentId = $firstPaymentId; // Default to settlement ID if no new installment
            $skipMainInsert = isset($skipMainInsert) && $skipMainInsert;

            if ($skipMainInsert && isset($emiPool) && $emiPool > 0) {
                // ── Multi-installment loop: split remaining pool into per_emi-sized chunks ──
                $pool = $emiPool;
                $isFirstInstallment = true;

                while ($pool > 0) {
                    $thisEmi = ($perEmiSuggested > 0) ? min($pool, $perEmiSuggested) : $pool;

                    // Calculate due for this installment
                    $thisDue = 0;
                    if ($perEmiSuggested > 0 && $thisEmi < $perEmiSuggested) {
                        $thisDue = round($perEmiSuggested - $thisEmi);
                    }
                    $thisStatus = ($thisDue > 0) ? 'partially_paid' : 'paid';

                    // Service fee for this installment
                    if ($csvServiceFee !== null && $isFirstInstallment) {
                        $thisSvcFee = (float)$csvServiceFee;
                    } else {
                        $thisSvcFee = ($serviceChargeRate > 0) ? round($thisEmi * ($serviceChargeRate / 100)) : 0;
                    }

                    // Discount only on first installment
                    $thisDiscount = $isFirstInstallment ? $bulkDiscount : 0.0;

                    $emiStmt = $this->db->prepare("
                        INSERT INTO lead_payments (
                            booking_id, payment_type, amount, land_share_amount, service_fee,
                            payment_method, payment_status, payment_date, transaction_ref,
                            bank_id, bank_name, bank_account_no, cheque_no, online_type,
                            due_emi_amount, bulk_emi_discount, bulk_pay_amount, verification_status,
                            verified_by, verified_at, parent_payment_id, settlement_type, attachment_paths, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if (!$emiStmt) throw new Exception("EMI prepare failed: " . $this->db->error);

                    $emiPayType = 'emi';
                    $emiLandShare = 0.0;
                    // Only attach document to first installment
                    $emiAttach = $isFirstInstallment ? $attachmentPaths : null;
                    // Only set bulk pay amount on the first actual payment record
                    $emiBulkPay = ($isFirstInstallment && !$firstPaymentId) ? $amount : null;

                    $emiStmt->bind_param(
                        "isdddssssissssdddssisssi",
                        $bookingId, $emiPayType, $thisEmi, $emiLandShare, $thisSvcFee,
                        $paymentMethod, $thisStatus, $paymentDate, $transactionRef,
                        $bankId, $bankNameLookup, $bankAccountNo, $chequeNo, $onlineType,
                        $thisDue, $thisDiscount, $emiBulkPay, $verificationStatus,
                        $verifiedBy, $verifiedAt, $parentPaymentId, $settlementType, $emiAttach, $createdBy
                    );

                    if (!$emiStmt->execute()) throw new Exception("EMI insert failed: " . $emiStmt->error);
                    $thisPaymentId = $this->db->insert_id;
                    if (!$paymentId) $paymentId = $thisPaymentId;
                    $emiStmt->close();

                    // Create emi_schedule for this installment
                    $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);
                    $emiDueDate = $rowData['emi_due_date'] ?? $paymentDate;

                    $schedStmt = $this->db->prepare("
                        INSERT INTO emi_schedules (
                            lead_booking_id, emi_no, emi_amount, service_fee,
                            due_emi_amount, due_date, status, lead_payment_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if (!$schedStmt) throw new Exception("EMI schedule prepare failed: " . $this->db->error);

                    $schedStmt->bind_param(
                        "iidddssi",
                        $bookingId, $nextEmiNo, $thisEmi, $thisSvcFee,
                        $thisDue, $emiDueDate, $thisStatus, $thisPaymentId
                    );
                    if (!$schedStmt->execute()) throw new Exception("EMI schedule insert failed: " . $schedStmt->error);
                    $schedStmt->close();

                    $pool -= $thisEmi;
                    $isFirstInstallment = false;
                }
            } elseif (!$skipMainInsert) {
                // ── Non-EMI types (dev charge, settlement, etc.) — single insert ──
                $stmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_id, bank_name, bank_account_no, cheque_no, online_type,
                        due_emi_amount, bulk_emi_discount, bulk_pay_amount, verification_status,
                        verified_by, verified_at, parent_payment_id, settlement_type, attachment_paths, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$stmt) throw new Exception("Prepare failed: " . $this->db->error);

                $stmtBulkPay = (!$firstPaymentId && $amount > 0) ? $amount : null;

                $stmt->bind_param(
                    "isdddssssissssdddssisssi",
                    $bookingId, $paymentType, $amount, $landShareAmount, $serviceFee,
                    $paymentMethod, $paymentStatus, $paymentDate, $transactionRef,
                    $bankId, $bankNameLookup, $bankAccountNo, $chequeNo, $onlineType,
                    $dueEmiAmount, $bulkDiscount, $stmtBulkPay, $verificationStatus,
                    $verifiedBy, $verifiedAt, $parentPaymentId, $settlementType, $attachmentPaths, $createdBy
                );
                if (!$stmt->execute()) throw new Exception("Payment insert failed: " . $stmt->error);
                $paymentId = $this->db->insert_id;
                $stmt->close();
            }

            $this->db->commit();

            return [
                'success'    => true,
                'payment_id' => $paymentId,
                'message'    => "Row imported successfully (Payment #{$paymentId})"
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Log a CSV import batch for audit purposes.
     */
    public function logCSVImport($filename, $totalRows, $successCount, $errorCount, $errorDetails, $createdBy)
    {
        $stmt = $this->db->prepare("
            INSERT INTO csv_import_logs (filename, total_rows, success_count, error_count, error_details, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            error_log("logCSVImport prepare failed: " . $this->db->error);
            return false;
        }

        $errorJson = json_encode($errorDetails);
        $status = ($errorCount === 0) ? 'completed' : (($successCount > 0) ? 'completed' : 'failed');

        $stmt->bind_param("siissis", $filename, $totalRows, $successCount, $errorCount, $errorJson, $status, $createdBy);
        $result = $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();

        return $result ? $insertId : false;
    }

    public function initiateHandover($paymentId, $handoverToId, $createdBy)
    {
        $this->db->begin_transaction();
        try {
            // Update the lead_payments table
            $stmt = $this->db->prepare("UPDATE lead_payments SET handover_to = ?, handover_status = 'pending' WHERE id = ?");
            $stmt->bind_param("ii", $handoverToId, $paymentId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update handover status.");
            }

            // Get payment details for the approval request
            $payStmt = $this->db->prepare("SELECT amount, payment_type FROM lead_payments WHERE id = ?");
            $payStmt->bind_param("i", $paymentId);
            $payStmt->execute();
            $paymentData = $payStmt->get_result()->fetch_assoc();

            if (!class_exists('ApprovalRequest')) {
                require_once __DIR__ . '/ApprovalRequest.php';
            }
            $approvalModel = new ApprovalRequest($this->db);
            $approvalModel->create([
                'department' => 'Accounts',
                'request_type' => 'Cash In Handover',
                'title' => 'Handover Approval Request for Payment #' . $paymentId,
                'source_table' => 'lead_payments',
                'source_id' => $paymentId,
                'requested_days' => 0,
                'requested_by' => $createdBy,
                'approve_role' => 'management',
                'meta_data' => json_encode([
                    'Amount' => $paymentData['amount'] ?? 0,
                    'Payment Type' => $paymentData['payment_type'] ?? ''
                ]),
                'request_amount' => $paymentData['amount'] ?? 0
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Handover request initiated successfully.'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
