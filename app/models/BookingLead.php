<?php

class BookingLead extends Model
{
    public $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db; // mysqli connection
    }

    // Get all follow-ups with related lead, project, flat, and creator info
    public function getAllOnBoard()
    {
        $sql = "SELECT lb.*, 
       l.full_name, 
       p.name AS project_name, 
       u2.name AS flat_name, 
       u1.full_name AS created_by_name
FROM lead_bookings lb
LEFT JOIN leads l ON lb.lead_id = l.id
LEFT JOIN flats f ON lb.flat_id = f.id
LEFT JOIN units u2 ON f.unit_id = u2.id
LEFT JOIN projects p ON f.project_id = p.id
LEFT JOIN users u1 ON lb.created_by = u1.id
WHERE lb.deleted_at IS NULL
ORDER BY lb.follow_up_date DESC, lb.follow_up_time ASC
";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

public function getAllOnboardClient($year = null, $month = null)
{
    $sql = "SELECT 
                lb.*, 
                l.full_name, 
                l.id as lead_id,
                l.phone,
                l.email,
                p.name AS project_name, 
                u2.name AS flat_name, 
                lp.payment_method ,
                lp.amount,
                lp.created_at As booking_pay_date,
                u1.full_name AS created_by_name,
                f.flat_name ,f.sqft,
                f.price_value
            FROM lead_bookings lb
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN units u2 ON f.unit_id = u2.id
            LEFT JOIN projects p ON f.project_id = p.id
            LEFT JOIN users u1 ON lb.created_by = u1.id
            LEFT JOIN  lead_payments AS lp ON lp.booking_id = lb.id
            WHERE lb.deleted_at IS NULL,
             AND lb.booking_status = 'completed'
             ";

    $params = [];
    $types  = "";
// 
    if (!empty($year)) {
        $sql .= " AND YEAR(l.created_at) = ?";
        $params[] = (int)$year;
        $types   .= "i";
    }

    if (!empty($month)) {
        $sql .= " AND MONTH(l.created_at) = ?";
        $params[] = (int)$month;
        $types   .= "i";
    }

    //  single ORDER BY at the end

    $stmt = $this->db->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        return [];
    }

    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}


    //dashboard
    public function getTotalBookings($period = null)
    {
        $sqlQuery = "SELECT COUNT(*) AS onboarded 
                 FROM lead_bookings 
                 WHERE booking_status = 'booked' 
                 AND deleted_at IS NULL";


        if ($period) {
            switch ($period) {
                case 'daily':
                    $sqlQuery .= " AND DATE(created_at) = CURDATE()";
                    break;
                case 'weekly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                case 'yearly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
            }
        }



        $result = $this->db->query($sqlQuery);
        $row = $result->fetch_assoc();
        return (int)($row['onboarded'] ?? 0);
    }

    // Get a single follow-up by ID
    public function create($data)
    {
        $sql = "
            INSERT INTO lead_bookings
            (
                lead_id,
                project_id,
                flat_id,
                created_by,
                agreement_value,
                booking_date,
                booking_status,
                discount_amount,
                approval_status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        $stmt->bind_param(
            "iiiidssss",
            $data['lead_id'],
            $data['project_id'],
            $data['flat_id'],
            $data['created_by'],
            $data['agreement_value'],
            $data['booking_date'],
            $data['booking_status'],
            $data['discount_amount'],
            $data['approval_status']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }

        return false;
    }

    // Update follow-up by ID
    public function update($data, $id)
    {
        $stmt = $this->db->prepare("
        UPDATE lead_bookings SET
            lead_id = ?,
            flat_id = ?,
            project_id = ?,
            created_by = ?,
            agreement_value = ?,
            booking_date = ?,
            booking_status = ?
        WHERE id = ?
    ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param(
            "iiiidssi",
            $data['lead_id'],
            $data['flat_id'],
            $data['project_id'],
            $data['created_by'],
            $data['agreement_value'],
            $data['booking_date'],
            $data['booking_status'],
            $id
        );

        return $stmt->execute();
    }

    /**
     * Update lead_payments.amount for the booking_money payment type
     */
    public function updateBookingPaymentAmount($bookingId, $amount)
    {
        $stmt = $this->db->prepare("
        UPDATE lead_payments
        SET amount = ?
        WHERE booking_id = ? AND payment_type = 'booking_money'
    ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param("di", $amount, $bookingId);
        return $stmt->execute();
    }

    // Optional: get onboard records by lead
    public function getByLead($lead_id)
    {
        $stmt = $this->db->prepare("
        SELECT o.*, 
               p.name AS project_name, 
               f.floor_no,
               f.flat_name, 
               f.sqft,
               f.price_value, 
               u.name AS unit_name
        FROM lead_bookings o
        LEFT JOIN projects p ON o.project_id = p.id
        LEFT JOIN flats f ON o.flat_id = f.id
        LEFT JOIN units u ON f.unit_id = u.id
        WHERE o.deleted_at IS NULL
          AND o.lead_id = ?
    ");

        if (!$stmt) {
            setError($this->db->error);
            return [];
        }

        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getBookingCountByLead()
    {
        $stmt = $this->db->prepare(
            "SELECT 
            lb.lead_id,
            COUNT(DISTINCT lb.id) AS booking_count
         FROM lead_bookings lb
         INNER JOIN lead_payments lp 
            ON lp.booking_id = lb.id
         GROUP BY lb.lead_id"
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['lead_id']] = $row['booking_count'];
        }

        return $data;
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
        SELECT lb.*, 
               l.full_name, 
               p.name AS project_name,
               p.total_share,
               f.floor_no, 
               f.price_value,
               f.sqft,
               u.name AS unit_name
        FROM lead_bookings lb
        LEFT JOIN leads l ON lb.lead_id = l.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        LEFT JOIN units u ON f.unit_id = u.id
        LEFT JOIN projects p ON lb.project_id = p.id
        WHERE lb.id = ?
        LIMIT 1
    ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // single record
    }


    public function getAllBooking()
    {
        $stmt = $this->db->prepare("SELECT lb.*, l.full_name,l.lead_image,l.phone, p.name AS project_name,f.price_value,f.flat_name,f.sqft,f.is_booked
            FROM lead_bookings lb LEFT JOIN leads l ON lb.lead_id = l.id 
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            Where lb.deleted_at IS NULL");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Delete follow-up by ID
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM lead_follow_ups WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
