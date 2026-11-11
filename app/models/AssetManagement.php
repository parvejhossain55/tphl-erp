<?php

class AssetManagement
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // ASSET PRODUCTS — CRUD
    // =========================================================================

    public function getAllProducts($filters = [])
    {
        $sql = "SELECT p.*, u.full_name AS created_by_name, cat.name AS category_name
                FROM office_asset_products p
                LEFT JOIN users u ON u.id = p.created_by
                LEFT JOIN office_procurement_categories cat ON cat.id = p.category_id
                WHERE 1=1";
                
        $params = [];
        $types = "";

        if (!empty($filters['search'])) {
            $sql .= " AND (p.product_code LIKE ? OR p.name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s]);
            $types .= 'ss';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
            $types .= 'i';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductStockCounts()
    {
        $sql = "SELECT 
                    product_id AS id, 
                    COUNT(*) AS total_assets,
                    SUM(CASE WHEN asset_status = 'in_store' THEN 1 ELSE 0 END) AS in_store,
                    SUM(CASE WHEN asset_status = 'in_use' THEN 1 ELSE 0 END) AS in_use,
                    SUM(CASE WHEN asset_status = 'maintenance' THEN 1 ELSE 0 END) AS in_maintenance,
                    SUM(CASE WHEN asset_status = 'disposed' THEN 1 ELSE 0 END) AS disposed,
                    SUM(CASE WHEN asset_status = 'transferred' THEN 1 ELSE 0 END) AS transferred
                FROM office_assets 
                WHERE product_id IS NOT NULL
                GROUP BY product_id";
        
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function productCodeExists($productCode, $excludeId = 0)
    {
        $sql = "SELECT id FROM office_asset_products WHERE product_code = ?";
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
        }
        $stmt = $this->db->prepare($sql);
        if ($excludeId > 0) {
            $stmt->bind_param('si', $productCode, $excludeId);
        } else {
            $stmt->bind_param('s', $productCode);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->num_rows > 0;
    }

    public function createProduct($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO office_asset_products 
            (product_code, name, category_id, brand, model, unit, unit_price, description, image_path, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'ssisssdsssi',
            $data['product_code'],
            $data['name'],
            $data['category_id'],
            $data['brand'],
            $data['model'],
            $data['unit'],
            $data['unit_price'],
            $data['description'],
            $data['image_path'],
            $data['status'],
            $data['created_by']
        );

        return $stmt->execute();
    }

    public function updateProduct($id, $data)
    {
        $sql = "UPDATE office_asset_products SET 
                product_code = ?, name = ?, category_id = ?, brand = ?, model = ?, unit = ?, 
                unit_price = ?, description = ?, status = ?";
        $params = [
            $data['product_code'], $data['name'], $data['category_id'], $data['brand'], 
            $data['model'], $data['unit'], $data['unit_price'], $data['description'], 
            $data['status']
        ];
        $types = "ssisssdss";

        if (array_key_exists('image_path', $data) && $data['image_path'] !== null) {
            $sql .= ", image_path = ?";
            $params[] = $data['image_path'];
            $types .= "s";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    public function deleteProduct($id)
    {
        $stmt = $this->db->prepare("SELECT id FROM office_assets WHERE product_id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Cannot delete product. It is linked to existing assets.'];
        }

        $stmt = $this->db->prepare("DELETE FROM office_asset_products WHERE id = ?");
        $stmt->bind_param('i', $id);
        $res = $stmt->execute();
        
        return ['success' => $res, 'message' => $res ? '' : 'Database error'];
    }

    // =========================================================================
    // OFFICE ASSETS — CRUD
    // =========================================================================

    // =========================================================================
    // OFFICE BRANCHES
    // =========================================================================

    public function ensureOfficeBranchesSchema()
    {
        // 1. Create table office_branches if not exists
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `office_branches` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `status` enum('active','inactive') DEFAULT 'active',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Ensure default branches are seeded if table is empty
        $check = $this->db->query("SELECT COUNT(*) as c FROM `office_branches`");
        if ($check && (int)($check->fetch_assoc()['c'] ?? 0) === 0) {
            $branches = [
                'Corporate Gulshan Office',
                'Ashulia Site Office',
                'Ati Site Office 2',
                'Shaymoli Zonal Office'
            ];
            $stmt = $this->db->prepare("INSERT INTO `office_branches` (`name`, `status`) VALUES (?, 'active')");
            if ($stmt) {
                foreach ($branches as $b) {
                    $stmt->bind_param('s', $b);
                    $stmt->execute();
                }
            }
        }

        // 3. Ensure office_branch_id column exists in office_assets table
        $res = $this->db->query("SHOW COLUMNS FROM `office_assets` LIKE 'office_branch_id'");
        if ($res && $res->num_rows === 0) {
            $this->db->query("ALTER TABLE `office_assets` ADD COLUMN `office_branch_id` INT NULL DEFAULT NULL AFTER `project_id`");
        }

        // 4. Ensure category_id column exists in office_asset_products table
        $resCat = $this->db->query("SHOW COLUMNS FROM `office_asset_products` LIKE 'category_id'");
        if ($resCat && $resCat->num_rows === 0) {
            $this->db->query("ALTER TABLE `office_asset_products` ADD COLUMN `category_id` INT NULL DEFAULT NULL AFTER `name`");
        }

        // 5. Ensure return_note column exists in project_extra_assets
        $resRn = $this->db->query("SHOW COLUMNS FROM `project_extra_assets` LIKE 'return_note'");
        if ($resRn && $resRn->num_rows === 0) {
            $this->db->query("ALTER TABLE `project_extra_assets` ADD COLUMN `return_note` TEXT NULL DEFAULT NULL AFTER `return_amount`");
        }
    }

    public function getAllOfficeBranches($status = 'active')
    {
        $this->ensureOfficeBranchesSchema();
        $sql = "SELECT * FROM office_branches WHERE 1=1";
        if ($status) {
            $sql .= " AND status = '$status'";
        }
        $sql .= " ORDER BY id ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get all assets with filters
     */
    public function getAllAssets($filters = [])
    {
        $this->ensureOfficeBranchesSchema();
        $sql = "SELECT oa.*, 
                       p.name AS project_name,
                       ob.name AS branch_name,
                       u_assigned.full_name AS assigned_to_name,
                       u_created.full_name AS created_by_name,
                       (SELECT COUNT(*) FROM asset_movement_logs WHERE asset_id = oa.id AND action = 'transferred') AS transfer_count
                FROM office_assets oa
                LEFT JOIN projects p ON p.id = oa.project_id
                LEFT JOIN office_branches ob ON ob.id = oa.office_branch_id
                LEFT JOIN users u_assigned ON u_assigned.id = oa.assigned_to
                LEFT JOIN users u_created ON u_created.id = oa.created_by
                WHERE 1=1";

        $params = [];
        $types = "";

        if (!empty($filters['search'])) {
            $sql .= " AND (oa.asset_code LIKE ? OR oa.item_name LIKE ? OR oa.serial_no LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s]);
            $types .= 'sss';
        }

        if (!empty($filters['project_id'])) {
            $sql .= " AND oa.project_id = ?";
            $params[] = (int)$filters['project_id'];
            $types .= 'i';
        }

        if (!empty($filters['office_branch_id'])) {
            $sql .= " AND oa.office_branch_id = ?";
            $params[] = (int)$filters['office_branch_id'];
            $types .= 'i';
        }

        if (!empty($filters['condition_status'])) {
            $sql .= " AND oa.condition_status = ?";
            $params[] = $filters['condition_status'];
            $types .= 's';
        }

        if (!empty($filters['asset_status'])) {
            $sql .= " AND oa.asset_status = ?";
            $params[] = $filters['asset_status'];
            $types .= 's';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND oa.product_id IN (SELECT id FROM office_asset_products WHERE category_id = ?)";
            $params[] = (int)$filters['category_id'];
            $types .= 'i';
        }

        $sql .= " ORDER BY oa.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Find single asset by ID with all joins
     */
    public function findAsset($id)
    {
        $this->ensureOfficeBranchesSchema();
        $stmt = $this->db->prepare(
            "SELECT oa.*, 
                    p.name AS project_name,
                    ob.name AS branch_name,
                    u_assigned.full_name AS assigned_to_name,
                    u_created.full_name AS created_by_name
             FROM office_assets oa
             LEFT JOIN projects p ON p.id = oa.project_id
             LEFT JOIN office_branches ob ON ob.id = oa.office_branch_id
             LEFT JOIN users u_assigned ON u_assigned.id = oa.assigned_to
             LEFT JOIN users u_created ON u_created.id = oa.created_by
             WHERE oa.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create new asset
     */
    public function createAsset($data)
    {
        $this->ensureOfficeBranchesSchema();
        $assignedTo = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;
        $branchId = !empty($data['office_branch_id']) ? (int)$data['office_branch_id'] : ($assignedTo ? $this->getEmployeeOfficeBranch($assignedTo) : null);
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $deptId = !empty($data['department_id']) ? (int)$data['department_id'] : null;

        $assetCode = !empty($data['asset_code']) ? $data['asset_code'] : $this->generateAssetCode();
        $productId = !empty($data['product_id']) ? (int)$data['product_id'] : null;
        $itemName = trim($data['item_name'] ?? '');

        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;

        // If product_id is empty but item_name is provided, find or create product automatically
        if (!$productId && !empty($itemName)) {
            $stmtP = $this->db->prepare("SELECT id FROM office_asset_products WHERE LOWER(name) = LOWER(?) LIMIT 1");
            if ($stmtP) {
                $stmtP->bind_param('s', $itemName);
                $stmtP->execute();
                $pRes = $stmtP->get_result()->fetch_assoc();
                if ($pRes) {
                    $productId = (int)$pRes['id'];
                    if ($categoryId) {
                        $this->db->query("UPDATE office_asset_products SET category_id = $categoryId WHERE id = $productId");
                    }
                }
            }

            if (!$productId) {
                $pCode = 'PRD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $cleanName = $this->db->real_escape_string($itemName);
                $cIdSql = $categoryId ? (int)$categoryId : "NULL";
                $cBy = (int)($data['created_by'] ?? 1);

                $insRes = $this->db->query("INSERT INTO office_asset_products (product_code, name, category_id, status, created_by) VALUES ('$pCode', '$cleanName', $cIdSql, 'active', $cBy)");
                if (!$insRes) {
                    $this->db->query("INSERT INTO office_asset_products (product_code, name, status, created_by) VALUES ('$pCode', '$cleanName', 'active', $cBy)");
                }
                $productId = (int)$this->db->insert_id;
            }
        } elseif ($productId && empty($itemName)) {
            $stmtName = $this->db->prepare("SELECT name FROM office_asset_products WHERE id = ?");
            $stmtName->bind_param('i', $productId);
            $stmtName->execute();
            $pRow = $stmtName->get_result()->fetch_assoc();
            if ($pRow) {
                $itemName = $pRow['name'];
            }
        }

        if (empty($itemName)) {
            $itemName = 'Office Asset';
        }

        $location = $data['location'] ?? 'Head Office';

        $stmt = $this->db->prepare(
            "INSERT INTO office_assets 
            (asset_code, product_id, item_name, project_id, office_branch_id, department_id, assigned_to, location, 
             purchase_date, purchase_price, warranty_expiry, serial_no, 
             condition_status, asset_status, remarks, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $conditionStatus = $data['condition_status'] ?? 'good';
        $assetStatus = $data['asset_status'] ?? (!empty($assignedTo) ? 'in_use' : 'in_store');
        $remarks = $data['remarks'] ?? '';
        $createdBy = (int)($data['created_by'] ?? 1);
        $purchaseDate = !empty($data['purchase_date']) ? $data['purchase_date'] : date('Y-m-d');
        $purchasePrice = isset($data['purchase_price']) ? (float)$data['purchase_price'] : 0.00;
        $warrantyExpiry = !empty($data['warranty_expiry']) ? $data['warranty_expiry'] : null;
        $serialNo = !empty($data['serial_no']) ? $data['serial_no'] : null;

        $stmt->bind_param(
            'sisiiiissdsssssi',
            $assetCode,
            $productId,
            $itemName,
            $projectId,
            $branchId,
            $deptId,
            $assignedTo,
            $location,
            $purchaseDate,
            $purchasePrice,
            $warrantyExpiry,
            $serialNo,
            $conditionStatus,
            $assetStatus,
            $remarks,
            $createdBy
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Update asset
     */
    public function updateAsset($id, $data)
    {
        $this->ensureOfficeBranchesSchema();
        $old = $this->findAsset($id);
        if (!$old) return false;

        $itemName = array_key_exists('item_name', $data) && trim($data['item_name']) !== '' ? trim($data['item_name']) : $old['item_name'];
        $productId = array_key_exists('product_id', $data) ? (!empty($data['product_id']) ? (int)$data['product_id'] : null) : $old['product_id'];
        $projectId = array_key_exists('project_id', $data) ? (!empty($data['project_id']) ? (int)$data['project_id'] : null) : $old['project_id'];
        $assignedTo = array_key_exists('assigned_to', $data) ? (!empty($data['assigned_to']) ? (int)$data['assigned_to'] : null) : $old['assigned_to'];
        $branchId = array_key_exists('office_branch_id', $data) ? (!empty($data['office_branch_id']) ? (int)$data['office_branch_id'] : ($assignedTo ? $this->getEmployeeOfficeBranch($assignedTo) : null)) : ($assignedTo && empty($old['office_branch_id']) ? $this->getEmployeeOfficeBranch($assignedTo) : $old['office_branch_id']);
        $deptId = array_key_exists('department_id', $data) ? (!empty($data['department_id']) ? (int)$data['department_id'] : null) : $old['department_id'];
        $location = array_key_exists('location', $data) ? $data['location'] : $old['location'];
        $purchaseDate = array_key_exists('purchase_date', $data) ? $data['purchase_date'] : $old['purchase_date'];
        $purchasePrice = (array_key_exists('purchase_price', $data) && $data['purchase_price'] !== '' && $data['purchase_price'] !== null) ? (float)$data['purchase_price'] : (float)($old['purchase_price'] ?? 0);
        $warrantyExpiry = array_key_exists('warranty_expiry', $data) ? $data['warranty_expiry'] : $old['warranty_expiry'];
        $serialNo = array_key_exists('serial_no', $data) ? $data['serial_no'] : $old['serial_no'];
        $conditionStatus = array_key_exists('condition_status', $data) ? $data['condition_status'] : $old['condition_status'];
        $assetStatus = array_key_exists('asset_status', $data) ? $data['asset_status'] : $old['asset_status'];
        $remarks = array_key_exists('remarks', $data) ? $data['remarks'] : $old['remarks'];

        $stmt = $this->db->prepare(
            "UPDATE office_assets SET
                item_name = ?, product_id = ?, project_id = ?, office_branch_id = ?, department_id = ?, assigned_to = ?,
                location = ?, purchase_date = ?, purchase_price = ?, warranty_expiry = ?,
                serial_no = ?, condition_status = ?, asset_status = ?, remarks = ?
            WHERE id = ?"
        );

        $stmt->bind_param(
            'siiiiissdsssssi',
            $itemName,
            $productId,
            $projectId,
            $branchId,
            $deptId,
            $assignedTo,
            $location,
            $purchaseDate,
            $purchasePrice,
            $warrantyExpiry,
            $serialNo,
            $conditionStatus,
            $assetStatus,
            $remarks,
            $id
        );

        return $stmt->execute();
    }

    /**
     * Generate next asset code: AST-YYYY-NNNN
     */
    public function generateAssetCode()
    {
        $year = date('Y');
        $prefix = "AST-{$year}-";

        $stmt = $this->db->prepare(
            "SELECT asset_code FROM office_assets WHERE asset_code LIKE ? ORDER BY id DESC LIMIT 1"
        );
        $like = $prefix . '%';
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $lastNum = (int)substr($row['asset_code'], strlen($prefix));
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate next product code: PRD-YYYY-NNNN
     */
    public function generateProductCode()
    {
        $year = date('Y');
        $prefix = "PRD-{$year}-";

        $stmt = $this->db->prepare(
            "SELECT product_code FROM office_asset_products WHERE product_code LIKE ? ORDER BY id DESC LIMIT 1"
        );
        $like = $prefix . '%';
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $lastNum = (int)substr($row['product_code'], strlen($prefix));
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Look up an employee's assigned office branch ID
     */
    public function getEmployeeOfficeBranch($userId)
    {
        if (empty($userId)) return null;
        $userId = (int)$userId;
        $stmt = $this->db->prepare("SELECT office_branch_id, office_branch, branch_name FROM employees WHERE user_id = ? OR id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ii', $userId, $userId);
            $stmt->execute();
            $emp = $stmt->get_result()->fetch_assoc();
            if ($emp && !empty($emp['office_branch_id'])) {
                return (int)$emp['office_branch_id'];
            }
        }
        return null;
    }

    /**
     * Resolve the appropriate Fixed Asset account ID (Property, Plant & Equipment) in Chart of Accounts
     */
    public function getFixedAssetAccountId($itemCategoryId = null, $itemName = '')
    {
        // 1. Keyword matching on Item Name
        $nameLower = strtolower(trim($itemName));
        if (strpos($nameLower, 'computer') !== false || strpos($nameLower, 'laptop') !== false || strpos($nameLower, 'printer') !== false || strpos($nameLower, 'monitor') !== false || strpos($nameLower, 'keyboard') !== false || strpos($nameLower, 'mouse') !== false || strpos($nameLower, 'ups') !== false || strpos($nameLower, 'scanner') !== false || strpos($nameLower, 'router') !== false) {
            $acc = $this->getAccountIdByCode('100001'); // Computer, Printer & IT Equipment
            if ($acc) return $acc;
        }
        if (strpos($nameLower, 'chair') !== false || strpos($nameLower, 'table') !== false || strpos($nameLower, 'desk') !== false || strpos($nameLower, 'sofa') !== false || strpos($nameLower, 'cabinet') !== false || strpos($nameLower, 'almirah') !== false || strpos($nameLower, 'furniture') !== false) {
            $acc = $this->getAccountIdByCode('100000'); // Furniture & Fixtures
            if ($acc) return $acc;
        }
        if (strpos($nameLower, 'vehicle') !== false || strpos($nameLower, 'bike') !== false || strpos($nameLower, 'car') !== false || strpos($nameLower, 'motorcycle') !== false) {
            $acc = $this->getAccountIdByCode('100003'); // Transport & Vehicle
            if ($acc) return $acc;
        }

        // 2. Check Procurement Category ID
        if ($itemCategoryId) {
            $catStmt = $this->db->prepare("SELECT name FROM office_procurement_categories WHERE id = ?");
            if ($catStmt) {
                $catStmt->bind_param('i', $itemCategoryId);
                $catStmt->execute();
                $cat = $catStmt->get_result()->fetch_assoc();
                if ($cat) {
                    $catName = strtolower($cat['name']);
                    if (strpos($catName, 'computer') !== false || strpos($catName, 'printer') !== false || strpos($catName, 'it security') !== false) {
                        $acc = $this->getAccountIdByCode('100001');
                        if ($acc) return $acc;
                    }
                    if (strpos($catName, 'furniture') !== false) {
                        $acc = $this->getAccountIdByCode('100000');
                        if ($acc) return $acc;
                    }
                    if (strpos($catName, 'vehicle') !== false) {
                        $acc = $this->getAccountIdByCode('100003');
                        if ($acc) return $acc;
                    }
                }
            }
        }

        // Default: Office Equipment (100002)
        $acc = $this->getAccountIdByCode('100002');
        return $acc ? $acc : $this->getAccountIdByCode('100');
    }

    /**
     * Helper to find chart of accounts ID by account_code
     */
    public function getAccountIdByCode($code)
    {
        $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res) return (int)$res['id'];
        }
        return null;
    }

    /**
     * Capitalize an existing or newly registered office asset into the Balance Sheet under Non-Current Assets (Property, Plant & Equipment)
     */
    public function syncAssetToBalanceSheet($assetId, $userId = 1)
    {
        $asset = $this->findAsset($assetId);
        if (!$asset) return false;

        $price = (float)($asset['purchase_price'] ?? 0);
        if ($price <= 0) return false;

        $fixedAssetAccId = $this->getFixedAssetAccountId($asset['category_id'] ?? null, $asset['item_name'] ?? '');
        if (!$fixedAssetAccId) {
            $fixedAssetAccId = $this->getAccountIdByCode('100002') ?: $this->getAccountIdByCode('100');
        }
        if (!$fixedAssetAccId) return false;

        // Equity account: Retained Earnings (352000) or Paid Up Capital (29) or Equity (3000)
        $equityAccId = $this->getAccountIdByCode('352000') ?: ($this->getAccountIdByCode('29') ?: $this->getAccountIdByCode('3000'));
        if (!$equityAccId) return false;

        $purchaseDate = (!empty($asset['purchase_date']) && $asset['purchase_date'] !== '0000-00-00') ? $asset['purchase_date'] : date('Y-m-d');
        $assetCode = $asset['asset_code'] ?? ('AST-' . $assetId);
        $itemName = $asset['item_name'] ?? 'Office Asset';
        $location = !empty($asset['branch_name']) ? $asset['branch_name'] : (!empty($asset['location']) ? $asset['location'] : 'Head Office');
        $narration = "Company Asset Register (Fixed Asset) [{$assetCode}]: {$itemName}";

        // Check if journal entry already exists for this asset
        $stmtJ = $this->db->prepare("SELECT id FROM journals WHERE source_module = 'office_assets' AND source_ref_id = ? AND entry_type = 'accrual' LIMIT 1");
        $stmtJ->bind_param('i', $assetId);
        $stmtJ->execute();
        $jRow = $stmtJ->get_result()->fetch_assoc();

        if ($jRow) {
            $journalId = (int)$jRow['id'];
            // Update journal header date, amount, narration
            $updJ = $this->db->prepare("UPDATE journals SET date = ?, total_debit = ?, total_credit = ?, narration = ?, project_id = ?, office = ? WHERE id = ?");
            $updJ->bind_param('sddsisi', $purchaseDate, $price, $price, $narration, $asset['project_id'], $location, $journalId);
            $updJ->execute();

            // Update debit line to point to fixed asset account with exact price
            $updDr = $this->db->prepare("UPDATE journal_details SET account_id = ?, debit = ?, credit = 0.00, narration = ?, project_id = ? WHERE journal_id = ? AND debit > 0 LIMIT 1");
            $updDr->bind_param('idsii', $fixedAssetAccId, $price, $narration, $asset['project_id'], $journalId);
            $updDr->execute();

            // Update credit line to point to equity account with exact price
            $updCr = $this->db->prepare("UPDATE journal_details SET account_id = ?, debit = 0.00, credit = ?, narration = ?, project_id = ? WHERE journal_id = ? AND credit > 0 LIMIT 1");
            $updCr->bind_param('idsii', $equityAccId, $price, $narration, $asset['project_id'], $journalId);
            $updCr->execute();

            return $journalId;
        } else {
            try {
                require_once dirname(__DIR__) . '/services/AutoPostingService.php';
                $autoPosting = new \AutoPostingService($this->db);
                return $autoPosting->postAccrualEntry(
                    'office_assets',
                    $assetId,
                    $fixedAssetAccId,
                    $equityAccId,
                    $price,
                    $narration,
                    !empty($asset['project_id']) ? (int)$asset['project_id'] : null,
                    'employee',
                    !empty($asset['assigned_to']) ? (int)$asset['assigned_to'] : null,
                    $userId,
                    $location,
                    $purchaseDate
                );
            } catch (\Exception $e) {
                error_log("Failed to sync asset {$assetId} to balance sheet: " . $e->getMessage());
                return false;
            }
        }
    }

    /**
     * Auto sync any existing assets in office_assets that have purchase_price > 0 but no journal entry yet
     */
    public function syncAllUnpostedAssetsToBalanceSheet($userId = 1)
    {
        $res = $this->db->query("
            SELECT oa.id 
            FROM office_assets oa 
            LEFT JOIN journals j ON (j.source_module = 'office_assets' AND j.source_ref_id = oa.id AND j.entry_type = 'accrual')
            WHERE oa.purchase_price > 0 AND j.id IS NULL
        ");
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $this->syncAssetToBalanceSheet((int)$row['id'], $userId);
            }
        }
    }

    /**
     * Log an asset movement/change
     */
    public function logMovement($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO asset_movement_logs 
            (asset_id, action, from_location, to_location, from_user_id, to_user_id, 
             from_project_id, to_project_id, old_condition, new_condition, notes, performed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'isssiiiisssi',
            $data['asset_id'],
            $data['action'],
            $data['from_location'],
            $data['to_location'],
            $data['from_user_id'],
            $data['to_user_id'],
            $data['from_project_id'],
            $data['to_project_id'],
            $data['old_condition'],
            $data['new_condition'],
            $data['notes'],
            $data['performed_by']
        );

        return $stmt->execute();
    }

    /**
     * Get movement history for an asset
     */
    public function getMovementLog($assetId)
    {
        $stmt = $this->db->prepare(
            "SELECT aml.*, 
                    u_performer.full_name AS performed_by_name,
                    u_from.full_name AS from_user_name,
                    u_to.full_name AS to_user_name,
                    p_from.name AS from_project_name,
                    p_to.name AS to_project_name
             FROM asset_movement_logs aml
             LEFT JOIN users u_performer ON u_performer.id = aml.performed_by
             LEFT JOIN users u_from ON u_from.id = aml.from_user_id
             LEFT JOIN users u_to ON u_to.id = aml.to_user_id
             LEFT JOIN projects p_from ON p_from.id = aml.from_project_id
             LEFT JOIN projects p_to ON p_to.id = aml.to_project_id
             WHERE aml.asset_id = ?
             ORDER BY aml.created_at DESC"
        );
        $stmt->bind_param('i', $assetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // PROJECT EXTRA ASSET PURCHASES
    // =========================================================================

    /**
     * Ensure database table schema has all necessary columns on live/local
     */
    public function ensureExtraPurchasesTableSchema()
    {
        // 1. Ensure office_procurement_categories table
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `office_procurement_categories` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `status` enum('active','inactive') DEFAULT 'active',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 1.5 Ensure project_extra_assets table exists
        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `project_extra_assets` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `project_id` INT(11) NULL DEFAULT NULL,
                `department_id` INT(11) NULL DEFAULT NULL,
                `office_branch_id` INT(11) NULL DEFAULT NULL,
                `asset_location` VARCHAR(255) NULL DEFAULT 'Head Office',
                `assigned_to` INT(11) NULL DEFAULT NULL,
                `item_category_id` INT(11) NULL DEFAULT 1,
                `request_type` ENUM('Purchase','Servicing') DEFAULT 'Purchase',
                `account_id` INT(11) NULL DEFAULT NULL,
                `product_id` INT(11) NULL DEFAULT NULL,
                `item_name` VARCHAR(255) NOT NULL,
                `quantity` INT(11) NOT NULL DEFAULT 1,
                `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `total_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `justification` TEXT NULL DEFAULT NULL,
                `attachment` VARCHAR(255) NULL DEFAULT NULL,
                `requested_by` INT(11) NOT NULL,
                `reporting_user_id` INT(11) NULL DEFAULT NULL,
                `vendor_id` INT(11) NULL DEFAULT NULL,
                `vendor_name` VARCHAR(255) NULL DEFAULT NULL,
                `dept_head_status` ENUM('pending','approved','rejected','not_required') DEFAULT 'not_required',
                `dept_head_by` INT(11) NULL DEFAULT NULL,
                `dept_head_at` DATETIME NULL DEFAULT NULL,
                `dept_head_note` TEXT NULL DEFAULT NULL,
                `it_head_status` ENUM('pending','approved','rejected','not_required') DEFAULT 'not_required',
                `it_head_by` INT(11) NULL DEFAULT NULL,
                `it_head_at` DATETIME NULL DEFAULT NULL,
                `it_head_note` TEXT NULL DEFAULT NULL,
                `operations_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
                `operations_by` INT(11) NULL DEFAULT NULL,
                `operations_at` TIMESTAMP NULL DEFAULT NULL,
                `operations_note` TEXT NULL DEFAULT NULL,
                `purchase_head_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
                `purchase_head_by` INT(11) NULL DEFAULT NULL,
                `purchase_head_at` TIMESTAMP NULL DEFAULT NULL,
                `purchase_head_note` TEXT NULL DEFAULT NULL,
                `purchase_head_document` VARCHAR(255) NULL DEFAULT NULL,
                `qc_audit_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
                `qc_audit_by` INT(11) NULL DEFAULT NULL,
                `qc_audit_at` TIMESTAMP NULL DEFAULT NULL,
                `qc_audit_note` TEXT NULL DEFAULT NULL,
                `mgmt_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
                `mgmt_by` INT(11) NULL DEFAULT NULL,
                `mgmt_at` TIMESTAMP NULL DEFAULT NULL,
                `mgmt_note` TEXT NULL DEFAULT NULL,
                `payment_status` ENUM('unpaid','paid') DEFAULT 'unpaid',
                `paid_by` INT(11) NULL DEFAULT NULL,
                `paid_at` TIMESTAMP NULL DEFAULT NULL,
                `payment_method` VARCHAR(100) NULL DEFAULT NULL,
                `paid_amount` DECIMAL(15,2) NULL DEFAULT NULL,
                `payment_attachment` VARCHAR(255) NULL DEFAULT NULL,
                `overall_status` VARCHAR(50) DEFAULT 'pending',
                `purchase_document` VARCHAR(255) NULL DEFAULT NULL,
                `purchase_note` TEXT NULL DEFAULT NULL,
                `purchase_date` DATE NULL DEFAULT NULL,
                `return_amount` DECIMAL(15,2) NULL DEFAULT 0.00,
                `return_note` TEXT NULL DEFAULT NULL,
                `purchase_confirmed_by` INT(11) NULL DEFAULT NULL,
                `purchase_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2. Ensure project_id allows NULL
        $res = $this->db->query("SHOW COLUMNS FROM `project_extra_assets` LIKE 'project_id'");
        if ($res && $col = $res->fetch_assoc()) {
            if (isset($col['Null']) && strtoupper($col['Null']) === 'NO') {
                $this->db->query("ALTER TABLE `project_extra_assets` MODIFY COLUMN `project_id` INT NULL DEFAULT NULL");
            }
        }

        
        // 3. Ensure required extra columns exist
        $colsToAdd = [
            'department_id'          => "INT NULL DEFAULT NULL AFTER `project_id`",
            'office_branch_id'       => "INT NULL DEFAULT NULL AFTER `department_id`",
            'asset_location'         => "VARCHAR(255) NULL DEFAULT 'Head Office' AFTER `office_branch_id`",
            'assigned_to'            => "INT NULL DEFAULT NULL AFTER `asset_location`",
            'item_category_id'       => "INT NULL DEFAULT 1 AFTER `assigned_to`",
            'request_type'           => "ENUM('Purchase', 'Servicing') DEFAULT 'Purchase' AFTER `item_category_id`",
            'account_id'             => "INT NULL DEFAULT NULL AFTER `request_type`",
            'reporting_user_id'      => "INT NULL DEFAULT NULL AFTER `requested_by`",
            'vendor_id'              => "INT NULL DEFAULT NULL AFTER `requested_by`",
            'vendor_name'            => "VARCHAR(255) NULL DEFAULT NULL AFTER `vendor_id`",
            'dept_head_status'       => "ENUM('pending','approved','rejected','not_required') DEFAULT 'not_required' AFTER `attachment`",
            'dept_head_by'           => "INT NULL DEFAULT NULL AFTER `dept_head_status`",
            'dept_head_at'           => "DATETIME NULL DEFAULT NULL AFTER `dept_head_by`",
            'dept_head_note'         => "TEXT NULL DEFAULT NULL AFTER `dept_head_at`",
            'it_head_status'         => "ENUM('pending','approved','rejected','not_required') DEFAULT 'not_required' AFTER `dept_head_note`",
            'it_head_by'             => "INT NULL DEFAULT NULL AFTER `it_head_status`",
            'it_head_at'             => "DATETIME NULL DEFAULT NULL AFTER `it_head_by`",
            'it_head_note'           => "TEXT NULL DEFAULT NULL AFTER `it_head_at`",
            'operations_status'      => "ENUM('pending','approved','rejected') DEFAULT 'pending' AFTER `it_head_note`",
            'operations_by'          => "INT DEFAULT NULL AFTER `operations_status`",
            'operations_at'          => "TIMESTAMP NULL DEFAULT NULL AFTER `operations_by`",
            'operations_note'        => "TEXT DEFAULT NULL AFTER `operations_at`",
            'purchase_head_status'   => "ENUM('pending','approved','rejected') DEFAULT 'pending' AFTER `operations_note`",
            'purchase_head_by'       => "INT DEFAULT NULL AFTER `purchase_head_status`",
            'purchase_head_at'       => "TIMESTAMP NULL DEFAULT NULL AFTER `purchase_head_by`",
            'purchase_head_note'     => "TEXT DEFAULT NULL AFTER `purchase_head_at`",
            'purchase_head_document' => "VARCHAR(255) NULL DEFAULT NULL AFTER `purchase_head_note`",
            'paid_amount'            => "DECIMAL(15,2) NULL DEFAULT NULL AFTER `payment_method`",
            'payment_attachment'     => "VARCHAR(255) NULL DEFAULT NULL AFTER `paid_amount`",
            'purchase_document'      => "VARCHAR(255) NULL DEFAULT NULL AFTER `overall_status`",
            'purchase_note'          => "TEXT NULL DEFAULT NULL AFTER `purchase_document`",
            'purchase_date'          => "DATE NULL DEFAULT NULL AFTER `purchase_note`",
            'return_amount'          => "DECIMAL(15,2) NULL DEFAULT 0.00 AFTER `purchase_date`",
            'return_note'            => "TEXT NULL DEFAULT NULL AFTER `return_amount`",
            'purchase_confirmed_by'  => "INT NULL DEFAULT NULL AFTER `return_note`",
            'purchase_confirmed_at'  => "TIMESTAMP NULL DEFAULT NULL AFTER `purchase_confirmed_by`"
        ];

        foreach ($colsToAdd as $colName => $colDef) {
            $check = $this->db->query("SHOW COLUMNS FROM `project_extra_assets` LIKE '{$colName}'");
            if ($check && $check->num_rows == 0) {
                if (!@$this->db->query("ALTER TABLE `project_extra_assets` ADD COLUMN `{$colName}` {$colDef}")) {
                    $cleanDef = preg_replace('/\s+AFTER\s+`?\w+`?$/i', '', $colDef);
                    @$this->db->query("ALTER TABLE `project_extra_assets` ADD COLUMN `{$colName}` {$cleanDef}");
                }
            }
        }
    }

    /**
     * Get all extra purchase requests with filters
     */
    public function getExtraPurchases($filters = [])
    {
        $this->ensureExtraPurchasesTableSchema();

        $sql = "SELECT pea.*, 
                       p.name AS project_name,
                       d.name AS department_name,
                       ob.name AS branch_name,
                       COALESCE(ob.name, pea.asset_location, NULLIF(e_req.workplace, ''), 'Head Office') AS workplace_name,
                       COALESCE(NULLIF(u_req.full_name, ''), u_req.email, 'System Admin') AS requested_by_name,
                       u_dh.full_name AS dept_head_by_name,
                       u_it.full_name AS it_head_by_name,
                       u_rb.full_name AS reporting_boss_name,
                       u_op.full_name AS operations_by_name,
                       u_ph.full_name AS purchase_head_by_name,
                       u_qc.full_name AS qc_audit_by_name,
                       u_mgmt.full_name AS mgmt_by_name,
                       u_paid.full_name AS paid_by_name,
                       u_purchased.full_name AS purchased_by_name,
                       u_assign.full_name AS assigned_to_name,
                       cat.name AS category_name,
                    COALESCE(NULLIF(pea.vendor_name, ''), v.name) AS vendor_name,
                    pea.vendor_id,
  
  
                       ar.id AS approval_request_id,
                       ar.op_head_status,
                       ar.op_head_by,
                       ar.op_head_at,
                       ar.op_head_note,
                       u_ar_op.full_name AS op_head_by_name,
                       ar.accounts_head_status,
                       ar.accounts_head_by,
                       ar.accounts_head_at,
                       ar.accounts_head_note,
                       u_ah.full_name AS accounts_head_by_name
                FROM project_extra_assets pea
                LEFT JOIN office_branches ob ON ob.id = pea.office_branch_id
                LEFT JOIN office_procurement_categories cat ON cat.id = pea.item_category_id
                LEFT JOIN vendors v ON v.id = pea.vendor_id
                LEFT JOIN projects p ON p.id = pea.project_id
                LEFT JOIN users u_req ON u_req.id = pea.requested_by
                LEFT JOIN employees e_req ON e_req.user_id = u_req.id
                LEFT JOIN departments d ON d.id = e_req.department_id
                LEFT JOIN users u_dh ON u_dh.id = pea.dept_head_by
                LEFT JOIN users u_it ON u_it.id = pea.it_head_by
                LEFT JOIN users u_rb ON u_rb.id = pea.reporting_user_id
                LEFT JOIN users u_op ON u_op.id = pea.operations_by
                LEFT JOIN users u_ph ON u_ph.id = pea.purchase_head_by
                LEFT JOIN users u_qc ON u_qc.id = pea.qc_audit_by
                LEFT JOIN users u_mgmt ON u_mgmt.id = pea.mgmt_by
                LEFT JOIN users u_paid ON u_paid.id = pea.paid_by
                LEFT JOIN users u_purchased ON u_purchased.id = pea.purchase_confirmed_by
                LEFT JOIN users u_assign ON u_assign.id = pea.assigned_to
                LEFT JOIN approval_requests ar ON (ar.source_table IN ('project_extra_assets', 'purchase_requests') AND ar.source_id = pea.id)
                LEFT JOIN users u_ar_op ON u_ar_op.id = ar.op_head_by
                LEFT JOIN users u_ah ON u_ah.id = ar.accounts_head_by
                WHERE 1=1";

        $params = [];
        $types = '';

        if (!empty($filters['department_id'])) {
            $sql .= " AND e_req.department_id = ?";
            $params[] = (int)$filters['department_id'];
            $types .= 'i';
        }

        if (!empty($filters['office_branch_id'])) {
            $sql .= " AND pea.office_branch_id = ?";
            $params[] = (int)$filters['office_branch_id'];
            $types .= 'i';
        } elseif (!empty($filters['workplace'])) {
            if (is_numeric($filters['workplace'])) {
                $sql .= " AND pea.office_branch_id = ?";
                $params[] = (int)$filters['workplace'];
                $types .= 'i';
            } else {
                $sql .= " AND (ob.name = ? OR pea.asset_location = ? OR e_req.workplace = ?)";
                $params[] = $filters['workplace'];
                $params[] = $filters['workplace'];
                $params[] = $filters['workplace'];
                $types .= 'sss';
            }
        }

        if (!empty($filters['project_id'])) {
            $sql .= " AND pea.project_id = ?";
            $params[] = (int)$filters['project_id'];
            $types .= 'i';
        }

        if (!empty($filters['overall_status'])) {
            $sql .= " AND pea.overall_status = ?";
            $params[] = $filters['overall_status'];
            $types .= 's';
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND pea.requested_by = ?";
            $params[] = (int)$filters['created_by'];
            $types .= 'i';
        }

        if (!empty($filters['restrict_user'])) {
            $uId = (int)($filters['user_id'] ?? 0);
            $sql .= " AND (pea.requested_by = {$uId} OR pea.reporting_user_id = {$uId} OR e_req.reporting_user_id = {$uId})";
        }

        if (!empty($filters['tab'])) {
            $tab = $filters['tab'];
            $userId = (int)($filters['user_id'] ?? 0);
            $isAdmin = !empty($filters['is_admin']);
            $isGlobal = !empty($filters['is_global_viewer']);

            if ($tab === 'pending_approval') {
                if ($isAdmin) {
                    $sql .= " AND (pea.overall_status IN ('pending', 'in_review') OR pea.payment_status = 'unpaid')";
                } else {
                    $conds = [];
                    // Reporting Boss (Dept Head approval step)
                    $conds[] = "(pea.reporting_user_id = {$userId} AND pea.dept_head_status = 'pending')";

                    if ($userId === 242) {
                        $conds[] = "(pea.dept_head_status IN ('approved', 'not_required') AND pea.it_head_status = 'pending')";
                    }

                    $roleId = (int)($filters['role_id'] ?? 0);
                    $isOpHead = in_array($roleId, [20, 32], true) || !empty($filters['is_op_head']);

                    if (!empty($filters['is_operations']) || $isOpHead) {
                        $conds[] = "(pea.dept_head_status IN ('approved', 'not_required') AND pea.it_head_status IN ('approved', 'not_required') AND pea.operations_status = 'pending')";
                    }
                    if (!empty($filters['is_purchase_head'])) {
                        $conds[] = "(pea.operations_status = 'approved' AND pea.purchase_head_status = 'pending')";
                    }
                    if (!empty($filters['is_qc_audit'])) {
                        $conds[] = "(pea.purchase_head_status = 'approved' AND pea.qc_audit_status = 'pending')";
                    }
                    if (!empty($filters['is_management'])) {
                        $conds[] = "(pea.qc_audit_status = 'approved' AND pea.mgmt_status = 'pending' AND (pea.total_price <= 10000.01 OR (ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved')))";
                    }
                    if ($isOpHead) {
                        $conds[] = "(pea.qc_audit_status = 'approved' AND pea.mgmt_status = 'pending' AND (ar.op_head_status IS NULL OR ar.op_head_status != 'approved'))";
                    }
                    if (!empty($filters['is_accounts'])) {
                        $conds[] = "((pea.qc_audit_status = 'approved' AND ar.op_head_status = 'approved' AND (ar.accounts_head_status IS NULL OR ar.accounts_head_status != 'approved') AND pea.payment_status = 'unpaid') OR (pea.mgmt_status = 'approved' AND pea.payment_status = 'unpaid'))";
                    }

                    if (!empty($conds)) {
                        $sql .= " AND (" . implode(" OR ", $conds) . ")";
                    } else {
                        $sql .= " AND 1=0";
                    }
                }
            } elseif ($tab === 'all_requests' && !$isAdmin && !$isGlobal && empty($filters['restrict_user'])) {
                $conds = [
                    "pea.requested_by = " . $userId,
                    "pea.dept_head_by = " . $userId,
                    "pea.it_head_by = " . $userId,
                    "pea.reporting_user_id = " . $userId,
                    "pea.operations_by = " . $userId,
                    "pea.purchase_head_by = " . $userId,
                    "pea.qc_audit_by = " . $userId,
                    "pea.mgmt_by = " . $userId,
                    "pea.paid_by = " . $userId,
                    "e_req.reporting_user_id = " . $userId
                ];
                $sql .= " AND (" . implode(" OR ", $conds) . ")";
            }
        }

        $sql .= " ORDER BY pea.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed in getExtraPurchases: " . $this->db->error);
            return [];
        }
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Find single extra purchase request
     */
    public function findExtraPurchase($id)
    {
        $this->ensureExtraPurchasesTableSchema();

        $stmt = $this->db->prepare(
            "SELECT pea.*, 
                    p.name AS project_name,
                    d.name AS department_name,
                    ob.name AS branch_name,
                    COALESCE(ob.name, pea.asset_location, NULLIF(e_req.workplace, ''), 'Head Office') AS workplace_name,
                    e_req.reporting_user_id AS requester_reporting_boss_id,
                    COALESCE(NULLIF(u_req.full_name, ''), u_req.email, 'System Admin') AS requested_by_name,
                    u_dh.full_name AS dept_head_by_name,
                    u_it.full_name AS it_head_by_name,
                    u_rb.full_name AS reporting_boss_name,
                    u_op.full_name AS operations_by_name,
                    u_ph.full_name AS purchase_head_by_name,
                    u_qc.full_name AS qc_audit_by_name,
                    u_mgmt.full_name AS mgmt_by_name,
                    u_paid.full_name AS paid_by_name,
                    u_purchased.full_name AS purchased_by_name,
                    u_assign.full_name AS assigned_to_name,
                    cat.name AS category_name,
                    COALESCE(NULLIF(pea.vendor_name, ''), v.name) AS vendor_name,
                    pea.vendor_id,
                    ar.id AS approval_request_id,
                    ar.op_head_status,
                    ar.op_head_by,
                    ar.op_head_at,
                    ar.op_head_note,
                    u_ar_op.full_name AS op_head_by_name,
                    ar.accounts_head_status,
                    ar.accounts_head_by,
                    ar.accounts_head_at,
                    ar.accounts_head_note,
                    u_ah.full_name AS accounts_head_by_name
             FROM project_extra_assets pea
             LEFT JOIN office_branches ob ON ob.id = pea.office_branch_id
             LEFT JOIN office_procurement_categories cat ON cat.id = pea.item_category_id
             LEFT JOIN vendors v ON v.id = pea.vendor_id
             LEFT JOIN projects p ON p.id = pea.project_id
             LEFT JOIN users u_req ON u_req.id = pea.requested_by
             LEFT JOIN employees e_req ON e_req.user_id = u_req.id
             LEFT JOIN departments d ON d.id = e_req.department_id
             LEFT JOIN users u_dh ON u_dh.id = pea.dept_head_by
             LEFT JOIN users u_it ON u_it.id = pea.it_head_by
             LEFT JOIN users u_rb ON u_rb.id = pea.reporting_user_id
             LEFT JOIN users u_op ON u_op.id = pea.operations_by
             LEFT JOIN users u_ph ON u_ph.id = pea.purchase_head_by
             LEFT JOIN users u_qc ON u_qc.id = pea.qc_audit_by
             LEFT JOIN users u_mgmt ON u_mgmt.id = pea.mgmt_by
             LEFT JOIN users u_paid ON u_paid.id = pea.paid_by
             LEFT JOIN users u_purchased ON u_purchased.id = pea.purchase_confirmed_by
             LEFT JOIN users u_assign ON u_assign.id = pea.assigned_to
             LEFT JOIN approval_requests ar ON (ar.source_table IN ('project_extra_assets', 'purchase_requests') AND ar.source_id = pea.id)
             LEFT JOIN users u_ar_op ON u_ar_op.id = ar.op_head_by
             LEFT JOIN users u_ah ON u_ah.id = ar.accounts_head_by
             WHERE pea.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create extra purchase request
     */
    public function createExtraPurchase($data)
    {
        $this->ensureExtraPurchasesTableSchema();

        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $departmentId = !empty($data['department_id']) ? (int)$data['department_id'] : null;
        $officeBranchId = !empty($data['office_branch_id']) ? (int)$data['office_branch_id'] : null;

        $assetLocation = !empty($data['asset_location']) ? trim($data['asset_location']) : null;
        if ($officeBranchId && empty($assetLocation)) {
            $bRes = $this->db->query("SELECT name FROM office_branches WHERE id = {$officeBranchId} LIMIT 1");
            if ($bRes && $bRow = $bRes->fetch_assoc()) {
                $assetLocation = $bRow['name'];
            }
        } elseif (!$officeBranchId && !empty($assetLocation)) {
            $escLoc = $this->db->real_escape_string($assetLocation);
            $bRes = $this->db->query("SELECT id FROM office_branches WHERE name = '{$escLoc}' LIMIT 1");
            if ($bRes && $bRow = $bRes->fetch_assoc()) {
                $officeBranchId = (int)$bRow['id'];
            }
        }
        if (empty($assetLocation)) {
            $assetLocation = 'Head Office';
        }

        $assignedTo = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;
        $itemCategoryId = !empty($data['item_category_id']) ? (int)$data['item_category_id'] : 1;
        $itemName = $data['item_name'] ?? 'Procurement Item';
        $quantity = (int)($data['quantity'] ?? 1);
        $unitPrice = (float)($data['unit_price'] ?? 0);
        $totalPrice = $quantity * $unitPrice;
        $justification = $data['justification'] ?? '';
        $attachment = $data['attachment'] ?? null;
        $requestedBy = (int)($data['requested_by'] ?? 1);

        $reportingUserId = null;
        $deptHeadStatus = 'not_required';

        // Check if requester has a reporting boss in employees table and is not already a dept head
        $stmtEmp = $this->db->prepare("
            SELECT e.reporting_user_id, e.is_dept_head 
            FROM employees e 
            JOIN users u ON (e.user_id = u.id OR e.id = u.employee_id) 
            WHERE u.id = ? AND e.deleted_at IS NULL 
            LIMIT 1
        ");
        if ($stmtEmp) {
            $stmtEmp->bind_param('i', $requestedBy);
            $stmtEmp->execute();
            $empRes = $stmtEmp->get_result();
            if ($empRes && $empRow = $empRes->fetch_assoc()) {
                if (!empty($empRow['reporting_user_id']) && empty($empRow['is_dept_head']) && (int)$empRow['reporting_user_id'] !== $requestedBy) {
                    $reportingUserId = (int)$empRow['reporting_user_id'];
                    $deptHeadStatus = 'pending';
                }
            }
            $stmtEmp->close();
        }

        $itHeadStatus = 'not_required';
        if ($itemCategoryId) {
            $catStmt = $this->db->prepare("SELECT name FROM office_procurement_categories WHERE id = ?");
            if ($catStmt) {
                $catStmt->bind_param('i', $itemCategoryId);
                $catStmt->execute();
                $catRes = $catStmt->get_result();
                if ($catRes && $catRow = $catRes->fetch_assoc()) {
                    if (strpos(strtolower($catRow['name']), 'it security') !== false || strpos(strtolower($catRow['name']), 'computer') !== false) {
                        $itHeadStatus = ((int)$requestedBy === 242) ? 'not_required' : 'pending';
                    }
                }
                $catStmt->close();
            }
        }

        $requestType = $data['request_type'] ?? 'Purchase';

        $accountId = !empty($data['account_id']) ? (int)$data['account_id'] : null;

        $vendorId = !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null;
        $vendorName = !empty($data['vendor_name']) ? trim($data['vendor_name']) : null;
        if ($vendorId && empty($vendorName)) {
            $vStmt = $this->db->prepare("SELECT name FROM vendors WHERE id = ?");
            if ($vStmt) {
                $vStmt->bind_param('i', $vendorId);
                $vStmt->execute();
                $vRes = $vStmt->get_result();
                if ($vRes && $vRow = $vRes->fetch_assoc()) {
                    $vendorName = $vRow['name'];
                }
                $vStmt->close();
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO project_extra_assets 
            (project_id, department_id, office_branch_id, asset_location, assigned_to, item_category_id, request_type, item_name, quantity, unit_price, total_price, justification, attachment, requested_by, reporting_user_id, dept_head_status, it_head_status, account_id, vendor_id, vendor_name, operations_status, purchase_head_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')"
        );

        if (!$stmt) {
            error_log("Prepare failed in createExtraPurchase: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            'iiisiissiddssiissiis',
            $projectId,
            $departmentId,
            $officeBranchId,
            $assetLocation,
            $assignedTo,
            $itemCategoryId,
            $requestType,
            $itemName,
            $quantity,
            $unitPrice,
            $totalPrice,
            $justification,
            $attachment,
            $requestedBy,
            $reportingUserId,
            $deptHeadStatus,
            $itHeadStatus,
            $accountId,
            $vendorId,
            $vendorName
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        } else {
            error_log("Execute failed in createExtraPurchase: " . $stmt->error);
        }
        return false;
    }

    /**
     * Approve at a specific step
     * @param int $id Request ID
     * @param string $step 'dept_head' | 'operations' | 'purchase_head' | 'qc_audit' | 'mgmt'
     * @param string $status 'approved' | 'rejected'
     * @param int $userId Approver user ID
     * @param string $note Approval/rejection note
     */
    public function approveStep($id, $step, $status, $userId, $note = '', $document = null)
    {
        $validSteps = ['dept_head', 'it_head', 'operations', 'purchase_head', 'qc_audit', 'mgmt'];
        if (!in_array($step, $validSteps)) return false;

        $statusCol = $step . '_status';
        $byCol = $step . '_by';
        $atCol = $step . '_at';
        $noteCol = $step . '_note';

        if ($step === 'purchase_head' && $document !== null) {
            $sql = "UPDATE project_extra_assets SET 
                        `{$statusCol}` = ?, `{$byCol}` = ?, `{$atCol}` = NOW(), `{$noteCol}` = ?, `purchase_head_document` = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sissi', $status, $userId, $note, $document, $id);
        } else {
            $sql = "UPDATE project_extra_assets SET 
                        `{$statusCol}` = ?, `{$byCol}` = ?, `{$atCol}` = NOW(), `{$noteCol}` = ?
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sisi', $status, $userId, $note, $id);
        }
        
        $result = $stmt->execute();

        if ($result) {
            // Update overall_status based on current state
            $this->updateOverallStatus($id, $step, $status);
        }

        return $result;
    }

    /**
     * Process payment (Accounts step) — only marks as paid, does NOT register assets yet
     */
    public function processPayment($id, $userId, $paymentMethod, $paidAmount, $attachment = null)
    {
        $stmt = $this->db->prepare(
            "UPDATE project_extra_assets SET 
                payment_status = 'paid', paid_by = ?, paid_at = NOW(), 
                payment_method = ?, paid_amount = ?, payment_attachment = ?,
                overall_status = 'paid'
            WHERE id = ?"
        );

        $stmt->bind_param('isdsi', $userId, $paymentMethod, $paidAmount, $attachment, $id);
        return $stmt->execute();
    }

    /**
     * Operations confirms purchase received → registers assets into All Assets & auto codes product/asset
     */
    public function confirmPurchased($id, $userId, $document = null, $note = '', $assignedTo = null, $location = null, $returnAmount = 0, $returnNote = '', $purchaseDate = null)
    {
        $this->ensureOfficeBranchesSchema();
        $req = $this->findExtraPurchase($id);
        if (!$req) return false;

        $finalPurchaseDate = !empty($purchaseDate) ? $purchaseDate : (!empty($req['purchase_date']) ? $req['purchase_date'] : (!empty($req['paid_at']) ? date('Y-m-d', strtotime($req['paid_at'])) : date('Y-m-d')));
        $finalDoc = $document !== null ? $document : ($req['purchase_document'] ?? null);

        // Mark as purchased with document, note, and purchase date
        $stmt = $this->db->prepare(
            "UPDATE project_extra_assets SET 
                overall_status = 'purchased', 
                purchase_document = ?, 
                purchase_note = ?,
                purchase_date = ?,
                return_amount = ?,
                return_note = ?,
                purchase_confirmed_by = ?,
                purchase_confirmed_at = NOW()
            WHERE id = ?"
        );
        $stmt->bind_param('sssdsii', $finalDoc, $note, $finalPurchaseDate, $returnAmount, $returnNote, $userId, $id);
        $res = $stmt->execute();

        if ($res) {
            // Re-fetch request data
            $req = $this->findExtraPurchase($id);

            // Determine Assigned To
            $finalAssignedTo = $assignedTo !== null ? (int)$assignedTo : ($req['assigned_to'] ? (int)$req['assigned_to'] : null);

            // Determine Office Branch from employee or request
            $finalBranchId = null;
            if ($finalAssignedTo) {
                $finalBranchId = $this->getEmployeeOfficeBranch($finalAssignedTo);
            }
            if (!$finalBranchId && !empty($req['office_branch_id'])) {
                $finalBranchId = (int)$req['office_branch_id'];
            }
            if (!$finalBranchId) {
                $finalBranchId = 1; // Default to Corporate Gulshan Office
            }

            // Determine Location Text
            $finalLocation = $location !== null && $location !== '' ? $location : (!empty($req['asset_location']) ? $req['asset_location'] : null);
            if (empty($finalLocation) && $finalBranchId) {
                $bStmt = $this->db->prepare("SELECT name FROM office_branches WHERE id = ?");
                if ($bStmt) {
                    $bStmt->bind_param('i', $finalBranchId);
                    $bStmt->execute();
                    $bRow = $bStmt->get_result()->fetch_assoc();
                    if ($bRow) $finalLocation = $bRow['name'];
                }
            }
            if (empty($finalLocation)) {
                $finalLocation = 'Head Office';
            }

            // 1. Auto-register to Product Master (office_asset_products) if it doesn't exist
            $cleanItemName = (!empty($req['item_name']) && trim((string)$req['item_name']) !== '0')
                ? trim($req['item_name'])
                : (!empty($req['justification']) ? trim(mb_substr($req['justification'], 0, 80)) : 'Office Asset');

            // If item_name was stored as 0, update it in project_extra_assets now
            if (empty($req['item_name']) || trim((string)$req['item_name']) === '0') {
                $updName = $this->db->prepare("UPDATE project_extra_assets SET item_name = ? WHERE id = ?");
                $updName->bind_param('si', $cleanItemName, $id);
                $updName->execute();
            }

            $productId = 0;
            $stmtCheck = $this->db->prepare("SELECT id FROM office_asset_products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1");
            $stmtCheck->bind_param('s', $cleanItemName);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();

            if ($resCheck->num_rows > 0) {
                $productId = (int)$resCheck->fetch_assoc()['id'];
            } else {
                $productCode = $this->generateProductCode();
                $itemCatId = !empty($req['item_category_id']) ? (int)$req['item_category_id'] : null;
                $unitPrice = (float)($req['unit_price'] ?? 0);
                
                $stmtProd = $this->db->prepare("INSERT INTO office_asset_products (product_code, name, category_id, unit_price, status, created_by) VALUES (?, ?, ?, ?, 'active', ?)");
                $stmtProd->bind_param('ssidi', $productCode, $cleanItemName, $itemCatId, $unitPrice, $userId);
                if ($stmtProd->execute()) {
                    $productId = (int)$this->db->insert_id;
                }
            }

            // 2. Auto-register each unit into office_assets (Asset Tracker)
            $qty = max(1, (int)($req['quantity'] ?? 1));
            $unitPrice = (float)($req['unit_price'] > 0 ? $req['unit_price'] : ($req['total_price'] / $qty));
            $assetStatus = $finalAssignedTo ? 'in_use' : 'in_store';
            $conditionStatus = 'new';
            $projectId = !empty($req['project_id']) ? (int)$req['project_id'] : null;
            $deptId = !empty($req['department_id']) ? (int)$req['department_id'] : null;

            // Check if assets have already been registered for this PR to prevent duplicate insertion
            $chkAsset = $this->db->prepare("SELECT COUNT(*) AS cnt FROM office_assets WHERE remarks LIKE ?");
            $prRemark = "%PR-" . $id . "%";
            $chkAsset->bind_param('s', $prRemark);
            $chkAsset->execute();
            $existingAssetCnt = (int)($chkAsset->get_result()->fetch_assoc()['cnt'] ?? 0);

            if ($existingAssetCnt < $qty) {
                $needed = $qty - $existingAssetCnt;
                for ($i = 0; $i < $needed; $i++) {
                    $code = $this->generateAssetCode();
                    $assetRemark = "Auto-registered from Office Procurement PR-{$id}";

                    $insertStmt = $this->db->prepare(
                        "INSERT INTO office_assets 
                        (asset_code, product_id, item_name, project_id, office_branch_id, department_id, assigned_to, location, 
                         purchase_date, purchase_price, condition_status, asset_status, remarks, created_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                    );
                    $insertStmt->bind_param(
                        'sisiiiissdsssi',
                        $code,
                        $productId,
                        $cleanItemName,
                        $projectId,
                        $finalBranchId,
                        $deptId,
                        $finalAssignedTo,
                        $finalLocation,
                        $finalPurchaseDate,
                        $unitPrice,
                        $conditionStatus,
                        $assetStatus,
                        $assetRemark,
                        $userId
                    );
                    $insertStmt->execute();
                }
            }

            // 3. Update project_extra_assets with product_id, office_branch_id, and purchase_date
            $updReq = $this->db->prepare("UPDATE project_extra_assets SET product_id = COALESCE(?, product_id), office_branch_id = COALESCE(office_branch_id, ?), purchase_date = COALESCE(?, purchase_date) WHERE id = ?");
            $updReq->bind_param('iisi', $productId, $finalBranchId, $finalPurchaseDate, $id);
            $updReq->execute();

            // 4. Ensure Financial Hub Balance Sheet Journal is capitalized under Non-Current Assets
            $this->ensureFixedAssetJournalEntry($id, $req, $finalPurchaseDate, $userId);
        }

        return $res;
    }

    /**
     * Ensure financial journal reflects Capital/Fixed Asset under Non-Current Assets on Balance Sheet
     */
    public function ensureFixedAssetJournalEntry($id, $req, $purchaseDate, $userId)
    {
        $totalPrice = (float)($req['total_price'] ?? 0);
        if ($totalPrice <= 0) return;

        // Only capitalize purchases (request_type = 'Purchase')
        $requestType = $req['request_type'] ?? 'Purchase';
        if (strtolower($requestType) !== 'purchase') return;

        $fixedAssetAccId = $this->getFixedAssetAccountId($req['item_category_id'] ?? null, $req['item_name'] ?? '');
        if (!$fixedAssetAccId) return;

        // Check if accrual journal exists
        $stmtJ = $this->db->prepare("SELECT id FROM journals WHERE source_module = 'asset_procurement' AND source_ref_id = ? AND entry_type = 'accrual' LIMIT 1");
        $stmtJ->bind_param('i', $id);
        $stmtJ->execute();
        $jRow = $stmtJ->get_result()->fetch_assoc();

        if ($jRow) {
            $journalId = (int)$jRow['id'];
            // Update the debit line in journal_details to point to Fixed Asset account
            $updD = $this->db->prepare("UPDATE journal_details SET account_id = ? WHERE journal_id = ? AND debit > 0 LIMIT 1");
            $updD->bind_param('ii', $fixedAssetAccId, $journalId);
            $updD->execute();

            // Update journal date if purchase date provided
            if ($purchaseDate) {
                $updJ = $this->db->prepare("UPDATE journals SET date = ? WHERE id = ?");
                $updJ->bind_param('si', $purchaseDate, $journalId);
                $updJ->execute();
            }
        } else {
            // Create accrual entry via AutoPostingService
            try {
                require_once dirname(__DIR__) . '/services/AutoPostingService.php';
                $autoPosting = new \AutoPostingService($this->db);
                $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110');
                $procurementOffice = !empty($req['asset_location']) ? $req['asset_location'] : null;

                $autoPosting->postAccrualEntry(
                    'asset_procurement',
                    $id,
                    $fixedAssetAccId,
                    $vendorPayableAccId,
                    $totalPrice,
                    "Office Fixed Asset Procurement (PR-{$id}): " . $req['item_name'] . " (Qty: {$req['quantity']})",
                    $req['project_id'] ?? null,
                    'employee',
                    $req['assigned_to'] ?? $req['requested_by'] ?? 1,
                    $userId,
                    $procurementOffice
                );
            } catch (\Exception $e) {
                error_log("Failed creating fixed asset accrual entry for PR-{$id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Retroactively sync any paid or purchased procurement request into the Asset Tracker & Balance Sheet
     */
    public function syncPurchasedToAssetRegister($id, $userId = 1)
    {
        $req = $this->findExtraPurchase($id);
        if (!$req) return false;
        if (!in_array($req['overall_status'], ['paid', 'purchased']) && $req['payment_status'] !== 'paid') return false;

        $doc = $req['purchase_document'] ?? $req['payment_attachment'] ?? null;
        $note = !empty($req['purchase_note']) ? $req['purchase_note'] : 'Confirmed and synced to Asset Tracker';
        $pDate = !empty($req['purchase_date']) ? $req['purchase_date'] : (!empty($req['paid_at']) ? date('Y-m-d', strtotime($req['paid_at'])) : date('Y-m-d'));

        return $this->confirmPurchased($id, $userId, $doc, $note, null, null, 0, '', $pDate);
    }

    /**
     * Delete extra purchase request if allowed (Creator before approval, or Admin)
     */
    public function deleteExtraPurchase($id, $userId, $isAdmin = false)
    {
        $id = (int)$id;
        $req = $this->findExtraPurchase($id);
        if (!$req) return false;

        $isCreator = (int)$req['requested_by'] === (int)$userId;
        $isPendingInitial = in_array($req['dept_head_status'] ?? 'not_required', ['pending', 'not_required']) && ($req['operations_status'] === 'pending' && $req['purchase_head_status'] === 'pending' && $req['qc_audit_status'] === 'pending' && $req['mgmt_status'] === 'pending');

        if ($isAdmin || ($isCreator && $isPendingInitial)) {
            $stmt = $this->db->prepare("DELETE FROM project_extra_assets WHERE id = ?");
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        }

        return false;
    }

    /**
     * Update overall status based on approval chain progress
     */
    private function updateOverallStatus($id, $step, $stepStatus)
    {
        if ($stepStatus === 'rejected') {
            // Any rejection → overall rejected
            $this->db->query("UPDATE project_extra_assets SET overall_status = 'rejected' WHERE id = " . (int)$id);
            return;
        }

        // Get current state
        $row = $this->findExtraPurchase($id);
        if (!$row) return;

        if (in_array($step, ['dept_head', 'it_head', 'operations', 'purchase_head', 'qc_audit']) && $stepStatus === 'approved') {
            $this->db->query("UPDATE project_extra_assets SET overall_status = 'in_review' WHERE id = " . (int)$id);
        } elseif ($step === 'mgmt' && $stepStatus === 'approved') {
            $this->db->query("UPDATE project_extra_assets SET overall_status = 'approved' WHERE id = " . (int)$id);
        }
    }

    // =========================================================================
    // DASHBOARD STATS
    // =========================================================================

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        $stats = [];

        // Total products
        $r = $this->db->query("SELECT COUNT(*) AS c FROM office_asset_products WHERE status = 'active'");
        $stats['total_products'] = $r ? $r->fetch_assoc()['c'] : 0;

        // Total assets
        $r = $this->db->query("SELECT COUNT(*) AS c FROM office_assets");
        $stats['total_assets'] = $r ? $r->fetch_assoc()['c'] : 0;

        // Assets by status
        $r = $this->db->query("SELECT asset_status, COUNT(*) AS c FROM office_assets GROUP BY asset_status");
        $stats['asset_by_status'] = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

        // Assets needing repair
        $r = $this->db->query("SELECT COUNT(*) AS c FROM office_assets WHERE condition_status IN ('need_repair','damaged')");
        $stats['needs_attention'] = $r ? $r->fetch_assoc()['c'] : 0;

        // Pending extra purchase requests
        $r = $this->db->query("SELECT COUNT(*) AS c FROM project_extra_assets WHERE overall_status IN ('pending','in_review')");
        $stats['pending_purchases'] = $r ? $r->fetch_assoc()['c'] : 0;

        // Total asset value
        $r = $this->db->query("SELECT COALESCE(SUM(purchase_price), 0) AS total FROM office_assets WHERE asset_status != 'disposed'");
        $stats['total_value'] = $r ? $r->fetch_assoc()['total'] : 0;

        return $stats;
    }
    // =========================================================================
    // OFFICE PROCUREMENT CATEGORIES (Phase 2 Addition)
    // =========================================================================

    public function getProcurementCategories()
    {
        $stmt = $this->db->query("SELECT * FROM office_procurement_categories ORDER BY name ASC");
        return $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function addProcurementCategory($name)
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `office_procurement_categories` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `status` enum('active','inactive') DEFAULT 'active',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $stmt = $this->db->prepare("INSERT INTO office_procurement_categories (name) VALUES (?)");
        if (!$stmt) return false;
        $stmt->bind_param('s', $name);
        return $stmt->execute();
    }

    public function updateProcurementCategory($id, $name)
    {
        $stmt = $this->db->prepare("UPDATE office_procurement_categories SET name = ? WHERE id = ?");
        $stmt->bind_param('si', $name, $id);
        return $stmt->execute();
    }

    public function deleteProcurementCategory($id)
    {
        $stmt = $this->db->prepare("DELETE FROM office_procurement_categories WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
