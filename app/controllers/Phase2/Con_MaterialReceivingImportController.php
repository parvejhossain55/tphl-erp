<?php
namespace Phase2;

use Controller;

/**
 * Con_MaterialReceivingImportController
 *
 * CSV Import: purchase_request → purchase_request_items
 *           → purchase_orders  → purchase_order_items
 *           → material_receiving → material_receiving_items
 *           → con_stocks  (UPSERT)
 *
 * CST (quotations / comparisons) বাদ।
 *
 * CSV columns (header row required):
 *   project_name, created_by_email, approved_by_email,
 *   receiving_date, warehouse_name, material_name,
 *   qty, unit_price, vendor_name
 *
 * - Multiple rows with same project_name+created_by_email = ONE PR / PO / Receiving
 * - vendor_name  → vendors.name   → vendors.id
 * - warehouse_name → con_warehouses.name → con_warehouses.id
 */
class Con_MaterialReceivingImportController extends Controller
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // IMPORT PAGE (GET)
    // =========================================================================

    public function index()
    {
        $this->view('phase2/material-receiving/import-csv', [
            'current_route' => 'con-material-receiving-import',
            'page_title'    => 'Bulk Material Receiving Import (CSV)',
        ]);
    }

    // =========================================================================
    // PROCESS IMPORT (POST)
    // =========================================================================

    public function import()
    {
        if (empty($_FILES['csv_file']['tmp_name'])) {
            setError('Please upload a CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$file) {
            setError('Cannot read the uploaded file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }

        // ── Auto-detect delimiter & strip BOM ───────────────────────────────
        $rawFirst = fgets($file);
        if (!$rawFirst) {
            setError('CSV file is empty or invalid.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }
        // Strip UTF-8 BOM (\xEF\xBB\xBF) that Excel adds
        $rawFirst = preg_replace('/^\xEF\xBB\xBF/', '', $rawFirst);

        // Auto-detect delimiter: count occurrences of , ; \t in first line
        $counts = [
            ','  => substr_count($rawFirst, ','),
            ';'  => substr_count($rawFirst, ';'),
            "\t" => substr_count($rawFirst, "\t"),
        ];
        arsort($counts);
        $delimiter = array_key_first($counts); // pick the most frequent one

        // Re-open from the cleaned first line
        rewind($file);
        // Skip BOM bytes at start of file if present
        $bom = fread($file, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($file); // no BOM, go back to start
        }

        $header = fgetcsv($file, 0, $delimiter);
        if (!$header) {
            setError('CSV file is empty or invalid.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }

        // Normalize header keys (lowercase + trim whitespace/invisible chars)
        $header = array_map(fn($h) => strtolower(preg_replace('/[^\x20-\x7E]/', '', trim($h))), $header);

        // Required columns (unit is optional — defaults to 'pcs' if missing)
        $required = ['project_name','created_by_email','approved_by_email',
                     'receiving_date','warehouse_name','material_name',
                     'qty','unit_price','vendor_name'];
        $missing = array_diff($required, $header);
        if (!empty($missing)) {
            setError('CSV missing columns: ' . implode(', ', $missing) .
                     ' | Detected delimiter: "' . ($delimiter === "\t" ? 'TAB' : $delimiter) . '"' .
                     ' | First header cell: "' . ($header[0] ?? '?') . '"');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }

        // Add 'unit' to header map if not present (backward compat)
        $hasUnit = in_array('unit', $header);

        // Read all rows
        $rows = [];
        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            if (count($row) < count($header)) continue;
            $data = array_combine($header, $row);
            $data = array_map('trim', $data);
            // Skip comment rows and blank rows
            if (empty($data['project_name']) || str_starts_with($data['project_name'], '#')) continue;
            if (empty($data['material_name'])) continue;
            // Default unit to 'pcs' if column missing
            if (!$hasUnit) { $data['unit'] = 'pcs'; }
            $rows[] = $data;
        }
        fclose($file);

        if (empty($rows)) {
            setError('No valid rows found in CSV.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }

        // ── Build lookup caches ──────────────────────────────────────────────
        $projectMap  = $this->buildMap("SELECT id, name FROM projects", 'name', 'id');
        $userMap     = $this->buildMap("SELECT id, email FROM users",    'email', 'id');
        $materialMap = $this->buildMap("SELECT id, name FROM con_materials", 'name', 'id');
        $vendorMap   = $this->buildMap("SELECT id, name FROM vendors",   'name', 'id');
        // warehouse lookup is done per-project (name + project_id) — see resolveWarehouse()

        // ── Group rows by (project_name + created_by_email) ─────────────────
        // Each group → 1 PR → 1 PO → 1 Receiving
        $groups = [];
        foreach ($rows as $r) {
            $key = $r['project_name'] . '||' . $r['created_by_email'];
            $groups[$key][] = $r;
        }

        $errors  = [];
        $summary = ['materials_saved' => 0, 'pr' => 0, 'po' => 0, 'receiving' => 0, 'stock_lines' => 0];

        $this->db->begin_transaction();
        try {
            // ── Step 0: Auto-upsert all materials before processing ───────────
            // Collect unique (material_name, unit) pairs from CSV
            $uniqueMaterials = [];
            foreach ($rows as $r) {
                $key = strtolower(trim($r['material_name']));
                if (!isset($uniqueMaterials[$key])) {
                    $uniqueMaterials[$key] = [
                        'name' => trim($r['material_name']),
                        'unit' => trim($r['unit'] ?? 'pcs'),
                    ];
                }
            }

            $matUpsertStmt = $this->db->prepare(
                "INSERT INTO con_materials (name, unit)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE unit = VALUES(unit)"
            );
            foreach ($uniqueMaterials as $key => $mat) {
                if (!isset($materialMap[$key])) {
                    // New material — insert it
                    $matUpsertStmt->bind_param('ss', $mat['name'], $mat['unit']);
                    $matUpsertStmt->execute();
                    $newId = $this->db->insert_id;
                    $materialMap[$key] = $newId;
                    $summary['materials_saved']++;
                }
                // If already in map — keep existing id (no change needed)
            }

            foreach ($groups as $groupKey => $groupRows) {
                $firstRow = $groupRows[0];

                // ── Resolve IDs ──────────────────────────────────────────
                $projectId   = $projectMap[strtolower($firstRow['project_name'])]   ?? null;
                $createdById = $userMap[strtolower($firstRow['created_by_email'])]  ?? null;
                $approvedById= $userMap[strtolower($firstRow['approved_by_email'])] ?? null;
                $receivingDate = $firstRow['receiving_date'];

                if (!$projectId)   { $errors[] = "Project not found: {$firstRow['project_name']}"; continue; }
                if (!$createdById) { $errors[] = "User not found: {$firstRow['created_by_email']}"; continue; }
                if (!$approvedById){ $errors[] = "Approver not found: {$firstRow['approved_by_email']}"; continue; }

                // Warehouse: match by name AND project_id
                $warehouseId = $this->resolveWarehouse($firstRow['warehouse_name'], $projectId);
                if (!$warehouseId) { $errors[] = "Warehouse '{$firstRow['warehouse_name']}' not found for project '{$firstRow['project_name']}'"; continue; }

                // Resolve all material IDs for this group first
                $resolvedItems = [];
                foreach ($groupRows as $idx => $r) {
                    $matId    = $materialMap[strtolower($r['material_name'])] ?? null;
                    $vendorId = $vendorMap[strtolower($r['vendor_name'])]     ?? null;
                    $qty      = (float)$r['qty'];
                    $unitPrice= (float)$r['unit_price'];

                    if (!$matId)    { $errors[] = "Material id missing for: {$r['material_name']}"; continue; }
                    if (!$vendorId) { $errors[] = "Vendor not found: {$r['vendor_name']}"; continue; }
                    if ($qty <= 0)  { $errors[] = "Invalid qty for {$r['material_name']}"; continue; }

                    $resolvedItems[] = [
                        'material_id' => $matId,
                        'vendor_id'   => $vendorId,
                        'qty'         => $qty,
                        'unit_price'  => $unitPrice,
                        'warehouse_id'=> $warehouseId,
                    ];
                }
                if (empty($resolvedItems)) continue;

                // ── 1. purchase_requests ──────────────────────────────────
                $stmt = $this->db->prepare(
                    "INSERT INTO purchase_requests
                        (project_id, created_by, status, approved_by_id, approved_at, notes)
                     VALUES (?, ?, 'PO Issued', ?, NOW(), 'CSV Import')"
                );
                $stmt->bind_param('iii', $projectId, $createdById, $approvedById);
                $stmt->execute();
                $prId = $this->db->insert_id;
                $summary['pr']++;

                // ── 2. purchase_request_items ─────────────────────────────
                $priStmt = $this->db->prepare(
                    "INSERT INTO purchase_request_items (request_id, material_id, quantity, created_by)
                     VALUES (?, ?, ?, ?)"
                );
                foreach ($resolvedItems as $item) {
                    $priStmt->bind_param('iidi',
                        $prId, $item['material_id'], $item['qty'], $createdById
                    );
                    $priStmt->execute();
                }

                // ── 3. purchase_orders ────────────────────────────────────
                $today = date('Y-m-d');
                $stmt2 = $this->db->prepare(
                    "INSERT INTO purchase_orders
                        (request_id, created_by, status, order_date,
                         approved_by_pm, pm_approved_at,
                         approved_by_se, se_approved_at)
                     VALUES (?, ?, 'Fully Received', ?, ?, NOW(), ?, NOW())"
                );
                $stmt2->bind_param('iisii', $prId, $createdById, $today, $approvedById, $approvedById);
                $stmt2->execute();
                $poId = $this->db->insert_id;
                $summary['po']++;

                // ── 4. purchase_order_items ───────────────────────────────
                // total_price is STORED GENERATED → don't insert it
                $poiStmt = $this->db->prepare(
                    "INSERT INTO purchase_order_items
                        (purchase_order_id, material_id, selected_vendor_id,
                         quantity, unit_price, created_by)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $poiIds = []; // material_id → poi_id
                foreach ($resolvedItems as $item) {
                    $poiStmt->bind_param('iiiidi',
                        $poId,
                        $item['material_id'],
                        $item['vendor_id'],
                        $item['qty'],
                        $item['unit_price'],
                        $createdById
                    );
                    $poiStmt->execute();
                    $poiIds[$item['material_id']] = $this->db->insert_id;
                }

                // ── 5. material_receiving ─────────────────────────────────
                $stmt3 = $this->db->prepare(
                    "INSERT INTO material_receiving
                        (purchase_order_id, received_by, receiving_date,
                         received_status, overall_status, created_by, warehouse_id,
                         qc_checked_by, pm_checked_by)
                     VALUES (?, ?, ?, 'Full', 'Accepted', ?, ?, ?, ?)"
                );
                $stmt3->bind_param('iisiiii',
                    $poId, $createdById, $receivingDate,
                    $createdById, $warehouseId,
                    $approvedById, $approvedById
                );
                $stmt3->execute();
                $recId = $this->db->insert_id;
                $summary['receiving']++;

                // ── 6. material_receiving_items ───────────────────────────
                $mriStmt = $this->db->prepare(
                    "INSERT INTO material_receiving_items
                        (receiving_id, purchase_order_item_id,
                         ordered_quantity, received_quantity,
                         status, site_engineer_checked, qc_checked, created_by)
                     VALUES (?, ?, ?, ?, 'Accepted', 1, 1, ?)"
                );
                foreach ($resolvedItems as $item) {
                    $poItemId = $poiIds[$item['material_id']];
                    $mriStmt->bind_param('iiddi',
                        $recId, $poItemId,
                        $item['qty'], $item['qty'],
                        $createdById
                    );
                    $mriStmt->execute();
                }

                // ── 7. con_stocks UPSERT ──────────────────────────────────
                $stockStmt = $this->db->prepare(
                    "INSERT INTO con_stocks
                        (material_id, warehouse_id, current_quantity, unit_price, total_value)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        current_quantity = current_quantity + VALUES(current_quantity),
                        unit_price       = VALUES(unit_price),
                        total_value      = (current_quantity + VALUES(current_quantity)) * VALUES(unit_price)"
                );
                // ── 8. con_stock_movements (audit IN) ─────────────────────
                $movStmt = $this->db->prepare(
                    "INSERT INTO con_stock_movements
                        (material_id, warehouse_id, type, quantity, ref_type, ref_id, moved_by)
                     VALUES (?, ?, 'IN', ?, 'material_receiving', ?, ?)"
                );
                foreach ($resolvedItems as $item) {
                    $totalVal = $item['qty'] * $item['unit_price'];
                    $stockStmt->bind_param('iiddd',
                        $item['material_id'],
                        $item['warehouse_id'],
                        $item['qty'],
                        $item['unit_price'],
                        $totalVal
                    );
                    $stockStmt->execute();

                    $movStmt->bind_param('iidii',
                        $item['material_id'],
                        $item['warehouse_id'],
                        $item['qty'],
                        $recId,
                        $createdById
                    );
                    $movStmt->execute();
                    $summary['stock_lines']++;
                }
            } // end foreach groups

            if (!empty($errors)) {
                // Partial errors — still commit what succeeded
                $this->db->commit();
                setError('Import completed with warnings: ' . implode(' | ', array_slice($errors, 0, 5)));
            } else {
                $this->db->commit();
                setSuccess(
                    "Import successful! " .
                    "Materials saved: {$summary['materials_saved']}, " .
                    "PR: {$summary['pr']}, PO: {$summary['po']}, " .
                    "Receivings: {$summary['receiving']}, Stock lines updated: {$summary['stock_lines']}"
                );
            }

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('CSV Import Error: ' . $e->getMessage());
            setError('Import failed: ' . $e->getMessage());
        }

        header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
        exit;
    }

    // =========================================================================
    // HELPER: Resolve warehouse by name + project_id
    // =========================================================================

    private function resolveWarehouse(string $name, int $projectId): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM con_warehouses
             WHERE LOWER(name) = LOWER(?) AND project_id = ?
             LIMIT 1"
        );
        $stmt->bind_param('si', $name, $projectId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int)$row['id'] : null;
    }

    // =========================================================================
    // HELPER: Build name→id lookup map (case-insensitive)
    // =========================================================================

    private function buildMap(string $sql, string $keyCol, string $valCol): array
    {
        $result = $this->db->query($sql);
        $map = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $map[strtolower($row[$keyCol])] = $row[$valCol];
            }
        }
        return $map;
    }

    // =========================================================================
    // DOWNLOAD TEMPLATE CSV (with real DB sample data)
    // =========================================================================

    public function downloadTemplate()
    {
        // ── Pull real data from DB for dummy rows ────────────────────────────
        $project = $this->db->query(
            "SELECT p.name AS project_name, w.name AS warehouse_name
             FROM projects p
             LEFT JOIN con_warehouses w ON w.project_id = p.id
             LIMIT 1"
        )->fetch_assoc();

        $creator = $this->db->query(
            "SELECT email FROM users WHERE role_id NOT IN (1,3) AND email IS NOT NULL LIMIT 1"
        )->fetch_assoc();

        $approver = $this->db->query(
            "SELECT email FROM users WHERE role_id IN (1,3,15,22) AND email IS NOT NULL LIMIT 1"
        )->fetch_assoc();

        $materials = $this->db->query(
            "SELECT name FROM con_materials LIMIT 3"
        )->fetch_all(MYSQLI_ASSOC);

        $vendor = $this->db->query(
            "SELECT name FROM vendors WHERE status = 'active' LIMIT 1"
        )->fetch_assoc();

        // ── Fallback values if DB empty ──────────────────────────────────────
        $projName   = $project['project_name']  ?? 'Mirpur Housing Project';
        $warehouse  = $project['warehouse_name'] ?? 'Main Warehouse';
        $creatorEmail  = $creator['email']  ?? 'site.engineer@tphl.com';
        $approverEmail = $approver['email'] ?? 'pm@tphl.com';
        $vendorName = $vendor['name'] ?? 'ABC Suppliers Ltd';
        $today      = date('Y-m-d');

        $matNames = [];
        foreach ($materials as $m) { $matNames[] = $m['name']; }
        if (empty($matNames)) {
            $matNames = ['Rod 10mm', 'Cement (50kg)', 'Brick (1000pcs)'];
        }

        // ── Build rows ───────────────────────────────────────────────────────
        $sampleQtys   = ['500', '200', '1000'];
        $samplePrices = ['85.50', '420.00', '12.00'];
        $sampleUnits  = ['kg', 'bag', 'pcs'];

        // Try to get unit from DB
        $matWithUnit = $this->db->query("SELECT name, unit FROM con_materials LIMIT 3")->fetch_all(MYSQLI_ASSOC);

        $rows = [];
        foreach ($matNames as $i => $mat) {
            $unit = $matWithUnit[$i]['unit'] ?? $sampleUnits[$i] ?? 'pcs';
            $rows[] = [
                $projName,
                $creatorEmail,
                $approverEmail,
                $today,
                $warehouse,
                $mat,
                $unit,
                $sampleQtys[$i]   ?? '100',
                $samplePrices[$i] ?? '50.00',
                $vendorName,
            ];
        }

        // ── Output CSV ───────────────────────────────────────────────────────
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="material_receiving_sample_' . date('Ymd') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');

        // Header row
        fputcsv($out, [
            'project_name', 'created_by_email', 'approved_by_email',
            'receiving_date', 'warehouse_name', 'material_name',
            'unit', 'qty', 'unit_price', 'vendor_name'
        ]);

        // Instruction comment row (skipped during import)
        fputcsv($out, [
            '# Replace values below with real data. Do not change column headers.',
            '', '', '', '', '', '', '', '', ''
        ]);

        // Data rows
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }
}
