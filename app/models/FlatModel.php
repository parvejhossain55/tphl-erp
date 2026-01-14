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
    public function getAll($search = '', $project_id = '')
    {
        $sql = "
        SELECT 
            f.*,
            p.name AS project_name,
            u.name AS unit_name
        FROM flats AS f
        LEFT JOIN projects AS p ON f.project_id = p.id
        LEFT JOIN units AS u ON f.unit_id = u.id
        WHERE f.deleted_at IS NULL
    ";

        $params = [];
        $types  = "";

        // 🔍 Search by flat name or sqft
        if (!empty($search)) {
            $sql .= " AND (f.flat_name LIKE ? OR f.sqft LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types   .= "ss";
        }

        //  Project wise filter
        if (!empty($project_id)) {
            $sql .= " AND f.project_id = ?";
            $params[] = $project_id;
            $types   .= "i";
        }

        $sql .= " ORDER BY f.id DESC";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    public function getFlatStatusCount()
    {
        $sql = $this->db->prepare("
        SELECT 
            SUM(CASE WHEN is_booked = 1 THEN 1 ELSE 0 END) AS booked,
            SUM(CASE WHEN is_booked = 0 THEN 1 ELSE 0 END) AS available
        FROM flats
        WHERE flats.deleted_at IS NULL
    ");

        $sql->execute();
        return $sql->get_result()->fetch_assoc() ?? [
            'booked' => 0,
            'available' => 0
        ];
    }
    /* =========================
       Create Project
    ========================== */
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO flats (
                project_id,
                flat_name,
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
                facilities,
                created_by
            ) VALUES (?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)
        ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param(
            "isiiiiiiiiidsi",
            $data['project_id'],
            $data['flat_name'],
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
            $data['facilities'],
            $data['created_by']
        );

        return $stmt->execute();
    }

    /* =========================
       Find Project (Edit)
    ========================== */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT 
        f.*, p.name AS project_name, u.name AS unit_name  FROM flats AS f 
        JOIN projects AS p ON f.project_id = p.id
        JOIN units AS u ON f.unit_id = u.id
        WHERE f.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    /**
     * Update lead
     */
    public function update($id, $data)
    {
        $sql = "UPDATE flats SET
        project_id = ?,flat_name=?,  sqft = ?, bhk = ?, bedroom = ?, bathroom = ?, balcony = ?,
        drawing_dining_kitchen = ?, common_dining = ?, floor_no = ?, facing_site = ?, price_value = ?, facilities = ?,created_by=?
        WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "isiiiiiiiisdsii",
            $data['project_id'],
            $data['flat_name'],
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
            $data['facilities'],
            $data['created_by'],
            $id
        );


        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        return true;
    }
    public function getProjectsFromFlats()
    {
        $sql = "SELECT DISTINCT p.id, p.name
                FROM flats f
                INNER JOIN projects p ON f.project_id = p.id
                ORDER BY p.name ASC";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getFlatsByProject($projectId)
    {
        $stmt = $this->db->prepare("SELECT f.id, f.flat_name,f.is_booked,f.sqft,f.floor_no
                                     FROM flats f
                                     WHERE f.project_id = ?
                                     ORDER BY f.flat_name ASC");
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }



    public function getFlatsWithUnitByProject($projectId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                f.id AS flat_id,
                f.is_booked,
                f.floor_no,
                f.sqft,
                f.bhk,
                f.price_value,
                f.flat_name
            FROM flats f
            WHERE f.project_id = ?
            AND f.deleted_at IS NULL
            ORDER BY f.floor_no ASC,f.flat_name ASC
        ");

        $stmt->bind_param("i", $projectId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function softDelete($flatId)
    {
        $this->db->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');

            $tables = [
                ['flats', 'id'],
                ['lead_follow_ups', 'flat_id'],
                ['lead_bookings', 'flat_id'],
            ];

            foreach ($tables as [$table, $col]) {
                $stmt = $this->db->prepare(
                    "UPDATE $table SET deleted_at=? WHERE $col=?"
                );
                $stmt->bind_param("si", $now, $flatId);
                $stmt->execute();
                $stmt->close();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }



}
