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
               l.lead_name, 
               p.name AS project_name, 
               u2.name AS flat_name, 
               u1.full_name AS created_by_name
        FROM lead_bookings lb
        LEFT JOIN leads l ON lb.lead_id = l.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        LEFT JOIN units u2 ON f.unit_id = u2.id
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN users u1 ON lb.created_by = u1.id
        ORDER BY fu.follow_up_date DESC, fu.follow_up_time ASC ";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Create a new follow-up


    // Get a single follow-up by ID
    public function create($data)
    {
        $stmt = $this->db->prepare("
    INSERT INTO lead_bookings
    (lead_id, project_id, flat_id, created_by, agreement_value, booking_date, booking_status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

        $stmt->bind_param(
            "iiiidss",
            $data['lead_id'],
            $data['project_id'],
            $data['flat_id'],
            $data['created_by'],
            $data['agreement_value'],
            $data['booking_date'],
            $data['booking_status']
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }

        return false;
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
            booking_status = ?,
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
            $data['booking_status'],
            $data['discussion_note'],
            $data['next_follow_up_date'],
            $data['next_action'],
            $data['id']
        );
        return $stmt->execute();
    }
    // Optional: get onboard records by lead
    public function getByLead($lead_id)
    {
        $stmt = $this->db->prepare("
        SELECT o.*, p.name AS project_name, f.floor_no, f.sqft,f.price_value, u.name AS unit_name
        FROM lead_bookings o
        LEFT JOIN projects p ON o.project_id = p.id
        LEFT JOIN flats f ON o.flat_id = f.id
        LEFT JOIN units u ON f.unit_id = u.id
        WHERE o.lead_id = ?
    ");
        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
               l.lead_name, 
               p.name AS project_name,
               f.floor_no, 
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

    // Delete follow-up by ID
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM lead_follow_ups WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
