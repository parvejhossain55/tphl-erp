<?php

class Con_Wastage extends Model
{
    protected $table = 'con_wastage';
    protected $itemTable = 'con_wastage_items';

    // =========================================================================
    // READ
    // =========================================================================

    public function getAll()
    {
        $sql = "SELECT w.*, p.name AS project_name, wh.name AS warehouse_name, u.full_name AS recorded_by_name
                FROM {$this->table} w
                LEFT JOIN projects p ON p.id = w.project_id
                LEFT JOIN con_warehouses wh ON wh.id = w.warehouse_id
                LEFT JOIN users u ON u.id = w.recorded_by
                ORDER BY w.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT w.*, p.name AS project_name, wh.name AS warehouse_name, 
                    u.full_name AS recorded_by_name,
                    qc_u.full_name AS qc_approver_name,
                    pur_u.full_name AS purchase_approver_name,
                    acc_u.full_name AS accounts_receiver_name
             FROM {$this->table} w
             LEFT JOIN projects p ON p.id = w.project_id
             LEFT JOIN con_warehouses wh ON wh.id = w.warehouse_id
             LEFT JOIN users u ON u.id = w.recorded_by
             LEFT JOIN users qc_u ON qc_u.id = w.qc_approved_by
             LEFT JOIN users pur_u ON pur_u.id = w.purchase_approved_by
             LEFT JOIN users acc_u ON acc_u.id = w.payment_received_by
             WHERE w.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($wastageId)
    {
        $stmt = $this->db->prepare(
            "SELECT wi.*, m.name AS material_name, m.unit, COALESCE(cat.name, 'Uncategorized') AS category_name
             FROM {$this->itemTable} wi
             JOIN con_materials m ON m.id = wi.material_id
             LEFT JOIN con_material_categories cat ON cat.id = m.category_id
             WHERE wi.wastage_id = ?
             ORDER BY wi.id ASC"
        );
        $stmt->bind_param('i', $wastageId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    public function create($data, $items)
    {
        $this->db->begin_transaction();
        try {
            // 1. Create wastage record
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (project_id, warehouse_id, recorded_by, total_selling_amount, status)
                 VALUES (?, ?, ?, ?, 'Pending QC')"
            );
            $stmt->bind_param('iiid', 
                $data['project_id'], 
                $data['warehouse_id'], 
                $data['recorded_by'], 
                $data['total_selling_amount']
            );
            $stmt->execute();
            $wastageId = $this->db->insert_id;

            // 2. Create items
            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable} (wastage_id, material_id, quantity, condition_note, document, selling_amount)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($items as $item) {
                $itemStmt->bind_param('iidssd',
                    $wastageId,
                    $item['material_id'],
                    $item['quantity'],
                    $item['condition_note'],
                    $item['document'],
                    $item['selling_amount']
                );
                $itemStmt->execute();
            }

            $this->db->commit();
            return ['success' => true, 'id' => $wastageId];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function approveQC($id, $userId, $note, $docPath)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} 
             SET status = 'Pending Purchase', qc_approved_by = ?, qc_approved_at = NOW(), qc_note = ?, qc_doc = ? 
             WHERE id = ?"
        );
        $stmt->bind_param('issi', $userId, $note, $docPath, $id);
        return $stmt->execute();
    }

    public function approvePurchase($id, $userId, $note, $docPath)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} 
             SET status = 'Pending Payment', purchase_approved_by = ?, purchase_approved_at = NOW(), purchase_note = ?, purchase_doc = ? 
             WHERE id = ?"
        );
        $stmt->bind_param('issi', $userId, $note, $docPath, $id);
        return $stmt->execute();
    }

    public function receivePayment($id, $userId, $paymentMethod, $docPath)
    {
        $this->db->begin_transaction();
        try {
            $wastage = $this->find($id);
            if (!$wastage || $wastage['status'] !== 'Pending Payment') {
                throw new \Exception("Invalid wastage record or status.");
            }

            // 1. Update Wastage Record
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} 
                 SET status = 'Completed', payment_received_by = ?, payment_received_at = NOW(), payment_method = ?, payment_doc = ? 
                 WHERE id = ?"
            );
            $stmt->bind_param('issi', $userId, $paymentMethod, $docPath, $id);
            $stmt->execute();

            // 2. Deduct from Stock (now that payment is received as per user request)
            $items = $this->getItems($id);
            require_once __DIR__ . '/Con_Stock.php';
            $stockModel = new \Con_Stock($this->db);

            foreach ($items as $item) {
                $stockModel->logMovement([
                    'material_id'  => $item['material_id'],
                    'warehouse_id' => $wastage['warehouse_id'],
                    'type'         => 'OUT',
                    'quantity'     => $item['quantity'],
                    'ref_type'     => 'wastage_sale',
                    'ref_id'       => $wastage['id'],
                    'moved_by'     => $userId
                ]);
            }

            // 3. Add to management_cashin
            $roleName = $_SESSION['user']['role_name'] ?? 'Accounts';
            $personName = $_SESSION['user']['full_name'] ?? 'Unknown';
            $amount = $wastage['total_selling_amount'];
            $desc = "Payment received from Wastage Sale #" . $wastage['id'];

            $cashinStmt = $this->db->prepare(
                "INSERT INTO management_cashin (role_name, person_name, transaction_date, source, description, amount, payment_method, payment_status, created_by)
                 VALUES (?, ?, CURDATE(), 'Wastage Sale', ?, ?, ?, 'paid', ?)"
            );
            $cashinStmt->bind_param('sssdsi', $roleName, $personName, $desc, $amount, $paymentMethod, $userId);
            $cashinStmt->execute();

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}
