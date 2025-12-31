<?php

class Lead extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    public function getAll()
    {
        $sql = $this->db->prepare("
        SELECT 
            l.*,
            u.id AS assigned_id,
            u.full_name AS agent_name
        FROM leads AS l
        LEFT JOIN users AS u ON l.assigned_id = u.id
    ");
        $sql->execute();
        if (!$sql->execute()) {
            return [];
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }



    public function create($data)
    {
        $stmt = $this->db->prepare("
        INSERT INTO leads (
            lead_name, father_name, mother_name, dob, permanent_address, nid_or_passport,
            phone, alt_phone_number, email,
            source_type_id, assigned_id,
            address, pref_location, designation,
            project_type, interest_on, pref_flat_size,
            communication_type, tags, description,
            visibility, status, lead_quality, lead_type, company
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $interest_on = $data['interest_on'] ?? null;
        $pref_flat_size = $data['pref_flat_size'] ?? null;
        $communication_type = $data['communication_type'] ?? null;
        $tags = $data['tags'] ?? null;
        $description = $data['description'] ?? null;
        $visibility = $data['visibility'] ?? 'public';
        $status = $data['status'] ?? 'active';
        $lead_quality = $data['lead_quality'] ?? 'Pending';
        $lead_type = $data['lead_type'] ?? null;
        $company = $data['company'] ?? null;

        // Bind parameters
        $stmt->bind_param(
            "sssssssssssisssssssssssss",
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
            $interest_on,
            $pref_flat_size,
            $communication_type,
            $tags,
            $description,
            $visibility,
            $status,
            $lead_quality,
            $lead_type,
            $company
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
        $sql = "UPDATE leads SET
        lead_name = ?, father_name = ?, mother_name = ?, dob = ?, permanent_address = ?, nid_or_passport = ?,
        phone = ?, alt_phone_number = ?,
        email = ?, source_type_id = ?, assigned_id=?, address = ?, pref_location = ?, designation = ?, project_type = ?,
        interest_on = ?, pref_flat_size = ?, communication_type = ?, tags = ?, description = ?, visibility = ?, status = ?, lead_quality = ?
        WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "sssssssssssiissssssssssssi",
            $data['lead_name'],
            $data['father_name'],
            $data['mother_name'],
            $data['dob'],
            $data['permanent_address'],
            $data['nid_or_passport'],
            $data['phone'],
            $data['alt_phone_number'],
            $data['email'],
            $data['source_type_id'],
            $data['assigned_id'],
            $data['address'],
            $data['pref_location'],
            $data['designation'],
            $data['project_type'],
            $data['interest_on'],
            $data['pref_flat_size'],
            $data['communication_type'],
            $data['tags'],
            $data['description'],
            $data['visibility'],
            $data['status'],
            $data['lead_quality'],
            $id
        );

        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        return true;
    }


    //     public function getById($lead_id)
    // {
    //     $stmt = $this->db->prepare("
    //         SELECT *
    //         FROM leads
    //         WHERE id = ?
    //         LIMIT 1
    //     ");
    //     $stmt->bind_param("i", $lead_id);
    //     $stmt->execute();
    //     $result = $stmt->get_result();
    //     return $result->fetch_assoc(); // single record
    // }
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
