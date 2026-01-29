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
            SELECT DISTINCT
                l.id as lead_id,
                l.full_name as client_name,
                l.phone,
                l.email,
                l.designation,
                u.full_name as agent_name,
                COUNT(DISTINCT lb.id) as total_lands,
                SUM(lb.agreement_value) as total_agreement_value,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id IN (
                         SELECT id FROM lead_bookings WHERE lead_id = l.id
                     ) AND lp.payment_status = 'paid'), 
                    0
                ) as total_paid,
                COALESCE(
                    (SELECT MAX(lp.payment_date) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id IN (
                         SELECT id FROM lead_bookings WHERE lead_id = l.id
                     ) AND lp.payment_status = 'paid'), 
                    NULL
                ) as last_payment_date
            FROM leads l
            LEFT JOIN lead_bookings lb ON l.id = lb.lead_id AND lb.booking_status != 'cancelled'
            LEFT JOIN users u ON l.assigned_id = u.id
            WHERE EXISTS (SELECT 1 FROM lead_bookings WHERE lead_id = l.id AND booking_status != 'cancelled')
            GROUP BY l.id, l.full_name, l.phone, l.email, l.designation, u.full_name
            ORDER BY l.full_name
        ");
        
        $sql->execute();
        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
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
                un.name as unit_name,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    0
                ) as total_paid,
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
            INNER JOIN projects p ON lb.project_id = p.id
            INNER JOIN flats f ON lb.flat_id = f.id
            INNER JOIN units un ON f.unit_id = un.id
            WHERE lb.lead_id = ? 
            AND (lb.booking_status = 'booked' OR lb.booking_status = '' OR lb.booking_status IS NULL)
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
        $stmt = $this->db->prepare("
            SELECT 
                lb.id as booking_id,
                lb.lead_id,
                lb.booking_date,
                lb.agreement_value,
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
                un.id as unit_id,
                un.name as unit_name,
                u.full_name as agent_name,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    0
                ) as total_paid,
                COALESCE(
                    (SELECT SUM(lp.service_fee) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    0
                ) as total_service_fee
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
            $clientDetails['due_amount'] = $clientDetails['total_price'] - $clientDetails['total_paid'];
            $clientDetails['net_paid'] = $clientDetails['total_paid'] - $clientDetails['total_service_fee'];
        }
        
        return $clientDetails;
    }
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
public function getTransactionsByBookingId($bookingId)
{
    // Get all payments from lead_payments table ONLY
    $paymentsStmt = $this->db->prepare("
        SELECT 
            lp.*,
            l.full_name as client_name,
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
        
        // Add status badge class - FIXED HERE
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

 public function addTransactionWithServiceFee($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            // Calculate service fee (10% of EMI amount)
            $serviceFee = 0;
            if ($transactionData['payment_type'] === 'emi') {
                $serviceFee = $transactionData['amount'] * 0.10;
            }

            $stmt = $this->db->prepare("
                INSERT INTO lead_payments (
                    booking_id, payment_type, amount, service_fee, payment_method,
                    payment_status, payment_date, transaction_ref, 
                    bank_name, bank_account_no, cheque_no, online_type, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $stmt->bind_param(
                "isddsdssssssi",
                $transactionData['booking_id'],
                $transactionData['payment_type'],
                $transactionData['amount'],
                $serviceFee,
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

            // Update service fee balance in lead_bookings
            $updateServiceFeeStmt = $this->db->prepare("
                UPDATE lead_bookings 
                SET service_fee_balance = service_fee_balance + ? 
                WHERE id = ?
            ");
            $updateServiceFeeStmt->bind_param("di", $serviceFee, $transactionData['booking_id']);
            $updateServiceFeeStmt->execute();

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
                'message' => 'Transaction added successfully!' . ($emiScheduleData ? ' EMI schedule created.' : '') . ($serviceFee > 0 ? ' Service fee calculated.' : ''),
                'payment_id' => $paymentId,
                'service_fee' => $serviceFee
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
 * Get payment summary by booking ID including EMI numbers
 */
/**
 * Get payment summary by booking ID including EMI numbers
 */
/**
 * Get payment summary by booking ID including EMI numbers
 */
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
        'last_payment_date' => null,
        'emi_numbers' => [],
        'paid_emi_numbers' => [],
        'pending_emi_numbers' => [],
        'total_service_fee' => 0,
        'service_fee_balance' => 0,
        'net_received' => 0
    ];

    // Get booking details
    $stmt = $this->db->prepare("
        SELECT 
            lb.agreement_value,
            lb.booking_date,
            lb.service_fee_balance,
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
        $summary['service_fee_balance'] = $booking['service_fee_balance'] ?? 0;
    }

    // 1. GET TOTAL EXPECTED EMI COUNT from lead_payments.total_emi_no
    $totalEmiStmt = $this->db->prepare("
        SELECT total_emi_no 
        FROM lead_payments 
        WHERE booking_id = ? 
        AND payment_type = 'booking_money' 
        LIMIT 1
    ");
    
    $totalEmiStmt->bind_param("i", $bookingId);
    $totalEmiStmt->execute();
    $totalEmiResult = $totalEmiStmt->get_result();
    $totalEmiRow = $totalEmiResult ? $totalEmiResult->fetch_assoc() : null;
    
    // Set total expected EMI count
    $summary['total_emi'] = $totalEmiRow['total_emi_no'] ?? 0;

    // 2. GET ACTUAL EMI PAYMENTS from emi_schedules
    $emiDetailsStmt = $this->db->prepare("
        SELECT 
            emi_no,
            emi_amount,
            due_emi_amount,
            due_date,
            status
        FROM emi_schedules 
        WHERE lead_booking_id = ?
        ORDER BY emi_no ASC
    ");
    
    $emiDetailsStmt->bind_param("i", $bookingId);
    $emiDetailsStmt->execute();
    $emiDetailsResult = $emiDetailsStmt->get_result();
    $emiDetails = $emiDetailsResult ? $emiDetailsResult->fetch_all(MYSQLI_ASSOC) : [];
    
    // Process actual EMI payments
    $totalPaidEmi = 0;
    $paidEmiCount = 0;
    $paidEmiAmounts = [];
    $lastEmiDate = null;
    
    foreach ($emiDetails as $emi) {
        $summary['emi_numbers'][] = $emi['emi_no'];
        
        if ($emi['status'] === 'paid') {
            $summary['paid_emi_numbers'][] = $emi['emi_no'];
            $totalPaidEmi += $emi['emi_amount'];
            $paidEmiCount++;
            $paidEmiAmounts[] = $emi['emi_amount'];
            
            if (!$lastEmiDate || strtotime($emi['due_date']) > strtotime($lastEmiDate)) {
                $lastEmiDate = $emi['due_date'];
            }
        } else {
            $summary['pending_emi_numbers'][] = $emi['emi_no'];
        }
    }
    
    $summary['construction_fee_paid'] = $totalPaidEmi;
    $summary['completed_emi'] = $paidEmiCount;
    
    // 3. CALCULATE PENDING EMI COUNT
    // If total_emi is 48 and you've paid 1, then 47 are pending
    $summary['pending_emi'] = max(0, $summary['total_emi'] - $paidEmiCount);
    
    // 4. GENERATE ALL EXPECTED EMI NUMBERS (1 to total_emi)
    $summary['all_emi_numbers'] = [];
    if ($summary['total_emi'] > 0) {
        for ($i = 1; $i <= $summary['total_emi']; $i++) {
            $summary['all_emi_numbers'][] = $i;
        }
    }
    
    // Calculate average EMI amount
    if ($paidEmiCount > 0) {
        $summary['avg_emi_amount'] = $totalPaidEmi / $paidEmiCount;
    }
    
    if ($lastEmiDate) {
        $summary['last_payment_date'] = $lastEmiDate;
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

    // Get other payments (non-EMI) and service fee
    $stmt = $this->db->prepare("
        SELECT 
            SUM(amount) as total_other_paid,
            SUM(service_fee) as total_service_fee,
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
    $summary['total_service_fee'] = ($otherData['total_service_fee'] ?? 0);
    
    // Also get service fee from EMI payments
    $emiServiceFeeStmt = $this->db->prepare("
        SELECT SUM(service_fee) as emi_service_fee
        FROM lead_payments 
        WHERE booking_id = ? 
        AND payment_status = 'paid'
        AND payment_type = 'emi'
    ");
    
    $emiServiceFeeStmt->bind_param("i", $bookingId);
    $emiServiceFeeStmt->execute();
    $emiServiceFeeResult = $emiServiceFeeStmt->get_result();
    $emiServiceFeeData = $emiServiceFeeResult ? $emiServiceFeeResult->fetch_assoc() : [];
    
    $summary['total_service_fee'] += ($emiServiceFeeData['emi_service_fee'] ?? 0);
    
    if (!$summary['last_payment_date'] && ($otherData['last_payment'] ?? false)) {
        $summary['last_payment_date'] = $otherData['last_payment'];
    }

    // **CALCULATIONS:**
    // 1. Construction Fee = Total Price - Booking Money - Registration Money
    $summary['construction_fee'] = $summary['total_price'] - $summary['booking_money'] - $summary['registration_money'];
    
    // 2. Remaining Construction Fee = Construction Fee - Construction Fee Paid
    $summary['remaining_construction_fee'] = $summary['construction_fee'] - $summary['construction_fee_paid'];
    
    // 3. Calculate Due Amount
    $summary['due_amount'] = $summary['total_price'] - $summary['total_paid'];
    
    // 4. Calculate net received
    $summary['net_received'] = $summary['total_paid'] - $summary['total_service_fee'];

    // Format EMI numbers for display
    $summary['formatted_all_emi_numbers'] = implode(', ', $summary['all_emi_numbers']);
    $summary['formatted_paid_emi_numbers'] = implode(', ', $summary['paid_emi_numbers']);
    
    // Generate pending EMI numbers (expected but not paid)
    $summary['expected_pending_numbers'] = [];
    if ($summary['total_emi'] > 0) {
        for ($i = 1; $i <= $summary['total_emi']; $i++) {
            if (!in_array($i, $summary['paid_emi_numbers'])) {
                $summary['expected_pending_numbers'][] = $i;
            }
        }
    }
    $summary['formatted_pending_emi_numbers'] = implode(', ', $summary['expected_pending_numbers']);

    // Get due amounts for partially paid EMI
    $dueQuery = $this->db->prepare("
        SELECT 
            SUM(es.due_emi_amount) as total_due_amount,
            COUNT(*) as due_emi_count
        FROM emi_schedules es
        WHERE es.lead_booking_id = ?
        AND es.due_emi_amount > 0
    ");

    $dueQuery->bind_param("i", $bookingId);
    $dueQuery->execute();
    $dueResult = $dueQuery->get_result();
    $dueData = $dueResult ? $dueResult->fetch_assoc() : [];

    // Add to summary array
    $summary['total_due_amount'] = $dueData['total_due_amount'] ?? 0;
    $summary['due_emi_count'] = $dueData['due_emi_count'] ?? 0;

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


     * Get transaction by ID
     */
    public function getTransactionById($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT lp.*,
                   l.full_name as client_name,
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
/**
 * Update transaction with EMI schedule and due amount tracking
 */
public function updateTransactionWithEMI($transactionData, $emiScheduleData = null)
{
    error_log("=== MODEL: updateTransactionWithEMI START ===");
    error_log("Transaction Data: " . print_r($transactionData, true));
    error_log("EMI Schedule Data: " . print_r($emiScheduleData, true));

    $this->db->begin_transaction();

    try {
        // Update the payment transaction
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

        // Bind parameters
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
                SELECT id, emi_no, due_emi_amount FROM emi_schedules WHERE lead_payment_id = ?
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

                $status = $transactionData['payment_status'] === 'paid' ? 'paid' : 
                         ($transactionData['payment_status'] === 'partially_paid' ? 'partially_paid' : 'pending');
                
                // Use the updated transaction amount (which includes additional payment)
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
                
                // Log the due amount change
                $oldDue = (float)($emiScheduleData['old_due_amount'] ?? 0);
                $newDue = (float)($emiScheduleData['due_emi_amount'] ?? 0);
                
                if ($oldDue > 0 && $newDue < $oldDue) {
                    $reduction = $oldDue - $newDue;
                    error_log("Due amount reduced by: " . $reduction);
                    error_log("This amount has been added to the paid amount");
                }
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

                $status = $transactionData['payment_status'] === 'paid' ? 'paid' : 
                         ($transactionData['payment_status'] === 'partially_paid' ? 'partially_paid' : 'pending');
                
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


    // Add this method to your AccountClient model class

/**
 * Get transactions by booking ID with date filter
 */
/**
 * Get transactions by booking ID with date filter
 */
public function getFilteredTransactionsByBookingId($bookingId, $dateFilter = [])
{
    // Base query for all payments
    $sql = "
        SELECT 
            lp.*,
            l.full_name as client_name,
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
    ";

    $params = [$bookingId];
    $types = "i";

    // Add date filter if provided
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
            ELSE 3
        END,
        es.emi_no ASC,
        lp.payment_date DESC
    ";

    $stmt = $this->db->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $this->db->error);
        return [];
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return [];
    }
    
    $result = $stmt->get_result();
    $transactions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Format dates and add additional calculated fields
    foreach ($transactions as &$transaction) {
        if (isset($transaction['payment_date'])) {
            $transaction['formatted_date'] = date('d M, Y', strtotime($transaction['payment_date']));
        }
        
        // Add status badge class - FIXED: Added 'partially_paid' case
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
}