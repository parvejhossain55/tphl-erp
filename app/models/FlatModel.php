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
    public function getAll($search = '', $project_id = '', $status = '')
    {
        $sql = "
        SELECT 
            f.*,
            p.name AS project_name,
            u.full_name AS creator_name
        FROM flats AS f
        LEFT JOIN projects AS p ON f.project_id = p.id
        LEFT JOIN users AS u ON f.created_by = u.id
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

        // Status filter: available / booked / sold
        if (!empty($status) && in_array($status, ['available', 'booked', 'sold'])) {
            $sql .= " AND f.status = ?";
            $params[] = $status;
            $types   .= "s";
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
            SUM(CASE WHEN (is_booked = 1 OR status = 'booked') AND status != 'sold' THEN 1 ELSE 0 END) AS booked,
            SUM(CASE WHEN is_booked = 0 AND status != 'booked' AND status != 'sold' THEN 1 ELSE 0 END) AS available
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

    public function getFlatsAjax($start, $length, $search, $project_id, $status, $orderCol, $orderDir)
    {
        $sql = "
        SELECT 
            f.*,
            p.name AS project_name,
            u.full_name AS creator_name
        FROM flats AS f
        LEFT JOIN projects AS p ON f.project_id = p.id
        LEFT JOIN users AS u ON f.created_by = u.id
        WHERE f.deleted_at IS NULL
        ";

        $params = [];
        $types  = "";

        if (!empty($search)) {
            $sql .= " AND (f.flat_name LIKE ? OR f.sqft LIKE ? OR p.name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types   .= "sss";
        }

        if (!empty($project_id)) {
            $sql .= " AND f.project_id = ?";
            $params[] = $project_id;
            $types   .= "i";
        }

        if (!empty($status) && in_array($status, ['available', 'booked', 'sold'])) {
            $sql .= " AND f.status = ?";
            $params[] = $status;
            $types   .= "s";
        }

        // Ordering
        $allowedCols = [
            '1' => 'p.name',
            '2' => 'f.flat_name',
            '3' => 'f.unit_type',
            '4' => 'f.upload_from',
            '5' => 'f.facing_site',
            '6' => 'f.sqft',
            '7' => 'f.land_share_value',
            '8' => 'f.development_charges',
            '9' => 'f.price_value',
            '10' => 'f.shop_service_charge',
            '11' => 'f.status',
        ];

        if (array_key_exists($orderCol, $allowedCols)) {
            $colName = $allowedCols[$orderCol];
            $dir = (strtolower($orderDir) === 'desc') ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $colName $dir";
        } else {
            $sql .= " ORDER BY f.id DESC"; // Default
        }

        if ($length != -1) {
            $sql .= " LIMIT ?, ?";
            $params[] = (int)$start;
            $params[] = (int)$length;
            $types   .= "ii";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getFlatsAjaxCount($search, $project_id, $status)
    {
        $sql = "
        SELECT COUNT(*) AS total
        FROM flats AS f
        LEFT JOIN projects AS p ON f.project_id = p.id
        WHERE f.deleted_at IS NULL
        ";

        $params = [];
        $types  = "";

        if (!empty($search)) {
            $sql .= " AND (f.flat_name LIKE ? OR f.sqft LIKE ? OR p.name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types   .= "sss";
        }

        if (!empty($project_id)) {
            $sql .= " AND f.project_id = ?";
            $params[] = $project_id;
            $types   .= "i";
        }

        if (!empty($status) && in_array($status, ['available', 'booked', 'sold'])) {
            $sql .= " AND f.status = ?";
            $params[] = $status;
            $types   .= "s";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
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
        is_shop,
        unit_type,
        price_value,
        shop_service_charge,
        facilities,
        land_share_value,
        development_charges,
        created_by,
        status,
        is_booked,
        suite_id,
        per_sqft_price,
        booking_money,
        registration_money
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }
        $stmt->bind_param(
            "isdiiiiiissississsisissdd",
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
            $data['is_shop'],
            $data['unit_type'],
            $data['price_value'],
            $data['shop_service_charge'],
            $data['facilities'],
            $data['land_share_value'],
            $data['development_charges'],
            $data['created_by'],
            $data['status'],
            $data['is_booked'],
            $data['suite_id'] ?? null,
            $data['per_sqft_price'] ?? null,
            $data['booking_money'] ?? null,
            $data['registration_money'] ?? null
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
                is_shop,
                unit_type,
                price_value,
                shop_service_charge,
                facilities,
                land_share_value,
                development_charges,
                created_by,
                status,
                is_booked,
                upload_from
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        $project_id           = isset($data['project_id'])           && trim($data['project_id'])           !== '' ? (int)trim($data['project_id'])           : null;
        $flat_name            = isset($data['flat_name'])            && trim($data['flat_name'])            !== '' ? trim($data['flat_name'])            : null;

        // For integer/numeric fields: strip commas, units like "SFT", "BHK", etc.
        // preg_replace keeps only digits (and decimal point for floats)
        $extractInt = function($v) {
            $v = str_replace(',', '', trim($v ?? ''));
            return preg_replace('/[^0-9]/', '', $v); // keep digits only
        };
        $extractDecimal = function($v) {
            $v = str_replace(',', '', trim($v ?? ''));
            return preg_replace('/[^0-9.]/', '', $v); // keep digits + decimal point
        };

        $sqft                 = isset($data['sqft'])    && trim($data['sqft'])    !== '' ? $extractDecimal($data['sqft'])         : null;
        $bhk                  = isset($data['bhk'])     && trim($data['bhk'])     !== '' ? $extractInt($data['bhk'])              : null;
        $bedroom              = isset($data['bedroom']) && trim($data['bedroom']) !== '' ? $extractInt($data['bedroom'])           : null;
        $bathroom             = isset($data['bathroom'])&& trim($data['bathroom'])!== '' ? $extractInt($data['bathroom'])          : null;
        $balcony              = isset($data['balcony']) && trim($data['balcony']) !== '' ? $extractInt($data['balcony'])           : null;
        $drawing_dining       = isset($data['drawing_dining_kitchen']) ? (int)$data['drawing_dining_kitchen'] : 0;
        $common_dining        = isset($data['common_dining'])          ? (int)$data['common_dining']         : 0;
        $floor_no             = isset($data['floor_no'])    && trim($data['floor_no'])    !== '' ? trim($data['floor_no'])         : null;
        $facing_site          = isset($data['facing_site']) && trim($data['facing_site']) !== '' ? trim($data['facing_site'])      : null;
        $is_shop              = isset($data['is_shop'])     ? (int)$data['is_shop']                                                : 0;
        $unit_type            = !empty($data['unit_type'])  ? trim($data['unit_type'])                                             : 'residential';
        // Use extractDecimal → string for DECIMAL columns.
        // bind_param 'd' maps PHP null→0 in many MySQL configs; 's' keeps NULL as NULL.
        $price_value         = isset($data['price_value'])         && trim($data['price_value'])         !== '' ? $extractDecimal($data['price_value'])         : null;
        $land_share_value    = isset($data['land_share_value'])    && trim($data['land_share_value'])    !== '' ? $extractDecimal($data['land_share_value'])    : null;
        $development_charges = isset($data['development_charges']) && trim($data['development_charges']) !== '' ? $extractDecimal($data['development_charges']) : null;
        $shop_service_charge  = isset($data['shop_service_charge']) && $data['shop_service_charge'] !== '' ? (int)$extractInt($data['shop_service_charge']) : null;
        $facilities           = isset($data['facilities'])          && trim($data['facilities'])          !== '' ? trim($data['facilities'])                  : null;
        $created_by           = isset($data['created_by'])          && trim($data['created_by'])          !== '' ? (int)trim($data['created_by'])                  : null;

        // If status is booked or sold, is_booked = 1
        $status      = strtolower(trim($data['status'] ?? 'available')) ?: 'available';
        $is_booked   = in_array($status, ['booked', 'sold']) ? 1 : 0;
        $upload_from = $data['upload_from'] ?? 'From company csv';

        // 's' used for price_value/land_share_value/development_charges:
        // MySQL DECIMAL accepts numeric strings; avoids null→0 bug with 'd' type.
        // Types: i s sssssss ii ss i s s i s s s i s i s
        //        project_id, flat_name, sqft,bhk,bed,bath,balcony, draw,common, floor,facing, is_shop, unit_type, price_value, shop_sc, facilities, land_share, dev_charges, created_by, status, is_booked, upload_from
        $stmt->bind_param(
            "issssssiissississsisis",
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
            $is_shop,
            $unit_type,
            $price_value,
            $shop_service_charge,
            $facilities,
            $land_share_value,
            $development_charges,
            $created_by,
            $status,
            $is_booked,
            $upload_from
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
    /* =========================
       Create Suite From CSV
    ========================== */
    public function createFromCsvBulkSuite($data)
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
                is_shop,
                unit_type,
                price_value,
                shop_service_charge,
                facilities,
                land_share_value,
                development_charges,
                created_by,
                status,
                is_booked,
                upload_from,
                suite_id,
                per_sqft_price,
                booking_money,
                registration_money
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        $project_id           = (int)trim($data['project_id']);
        $flat_name            = trim($data['flat_name']);
        
        $sqft                 = $data['sqft'];
        $bhk                  = $data['bhk'];
        $bedroom              = $data['bedroom'];
        $bathroom             = $data['bathroom'];
        $balcony              = $data['balcony'];
        $drawing_dining       = $data['drawing_dining_kitchen'];
        $common_dining        = $data['common_dining'];
        $floor_no             = $data['floor_no'];
        $facing_site          = $data['facing_site'];
        $is_shop              = $data['is_shop'];
        $unit_type            = $data['unit_type'];
        $price_value          = $data['price_value'];
        $shop_service_charge  = $data['shop_service_charge'];
        $facilities           = $data['facilities'];
        $land_share_value     = $data['land_share_value'];
        $development_charges  = $data['development_charges'];
        $created_by           = $data['created_by'];

        $status               = $data['status'];
        $is_booked            = $data['is_booked'];
        $upload_from          = $data['upload_from'];
        
        $suite_id             = $data['suite_id'];
        $per_sqft_price       = $data['per_sqft_price'];
        $booking_money        = $data['booking_money'];
        $registration_money   = $data['registration_money'];

        $stmt->bind_param(
            "isdiiiiiissississsisissddd",
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
            $is_shop,
            $unit_type,
            $price_value,
            $shop_service_charge,
            $facilities,
            $land_share_value,
            $development_charges,
            $created_by,
            $status,
            $is_booked,
            $upload_from,
            $suite_id,
            $per_sqft_price,
            $booking_money,
            $registration_money
        );

        return $stmt->execute();
    }


    public function update($id, $data)
    {
        $sql = "UPDATE flats SET
        project_id = ?, flat_name = ?, sqft = ?, bhk = ?, bedroom = ?, bathroom = ?, balcony = ?,
        drawing_dining_kitchen = ?, common_dining = ?, floor_no = ?, facing_site = ?,
        is_shop = ?, unit_type = ?,
        price_value = ?, shop_service_charge = ?,
        facilities = ?, created_by = ?, land_share_value = ?, development_charges = ?,
        suite_id = ?, per_sqft_price = ?, booking_money = ?, registration_money = ?
        WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "isdiiiiiississisississsssi",
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
            $data['is_shop'],
            $data['unit_type'],
            $data['price_value'],
            $data['shop_service_charge'],
            $data['facilities'],
            $data['created_by'],
            $data['land_share_value'],
            $data['development_charges'],
            $data['suite_id'] ?? null,
            $data['per_sqft_price'] ?? null,
            $data['booking_money'] ?? null,
            $data['registration_money'] ?? null,
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
                (SELECT lb.booking_status FROM lead_bookings lb 
                 WHERE lb.flat_id = f.id AND lb.deleted_at IS NULL AND lb.booking_status IN ('booked', 'completed') 
                   AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                   AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                 ORDER BY lb.id DESC LIMIT 1) AS booking_status,
                (SELECT lb.created_at FROM lead_bookings lb 
                 WHERE lb.flat_id = f.id AND lb.deleted_at IS NULL AND lb.booking_status IN ('booked', 'completed') 
                   AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                   AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                 ORDER BY lb.id DESC LIMIT 1) AS booking_time,
                (SELECT l.full_name FROM lead_bookings lb 
                 JOIN leads l ON lb.lead_id = l.id
                 WHERE lb.flat_id = f.id AND lb.deleted_at IS NULL AND lb.booking_status IN ('booked', 'completed') 
                   AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                   AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                 ORDER BY lb.id DESC LIMIT 1) AS booked_client_name
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
