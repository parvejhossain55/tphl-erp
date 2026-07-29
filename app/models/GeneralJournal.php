<?php
require_once __DIR__ . '/../core/Model.php';

class GeneralJournal extends Model
{
    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    public function getActiveAccounts()
    {
        $query = "SELECT id, account_code, account_name, account_type, parent_id FROM chart_of_accounts WHERE status = 'active' ORDER BY account_code ASC";
        $result = $this->db->query($query);
        $accounts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
        }
        return $accounts;
    }

    public function getActiveProjects()
    {
        $query = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC";
        $result = $this->db->query($query);
        $projects = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
        }
        return $projects;
    }

    public function getJournals($projectId = null)
    {
        $where = "WHERE 1=1";
        if ($projectId) {
            $where .= " AND j.project_id = " . (int)$projectId;
        }
        
        $query = "
            SELECT j.*, u.user_name as created_by_name, p.name as project_name 
            FROM journals j 
            LEFT JOIN users u ON j.created_by = u.id 
            LEFT JOIN projects p ON j.project_id = p.id
            $where
            ORDER BY j.date DESC, j.id DESC
        ";
        $result = $this->db->query($query);
        $journals = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $journals[] = $row;
            }
        }
        return $journals;
    }

    public function generateJournalNumber()
    {
        $query = "SELECT COUNT(id) as cnt FROM journals";
        $res = $this->db->query($query);
        $count = $res->fetch_assoc()['cnt'];
        return 'JV-' . date('Ym') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function storeJournal($headerData, $details, $userId)
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO journals (journal_number, date, voucher_type, narration, total_debit, total_credit, created_by, project_id, office) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssddiis",
                $headerData['journal_number'],
                $headerData['date'],
                $headerData['voucher_type'],
                $headerData['narration'],
                $headerData['total_debit'],
                $headerData['total_credit'],
                $userId,
                $headerData['project_id'],
                $headerData['office']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert journal entry.");
            }

            $journalId = $stmt->insert_id;
            
            // Insert Details
            $stmtDetails = $this->db->prepare("INSERT INTO journal_details (journal_id, account_id, debit, credit, narration, project_id) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($details as $row) {
                if ($row['debit'] == 0 && $row['credit'] == 0) continue; // skip empty rows
                $projectId = isset($row['project_id']) && $row['project_id'] !== '' ? (int)$row['project_id'] : null;
                $stmtDetails->bind_param("iiddsi", $journalId, $row['account_id'], $row['debit'], $row['credit'], $row['narration'], $projectId);
                if (!$stmtDetails->execute()) {
                    throw new Exception("Error saving journal detail");
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function getJournalById($id)
    {
        $stmt = $this->db->prepare("SELECT j.*, u.full_name as created_by_name, p.name as project_name FROM journals j LEFT JOIN users u ON j.created_by = u.id LEFT JOIN projects p ON j.project_id = p.id WHERE j.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getJournalDetails($journalId)
    {
        $stmt = $this->db->prepare("SELECT jd.*, c.account_code, c.account_name, c.account_type, p.account_name as parent_name
                                    FROM journal_details jd 
                                    JOIN chart_of_accounts c ON jd.account_id = c.id 
                                    LEFT JOIN chart_of_accounts p ON c.parent_id = p.id
                                    WHERE jd.journal_id = ?");
        $stmt->bind_param("i", $journalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = [];
        while ($row = $result->fetch_assoc()) {
            $details[] = $row;
        }
        return $details;
    }

    public function approveJournal($id)
    {
        $stmt = $this->db->prepare("UPDATE journals SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateJournal($id, $data)
    {
        $this->db->begin_transaction();
        try {
            // Update main journal
            $stmt = $this->db->prepare("UPDATE journals SET voucher_type = ?, narration = ?, total_debit = ?, total_credit = ?, project_id = ?, office = ? WHERE id = ?");
            $stmt->bind_param("ssddisi", $data['voucher_type'], $data['narration'], $data['total_debit'], $data['total_credit'], $data['project_id'], $data['office'], $id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update journal entry.");
            }

            // Delete old details
            $stmtDel = $this->db->prepare("DELETE FROM journal_details WHERE journal_id = ?");
            $stmtDel->bind_param("i", $id);
            if (!$stmtDel->execute()) {
                throw new Exception("Failed to delete old journal details.");
            }

            // Insert new details
            $stmtDet = $this->db->prepare("INSERT INTO journal_details (journal_id, account_id, narration, debit, credit) VALUES (?, ?, ?, ?, ?)");
            foreach ($data['accounts'] as $index => $accountId) {
                if (empty($accountId)) continue;

                $narration = $data['narrations'][$index] ?? '';
                $debit = isset($data['debits'][$index]) && $data['debits'][$index] !== '' ? (float)$data['debits'][$index] : 0.00;
                $credit = isset($data['credits'][$index]) && $data['credits'][$index] !== '' ? (float)$data['credits'][$index] : 0.00;

                if ($debit == 0 && $credit == 0) continue;

                $stmtDet->bind_param("iisdd", $id, $accountId, $narration, $debit, $credit);
                if (!$stmtDet->execute()) {
                    throw new Exception("Failed to save journal details.");
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}
