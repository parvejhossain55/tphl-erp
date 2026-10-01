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
        // Auto-sync any verified expenses/payments that do not yet have a journal entry
        $autoFile = __DIR__ . '/../services/AutoPostingService.php';
        if (file_exists($autoFile)) {
            require_once $autoFile;
            $auto = new AutoPostingService($this->db);
            $auto->syncUnpostedExpenses(100);
            $auto->syncUnpostedSalesPayments(100);
        }

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
                p.account_code as parent_code,
                gp.account_name as sub_name,
                gp.account_code as sub_code,
                COALESCE(j.source_module, 'General/Manual') as source_module,
                COALESCE(SUM(jd.debit), 0) as total_debit,
                COALESCE(SUM(jd.credit), 0) as total_credit
            FROM journal_details jd
            INNER JOIN journals j ON jd.journal_id = j.id
            INNER JOIN chart_of_accounts c ON jd.account_id = c.id
            LEFT JOIN chart_of_accounts p ON c.parent_id = p.id
            LEFT JOIN chart_of_accounts gp ON p.parent_id = gp.id
            $where
            GROUP BY c.id, c.account_code, c.account_name, c.account_type, c.parent_id, p.account_name, p.account_code, gp.account_name, gp.account_code, source_module
            HAVING total_debit != 0 OR total_credit != 0
            ORDER BY c.account_code ASC
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($query);
        }

        $accountsMap = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $type = $row['account_type'];
                $accId = $row['account_id'];
                
                $modDebit = (float)$row['total_debit'];
                $modCredit = (float)$row['total_credit'];
                $modBal = 0;
                
                switch ($type) {
                    case 'Asset':
                    case 'Expense':
                        $modBal = $modDebit - $modCredit;
                        break;
                    case 'Liability':
                    case 'Equity':
                    case 'Revenue':
                        $modBal = $modCredit - $modDebit;
                        break;
                    default:
                        $modBal = $modDebit - $modCredit;
                }
                
                if (!isset($accountsMap[$type])) {
                    $accountsMap[$type] = [];
                }
                
                if (!isset($accountsMap[$type][$accId])) {
                    $accountsMap[$type][$accId] = [
                        'account_id' => $row['account_id'],
                        'account_code' => $row['account_code'],
                        'account_name' => $row['account_name'],
                        'account_type' => $row['account_type'],
                        'parent_id' => $row['parent_id'],
                        'parent_name' => $row['parent_name'],
                        'parent_code' => $row['parent_code'],
                        'sub_name' => $row['sub_name'],
                        'sub_code' => $row['sub_code'],
                        'total_debit' => 0,
                        'total_credit' => 0,
                        'balance' => 0,
                        'modules' => []
                    ];
                }
                
                $accountsMap[$type][$accId]['total_debit'] += $modDebit;
                $accountsMap[$type][$accId]['total_credit'] += $modCredit;
                $accountsMap[$type][$accId]['balance'] += $modBal;
                
                $moduleName = trim($row['source_module']);
                if (empty($moduleName) || $moduleName == 'General/Manual') {
                    $moduleName = 'General Journal';
                } else {
                    $moduleName = ucwords(str_replace('_', ' ', $moduleName));
                }
                
                if (!isset($accountsMap[$type][$accId]['modules'][$moduleName])) {
                    $accountsMap[$type][$accId]['modules'][$moduleName] = 0;
                }
                $accountsMap[$type][$accId]['modules'][$moduleName] += $modBal;
            }
        }
        
        $accounts = [];
        foreach ($accountsMap as $type => $accs) {
            $accounts[$type] = array_values($accs);
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
     * Calculate Profit & Loss summary with GAAP Category Breakdown
     */
    public function getProfitLoss($dateFrom = null, $dateTo = null, $projectId = null, $office = null)
    {
        $balances = $this->getAccountBalances($dateFrom, $dateTo, $projectId, $office);
        
        $rawRevenue = $balances['Revenue'] ?? [];
        $rawExpenses = $balances['Expense'] ?? [];

        $directRevenue = [];
        $indirectRevenue = [];
        foreach ($rawRevenue as $r) {
            $code = (string)$r['account_code'];
            $subName = strtolower($r['sub_name'] ?? '');
            if (str_starts_with($code, '40') || str_contains($subName, 'operating income') || str_contains(strtolower($r['parent_name'] ?? ''), 'sales')) {
                $directRevenue[] = $r;
            } else {
                $indirectRevenue[] = $r;
            }
        }

        $directExpenses = [];
        $operatingExpenses = [];
        foreach ($rawExpenses as $e) {
            $code = (string)$e['account_code'];
            $subName = strtolower($e['sub_name'] ?? '');
            $parentName = strtolower($e['parent_name'] ?? '');
            if (str_starts_with($code, '520') || str_starts_with($code, '530') || str_starts_with($code, '540') || str_starts_with($code, '550') || str_starts_with($code, '570')
                || str_contains($subName, 'contractor') || str_contains($subName, 'construction') || str_contains($subName, 'architectural') || str_contains($subName, 'land') || str_contains($parentName, 'contractor') || str_contains($parentName, 'construction')) {
                $directExpenses[] = $e;
            } else {
                $operatingExpenses[] = $e;
            }
        }

        $totalDirectRevenue = array_sum(array_column($directRevenue, 'balance'));
        $totalIndirectRevenue = array_sum(array_column($indirectRevenue, 'balance'));
        $totalRevenue = $totalDirectRevenue + $totalIndirectRevenue;

        $totalDirectExpenses = array_sum(array_column($directExpenses, 'balance'));
        $totalOperatingExpenses = array_sum(array_column($operatingExpenses, 'balance'));
        $totalExpenses = $totalDirectExpenses + $totalOperatingExpenses;

        $grossProfit = $totalDirectRevenue - $totalDirectExpenses;
        $operatingProfit = $grossProfit - $totalOperatingExpenses;
        $netIncome = $operatingProfit + $totalIndirectRevenue;

        return [
            'revenue' => $rawRevenue,
            'expenses' => $rawExpenses,
            'direct_revenue' => $directRevenue,
            'indirect_revenue' => $indirectRevenue,
            'direct_expenses' => $directExpenses,
            'operating_expenses' => $operatingExpenses,
            'total_direct_revenue' => $totalDirectRevenue,
            'total_indirect_revenue' => $totalIndirectRevenue,
            'total_revenue' => $totalRevenue,
            'total_direct_expenses' => $totalDirectExpenses,
            'total_operating_expenses' => $totalOperatingExpenses,
            'total_expenses' => $totalExpenses,
            'gross_profit' => $grossProfit,
            'operating_profit' => $operatingProfit,
            'net_income' => $netIncome
        ];
    }

    /**
     * Calculate Balance Sheet with GAAP Category Breakdown
     */
    public function getBalanceSheet($dateFrom = null, $dateTo = null, $projectId = null, $office = null)
    {
        $balances = $this->getAccountBalances($dateFrom, $dateTo, $projectId, $office);
        
        $plData = $this->getProfitLoss($dateFrom, $dateTo, $projectId, $office);
        $netIncome = $plData['net_income'];

        $rawAssets = $balances['Asset'] ?? [];
        $rawLiabilities = $balances['Liability'] ?? [];
        $rawEquity = $balances['Equity'] ?? [];

        $nonCurrentAssets = [];
        $currentAssets = [];
        foreach ($rawAssets as $a) {
            $code = (string)$a['account_code'];
            $subName = strtolower($a['sub_name'] ?? '');
            if (str_starts_with($code, '10') || str_contains($subName, 'non-current') || str_contains(strtolower($a['parent_name'] ?? ''), 'property') || str_contains(strtolower($a['parent_name'] ?? ''), 'intangible')) {
                $nonCurrentAssets[] = $a;
            } else {
                $currentAssets[] = $a;
            }
        }

        $nonCurrentLiabilities = [];
        $currentLiabilities = [];
        foreach ($rawLiabilities as $l) {
            $code = (string)$l['account_code'];
            $subName = strtolower($l['sub_name'] ?? '');
            if (str_starts_with($code, '20') || str_contains($subName, 'non current') || str_contains($subName, 'long term') || str_contains(strtolower($l['parent_name'] ?? ''), 'loan')) {
                $nonCurrentLiabilities[] = $l;
            } else {
                $currentLiabilities[] = $l;
            }
        }

        $totalNonCurrentAssets = array_sum(array_column($nonCurrentAssets, 'balance'));
        $totalCurrentAssets = array_sum(array_column($currentAssets, 'balance'));
        $totalAssets = $totalNonCurrentAssets + $totalCurrentAssets;

        $totalNonCurrentLiabilities = array_sum(array_column($nonCurrentLiabilities, 'balance'));
        $totalCurrentLiabilities = array_sum(array_column($currentLiabilities, 'balance'));
        $totalLiabilities = $totalNonCurrentLiabilities + $totalCurrentLiabilities;

        $totalEquity = array_sum(array_column($rawEquity, 'balance'));
        $totalEquityWithIncome = $totalEquity + $netIncome;

        return [
            'assets' => $rawAssets,
            'liabilities' => $rawLiabilities,
            'equity' => $rawEquity,
            'non_current_assets' => $nonCurrentAssets,
            'current_assets' => $currentAssets,
            'non_current_liabilities' => $nonCurrentLiabilities,
            'current_liabilities' => $currentLiabilities,
            'total_non_current_assets' => $totalNonCurrentAssets,
            'total_current_assets' => $totalCurrentAssets,
            'total_assets' => $totalAssets,
            'total_non_current_liabilities' => $totalNonCurrentLiabilities,
            'total_current_liabilities' => $totalCurrentLiabilities,
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
        // Auto-sync any verified client payments that do not yet have a journal entry
        $autoFile = __DIR__ . '/../services/AutoPostingService.php';
        if (file_exists($autoFile)) {
            require_once $autoFile;
            $auto = new AutoPostingService($this->db);
            $auto->syncUnpostedSalesPayments(50);
            $auto->syncUnpostedExpenses(50);
        }

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

    /**
     * Get Accounts Payable (AP) Ageing Analysis Report
     */
    public function getApAgeingReport($asOfDate = null, $projectId = null, $office = null)
    {
        $asOfDate = $asOfDate ?? date('Y-m-d');
        $where = "WHERE (LOWER(j.approval_status) IN ('approved', 'posted') OR LOWER(j.status) IN ('approved', 'posted')) AND j.date <= '" . $this->db->real_escape_string($asOfDate) . "' AND c.account_type IN ('Liability', 'Liabilities', 'Current Liability')";
        if ($projectId) $where .= " AND j.project_id = " . (int)$projectId;
        if ($office) $where .= " AND j.office = '" . $this->db->real_escape_string($office) . "'";

        $sql = "
            SELECT 
                c.id as account_id,
                c.account_code,
                c.account_name,
                jd.party_type,
                jd.party_id,
                COALESCE(v.name, v.company_name, u.full_name, 'General Accounts Payable') as party_name,
                j.date as journal_date,
                DATEDIFF('" . $this->db->real_escape_string($asOfDate) . "', j.date) as age_days,
                COALESCE(SUM(jd.credit), 0) - COALESCE(SUM(jd.debit), 0) as net_payable
            FROM journal_details jd
            INNER JOIN journals j ON jd.journal_id = j.id
            INNER JOIN chart_of_accounts c ON jd.account_id = c.id
            LEFT JOIN vendors v ON (jd.party_type = 'vendor' AND jd.party_id = v.id)
            LEFT JOIN users u ON (jd.party_id = u.id)
            $where
            GROUP BY c.id, c.account_code, c.account_name, jd.party_type, jd.party_id, party_name, j.date
            HAVING net_payable > 0
            ORDER BY age_days DESC, party_name ASC
        ";

        $res = $this->db->query($sql);
        $parties = [];
        $summary = [
            'total_payable' => 0,
            'days_0_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_90_plus' => 0
        ];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $pKey = ($row['party_type'] ?: 'general') . '_' . ($row['party_id'] ?: '0');
                if (!isset($parties[$pKey])) {
                    $parties[$pKey] = [
                        'party_name' => $row['party_name'],
                        'account_code' => $row['account_code'],
                        'account_name' => $row['account_name'],
                        'days_0_30' => 0,
                        'days_31_60' => 0,
                        'days_61_90' => 0,
                        'days_90_plus' => 0,
                        'total' => 0
                    ];
                }

                $amt = (float)$row['net_payable'];
                $days = (int)$row['age_days'];

                if ($days <= 30) {
                    $parties[$pKey]['days_0_30'] += $amt;
                    $summary['days_0_30'] += $amt;
                } elseif ($days <= 60) {
                    $parties[$pKey]['days_31_60'] += $amt;
                    $summary['days_31_60'] += $amt;
                } elseif ($days <= 90) {
                    $parties[$pKey]['days_61_90'] += $amt;
                    $summary['days_61_90'] += $amt;
                } else {
                    $parties[$pKey]['days_90_plus'] += $amt;
                    $summary['days_90_plus'] += $amt;
                }

                $parties[$pKey]['total'] += $amt;
                $summary['total_payable'] += $amt;
            }
        }

        return [
            'summary' => $summary,
            'parties' => array_values($parties)
        ];
    }

    /**
     * Get Cash Flow Commitment Forecast
     */
    public function getCashCommitmentForecast($projectId = null, $office = null)
    {
        $commitments = [];
        $totalCommitment = 0;

        // 1. Management Approved / Verified Fund Requisitions (Pending Payment)
        $frSql = "SELECT fr.id, fr.purpose as description, fr.amount, fr.created_at as req_date, p.name as project_name, u.full_name as requested_by
                  FROM fund_requisitions fr
                  LEFT JOIN projects p ON p.id = fr.project_id
                  LEFT JOIN users u ON u.id = fr.created_by
                  WHERE fr.status IN ('approved', 'mgmt_approved', 'management_approved', 'verified', 'approved_by_management') AND fr.status != 'paid'";
        if ($projectId) $frSql .= " AND fr.project_id = " . (int)$projectId;
        $frRes = $this->db->query($frSql);
        if ($frRes) {
            while ($r = $frRes->fetch_assoc()) {
                $amt = (float)$r['amount'];
                $totalCommitment += $amt;
                $commitments[] = [
                    'module' => 'Fund Requisition',
                    'ref_no' => 'REQ-' . $r['id'],
                    'description' => $r['description'] ?: 'Site Expense Fund Requisition',
                    'project_name' => $r['project_name'] ?: 'Head Office',
                    'requested_by' => $r['requested_by'] ?: 'Employee',
                    'amount' => $amt,
                    'req_date' => date('d M Y', strtotime($r['req_date'])),
                    'status' => 'Pending Disbursement'
                ];
            }
        }

        // 2. Management Approved Asset Procurement Requests (Pending Payment)
        $apSql = "SELECT pea.id, pea.item_name, pea.total_price, pea.created_at as req_date, p.name as project_name, u.full_name as requested_by
                  FROM project_extra_assets pea
                  LEFT JOIN projects p ON p.id = pea.project_id
                  LEFT JOIN users u ON u.id = pea.requested_by
                  WHERE pea.payment_status = 'unpaid' AND (pea.purchase_head_status = 'approved' OR pea.mgmt_status = 'approved' OR pea.overall_status IN ('approved', 'in_review'))";
        if ($projectId) $apSql .= " AND pea.project_id = " . (int)$projectId;
        $apRes = $this->db->query($apSql);
        if ($apRes) {
            while ($r = $apRes->fetch_assoc()) {
                $amt = (float)$r['total_price'];
                $totalCommitment += $amt;
                $commitments[] = [
                    'module' => 'Asset Procurement',
                    'ref_no' => 'PR-' . $r['id'],
                    'description' => $r['item_name'],
                    'project_name' => $r['project_name'] ?: 'Head Office',
                    'requested_by' => $r['requested_by'] ?: 'Employee',
                    'amount' => $amt,
                    'req_date' => date('d M Y', strtotime($r['req_date'])),
                    'status' => 'Pending Disbursement'
                ];
            }
        }

        // 3. Management Approved Vendor Payment Requests (Pending Payment)
        $vprSql = "SELECT vpr.id, vpr.vpr_no, vpr.net_payable_amount, vpr.created_at as req_date, p.name as project_name, v.name as vendor_name
                   FROM con_vendor_payment_requests vpr
                   LEFT JOIN projects p ON p.id = vpr.project_id
                   LEFT JOIN vendors v ON v.id = vpr.vendor_id
                   WHERE vpr.status IN ('approved', 'mgmt_approved', 'management_approved', 'qc_approved', 'submitted') AND vpr.payment_status != 'paid'";
        if ($projectId) $vprSql .= " AND vpr.project_id = " . (int)$projectId;
        $vprRes = $this->db->query($vprSql);
        if ($vprRes) {
            while ($r = $vprRes->fetch_assoc()) {
                $amt = (float)$r['net_payable_amount'];
                $totalCommitment += $amt;
                $commitments[] = [
                    'module' => 'Vendor Bill',
                    'ref_no' => $r['vpr_no'] ?: ('VPR-' . $r['id']),
                    'description' => 'Vendor Bill Payment to ' . ($r['vendor_name'] ?: 'Vendor'),
                    'project_name' => $r['project_name'] ?: 'General',
                    'requested_by' => $r['vendor_name'] ?: 'Vendor',
                    'amount' => $amt,
                    'req_date' => date('d M Y', strtotime($r['req_date'])),
                    'status' => 'Pending Disbursement'
                ];
            }
        }

        // 4. Management Approved Material Purchase Orders (PO)
        $poSql = "SELECT po.id, po.order_date as req_date, v.name as vendor_name, pr.project_id, p.name as project_name, SUM(poi.total_price) as po_total
                  FROM purchase_orders po
                  INNER JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                  LEFT JOIN purchase_requests pr ON pr.id = po.request_id
                  LEFT JOIN projects p ON p.id = pr.project_id
                  LEFT JOIN vendors v ON v.id = po.vendor_id
                  WHERE po.status = 'Approved'";
        if ($projectId) $poSql .= " AND pr.project_id = " . (int)$projectId;
        $poSql .= " GROUP BY po.id, po.order_date, v.name, pr.project_id, p.name";

        $poRes = $this->db->query($poSql);
        if ($poRes) {
            while ($r = $poRes->fetch_assoc()) {
                $amt = (float)$r['po_total'];
                $totalCommitment += $amt;
                $commitments[] = [
                    'module' => 'Material Purchase Order',
                    'ref_no' => 'PO-' . $r['id'],
                    'description' => 'Approved Material Purchase Order for ' . ($r['vendor_name'] ?: 'Vendor'),
                    'project_name' => $r['project_name'] ?: 'Project Site',
                    'requested_by' => $r['vendor_name'] ?: 'Vendor',
                    'amount' => $amt,
                    'req_date' => date('d M Y', strtotime($r['req_date'])),
                    'status' => 'Management Approved'
                ];
            }
        }

        return [
            'total_commitment' => $totalCommitment,
            'commitments' => $commitments
        ];
    }
}
