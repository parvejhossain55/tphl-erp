<?php
/**
 * Con_BOQ Model
 * Tables: boq_master, boq_items
 *
 * Schema:
 * boq_master: id, project_id, target_id (→ phase_monthly_targets.id), design_type_id,
 *             version, status(Draft/Submitted/Approved/Rejected),
 *             approved_by, approved_at, created_by, created_at, updated_at
 * boq_items:  id, boq_id, material_id, quantity, unit_cost, total_cost(GENERATED)
 */
class Con_BOQ extends Model
{
    protected $db;
    protected $masterTable = 'boq_master';
    protected $itemTable   = 'boq_items';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // BOQ MASTER — READ
    // =========================================================================

    /** All BOQ masters with project + target + user info */
    public function getAll()
    {
        $sql = "SELECT b.*, p.name AS project_name, u.full_name AS created_by_name,
                       a.full_name AS approved_by_name,
                       a_r.name    AS approved_by_role,
                       CONCAT(pp.phase_name, ' - ', DATE_FORMAT(pmt.target_month, '%M %Y')) AS target_title,
                       dt.name AS design_type_name
                FROM {$this->masterTable} b
                LEFT JOIN projects             p   ON p.id   = b.project_id
                LEFT JOIN users               u   ON u.id   = b.created_by
                LEFT JOIN users               a   ON a.id   = b.approved_by
                LEFT JOIN roles               a_r ON a_r.id = a.role_id
                LEFT JOIN phase_monthly_targets pmt ON pmt.id  = b.target_id
                LEFT JOIN project_phases       pp  ON pp.id   = pmt.phase_id
                LEFT JOIN design_types         dt  ON dt.id   = b.design_type_id
                ORDER BY b.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Single BOQ master with project name + target + design type */
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, p.name AS project_name, u.full_name AS created_by_name,
                    a.full_name AS approved_by_name,
                    a_r.name    AS approved_by_role,
                    CONCAT(pp.phase_name, ' - ', DATE_FORMAT(pmt.target_month, '%M %Y')) AS target_title,
                    pmt.total_target_weight_pct AS target_percentage,
                    pmt.target_year       AS target_year,
                    pmt.target_month_number AS target_month,
                    NULL                  AS target_week,
                    dt.name AS design_type_name
             FROM {$this->masterTable} b
             LEFT JOIN projects             p   ON p.id   = b.project_id
             LEFT JOIN users               u   ON u.id   = b.created_by
             LEFT JOIN users               a   ON a.id   = b.approved_by
             LEFT JOIN roles               a_r ON a_r.id = a.role_id
             LEFT JOIN phase_monthly_targets pmt ON pmt.id  = b.target_id
             LEFT JOIN project_phases        pp  ON pp.id   = pmt.phase_id
             LEFT JOIN design_types          dt  ON dt.id   = b.design_type_id
             WHERE b.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** All BOQs for a specific project */
    public function getByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, p.name AS project_name
             FROM {$this->masterTable} b
             LEFT JOIN projects p ON p.id = b.project_id
             WHERE b.project_id = ?
             ORDER BY b.id DESC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // BOQ ITEMS — READ
    // =========================================================================

    /** All items for a BOQ with material details */
    public function getItems($boqId)
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, m.name AS material_name, m.unit, COALESCE(cat.name, 'Uncategorized') AS category_name
             FROM {$this->itemTable} i
             JOIN con_materials m ON m.id = i.material_id
             LEFT JOIN con_material_categories cat ON cat.id = m.category_id
             WHERE i.boq_id = ?
             ORDER BY i.id ASC"
        );
        $stmt->bind_param('i', $boqId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Remaining BOQ balance per material for a project.
     * Returns: material_id, material_name, unit, boq_qty, ordered_qty, remaining_qty
     *
     * Used by Purchase Requests to validate against BOQ limits.
     */
public function getRemainingBOQ($projectId, $boqId = null) {
    if ($boqId) {
        $sql = "SELECT 
                    bi.material_id, 
                    m.name as material_name,
                    m.unit,
                    COALESCE(cat.name, 'Uncategorized') AS category_name,
                    bi.quantity as total_boq_qty,
                    COALESCE((
                        SELECT SUM(pri.quantity) 
                        FROM purchase_request_items pri
                        JOIN purchase_requests pr ON pr.id = pri.request_id
                        WHERE pr.bom_id = ? AND pri.material_id = bi.material_id
                        AND pr.status != 'Rejected'
                    ), 0) as used_qty,
                    (bi.quantity - COALESCE((
                        SELECT SUM(pri.quantity) 
                        FROM purchase_request_items pri
                        JOIN purchase_requests pr ON pr.id = pri.request_id
                        WHERE pr.bom_id = ? AND pri.material_id = bi.material_id
                        AND pr.status != 'Rejected'
                    ), 0)) as remaining_qty
                FROM boq_items bi
                JOIN boq_master bm ON bm.id = bi.boq_id
                JOIN con_materials m ON m.id = bi.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                WHERE bm.id = ? AND bm.status = 'Approved'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $boqId, $boqId, $boqId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $sql = "SELECT 
                    bi.material_id, 
                    m.name as material_name,
                    m.unit,
                    COALESCE(cat.name, 'Uncategorized') AS category_name,
                    SUM(bi.quantity) as total_boq_qty,
                    COALESCE((
                        SELECT SUM(pri.quantity) 
                        FROM purchase_request_items pri
                        JOIN purchase_requests pr ON pr.id = pri.request_id
                        WHERE pr.project_id = ? AND pri.material_id = bi.material_id
                        AND pr.status != 'Rejected'
                    ), 0) as used_qty,
                    (SUM(bi.quantity) - COALESCE((
                        SELECT SUM(pri.quantity) 
                        FROM purchase_request_items pri
                        JOIN purchase_requests pr ON pr.id = pri.request_id
                        WHERE pr.project_id = ? AND pri.material_id = bi.material_id
                        AND pr.status != 'Rejected'
                    ), 0)) as remaining_qty
                FROM boq_items bi
                JOIN boq_master bm ON bm.id = bi.boq_id
                JOIN con_materials m ON m.id = bi.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                WHERE bm.project_id = ? AND bm.status = 'Approved'
                GROUP BY bi.material_id, m.name, m.unit, cat.name";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $projectId, $projectId, $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

    /**
     * Per-material consumption for a specific BOQ:
     * boq_qty, ordered_qty (from POs), received_qty (from material_receiving Approved/Accepted),
     * remaining_qty = boq_qty - ordered_qty
     * Returns array keyed by material_id for fast lookup.
     */
    public function getBoqConsumption($boqId, $projectId)
    {
        $sql = "
            SELECT
                bi.material_id,
                m.name      AS material_name,
                m.unit,
                COALESCE(cat.name, 'Uncategorized') AS category_name,
                bi.quantity AS boq_qty,
                COALESCE((
                    SELECT SUM(poi.quantity)
                    FROM purchase_order_items poi
                    JOIN purchase_orders po ON po.id = poi.purchase_order_id
                    JOIN purchase_requests pr ON pr.id = po.request_id
                    WHERE pr.project_id = ?
                      AND poi.material_id = bi.material_id
                      AND po.status NOT IN ('Cancelled')
                ), 0) AS ordered_qty,
                COALESCE((
                    SELECT SUM(poi2.quantity)
                    FROM purchase_order_items poi2
                    JOIN purchase_orders po2 ON po2.id = poi2.purchase_order_id
                    JOIN purchase_requests pr2 ON pr2.id = po2.request_id
                    JOIN material_receiving mr ON mr.purchase_order_id = po2.id
                    WHERE pr2.project_id = ?
                      AND poi2.material_id = bi.material_id
                      AND mr.overall_status IN ('QC Approved','Accepted','Fully Received','Partially Received')
                ), 0) AS received_qty
            FROM boq_items bi
            JOIN con_materials m ON m.id = bi.material_id
            LEFT JOIN con_material_categories cat ON cat.id = m.category_id
            WHERE bi.boq_id = ?
            ORDER BY m.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $projectId, $projectId, $boqId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $row['remaining_qty']  = max(0, (float)$row['boq_qty'] - (float)$row['ordered_qty']);
            $row['consumed_pct']   = $row['boq_qty'] > 0
                ? min(100, round((float)$row['received_qty'] / (float)$row['boq_qty'] * 100, 1))
                : 0;
            $result[$row['material_id']] = $row;
        }
        return $result;
    }

    /**
     * All BOQs with target info + consumption summary stats for list view
     * Returns total_boq_value, total_ordered_qty, total_received_qty per BOQ
     */
    public function getAllWithConsumption()
    {
        $sql = "
            SELECT b.*, p.name AS project_name, u.full_name AS created_by_name,
                   a.full_name AS approved_by_name,
                   a_r.name    AS approved_by_role,
                   CONCAT(pp.phase_name, ' - ', DATE_FORMAT(pmt.target_month, '%M %Y')) AS target_title,
                   dt.name AS design_type_name,
                   COUNT(bi.id)          AS item_count,
                   COALESCE(SUM(bi.quantity * bi.unit_cost), 0) AS total_boq_value,
                   -- Total ordered from POs (not cancelled)
                   COALESCE((
                       SELECT SUM(poi.quantity)
                       FROM boq_items bi2
                       JOIN purchase_order_items poi ON poi.material_id = bi2.material_id
                       JOIN purchase_orders po ON po.id = poi.purchase_order_id
                       JOIN purchase_requests pr ON pr.id = po.request_id
                       WHERE bi2.boq_id = b.id
                         AND pr.project_id = b.project_id
                         AND po.status NOT IN ('Cancelled')
                   ), 0) AS total_ordered,
                   -- Total actually received via material_receiving
                   COALESCE((
                       SELECT SUM(poi2.quantity)
                       FROM boq_items bi3
                       JOIN purchase_order_items poi2 ON poi2.material_id = bi3.material_id
                       JOIN purchase_orders po2 ON po2.id = poi2.purchase_order_id
                       JOIN purchase_requests pr2 ON pr2.id = po2.request_id
                       JOIN material_receiving mr ON mr.purchase_order_id = po2.id
                       WHERE bi3.boq_id = b.id
                         AND pr2.project_id = b.project_id
                         AND mr.overall_status IN ('QC Approved','Accepted','Fully Received','Partially Received')
                   ), 0) AS total_received
            FROM {$this->masterTable} b
            LEFT JOIN projects             p   ON p.id   = b.project_id
            LEFT JOIN users               u   ON u.id   = b.created_by
            LEFT JOIN users               a   ON a.id   = b.approved_by
            LEFT JOIN roles               a_r ON a_r.id = a.role_id
            LEFT JOIN phase_monthly_targets pmt ON pmt.id  = b.target_id
            LEFT JOIN project_phases        pp  ON pp.id   = pmt.phase_id
            LEFT JOIN design_types          dt  ON dt.id   = b.design_type_id
            LEFT JOIN boq_items     bi ON bi.boq_id = b.id
            GROUP BY b.id
            ORDER BY b.id DESC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // BOQ MASTER — WRITE
    // =========================================================================

    /** Create a new BOQ master, returns insert_id or false */
    public function createMaster($data)
    {
        $targetId      = !empty($data['target_id'])      ? (int)$data['target_id']      : null;
        $designTypeId  = !empty($data['design_type_id']) ? (int)$data['design_type_id'] : null;

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->masterTable}
             (project_id, target_id, design_type_id, version, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'iiissi',
            $data['project_id'],
            $targetId,
            $designTypeId,
            $data['version'],
            $data['status'],
            $data['created_by']
        );
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    /** Update BOQ master header (project, version) */
    public function updateMaster($id, $data)
    {
        $status = $data['status'] ?? 'Draft';
        $projectId = (int)$data['project_id'];
        $version = $data['version'] ?? '1.0';

        if ($status === 'Approved') {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $approvedBy = (int)($_SESSION['user']['id'] ?? 0);
            $stmt = $this->db->prepare(
                "UPDATE {$this->masterTable}
                 SET project_id = ?, version = ?, status = ?, approved_by = ?, approved_at = NOW()
                 WHERE id = ?"
            );
            $stmt->bind_param(
                'issii',
                $projectId,
                $version,
                $status,
                $approvedBy,
                $id
            );
        } else {
            $stmt = $this->db->prepare(
                "UPDATE {$this->masterTable}
                 SET project_id = ?, version = ?, status = ?, approved_by = NULL, approved_at = NULL
                 WHERE id = ?"
            );
            $stmt->bind_param(
                'issi',
                $projectId,
                $version,
                $status,
                $id
            );
        }
        return $stmt->execute();
    }

    /** Update BOQ status (Submit / Approve / Reject) */
    public function updateStatus($id, $status, $userId = null)
    {
        if ($status === 'Approved') {
            $stmt = $this->db->prepare(
                "UPDATE {$this->masterTable}
                 SET status = ?, approved_by = ?, approved_at = NOW()
                 WHERE id = ?"
            );
            $stmt->bind_param('sii', $status, $userId, $id);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE {$this->masterTable} SET status = ? WHERE id = ?"
            );
            $stmt->bind_param('si', $status, $id);
        }
        return $stmt->execute();
    }

    // =========================================================================
    // BOQ ITEMS — WRITE
    // =========================================================================

    /** Replace all items for a BOQ (delete + insert) */
    public function addItems($boqId, $items)
    {
        // Clear existing
        $del = $this->db->prepare("DELETE FROM {$this->itemTable} WHERE boq_id = ?");
        $del->bind_param('i', $boqId);
        $del->execute();

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->itemTable} (boq_id, material_id, floor_type, floor_no, quantity, unit_cost, required_date)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($items as $item) {
            $reqDate = !empty($item['required_date']) ? $item['required_date'] : null;
            $floorType = $item['floor_type'] ?? 'Typical_Floor';
            $floorNo = isset($item['floor_no']) && $item['floor_no'] !== '' ? (int)$item['floor_no'] : null;
            $stmt->bind_param(
                'iisidds',
                $boqId,
                $item['material_id'],
                $floorType,
                $floorNo,
                $item['quantity'],
                $item['unit_cost'],
                $reqDate
            );
            $stmt->execute();
        }
        return true;
    }

    // =========================================================================
    // BOQ — DELETE
    // =========================================================================

    /** Delete a BOQ and its items (only Draft/Submitted) */
    public function delete($id)
    {
        $boq = $this->find($id);
        if (!$boq || in_array($boq['status'], ['Approved'])) {
            return false;
        }
        $this->db->begin_transaction();
        try {
            $del1 = $this->db->prepare("DELETE FROM {$this->itemTable} WHERE boq_id = ?");
            $del1->bind_param('i', $id);
            $del1->execute();

            $del2 = $this->db->prepare("DELETE FROM {$this->masterTable} WHERE id = ?");
            $del2->bind_param('i', $id);
            $del2->execute();

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }
}
