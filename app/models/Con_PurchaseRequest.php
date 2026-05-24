<?php
/**
 * Con_PurchaseRequest Model
 * Tables: purchase_requests, purchase_request_items
 */
class Con_PurchaseRequest extends Model
{
    protected $table     = 'purchase_requests';
    protected $itemTable = 'purchase_request_items';

    // =========================================================================
    // READ
    // =========================================================================

    public function getAll($executiveId = null)
    {
        $sql = "SELECT pr.*, p.name AS project_name, u.full_name AS created_by_name,
                       exec.full_name AS assigned_executive_name,
                       (SELECT COUNT(*) FROM {$this->itemTable} WHERE request_id = pr.id) AS item_count,
                       (SELECT SUM(pri.quantity * COALESCE(bi.unit_cost, 0))
                        FROM {$this->itemTable} pri
                        LEFT JOIN boq_items bi ON bi.material_id = pri.material_id
                            AND bi.boq_id = (SELECT MIN(bm.id) FROM boq_master bm WHERE bm.project_id = pr.project_id AND bm.status IN ('Approved','Submitted'))
                        WHERE pri.request_id = pr.id) AS estimated_total
                FROM {$this->table} pr
                LEFT JOIN projects p ON p.id = pr.project_id
                LEFT JOIN users u ON u.id = pr.created_by
                LEFT JOIN users exec ON exec.id = pr.assigned_executive_id ";
        
        if ($executiveId) {
            $sql .= " WHERE pr.assigned_executive_id = " . (int)$executiveId . " ";
        }
                
        $sql .= " ORDER BY pr.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*, p.name AS project_name, u.full_name AS created_by_name,
                    apv.full_name AS approved_by_name,
                    apv_r.name    AS approved_by_role,
                    exec.full_name AS assigned_executive_name
             FROM {$this->table} pr
             LEFT JOIN projects p   ON p.id    = pr.project_id
             LEFT JOIN users u      ON u.id    = pr.created_by
             LEFT JOIN users apv    ON apv.id  = pr.approved_by_id
             LEFT JOIN roles apv_r  ON apv_r.id = apv.role_id
             LEFT JOIN users exec   ON exec.id = pr.assigned_executive_id
             WHERE pr.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($requestId)
    {
        $stmt = $this->db->prepare(
            "SELECT pri.*, m.name AS material_name, m.unit, COALESCE(cat.name, 'Uncategorized') AS category_name,
                    bi.required_date AS boq_required_date
             FROM {$this->itemTable} pri
             JOIN con_materials m ON m.id = pri.material_id
             LEFT JOIN con_material_categories cat ON cat.id = m.category_id
             JOIN purchase_requests pr ON pr.id = pri.request_id
             LEFT JOIN boq_items bi ON bi.boq_id = pr.bom_id AND bi.material_id = pri.material_id
             WHERE pri.request_id = ?
             ORDER BY pri.id ASC"
        );
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Get approved PRs without PO (for PO generation dropdown) */
    public function getApprovedWithoutPO()
    {
        $sql = "SELECT pr.*, p.name AS project_name
                FROM {$this->table} pr
                LEFT JOIN projects p ON p.id = pr.project_id
                WHERE pr.status = 'Approved'
                  AND NOT EXISTS (SELECT 1 FROM purchase_orders po WHERE po.request_id = pr.id)
                ORDER BY pr.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /**
     * Create PR with items.
     * Validates each item against BOQ remaining balance.
     * Returns ['success' => bool, 'id' => int, 'errors' => []]
     */
    public function create($data, $items)
    {
        $errors = [];

        // BOQ validation — get remaining for this project / specific BOQ
        $bomId = !empty($data['bom_id']) ? (int)$data['bom_id'] : null;
        $boqModel = new \Con_BOQ($this->db);
        $remaining = $boqModel->getRemainingBOQ($data['project_id'], $bomId);
        $remainMap = [];
        foreach ($remaining as $r) {
            $remainMap[$r['material_id']] = $r;
        }

        foreach ($items as $idx => $item) {
            $mid = (int)$item['material_id'];
            $qty = (float)$item['quantity'];
            if (isset($remainMap[$mid])) {
                $avail = (float)$remainMap[$mid]['remaining_qty'];
                if ($qty > $avail) {
                    $name = $remainMap[$mid]['material_name'];
                    $errors[] = "{$name}: Requested {$qty} but only {$avail} remaining in BOQ.";
                }
            }
            // If material not in BOQ, allow (some PRs may be for non-BOQ items)
        }

        if (!empty($errors)) {
            return ['success' => false, 'id' => 0, 'errors' => $errors];
        }

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (project_id, bom_id, created_by, status, notes)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $bomId = $data['bom_id'] ?? null;
            $stmt->bind_param('iiiss',
                $data['project_id'],
                $bomId,
                $data['created_by'],
                $data['status'],
                $data['notes']
            );
            $stmt->execute();
            $prId = $this->db->insert_id;

            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable} (request_id, material_id, quantity, created_by)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $itemStmt->bind_param('iidi',
                    $prId,
                    $item['material_id'],
                    $item['quantity'],
                    $data['created_by']
                );
                $itemStmt->execute();
            }

            $this->db->commit();
            return ['success' => true, 'id' => $prId, 'errors' => []];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'id' => 0, 'errors' => [$e->getMessage()]];
        }
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }
}
