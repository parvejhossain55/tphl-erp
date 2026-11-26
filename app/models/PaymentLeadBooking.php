<?php

class PaymentLeadBooking extends Model
{
    public $db;
    protected $lastError = '';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db; // mysqli connection
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    // Get all follow-ups with related lead, project, flat, and creator info
    // Get all payments for a specific onboarding record
    public function getByOnboardId($booking_id)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name AS created_by_name
            FROM lead_payments p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.booking_id = ?
            ORDER BY p.payment_date ASC, p.id ASC
        ");

        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Create a new follow-up

    public function createFromCsvBulk($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO lead_payments
            (booking_id, bank_id, bank_name, bank_account_no, cheque_no, online_type, 
             payment_type, amount, payment_method, payment_status, verification_status, verified_by,
             payment_date, transaction_ref, total_emi_no, created_by, attachment_image, attachment_image2)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $v = function($key) use ($data) {
            return (isset($data[$key]) && trim($data[$key]) !== '') ? trim($data[$key]) : null;
        };

        $v_booking_id = $v('booking_id'); $v_bank_id = $v('bank_id'); $v_bank_name = $v('bank_name'); $v_bank_account_no = $v('bank_account_no');
        $v_cheque_no = $v('cheque_no'); $v_online_type = $v('online_type'); $v_payment_type = $v('payment_type');
        $v_amount = $v('amount'); $v_payment_method = $v('payment_method'); $v_payment_status = $v('payment_status');
        $v_verification_status = $v('verification_status') ?? 'pending'; $v_verified_by = $v('verified_by');
        $v_payment_date = $v('payment_date'); $v_transaction_ref = $v('transaction_ref'); $v_total_emi_no = $v('total_emi_no');
        $v_created_by = $v('created_by'); $v_attachment_image = $v('attachment_image'); $v_attachment_image2 = $v('attachment_image2');

        $stmt->bind_param(
            "iisssssdsssissssss",
            $v_booking_id,
            $v_bank_id,
            $v_bank_name,
            $v_bank_account_no,
            $v_cheque_no,
            $v_online_type,
            $v_payment_type,
            $v_amount,
            $v_payment_method,
            $v_payment_status,
            $v_verification_status,
            $v_verified_by,
            $v_payment_date,
            $v_transaction_ref,
            $v_total_emi_no,
            $v_created_by,
            $v_attachment_image,
            $v_attachment_image2
        );

        if (!$stmt->execute()) {
            throw new Exception("Payment insert failed: " . $stmt->error);
        }

        return $this->db->insert_id;
    }

    public function create($data)
    {
        $this->lastError = '';
        $this->db->begin_transaction();

        try {
            $stmt = $this->db->prepare("
            INSERT INTO lead_payments
            (booking_id, bank_id, bank_name, bank_account_no, cheque_no, online_type, 
             payment_type, amount, payment_method, payment_status, verification_status,
             payment_date, transaction_ref, total_emi_no, created_by, attachment_image, attachment_image2, batch_id,
             attachment_image3, attachment_image4, attachment_paths)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $bookingId          = (int)($data['booking_id'] ?? 0);
            $bankId             = !empty($data['bank_id']) ? (int)$data['bank_id'] : null;
            $bankName           = (isset($data['bank_name']) && trim((string)$data['bank_name']) !== '') ? trim((string)$data['bank_name']) : null;
            $bankAccountNo      = (isset($data['bank_account_no']) && trim((string)$data['bank_account_no']) !== '') ? trim((string)$data['bank_account_no']) : null;
            $chequeNo           = (isset($data['cheque_no']) && trim((string)$data['cheque_no']) !== '') ? trim((string)$data['cheque_no']) : null;
            $onlineType         = (isset($data['online_type']) && trim((string)$data['online_type']) !== '') ? trim((string)$data['online_type']) : null;
            $paymentType        = !empty($data['payment_type']) ? trim((string)$data['payment_type']) : 'booking_money';
            $amount             = (float)($data['amount'] ?? 0);
            $paymentMethod      = !empty($data['payment_method']) ? trim((string)$data['payment_method']) : 'cash';
            $paymentStatus      = !empty($data['payment_status']) ? trim((string)$data['payment_status']) : 'pending';
            $verificationStatus = !empty($data['verification_status']) ? trim((string)$data['verification_status']) : 'pending';
            $paymentDate        = !empty($data['payment_date']) ? trim((string)$data['payment_date']) : date('Y-m-d');
            $transactionRef     = (isset($data['transaction_ref']) && trim((string)$data['transaction_ref']) !== '') ? trim((string)$data['transaction_ref']) : null;
            $totalEmiNo         = (isset($data['total_emi_no']) && trim((string)$data['total_emi_no']) !== '') ? (int)$data['total_emi_no'] : null;
            $createdBy          = !empty($data['created_by']) ? (int)$data['created_by'] : 1;

            $attachment_image  = $data['attachment_image'] ?? '';
            $attachment_image2 = $data['attachment_image2'] ?? '';
            $attachment_image3 = $data['attachment_image3'] ?? null;
            $attachment_image4 = $data['attachment_image4'] ?? null;
            $batch_id          = !empty($data['batch_id']) ? (string)$data['batch_id'] : null;

            // Also build attachment_paths JSON array so modern views see all attachments seamlessly
            $allAttachments = [];
            foreach ([$attachment_image, $attachment_image2, $attachment_image3, $attachment_image4] as $fName) {
                if (!empty($fName)) {
                    $allAttachments[] = 'uploads/payments/' . $fName;
                }
            }
            $attachment_paths = !empty($allAttachments) ? json_encode($allAttachments) : null;

            $stmt->bind_param(
                "iisssssdsssssiissssss",
                $bookingId,
                $bankId,
                $bankName,
                $bankAccountNo,
                $chequeNo,
                $onlineType,
                $paymentType,
                $amount,
                $paymentMethod,
                $paymentStatus,
                $verificationStatus,
                $paymentDate,
                $transactionRef,
                $totalEmiNo,
                $createdBy,
                $attachment_image,
                $attachment_image2,
                $batch_id,
                $attachment_image3,
                $attachment_image4,
                $attachment_paths
            );

            if (!$stmt->execute()) {
                throw new Exception("Payment insert failed: " . $stmt->error);
            }

            $insertId = $stmt->insert_id ?: $this->db->insert_id;
            $this->db->commit();
            return $insertId;
        } catch (Throwable $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            error_log("[PaymentLeadBooking::create] " . $this->lastError);
            return false;
        }
    }



    // Flat is_booked update
    private function markFlatBooked($booking_id)
    {
        $sql = "UPDATE flats f
            JOIN lead_bookings b ON b.flat_id = f.id
            SET f.is_booked = 1,
                f.status = 'booked'
            WHERE b.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
    }


    // All payments for a booking
    public function getByBooking($booking_id)
    {
        $stmt = $this->db->prepare("SELECT p.*, 
                    u.full_name AS created_by_name,
                    lb.agreement_value,
                    lb.discount_amount,
                    us.full_name AS verified_by_name,
                    cb.bank_name AS company_bank_name,
                    cb.account_name AS company_account_name,
                    cb.account_number AS company_account_number
                FROM lead_payments p
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN lead_bookings lb ON p.booking_id = lb.id
                LEFT JOIN users us ON us.id = p.verified_by
                LEFT JOIN company_bank_accounts cb ON cb.id = p.bank_id
                WHERE p.booking_id = ?
                AND p.payment_type IN('booking_money','registration','land_share','emi','development_charge','others')
                AND (p.is_trashed = 0 OR p.is_trashed IS NULL)
                ORDER BY p.id ASC
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPaymentCount()
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM lead_payments"
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    // Check if booking payment already exists
    public function getByBookingType($booking_id, $type)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM lead_payments
            WHERE booking_id = ? AND payment_type = ?
        ");
        $stmt->bind_param("is", $booking_id, $type);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getByLead($lead_id)
    {
        $stmt = $this->db->prepare("
        SELECT o.*, p.name AS project_name, f.floor_no, f.sqft, f.flat_name AS unit_name
        FROM lead_payments o
        LEFT JOIN projects p ON o.project_id = p.id
        LEFT JOIN flats f ON o.flat_id = f.id
        WHERE o.lead_id = ?
    ");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }


    public function getFollowUpCountByLead()
    {
        $sql = "
        SELECT lead_id, COUNT(*) AS follow_up_count
        FROM lead_follow_ups
        GROUP BY lead_id
    ";

        $result = $this->db->query($sql);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[$row['lead_id']] = $row['follow_up_count'];
        }

        return $data;
    }

    public function getInvoiceData($payment_id)
    {
        $stmt = $this->db->prepare("SELECT
            lp.*,

            lb.id AS booking_id,
            lb.agreement_value,
            lb.discount_amount,
            lb.booking_date,
            l.id AS lead_id,
            l.full_name AS full_name,
            l.2nd_lead_owner_name,
            l.2nd_owner_phone,
            l.father_name,l.mother_name,l.email,l.address,l.nid_or_passport,l.permanent_address,l.dob,l.phone,l.designation AS profession,
            l.lead_name_bn,
            l.father_name_bn,l.mother_name_bn,l.profession_bn,l.permanent_add_bn,l.present_add_bn,l.identity_type,l.nationality,l.religion,l.lead_image,
            
            l.nomi_image,l.relation_with_lead,l.nominee_name,l.nomi_father_name,l.nomi_mother_name,l.nomi_profession,l.nomi_dob,l.nomi_phone,l.nomi_email,
            l.nomi_nid_passport,l.nomi_present_add,l.nomi_permanent_address,
            p.name AS project_name,
            p.car_parking,
            p.total_share,
            f.flat_name,
            f.floor_no,
            f.sqft,
            f.price_value,

            cr.full_name AS created_by_name,
            cb.bank_name AS company_bank_name,
            cb.account_name AS company_account_name,
            cb.account_number AS company_account_number

        FROM lead_payments lp

        INNER JOIN lead_bookings lb 
            ON lb.id = lp.booking_id

        INNER JOIN leads l 
            ON l.id = lb.lead_id

        INNER JOIN projects p 
            ON p.id = lb.project_id

        INNER JOIN flats f 
            ON f.id = lb.flat_id

        LEFT JOIN users cr 
            ON cr.id = lp.created_by

        LEFT JOIN company_bank_accounts cb 
            ON cb.id = lp.bank_id

        WHERE lp.id = ?
        LIMIT 1");

        $stmt->bind_param("i", $payment_id);
        $stmt->execute();

        $invoice = $stmt->get_result()->fetch_assoc();

        if ($invoice && !empty($invoice['batch_id'])) {
            // It's part of a split/batch, get total amount for this batch
            $batchStmt = $this->db->prepare("SELECT SUM(amount) as total_batch_amount FROM lead_payments WHERE batch_id = ? AND booking_id = ?");
            $batchStmt->bind_param("si", $invoice['batch_id'], $invoice['booking_id']);
            $batchStmt->execute();
            $batchRow = $batchStmt->get_result()->fetch_assoc();
            
            if ($batchRow && $batchRow['total_batch_amount'] > 0) {
                // If it's a combined payment, we can change the display type to indicate it
                if ($invoice['payment_type'] === 'booking_money' || $invoice['payment_type'] === 'registration') {
                    $invoice['payment_type'] = 'Booking_and_Registration'; 
                }
                $invoice['amount'] = $batchRow['total_batch_amount'];
            }
        }

        return $invoice;
    }

    public function isPaymentVerified($payment_id)
    {
        $stmt = $this->db->prepare("SELECT verification_status FROM lead_payments WHERE id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ($row && ($row['verification_status'] === 'verified'));
    }

    public function hasVerifiedPayment($booking_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM lead_payments WHERE booking_id = ? AND verification_status = 'verified'");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ($row && (int)$row['cnt'] > 0);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM lead_payments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update payment by ID (allowed for verified records only when explicitly authorized via $allowVerified)
    public function updatePayment($data, $allowVerified = false)
    {
        $id = (int)($data['id'] ?? 0);
        if (!$allowVerified && $this->isPaymentVerified($id)) {
            return false;
        }

        $fields = [];
        $params = [];
        $types = "";

        if (isset($data['payment_type'])) {
            $fields[] = "payment_type = ?";
            $params[] = $data['payment_type'];
            $types .= "s";
        }

        if (isset($data['amount'])) {
            $fields[] = "amount = ?";
            $params[] = (float)$data['amount'];
            $types .= "d";
        }

        if (isset($data['payment_method'])) {
            $fields[] = "payment_method = ?";
            $params[] = $data['payment_method'];
            $types .= "s";
        }

        if (isset($data['payment_status'])) {
            $fields[] = "payment_status = ?";
            $params[] = $data['payment_status'];
            $types .= "s";
        }

        if (isset($data['payment_date'])) {
            $fields[] = "payment_date = ?";
            $params[] = $data['payment_date'];
            $types .= "s";
        }

        if (array_key_exists('total_emi_no', $data)) {
            $fields[] = "total_emi_no = ?";
            $params[] = !empty($data['total_emi_no']) ? (int)$data['total_emi_no'] : null;
            $types .= "i";
        }

        if (array_key_exists('transaction_ref', $data)) {
            $fields[] = "transaction_ref = ?";
            $params[] = $data['transaction_ref'];
            $types .= "s";
        }

        if (array_key_exists('bank_id', $data)) {
            $fields[] = "bank_id = ?";
            $params[] = !empty($data['bank_id']) ? (int)$data['bank_id'] : null;
            $types .= "i";
        }

        if (array_key_exists('bank_name', $data)) {
            $fields[] = "bank_name = ?";
            $params[] = $data['bank_name'];
            $types .= "s";
        }

        if (array_key_exists('bank_account_no', $data)) {
            $fields[] = "bank_account_no = ?";
            $params[] = $data['bank_account_no'];
            $types .= "s";
        }

        if (array_key_exists('cheque_no', $data)) {
            $fields[] = "cheque_no = ?";
            $params[] = $data['cheque_no'];
            $types .= "s";
        }

        if (array_key_exists('online_type', $data)) {
            $fields[] = "online_type = ?";
            $params[] = $data['online_type'];
            $types .= "s";
        }

        if (!empty($data['attachment_image'])) {
            $fields[] = "attachment_image = ?";
            $params[] = $data['attachment_image'];
            $types .= "s";
        }

        if (!empty($data['attachment_image2'])) {
            $fields[] = "attachment_image2 = ?";
            $params[] = $data['attachment_image2'];
            $types .= "s";
        }

        if (!empty($data['attachment_image3'])) {
            $fields[] = "attachment_image3 = ?";
            $params[] = $data['attachment_image3'];
            $types .= "s";
        }

        if (!empty($data['attachment_image4'])) {
            $fields[] = "attachment_image4 = ?";
            $params[] = $data['attachment_image4'];
            $types .= "s";
        }

        // Keep attachment_paths in sync if any image changed
        if (!empty($data['attachment_image']) || !empty($data['attachment_image2']) || !empty($data['attachment_image3']) || !empty($data['attachment_image4'])) {
            $paymentId = (int)($data['id'] ?? 0);
            if ($paymentId > 0) {
                $chkExisting = $this->db->query("SELECT attachment_image, attachment_image2, attachment_image3, attachment_image4 FROM lead_payments WHERE id = {$paymentId}")->fetch_assoc();
                $cur1 = !empty($data['attachment_image']) ? $data['attachment_image'] : ($chkExisting['attachment_image'] ?? '');
                $cur2 = !empty($data['attachment_image2']) ? $data['attachment_image2'] : ($chkExisting['attachment_image2'] ?? '');
                $cur3 = !empty($data['attachment_image3']) ? $data['attachment_image3'] : ($chkExisting['attachment_image3'] ?? '');
                $cur4 = !empty($data['attachment_image4']) ? $data['attachment_image4'] : ($chkExisting['attachment_image4'] ?? '');
                $newPaths = [];
                foreach ([$cur1, $cur2, $cur3, $cur4] as $f) {
                    if (!empty($f)) $newPaths[] = 'uploads/payments/' . $f;
                }
                if (!empty($newPaths)) {
                    $fields[] = "attachment_paths = ?";
                    $params[] = json_encode($newPaths);
                    $types .= "s";
                }
            }
        }

        if (isset($data['updated_by'])) {
            $fields[] = "updated_by = ?";
            $params[] = (int)$data['updated_by'];
            $types .= "i";
        }
        $fields[] = "updated_at = NOW()";

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE lead_payments SET " . implode(", ", $fields);

        if ($allowVerified) {
            $sql .= " WHERE id = ?";
        } else {
            $sql .= " WHERE id = ? AND (verification_status != 'verified' OR verification_status IS NULL)";
        }
        $params[] = $id;
        $types .= "i";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    // Delete payment by ID (only allowed if not verified)
    public function deletePayment($id)
    {
        if ($this->isPaymentVerified($id)) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM lead_payments WHERE id = ? AND (verification_status != 'verified' OR verification_status IS NULL)");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
