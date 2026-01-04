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
                lb.id as booking_id,
                lb.booking_date,
                lb.agreement_value,
                lb.booking_status,
                l.id as lead_id,
                l.lead_name as client_name,
                l.phone,
                l.email,
                l.designation,
                u.full_name as agent_name,
                p.name as project_name,
                p.types as project_type,
                f.id as flat_id,
                f.floor_no as flat_no,
                f.sqft as flat_size,
                f.price_value as total_price,
                un.name as unit_name,
                -- Total paid amount (sum of all payments)
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    0
                ) as total_paid,
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
            INNER JOIN units un ON f.unit_id = un.id
            LEFT JOIN users u ON l.assigned_id = u.id
            WHERE (lb.booking_status = 'booked' OR lb.booking_status = '' OR lb.booking_status IS NULL)
            ORDER BY lb.booking_date DESC
        ");
        
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
                COUNT(DISTINCT lb.id) as total_clients,
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
                END) as approved_bookings
            FROM lead_bookings lb
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
                l.lead_name as client_name,
                p.name as project_name,
                f.id as flat_id,
                f.floor_no,
                un.name as unit_name,
                CONCAT('UNIT ', un.name, ' - FLOOR ', f.floor_no) as flat_no,
                u.full_name as created_by_name
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN units un ON un.id = f.unit_id
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
 * Get client details by booking ID with payment summary and flat price
 */
public function getClientByBookingId($bookingId)
{
    if (empty($bookingId) || !is_numeric($bookingId)) {
        error_log("Invalid booking ID provided: " . print_r($bookingId, true));
        return null;
    }

    $stmt = $this->db->prepare("
        SELECT 
            lb.id as booking_id,
            lb.booking_date,
            lb.agreement_value,
            lb.booking_status,
            l.id as lead_id,
            l.lead_name as client_name,
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
            un.id as unit_id,
            un.name as unit_name,
            u.full_name as agent_name,
            COALESCE(
                (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status = 'paid'), 
                0
            ) as total_paid
        FROM lead_bookings lb
        INNER JOIN leads l ON lb.lead_id = l.id
        INNER JOIN projects p ON lb.project_id = p.id
        INNER JOIN flats f ON lb.flat_id = f.id
        INNER JOIN units un ON f.unit_id = un.id
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
        // Add calculated fields
        $clientDetails['due_amount'] = $clientDetails['total_price'] - $clientDetails['total_paid'];
    } else {
        error_log("No client found for booking ID: " . $bookingId);
    }
    
    return $clientDetails;
}

public function getTransactionsByBookingId($bookingId)
{
    // Get all payments from lead_payments table ONLY
    $paymentsStmt = $this->db->prepare("
        SELECT 
            lp.*,
            l.lead_name as client_name,
            p.name as project_name,
            f.floor_no,
            un.name as unit_name,
            CONCAT('UNIT ', un.name, ' - FLOOR ', f.floor_no) as flat_no,
            u.full_name as created_by_name,
            es.emi_no,
            es.emi_amount,
            es.due_emi_amount,
            es.due_date as emi_due_date,
            es.status as emi_status,
            CASE 
                WHEN lp.payment_type = 'emi' AND es.emi_no = 1 THEN '1st Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 2 THEN '2nd Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 3 THEN '3rd Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 4 THEN '4th Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 5 THEN '5th Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 6 THEN '6th Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 7 THEN '7th Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 8 THEN '8th Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 9 THEN '9th Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 10 THEN '10th Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 11 THEN '11th Installment'
                WHEN lp.payment_type = 'emi' AND es.emi_no = 12 THEN '12th Installment'
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
                WHEN lp.payment_type = 'construction_fee' THEN 'CONSTRUCTION'
                ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
            END as installment_label,
            CASE 
                WHEN lp.payment_type = 'emi' THEN 'EMI Payment'
                WHEN lp.payment_type = 'registration' THEN 'Registration Money'
                WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                WHEN lp.payment_type = 'construction_fee' THEN 'Construction Fee'
                ELSE 'Other Payment'
            END as payment_details
        FROM lead_payments lp
        LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
        LEFT JOIN leads l ON l.id = lb.lead_id
        LEFT JOIN projects p ON p.id = lb.project_id
        LEFT JOIN flats f ON f.id = lb.flat_id
        LEFT JOIN units un ON un.id = f.unit_id
        LEFT JOIN users u ON u.id = lp.created_by
        LEFT JOIN emi_schedules es ON es.lead_payment_id = lp.id
        WHERE lp.booking_id = ?
        ORDER BY 
            CASE 
                WHEN lp.payment_type = 'booking_money' THEN 1
                WHEN lp.payment_type = 'registration' THEN 2
                ELSE 3
            END,
            es.emi_no ASC,
            lp.payment_date ASC
    ");

    $paymentsStmt->bind_param("i", $bookingId);
    $paymentsStmt->execute();
    $paymentsResult = $paymentsStmt->get_result();
    $transactions = $paymentsResult ? $paymentsResult->fetch_all(MYSQLI_ASSOC) : [];
    
    // Format dates and add additional calculated fields
    foreach ($transactions as &$transaction) {
        // Format payment date
        if (isset($transaction['payment_date'])) {
            $transaction['formatted_date'] = date('d M, Y', strtotime($transaction['payment_date']));
        }
        
        // Add status badge class
        if (isset($transaction['payment_status'])) {
            switch ($transaction['payment_status']) {
                case 'paid':
                    $transaction['status_class'] = 'bg-success';
                    $transaction['status_text'] = 'Paid';
                    break;
                case 'pending':
                    $transaction['status_class'] = 'bg-warning';
                    $transaction['status_text'] = 'Pending';
                    break;
                default:
                    $transaction['status_class'] = 'bg-danger';
                    $transaction['status_text'] = 'Failed';
            }
        }
    }

    return $transactions;
}
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
     * Add new transaction with EMI schedule
     */
    public function addTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO lead_payments (
                    booking_id, payment_type, amount, payment_method,
                    payment_status, payment_date, transaction_ref, 
                    bank_name, bank_account_no, cheque_no, online_type, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $stmt->bind_param(
                "isdssssssssi",
                $transactionData['booking_id'],
                $transactionData['payment_type'],
                $transactionData['amount'],
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
                // Get the next EMI number
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
                        lead_booking_id, emi_no, emi_amount, 
                        due_emi_amount, due_date, status, lead_payment_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$emiStmt) {
                    throw new Exception("EMI schedule prepare failed: " . $this->db->error);
                }

                $status = $transactionData['payment_status'] === 'paid' ? 'paid' : 'pending';
                
                $emiStmt->bind_param(
                    "iiddssi",
                    $transactionData['booking_id'],
                    $nextEmiNo,
                    $transactionData['amount'],
                    $emiScheduleData['due_emi_amount'],
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
                'message' => 'Transaction added successfully!' . ($emiScheduleData ? ' EMI schedule created.' : ''),
                'payment_id' => $paymentId
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }

public function getPaymentSummaryByBookingId($bookingId)
{
    $summary = [
        'total_price' => 0,
        'booking_money' => 0,
        'registration_money' => 0,
        'construction_fee' => 0,
        'construction_fee_paid' => 0,
        'total_paid' => 0,
        'due_amount' => 0,
        'avg_emi_amount' => 0,
        'total_emi' => 0,
        'completed_emi' => 0,
        'pending_emi' => 0,
        'remaining_construction_fee' => 0,
        'last_payment_date' => null
    ];

    // Get booking details
    $stmt = $this->db->prepare("
        SELECT 
            lb.agreement_value,
            lb.booking_date,
            f.price_value as flat_price
        FROM lead_bookings lb
        LEFT JOIN flats f ON f.id = lb.flat_id
        WHERE lb.id = ?
    ");

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result ? $result->fetch_assoc() : null;

    if ($booking) {
        $summary['booking_money'] = $booking['agreement_value'] ?? 0;
        $summary['total_price'] = $booking['flat_price'] ?? 0;
    }

    // Get total EMI count from emi_schedules table (this is the correct table)
    $emiCountStmt = $this->db->prepare("
        SELECT COUNT(*) as total_emi
        FROM emi_schedules 
        WHERE lead_booking_id = ?
    ");
    
    $emiCountStmt->bind_param("i", $bookingId);
    $emiCountStmt->execute();
    $emiCountResult = $emiCountStmt->get_result();
    $emiCountData = $emiCountResult ? $emiCountResult->fetch_assoc() : null;
    
    if ($emiCountData) {
        $summary['total_emi'] = $emiCountData['total_emi'] ?? 0;
    }

    // Get EMI data from emi_schedules table
    $stmt = $this->db->prepare("
        SELECT 
            SUM(CASE WHEN status = 'paid' THEN emi_amount ELSE 0 END) as total_paid_emi,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_emi_count,
            AVG(CASE WHEN status = 'paid' THEN emi_amount END) as avg_paid_emi,
            MAX(CASE WHEN status = 'paid' THEN due_date END) as last_emi_date
        FROM emi_schedules 
        WHERE lead_booking_id = ?
    ");

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $emiData = $result ? $result->fetch_assoc() : [];
    
    $summary['construction_fee_paid'] = $emiData['total_paid_emi'] ?? 0;
    $summary['completed_emi'] = $emiData['paid_emi_count'] ?? 0;
    $summary['avg_emi_amount'] = $emiData['avg_paid_emi'] ?? 0;
    
    if ($emiData['last_emi_date'] ?? false) {
        $summary['last_payment_date'] = $emiData['last_emi_date'];
    }

    // Get registration payments
    $stmt = $this->db->prepare("
        SELECT 
            SUM(amount) as total_registration_paid
        FROM lead_payments 
        WHERE booking_id = ? 
        AND payment_type = 'registration'
        AND payment_status = 'paid'
    ");

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $regData = $result ? $result->fetch_assoc() : [];
    
    $summary['registration_money'] = $regData['total_registration_paid'] ?? 0;

    // Get other payments (non-EMI)
    $stmt = $this->db->prepare("
        SELECT 
            SUM(amount) as total_other_paid,
            MAX(payment_date) as last_payment
        FROM lead_payments 
        WHERE booking_id = ? 
        AND payment_status = 'paid'
        AND payment_type != 'emi'
    ");

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $otherData = $result ? $result->fetch_assoc() : [];
    
    $summary['total_paid'] = ($otherData['total_other_paid'] ?? 0) + $summary['construction_fee_paid'];
    
    if (!$summary['last_payment_date'] && ($otherData['last_payment'] ?? false)) {
        $summary['last_payment_date'] = $otherData['last_payment'];
    }

    // **CALCULATIONS:**
    // 1. Construction Fee = Total Price - Booking Money - Registration Money
    $summary['construction_fee'] = $summary['total_price'] - $summary['booking_money'] - $summary['registration_money'];
    
    // 2. Remaining Construction Fee = Construction Fee - Construction Fee Paid
    $summary['remaining_construction_fee'] = $summary['construction_fee'] - $summary['construction_fee_paid'];
    
    // 3. Pending EMI = Total EMI - Completed EMI
    $summary['pending_emi'] = max(0, $summary['total_emi'] - $summary['completed_emi']);
    
    // 4. Calculate Due Amount
    $summary['due_amount'] = $summary['total_price'] - $summary['total_paid'];

    return $summary;
}

    /**
     * Calculate EMI statistics based on construction fee and paid EMIs
     * Now uses total_emi from lead_bookings table
     */
    private function calculateEMIStats($constructionFee, $constructionFeePaid, $emiPayments = [], $totalEmiFromBooking = 0)
    {
        $stats = [
            'avg_emi_amount' => 0,
            'total_emi' => $totalEmiFromBooking,  // Use the value from lead_bookings.emi_no
            'completed_emi' => 0,
            'pending_emi' => 0
        ];

        // 1. Completed EMI = Count of EMI payments
        $stats['completed_emi'] = count($emiPayments);
        
        // 2. Average EMI Amount = Sum of EMI payments ÷ Count of EMI payments
        if ($stats['completed_emi'] > 0) {
            $stats['avg_emi_amount'] = array_sum($emiPayments) / $stats['completed_emi'];
        }
        
        // 3. Pending EMI = Total EMI (from lead_bookings) - Completed EMI
        $stats['pending_emi'] = max(0, $stats['total_emi'] - $stats['completed_emi']);
        
        return $stats;
    }
/**

    /**
     * Get transaction by ID
     */
    public function getTransactionById($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT lp.*,
                   l.lead_name as client_name,
                   p.name as project_name,
                   f.floor_no as flat_no
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
            LEFT JOIN leads l ON l.id = lb.lead_id
            LEFT JOIN projects p ON p.id = lb.project_id
            LEFT JOIN flats f ON f.id = lb.flat_id
            WHERE lp.id = ?
        ");

        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
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
     * Update transaction with EMI schedule
     */
/**
 * Update transaction with EMI schedule
 */
public function updateTransactionWithEMI($transactionData, $emiScheduleData = null)
{
    error_log("=== MODEL: updateTransactionWithEMI START ===");
    error_log("Transaction Data: " . print_r($transactionData, true));

    $this->db->begin_transaction();

    try {
        // Update the payment transaction WITHOUT updated_by column
        $stmt = $this->db->prepare("
            UPDATE lead_payments SET
                payment_type = ?,
                amount = ?,
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
            error_log("Prepare failed: " . $this->db->error);
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        // Bind parameters WITHOUT updated_by
        $stmt->bind_param(
            "sdssssssssi",
            $transactionData['payment_type'],
            $transactionData['amount'],
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
            error_log("Execute failed: " . $stmt->error);
            throw new Exception("Transaction update failed: " . $stmt->error);
        }

        error_log("Payment updated successfully. Affected rows: " . $this->db->affected_rows);

        // Handle EMI schedule
        if ($transactionData['payment_type'] === 'emi' && $emiScheduleData) {
            error_log("Processing EMI schedule update...");
            
            // Check if EMI schedule already exists
            $checkStmt = $this->db->prepare("
                SELECT id, emi_no FROM emi_schedules WHERE lead_payment_id = ?
            ");
            $checkStmt->bind_param("i", $transactionData['payment_id']);
            $checkStmt->execute();
            $existingSchedule = $checkStmt->get_result()->fetch_assoc();

            error_log("Existing Schedule: " . print_r($existingSchedule, true));

            if ($existingSchedule) {
                // Update existing EMI schedule
                $emiStmt = $this->db->prepare("
                    UPDATE emi_schedules SET
                        emi_amount = ?,
                        due_emi_amount = ?,
                        due_date = ?,
                        status = ?
                    WHERE lead_payment_id = ?
                ");

                if (!$emiStmt) {
                    error_log("EMI update prepare failed: " . $this->db->error);
                    throw new Exception("EMI schedule prepare failed: " . $this->db->error);
                }

                $status = $transactionData['payment_status'] === 'paid' ? 'paid' : 'pending';
                
                $emiStmt->bind_param(
                    "ddssi",
                    $transactionData['amount'],
                    $emiScheduleData['due_emi_amount'],
                    $transactionData['payment_date'],
                    $status,
                    $transactionData['payment_id']
                );

                if (!$emiStmt->execute()) {
                    error_log("EMI update execute failed: " . $emiStmt->error);
                    throw new Exception("EMI schedule update failed: " . $emiStmt->error);
                }
                
                error_log("EMI schedule updated successfully");
            } else {
                error_log("Creating new EMI schedule...");
                
                // Create new EMI schedule
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

                error_log("Next EMI No: " . $nextEmiNo);

                $emiStmt = $this->db->prepare("
                    INSERT INTO emi_schedules (
                        lead_booking_id, emi_no, emi_amount, 
                        due_emi_amount, due_date, status, lead_payment_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$emiStmt) {
                    error_log("EMI insert prepare failed: " . $this->db->error);
                    throw new Exception("EMI schedule insert prepare failed: " . $this->db->error);
                }

                $status = $transactionData['payment_status'] === 'paid' ? 'paid' : 'pending';
                
                $emiStmt->bind_param(
                    "iiddssi",
                    $transactionData['booking_id'],
                    $nextEmiNo,
                    $transactionData['amount'],
                    $emiScheduleData['due_emi_amount'],
                    $transactionData['payment_date'],
                    $status,
                    $transactionData['payment_id']
                );

                if (!$emiStmt->execute()) {
                    error_log("EMI insert execute failed: " . $emiStmt->error);
                    throw new Exception("EMI schedule insert failed: " . $emiStmt->error);
                }
                
                error_log("EMI schedule created successfully. ID: " . $this->db->insert_id);
            }
        } elseif ($transactionData['payment_type'] !== 'emi') {
            // Delete EMI schedule if payment type changed from EMI to something else
            $deleteEmiStmt = $this->db->prepare("
                DELETE FROM emi_schedules WHERE lead_payment_id = ?
            ");
            $deleteEmiStmt->bind_param("i", $transactionData['payment_id']);
            if ($deleteEmiStmt->execute()) {
                error_log("Deleted EMI schedule (payment type changed to non-EMI)");
            }
        }

        $this->db->commit();
        error_log("=== MODEL: Transaction committed successfully ===");

        return [
            'success' => true,
            'message' => 'Transaction updated successfully!'
        ];
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("=== MODEL: ERROR - " . $e->getMessage() . " ===");
        error_log("Error trace: " . $e->getTraceAsString());
        return [
            'success' => false,
            'message' => 'Transaction update failed: ' . $e->getMessage()
        ];
    }
}

    /**
     * Delete transaction and its EMI schedule
     */
    public function deleteTransaction($paymentId)
    {
        $this->db->begin_transaction();

        try {
            $deleteEmiStmt = $this->db->prepare("
                DELETE FROM emi_schedules WHERE lead_payment_id = ?
            ");
            $deleteEmiStmt->bind_param("i", $paymentId);
            $deleteEmiStmt->execute();

            $deletePaymentStmt = $this->db->prepare("
                DELETE FROM lead_payments WHERE id = ?
            ");
            $deletePaymentStmt->bind_param("i", $paymentId);

            if (!$deletePaymentStmt->execute()) {
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
     * Check if booking money exists in database for a booking
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
}