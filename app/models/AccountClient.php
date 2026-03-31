<?php

class AccountClient extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    /**
     * Get all clients (booked leads) with full details including flat price
     */
    public function getAllClients()
    {
        $sql = $this->db->prepare("
        SELECT
            l.id AS lead_id,
            l.full_name AS client_name,
            l.phone,
            l.email,
            l.designation,
            (SELECT GROUP_CONCAT(DISTINCT u_agent.full_name SEPARATOR ', ')
             FROM lead_bookings lb_sub
             INNER JOIN lead_payments lp_sub ON lp_sub.booking_id = lb_sub.id AND lp_sub.payment_type = 'booking_money'
             INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
             WHERE lb_sub.lead_id = l.id AND lb_sub.booking_status != 'cancelled'
            ) AS agent_name,
            COUNT(lb.id) AS total_bookings,
            SUM(lb.agreement_value) AS total_agreement_value,
            COALESCE(
                (SELECT SUM(lp.amount)
                 FROM lead_payments lp
                 INNER JOIN lead_bookings lb2 ON lb2.id = lp.booking_id
                 WHERE lb2.lead_id = l.id
                 AND lp.payment_status = 'paid'),
                0
            ) AS total_paid,
            (
                SELECT MAX(lp.payment_date)
                FROM lead_payments lp
                INNER JOIN lead_bookings lb2 ON lb2.id = lp.booking_id
                WHERE lb2.lead_id = l.id
                AND lp.payment_status = 'paid'
            ) AS last_payment_date
        FROM leads l
        INNER JOIN lead_bookings lb ON lb.lead_id = l.id
        WHERE lb.booking_status != 'cancelled'
        GROUP BY l.id
        ORDER BY l.full_name
    ");

        if (!$sql) {
            error_log("getAllClients prepare failed: " . $this->db->error);
            return [];
        }

        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get all bookings with client and location info
     */
    public function getAllBookings()
    {
        $sql = $this->db->prepare("
        SELECT
            l.id AS lead_id,
            l.full_name AS client_name,
            l.phone,
            l.email,
            l.designation,
            lb.id AS booking_id,
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
                 AND lp.payment_status = 'paid'),
                0
            ) AS total_paid,
            (
                SELECT MAX(lp.payment_date)
                FROM lead_payments lp
                WHERE lp.booking_id = lb.id
                AND lp.payment_status = 'paid'
            ) AS last_payment_date
        FROM leads l
        INNER JOIN lead_bookings lb ON lb.lead_id = l.id
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        WHERE lb.booking_status != 'cancelled'
        ORDER BY l.full_name
        ");

        if (!$sql) {
            error_log("getAllBookings prepare failed: " . $this->db->error);
            return [];
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
                 AND lp.payment_status = 'paid'), 
                0
            ) as total_paid,
            COALESCE(
                (SELECT SUM(lp.land_share_amount) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status = 'paid'), 
                0
            ) as total_land_share_paid,
            COALESCE(
                (SELECT SUM(lp.service_fee) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status = 'paid'), 
                0
            ) as total_service_fee,
            COALESCE(
                (SELECT MAX(lp.payment_date) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status = 'paid'), 
                NULL
            ) as last_payment_date
        FROM lead_bookings lb
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
 WHERE lb.lead_id = ?
AND (
    SELECT COUNT(*) 
    FROM lead_payments lp 
    WHERE lp.booking_id = lb.id 
    AND lp.payment_type = 'booking_money'
    AND lp.payment_status = 'paid'
) > 0
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
    public function getPaymentStatistics()
    {
        $sql = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT l.id) as total_clients,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT SUM(lp.amount) 
                          FROM lead_payments lp 
                          WHERE lp.booking_id = lb.id 
                          AND lp.payment_status = 'paid') >= lb.agreement_value 
                    THEN lb.id 
                END) as full_payment_completed,
                COUNT(DISTINCT CASE 
                    WHEN lb.booking_status = 'booked' 
                    THEN lb.id 
                END) as approved_bookings,
                COALESCE(SUM(lp.service_fee), 0) as total_service_fee_collected
            FROM leads l
            INNER JOIN lead_bookings lb ON l.id = lb.lead_id
            LEFT JOIN lead_payments lp ON lb.id = lp.booking_id AND lp.payment_status = 'paid'
        ");

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
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.payment_status = 'paid'), 
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
                f.id as flat_id,
                f.floor_no as flat_no,
                f.sqft as flat_size,
                f.price_value as total_price,
                f.land_share_value as total_land_share,
                f.flat_name as unit_name,
                u.full_name as agent_name,
                -- Payment calculations
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    0
                ) as total_paid,
                COALESCE(
                    (SELECT SUM(lp.land_share_amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    0
                ) as additional_land_share_paid,
                -- FIXED: Calculate service fee from emi_schedules table instead of lead_payments
                COALESCE(
                    (SELECT SUM(es.service_fee) 
                     FROM emi_schedules es 
                     WHERE es.lead_booking_id = lb.id 
                     AND es.status = 'paid'), 
                    0
                ) as total_service_fee,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'
                     AND lp.payment_type = 'registration'), 
                    0
                ) as registration_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'
                     AND lp.payment_type = 'booking_money'), 
                    0
                ) as booking_money_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'
                     AND lp.payment_type = 'emi'), 
                    0
                ) as emi_paid,
                COALESCE(
                    (SELECT MAX(lp.payment_date) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    NULL
                ) as last_payment_date
            FROM lead_bookings lb
            INNER JOIN leads l ON lb.lead_id = l.id
            INNER JOIN projects p ON lb.project_id = p.id
            INNER JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON l.assigned_id = u.id
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
            $totalPrice = (float) $clientDetails['total_price'];
            $totalLandShare = (float) $clientDetails['total_land_share'];
            $bookingMoney = (float) $clientDetails['booking_money'];
            $discountAmount = (float) $clientDetails['discount_amount'];

            // Paid amounts
            $totalPaid = (float) $clientDetails['total_paid'];
            $bookingMoneyPaid = (float) $clientDetails['booking_money_paid'];
            $additionalLandSharePaid = (float) $clientDetails['additional_land_share_paid'];
            $registrationPaid = (float) $clientDetails['registration_paid'];
            $emiPaid = (float) $clientDetails['emi_paid'];
            $totalServiceFee = (float) $clientDetails['total_service_fee'];

            // CORRECT: Total land share paid = booking money + additional land share payments
            $totalLandSharePaid = $bookingMoney + $additionalLandSharePaid;

            // CORRECT: Remaining land share = total land share - (booking money + additional land share + discount)
            $remainingLandShare = $totalLandShare - ($totalLandSharePaid + $discountAmount);

            // CORRECT: Construction fee = total price - (total land share + registration)
            $constructionFee = $totalPrice - ($totalLandShare + $registrationPaid);

            // CORRECT: Construction fee paid = total paid - (booking money + registration + additional land share)
            $constructionFeePaid = $totalPaid - ($bookingMoneyPaid + $registrationPaid + $additionalLandSharePaid);
            $constructionFeePaid = max(0, $constructionFeePaid);

            // Calculate remaining construction fee (also considering discount)
            $remainingConstructionFee = max(0, $constructionFee - $constructionFeePaid);

            // Calculate total due (total price minus discount and what's been paid)
            $totalDue = max(0, ($totalPrice - $discountAmount) - $totalPaid);

            // Calculate net received
            $netReceived = $totalPaid - $totalServiceFee;

            // Get EMI statistics
            $emiStats = $this->getEMIStatistics($bookingId);

            // Add calculated fields to response
            $clientDetails['total_land_share_paid'] = $totalLandSharePaid;
            $clientDetails['remaining_land_share'] = $remainingLandShare;
            $clientDetails['construction_fee'] = $constructionFee;
            $clientDetails['construction_fee_paid'] = $constructionFeePaid;
            $clientDetails['construction_fee_paid_after_service'] = $constructionFeePaid;
            $clientDetails['remaining_construction_fee'] = $remainingConstructionFee;
            $clientDetails['total_due'] = $totalDue;
            $clientDetails['net_received'] = $netReceived;
            $clientDetails['is_land_share_completed'] = ($remainingLandShare <= 0);

            // Add EMI statistics
            $clientDetails['total_emi'] = $emiStats['total_emi'];
            $clientDetails['completed_emi'] = $emiStats['completed_emi'];
            $clientDetails['pending_emi'] = $emiStats['pending_emi'];
            $clientDetails['paid_emi_numbers'] = $emiStats['paid_emi_numbers'];
            $clientDetails['pending_emi_numbers'] = $emiStats['pending_emi_numbers'];
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
            AND lp.payment_status = 'paid'
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

        // Calculate pending EMI numbers
        if ($stats['total_emi'] > 0) {
            for ($i = 1; $i <= $stats['total_emi']; $i++) {
                if (!in_array($i, $paidEmiNumbers)) {
                    $stats['pending_emi_numbers'][] = $i;
                }
            }
        }

        $stats['pending_emi'] = count($stats['pending_emi_numbers']);

        return $stats;
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
                'total_price' => 0,
                'total_land_share' => 0,
                'booking_money' => 0,
                'booking_money_paid' => 0,
                'additional_land_share_paid' => 0,
                'total_land_share_paid' => 0,
                'remaining_land_share' => 0,
                'discount_amount' => 0,
                'registration_paid' => 0,
                'construction_fee' => 0,
                'construction_fee_paid' => 0,
                'construction_fee_paid_after_service' => 0,
                'remaining_construction_fee' => 0,
                'total_paid' => 0,
                'total_due' => 0,
                'total_emi' => 0,
                'completed_emi' => 0,
                'pending_emi' => 0,
                'last_payment_date' => null,
                'total_service_fee' => 0,
                'net_received' => 0,
                'is_land_share_completed' => false,
                'paid_emi_numbers' => [],
                'pending_emi_numbers' => []
            ];
        }

        return [
            'total_price' => $clientDetails['total_price'],
            'total_land_share' => $clientDetails['total_land_share'],
            'booking_money' => $clientDetails['booking_money'],
            'booking_money_paid' => $clientDetails['booking_money_paid'],
            'additional_land_share_paid' => $clientDetails['additional_land_share_paid'],
            'total_land_share_paid' => $clientDetails['total_land_share_paid'],
            'remaining_land_share' => $clientDetails['remaining_land_share'],
            'discount_amount' => $clientDetails['discount_amount'],
            'registration_paid' => $clientDetails['registration_paid'],
            'registration_money' => $clientDetails['registration_paid'],
            'construction_fee' => $clientDetails['construction_fee'],
            'construction_fee_paid' => $clientDetails['construction_fee_paid'],
            'construction_fee_paid_after_service' => $clientDetails['construction_fee_paid_after_service'],
            'remaining_construction_fee' => $clientDetails['remaining_construction_fee'],
            'total_paid' => $clientDetails['total_paid'],
            'total_due' => $clientDetails['total_due'],
            'total_emi' => $clientDetails['total_emi'],
            'completed_emi' => $clientDetails['completed_emi'],
            'pending_emi' => $clientDetails['pending_emi'],
            'last_payment_date' => $clientDetails['last_payment_date'],
            'total_service_fee' => $clientDetails['total_service_fee'],
            'net_received' => $clientDetails['net_received'],
            'is_land_share_completed' => $clientDetails['is_land_share_completed'],
            'paid_emi_numbers' => $clientDetails['paid_emi_numbers'],
            'pending_emi_numbers' => $clientDetails['pending_emi_numbers']
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
                    if ($summary['booking_money_paid'] > 0) {
                        throw new Exception("Booking money has already been paid for this booking");
                    }
                    if ($amount > $summary['booking_money']) {
                        throw new Exception("Booking money amount cannot exceed agreed amount: ৳" . number_format($summary['booking_money'], 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'land_share':
                    if ($summary['remaining_land_share'] <= 0) {
                        throw new Exception("Land share payment is already completed!");
                    }
                    if ($amount > $summary['remaining_land_share']) {
                        throw new Exception("Land share amount cannot exceed remaining land share: ৳" . number_format($summary['remaining_land_share'], 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'emi':
                    $serviceFee = $amount * 0.10;
                    $emiPaidSoFar = $summary['construction_fee_paid'];
                    if (($emiPaidSoFar + $amount) > $summary['construction_fee']) {
                        throw new Exception("EMI payment cannot exceed total construction fee: ৳" . number_format($summary['construction_fee'], 2));
                    }
                    break;

                case 'registration':
                    break;

                default:
                    break;
            }

            // Insert payment record
            $stmt = $this->db->prepare("
                INSERT INTO lead_payments (
                    booking_id, payment_type, amount, land_share_amount, service_fee,
                    payment_method, payment_status, payment_date, transaction_ref, 
                    bank_name, bank_account_no, cheque_no, online_type, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $stmt->bind_param(
                "isdddsdssssssi",
                $bookingId,
                $paymentType,
                $amount,
                $landShareAmount,
                $serviceFee,
                $transactionData['payment_method'] ?? 'cash',
                $transactionData['payment_status'],
                $transactionData['payment_date'] ?? date('Y-m-d'),
                $transactionData['transaction_ref'] ?? '',
                $transactionData['bank_name'] ?? '',
                $transactionData['bank_account_no'] ?? '',
                $transactionData['cheque_no'] ?? '',
                $transactionData['online_type'] ?? '',
                $transactionData['created_by'] ?? 0
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction insert failed: " . $stmt->error);
            }

            $paymentId = $this->db->insert_id;

            if ($paymentType === 'emi' && $emiScheduleData) {
                $this->createEMISchedule($bookingId, $paymentId, $amount, $serviceFee, $emiScheduleData);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction added successfully!',
                'payment_id' => $paymentId,
                'service_fee' => $serviceFee,
                'land_share_amount' => $landShareAmount
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
     * Create EMI schedule
     */
    private function createEMISchedule($bookingId, $paymentId, $emiAmount, $serviceFee, $emiScheduleData)
    {
        $emiNoStmt = $this->db->prepare("
            SELECT COALESCE(MAX(emi_no), 0) + 1 as next_emi_no 
            FROM emi_schedules 
            WHERE lead_booking_id = ?
        ");
        $emiNoStmt->bind_param("i", $bookingId);
        $emiNoStmt->execute();
        $emiNoResult = $emiNoStmt->get_result();
        $emiNoRow = $emiNoResult ? $emiNoResult->fetch_assoc() : null;
        $nextEmiNo = $emiNoRow ? $emiNoRow['next_emi_no'] : 1;

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
                es.emi_no,
                es.emi_amount,
                es.due_emi_amount,
                es.due_date as emi_due_date,
                es.status as emi_status,
                es.service_fee as emi_service_fee,
                CASE 
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
                    ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
                END as installment_label,
                CASE 
                    WHEN lp.payment_type = 'emi' THEN 'EMI Payment'
                    WHEN lp.payment_type = 'registration' THEN 'Registration Money'
                    WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                    WHEN lp.payment_type = 'land_share' THEN 'Land Share Payment'
                    WHEN lp.payment_type = 'construction_fee' THEN 'Construction Fee'
                    ELSE 'Other Payment'
                END as payment_details
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN users u ON u.id = lp.created_by
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
            WHERE lp.booking_id = ?
            ORDER BY lp.payment_date DESC, lp.id DESC
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
                    ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
                END as installment_label,
                CASE 
                    WHEN lp.payment_type = 'emi' THEN 'EMI Payment'
                    WHEN lp.payment_type = 'registration' THEN 'Registration Money'
                    WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                    WHEN lp.payment_type = 'land_share' THEN 'Land Share Payment'
                    WHEN lp.payment_type = 'construction_fee' THEN 'Construction Fee'
                    ELSE 'Other Payment'
                END as payment_details
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN users u ON u.id = lp.created_by
            LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
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
                    online_type = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "sdddssssssssi",
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
            $emiNoStmt = $this->db->prepare("
                SELECT COALESCE(MAX(emi_no), 0) + 1 as next_emi_no 
                FROM emi_schedules 
                WHERE lead_booking_id = ?
            ");
            $emiNoStmt->bind_param("i", $bookingId);
            $emiNoStmt->execute();
            $emiNoResult = $emiNoStmt->get_result();
            $emiNoRow = $emiNoResult ? $emiNoResult->fetch_assoc() : null;
            $nextEmiNo = $emiNoRow ? $emiNoRow['next_emi_no'] : 1;

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
            AND payment_status = 'paid'
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

            if ($transactionData['payment_type'] === 'emi') {
                $emiAmount = (float) $transactionData['amount'];
                $serviceFee = $emiAmount * 0.10;
            } elseif ($transactionData['payment_type'] === 'land_share') {
                $landShareAmount = (float) $transactionData['amount'];

                $clientDetails = $this->getClientByBookingId($transactionData['booking_id']);
                $remainingLandShare = $clientDetails['remaining_land_share'];

                if ($remainingLandShare <= 0) {
                    throw new Exception("Land share payment is already completed!");
                }

                if ($landShareAmount > $remainingLandShare) {
                    throw new Exception("Land share amount cannot exceed remaining land share: ৳" . number_format($remainingLandShare, 2));
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO lead_payments (
                    booking_id, payment_type, amount, land_share_amount,
                    payment_method, payment_status, payment_date, transaction_ref, 
                    bank_name, bank_account_no, cheque_no, online_type, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $stmt->bind_param(
                "isddssssssssi",
                $transactionData['booking_id'],
                $transactionData['payment_type'],
                $transactionData['amount'],
                $landShareAmount,
                $transactionData['payment_method'],
                $transactionData['payment_status'],
                $transactionData['payment_date'],
                $transactionData['transaction_ref'],
                $transactionData['bank_name'],
                $transactionData['bank_account_no'],
                $transactionData['cheque_no'],
                $transactionData['online_type'],
                $transactionData['created_by']
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction insert failed: " . $stmt->error);
            }

            $paymentId = $this->db->insert_id;

            if ($transactionData['payment_type'] === 'emi' && $emiScheduleData) {
                $emiNoStmt = $this->db->prepare("
                    SELECT COALESCE(MAX(emi_no), 0) + 1 as next_emi_no 
                    FROM emi_schedules 
                    WHERE lead_booking_id = ?
                ");
                $emiNoStmt->bind_param("i", $transactionData['booking_id']);
                $emiNoStmt->execute();
                $emiNoResult = $emiNoStmt->get_result();
                $emiNoRow = $emiNoResult ? $emiNoResult->fetch_assoc() : null;
                $nextEmiNo = $emiNoRow ? $emiNoRow['next_emi_no'] : 1;

                $emiStmt = $this->db->prepare("
                    INSERT INTO emi_schedules (
                        lead_booking_id, emi_no, emi_amount, service_fee,
                        due_emi_amount, due_date, status, lead_payment_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$emiStmt) {
                    throw new Exception("EMI schedule prepare failed: " . $this->db->error);
                }

                $status = $transactionData['payment_status'] === 'paid' ? 'paid' : 'pending';
                $dueEmiAmount = $emiScheduleData['due_emi_amount'] ?? 0;

                $emiStmt->bind_param(
                    "iidddssi",
                    $transactionData['booking_id'],
                    $nextEmiNo,
                    $transactionData['amount'],
                    $serviceFee,
                    $dueEmiAmount,
                    $transactionData['payment_date'],
                    $status,
                    $paymentId
                );

                if (!$emiStmt->execute()) {
                    throw new Exception("EMI schedule insert failed: " . $emiStmt->error);
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction added successfully!' .
                    ($emiScheduleData ? ' EMI schedule created.' : '') .
                    ($serviceFee > 0 ? ' Service fee: ৳' . number_format($serviceFee, 2) : ''),
                'payment_id' => $paymentId,
                'service_fee' => $serviceFee,
                'land_share_amount' => $landShareAmount
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
                    online_type = ?
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $stmt->bind_param(
                "sddssssssssi",
                $transactionData['payment_type'],
                $finalAmount,
                $landShareAmount,
                $transactionData['payment_method'],
                $transactionData['payment_status'],
                $transactionData['payment_date'],
                $transactionData['transaction_ref'],
                $transactionData['bank_name'],
                $transactionData['bank_account_no'],
                $transactionData['cheque_no'],
                $transactionData['online_type'],
                $transactionData['payment_id']
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
                    $emiNoStmt = $this->db->prepare("
                        SELECT COALESCE(MAX(emi_no), 0) + 1 as next_emi_no 
                        FROM emi_schedules 
                        WHERE lead_booking_id = ?
                    ");
                    $emiNoStmt->bind_param("i", $transactionData['booking_id']);
                    $emiNoStmt->execute();
                    $emiNoResult = $emiNoStmt->get_result();
                    $emiNoRow = $emiNoResult->fetch_assoc();
                    $nextEmiNo = $emiNoRow ? $emiNoRow['next_emi_no'] : 1;

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
}