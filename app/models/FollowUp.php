<?php 

class FollowUp extends Model {
    public $db;

    public function __construct($db = null){
        parent::__construct($db);
        $this->db = $db; // mysqli connection
    }

    // Get all follow-ups with related lead, project, flat, and creator info
public function getAllFollowUp() {
    $sql = "SELECT fu.*, 
               l.lead_name, 
               p.name AS project_name, 
               u2.name AS flat_name, 
               u1.full_name AS created_by_name
        FROM lead_follow_ups fu
        LEFT JOIN leads l ON fu.lead_id = l.id
        LEFT JOIN flats f ON fu.flat_id = f.id
        LEFT JOIN units u2 ON f.unit_id = u2.id
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN users u1 ON fu.created_by = u1.id
        ORDER BY fu.follow_up_date DESC, fu.follow_up_time ASC ";
    $result = $this->db->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

    // Create a new follow-up
    public function create($data){
        $stmt = $this->db->prepare("
            INSERT INTO lead_follow_ups 
            (lead_id, flat_id, follow_up_date, follow_up_time, follow_up_type, status, discussion_note, next_follow_up_date, next_action, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param(
            "iissssssss",
            $data['lead_id'],
            // $data['project_id'],
            $data['flat_id'],
            $data['follow_up_date'],
            $data['follow_up_time'],
            $data['follow_up_type'],
            $data['status'],
            $data['discussion_note'],
            $data['next_follow_up_date'],
            $data['next_action'],
            $data['created_by']
        );

        return $stmt->execute();
    }

    // Get a single follow-up by ID
    public function getById($id){
        $stmt = $this->db->prepare("
            SELECT fu.*, 
                   l.lead_name, 
                   p.name AS project_name, 
                   f.flat_name, 
                   u.full_name AS created_by_name
            FROM lead_follow_ups fu
            LEFT JOIN leads l ON fu.lead_id = l.id
            LEFT JOIN projects p ON fu.project_id = p.id
            LEFT JOIN flats f ON fu.flat_id = f.id
            LEFT JOIN users u ON fu.created_by = u.id
            WHERE fu.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
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
public function getByLead($lead_id)
{
    $lead_id = (int)$lead_id; // sanitize input

    $sql = "
        SELECT f.*, 
               l.lead_name, 
               p.name AS project_name,
               CONCAT(u.name, ' - ', u.id) AS flat_name,
               usr.full_name AS created_by_name
        FROM lead_follow_ups f
        LEFT JOIN leads l ON f.lead_id = l.id
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN flats fl ON f.flat_id = fl.id
        LEFT JOIN units u ON fl.unit_id = u.id
        LEFT JOIN users usr ON f.created_by = usr.id
        WHERE f.lead_id = $lead_id
        ORDER BY f.follow_up_date DESC, f.follow_up_time DESC
    ";

    $result = $this->db->query($sql);

    $followUps = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $followUps[] = $row;
        }
    }

    return $followUps;
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


    // Delete follow-up by ID
    public function delete($id){
        $stmt = $this->db->prepare("DELETE FROM lead_follow_ups WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}

?>
