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

    public function getAll($createdBy = null, $executiveId = null, $pmId = null)
    {
        $sql = "SELECT pr.*, p.name AS project_name, u.full_name AS created_by_name,
                       exec.full_name AS assigned_executive_name,
                       COALESCE(pr.pr_type, 'boq_linked') AS pr_type,
                       (SELECT COUNT(*) FROM {$this->itemTable} WHERE request_id = pr.id) AS item_count,
                       (SELECT SUM(pri.quantity * COALESCE(bi.unit_cost, 0))
                        FROM {$this->itemTable} pri
                        LEFT JOIN boq_items bi ON bi.material_id = pri.material_id
                            AND bi.boq_id = (SELECT MIN(bm.id) FROM boq_master bm WHERE bm.project_id = pr.project_id AND bm.status IN ('Approved','Submitted'))
                        WHERE pri.request_id = pr.id) AS estimated_total,
                       (SELECT u2.full_name FROM po_negotiation_notes n JOIN users u2 ON u2.id = n.user_id WHERE n.request_id = pr.id ORDER BY n.created_at DESC LIMIT 1) AS latest_note_author,
                       (SELECT n2.note_text FROM po_negotiation_notes n2 WHERE n2.request_id = pr.id ORDER BY n2.created_at DESC LIMIT 1) AS latest_note_text
                FROM {$this->table} pr
                LEFT JOIN projects p ON p.id = pr.project_id
                LEFT JOIN users u ON u.id = pr.created_by
                LEFT JOIN users exec ON exec.id = pr.assigned_executive_id ";
        
        $where = [];
        if ($createdBy) {
            $where[] = "pr.created_by = " . (int)$createdBy;
        }
        if ($executiveId) {
            $where[] = "pr.assigned_executive_id = " . (int)$executiveId;
        }
        if ($pmId) {
            $where[] = "(p.pm_id = " . (int)$pmId . " OR p.pm_id IS NULL)";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
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
                    qc.full_name  AS qc_approved_by_name,
                    mgmt.full_name AS mgmt_approved_by_name,
                    exec.full_name AS assigned_executive_name,
                    COALESCE(pr.pr_type, 'boq_linked') AS pr_type
             FROM {$this->table} pr
             LEFT JOIN projects p   ON p.id    = pr.project_id
             LEFT JOIN users u      ON u.id    = pr.created_by
             LEFT JOIN users apv    ON apv.id  = pr.approved_by_id
             LEFT JOIN roles apv_r  ON apv_r.id = apv.role_id
             LEFT JOIN users qc     ON qc.id   = pr.qc_approved_by_id
             LEFT JOIN users mgmt   ON mgmt.id = pr.mgmt_approved_by_id
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
            "SELECT pri.*, m.name AS material_name, m.unit,
                    COALESCE(cat.name, 'Uncategorized') AS category_name,
                    bi.required_date AS boq_required_date,
                    pri.required_date AS item_required_date
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
        $sql = "SELECT pr.*, p.name AS project_name,
                       COALESCE(pr.pr_type, 'boq_linked') AS pr_type
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
     * For boq_linked: validates each item against BOQ remaining balance.
     * For independent: skips BOQ validation entirely.
     * Returns ['success' => bool, 'id' => int, 'errors' => []]
     */
    public function create($data, $items)
    {
        $errors = [];

        $prType = $data['pr_type'] ?? 'boq_linked';

        // BOQ validation — only for boq_linked PRs
        if ($prType === 'boq_linked') {
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
        }
        // For 'independent' PRs: no BOQ validation at all

        if (!empty($errors)) {
            return ['success' => false, 'id' => 0, 'errors' => $errors];
        }

        $this->db->begin_transaction();
        try {
            $bomId        = ($prType === 'boq_linked' && !empty($data['bom_id'])) ? (int)$data['bom_id'] : null;
            $requiredDate = !empty($data['required_date']) ? $data['required_date'] : null;
            $purpose      = !empty($data['purpose'])       ? trim($data['purpose'])  : null;
            $attachment   = !empty($data['attachment'])    ? trim($data['attachment']) : null;

            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table}
                    (project_id, bom_id, pr_type, required_date, purpose, created_by, status, notes, attachment)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iisssssss',
                $data['project_id'],
                $bomId,
                $prType,
                $requiredDate,
                $purpose,
                $data['created_by'],
                $data['status'],
                $data['notes'],
                $attachment
            );
            $stmt->execute();
            $prId = $this->db->insert_id;

            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable}
                    (request_id, material_id, quantity, required_date, created_by)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $itemReqDate = !empty($item['required_date']) ? $item['required_date'] : null;
                $itemStmt->bind_param('iidsi',
                    $prId,
                    $item['material_id'],
                    $item['quantity'],
                    $itemReqDate,
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

    public function update($id, $data, $items)
    {
        $id = (int)$id;
        $prType = $data['pr_type'] ?? 'boq_linked';
        $bomId  = ($prType === 'boq_linked' && !empty($data['bom_id'])) ? (int)$data['bom_id'] : null;
        $requiredDate = !empty($data['required_date']) ? $data['required_date'] : null;
        $purpose      = !empty($data['purpose'])       ? trim($data['purpose'])  : null;
        $attachment   = !empty($data['attachment'])    ? trim($data['attachment']) : null;
        $status       = !empty($data['status'])        ? $data['status']         : 'Draft';

        $this->db->begin_transaction();
        try {
            if ($attachment) {
                $stmt = $this->db->prepare(
                    "UPDATE {$this->table}
                     SET project_id = ?, bom_id = ?, pr_type = ?, required_date = ?, purpose = ?, status = ?, notes = ?, attachment = ?
                     WHERE id = ?"
                );
                $stmt->bind_param('iissssssi',
                    $data['project_id'], $bomId, $prType, $requiredDate, $purpose, $status, $data['notes'], $attachment, $id
                );
            } else {
                $stmt = $this->db->prepare(
                    "UPDATE {$this->table}
                     SET project_id = ?, bom_id = ?, pr_type = ?, required_date = ?, purpose = ?, status = ?, notes = ?
                     WHERE id = ?"
                );
                $stmt->bind_param('iisssssi',
                    $data['project_id'], $bomId, $prType, $requiredDate, $purpose, $status, $data['notes'], $id
                );
            }
            $stmt->execute();

            $delItems = $this->db->prepare("DELETE FROM {$this->itemTable} WHERE request_id = ?");
            $delItems->bind_param('i', $id);
            $delItems->execute();

            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable}
                    (request_id, material_id, quantity, required_date, created_by)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($items as $item) {
                $itemReqDate = !empty($item['required_date']) ? $item['required_date'] : null;
                $itemStmt->bind_param('iidsi',
                    $id,
                    $item['material_id'],
                    $item['quantity'],
                    $itemReqDate,
                    $data['created_by']
                );
                $itemStmt->execute();
            }

            $this->db->commit();
            return ['success' => true, 'id' => $id, 'errors' => []];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'id' => 0, 'errors' => [$e->getMessage()]];
        }
    }

    public function delete($id)
    {
        $id = (int)$id;
        $this->db->begin_transaction();
        try {
            $stmtItems = $this->db->prepare("DELETE FROM {$this->itemTable} WHERE request_id = ?");
            $stmtItems->bind_param('i', $id);
            $stmtItems->execute();

            $stmtReq = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmtReq->bind_param('i', $id);
            $stmtReq->execute();

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Con_PurchaseRequest delete exception: " . $e->getMessage());
            return false;
        }
    }
}
