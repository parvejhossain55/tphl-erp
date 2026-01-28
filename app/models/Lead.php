<?php

class Lead extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    public function getAll($period = null)
    {
        // Base query
        $sqlQuery = "
        SELECT 
            l.*,
            u.id AS assigned_id,
            u.full_name AS agent_name
        FROM leads AS l
        LEFT JOIN users AS u ON l.assigned_id = u.id
        WHERE l.status = 'active'
        AND l.deleted_at IS NULL
    ";

        // Add period filter
        if ($period) {
            switch ($period) {
                case 'daily':
                    $sqlQuery .= " AND DATE(l.created_at) = CURDATE()";
                    break;
                case 'weekly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                case 'yearly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
            }
        }

        $stmt = $this->db->prepare($sqlQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getAllByDate($period, $previous = false)
{
    $sqlQuery = "
        SELECT l.* FROM leads AS l
        WHERE l.status = 'active' AND l.deleted_at IS NULL
    ";

    $today = date('Y-m-d');

    if ($period) {
        switch ($period) {
            case 'daily':
                $date = $previous ? date('Y-m-d', strtotime('-1 day')) : $today;
                $sqlQuery .= " AND DATE(l.created_at) = '$date'";
                break;
            case 'weekly':
                if ($previous) {
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND l.created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                } else {
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                }
                break;
            case 'monthly':
                if ($previous) {
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND l.created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                } else {
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                }
                break;
            case 'yearly':
                if ($previous) {
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) AND l.created_at < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                } else {
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                }
                break;
        }
    }

    $stmt = $this->db->prepare($sqlQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}




    public function getAllWithFilter($year = null, $month = null)
    {
        $sql = "
        SELECT 
            l.*,
            u.id AS assigned_id,
            u.full_name AS agent_name
        FROM leads AS l
        LEFT JOIN users AS u ON l.assigned_id = u.id
        WHERE l.status = 'active'
        AND l.deleted_at IS NULL
        ";


        $params = [];
        $types  = "";

        if (!empty($year)) {
            $sql .= " AND YEAR(l.created_at) = ?";
            $params[] = $year;
            $types   .= "i";
        }

        if (!empty($month)) {
            $sql .= " AND MONTH(l.created_at) = ?";
            $params[] = $month;
            $types   .= "i";
        }

        $sql .= " ORDER BY l.id DESC";

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


public function getVisitedLeadCount($period = null)
{
    $sqlQuery = "
        SELECT COUNT(*) AS total_visited
        FROM leads
        WHERE status = 'active'
        AND deleted_at IS NULL
        AND last_lead_action = 'visited'
    ";

    // Add period filter
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

    $stmt = $this->db->prepare($sqlQuery);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return (int)($result['total_visited'] ?? 0);
}




    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO leads (
            lead_name, father_name, mother_name, dob, permanent_address, nid_or_passport,
            phone, alt_phone_number, email,
            source_type_id, assigned_id,
            address, pref_location, designation,
            project_type, pref_flat_size,
            communication_type, tags, description,
            visibility, status, lead_quality, lead_type, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        if (!$stmt) {
            setError("Prepare failed: " . $this->db->error);
            return false;
        }

        // Assign variables first (pass by reference)
        $lead_name = $data['lead_name'] ?? null;
        $father_name = $data['father_name'] ?? null;
        $mother_name = $data['mother_name'] ?? null;
        $dob = $data['dob'] ?? null;
        $permanent_address = $data['permanent_address'] ?? null;
        $nid_or_passport = $data['nid_or_passport'] ?? null;
        $phone = $data['phone'] ?? null;
        $alt_phone_number = $data['alt_phone_number'] ?? null;
        $email = $data['email'] ?? null;
        $source_type_id = $data['source_type_id'] ?? null;
        $assigned_id = $data['assigned_id'] ?? null;
        $address = $data['address'] ?? null;
        $pref_location = $data['pref_location'] ?? null;
        $designation = $data['designation'] ?? null;
        $project_type = $data['project_type'] ?? null;
        $pref_flat_size = $data['pref_flat_size'] ?? null;
        $communication_type = $data['communication_type'] ?? null;
        $tags = $data['tags'] ?? null;
        $description = $data['description'] ?? null;
        $visibility = $data['visibility'] ?? 'public';
        $status = $data['status'] ?? 'active';
        $lead_quality = $data['lead_quality'] ?? 'Pending';
        $lead_type = $data['lead_type'] ?? null;
        $created_by = $data['created_by'] ?? null;

        // Bind parameters
        $stmt->bind_param(
            "sssssssssssisssssssssssi",
            $lead_name,
            $father_name,
            $mother_name,
            $dob,
            $permanent_address,
            $nid_or_passport,
            $phone,
            $alt_phone_number,
            $email,
            $source_type_id,
            $assigned_id,
            $address,
            $pref_location,
            $designation,
            $project_type,
            $pref_flat_size,
            $communication_type,
            $tags,
            $description,
            $visibility,
            $status,
            $lead_quality,
            $lead_type,
            $created_by
        );

        if ($stmt->execute()) {
            setSuccess("Lead created successfully!");
            return true;
        } else {
            setError("Lead creation failed: " . $stmt->error);
            return false;
        }
    }





    //employee id find for edit
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT 
            leads.*,
            s.name AS source_name,
            u.full_name AS user_name,
            COUNT(f.id) AS follow_up_count

        FROM leads
        LEFT JOIN lead_sources s 
            ON leads.source_type_id = s.id
        LEFT JOIN lead_follow_ups f 
            ON leads.id = f.lead_id
        LEFT JOIN users u 
            ON leads.assigned_id = u.id

        WHERE leads.id = ?
        GROUP BY leads.id"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    /**
     * Update lead
     */
    public function update($id, $data)
    {
        $old = $this->find($id);
        if (!$old) {
            return false;
        }
        $nomi_dob = !empty($data['nomi_dob']) ? $data['nomi_dob'] : null;
        // ======================
        // Image Upload (fallback old)
        // ======================
        $lead_image = !empty($_FILES['lead_image']['name'])
            ? uploadImage('lead_image', 'leads', 'fixed')
            : $old['lead_image'];

        $nomi_image = !empty($_FILES['nomi_image']['name'])
            ? uploadImage('nomi_image', 'leads', 'fixed')
            : $old['nomi_image'];

        // ======================
        // Prepare SQL
        // ======================
        $sql = "UPDATE leads SET
        lead_name = ?, father_name = ?, mother_name = ?, dob = ?, permanent_address = ?, nid_or_passport = ?,
        phone = ?, alt_phone_number = ?, email = ?, address = ?, designation = ?,
        lead_name_bn = ?, father_name_bn = ?, mother_name_bn = ?, identity_type = ?, permanent_add_bn = ?,
        present_add_bn = ?, profession_bn = ?, nationality = ?, religion = ?, lead_image = ?,
        nominee_name = ?, relation_with_lead = ?, nomi_father_name = ?, nomi_mother_name = ?, nomi_profession = ?,
        nomi_dob = ?, nomi_phone = ?, nomi_email = ?, nomi_nid_passport = ?, nomi_present_add = ?, 
        nomi_permanent_address = ?, nomi_image = ?, description = ?,created_by = ?
        WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        // ======================
        // Bind all strings + id as integer
        // ======================
        $stmt->bind_param(
            str_repeat("s", 34) . "ii", // 34 strings + 1 integer for id
            $data['lead_name'],
            $data['father_name'],
            $data['mother_name'],
            $data['dob'],
            $data['permanent_address'],
            $data['nid_or_passport'],
            $data['phone'],
            $data['alt_phone_number'],
            $data['email'],
            $data['address'],
            $data['designation'],
            $data['lead_name_bn'],
            $data['father_name_bn'],
            $data['mother_name_bn'],
            $data['identity_type'],
            $data['permanent_add_bn'],
            $data['present_add_bn'],
            $data['profession_bn'],
            $data['nationality'],
            $data['religion'],
            $lead_image,
            $data['nominee_name'],
            $data['relation_with_lead'],
            $data['nomi_father_name'],
            $data['nomi_mother_name'],
            $data['nomi_profession'],
            $nomi_dob,
            $data['nomi_phone'],
            $data['nomi_email'],
            $data['nomi_nid_passport'],
            $data['nomi_present_add'],
            $data['nomi_permanent_address'],
            $nomi_image,
            $data['description'],
            $data['created_by'],
            $id // integer
        );

        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }

        return true;
    }




    public function countLeadsByMonth($year, $month)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) AS total
        FROM leads
        WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
    ");
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }

    // public function delete($id)
    // {
    //     $stmt = $this->db->prepare("DELETE FROM leads WHERE id = ?");
    //     $stmt->bind_param("i", $id);
    //     return $stmt->execute();
    // }

    public function updateLastLeadAction($leadId, $lastAction)
    {
        $allowedActions = ['not_called', 'calling', 'called', 'visited'];

        if (!in_array($lastAction, $allowedActions)) {
            return false;
        }

        $sql = "UPDATE leads 
            SET last_lead_action = ?, 
                updated_at = NOW()
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $lastAction, $leadId);

        return $stmt->execute();
    }



    public function softDelete($leadId)
    {
        $this->db->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');

            // 1. Leads table (also update status)
            $stmt = $this->db->prepare("
            UPDATE leads 
            SET deleted_at = ?, status = 'inactive'
            WHERE id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            // 2. Lead bookings
            $stmt = $this->db->prepare("
            UPDATE lead_bookings 
            SET deleted_at = ?
            WHERE lead_id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            // 3. Lead follow ups
            $stmt = $this->db->prepare("
            UPDATE lead_follow_ups 
            SET deleted_at = ?
            WHERE lead_id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}
