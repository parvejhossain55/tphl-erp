<?php
/**
 * Con_Quotation Model
 * Tables: quotations, quotation_items, quotation_comparisons, comparison_items
 */
class Con_Quotation extends Model
{
    protected $table     = 'quotations';
    protected $itemTable = 'quotation_items';
    public    $lastError = null;  // stores the last error message for caller

    // =========================================================================
    // QUOTATION — READ
    // =========================================================================

    /** All quotations for a specific Purchase Request */
    public function getByRequest($requestId)
    {
        $stmt = $this->db->prepare(
            "SELECT q.*, v.name AS vendor_name, v.phone AS vendor_phone,
                    (SELECT SUM(qi.quantity * qi.unit_price) FROM {$this->itemTable} qi WHERE qi.quotation_id = q.id) AS total_amount
             FROM {$this->table} q
             JOIN vendors v ON v.id = q.vendor_id
             WHERE q.request_id = ?
             ORDER BY q.id ASC"
        );
        if (!$stmt) {
            error_log('[Con_Quotation::getByRequest] prepare failed: ' . $this->db->error);
            return [];
        }
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT q.*, v.name AS vendor_name
             FROM {$this->table} q
             JOIN vendors v ON v.id = q.vendor_id
             WHERE q.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($quotationId)
    {
        $stmt = $this->db->prepare(
            "SELECT qi.*, m.name AS material_name, m.unit
             FROM {$this->itemTable} qi
             JOIN con_materials m ON m.id = qi.material_id
             WHERE qi.quotation_id = ?
             ORDER BY qi.id ASC"
        );
        $stmt->bind_param('i', $quotationId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // QUOTATION — WRITE
    // =========================================================================

    public function create($data, $items)
    {
        $this->lastError = null;
        $this->db->begin_transaction();
        try {
            // ── Insert quotation header ──────────────────────────────────────
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (request_id, vendor_id, validity_date, attachment, created_by)
                 VALUES (?, ?, ?, ?, ?)"
            );
            if (!$stmt) throw new \RuntimeException('Quotation header prepare failed: ' . $this->db->error);

            $stmt->bind_param('iissi',
                $data['request_id'],
                $data['vendor_id'],
                $data['validity_date'],
                $data['attachment'],
                $data['created_by']
            );
            if (!$stmt->execute()) throw new \RuntimeException('Quotation header execute failed: ' . $stmt->error);
            $quotationId = $this->db->insert_id;

            // ── Insert quotation items ───────────────────────────────────────
            // We do NOT insert total_price explicitly
            // (handles both GENERATED columns and regular nullable columns)
            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable}
                     (quotation_id, material_id, quantity, unit_price, created_by)
                 VALUES (?, ?, ?, ?, ?)"
            );
            if (!$itemStmt) throw new \RuntimeException('Quotation items prepare failed: ' . $this->db->error);

            foreach ($items as $item) {
                $itemStmt->bind_param('iiddi',
                    $quotationId,
                    $item['material_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $data['created_by']
                );
                if (!$itemStmt->execute()) {
                    throw new \RuntimeException('Item execute failed: ' . $itemStmt->error);
                }
            }

            $this->db->commit();
            return $quotationId;

        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            error_log('[Con_Quotation::create] ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // COMPARATIVE STATEMENT (CST) — Build comparison data
    // =========================================================================

    /**
     * Build a comparison matrix for a Purchase Request.
     * Returns: ['materials' => [...], 'vendors' => [...], 'matrix' => [material_id => [vendor_id => price]]]
     */
    public function buildComparison($requestId)
    {
        // Get all quotations for this request
        $quotations = $this->getByRequest($requestId);
        if (empty($quotations)) return ['materials' => [], 'vendors' => [], 'matrix' => [], 'totals' => []];

        // Get PR items (materials list)
        $prModel = new \Con_PurchaseRequest($this->db);
        $prItems = $prModel->getItems($requestId);

        $vendors = [];
        $matrix  = [];
        $totals  = [];

        foreach ($quotations as $q) {
            $vendors[$q['vendor_id']] = $q['vendor_name'];
            $totals[$q['vendor_id']] = 0;
            $qItems = $this->getItems($q['id']);
            foreach ($qItems as $qi) {
                $matrix[$qi['material_id']][$q['vendor_id']] = [
                    'unit_price'  => $qi['unit_price'],
                    'quantity'    => $qi['quantity'],
                    'total_price' => $qi['quantity'] * $qi['unit_price'],
                    'quotation_id' => $q['id'],
                ];
                $totals[$q['vendor_id']] += ($qi['quantity'] * $qi['unit_price']);
            }
        }

        return [
            'materials' => $prItems,
            'vendors'   => $vendors,
            'matrix'    => $matrix,
            'totals'    => $totals,
        ];
    }

    /**
     * Save the winning vendor selections from CST
     * Creates records in quotation_comparisons + comparison_items
     */
    public function saveComparison($requestId, $selections, $userId)
    {
        $this->db->begin_transaction();
        try {
            // Delete existing comparison for this request (re-select)
            $this->db->query("DELETE FROM comparison_items WHERE comparison_id IN
                (SELECT id FROM quotation_comparisons WHERE request_id = {$requestId})");
            $this->db->query("DELETE FROM quotation_comparisons WHERE request_id = {$requestId}");

            // Create comparison header
            $stmt = $this->db->prepare(
                "INSERT INTO quotation_comparisons (request_id, prepared_by) VALUES (?, ?)"
            );
            $stmt->bind_param('ii', $requestId, $userId);
            $stmt->execute();
            $compId = $this->db->insert_id;

            // Insert comparison items with is_selected flag
            $itemStmt = $this->db->prepare(
                "INSERT INTO comparison_items (comparison_id, material_id, vendor_id, unit_price, is_selected, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($selections as $sel) {
                $itemStmt->bind_param('iiidii',
                    $compId,
                    $sel['material_id'],
                    $sel['vendor_id'],
                    $sel['unit_price'],
                    $sel['is_selected'],
                    $userId
                );
                $itemStmt->execute();
            }

            $this->db->commit();
            return $compId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    /** Get the winning selections for a request (used to generate PO) */
    public function getWinningSelections($requestId)
    {
        $sql = "SELECT ci.*, m.name AS material_name, m.unit, v.name AS vendor_name
                FROM comparison_items ci
                JOIN quotation_comparisons qc ON qc.id = ci.comparison_id
                JOIN con_materials m ON m.id = ci.material_id
                JOIN vendors v ON v.id = ci.vendor_id
                WHERE qc.request_id = ? AND ci.is_selected = 1
                ORDER BY ci.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Check if CST has been completed for a request */
    public function hasComparison($requestId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS cnt FROM quotation_comparisons WHERE request_id = ?"
        );
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ($row['cnt'] > 0);
    }
}
