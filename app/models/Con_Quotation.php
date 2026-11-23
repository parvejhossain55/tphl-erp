<?php
/**
 * Con_Quotation Model
 */
require_once __DIR__ . '/Con_PurchaseRequest.php';

class Con_Quotation extends Model
{
    protected $table     = 'quotations';
    protected $itemTable = 'quotation_items';
    public    $lastError = null;  // stores the last error message for caller

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        try {
            $colRes = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'attachment'");
            if ($colRes && $col = $colRes->fetch_assoc()) {
                if (stripos($col['Type'], 'int') !== false) {
                    $this->db->query("ALTER TABLE {$this->table} MODIFY COLUMN attachment VARCHAR(255) NULL DEFAULT NULL");
                }
            }
            $linkRes = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'drive_link'");
            if ($linkRes && $linkRes->num_rows === 0) {
                $this->db->query("ALTER TABLE {$this->table} ADD COLUMN drive_link TEXT NULL AFTER attachment");
            }
        } catch (\Throwable $e) {
            // Silently ignore if already modified or DDL restricted
        }
    }

    // =========================================================================
    // QUOTATION — READ
    // =========================================================================

    /** All quotations for a specific Purchase Request */
    public function getByRequest($requestId)
    {
        $stmt = $this->db->prepare(
            "SELECT q.*, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, v.company_name AS vendor_company_name, v.phone AS vendor_phone,
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
        $quotations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($quotations as &$q) {
            $q['items'] = $this->getItems($q['id']);
        }
        unset($q);

        return $quotations;
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT q.*, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, v.company_name AS vendor_company_name
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
            "SELECT qi.*, m.name AS material_name, m.unit, COALESCE(cat.name, 'Uncategorized') AS category_name
             FROM {$this->itemTable} qi
             JOIN con_materials m ON m.id = qi.material_id
             LEFT JOIN con_material_categories cat ON cat.id = m.category_id
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
                "INSERT INTO {$this->table} (request_id, vendor_id, validity_date, attachment, drive_link, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt) throw new \RuntimeException('Quotation header prepare failed: ' . $this->db->error);

            $driveLinkVal = !empty($data['drive_link']) ? $data['drive_link'] : null;
            $stmt->bind_param('iisssi',
                $data['request_id'],
                $data['vendor_id'],
                $data['validity_date'],
                $data['attachment'],
                $driveLinkVal,
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
        $prItemsRaw = $prModel->getItems($requestId);
        $prItems = [];
        $prQtyMap = [];
        
        // Aggregate PR items by material_id to prevent duplicates in CST matrix
        foreach ($prItemsRaw as $pri) {
            $mid = $pri['material_id'];
            if (!isset($prQtyMap[$mid])) {
                $prItems[$mid] = $pri;
                $prQtyMap[$mid] = (float)$pri['quantity'];
            } else {
                $prItems[$mid]['quantity'] += (float)$pri['quantity'];
                $prQtyMap[$mid] += (float)$pri['quantity'];
            }
        }
        $prItems = array_values($prItems); // Re-index for the view

        $vendors = [];
        $matrix  = [];
        $totals  = [];

        foreach ($quotations as $q) {
            $comp = trim($q['vendor_company_name'] ?? '');
            $pers = trim($q['vendor_name'] ?? '');
            if (!empty($comp) && !empty($pers) && strcasecmp($comp, $pers) !== 0) {
                $displayVName = $comp . ' (' . $pers . ')';
            } else {
                $displayVName = !empty($comp) ? $comp : $pers;
            }
            $vendors[$q['vendor_id']] = $displayVName;
            $totals[$q['vendor_id']] = 0;
            $qItems = $this->getItems($q['id']);
            foreach ($qItems as $qi) {
                // If material is still part of the PR, calculate using current PR quantity
                if (isset($prQtyMap[$qi['material_id']])) {
                    $effQty = $prQtyMap[$qi['material_id']];
                    $matrix[$qi['material_id']][$q['vendor_id']] = [
                        'unit_price'  => $qi['unit_price'],
                        'quantity'    => $effQty,
                        'total_price' => $effQty * $qi['unit_price'],
                        'quotation_id' => $q['id'],
                    ];
                    $totals[$q['vendor_id']] += ($effQty * $qi['unit_price']);
                }
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

    public function getWinningSelections($requestId)
    {
        $sql = "SELECT ci.material_id, ci.vendor_id, MAX(ci.unit_price) AS unit_price, 
                       m.name AS material_name, m.unit, 
                       COALESCE(cat.name, 'Uncategorized') AS category_name, 
                       COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, 
                       (SELECT SUM(quantity) FROM purchase_request_items WHERE request_id = qc.request_id AND material_id = ci.material_id) AS pr_quantity
                FROM comparison_items ci
                JOIN quotation_comparisons qc ON qc.id = ci.comparison_id
                JOIN con_materials m ON m.id = ci.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                JOIN vendors v ON v.id = ci.vendor_id
                WHERE qc.request_id = ? AND ci.is_selected = 1
                GROUP BY ci.material_id, ci.vendor_id, m.name, m.unit, category_name, v.company_name, v.name, qc.request_id
                ORDER BY MIN(ci.id) ASC";
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

    public function update($quotationId, $data, $items)
    {
        $this->lastError = null;
        $this->db->begin_transaction();
        try {
            // Update quotation header
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET validity_date = ?, attachment = ?, drive_link = ?, created_by = ?
                 WHERE id = ?"
            );
            if (!$stmt) throw new \RuntimeException('Quotation header update prepare failed: ' . $this->db->error);

            $driveLinkVal = !empty($data['drive_link']) ? $data['drive_link'] : null;
            $stmt->bind_param('sssii',
                $data['validity_date'],
                $data['attachment'],
                $driveLinkVal,
                $data['created_by'],
                $quotationId
            );
            if (!$stmt->execute()) throw new \RuntimeException('Quotation header update execute failed: ' . $stmt->error);

            // Delete existing quotation items
            $delStmt = $this->db->prepare("DELETE FROM {$this->itemTable} WHERE quotation_id = ?");
            if (!$delStmt) throw new \RuntimeException('Quotation items delete prepare failed: ' . $this->db->error);
            $delStmt->bind_param('i', $quotationId);
            if (!$delStmt->execute()) throw new \RuntimeException('Quotation items delete execute failed: ' . $delStmt->error);

            // Insert new quotation items
            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable}
                     (quotation_id, material_id, quantity, unit_price, created_by)
                 VALUES (?, ?, ?, ?, ?)"
            );
            if (!$itemStmt) throw new \RuntimeException('Quotation items insert prepare failed: ' . $this->db->error);

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
            return true;

        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            error_log('[Con_Quotation::update] ' . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        $this->lastError = null;
        $this->db->begin_transaction();
        try {
            // First fetch the quotation to find the request_id
            $stmt = $this->db->prepare("SELECT request_id FROM {$this->table} WHERE id = ?");
            if (!$stmt) throw new \RuntimeException('Fetch quotation prepare failed: ' . $this->db->error);
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) throw new \RuntimeException('Fetch quotation execute failed: ' . $stmt->error);
            $quotation = $stmt->get_result()->fetch_assoc();
            if (!$quotation) {
                throw new \RuntimeException('Quotation not found.');
            }
            $requestId = $quotation['request_id'];

            // Delete quotation items
            $itemStmt = $this->db->prepare("DELETE FROM {$this->itemTable} WHERE quotation_id = ?");
            if (!$itemStmt) throw new \RuntimeException('Quotation items delete prepare failed: ' . $this->db->error);
            $itemStmt->bind_param('i', $id);
            if (!$itemStmt->execute()) throw new \RuntimeException('Quotation items delete execute failed: ' . $itemStmt->error);

            // Delete quotation comparison items and comparisons for this request
            $compItemsStmt = $this->db->prepare(
                "DELETE ci FROM comparison_items ci
                 JOIN quotation_comparisons qc ON qc.id = ci.comparison_id
                 WHERE qc.request_id = ?"
            );
            if (!$compItemsStmt) throw new \RuntimeException('Comparison items delete prepare failed: ' . $this->db->error);
            $compItemsStmt->bind_param('i', $requestId);
            if (!$compItemsStmt->execute()) throw new \RuntimeException('Comparison items delete execute failed: ' . $compItemsStmt->error);

            $compStmt = $this->db->prepare("DELETE FROM quotation_comparisons WHERE request_id = ?");
            if (!$compStmt) throw new \RuntimeException('Quotation comparisons delete prepare failed: ' . $this->db->error);
            $compStmt->bind_param('i', $requestId);
            if (!$compStmt->execute()) throw new \RuntimeException('Quotation comparisons delete execute failed: ' . $compStmt->error);

            // Delete quotation itself
            $quotStmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            if (!$quotStmt) throw new \RuntimeException('Quotation delete prepare failed: ' . $this->db->error);
            $quotStmt->bind_param('i', $id);
            if (!$quotStmt->execute()) throw new \RuntimeException('Quotation delete execute failed: ' . $quotStmt->error);

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            error_log('[Con_Quotation::delete] ' . $e->getMessage());
            return false;
        }
    }
}
