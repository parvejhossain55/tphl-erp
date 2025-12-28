<?php

class AccountClient extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    /**
     * Get all clients (booked leads) with full details
     */
public function getAllClients()
{
    $sql = $this->db->prepare("
        SELECT 
            lb.id as booking_id,
            lb.booking_date,
            lb.agreement_value,  -- This is the total booking money/agreement value
            lb.booking_status,
            l.id as lead_id,
            l.lead_name as client_name,
            l.company,
            l.phone,
            l.email,
            l.designation,
            u.full_name as agent_name,
            p.name as project_name,
            p.types as project_type,
            f.id as flat_id,
            f.floor_no as flat_no,
            f.sqft as flat_size,
            un.name as unit_name,
            -- Total paid amount (sum of all payments)
            COALESCE(
                (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status = 'paid'), 
                0
            ) as booking_money_paid,  -- Renamed: This is the paid amount against agreement
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
     * Add new transaction with EMI schedule - Updated to handle both tables
     */
    public function addTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        // Start transaction
        $this->db->begin_transaction();

        try {
            // Insert into lead_payments (removed emi_no and emi_total)
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

            // If payment type is EMI and schedule data is provided, insert into emi_schedules
            if ($transactionData['payment_type'] === 'emi' && $emiScheduleData) {
                // Get the next EMI number for this booking
                $emiNoStmt = $this->db->prepare("
                    SELECT COALESCE(MAX(emi_no), 0) + 1 as next_emi_no 
                    FROM emi_schedules 
                    WHERE lead_booking_id = ?
                ");
                $emiNoStmt->bind_param("i", $transactionData['booking_id']);
                $emiNoStmt->execute();
                $emiNoResult = $emiNoStmt->get_result();
                $nextEmiNo = $emiNoResult->fetch_assoc()['next_emi_no'];

                // Insert EMI schedule - use payment_date as due_date
                $emiStmt = $this->db->prepare("
                    INSERT INTO emi_schedules (
                        lead_booking_id, emi_no, emi_amount, 
                        due_emi_amount, due_date, status, lead_payment_id
                    ) VALUES (?, ?, ?, ?, ?, 'paid', ?)
                ");

                if (!$emiStmt) {
                    throw new Exception("EMI schedule prepare failed: " . $this->db->error);
                }

                // Use payment_date as due_date since no separate date needed
                $emiStmt->bind_param(
                    "iiddsi",
                    $transactionData['booking_id'],
                    $nextEmiNo,
                    $transactionData['amount'],
                    $emiScheduleData['due_emi_amount'],
                    $transactionData['payment_date'],
                    $paymentId
                );

                if (!$emiStmt->execute()) {
                    throw new Exception("EMI schedule insert failed: " . $emiStmt->error);
                }
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction added successfully!' . ($emiScheduleData ? ' EMI schedule created.' : ''),
                'payment_id' => $paymentId
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get client details by booking ID with payment summary
     */
    public function getClientByBookingId($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lb.*,
                l.lead_name as client_name,
                l.phone,
                l.email,
                p.name as project_name,
                f.floor_no as flat_no,
                f.sqft as flat_size,
                un.name as unit_name,
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
            WHERE lb.id = ?
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Get all transactions for a specific booking ID with EMI schedule info
     */
    public function getTransactionsByBookingId($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lp.*,
                l.lead_name as client_name,
                l.phone,
                l.email,
                p.name as project_name,
                f.floor_no as flat_no,
                u.full_name as created_by_name,
                es.emi_no,
                es.due_emi_amount,
                es.due_date as emi_due_date,
                es.status as emi_status,
                CASE 
                    WHEN lp.payment_type = 'emi' THEN CONCAT('EMI ', COALESCE(es.emi_no, 'N/A'))
                    ELSE UPPER(REPLACE(lp.payment_type, '_', ' '))
                END as installment_label,
                CASE 
                    WHEN lp.payment_method = 'bank' THEN CONCAT('Bank: ', COALESCE(lp.bank_name, ''), ' (', COALESCE(lp.bank_account_no, ''), ')')
                    WHEN lp.payment_method = 'cheque' THEN CONCAT('Cheque: ', COALESCE(lp.cheque_no, ''), ' - ', COALESCE(lp.bank_name, ''))
                    WHEN lp.payment_method = 'online' THEN CONCAT('Online: ', COALESCE(lp.online_type, ''))
                    ELSE 'Cash'
                END as payment_details
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            INNER JOIN projects p ON lb.project_id = p.id
            INNER JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON lp.created_by = u.id
            LEFT JOIN emi_schedules es ON lp.id = es.lead_payment_id
            WHERE lb.id = ?
            ORDER BY lp.payment_date DESC, lp.created_at DESC
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get payment summary for a specific booking ID
     */
    public function getPaymentSummaryByBookingId($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lb.agreement_value as total_price,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'
                     AND lp.payment_type = 'booking_money'), 
                    0
                ) as booking_money,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    0
                ) as total_paid,
                (lb.agreement_value - COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    0
                )) as due_amount,
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM emi_schedules es 
                     WHERE es.lead_booking_id = lb.id), 
                    0
                ) as total_emi,
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM emi_schedules es 
                     WHERE es.lead_booking_id = lb.id 
                     AND es.status = 'paid'), 
                    0
                ) as completed_emi,
                COALESCE(
                    (SELECT COUNT(*) 
                     FROM emi_schedules es 
                     WHERE es.lead_booking_id = lb.id 
                     AND es.status = 'pending'), 
                    0
                ) as pending_emi,
                COALESCE(
                    (SELECT SUM(es.due_emi_amount) 
                     FROM emi_schedules es 
                     WHERE es.lead_booking_id = lb.id 
                     AND es.status = 'pending'), 
                    0
                ) as total_due_emi_amount,
                COALESCE(
                    (SELECT MAX(lp.payment_date) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status = 'paid'), 
                    NULL
                ) as last_payment_date
            FROM lead_bookings lb
            WHERE lb.id = ?
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : [];
    }

    /**
     * Get transaction by ID
     */
    public function getTransactionById($paymentId)
    {
        $stmt = $this->db->prepare("
            SELECT lp.* 
            FROM lead_payments lp
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
    public function updateTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        // Start transaction
        $this->db->begin_transaction();

        try {
            // Update lead_payments
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
                throw new Exception("Prepare failed: " . $this->db->error);
            }

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
                throw new Exception("Transaction update failed: " . $stmt->error);
            }

            // Handle EMI schedule
            if ($transactionData['payment_type'] === 'emi' && $emiScheduleData) {
                // Check if EMI schedule exists
                $checkStmt = $this->db->prepare("
                    SELECT id FROM emi_schedules WHERE lead_payment_id = ?
                ");
                $checkStmt->bind_param("i", $transactionData['payment_id']);
                $checkStmt->execute();
                $existingSchedule = $checkStmt->get_result()->fetch_assoc();

                if ($existingSchedule) {
                    // Update existing EMI schedule
                    $emiStmt = $this->db->prepare("
                        UPDATE emi_schedules SET
                            emi_amount = ?,
                            due_emi_amount = ?,
                            due_date = ?
                        WHERE lead_payment_id = ?
                    ");

                    $emiStmt->bind_param(
                        "ddsi",
                        $transactionData['amount'],
                        $emiScheduleData['due_emi_amount'],
                        $transactionData['payment_date'],
                        $transactionData['payment_id']
                    );
                } else {
                    // Create new EMI schedule
                    $emiNoStmt = $this->db->prepare("
                        SELECT COALESCE(MAX(emi_no), 0) + 1 as next_emi_no 
                        FROM emi_schedules 
                        WHERE lead_booking_id = ?
                    ");
                    $emiNoStmt->bind_param("i", $transactionData['booking_id']);
                    $emiNoStmt->execute();
                    $emiNoResult = $emiNoStmt->get_result();
                    $nextEmiNo = $emiNoResult->fetch_assoc()['next_emi_no'];

                    $emiStmt = $this->db->prepare("
                        INSERT INTO emi_schedules (
                            lead_booking_id, emi_no, emi_amount, 
                            due_emi_amount, due_date, status, lead_payment_id
                        ) VALUES (?, ?, ?, ?, ?, 'paid', ?)
                    ");

                    $emiStmt->bind_param(
                        "iiddsi",
                        $transactionData['booking_id'],
                        $nextEmiNo,
                        $transactionData['amount'],
                        $emiScheduleData['due_emi_amount'],
                        $transactionData['payment_date'],
                        $transactionData['payment_id']
                    );
                }

                if (!$emiStmt->execute()) {
                    throw new Exception("EMI schedule update failed: " . $emiStmt->error);
                }
            } elseif ($transactionData['payment_type'] !== 'emi') {
                // If payment type changed from EMI to something else, delete EMI schedule
                $deleteEmiStmt = $this->db->prepare("
                    DELETE FROM emi_schedules WHERE lead_payment_id = ?
                ");
                $deleteEmiStmt->bind_param("i", $transactionData['payment_id']);
                $deleteEmiStmt->execute();
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction updated successfully!' . ($emiScheduleData ? ' EMI schedule updated.' : '')
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollback();
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
        // Start transaction
        $this->db->begin_transaction();

        try {
            // Delete EMI schedule first (if exists)
            $deleteEmiStmt = $this->db->prepare("
                DELETE FROM emi_schedules WHERE lead_payment_id = ?
            ");
            $deleteEmiStmt->bind_param("i", $paymentId);
            $deleteEmiStmt->execute();

            // Delete payment
            $deletePaymentStmt = $this->db->prepare("
                DELETE FROM lead_payments WHERE id = ?
            ");
            $deletePaymentStmt->bind_param("i", $paymentId);

            if (!$deletePaymentStmt->execute()) {
                throw new Exception("Failed to delete transaction");
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Transaction deleted successfully!'
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }
}
