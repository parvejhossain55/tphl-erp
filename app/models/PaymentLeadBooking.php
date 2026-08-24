<?php

class PaymentLeadBooking extends Model
{
    public $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db; // mysqli connection
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
             payment_date, transaction_ref, total_emi_no, created_by, attachment_image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $v = function($key) use ($data) {
            return (isset($data[$key]) && trim($data[$key]) !== '') ? trim($data[$key]) : null;
        };

        $v_booking_id = $v('booking_id'); $v_bank_id = $v('bank_id'); $v_bank_name = $v('bank_name'); $v_bank_account_no = $v('bank_account_no');
        $v_cheque_no = $v('cheque_no'); $v_online_type = $v('online_type'); $v_payment_type = $v('payment_type');
        $v_amount = $v('amount'); $v_payment_method = $v('payment_method'); $v_payment_status = $v('payment_status');
        $v_verification_status = $v('verification_status') ?? 'pending'; $v_verified_by = $v('verified_by');
        $v_payment_date = $v('payment_date'); $v_transaction_ref = $v('transaction_ref'); $v_total_emi_no = $v('total_emi_no');
        $v_created_by = $v('created_by'); $v_attachment_image = $v('attachment_image');

        $stmt->bind_param(
            "iisssssdsssisssss",
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
            $v_attachment_image
        );

        if (!$stmt->execute()) {
            throw new Exception("Payment insert failed: " . $stmt->error);
        }

        return $this->db->insert_id;
    }

    public function create($data)
    {
        $this->db->begin_transaction();

        try {
            $stmt = $this->db->prepare("
            INSERT INTO lead_payments
            (booking_id, bank_id, bank_name, bank_account_no, cheque_no, online_type, 
             payment_type, amount, payment_method, payment_status, verification_status,
             payment_date, transaction_ref, total_emi_no, created_by, attachment_image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

            $verificationStatus = $data['verification_status'] ?? 'pending';
            $attachment_image = $data['attachment_image'] ?? '';

            $stmt->bind_param(
                "iisssssdsssssiss",
                $data['booking_id'],
                $data['bank_id'],
                $data['bank_name'],
                $data['bank_account_no'],
                $data['cheque_no'],
                $data['online_type'],
                $data['payment_type'],
                $data['amount'],
                $data['payment_method'],
                $data['payment_status'],
                $verificationStatus,
                $data['payment_date'],
                $data['transaction_ref'],
                $data['total_emi_no'],
                $data['created_by'],
                $attachment_image
            );

            if (!$stmt->execute()) {
                throw new Exception("Payment insert failed: " . $stmt->error);
            }

            $insertId = $stmt->insert_id ?: $this->db->insert_id;
            $this->db->commit();
            return $insertId;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log($e->getMessage());
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
                    us.full_name AS verified_by_name
                FROM lead_payments p
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN lead_bookings lb ON p.booking_id = lb.id
                LEFT JOIN users us ON us.id = p.verified_by
                WHERE p.booking_id = ?
                AND p.payment_type IN('booking_money','registration')
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

            cr.full_name AS created_by_name

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

        WHERE lp.id = ?
        LIMIT 1");

        $stmt->bind_param("i", $payment_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // Update follow-up by ID
    public function update($data)
    {
        $stmt = $this->db->prepare("
        UPDATE lead_follow_ups SET
            lead_id = ?,
            flat_id = ?,
            follow_up_date = ?,
            follow_up_time = ?,
            follow_up_type = ?,
            status = ?,
            discussion_note = ?,
            next_follow_up_date = ?,
            next_action = ?
        WHERE id = ?
    ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }
        $stmt->bind_param(
            "iisssssssi",
            $data['lead_id'],
            $data['flat_id'],   // NULL or valid flats.id
            $data['follow_up_date'],
            $data['follow_up_time'],
            $data['follow_up_type'],
            $data['status'],
            $data['discussion_note'],
            $data['next_follow_up_date'],
            $data['next_action'],
            $data['id']
        );


        return $stmt->execute();
    }
    // Optional: get onboard records by lead



    // Delete follow-up by ID
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM lead_follow_ups WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
