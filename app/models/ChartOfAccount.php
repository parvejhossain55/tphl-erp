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

    public function getActiveAccountsWithTxnCount()
    {
        $query = "SELECT c.*, p.account_name as parent_name, p.account_code as parent_code, 
                         COUNT(jd.id) as txn_count 
                  FROM chart_of_accounts c 
                  LEFT JOIN chart_of_accounts p ON c.parent_id = p.id 
                  LEFT JOIN journal_details jd ON c.id = jd.account_id
                  WHERE c.status = 'active'
                  GROUP BY c.id
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

    public function deleteAccount($id)
    {
        $stmt = $this->db->prepare("DELETE FROM chart_of_accounts WHERE id = ? AND is_system_account = 0");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getAccountLedger($accountId, $startDate = null, $endDate = null, $projectId = null, $partyId = null, $searchQuery = null)
    {
        $accountId = (int)$accountId;
        $where = "WHERE jd.account_id = $accountId";
        if ($startDate) $where .= " AND j.date >= '" . $this->db->real_escape_string($startDate) . "'";
        if ($endDate) $where .= " AND j.date <= '" . $this->db->real_escape_string($endDate) . "'";
        if ($projectId) $where .= " AND j.project_id = " . (int)$projectId;
        if ($partyId) $where .= " AND jd.party_id = " . (int)$partyId;
        if ($searchQuery) {
            $sq = $this->db->real_escape_string($searchQuery);
            $where .= " AND (j.narration LIKE '%$sq%' OR jd.narration LIKE '%$sq%' OR j.journal_number LIKE '%$sq%')";
        }

        $sql = "SELECT 
                    j.id as journal_id,
                    j.date as transaction_date,
                    j.journal_number as voucher_no,
                    j.voucher_type,
                    j.narration as main_narration,
                    jd.narration as line_narration,
                    jd.debit,
                    jd.credit,
                    jd.party_type,
                    jd.party_id,
                    COALESCE(v.name, v.company_name, u.full_name, l.full_name) as party_name,
                    j.project_id,
                    p.name as project_name,
                    u_created.full_name as created_by_name,
                    (
                        SELECT GROUP_CONCAT(CONCAT(opp_coa.account_code, ' - ', opp_coa.account_name) SEPARATOR ', ')
                        FROM journal_details opp_jd
                        JOIN chart_of_accounts opp_coa ON opp_jd.account_id = opp_coa.id
                        WHERE opp_jd.journal_id = j.id AND opp_jd.account_id != jd.account_id
                    ) as opposite_account
                FROM journal_details jd
                JOIN journals j ON jd.journal_id = j.id
                LEFT JOIN vendors v ON (jd.party_type = 'vendor' AND jd.party_id = v.id)
                LEFT JOIN users u ON (jd.party_id = u.id)
                LEFT JOIN leads l ON (jd.party_type = 'client' AND jd.party_id = l.id)
                LEFT JOIN projects p ON j.project_id = p.id
                LEFT JOIN users u_created ON j.created_by = u_created.id
                $where
                ORDER BY j.date ASC, j.id ASC";

        $res = $this->db->query($sql);
        $transactions = [];
        $totalDebit = 0;
        $totalCredit = 0;

        $accInfo = $this->getAccountById($accountId);
        $rootType = strtolower($accInfo['root_type'] ?? $accInfo['account_type'] ?? 'asset');
        $isNormalDebit = in_array($rootType, ['asset', 'expense']);

        $runningBalance = 0;

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $debit = (float)$row['debit'];
                $credit = (float)$row['credit'];
                $totalDebit += $debit;
                $totalCredit += $credit;

                if ($isNormalDebit) {
                    $runningBalance += ($debit - $credit);
                } else {
                    $runningBalance += ($credit - $debit);
                }

                $row['running_balance'] = $runningBalance;
                $transactions[] = $row;
            }
        }

        return [
            'transactions' => $transactions,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'net_balance' => $runningBalance,
            'is_normal_debit' => $isNormalDebit
        ];
    }

    public function getAccountParties($accountId)
    {
        $accountId = (int)$accountId;
        $sql = "
            SELECT DISTINCT 
                jd.party_type, 
                jd.party_id,
                COALESCE(v.name, v.company_name, u.full_name, l.full_name, CONCAT('Party #', jd.party_id)) as party_name
            FROM journal_details jd
            LEFT JOIN vendors v ON (jd.party_type = 'vendor' AND jd.party_id = v.id)
            LEFT JOIN users u ON (jd.party_id = u.id)
            LEFT JOIN leads l ON (jd.party_type = 'client' AND jd.party_id = l.id)
            WHERE jd.account_id = $accountId AND jd.party_id IS NOT NULL AND jd.party_id != 0
            ORDER BY party_name ASC
        ";
        $res = $this->db->query($sql);
        $parties = [];
        $existingKeys = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $parties[] = $row;
                $existingKeys[$row['party_type'] . '_' . $row['party_id']] = true;
            }
        }

        // Also include active Vendors from vendors table if account is vendor related
        $acc = $this->getAccountById($accountId);
        $code = $acc['account_code'] ?? '';
        $accName = strtolower($acc['account_name'] ?? '');
        if (strpos($code, '2110') === 0 || strpos($code, '5120') === 0 || strpos($accName, 'vendor') !== false) {
            $vRes = $this->db->query("SELECT id, COALESCE(NULLIF(name, ''), company_name) as name FROM vendors ORDER BY name ASC");
            if ($vRes) {
                while ($v = $vRes->fetch_assoc()) {
                    $key = 'vendor_' . $v['id'];
                    if (!isset($existingKeys[$key]) && !empty($v['name'])) {
                        $parties[] = [
                            'party_type' => 'vendor',
                            'party_id'   => $v['id'],
                            'party_name' => $v['name']
                        ];
                    }
                }
            }
        }

        return $parties;
    }

    public function getProjects()
    {
        $res = $this->db->query("SELECT id, name FROM projects ORDER BY name ASC");
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
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

