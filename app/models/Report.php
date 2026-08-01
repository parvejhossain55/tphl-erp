<?php
require_once __DIR__ . '/../core/Model.php';

class Report extends Model
{
    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /**
     * Get active projects for filter dropdowns
     */
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

    /**
     * Get account balances grouped by account type for financial reports.
     * Only includes APPROVED journals.
     * 
     * @param string|null $dateFrom  Start date (Y-m-d)
     * @param string|null $dateTo    End date (Y-m-d)
     * @param int|null    $projectId Filter by project
     * @param string|null $office    Filter by office
     * @return array Grouped by account_type => [accounts with balances]
     */
    public function getAccountBalances($dateFrom = null, $dateTo = null, $projectId = null, $office = null)
    {
        $where = "WHERE (LOWER(j.approval_status) IN ('approved', 'posted') OR LOWER(j.status) IN ('approved', 'posted'))";
        $params = [];
        $types = '';

        if ($dateFrom) {
            $where .= " AND j.date >= ?";
            $params[] = $dateFrom;
            $types .= 's';
        }
        if ($dateTo) {
            $where .= " AND j.date <= ?";
            $params[] = $dateTo;
            $types .= 's';
        }
        if ($projectId) {
            $where .= " AND j.project_id = ?";
            $params[] = (int)$projectId;
            $types .= 'i';
        }
        if ($office) {
            $where .= " AND j.office = ?";
            $params[] = $office;
            $types .= 's';
        }

        $query = "
            SELECT 
                c.id as account_id,
                c.account_code,
                c.account_name,
                c.account_type,
                c.parent_id,
                p.account_name as parent_name,
                COALESCE(SUM(jd.debit), 0) as total_debit,
                COALESCE(SUM(jd.credit), 0) as total_credit
            FROM journal_details jd
            INNER JOIN journals j ON jd.journal_id = j.id
            INNER JOIN chart_of_accounts c ON jd.account_id = c.id
            LEFT JOIN chart_of_accounts p ON c.parent_id = p.id
            $where
            GROUP BY c.id, c.account_code, c.account_name, c.account_type, c.parent_id, p.account_name
            HAVING total_debit != 0 OR total_credit != 0
            ORDER BY c.account_type, c.account_code
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($query);
        }

        $accounts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $type = $row['account_type'];
                if (!isset($accounts[$type])) {
                    $accounts[$type] = [];
                }
                
                // Calculate the balance based on account type
                switch ($type) {
                    case 'Asset':
                    case 'Expense':
                        $row['balance'] = $row['total_debit'] - $row['total_credit'];
                        break;
                    case 'Liability':
                    case 'Equity':
                    case 'Revenue':
                        $row['balance'] = $row['total_credit'] - $row['total_debit'];
                        break;
                    default:
                        $row['balance'] = $row['total_debit'] - $row['total_credit'];
                }
                
                $accounts[$type][] = $row;
            }
        }

        return $accounts;
    }

    /**
     * Get cumulative account balances up to a specific date (for Balance Sheet).
     * Balance Sheet is always cumulative from inception.
     */
    public function getCumulativeBalances($asOfDate = null, $projectId = null, $office = null)
    {
        return $this->getAccountBalances(null, $asOfDate, $projectId, $office);
    }

    /**
     * Calculate Profit & Loss summary
     */
    public function getProfitLoss($dateFrom = null, $dateTo = null, $projectId = null, $office = null)
    {
        $balances = $this->getAccountBalances($dateFrom, $dateTo, $projectId, $office);
        
        $revenue = $balances['Revenue'] ?? [];
        $expenses = $balances['Expense'] ?? [];

        $totalRevenue = array_sum(array_column($revenue, 'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome
        ];
    }

    /**
     * Calculate Balance Sheet
     */
    public function getBalanceSheet($asOfDate = null, $projectId = null, $office = null)
    {
        $balances = $this->getCumulativeBalances($asOfDate, $projectId, $office);
        
        // Also get net income to add to equity
        $plData = $this->getProfitLoss(null, $asOfDate, $projectId, $office);
        $netIncome = $plData['net_income'];

        $assets = $balances['Asset'] ?? [];
        $liabilities = $balances['Liability'] ?? [];
        $equity = $balances['Equity'] ?? [];

        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity = array_sum(array_column($equity, 'balance'));

        // Add net income to equity to balance the sheet
        $totalEquityWithIncome = $totalEquity + $netIncome;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'net_income' => $netIncome,
            'total_equity_with_income' => $totalEquityWithIncome,
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquityWithIncome,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquityWithIncome)) < 0.01
        ];
    }

    /**
     * Calculate Cashflow Statement
     */
    public function getCashflow($dateFrom = null, $dateTo = null, $projectId = null, $office = null)
    {
        $where = "WHERE (LOWER(j.approval_status) IN ('approved', 'posted') OR LOWER(j.status) IN ('approved', 'posted'))";
        $params = [];
        $types = '';

        if ($dateFrom) {
            $where .= " AND j.date >= ?";
            $params[] = $dateFrom;
            $types .= 's';
        }
        if ($dateTo) {
            $where .= " AND j.date <= ?";
            $params[] = $dateTo;
            $types .= 's';
        }
        if ($projectId) {
            $where .= " AND j.project_id = ?";
            $params[] = (int)$projectId;
            $types .= 'i';
        }
        if ($office) {
            $where .= " AND j.office = ?";
            $params[] = $office;
            $types .= 's';
        }

        // Cash accounts: accounts whose name contains 'Cash' or 'Bank'
        $cashWhere = "(LOWER(c.account_name) LIKE '%cash%' OR LOWER(c.account_name) LIKE '%bank%')";

        $query = "
            SELECT 
                c.id as account_id,
                c.account_code,
                c.account_name,
                c.account_type,
                c.parent_id,
                p.account_name as parent_name,
                COALESCE(SUM(jd.debit), 0) as total_inflow,
                COALESCE(SUM(jd.credit), 0) as total_outflow
            FROM journal_details jd
            INNER JOIN journals j ON jd.journal_id = j.id
            INNER JOIN chart_of_accounts c ON jd.account_id = c.id
            LEFT JOIN chart_of_accounts p ON c.parent_id = p.id
            $where
            AND $cashWhere
            GROUP BY c.id, c.account_code, c.account_name, c.account_type, c.parent_id, p.account_name
            HAVING total_inflow != 0 OR total_outflow != 0
            ORDER BY c.account_code
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($query);
        }

        $accounts = [];
        $totalInflow = 0;
        $totalOutflow = 0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
                $totalInflow += $row['total_inflow'];
                $totalOutflow += $row['total_outflow'];
            }
        }

        return [
            'accounts' => $accounts,
            'total_inflow' => $totalInflow,
            'total_outflow' => $totalOutflow,
            'net_cashflow' => $totalInflow - $totalOutflow
        ];
    }

    public function getTrialBalance($asOfDate = null, $projectId = null, $office = null)
    {
        $asOfDate = $asOfDate ?? date('Y-m-d');
        $where = "WHERE (LOWER(j.approval_status) IN ('approved', 'posted') OR LOWER(j.status) IN ('approved', 'posted')) AND j.date <= '" . $this->db->real_escape_string($asOfDate) . "'";
        if ($projectId) $where .= " AND j.project_id = " . (int)$projectId;
        if ($office) $where .= " AND j.office = '" . $this->db->real_escape_string($office) . "'";

        $sql = "
            SELECT 
                c.id as account_id,
                c.account_code,
                c.account_name,
                c.account_type,
                c.root_type,
                COALESCE(SUM(jd.debit), 0) as total_debit,
                COALESCE(SUM(jd.credit), 0) as total_credit
            FROM journal_details jd
            INNER JOIN journals j ON jd.journal_id = j.id
            INNER JOIN chart_of_accounts c ON jd.account_id = c.id
            $where
            GROUP BY c.id, c.account_code, c.account_name, c.account_type, c.root_type
            HAVING total_debit != 0 OR total_credit != 0
            ORDER BY c.account_code ASC
        ";

        $res = $this->db->query($sql);
        $accounts = [];
        $grandTotalDebit = 0;
        $grandTotalCredit = 0;

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $debit = (float)$row['total_debit'];
                $credit = (float)$row['total_credit'];
                $grandTotalDebit += $debit;
                $grandTotalCredit += $credit;
                $accounts[] = $row;
            }
        }

        return [
            'accounts' => $accounts,
            'total_debit' => $grandTotalDebit,
            'total_credit' => $grandTotalCredit,
            'is_balanced' => (abs($grandTotalDebit - $grandTotalCredit) < 0.01)
        ];
    }

    public function getDetailedTrialBalance($asOfDate = null, $projectId = null, $office = null)
    {
        $asOfDate = $asOfDate ?? date('Y-m-d');
        $where = "WHERE (LOWER(j.approval_status) IN ('approved', 'posted') OR LOWER(j.status) IN ('approved', 'posted')) AND j.date <= '" . $this->db->real_escape_string($asOfDate) . "'";
        if ($projectId) $where .= " AND j.project_id = " . (int)$projectId;
        if ($office) $where .= " AND j.office = '" . $this->db->real_escape_string($office) . "'";

        $sql = "
            SELECT 
                c.id as account_id,
                c.account_code,
                c.account_name,
                c.account_type,
                c.root_type,
                jd.party_type,
                jd.party_id,
                COALESCE(v.name, v.company_name, u.full_name, l.full_name, 'General / Direct Entry') as party_name,
                COALESCE(SUM(jd.debit), 0) as total_debit,
                COALESCE(SUM(jd.credit), 0) as total_credit
            FROM journal_details jd
            INNER JOIN journals j ON jd.journal_id = j.id
            INNER JOIN chart_of_accounts c ON jd.account_id = c.id
            LEFT JOIN vendors v ON (jd.party_type = 'vendor' AND jd.party_id = v.id)
            LEFT JOIN users u ON (jd.party_id = u.id)
            LEFT JOIN leads l ON (jd.party_type = 'client' AND jd.party_id = l.id)
            $where
            GROUP BY c.id, c.account_code, c.account_name, c.account_type, c.root_type, jd.party_type, jd.party_id, party_name
            HAVING total_debit != 0 OR total_credit != 0
            ORDER BY c.account_code ASC, party_name ASC
        ";

        $res = $this->db->query($sql);
        $accounts = [];
        $grandTotalDebit = 0;
        $grandTotalCredit = 0;

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $debit = (float)$row['total_debit'];
                $credit = (float)$row['total_credit'];
                $grandTotalDebit += $debit;
                $grandTotalCredit += $credit;
                $accounts[] = $row;
            }
        }

        return [
            'accounts' => $accounts,
            'total_debit' => $grandTotalDebit,
            'total_credit' => $grandTotalCredit,
            'is_balanced' => (abs($grandTotalDebit - $grandTotalCredit) < 0.01)
        ];
    }
}
