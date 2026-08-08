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
        $branchId = !empty($data['office_branch_id']) ? (int)$data['office_branch_id'] : null;
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $deptId = !empty($data['department_id']) ? (int)$data['department_id'] : null;
        $assignedTo = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;

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

        $stmt->bind_param(
            'sisiiiisdssssssi',
            $assetCode,
            $productId,
            $itemName,
            $projectId,
            $branchId,
            $deptId,
            $assignedTo,
            $location,
            $data['purchase_date'],
            $data['purchase_price'],
            $data['warranty_expiry'],
            $data['serial_no'],
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
        $branchId = array_key_exists('office_branch_id', $data) ? (!empty($data['office_branch_id']) ? (int)$data['office_branch_id'] : null) : $old['office_branch_id'];
        $deptId = array_key_exists('department_id', $data) ? (!empty($data['department_id']) ? (int)$data['department_id'] : null) : $old['department_id'];
        $assignedTo = array_key_exists('assigned_to', $data) ? (!empty($data['assigned_to']) ? (int)$data['assigned_to'] : null) : $old['assigned_to'];
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

    // =========================================================================
    // ASSET MOVEMENT LOGS
    // =========================================================================

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

        // 2. Ensure project_id allows NULL
        $res = $this->db->query("SHOW COLUMNS FROM `project_extra_assets` LIKE 'project_id'");
        if ($res && $col = $res->fetch_assoc()) {
            if (isset($col['Null']) && strtoupper($col['Null']) === 'NO') {
                $this->db->query("ALTER TABLE `project_extra_assets` MODIFY COLUMN `project_id` INT NULL DEFAULT NULL");
            }
        }

        
        // 3. Ensure required extra columns exist
        $colsToAdd = [
            'department_id'     => "INT NULL DEFAULT NULL AFTER `project_id`",
            'asset_location'    => "VARCHAR(255) NULL DEFAULT 'Head Office' AFTER `department_id`",
            'assigned_to'       => "INT NULL DEFAULT NULL AFTER `asset_location`",
            'item_category_id'  => "INT NULL DEFAULT 1 AFTER `assigned_to`",
            'operations_status' => "ENUM('pending','approved','rejected') DEFAULT 'approved' AFTER `attachment`",
            'operations_by'     => "INT DEFAULT NULL AFTER `operations_status`",
            'operations_at'     => "TIMESTAMP NULL DEFAULT NULL AFTER `operations_by`",
            'operations_note'   => "TEXT DEFAULT NULL AFTER `operations_at`",
        ];

        foreach ($colsToAdd as $colName => $colDef) {
            $check = $this->db->query("SHOW COLUMNS FROM `project_extra_assets` LIKE '{$colName}'");
            if ($check && $check->num_rows == 0) {
                $this->db->query("ALTER TABLE `project_extra_assets` ADD COLUMN `{$colName}` {$colDef}");
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
                       COALESCE(NULLIF(e_req.workplace, ''), pea.asset_location) AS workplace_name,
                       COALESCE(NULLIF(u_req.full_name, ''), u_req.email, 'System Admin') AS requested_by_name,
                       u_op.full_name AS operations_by_name,
                       u_ph.full_name AS purchase_head_by_name,
                       u_qc.full_name AS qc_audit_by_name,
                       u_mgmt.full_name AS mgmt_by_name,
                       u_paid.full_name AS paid_by_name,
                       u_purchased.full_name AS purchased_by_name,
                       u_assign.full_name AS assigned_to_name,
                       cat.name AS category_name
                FROM project_extra_assets pea
                LEFT JOIN office_procurement_categories cat ON cat.id = pea.item_category_id
                LEFT JOIN projects p ON p.id = pea.project_id
                LEFT JOIN users u_req ON u_req.id = pea.requested_by
                LEFT JOIN employees e_req ON e_req.user_id = u_req.id
                LEFT JOIN departments d ON d.id = e_req.department_id
                LEFT JOIN users u_op ON u_op.id = pea.operations_by
                LEFT JOIN users u_ph ON u_ph.id = pea.purchase_head_by
                LEFT JOIN users u_qc ON u_qc.id = pea.qc_audit_by
                LEFT JOIN users u_mgmt ON u_mgmt.id = pea.mgmt_by
                LEFT JOIN users u_paid ON u_paid.id = pea.paid_by
                LEFT JOIN users u_purchased ON u_purchased.id = pea.purchase_confirmed_by
                LEFT JOIN users u_assign ON u_assign.id = pea.assigned_to
                WHERE 1=1";

        $params = [];
        $types = '';

        if (!empty($filters['department_id'])) {
            $sql .= " AND e_req.department_id = ?";
            $params[] = (int)$filters['department_id'];
            $types .= 'i';
        }

        if (!empty($filters['workplace'])) {
            $sql .= " AND (e_req.workplace = ? OR pea.asset_location = ?)";
            $params[] = $filters['workplace'];
            $params[] = $filters['workplace'];
            $types .= 'ss';
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
            $sql .= " AND (pea.requested_by = {$uId} OR e_req.reporting_user_id = {$uId})";
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
                    if (!empty($filters['is_operations'])) {
                        $conds[] = "pea.operations_status = 'pending'";
                    }
                    if (!empty($filters['is_purchase_head'])) {
                        $conds[] = "(pea.operations_status = 'approved' AND pea.purchase_head_status = 'pending')";
                    }
                    if (!empty($filters['is_qc_audit'])) {
                        $conds[] = "(pea.purchase_head_status = 'approved' AND pea.qc_audit_status = 'pending')";
                    }
                    if (!empty($filters['is_management'])) {
                        $conds[] = "(pea.qc_audit_status = 'approved' AND pea.mgmt_status = 'pending')";
                    }
                    if (!empty($filters['is_accounts'])) {
                        $conds[] = "(pea.mgmt_status = 'approved' AND pea.payment_status = 'unpaid')";
                    }
                    // Department Head: pending operations step for reporting subordinates
                    $conds[] = "(e_req.reporting_user_id = {$userId} AND pea.operations_status = 'pending')";

                    if (!empty($conds)) {
                        $sql .= " AND (" . implode(" OR ", $conds) . ")";
                    } else {
                        $sql .= " AND 1=0";
                    }
                }
            } elseif ($tab === 'all_requests' && !$isAdmin && !$isGlobal && empty($filters['restrict_user'])) {
                $conds = [
                    "pea.requested_by = " . $userId,
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
                    e_req.reporting_user_id AS requester_reporting_boss_id,
                    u_req.full_name AS requested_by_name,
                    u_op.full_name AS operations_by_name,
                    u_purchased.full_name AS purchased_by_name,
                    u_assign.full_name AS assigned_to_name,
                    cat.name AS category_name
             FROM project_extra_assets pea
             LEFT JOIN office_procurement_categories cat ON cat.id = pea.item_category_id
             LEFT JOIN projects p ON p.id = pea.project_id
             LEFT JOIN users u_req ON u_req.id = pea.requested_by
             LEFT JOIN employees e_req ON e_req.user_id = u_req.id
             LEFT JOIN users u_op ON u_op.id = pea.operations_by
             LEFT JOIN users u_purchased ON u_purchased.id = pea.purchase_confirmed_by
             LEFT JOIN users u_assign ON u_assign.id = pea.assigned_to
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
        $assetLocation = !empty($data['asset_location']) ? $data['asset_location'] : 'Head Office';
        $assignedTo = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;
        $itemCategoryId = !empty($data['item_category_id']) ? (int)$data['item_category_id'] : 1;
        $itemName = $data['item_name'] ?? 'Procurement Item';
        $quantity = (int)($data['quantity'] ?? 1);
        $unitPrice = (float)($data['unit_price'] ?? 0);
        $totalPrice = $quantity * $unitPrice;
        $justification = $data['justification'] ?? '';
        $attachment = $data['attachment'] ?? null;
        $requestedBy = (int)($data['requested_by'] ?? 1);

        $stmt = $this->db->prepare(
            "INSERT INTO project_extra_assets 
            (project_id, department_id, asset_location, assigned_to, item_category_id, item_name, quantity, unit_price, total_price, justification, attachment, requested_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            error_log("Prepare failed in createExtraPurchase: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            'iisiisiddssi',
            $projectId,
            $departmentId,
            $assetLocation,
            $assignedTo,
            $itemCategoryId,
            $itemName,
            $quantity,
            $unitPrice,
            $totalPrice,
            $justification,
            $attachment,
            $requestedBy
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
     * @param string $step 'purchase_head' | 'qc_audit' | 'mgmt'
     * @param string $status 'approved' | 'rejected'
     * @param int $userId Approver user ID
     * @param string $note Approval/rejection note
     */
    public function approveStep($id, $step, $status, $userId, $note = '', $document = null)
    {
        $validSteps = ['operations', 'purchase_head', 'qc_audit', 'mgmt'];
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
     * Operations confirms purchase received → registers assets into All Assets
     */
    public function confirmPurchased($id, $userId, $document = null, $note = '', $assignedTo = null, $location = null, $returnAmount = 0, $returnNote = '')
    {
        // Mark as purchased with document and note
        $stmt = $this->db->prepare(
            "UPDATE project_extra_assets SET 
                overall_status = 'purchased', 
                purchase_document = ?, 
                purchase_note = ?,
                return_amount = ?,
                return_note = ?,
                purchase_confirmed_by = ?,
                purchase_confirmed_at = NOW()
            WHERE id = ?"
        );
        $stmt->bind_param('ssdsii', $document, $note, $returnAmount, $returnNote, $userId, $id);
        $res = $stmt->execute();

        if ($res) {
            $req = $this->findExtraPurchase($id);
            if ($req) {
                // Auto-register to Product Master if it doesn't exist
                $stmtCheck = $this->db->prepare("SELECT id FROM office_asset_products WHERE name = ? LIMIT 1");
                $stmtCheck->bind_param('s', $req['item_name']);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();
                
                $productId = 0;
                if ($resCheck->num_rows > 0) {
                    $productId = $resCheck->fetch_assoc()['id'];
                } else {
                    // Create new product
                    $productCode = 'PROD-' . time() . '-' . rand(10, 99);
                    $stmtProd = $this->db->prepare("INSERT INTO office_asset_products (product_code, name, category_id, unit_price, status, created_by) VALUES (?, ?, ?, ?, 'active', ?)");
                    $stmtProd->bind_param('ssidi', $productCode, $req['item_name'], $req['item_category_id'], $req['unit_price'], $userId);
                    if ($stmtProd->execute()) {
                        $productId = $this->db->insert_id;
                    }
                }

                $qty = (int)$req['quantity'];
                for ($i = 0; $i < $qty; $i++) {
                    $code = $this->generateAssetCode();
                    
                    $finalAssignedTo = $assignedTo !== null ? $assignedTo : ($req['assigned_to'] ? (int)$req['assigned_to'] : null);
                    $finalLocation = $location !== null && $location !== '' ? $location : $req['asset_location'];
                    $assetStatus = $finalAssignedTo ? 'in_use' : 'in_store';
                    
                    $insertStmt = $this->db->prepare("INSERT INTO office_assets (asset_code, product_id, item_name, project_id, location, assigned_to, purchase_price, purchase_date, condition_status, asset_status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'new', ?, ?, NOW())");
                    $insertStmt->bind_param('sisisisdi', $code, $productId, $req['item_name'], $req['project_id'], $finalLocation, $finalAssignedTo, $req['unit_price'], $assetStatus, $userId);
                    $insertStmt->execute();
                }

                // Update project_extra_assets with product_id
                if ($productId > 0) {
                    $updReq = $this->db->prepare("UPDATE project_extra_assets SET product_id = ? WHERE id = ?");
                    $updReq->bind_param('ii', $productId, $id);
                    $updReq->execute();
                }
            }
        }
        return $res;
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
        $isPendingInitial = ($req['operations_status'] === 'pending' && $req['purchase_head_status'] === 'pending' && $req['qc_audit_status'] === 'pending' && $req['mgmt_status'] === 'pending');

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

        if ($step === 'purchase_head' && $stepStatus === 'approved') {
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
