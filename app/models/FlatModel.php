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
            p.name AS project_name
        FROM flats AS f
        LEFT JOIN projects AS p ON f.project_id = p.id
        WHERE f.deleted_at IS NULL
    ";

        $params = [];
        $types  = "";
        if (!empty($search)) {
            $sql .= " AND (f.flat_name LIKE ? OR f.sqft LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types   .= "ss";
        }

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

    public function getTotalFlats()
    {
        $sql = $this->db->prepare("SELECT COUNT(*) AS total FROM flats WHERE deleted_at IS NULL");
        $sql->execute();
        $result = $sql->get_result()->fetch_assoc();
        return $result['total'];
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
        land_share_value,
        development_charges,
        created_by,
        status,
        is_booked
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }
        $stmt->bind_param(
            "isiiiiiiiisdsddssi",
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
            $data['land_share_value'],
            $data['development_charges'],
            $data['created_by'],
            $data['status'],
            $data['is_booked']
        );

        return $stmt->execute();
    }

    /* =========================
       Create Flat From CSV
    ========================== */
    public function createFromCsvBulk($data)
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
                land_share_value,
                development_charges,
                created_by,
                status,
                is_booked,
                upload_from
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        // Extract values into named variables (bind_param requires variables by reference)
        $project_id           = isset($data['project_id'])           && trim($data['project_id'])           !== '' ? trim($data['project_id'])           : null;
        $flat_name            = isset($data['flat_name'])            && trim($data['flat_name'])            !== '' ? trim($data['flat_name'])            : null;
        $sqft                 = isset($data['sqft'])                 && trim($data['sqft'])                 !== '' ? trim($data['sqft'])                 : null;
        $bhk                  = isset($data['bhk'])                  && trim($data['bhk'])                  !== '' ? trim($data['bhk'])                  : null;
        $bedroom              = isset($data['bedroom'])              && trim($data['bedroom'])              !== '' ? trim($data['bedroom'])              : null;
        $bathroom             = isset($data['bathroom'])             && trim($data['bathroom'])             !== '' ? trim($data['bathroom'])             : null;
        $balcony              = isset($data['balcony'])              && trim($data['balcony'])              !== '' ? trim($data['balcony'])              : null;
        $drawing_dining       = isset($data['drawing_dining_kitchen']) && trim($data['drawing_dining_kitchen']) !== '' ? trim($data['drawing_dining_kitchen']) : null;
        $common_dining        = isset($data['common_dining'])        && trim($data['common_dining'])        !== '' ? trim($data['common_dining'])        : null;
        $floor_no             = isset($data['floor_no'])             && trim($data['floor_no'])             !== '' ? trim($data['floor_no'])             : null;
        $facing_site          = isset($data['facing_site'])          && trim($data['facing_site'])          !== '' ? trim($data['facing_site'])          : null;
        $price_value          = isset($data['price_value'])          && trim($data['price_value'])          !== '' ? trim($data['price_value'])          : null;
        $facilities           = isset($data['facilities'])           && trim($data['facilities'])           !== '' ? trim($data['facilities'])           : null;
        $land_share_value     = isset($data['land_share_value'])     && trim($data['land_share_value'])     !== '' ? trim($data['land_share_value'])     : null;
        $development_charges  = isset($data['development_charges'])  && trim($data['development_charges'])  !== '' ? trim($data['development_charges'])  : null;
        $created_by           = isset($data['created_by'])           && trim($data['created_by'])           !== '' ? trim($data['created_by'])           : null;

        // If status is booked or sold, is_booked = 1
        $status      = strtolower(trim($data['status'] ?? 'available')) ?: 'available';
        $is_booked   = in_array($status, ['booked', 'sold']) ? 1 : 0;
        $upload_from = $data['upload_from'] ?? 'From company csv';

        $stmt->bind_param(
            "ssssssssssssssssssi",
            $project_id,
            $flat_name,
            $sqft,
            $bhk,
            $bedroom,
            $bathroom,
            $balcony,
            $drawing_dining,
            $common_dining,
            $floor_no,
            $facing_site,
            $price_value,
            $facilities,
            $land_share_value,
            $development_charges,
            $created_by,
            $status,
            $upload_from,
            $is_booked
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        return true;
    }

    /* =========================
       Find Project (Edit)
    ========================== */
    public function find($id)
    {
        $stmt = $this->db->prepare("
        SELECT 
            f.*, 
            p.name AS project_name
        FROM flats AS f 
        LEFT JOIN projects AS p ON f.project_id = p.id
        WHERE f.id = ?
    ");
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
        drawing_dining_kitchen = ?, common_dining = ?, floor_no = ?, facing_site = ?, price_value = ?, facilities = ?,created_by=?,land_share_value=?,development_charges=?
        WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "isiiiiiiiisdsiddi",
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
            $data['land_share_value'],
            $data['development_charges'],
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
                                     AND f.deleted_at IS NULL
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
                f.land_share_value,
                f.flat_name,
                (SELECT booking_status FROM lead_bookings lb WHERE lb.flat_id = f.id AND lb.deleted_at IS NULL AND lb.booking_status IN ('booked', 'completed') ORDER BY lb.id DESC LIMIT 1) AS booking_status,
                (SELECT created_at FROM lead_bookings lb WHERE lb.flat_id = f.id AND lb.deleted_at IS NULL AND lb.booking_status IN ('booked', 'completed') ORDER BY lb.id DESC LIMIT 1) AS booking_time
            FROM flats f
            WHERE f.project_id = ?
            AND f.deleted_at IS NULL
            ORDER BY f.floor_no ASC, f.flat_name ASC
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
