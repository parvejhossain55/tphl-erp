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
        $query = "SELECT c.*, p.account_name as parent_name 
                  FROM chart_of_accounts c 
                  LEFT JOIN chart_of_accounts p ON c.parent_id = p.id 
                  ORDER BY c.account_type, c.account_code";
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
        $query = "SELECT c.*, p.account_name as parent_name 
                  FROM chart_of_accounts c 
                  LEFT JOIN chart_of_accounts p ON c.parent_id = p.id 
                  WHERE c.status = 'active'
                  ORDER BY c.account_type, c.account_code";
        $result = $this->db->query($query);
        $accounts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
        }
        return $accounts;
    }

    public function getAccountById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM chart_of_accounts WHERE id = ?");
        $stmt->bind_param("i", $id);
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

    public function createAccount($data)
    {
        $stmt = $this->db->prepare("INSERT INTO chart_of_accounts (parent_id, account_code, account_name, account_type, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $data['parent_id'], $data['account_code'], $data['account_name'], $data['account_type'], $data['status']);
        return $stmt->execute();
    }

    public function updateAccount($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE chart_of_accounts SET parent_id = ?, account_code = ?, account_name = ?, account_type = ?, status = ? WHERE id = ?");
        $stmt->bind_param("issssi", $data['parent_id'], $data['account_code'], $data['account_name'], $data['account_type'], $data['status'], $id);
        return $stmt->execute();
    }
}
