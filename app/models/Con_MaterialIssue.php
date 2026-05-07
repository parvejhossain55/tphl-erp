<?php
/**
 * Con_MaterialIssue Model
 * Table: con_material_issues (FLAT — each row = one material issue)
 *
 * ACTUAL DB SCHEMA:
 * id, project_id, warehouse_id, material_id, quantity, issued_to, purpose,
 * issued_by, issue_date, created_at
 *
 * Stock OUT: deducts from con_stocks via Con_Stock::logMovement(type=OUT)
 */
class Con_MaterialIssue extends Model
{
    protected $table = 'con_material_issues';

    // =========================================================================
    // READ
    // =========================================================================

    public function getAll()
    {
        $sql = "SELECT mi.*, p.name AS project_name, w.name AS warehouse_name,
                       m.name AS material_name, m.unit,
                       u.full_name AS issued_by_name,
                       wp.work_description AS work_plan_name,
                       tf.target_title AS target_name
                FROM {$this->table} mi
                LEFT JOIN projects p ON p.id = mi.project_id
                LEFT JOIN con_warehouses w ON w.id = mi.warehouse_id
                LEFT JOIN con_materials m ON m.id = mi.material_id
                LEFT JOIN users u ON u.id = mi.issued_by
                LEFT JOIN project_working_plans wp ON wp.id = mi.work_plan_id
                LEFT JOIN target_fixed tf ON tf.id = mi.target_id
                ORDER BY mi.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT mi.*, p.name AS project_name, w.name AS warehouse_name,
                    m.name AS material_name, m.unit,
                    u.full_name AS issued_by_name,
                    wp.work_description AS work_plan_name,
                    tf.target_title AS target_name
             FROM {$this->table} mi
             LEFT JOIN projects p ON p.id = mi.project_id
             LEFT JOIN con_warehouses w ON w.id = mi.warehouse_id
             LEFT JOIN con_materials m ON m.id = mi.material_id
             LEFT JOIN users u ON u.id = mi.issued_by
             LEFT JOIN project_working_plans wp ON wp.id = mi.work_plan_id
             LEFT JOIN target_fixed tf ON tf.id = mi.target_id
             WHERE mi.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // CREATE — Insert issue + deduct stock
    // =========================================================================

    public function create($data)
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table}
                    (project_id, warehouse_id, material_id, quantity, issued_to, purpose, issued_by, issue_date, target_id, work_plan_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $issuedTo = (int)$data['issued_to'];
            $stmt->bind_param('iiidisisii',
                $data['project_id'],   // i
                $data['warehouse_id'], // i
                $data['material_id'],  // i
                $data['quantity'],     // d
                $issuedTo,             // i  contractor_id
                $data['purpose'],      // s
                $data['issued_by'],    // i
                $data['issue_date'],   // s
                $data['target_id'],    // i
                $data['work_plan_id']  // i
            );
            $stmt->execute();
            $issueId = $this->db->insert_id;


            // Deduct stock
            $stockModel = new \Con_Stock($this->db);

            // Validate available stock
            $stocks = $stockModel->getCurrentStock($data['material_id'], $data['warehouse_id']);
            $available = 0;
            foreach ($stocks as $s) {
                $available += (float)$s['current_quantity'];
            }

            if ($available < $data['quantity']) {
                throw new \Exception(
                    "Insufficient stock: available {$available}, requested {$data['quantity']}"
                );
            }

            // Log OUT movement
            $stockModel->logMovement([
                'material_id'  => $data['material_id'],
                'warehouse_id' => $data['warehouse_id'],
                'type'         => 'OUT',
                'quantity'     => $data['quantity'],
                'ref_type'     => 'material_issue',
                'ref_id'       => $issueId,
                'moved_by'     => $data['issued_by'],
            ]);

            $this->db->commit();
            return $issueId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Material Issue create failed: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
