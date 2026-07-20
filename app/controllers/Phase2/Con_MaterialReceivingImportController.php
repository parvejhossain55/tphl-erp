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
 *           → vendor_payment_requests (NEW)
 *           → con_financial_transactions (NEW)
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
            'page_title'    => 'Bulk Material Receiving & Payment Import (CSV)',
        ]);
    }

    // =========================================================================
    // VALIDATE CSV (POST)
    // =========================================================================

    public function validateCsv()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
            echo json_encode(['success' => false, 'message' => 'No CSV file uploaded']);
            exit;
        }

        $file = $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
            exit;
        }

        $handle = fopen($file['tmp_name'], "r");
        if ($handle === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to read CSV file']);
            exit;
        }

        // Auto-detect delimiter
        $rawFirst = fgets($handle);
        if (!$rawFirst) {
            echo json_encode(['success' => false, 'message' => 'CSV file is empty']);
            fclose($handle);
            exit;
        }
        $rawFirst = preg_replace('/^\xEF\xBB\xBF/', '', $rawFirst); // Strip BOM
        $counts = [
            ','  => substr_count($rawFirst, ','),
            ';'  => substr_count($rawFirst, ';'),
            "\t" => substr_count($rawFirst, "\t"),
        ];
        arsort($counts);
        $delimiter = array_key_first($counts);

        rewind($handle);
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            echo json_encode(['success' => false, 'message' => 'Failed to parse CSV header']);
            fclose($handle);
            exit;
        }
        $header = array_map(fn($h) => strtolower(preg_replace('/[^\x20-\x7E]/', '', trim($h))), $header);

        $reqHeaders = ['project_name', 'receiving_date', 'warehouse_name', 'material_name', 'qty', 'unit_price', 'vendor_name'];
        $missing = array_diff($reqHeaders, $header);
        if (!empty($missing)) {
            echo json_encode(['success' => false, 'message' => 'Missing required columns: ' . implode(', ', $missing)]);
            fclose($handle);
            exit;
        }

        $projectMap = $this->buildMap("SELECT id, name FROM projects", 'name', 'id');
        $userMap    = $this->buildMap("SELECT id, email FROM users", 'email', 'id');
        
        $results = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (count(array_filter($row)) === 0) continue;
            if (count($header) !== count($row)) {
                $results[] = [
                    'row' => $rowNum,
                    'valid' => false,
                    'error' => 'Column count mismatch'
                ];
                continue;
            }

            $data = array_combine($header, $row);
            $data = array_map('trim', $data);
            $rowIssues = [];

            // 1. Resolve project
            $projectName = $data['project_name'] ?? '';
            $projectId = $projectMap[strtolower($projectName)] ?? null;
            if (empty($projectName)) {
                $rowIssues[] = "project_name is empty";
            } elseif (!$projectId) {
                $rowIssues[] = "Project not found '$projectName'";
            }

            // 2. Resolve users (Optional)
            $creatorEmail = $data['purchase_request_email'] ?? '';
            if (!empty($creatorEmail)) {
                $creatorId = $userMap[strtolower($creatorEmail)] ?? null;
                if (!$creatorId) {
                    $rowIssues[] = "Creator user not found '$creatorEmail'";
                }
            }

            $pmEmail = $data['pm_approved_email'] ?? '';
            if (!empty($pmEmail)) {
                $pmId = $userMap[strtolower($pmEmail)] ?? null;
                if (!$pmId) {
                    $rowIssues[] = "Project Manager user not found '$pmEmail'";
                }
            }

            $mgmtEmail = $data['management_approved_email'] ?? '';
            if (!empty($mgmtEmail)) {
                $mgmtId = $userMap[strtolower($mgmtEmail)] ?? null;
                if (!$mgmtId) {
                    $rowIssues[] = "Management user not found '$mgmtEmail'";
                }
            }

            // 3. Resolve warehouse (requires projectId)
            $warehouseName = $data['warehouse_name'] ?? '';
            if (empty($warehouseName)) {
                $rowIssues[] = "warehouse_name is empty";
            }

            // 4. Resolve vendor (by name and optional phone)
            $vendorName = $data['vendor_name'] ?? '';
            $vendorPhone = $data['vendor_phone'] ?? '';
            $vendorId = null;
            if (empty($vendorName)) {
                $rowIssues[] = "vendor_name is empty";
            } else {
                if (!empty($vendorPhone)) {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $vendorPhone);
                    $stmt = $this->db->prepare("SELECT id FROM vendors WHERE LOWER(name) = LOWER(?) AND (phone = ? OR phone LIKE ? OR REPLACE(phone, '-', '') LIKE ?) LIMIT 1");
                    $likePhone1 = "%" . $vendorPhone . "%";
                    $likePhone2 = "%" . $cleanPhone . "%";
                    $stmt->bind_param("ssss", $vendorName, $vendorPhone, $likePhone1, $likePhone2);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res) {
                        $vendorId = $res['id'];
                    } else {
                        $rowIssues[] = "Vendor not found with name '$vendorName' and phone '$vendorPhone'";
                    }
                } else {
                    $stmt = $this->db->prepare("SELECT id FROM vendors WHERE LOWER(name) = LOWER(?) LIMIT 1");
                    $stmt->bind_param("s", $vendorName);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    if ($res) {
                        $vendorId = $res['id'];
                    } else {
                        $rowIssues[] = "Vendor not found with name '$vendorName'";
                    }
                }
            }

            // 5. Quantity & price validation
            $qty = floatval(str_replace(',', '', $data['qty'] ?? '0'));
            if ($qty <= 0) {
                $rowIssues[] = "qty must be greater than 0";
            }
            $unitPrice = floatval(str_replace(',', '', $data['unit_price'] ?? '0'));
            if ($unitPrice < 0) {
                $rowIssues[] = "unit_price cannot be negative";
            }

            // 6. Payment validation
            $payAmount = floatval(str_replace(',', '', $data['payment_amount'] ?? '0'));
            if ($payAmount > 0) {
                $accEmail = $data['accounts_paid_email'] ?? '';
                if (!empty($accEmail)) {
                    $accId = $userMap[strtolower($accEmail)] ?? null;
                    if (!$accId) {
                        $rowIssues[] = "Accounts user not found '$accEmail'";
                    }
                }

                $payMethod = trim($data['payment_method'] ?? '');
                if (empty($payMethod)) {
                    $rowIssues[] = "payment_method is required since payment_amount is greater than 0";
                } else {
                    $normMethod = '';
                    $m = strtolower($payMethod);
                    if ($m === 'cash') $normMethod = 'Cash';
                    elseif (in_array($m, ['cheque', 'check'])) $normMethod = 'Cheque';
                    elseif (in_array($m, ['banktransfer', 'bank_transfer', 'bank transfer', 'bank'])) $normMethod = 'BankTransfer';
                    elseif (in_array($m, ['mobilewallet', 'mobile wallet', 'bkash', 'nagad', 'rocket', 'mobile_banking', 'online'])) $normMethod = 'MobileWallet';
                    else $normMethod = 'Other';

                    $allowedMethods = ['Cash', 'Cheque', 'BankTransfer', 'MobileWallet', 'Other'];
                    if (!in_array($normMethod, $allowedMethods)) {
                        $rowIssues[] = "payment_method '$payMethod' is invalid (allowed: Cash, Cheque, BankTransfer, MobileWallet, Other)";
                    }
                }
            }

            $results[] = [
                'row' => $rowNum,
                'valid' => empty($rowIssues),
                'error' => empty($rowIssues) ? '' : implode(' | ', $rowIssues),
                'data' => [
                    'project_name' => $projectName,
                    'purchase_request_email' => $creatorEmail,
                    'pm_approved_email' => $pmEmail,
                    'management_approved_email' => $mgmtEmail,
                    'accounts_paid_email' => $data['accounts_paid_email'] ?? '',
                    'receiving_date' => $data['receiving_date'] ?? '',
                    'warehouse_name' => $warehouseName,
                    'material_name' => $data['material_name'] ?? '',
                    'material_category' => $data['material_category'] ?? '',
                    'unit' => $data['unit'] ?? 'pcs',
                    'qty' => $data['qty'] ?? '0',
                    'unit_price' => $data['unit_price'] ?? '0',
                    'vendor_name' => $vendorName,
                    'vendor_phone' => $vendorPhone,
                    'payment_amount' => $data['payment_amount'] ?? '',
                    'payment_method' => $data['payment_method'] ?? '',
                    'payment_date' => $data['payment_date'] ?? '',
                    'payment_reference' => $data['payment_reference'] ?? '',
                    'payment_attachment' => $data['payment_attachment'] ?? ''
                ]
            ];
        }

        fclose($handle);

        echo json_encode([
            'success' => true,
            'rows' => $results
        ]);
        exit;
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

        $rawFirst = fgets($file);
        if (!$rawFirst) {
            setError('CSV file is empty or invalid.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }
        $rawFirst = preg_replace('/^\xEF\xBB\xBF/', '', $rawFirst);

        $counts = [
            ','  => substr_count($rawFirst, ','),
            ';'  => substr_count($rawFirst, ';'),
            "\t" => substr_count($rawFirst, "\t"),
        ];
        arsort($counts);
        $delimiter = array_key_first($counts);

        rewind($file);
        $bom = fread($file, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($file);
        }

        $header = fgetcsv($file, 0, $delimiter);
        if (!$header) {
            setError('CSV file is empty or invalid.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }
        $header = array_map(fn($h) => strtolower(preg_replace('/[^\x20-\x7E]/', '', trim($h))), $header);

        $required = ['project_name','receiving_date','warehouse_name','material_name','qty','unit_price','vendor_name'];
        $missing = array_diff($required, $header);
        if (!empty($missing)) {
            setError('CSV missing required columns: ' . implode(', ', $missing));
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }

        $hasUnit = in_array('unit', $header);

        $rows = [];
        while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
            if (count($row) < count($header)) continue;
            $data = array_combine($header, $row);
            $data = array_map('trim', $data);
            if (empty($data['project_name']) || str_starts_with($data['project_name'], '#')) continue;
            if (empty($data['material_name'])) continue;
            if (!$hasUnit) { $data['unit'] = 'pcs'; }
            $rows[] = $data;
        }
        fclose($file);

        if (empty($rows)) {
            setError('No valid rows found in CSV.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-material-receiving-import');
            exit;
        }

        $projectMap  = $this->buildMap("SELECT id, name FROM projects", 'name', 'id');
        $userMap     = $this->buildMap("SELECT id, email FROM users",    'email', 'id');
        $vendorMap   = $this->buildMap("SELECT id, name FROM vendors",   'name', 'id');
        
        $fallbackUserId = $this->getFallbackUserId();

        // Group rows by PO identifier
        $groups = [];
        foreach ($rows as $r) {
            $key = $r['project_name'] . '||' . ($r['purchase_request_email'] ?? '') . '||' . $r['vendor_name'] . '||' . ($r['vendor_phone'] ?? '') . '||' . ($r['payment_amount'] ?? '0') . '||' . ($r['payment_reference'] ?? '');
            $groups[$key][] = $r;
        }

        $errors  = [];
        $summary = ['materials_saved' => 0, 'pr' => 0, 'po' => 0, 'receiving' => 0, 'stock_lines' => 0, 'vpr' => 0, 'transactions' => 0];

        $this->db->begin_transaction();
        try {
            foreach ($groups as $groupKey => $groupRows) {
                $firstRow = $groupRows[0];

                // Resolve main IDs
                $projectId    = $projectMap[strtolower($firstRow['project_name'])]   ?? null;
                if (!$projectId)   { $errors[] = "Project not found: {$firstRow['project_name']}"; continue; }

                $creatorEmail = $firstRow['purchase_request_email'] ?? '';
                $createdById  = !empty($creatorEmail) ? ($userMap[strtolower($creatorEmail)] ?? $fallbackUserId) : $fallbackUserId;

                $pmEmail = $firstRow['pm_approved_email'] ?? '';
                $pmApprovedId = !empty($pmEmail) ? ($userMap[strtolower($pmEmail)] ?? $fallbackUserId) : $fallbackUserId;

                $mgmtEmail = $firstRow['management_approved_email'] ?? '';
                $mgmtApprovedId = !empty($mgmtEmail) ? ($userMap[strtolower($mgmtEmail)] ?? $fallbackUserId) : $fallbackUserId;

                $receivingDate = $firstRow['receiving_date'];

                // Resolve or create warehouse
                $warehouseId = $this->findOrCreateWarehouse($firstRow['warehouse_name'], $projectId, $createdById);

                // Resolve vendor
                $vName = trim($firstRow['vendor_name']);
                $vPhone = trim($firstRow['vendor_phone'] ?? '');
                $vendorId = null;
                if (!empty($vPhone)) {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $vPhone);
                    $stmt = $this->db->prepare("SELECT id FROM vendors WHERE LOWER(name) = LOWER(?) AND (phone = ? OR phone LIKE ? OR REPLACE(phone, '-', '') LIKE ?) LIMIT 1");
                    $likePhone1 = "%" . $vPhone . "%";
                    $likePhone2 = "%" . $cleanPhone . "%";
                    $stmt->bind_param("ssss", $vName, $vPhone, $likePhone1, $likePhone2);
                    $stmt->execute();
                    $res = $stmt->get_result()->fetch_assoc();
                    $vendorId = $res ? (int)$res['id'] : null;
                } else {
                    $vendorId = $vendorMap[strtolower($vName)] ?? null;
                }

                if (!$vendorId) { $errors[] = "Vendor not found: $vName (phone: $vPhone)"; continue; }

                $resolvedItems = [];
                $totalPoAmount = 0.0;

                foreach ($groupRows as $r) {
                    $unit = trim($r['unit'] ?? 'pcs');
                    if (empty($unit)) $unit = 'pcs';

                    // Resolve or create category
                    $catName = trim($r['material_category'] ?? '');
                    $categoryId = null;
                    if (!empty($catName)) {
                        $categoryId = $this->findOrCreateCategory($catName, $createdById);
                    }

                    // Resolve or create material
                    $matName = trim($r['material_name']);
                    $matId = $this->findOrCreateMaterial($matName, $unit, $categoryId, $createdById);

                    $qty      = floatval(str_replace(',', '', $r['qty']));
                    $unitPrice= floatval(str_replace(',', '', $r['unit_price']));

                    if ($qty <= 0)  { $errors[] = "Invalid qty for $matName"; continue; }

                    $resolvedItems[] = [
                        'material_id' => $matId,
                        'qty'         => $qty,
                        'unit_price'  => $unitPrice,
                    ];
                    $totalPoAmount += ($qty * $unitPrice);
                }

                if (empty($resolvedItems)) continue;

                // ── 1. purchase_requests ──────────────────────────────────
                $stmt = $this->db->prepare(
                    "INSERT INTO purchase_requests
                        (project_id, created_by, status, approved_by_id, approved_at, notes, upload_from)
                     VALUES (?, ?, 'PO Issued', ?, NOW(), 'CSV Import', 'from CSV')"
                );
                $stmt->bind_param('iii', $projectId, $createdById, $pmApprovedId);
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
                         approved_by_se, se_approved_at,
                         qc_approved_by_id, qc_approved_at, qc_note,
                         mgmt_approved_by_id, mgmt_approved_at, mgmt_note,
                         vendor_id, upload_from)
                     VALUES (?, ?, 'Fully Received', ?, ?, NOW(), ?, NOW(), ?, NOW(), 'CSV Import', ?, NOW(), 'CSV Import', ?, 'from CSV')"
                );
                $stmt2->bind_param('iisiiiii', $prId, $createdById, $today, $pmApprovedId, $pmApprovedId, $pmApprovedId, $mgmtApprovedId, $vendorId);
                $stmt2->execute();
                $poId = $this->db->insert_id;
                $summary['po']++;

                // ── 4. purchase_order_items ───────────────────────────────
                $poiStmt = $this->db->prepare(
                    "INSERT INTO purchase_order_items
                        (purchase_order_id, material_id, selected_vendor_id,
                         quantity, unit_price, created_by)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $poiIds = [];
                foreach ($resolvedItems as $item) {
                    $poiStmt->bind_param('iiiidi',
                        $poId,
                        $item['material_id'],
                        $vendorId,
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
                    $pmApprovedId, $pmApprovedId
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
                        $warehouseId,
                        $item['qty'],
                        $item['unit_price'],
                        $totalVal
                    );
                    $stockStmt->execute();

                    $movStmt->bind_param('iidii',
                        $item['material_id'],
                        $warehouseId,
                        $item['qty'],
                        $recId,
                        $createdById
                    );
                    $movStmt->execute();
                    $summary['stock_lines']++;
                }

                // ── 9. vendor_payment_requests ────────────────────────────
                $paymentAmount = floatval(str_replace(',', '', $firstRow['payment_amount'] ?? '0'));
                $dueAmount = $totalPoAmount - $paymentAmount;
                $vprStatus = 'Approved';
                if ($paymentAmount >= $totalPoAmount) {
                    $vprStatus = 'Fully_Paid';
                } elseif ($paymentAmount > 0) {
                    $vprStatus = 'Partially_Paid';
                }

                $paymentAttachment = trim($firstRow['payment_attachment'] ?? '');

                $stmtVpr = $this->db->prepare(
                    "INSERT INTO vendor_payment_requests
                        (vendor_id, project_id, purchase_order_id, payment_type, created_by,
                         invoice_number, invoice_amount, delivery_verified, qc_verified, qc_approved_by_id,
                         qc_approved_at, qc_note, management_note, status, approved_amount,
                         net_payable, approved_by, due_amount, payment_attachment)
                     VALUES (?, ?, ?, 'Final', ?, ?, ?, 1, 1, ?, NOW(), 'CSV Import', 'CSV Import', ?, ?, ?, ?, ?, ?)"
                );
                $invNum = 'INV-PO-' . $poId;
                $stmtVpr->bind_param('iiiisdisddids',
                    $vendorId, $projectId, $poId, $createdById, $invNum,
                    $totalPoAmount, $pmApprovedId, $vprStatus, $totalPoAmount,
                    $totalPoAmount, $mgmtApprovedId, $dueAmount, $paymentAttachment
                );
                $stmtVpr->execute();
                $vprId = $this->db->insert_id;
                $summary['vpr']++;

                // ── 10. con_financial_transactions (if paymentAmount > 0) ──
                if ($paymentAmount > 0) {
                    $accountsPaidEmail = $firstRow['accounts_paid_email'] ?? '';
                    $accountsPaidId = !empty($accountsPaidEmail) ? ($userMap[strtolower($accountsPaidEmail)] ?? $fallbackUserId) : $fallbackUserId;

                    $rawPayMethod = trim($firstRow['payment_method'] ?? 'Other');
                    $normMethod = 'Other';
                    $m = strtolower($rawPayMethod);
                    if ($m === 'cash') $normMethod = 'Cash';
                    elseif (in_array($m, ['cheque', 'check'])) $normMethod = 'Cheque';
                    elseif (in_array($m, ['banktransfer', 'bank_transfer', 'bank transfer', 'bank'])) $normMethod = 'BankTransfer';
                    elseif (in_array($m, ['mobilewallet', 'mobile wallet', 'bkash', 'nagad', 'rocket', 'mobile_banking', 'online'])) $normMethod = 'MobileWallet';

                    $payTypeFT = ($paymentAmount >= $totalPoAmount) ? 'Full' : 'Partial';
                    $payRef = trim($firstRow['payment_reference'] ?? '');
                    if (empty($payRef)) {
                        $payRef = 'REF-PO-' . $poId;
                    }

                    $payDate = trim($firstRow['payment_date'] ?? '');
                    if (empty($payDate)) {
                        $payDate = date('Y-m-d H:i:s');
                        $payDateStr = date('Y-m-d');
                    } else {
                        $payDateStr = date('Y-m-d', strtotime($payDate));
                    }

                    $stmtFt = $this->db->prepare(
                        "INSERT INTO con_financial_transactions
                            (ref_type, project_id, ref_id, payment_type, payment_method, amount_paid,
                             reference_number, processed_by, transaction_date, transaction_date_str,
                             update_status, notes, disbursement_proof, created_by)
                         VALUES ('vendor_payment', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', 'CSV Import', ?, ?)"
                    );
                    $stmtFt->bind_param('iisssdisssi',
                        $projectId, $vprId, $payTypeFT, $normMethod, $paymentAmount,
                        $payRef, $accountsPaidId, $payDate, $payDateStr, $paymentAttachment, $accountsPaidId
                    );
                    $stmtFt->execute();
                    $summary['transactions']++;
                }
            }

            if (!empty($errors)) {
                $this->db->commit();
                setError('Import completed with warnings: ' . implode(' | ', array_slice($errors, 0, 5)));
            } else {
                $this->db->commit();
                setSuccess(
                    "Import successful! " .
                    "PR: {$summary['pr']}, PO: {$summary['po']}, " .
                    "Receivings: {$summary['receiving']}, Stock: {$summary['stock_lines']}, " .
                    "Payment Requests: {$summary['vpr']}, Payments Made: {$summary['transactions']}"
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
    // HELPER: Resolve/Create Material Category
    // =========================================================================

    private function findOrCreateCategory(string $catName, int $createdById): int
    {
        $cleanName = strtolower(str_replace(' ', '', $catName));
        
        $stmt = $this->db->prepare("SELECT id FROM con_material_categories WHERE LOWER(REPLACE(name, ' ', '')) = ? LIMIT 1");
        $stmt->bind_param("s", $cleanName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            return (int)$row['id'];
        }

        $stmt2 = $this->db->prepare("INSERT INTO con_material_categories (name, created_by, created_at) VALUES (?, ?, NOW())");
        $stmt2->bind_param("si", $catName, $createdById);
        $stmt2->execute();
        return (int)$this->db->insert_id;
    }

    // =========================================================================
    // HELPER: Resolve/Create Material
    // =========================================================================

    private function findOrCreateMaterial(string $matName, string $unit, ?int $categoryId, int $createdById): int
    {
        $cleanName = strtolower(str_replace(' ', '', $matName));
        
        $stmt = $this->db->prepare("SELECT id, category_id FROM con_materials WHERE LOWER(REPLACE(name, ' ', '')) = ? LIMIT 1");
        $stmt->bind_param("s", $cleanName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $matId = (int)$row['id'];
            if ($categoryId !== null && (int)$row['category_id'] !== $categoryId) {
                $stmtUp = $this->db->prepare("UPDATE con_materials SET category_id = ? WHERE id = ?");
                $stmtUp->bind_param("ii", $categoryId, $matId);
                $stmtUp->execute();
            }
            return $matId;
        }

        $stmt2 = $this->db->prepare("INSERT INTO con_materials (name, unit, category_id, min_stock_level, created_by, created_at) VALUES (?, ?, ?, 0, ?, NOW())");
        $stmt2->bind_param("ssii", $matName, $unit, $categoryId, $createdById);
        $stmt2->execute();
        return (int)$this->db->insert_id;
    }

    // =========================================================================
    // HELPER: Resolve/Create Warehouse
    // =========================================================================

    private function findOrCreateWarehouse(string $name, int $projectId, int $createdById): int
    {
        $cleanName = strtolower(str_replace(' ', '', $name));
        
        $stmt = $this->db->prepare("SELECT id FROM con_warehouses WHERE LOWER(REPLACE(name, ' ', '')) = ? AND project_id = ? LIMIT 1");
        $stmt->bind_param("si", $cleanName, $projectId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            return (int)$row['id'];
        }

        $stmt2 = $this->db->prepare("INSERT INTO con_warehouses (name, project_id, location, created_by, created_at) VALUES (?, ?, 'CSV Import', ?, NOW())");
        $stmt2->bind_param("sisi", $name, $projectId, $createdById);
        $stmt2->execute();
        return (int)$this->db->insert_id;
    }

    // =========================================================================
    // HELPER: Get Fallback User ID
    // =========================================================================

    private function getFallbackUserId(): int
    {
        $res = $this->db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['id'];
        }
        return 1; // default absolute fallback
    }

    // =========================================================================
    // DOWNLOAD TEMPLATE CSV
    // =========================================================================

    public function downloadTemplate()
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="purchase_orders_payments_bulk_template.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');

        // Header row
        fputcsv($out, [
            'project_name', 'purchase_request_email', 'pm_approved_email', 'management_approved_email', 'accounts_paid_email',
            'receiving_date', 'warehouse_name', 'material_name', 'material_category', 'unit', 'qty', 'unit_price',
            'vendor_name', 'vendor_phone', 'payment_amount', 'payment_method', 'payment_date', 'payment_reference', 'payment_attachment'
        ]);

        // Sample data row
        fputcsv($out, [
            'Mirpur Housing Project', 'site.engineer@tphl.com', 'pm@tphl.com', 'management@tphl.com', 'accounts@tphl.com',
            date('Y-m-d'), 'Main Warehouse', 'Brick (1000pcs)', 'Civil Materials', 'pcs', '50', '12.00',
            'ABC Suppliers Ltd', '01712345678', '600.00', 'BankTransfer', date('Y-m-d'), 'TXN987654321', 'receipt.pdf'
        ]);

        fclose($out);
        exit;
    }
}
