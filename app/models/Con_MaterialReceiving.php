<?php
/**
 * Con_MaterialReceiving Model
 * Tables: material_receiving, material_receiving_items
 *
 * ACTUAL DB SCHEMA:
 * material_receiving: id, purchase_order_id, received_by, receiving_date, received_status,
 *   site_eng_note, site_eng_photo, qc_checked_by, qc_note, qc_photo, overall_status,
 *   created_by, warehouse_id, notes, created_at, updated_at
 *
 * material_receiving_items: id, receiving_id, purchase_order_item_id, ordered_quantity,
 *   received_quantity, status(Accepted|Partially Accepted|Rejected|Pending QC),
 *   return_quantity, penalty_applied, site_engineer_checked, qc_checked,
 *   issue_reason, attachment, created_by
 */
class Con_MaterialReceiving extends Model
{
    protected $table     = 'material_receiving';
    protected $itemTable = 'material_receiving_items';

    // =========================================================================
    // READ
    // =========================================================================

    public function getAll()
    {
        $sql = "SELECT mr.*, po.request_id, po.status AS po_status,
                       p.name AS project_name, w.name AS warehouse_name,
                       u.full_name AS received_by_name,
                       (SELECT COUNT(*) FROM {$this->itemTable} WHERE receiving_id = mr.id) AS item_count,
                       (SELECT COUNT(*) FROM {$this->itemTable} WHERE receiving_id = mr.id AND status = 'Accepted') AS accepted_count,
                       (SELECT COUNT(*) FROM {$this->itemTable} WHERE receiving_id = mr.id AND status = 'Rejected') AS rejected_count
                FROM {$this->table} mr
                JOIN purchase_orders po ON po.id = mr.purchase_order_id
                JOIN purchase_requests pr ON pr.id = po.request_id
                LEFT JOIN projects p ON p.id = pr.project_id
                LEFT JOIN con_warehouses w ON w.id = mr.warehouse_id
                LEFT JOIN users u ON u.id = mr.received_by
                ORDER BY mr.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT mr.*, po.request_id, po.status AS po_status,
                    p.name AS project_name, w.name AS warehouse_name,
                    u.full_name AS received_by_name
             FROM {$this->table} mr
             JOIN purchase_orders po ON po.id = mr.purchase_order_id
             JOIN purchase_requests pr ON pr.id = po.request_id
             LEFT JOIN projects p ON p.id = pr.project_id
             LEFT JOIN con_warehouses w ON w.id = mr.warehouse_id
             LEFT JOIN users u ON u.id = mr.received_by
             WHERE mr.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($receivingId)
    {
        $stmt = $this->db->prepare(
            "SELECT mri.*,
                    poi.material_id, poi.quantity AS po_quantity, poi.unit_price,
                    poi.selected_vendor_id,
                    m.name AS material_name, m.unit,
                    v.name AS vendor_name
             FROM {$this->itemTable} mri
             JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
             JOIN con_materials m ON m.id = poi.material_id
             LEFT JOIN vendors v ON v.id = poi.selected_vendor_id
             WHERE mri.receiving_id = ?
             ORDER BY mri.id ASC"
        );
        $stmt->bind_param('i', $receivingId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // CREATE — Log Material Receiving (GRN)
    // =========================================================================

    public function create($data, $items)
    {
        $this->db->begin_transaction();
        try {
            // Create receiving header
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table}
                    (purchase_order_id, warehouse_id, received_by, receiving_date, received_status, overall_status, notes, site_eng_note, site_eng_photo, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt) {
                throw new \Exception("Header prepare failed: " . $this->db->error);
            }
            $overallStatus  = 'Pending QC';
            $receivedStatus = $data['received_status'] ?? 'Full';
            $notes = $data['notes'] ?? '';
            $siteEngNote  = $data['site_eng_note'] ?? null;
            $siteEngPhoto = $data['site_eng_photo'] ?? null;

            $stmt->bind_param('iiissssssi',
                $data['purchase_order_id'],
                $data['warehouse_id'],
                $data['received_by'],
                $data['receiving_date'],
                $receivedStatus,
                $overallStatus,
                $notes,
                $siteEngNote,
                $siteEngPhoto,
                $data['created_by']
            );
            if (!$stmt->execute()) {
                throw new \Exception("Header insert failed: " . $stmt->error);
            }
            $receivingId = $this->db->insert_id;

            // Create receiving items
            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable}
                    (receiving_id, purchase_order_item_id, ordered_quantity, received_quantity, status, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if (!$itemStmt) {
                throw new \Exception("Item prepare failed: " . $this->db->error);
            }
            $pendingStatus = 'Pending QC';
            foreach ($items as $item) {
                $itemStmt->bind_param('iiddsi',
                    $receivingId,
                    $item['purchase_order_item_id'],
                    $item['ordered_quantity'],
                    $item['received_quantity'],
                    $pendingStatus,
                    $data['created_by']
                );
                if (!$itemStmt->execute()) {
                    throw new \Exception("Item insert failed: " . $itemStmt->error);
                }
            }

            $this->db->commit();
            return $receivingId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Material Receiving create failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // QC REVIEW — THE CORE AUTOMATED LOGIC
    // =========================================================================

    public function qcReview($receivingId, $itemStatuses, $userId)
    {
        $receiving = $this->find($receivingId);
        if (!$receiving) return false;

        $stockModel = new \Con_Stock($this->db);

        $this->db->begin_transaction();
        try {
            $acceptedCount = 0;
            $rejectedCount = 0;
            $totalCount    = 0;
            $returnItemsToCreate = [];

            $qcNote = $itemStatuses['qc_note'] ?? '';
            $qcPhoto = $itemStatuses['qc_photo'] ?? null;

            foreach ($itemStatuses as $itemId => $review) {
                if ($itemId === 'qc_note' || $itemId === 'qc_photo') continue;

                $totalCount++;
                $status      = $review['status'];
                $issueReason = $review['issue_reason'] ?? null;

                // 1. Update item status + qc fields
                $accQty = 0;
                $rejQty = 0;

                // Get item details via PO item join to know received qty
                $itemStmt = $this->db->prepare(
                    "SELECT mri.*, poi.material_id, poi.selected_vendor_id
                     FROM {$this->itemTable} mri
                     JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                     WHERE mri.id = ?"
                );
                $itemStmt->bind_param('i', $itemId);
                $itemStmt->execute();
                $item = $itemStmt->get_result()->fetch_assoc();

                if ($status === 'Accepted') {
                    $accQty = $item['received_quantity'];
                    $rejQty = 0;
                } elseif ($status === 'Rejected') {
                    $accQty = 0;
                    $rejQty = $item['received_quantity'];
                } elseif ($status === 'Partially Accepted') {
                    $accQty = $review['accepted_qty'] ?? 0;
                    $rejQty = $review['rejected_qty'] ?? 0;
                }

                $stmt = $this->db->prepare(
                    "UPDATE {$this->itemTable}
                     SET status = ?, issue_reason = ?, qc_checked = 1, accepted_qty = ?, rejected_qty = ?
                     WHERE id = ? AND receiving_id = ?"
                );
                $stmt->bind_param('ssddii', $status, $issueReason, $accQty, $rejQty, $itemId, $receivingId);
                $stmt->execute();

                if ($status === 'Accepted') {
                    $acceptedCount++;
                    // Stock In moved to PM Approval
                } elseif ($status === 'Rejected' || $status === 'Partially Accepted') {
                    if ($status === 'Rejected') {
                        $rejectedCount++;
                    } else {
                        $acceptedCount++; // counts as partially accepted
                    }

                    if ($rejQty > 0) {
                        // Mark penalty applied
                        $penStmt = $this->db->prepare(
                            "UPDATE {$this->itemTable} SET penalty_applied = 1 WHERE id = ?"
                        );
                        $penStmt->bind_param('i', $itemId);
                        $penStmt->execute();

                        // Create vendor penalty
                        if ($item['selected_vendor_id']) {
                            $penaltyStmt = $this->db->prepare(
                                "INSERT INTO vendor_penalties
                                    (vendor_id, purchase_order_id, receiving_id, penalty_category, penalty_amount, reason, penalty_status, created_by)
                                 VALUES (?, ?, ?, 'Quality Issue', 0, ?, 'Pending', ?)"
                            );
                            $reason = "Material rejected during QC. GRN #{$receivingId}. Rejected Qty: {$rejQty}. Reason: " . ($issueReason ?: 'Not specified');
                            $penaltyStmt->bind_param('iiisi',
                                $item['selected_vendor_id'],
                                $receiving['purchase_order_id'],
                                $receivingId,
                                $reason,
                                $userId
                            );
                            $penaltyStmt->execute();
                        }

                        // Collect for Return Request
                        $returnItemsToCreate[] = [
                            'material_item_id' => $item['material_id'],
                            'return_qty' => $rejQty,
                            'unit_price' => $item['unit_price'] ?? 0,
                            'vendor_id' => $item['selected_vendor_id']
                        ];
                    }
                }
            }

            // Create Return Request if any rejected qty
            if (!empty($returnItemsToCreate)) {
                $poQuery = $this->db->query("SELECT pr.project_id FROM purchase_orders po JOIN purchase_requests pr ON pr.id = po.request_id WHERE po.id = " . (int)$receiving['purchase_order_id']);
                $poData = $poQuery->fetch_assoc();
                $projectId = $poData['project_id'] ?? 0;
                $vendorId = $returnItemsToCreate[0]['vendor_id'] ?? 0;

                require_once __DIR__ . '/Con_MaterialReturn.php';
                $returnModel = new \Con_MaterialReturn($this->db);
                $returnData = [
                    'receiving_id' => $receivingId,
                    'vendor_id' => $vendorId,
                    'project_id' => $projectId,
                    'return_date' => date('Y-m-d'),
                    'return_reason' => "QC Review Rejections. See QC Notes.",
                    'attachment' => null,
                    'status' => 'pending',
                    'created_by' => $userId
                ];
                
                $returnModel->create($returnData, $returnItemsToCreate);
                
                require_once __DIR__ . '/HeadNotification.php';
                $notifModel = new \HeadNotification($this->db);
                $notifModel->create([
                    'user_id' => $receiving['received_by'],
                    'project_id' => $projectId,
                    'type' => 'Material Return',
                    'title' => 'QC Review Rejected Items',
                    'message' => "QC Review completed for GRN-{$receivingId} with rejected items. Please process return.",
                    'link' => '/con-material-returns'
                ]);
            }

            // 3. Update QC fields on header
            $overallStatus = 'Pending PM';
            $qcNote  = $itemStatuses['qc_note'] ?? null;
            $qcPhoto = $itemStatuses['qc_photo'] ?? null;

            $oStmt = $this->db->prepare(
                "UPDATE {$this->table} SET overall_status = ?, qc_checked_by = ?, qc_note = COALESCE(?, qc_note), qc_photo = COALESCE(?, qc_photo) WHERE id = ?"
            );
            $oStmt->bind_param('sissi', $overallStatus, $userId, $qcNote, $qcPhoto, $receivingId);
            $oStmt->execute();

            // Notify PMs
            require_once __DIR__ . '/HeadNotification.php';
            $notifModel = new \HeadNotification($this->db);
            $pmQuery = $this->db->query("SELECT id FROM users WHERE role_id IN (1, 3, 15, 20, 22, 23)");
            while ($pm = $pmQuery->fetch_assoc()) {
                $notifModel->create([
                    'user_id' => $pm['id'],
                    'project_id' => 0,
                    'type' => 'GRN PM Approval',
                    'title' => 'Pending PM Approval',
                    'message' => "QC Review completed for GRN-{$receivingId}. Waiting for PM acknowledgement.",
                    'url' => "/con-material-receiving-qc/{$receivingId}"
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("QC Review failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // PM APPROVAL (Stock In)
    // =========================================================================

    public function pmReview($receivingId, $userId, $pmNote)
    {
        $receiving = $this->find($receivingId);
        if (!$receiving || $receiving['overall_status'] !== 'Pending PM') return false;

        $stockModel = new \Con_Stock($this->db);
        $this->db->begin_transaction();
        try {
            $items = $this->getItems($receivingId);
            $acceptedCount = 0;
            $rejectedCount = 0;
            $totalCount    = count($items);

            foreach ($items as $item) {
                if ($item['status'] === 'Accepted') {
                    $acceptedCount++;
                    // AUTO STOCK IN
                    $stockModel->logMovement([
                        'material_id'  => $item['material_id'],
                        'warehouse_id' => $receiving['warehouse_id'],
                        'type'         => 'IN',
                        'quantity'     => $item['received_quantity'],
                        'ref_type'     => 'material_receiving',
                        'ref_id'       => $receivingId,
                        'moved_by'     => $userId,
                    ]);
                } elseif ($item['status'] === 'Rejected') {
                    $rejectedCount++;
                }
            }

            // Update overall status
            $overallStatus = 'Accepted';
            if ($acceptedCount === $totalCount && $totalCount > 0) {
                $overallStatus = 'Accepted';
            } elseif ($rejectedCount === $totalCount && $totalCount > 0) {
                $overallStatus = 'Rejected';
            } else {
                $overallStatus = 'Partially Accepted';
            }

            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET overall_status = ?, pm_checked_by = ?, pm_note = ?, pm_approved_at = NOW() WHERE id = ?"
            );
            $stmt->bind_param('sisi', $overallStatus, $userId, $pmNote, $receivingId);
            $stmt->execute();

            // Check if PO is fully received
            $this->checkAndUpdatePOStatus($receiving['purchase_order_id']);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("PM Review failed: " . $e->getMessage());
            return false;
        }
    }

    private function checkAndUpdatePOStatus($poId)
    {
        $sql = "SELECT poi.id, poi.quantity AS ordered,
                       COALESCE(SUM(CASE WHEN mri.status = 'Accepted' THEN mri.received_quantity ELSE 0 END), 0) AS received
                FROM purchase_order_items poi
                LEFT JOIN {$this->itemTable} mri ON mri.purchase_order_item_id = poi.id
                WHERE poi.purchase_order_id = ?
                GROUP BY poi.id, poi.quantity";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $allComplete = true;
        $anyReceived = false;
        foreach ($rows as $row) {
            if ($row['received'] >= $row['ordered']) {
                $anyReceived = true;
            } else {
                $allComplete = false;
                if ($row['received'] > 0) $anyReceived = true;
            }
        }

        if ($allComplete) {
            $newStatus = 'Fully Received';
        } elseif ($anyReceived) {
            $newStatus = 'Partially Received';
        } else {
            return;
        }

        $upd = $this->db->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
        $upd->bind_param('si', $newStatus, $poId);
        $upd->execute();
    }
}
