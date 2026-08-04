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
        // Auto-sync any verified client payments or vendor payments that do not yet have a journal entry
        $autoFile = __DIR__ . '/../services/AutoPostingService.php';
        if (file_exists($autoFile)) {
            require_once $autoFile;
            $auto = new AutoPostingService($this->db);
            $auto->syncUnpostedSalesPayments(50);
        }

        $jisFile = __DIR__ . '/../services/JournalIntegrationService.php';
        if (file_exists($jisFile)) {
            require_once $jisFile;
            $jis = new \Services\JournalIntegrationService($this->db);
            $jis->syncUnpostedVendorPayments(50);
        }

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
            ORDER BY j.id DESC
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

    public function getProjectsWithJournals()
    {
        $query = "
            SELECT DISTINCT p.id, p.name 
            FROM projects p 
            INNER JOIN journals j ON j.project_id = p.id 
            WHERE p.deleted_at IS NULL 
            ORDER BY p.name ASC
        ";
        $result = $this->db->query($query);
        $projects = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
        }
        return $projects;
    }

    public function getFilteredJournals($filters = [])
    {
        // Auto-sync any verified client payments or vendor payments
        $autoFile = __DIR__ . '/../services/AutoPostingService.php';
        if (file_exists($autoFile)) {
            require_once $autoFile;
            $auto = new AutoPostingService($this->db);
            $auto->syncUnpostedSalesPayments(50);
        }

        $jisFile = __DIR__ . '/../services/JournalIntegrationService.php';
        if (file_exists($jisFile)) {
            require_once $jisFile;
            $jis = new \Services\JournalIntegrationService($this->db);
            $jis->syncUnpostedVendorPayments(50);
        }

        $where = "WHERE 1=1";

        // Filter 1: Project Filter (Only specific project or 'head_office')
        if (isset($filters['project_id']) && $filters['project_id'] !== '') {
            if ($filters['project_id'] === 'head_office' || $filters['project_id'] === '0') {
                $where .= " AND (j.project_id IS NULL OR j.project_id = 0)";
            } else {
                $where .= " AND j.project_id = " . (int)$filters['project_id'];
            }
        }

        // Filter 2: Module / Category Filter
        if (!empty($filters['module'])) {
            $mod = $filters['module'];
            if ($mod === 'fund_requisition') {
                $where .= " AND (j.source_module = 'fund_requisition' OR j.narration LIKE '%Fund Requisition%' OR j.narration LIKE '%REQ-%')";
            } elseif ($mod === 'vendor_payment') {
                $where .= " AND (j.source_module IN ('vendor_payment', 'vendor_payment_accrual') OR j.narration LIKE '%Vendor Payment%' OR j.narration LIKE '%VPR-%')";
            } elseif ($mod === 'contractor_bill') {
                $where .= " AND (j.source_module IN ('contractor_bill', 'contractor_bill_accrual') OR j.narration LIKE '%Contractor%')";
            } elseif ($mod === 'material_purchase') {
                $where .= " AND (j.source_module IN ('material_purchase', 'procurement') OR j.narration LIKE '%Material%' OR j.narration LIKE '%Procurement%')";
            } elseif ($mod === 'payroll') {
                $where .= " AND (j.source_module IN ('payroll', 'salary') OR j.narration LIKE '%Salary%' OR j.narration LIKE '%Payroll%')";
            } elseif ($mod === 'management_cashin') {
                $where .= " AND (j.source_module IN ('management_cashin', 'investment') OR j.narration LIKE '%Management Cash In%' OR j.narration LIKE '%Investment%')";
            } elseif ($mod === 'land_acquisition') {
                $where .= " AND (j.source_module = 'land_requisition' OR j.narration LIKE '%Land Acquisition%' OR j.narration LIKE '%Bayana%' OR j.narration LIKE '%Registration%')";
            } elseif ($mod === 'sales_payment') {
                $where .= " AND (j.source_module = 'sales_payment' OR j.narration LIKE '%Client Receipt%' OR j.narration LIKE '%Booking Money%')";
            } elseif ($mod === 'expense_voucher') {
                $where .= " AND (j.voucher_type = 'Payment Voucher' OR j.narration LIKE '%Expense%')";
            }
        }

        // Filter 3: Expense Sub-Type Filter (Vendor & Material Payment, Contractor Bill Payment)
        if (!empty($filters['expense_type'])) {
            $expType = $filters['expense_type'];
            if ($expType === 'vendor_material' || $expType === 'vendor' || $expType === 'material') {
                $where .= " AND (j.source_module IN ('vendor_payment', 'vendor_payment_accrual', 'material_purchase', 'procurement') OR j.narration LIKE '%Vendor%' OR j.narration LIKE '%Material%' OR j.narration LIKE '%VPR-%' OR j.id IN (SELECT DISTINCT journal_id FROM journal_details jd INNER JOIN chart_of_accounts coa ON jd.account_id = coa.id WHERE coa.account_code LIKE '5120%' OR coa.account_code LIKE '1320%' OR coa.account_code LIKE '2110%'))";
            } elseif ($expType === 'contractor') {
                $where .= " AND (j.source_module IN ('contractor_bill', 'contractor_bill_accrual') OR j.narration LIKE '%Contractor%' OR j.id IN (SELECT DISTINCT journal_id FROM journal_details jd INNER JOIN chart_of_accounts coa ON jd.account_id = coa.id WHERE coa.account_code LIKE '5110%' OR coa.account_code LIKE '2120%'))";
            }
        }

        // Filter 4: Keyword Search
        if (!empty($filters['search'])) {
            $s = $this->db->real_escape_string(trim($filters['search']));
            $where .= " AND (j.journal_number LIKE '%$s%' OR j.narration LIKE '%$s%' OR j.voucher_type LIKE '%$s%' OR j.id IN (
                SELECT DISTINCT jd.journal_id FROM journal_details jd 
                INNER JOIN chart_of_accounts coa ON jd.account_id = coa.id 
                WHERE coa.account_name LIKE '%$s%' OR coa.account_code LIKE '%$s%' OR jd.narration LIKE '%$s%'
            ))";
        }

        // Filter 5: Date Range
        if (!empty($filters['date_from'])) {
            $where .= " AND j.date >= '" . $this->db->real_escape_string($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND j.date <= '" . $this->db->real_escape_string($filters['date_to']) . "'";
        }

        $query = "
            SELECT j.*, u.user_name as created_by_name, p.name as project_name 
            FROM journals j 
            LEFT JOIN users u ON j.created_by = u.id 
            LEFT JOIN projects p ON j.project_id = p.id
            $where
            ORDER BY j.id DESC
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
        $query = "SELECT COALESCE(MAX(id), 0) as max_id FROM journals";
        $res = $this->db->query($query);
        $maxId = $res ? (int)$res->fetch_assoc()['max_id'] : 0;
        return 'JV-' . date('Ym') . '-' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
    }

    public function storeJournal($headerData, $details, $userId)
    {
        if (empty($headerData['office'])) {
            $headerData['office'] = $this->getUserWorkplaceOffice($userId);
        }

        $status       = !empty($headerData['status']) ? $headerData['status'] : 'approved';
        $sourceModule = !empty($headerData['source_module']) ? $headerData['source_module'] : null;
        $sourceRefId  = !empty($headerData['source_ref_id']) ? (int)$headerData['source_ref_id'] : null;
        $entryType    = !empty($headerData['entry_type']) ? $headerData['entry_type'] : null;

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO journals (journal_number, date, voucher_type, narration, total_debit, total_credit, created_by, project_id, office, status, source_module, source_ref_id, entry_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssddiisssis",
                $headerData['journal_number'],
                $headerData['date'],
                $headerData['voucher_type'],
                $headerData['narration'],
                $headerData['total_debit'],
                $headerData['total_credit'],
                $userId,
                $headerData['project_id'],
                $headerData['office'],
                $status,
                $sourceModule,
                $sourceRefId,
                $entryType
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert journal entry.");
            }

            $journalId = $stmt->insert_id;
            
            // Insert Details
            $stmtDetails = $this->db->prepare("INSERT INTO journal_details (journal_id, account_id, debit, credit, narration, project_id, party_type, party_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($details as $row) {
                if ($row['debit'] == 0 && $row['credit'] == 0) continue; // skip empty rows
                $projectId = isset($row['project_id']) && $row['project_id'] !== '' ? (int)$row['project_id'] : null;
                $partyType = !empty($row['party_type']) ? $row['party_type'] : null;
                $partyId   = !empty($row['party_id']) ? (int)$row['party_id'] : null;
                $stmtDetails->bind_param("iiddsisi", $journalId, $row['account_id'], $row['debit'], $row['credit'], $row['narration'], $projectId, $partyType, $partyId);
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

    /**
     * Helper to get employee workplace for office field, default to 'Site Office' if not found
     */
    public function getUserWorkplaceOffice($userId = null)
    {
        if (!$userId) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userId = $_SESSION['user_id'] ?? null;
        }

        if ($userId) {
            $stmt = $this->db->prepare("
                SELECT COALESCE(
                    NULLIF(TRIM(e.office_branch), ''), 
                    NULLIF(TRIM(e.workplace), '')
                ) AS office_branch 
                FROM users u 
                LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id) 
                WHERE u.id = ? 
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                if (!empty($res['office_branch'])) {
                    return trim($res['office_branch']);
                }
            }
        }
        return 'Site Office';
    }
}
