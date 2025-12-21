<?php

class FlatModel extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /* =========================
       Get All Projects
    ========================== */
    public function getAll()
    {
        $sql = $this->db->prepare("
            SELECT 
                f.*,
                p.name AS project_name,
                u.name AS unit_name
            FROM flats AS f
            LEFT JOIN projects AS p ON f.project_id = p.id
            LEFT JOIN units AS u ON f.unit_id = u.id
            ORDER BY p.id DESC
        ");

        if (!$sql->execute()) {
            return [];
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }





    /* =========================
       Create Project
    ========================== */
  public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO flats (
                project_id,
                unit_id,
                sqft,
                bhk,
                bedroom,
                bathroom,
                balcony,
                drawing_dining_kitchen,
                common_dining,
                floor_no,
                facing_site,
                price_value,
                facilities
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param(
            "iiiiiiiiiiids",
            $data['project_id'],
            $data['unit_id'],
            $data['sqft'],
            $data['bhk'],
            $data['bedroom'],
            $data['bathroom'],
            $data['balcony'],
            $data['drawing_dining_kitchen'],
            $data['common_dining'],
            $data['floor_no'],
            $data['facing_site'],
            $data['price_value'],
            $data['facilities']
        );

        return $stmt->execute();
    }




    /* =========================
       Find Project (Edit)
    ========================== */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
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
        lead_name = ?, lead_type = ?, company = ?, value_amount = ?, currency = ?, phone = ?, alt_phone_number = ?,
        email = ?, source_type_id = ?, address = ?, pref_location = ?, designation = ?, project_type = ?,
        interest_on = ?, pref_flat_size = ?, communication_type = ?, tags = ?, description = ?, visibility = ?, status = ?, lead_quality = ?
        WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "sssdssssissssssssssssi",
            $data['lead_name'],
            $data['lead_type'],
            $data['company'],
            $data['value_amount'],
            $data['currency'],
            $data['phone'],
            $data['alt_phone_number'],
            $data['email'],
            $data['source_type_id'],
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



  public function getProjectsFromFlats() {
        $sql = "SELECT DISTINCT p.id, p.name
                FROM flats f
                INNER JOIN projects p ON f.project_id = p.id
                ORDER BY p.name ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getFlatsByProject($projectId) {
        $stmt = $this->db->prepare("SELECT f.id, u.name AS flat_name
                                     FROM flats f
                                     INNER JOIN units u ON f.unit_id = u.id
                                     WHERE f.project_id = ?
                                     ORDER BY u.name ASC");
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }



     public function getFlatsWithUnitByProject($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                f.id AS flat_id,
                f.floor_no,
                f.sqft,
                f.bhk,
                f.price_value,
                u.name AS unit_name
            FROM flats f
            JOIN units u ON f.unit_id = u.id
            WHERE f.project_id = ?
            ORDER BY f.floor_no ASC, u.name ASC
        ");

        $stmt->bind_param("i", $projectId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
