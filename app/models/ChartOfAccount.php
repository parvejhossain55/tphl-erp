<?php

class ChartOfAccount
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllAccounts()
    {
        $query = "SELECT c.*, p.account_name as parent_name, p.account_code as parent_code 
                  FROM chart_of_accounts c 
                  LEFT JOIN chart_of_accounts p ON c.parent_id = p.id 
                  ORDER BY c.account_code ASC";
        $result = $this->db->query($query);
        $accounts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
        }
        return $accounts;
    }

    public function getActiveAccounts()
    {
        $query = "SELECT c.*, p.account_name as parent_name, p.account_code as parent_code 
                  FROM chart_of_accounts c 
                  LEFT JOIN chart_of_accounts p ON c.parent_id = p.id 
                  WHERE c.status = 'active'
                  ORDER BY c.account_code ASC";
        $result = $this->db->query($query);
        $accounts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
        }
        return $accounts;
    }

    public function getAccountsByLevel($level)
    {
        $stmt = $this->db->prepare("SELECT * FROM chart_of_accounts WHERE level = ? AND status = 'active' ORDER BY account_code ASC");
        $stmt->bind_param("s", $level);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAccountsByRootType($rootType)
    {
        $stmt = $this->db->prepare("SELECT * FROM chart_of_accounts WHERE root_type = ? AND status = 'active' ORDER BY account_code ASC");
        $stmt->bind_param("s", $rootType);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAccountById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM chart_of_accounts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getAccountByCode($code)
    {
        $stmt = $this->db->prepare("SELECT * FROM chart_of_accounts WHERE account_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function checkAccountCodeExists($code, $excludeId = null)
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ? AND id != ?");
            $stmt->bind_param("si", $code, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ?");
            $stmt->bind_param("s", $code);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function generateNextCode($parentId, $level)
    {
        if (empty($parentId)) {
            // Main category auto code (1000, 2000, etc)
            $query = "SELECT MAX(CAST(account_code AS UNSIGNED)) as max_code FROM chart_of_accounts WHERE level = 'main'";
            $res = $this->db->query($query)->fetch_assoc();
            $max = $res['max_code'] ? (int)$res['max_code'] : 0;
            return (string)($max > 0 ? $max + 1000 : 1000);
        }

        $parent = $this->getAccountById($parentId);
        if (!$parent) return '';

        $parentCode = $parent['account_code'];

        if ($level === 'sub') {
            // Sub-category (e.g. 1100, 1200 under 1000)
            $stmt = $this->db->prepare("SELECT account_code FROM chart_of_accounts WHERE parent_id = ? AND level = 'sub' ORDER BY account_code DESC LIMIT 1");
            $stmt->bind_param("i", $parentId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res) {
                $lastCode = (int)$res['account_code'];
                return (string)($lastCode + 100);
            }
            return (string)((int)$parentCode + 100);
        } elseif ($level === 'parent') {
            // Parent / Control Account (e.g. 1110, 1120 under 1100)
            $stmt = $this->db->prepare("SELECT account_code FROM chart_of_accounts WHERE parent_id = ? AND level = 'parent' ORDER BY account_code DESC LIMIT 1");
            $stmt->bind_param("i", $parentId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res) {
                $lastCode = (int)$res['account_code'];
                return (string)($lastCode + 10);
            }
            return (string)((int)$parentCode + 10);
        } else {
            // Ledger / Child Account (e.g. 1110-001, 1110-002 under 1110)
            $stmt = $this->db->prepare("SELECT account_code FROM chart_of_accounts WHERE parent_id = ? AND level = 'ledger' ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("i", $parentId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res && strpos($res['account_code'], '-') !== false) {
                $parts = explode('-', $res['account_code']);
                $seq = (int)end($parts) + 1;
                return $parentCode . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
            }
            return $parentCode . '-001';
        }
    }

    public function createAccount($data)
    {
        $stmt = $this->db->prepare("INSERT INTO chart_of_accounts (parent_id, account_code, account_name, account_type, level, root_type, is_system_account, project_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $level = $data['level'] ?? 'ledger';
        $rootType = $data['root_type'] ?? 'asset';
        $isSystem = isset($data['is_system_account']) ? (int)$data['is_system_account'] : 0;
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $status = $data['status'] ?? 'active';

        $stmt->bind_param("issssssis", $parentId, $data['account_code'], $data['account_name'], $data['account_type'], $level, $rootType, $isSystem, $projectId, $status);
        return $stmt->execute();
    }

    public function updateAccount($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE chart_of_accounts SET parent_id = ?, account_code = ?, account_name = ?, account_type = ?, level = ?, root_type = ?, is_system_account = ?, project_id = ?, status = ? WHERE id = ?");
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $level = $data['level'] ?? 'ledger';
        $rootType = $data['root_type'] ?? 'asset';
        $isSystem = isset($data['is_system_account']) ? (int)$data['is_system_account'] : 0;
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $status = $data['status'] ?? 'active';

        $stmt->bind_param("issssssisi", $parentId, $data['account_code'], $data['account_name'], $data['account_type'], $level, $rootType, $isSystem, $projectId, $status, $id);
        return $stmt->execute();
    }

    public function getTreeAccounts()
    {
        $all = $this->getAllAccounts();
        $tree = [];
        $indexed = [];

        foreach ($all as $acc) {
            $acc['children'] = [];
            $indexed[$acc['id']] = $acc;
        }

        foreach ($indexed as $id => &$acc) {
            if ($acc['parent_id'] && isset($indexed[$acc['parent_id']])) {
                $indexed[$acc['parent_id']]['children'][] = &$acc;
            } else {
                $tree[] = &$acc;
            }
        }
        return $tree;
    }
}

