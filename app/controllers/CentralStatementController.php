<?php
require_once __DIR__ . '/../models/CentralStatement.php';

class CentralStatementController extends Controller
{
    protected $db;
    protected $statementModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->statementModel = new CentralStatement($this->db);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /**
     * Parse project_id filter safely ('head_office', int > 0, or null)
     */
    private function parseProjectId($rawProjectId)
    {
        if ($rawProjectId === 'head_office') {
            return 'head_office';
        }
        return (!empty($rawProjectId) && is_numeric($rawProjectId) && (int)$rawProjectId > 0)
            ? (int)$rawProjectId
            : null;
    }

    /**
     * Resolve human-readable project name for filter display
     */
    private function resolveProjectName($projectId): string
    {
        if ($projectId === 'head_office') {
            return 'Head Office / General';
        }
        if ($projectId && is_numeric($projectId) && (int)$projectId > 0) {
            $pid = (int)$projectId;
            $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $pid);
                $stmt->execute();
                $res = $stmt->get_result();
                $name = ($row = $res->fetch_assoc()) ? ($row['name'] ?? '') : '';
                $stmt->close();
                return $name;
            }
        }
        return '';
    }

    /**
     * Resolve human-readable category name
     */
    private function resolveCategoryName(string $category): string
    {
        if ($category === 'all' || $category === '') {
            return '';
        }
        $map = [
            'total_cashin' => 'Total Cash IN (Combined)',
            'landshare' => 'Landshare Only',
            'service_fee' => 'Service Fee Only',
            'emi' => 'EMI Only',
            'registration' => 'Registration Only',
            'booking_money' => 'Booking Money Only',
            'development_charge' => 'Development Charge Only',
            'management_cashin' => 'Management Cashin Only',
            'expense_adjustment_cashin' => 'Expense Adjustment Returns',
            'procurement_returns_cashin' => 'Procurement Returns',
            'bank_cashin' => 'Bank Cash In',
            'bank_cashout' => 'Bank Cash Out',
            'cashout' => 'Cash Out',
            'utilities' => 'Utilities (Bill/Electricity/Wifi/etc)',
            'vendor_payment' => 'Vendor Payments',
            'contractor_bill' => 'Contractor Bills',
            'office_procurement_assets' => 'Office Procurement Assets',
            'assets_repairing' => 'Assets Repairing',
            'fund_requisitions' => 'Fund Requisitions',
        ];
        return $map[$category] ?? ucfirst(str_replace('_', ' ', $category));
    }

    /**
     * Resolve Period & Statement Data consistently across index, print, and export
     */
    private function resolveStatementByFilters(string $periodType, int $year, $month, ?string $startDate, ?string $endDate, $projectId, string $category): array
    {
        $statementData = null;
        $periodText = '';
        $statementStartDate = '';
        $statementEndDate = '';

        if ($periodType === 'all_time') {
            $statementData = $this->statementModel->getAllTimeStatement($projectId, $category);
            $periodText = 'All Time';
            $statementStartDate = $statementData['start_date'] ?? date('Y-01-01');
            $statementEndDate = $statementData['end_date'] ?? date('Y-12-31');
        } elseif ($periodType === 'custom') {
            if ($startDate && $endDate) {
                $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate, $projectId, $category);
                $periodText = date('d M, Y', strtotime($startDate)) . ' to ' . date('d M, Y', strtotime($endDate));
                $statementStartDate = $startDate;
                $statementEndDate = $endDate;
            } else {
                $year = (int)date('Y');
                $mInt = (int)date('m');
                $statementData = $this->statementModel->getMonthlyStatement($year, $mInt, $projectId, $category);
                $periodText = date('F Y');
                $periodType = 'monthly';
                $statementStartDate = date('Y-m-01');
                $statementEndDate = date('Y-m-t');
            }
        } elseif ($periodType === '6months') {
            $mInt = ($month === 'all' || empty($month) || (int)$month < 1 || (int)$month > 12) ? (int)date('m') : (int)$month;
            $statementData = $this->statementModel->getSixMonthStatement($year, $mInt, $projectId, $category);
            $periodText = 'Last 6 Months';
            $statementStartDate = date('Y-m-01', strtotime("-5 months", strtotime("$year-" . sprintf('%02d', $mInt) . "-01")));
            $statementEndDate = date('Y-m-t', strtotime("$year-" . sprintf('%02d', $mInt) . "-01"));
        } elseif ($periodType === 'yearly') {
            $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
            $periodText = "Year $year";
            $statementStartDate = "$year-01-01";
            $statementEndDate = "$year-12-31";
        } elseif ($month === 'all' || $month === '0' || $month === 0) {
            $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
            $periodText = "All Months ($year)";
            $statementStartDate = "$year-01-01";
            $statementEndDate = "$year-12-31";
        } else {
            $mInt = (int)$month;
            $statementData = $this->statementModel->getMonthlyStatement($year, $mInt, $projectId, $category);
            $periodText = date('F Y', strtotime("$year-" . sprintf('%02d', $mInt) . "-01"));
            $statementStartDate = "$year-" . sprintf('%02d', $mInt) . "-01";
            $statementEndDate = date('Y-m-t', strtotime($statementStartDate));
        }

        return [$statementData, $periodType, $periodText, $statementStartDate, $statementEndDate];
    }

    /**
     * Main index - Show central statement page
     */
    public function index()
    {
        $isPrint = isset($_GET['print']) && $_GET['print'] == 'true';
        if ($isPrint) {
            $this->printStatement();
            return;
        }

        $periodType = $_GET['period_type'] ?? 'monthly';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = $_GET['month'] ?? date('m');
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : null;
        $projectId = $this->parseProjectId($_GET['project_id'] ?? null);
        $category = $_GET['category'] ?? 'all';

        if (empty($_GET['period_type']) && $startDate && $endDate) {
            $periodType = 'custom';
        }

        [$statementData, $periodType, $periodText, $statementStartDate, $statementEndDate] =
            $this->resolveStatementByFilters($periodType, $year, $month, $startDate, $endDate, $projectId, $category);

        $projects = $this->getProjects();

        $overallStats = [
            'total_agreement' => $statementData['summary']['total_agreement_value'] ?? 0,
            'total_service_fee' => $statementData['summary']['total_service_fee'] ?? 0,
            'total_management_cashin' => $statementData['summary']['total_management_cashin'] ?? 0,
            'total_cashin' => $statementData['summary']['total_cashin'] ?? 0,
            'total_revenue' => $statementData['summary']['total_revenue'] ?? 0,
            'total_cash_out' => $statementData['summary']['total_cash_out'] ?? 0,
            'net_balance' => $statementData['summary']['net_balance'] ?? 0,
            'final_balance' => $statementData['summary']['final_balance'] ?? 0
        ];

        $revenueData = null;
        if ($statementStartDate && $statementEndDate) {
            $revenueData = $this->statementModel->calculateRevenue($statementStartDate, $statementEndDate, $projectId);
        }

        // Fetch client payment details (includes Client Name, Project Name, Flat Name)
        $clientPaymentDetails = [];
        if ($statementStartDate && $statementEndDate) {
            $clientPaymentDetails = $this->statementModel->getClientPaymentDetails(
                $statementStartDate,
                $statementEndDate,
                $projectId
            );
        }

        // Fetch cashout payment details
        $cashoutPaymentDetails = [];
        if ($category === 'cashout' && $statementStartDate && $statementEndDate) {
            $cashoutPaymentDetails = $this->statementModel->getCashoutPaymentDetails(
                $statementStartDate,
                $statementEndDate,
                $projectId
            );
        }

        // Fetch vendor payment details
        $vendorPaymentDetails = [];
        if ($category === 'vendor_payment' && $statementStartDate && $statementEndDate) {
            $vendorPaymentDetails = $this->statementModel->getVendorPaymentDetails(
                $statementStartDate,
                $statementEndDate,
                $projectId
            );
        }

        // Fetch contractor bill details
        $contractorBillDetails = [];
        if ($category === 'contractor_bill' && $statementStartDate && $statementEndDate) {
            $contractorBillDetails = $this->statementModel->getContractorBillDetails(
                $statementStartDate,
                $statementEndDate,
                $projectId
            );
        }

        // Fetch bank transactions
        $bankTransactions = [];
        if (in_array($category, ['bank_cashin', 'bank_cashout'], true) && $statementStartDate && $statementEndDate) {
            $bankTransactions = $this->getBankTransactions($category, $statementStartDate, $statementEndDate, $projectId);
        }

        $viewData = [
            'current_route' => 'central-statement',
            'page_title' => 'Central Financial Statement',
            'period_type' => $periodType,
            'period_text' => $periodText,
            'year' => $year,
            'month' => $month === 'all' ? 'all' : sprintf('%02d', (int)$month),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'project_id' => $projectId,
            'category' => $category,
            'projects' => $projects,
            'statement_data' => $statementData,
            'overall_stats' => $overallStats,
            'revenue_data' => $revenueData,
            'client_payment_details' => $clientPaymentDetails,
            'cashout_payment_details' => $cashoutPaymentDetails,
            'vendor_payment_details' => $vendorPaymentDetails,
            'contractor_bill_details' => $contractorBillDetails,
            'bank_transactions' => $bankTransactions,
        ];

        $this->view('accounts/central-statement', $viewData);
    }

    /**
     * Get projects list
     */
    private function getProjects()
    {
        return $this->statementModel->getProjects();
    }

    /**
     * Print statement - separate method for print view
     */
    public function printStatement()
    {
        $periodType = $_GET['period_type'] ?? 'monthly';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = $_GET['month'] ?? date('m');
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : null;
        $projectId = $this->parseProjectId($_GET['project_id'] ?? null);
        $category = $_GET['category'] ?? 'all';

        if (empty($_GET['period_type']) && $startDate && $endDate) {
            $periodType = 'custom';
        }

        [$statement_data, $periodType, $periodText, $statementStartDate, $statementEndDate] =
            $this->resolveStatementByFilters($periodType, $year, $month, $startDate, $endDate, $projectId, $category);

        $overall_stats = [
            'total_agreement' => $statement_data['summary']['total_agreement_value'] ?? 0,
            'total_service_fee' => $statement_data['summary']['total_service_fee'] ?? 0,
            'total_management_cashin' => $statement_data['summary']['total_management_cashin'] ?? 0,
            'total_cashin' => $statement_data['summary']['total_cashin'] ?? 0,
            'total_revenue' => $statement_data['summary']['total_revenue'] ?? 0,
            'total_cash_out' => $statement_data['summary']['total_cash_out'] ?? 0,
            'net_balance' => $statement_data['summary']['net_balance'] ?? 0,
            'final_balance' => $statement_data['summary']['final_balance'] ?? 0
        ];

        $revenue_data = null;
        if ($statementStartDate && $statementEndDate) {
            $revenue_data = $this->statementModel->calculateRevenue($statementStartDate, $statementEndDate, $projectId);
        }

        $cashout_payment_details = [];
        if ($category === 'cashout' && $statementStartDate && $statementEndDate) {
            $cashout_payment_details = $this->statementModel->getCashoutPaymentDetails($statementStartDate, $statementEndDate, $projectId);
        }

        $company_name = "THE PREMIUM HOMES LTD.";
        $company_address = "Land View Commercial Center, 9th Floor 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
        $company_phone = "+8801958253300";
        $company_email = "info@thepremiumhomes.com";

        $projectName = $this->resolveProjectName($projectId);
        $categoryName = $this->resolveCategoryName($category);

        $viewData = [
            'statement_data' => $statement_data,
            'overall_stats' => $overall_stats,
            'period_text' => $periodText,
            'start_date' => $statementStartDate,
            'end_date' => $statementEndDate,
            'category' => $category,
            'projectName' => $projectName,
            'categoryName' => $categoryName,
            'company_name' => $company_name,
            'company_address' => $company_address,
            'company_phone' => $company_phone,
            'company_email' => $company_email,
            'period_type' => $periodType,
            'year' => $year,
            'month' => $month,
            'cashout_payment_details' => $cashout_payment_details,
        ];

        if ($category === 'utilities') {
            $viewData['utilitiesTransactions'] = $this->statementModel->getUtilitiesTransactions($statementStartDate, $statementEndDate, $projectId);
        }

        if ($category === 'vendor_payment' && $statementStartDate && $statementEndDate) {
            $viewData['vendor_payment_details'] = $this->statementModel->getVendorPaymentDetails(
                $statementStartDate,
                $statementEndDate,
                $projectId
            );
        }

        if ($category === 'contractor_bill' && $statementStartDate && $statementEndDate) {
            $viewData['contractor_bill_details'] = $this->statementModel->getContractorBillDetails(
                $statementStartDate,
                $statementEndDate,
                $projectId
            );
        }

        if (in_array($category, ['bank_cashin', 'bank_cashout'], true) && $statementStartDate && $statementEndDate) {
            $viewData['bank_transactions'] = $this->getBankTransactions($category, $statementStartDate, $statementEndDate, $projectId);
        }

        extract($viewData);
        include __DIR__ . '/../views/accounts/central-statement-print.php';
        exit;
    }

    /**
     * Get bank transactions for bank_cashin or bank_cashout across all relevant tables
     */
    private function getBankTransactions($type, $startDate, $endDate, $projectId = null)
    {
        $transactions = [];
        $isHeadOffice = ($projectId === 'head_office');
        $isSpecificProj = ($projectId !== null && $projectId !== '' && !$isHeadOffice && is_numeric($projectId) && (int)$projectId > 0);
        $pid = $isSpecificProj ? (int)$projectId : 0;

        if ($type === 'bank_cashin') {
            $parts = [];
            $params = [];
            $types = "";

            // 1. Lead payments via bank
            if (!$isHeadOffice) {
                $lpSql = "SELECT 
                            DATE(lp.payment_date) as payment_date,
                            lp.amount,
                            COALESCE(NULLIF(b.bank_name, ''), NULLIF(lp.bank_name, ''), '-') as bank_name,
                            COALESCE(NULLIF(b.account_name, ''), '-') as branch_name,
                            '' as routing_number,
                            COALESCE(NULLIF(b.account_number, ''), NULLIF(lp.bank_account_no, ''), '-') as account_number,
                            CONCAT(COALESCE(l.full_name, 'Client'), ' — ', UPPER(REPLACE(lp.payment_type, '_', ' '))) as description,
                            lp.transaction_ref
                        FROM lead_payments lp
                        LEFT JOIN company_bank_accounts b ON b.id = lp.bank_id
                        INNER JOIN lead_bookings lb ON lb.id = lp.booking_id
                        LEFT JOIN leads l ON l.id = lb.lead_id
                        WHERE lp.payment_method = 'bank'
                        AND DATE(lp.payment_date) BETWEEN ? AND ?
                        AND lp.payment_status IN ('paid', 'partially_paid')
                        AND lp.verification_status = 'verified'";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
                if ($isSpecificProj) {
                    $lpSql .= " AND lb.project_id = ?";
                    $params[] = $pid;
                    $types .= "i";
                }
                $parts[] = $lpSql;
            }

            // 2. Management cash-in via bank
            if (!$isSpecificProj) {
                $mcSql = "SELECT 
                            DATE(mc.transaction_date) as payment_date,
                            mc.amount,
                            COALESCE(NULLIF(mc.bank_name, ''), '-') as bank_name,
                            '-' as branch_name,
                            '' as routing_number,
                            COALESCE(NULLIF(mc.bank_account_no, ''), '-') as account_number,
                            CONCAT('Management Cash-In: ', COALESCE(mc.person_name, ''), ' (', COALESCE(mc.source, ''), ')') as description,
                            mc.transaction_ref
                        FROM management_cashin mc
                        WHERE mc.payment_method = 'bank'
                        AND mc.payment_status = 'paid'
                        AND DATE(mc.transaction_date) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
                $parts[] = $mcSql;
            }

            // 3. Expense adjustment refunds via bank
            $eaSql = "SELECT 
                        DATE(at.payment_date) as payment_date,
                        at.amount,
                        COALESCE(NULLIF(b.bank_name, ''), '-') as bank_name,
                        COALESCE(NULLIF(b.account_name, ''), '-') as branch_name,
                        '' as routing_number,
                        COALESCE(NULLIF(b.account_number, ''), NULLIF(at.bank_account_no, ''), '-') as account_number,
                        CONCAT('Expense Adj. Return: ', COALESCE(ea.title, 'Refund')) as description,
                        at.transaction_ref
                    FROM adjustment_transactions at
                    JOIN expense_adjustments ea ON at.adjustment_id = ea.id
                    LEFT JOIN company_bank_accounts b ON b.id = at.bank_id
                    WHERE at.transaction_type = 'cashin'
                    AND at.payment_method = 'bank'
                    AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
                    AND DATE(at.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
            if ($isHeadOffice) {
                $eaSql .= " AND (ea.project_id IS NULL OR ea.project_id = 0)";
            } elseif ($isSpecificProj) {
                $eaSql .= " AND ea.project_id = ?";
                $params[] = $pid;
                $types .= "i";
            }
            $parts[] = $eaSql;

            $sql = "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") combined_bank_in ORDER BY payment_date DESC";
        } else {
            $parts = [];
            $params = [];
            $types = "";

            // 1. Cashout request payments via bank
            $cpSql = "SELECT 
                        DATE(cp.payment_date) as payment_date,
                        cp.amount,
                        COALESCE(NULLIF(b.bank_name, ''), NULLIF(cp.bank_name, ''), '-') as bank_name,
                        COALESCE(NULLIF(b.account_name, ''), '-') as branch_name,
                        '' as routing_number,
                        COALESCE(NULLIF(b.account_number, ''), NULLIF(cp.bank_account_no, ''), '-') as account_number,
                        COALESCE(cr.purpose_paid_to, cp.purpose_paid_to, 'Cashout Request') as description,
                        cp.transaction_ref
                    FROM cashout_payments cp
                    LEFT JOIN company_bank_accounts b ON b.id = cp.bank_id
                    LEFT JOIN cashout_requests cr ON cr.id = cp.cashout_request_id
                    WHERE cp.payment_method = 'bank'
                    AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    AND DATE(cp.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
            if ($isHeadOffice) {
                $cpSql .= " AND COALESCE(cr.project_id, cp.project_id, 0) = 0";
            } elseif ($isSpecificProj) {
                $cpSql .= " AND (cr.project_id = ? OR cp.project_id = ?)";
                $params[] = $pid;
                $params[] = $pid;
                $types .= "ii";
            }
            $parts[] = $cpSql;

            // 2. Management cash-out via bank
            if (!$isSpecificProj) {
                $mcoSql = "SELECT 
                            DATE(mco.transaction_date) as payment_date,
                            mco.amount,
                            COALESCE(NULLIF(mco.bank_name, ''), '-') as bank_name,
                            '-' as branch_name,
                            '' as routing_number,
                            COALESCE(NULLIF(mco.bank_account_no, ''), '-') as account_number,
                            CONCAT('Management Cash-Out: ', COALESCE(mco.paid_to, mco.description, 'N/A')) as description,
                            mco.transaction_ref
                        FROM management_cashout mco
                        WHERE mco.payment_method = 'bank'
                        AND mco.payment_status = 'paid'
                        AND DATE(mco.transaction_date) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
                $parts[] = $mcoSql;
            }

            // 3. Construction Vendor Payments, Contractor Bills & Operational Fund via bank/cheque
            $cftSql = "SELECT 
                        DATE(cft.transaction_date) as payment_date,
                        cft.amount_paid as amount,
                        '-' as bank_name,
                        '-' as branch_name,
                        '' as routing_number,
                        '-' as account_number,
                        CONCAT(UPPER(REPLACE(cft.ref_type, '_', ' ')), ': ', COALESCE(cft.notes, 'Disbursement')) as description,
                        cft.reference_number as transaction_ref
                    FROM con_financial_transactions cft
                    WHERE LOWER(cft.payment_method) IN ('bank', 'cheque', 'beftn', 'rtgs', 'npsb', 'bank_transfer')
                    AND cft.ref_type IN ('vendor_payment', 'contractor_bill', 'operational_fund')
                    AND DATE(cft.transaction_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
            if ($isHeadOffice) {
                $cftSql .= " AND (cft.project_id IS NULL OR cft.project_id = 0)";
            } elseif ($isSpecificProj) {
                $cftSql .= " AND cft.project_id = ?";
                $params[] = $pid;
                $types .= "i";
            }
            $parts[] = $cftSql;

            // 4. Fund Requisitions via bank
            $frSql = "SELECT 
                        DATE(fr.paid_at) as payment_date,
                        fr.amount,
                        '-' as bank_name,
                        '-' as branch_name,
                        '' as routing_number,
                        '-' as account_number,
                        CONCAT('Fund Requisition: ', COALESCE(fr.purpose, fr.category, 'N/A')) as description,
                        '' as transaction_ref
                    FROM fund_requisitions fr
                    WHERE LOWER(fr.payment_method) IN ('bank', 'cheque', 'bank_transfer')
                    AND fr.status = 'paid'
                    AND DATE(fr.paid_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
            if ($isHeadOffice) {
                $frSql .= " AND (fr.project_id IS NULL OR fr.project_id = 0)";
            } elseif ($isSpecificProj) {
                $frSql .= " AND fr.project_id = ?";
                $params[] = $pid;
                $types .= "i";
            }
            $parts[] = $frSql;

            // 5. Land Fund Requisitions via bank
            if (!$isHeadOffice) {
                $lfrSql = "SELECT 
                            DATE(lfr.accounts_paid_at) as payment_date,
                            lfr.requested_amount as amount,
                            '-' as bank_name,
                            '-' as branch_name,
                            '' as routing_number,
                            '-' as account_number,
                            CONCAT('Land Fund Requisition: ', COALESCE(lfr.purpose, lfr.cost_purpose, 'N/A')) as description,
                            lfr.transaction_no as transaction_ref
                        FROM land_fund_requisitions lfr
                        LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
                        WHERE LOWER(lfr.payment_method) IN ('bank', 'cheque', 'bank_transfer')
                        AND lfr.mgmt_status = 'Approved'
                        AND lfr.accounts_status = 'Paid'
                        AND DATE(lfr.accounts_paid_at) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
                if ($isSpecificProj) {
                    $lfrSql .= " AND la.project_id = ?";
                    $params[] = $pid;
                    $types .= "i";
                }
                $parts[] = $lfrSql;
            }

            // 6. Office Procurement Assets via bank
            $peaSql = "SELECT 
                        DATE(pea.paid_at) as payment_date,
                        pea.paid_amount as amount,
                        '-' as bank_name,
                        '-' as branch_name,
                        '' as routing_number,
                        '-' as account_number,
                        CONCAT('Office Procurement: ', COALESCE(pea.item_name, 'Asset')) as description,
                        pea.invoice_no as transaction_ref
                    FROM project_extra_assets pea
                    WHERE LOWER(pea.payment_method) IN ('bank', 'cheque', 'bank_transfer')
                    AND pea.payment_status = 'paid'
                    AND DATE(pea.paid_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
            if ($isHeadOffice) {
                $peaSql .= " AND (pea.project_id IS NULL OR pea.project_id = 0)";
            } elseif ($isSpecificProj) {
                $peaSql .= " AND pea.project_id = ?";
                $params[] = $pid;
                $types .= "i";
            }
            $parts[] = $peaSql;

            // 7. Expense adjustment cash-out via bank
            $eaOutSql = "SELECT 
                            DATE(at.payment_date) as payment_date,
                            at.amount,
                            COALESCE(NULLIF(b.bank_name, ''), '-') as bank_name,
                            COALESCE(NULLIF(b.account_name, ''), '-') as branch_name,
                            '' as routing_number,
                            COALESCE(NULLIF(b.account_number, ''), NULLIF(at.bank_account_no, ''), '-') as account_number,
                            CONCAT('Expense Adj. Payout: ', COALESCE(ea.title, 'Adjustment')) as description,
                            at.transaction_ref
                        FROM adjustment_transactions at
                        JOIN expense_adjustments ea ON at.adjustment_id = ea.id
                        LEFT JOIN company_bank_accounts b ON b.id = at.bank_id
                        WHERE at.transaction_type = 'cashout'
                        AND at.payment_method = 'bank'
                        AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
                        AND DATE(at.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
            if ($isHeadOffice) {
                $eaOutSql .= " AND (ea.project_id IS NULL OR ea.project_id = 0)";
            } elseif ($isSpecificProj) {
                $eaOutSql .= " AND ea.project_id = ?";
                $params[] = $pid;
                $types .= "i";
            }
            $parts[] = $eaOutSql;

            $sql = "SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") combined_bank_out ORDER BY payment_date DESC";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("getBankTransactions prepare error: " . $this->db->error);
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        $stmt->close();

        return $transactions;
    }

    /**
     * Helper: Extract row value for a single category
     */
    private function extractRowValueForCategory(array $row, string $category): float
    {
        switch ($category) {
            case 'total_cashin':
                return (float)($row['total_cashin'] ?? 0);
            case 'emi':
                return (float)($row['emi_payments'] ?? 0);
            case 'landshare':
                return (float)($row['landshare_payments'] ?? 0);
            case 'service_fee':
                return (float)($row['service_fee'] ?? 0);
            case 'registration':
                return (float)($row['registration'] ?? $row['registration_payments'] ?? 0);
            case 'booking_money':
                return (float)($row['booking_money'] ?? 0);
            case 'development_charge':
                return (float)($row['development_charge'] ?? 0);
            case 'agreement':
                return (float)($row['agreement_value'] ?? 0);
            case 'management_cashin':
                return (float)($row['management_cashin'] ?? 0);
            case 'expense_adjustment_cashin':
                return (float)($row['expense_adjustment_cashin'] ?? 0);
            case 'procurement_returns_cashin':
                return (float)($row['procurement_returns_cashin'] ?? 0);
            case 'cashout':
            case 'utilities':
            case 'other':
            case 'vendor_payment':
            case 'contractor_bill':
            case 'office_procurement_assets':
            case 'assets_repairing':
            case 'fund_requisitions':
                return (float)($row['cash_out'] ?? 0);
            default:
                return (float)($row['total_cashin'] ?? 0);
        }
    }

    /**
     * Helper: Format detail items as a single text string for CSV export
     */
    private function formatCsvDetailsForRow(string $category, string $dateOrMonthPrefix, bool $isMonthly, array $cashoutDetails, array $vendorDetails, array $contractorDetails): string
    {
        $items = [];
        if ($category === 'cashout') {
            $source = $cashoutDetails;
        } elseif ($category === 'vendor_payment') {
            $source = $vendorDetails;
        } elseif ($category === 'contractor_bill') {
            $source = $contractorDetails;
        } else {
            return '';
        }

        $matchedPayments = [];
        if ($isMonthly) {
            foreach ($source as $dt => $list) {
                if (strpos((string)$dt, $dateOrMonthPrefix) === 0) {
                    foreach ($list as $p) {
                        $matchedPayments[] = $p;
                    }
                }
            }
        } else {
            $matchedPayments = $source[$dateOrMonthPrefix] ?? [];
        }

        foreach ($matchedPayments as $p) {
            if ($category === 'cashout') {
                $label = $p['purpose_paid_to'] ?? '—';
                $sub = !empty($p['project_name']) ? " ({$p['project_name']})" : '';
            } elseif ($category === 'vendor_payment') {
                $label = $p['vendor_name'] ?? '—';
                $sub = !empty($p['project_name']) ? " ({$p['project_name']})" : '';
            } else {
                $label = $p['contractor_name'] ?? '—';
                $sub = !empty($p['project_name']) ? " ({$p['project_name']})" : '';
            }
            $method = ucfirst($p['payment_method'] ?? 'cash');
            $amt = number_format((float)($p['amount'] ?? 0), 2, '.', '');
            $items[] = "{$label}{$sub} [{$method}: TK.{$amt}]";
        }

        return implode(' | ', $items);
    }

    /**
     * Export statement to CSV
     */
    public function exportCSV()
    {
        $periodType = $_GET['period_type'] ?? 'monthly';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = $_GET['month'] ?? date('m');
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : null;
        $projectId = $this->parseProjectId($_GET['project_id'] ?? null);
        $category = $_GET['category'] ?? 'all';

        if (empty($_GET['period_type']) && $startDate && $endDate) {
            $periodType = 'custom';
        }

        $projectName = $this->resolveProjectName($projectId);
        $categoryName = $this->resolveCategoryName($category);

        [$statementData, $periodType, $periodText, $statementStartDate, $statementEndDate] =
            $this->resolveStatementByFilters($periodType, $year, $month, $startDate, $endDate, $projectId, $category);

        if ($periodType === 'all_time') {
            $filename = "all_time_statement_" . date('Ymd') . ".csv";
        } elseif ($periodType === 'custom' && $startDate && $endDate) {
            $cleanStartDate = str_replace('-', '', $startDate);
            $cleanEndDate = str_replace('-', '', $endDate);
            $filename = "custom_statement_{$cleanStartDate}_to_{$cleanEndDate}.csv";
        } elseif ($periodType === '6months') {
            $mInt = ($month === 'all' || empty($month) || (int)$month < 1 || (int)$month > 12) ? (int)date('m') : (int)$month;
            $filename = "6months_statement_{$year}_{$mInt}.csv";
        } elseif ($periodType === 'yearly' || $month === 'all' || $month === '0' || $month === 0) {
            $filename = "yearly_statement_{$year}.csv";
        } else {
            $mInt = (int)$month;
            $filename = "monthly_statement_{$year}_" . sprintf('%02d', $mInt) . ".csv";
        }

        if (!$statementData) {
            setError('Unable to generate statement');
            header("Location: {$GLOBALS['base_url']}/central-statement");
            exit;
        }

        // Fetch detail maps if needed for CSV export
        $cashoutDetails = ($category === 'cashout' && $statementStartDate && $statementEndDate)
            ? $this->statementModel->getCashoutPaymentDetails($statementStartDate, $statementEndDate, $projectId)
            : [];
        $vendorDetails = ($category === 'vendor_payment' && $statementStartDate && $statementEndDate)
            ? $this->statementModel->getVendorPaymentDetails($statementStartDate, $statementEndDate, $projectId)
            : [];
        $contractorDetails = ($category === 'contractor_bill' && $statementStartDate && $statementEndDate)
            ? $this->statementModel->getContractorBillDetails($statementStartDate, $statementEndDate, $projectId)
            : [];

        // Set CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Output UTF-8 BOM so Excel opens properly without character encoding issues
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write header
        fputcsv($output, ['CENTRAL FINANCIAL STATEMENT']);
        fputcsv($output, ['Period', $statementData['period'] ?? $periodText]);
        fputcsv($output, ['Start Date', $statementData['start_date'] ?? $statementStartDate]);
        fputcsv($output, ['End Date', $statementData['end_date'] ?? $statementEndDate]);
        if ($projectName) {
            fputcsv($output, ['Project', $projectName]);
        }
        if ($categoryName) {
            fputcsv($output, ['Category', $categoryName]);
        }
        fputcsv($output, ['Generated', date('d M, Y h:i A')]);
        fputcsv($output, []);

        $hasDetailsCol = in_array($category, ['cashout', 'vendor_payment', 'contractor_bill'], true);

        // Bank transactions export
        if (in_array($category, ['bank_cashin', 'bank_cashout'], true)) {
            $bankTransactions = $this->getBankTransactions($category, $statementData['start_date'] ?? $statementStartDate, $statementData['end_date'] ?? $statementEndDate, $projectId);
            fputcsv($output, [
                '#',
                'Date',
                'Bank Name',
                'Branch',
                'Routing No',
                'Account No',
                $category === 'bank_cashin' ? 'Client / Payment Type' : 'Purpose / Paid To',
                $category === 'bank_cashin' ? 'Cash In (TK.)' : 'Withdrawal (TK.)'
            ]);
            $bankTotal = 0;
            foreach ($bankTransactions as $idx => $bt) {
                $amt = (float)($bt['amount'] ?? 0);
                $bankTotal += $amt;
                fputcsv($output, [
                    $idx + 1,
                    $bt['payment_date'] ?? '',
                    $bt['bank_name'] ?? '-',
                    $bt['branch_name'] ?? '-',
                    $bt['routing_number'] ?? '-',
                    $bt['account_number'] ?? '-',
                    $bt['description'] ?? '-',
                    number_format($amt, 2, '.', '')
                ]);
            }
            fputcsv($output, []);
            fputcsv($output, ['Grand Total', '', '', '', '', '', '', number_format($bankTotal, 2, '.', '')]);
        }
        // Daily breakdown
        elseif (isset($statementData['daily_data']) && is_array($statementData['daily_data'])) {
            if ($category === 'all') {
                fputcsv($output, [
                    'Date',
                    'Day',
                    'Booking Money (TK.)',
                    'Service Fee (TK.)',
                    'EMI Payments (TK.)',
                    'Landshare Payments (TK.)',
                    'Registration (TK.)',
                    'Development Charge (TK.)',
                    'Management Cashin (TK.)',
                    'Total Cash IN (TK.)',
                    'Cash Out (TK.)',
                    'Net Amount (TK.)',
                    'Running Balance (TK.)'
                ]);
                foreach ($statementData['daily_data'] as $row) {
                    fputcsv($output, [
                        $row['formatted_date'] ?? $row['date'] ?? '',
                        $row['day_name'] ?? '',
                        number_format((float)($row['booking_money'] ?? 0), 2, '.', ''),
                        number_format((float)($row['service_fee'] ?? 0), 2, '.', ''),
                        number_format((float)($row['emi_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['landshare_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['registration'] ?? $row['registration_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['development_charge'] ?? 0), 2, '.', ''),
                        number_format((float)($row['management_cashin'] ?? 0), 2, '.', ''),
                        number_format((float)($row['total_cashin'] ?? 0), 2, '.', ''),
                        number_format((float)($row['cash_out'] ?? 0), 2, '.', ''),
                        number_format((float)($row['net_amount'] ?? 0), 2, '.', ''),
                        number_format((float)($row['running_balance'] ?? 0), 2, '.', '')
                    ]);
                }
            } else {
                $catCol = ($categoryName ?: ucfirst(str_replace('_', ' ', $category))) . ' (TK.)';
                $headers = ['Date', 'Day', $catCol];
                if ($hasDetailsCol) {
                    $headers[] = 'Details / Purpose';
                }
                fputcsv($output, $headers);
                foreach ($statementData['daily_data'] as $row) {
                    $val = $this->extractRowValueForCategory($row, $category);
                    $csvRow = [
                        $row['formatted_date'] ?? $row['date'] ?? '',
                        $row['day_name'] ?? '',
                        number_format($val, 2, '.', ''),
                    ];
                    if ($hasDetailsCol) {
                        $rowDate = $row['date'] ?? '';
                        $csvRow[] = $this->formatCsvDetailsForRow($category, $rowDate, false, $cashoutDetails, $vendorDetails, $contractorDetails);
                    }
                    fputcsv($output, $csvRow);
                }
            }
        }
        // Monthly breakdown
        elseif (isset($statementData['monthly_data']) && is_array($statementData['monthly_data'])) {
            if ($category === 'all') {
                fputcsv($output, [
                    'Month',
                    'Booking Money (TK.)',
                    'Service Fee (TK.)',
                    'EMI Payments (TK.)',
                    'Landshare Payments (TK.)',
                    'Registration (TK.)',
                    'Development Charge (TK.)',
                    'Management Cashin (TK.)',
                    'Total Cash IN (TK.)',
                    'Cash Out (TK.)',
                    'Net Amount (TK.)',
                    'Running Balance (TK.)'
                ]);
                foreach ($statementData['monthly_data'] as $row) {
                    fputcsv($output, [
                        $row['month'] ?? '',
                        number_format((float)($row['booking_money'] ?? 0), 2, '.', ''),
                        number_format((float)($row['service_fee'] ?? 0), 2, '.', ''),
                        number_format((float)($row['emi_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['landshare_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['registration_payments'] ?? $row['registration'] ?? 0), 2, '.', ''),
                        number_format((float)($row['development_charge'] ?? 0), 2, '.', ''),
                        number_format((float)($row['management_cashin'] ?? 0), 2, '.', ''),
                        number_format((float)($row['total_cashin'] ?? 0), 2, '.', ''),
                        number_format((float)($row['cash_out'] ?? 0), 2, '.', ''),
                        number_format((float)($row['net_amount'] ?? 0), 2, '.', ''),
                        number_format((float)($row['running_balance'] ?? 0), 2, '.', '')
                    ]);
                }
            } else {
                $catCol = ($categoryName ?: ucfirst(str_replace('_', ' ', $category))) . ' (TK.)';
                $headers = ['Month', $catCol];
                if ($hasDetailsCol) {
                    $headers[] = 'Details / Purpose';
                }
                fputcsv($output, $headers);
                foreach ($statementData['monthly_data'] as $row) {
                    $val = $this->extractRowValueForCategory($row, $category);
                    $csvRow = [
                        $row['month'] ?? '',
                        number_format($val, 2, '.', ''),
                    ];
                    if ($hasDetailsCol) {
                        $monthPrefix = ($row['year'] ?? '') . '-' . sprintf('%02d', (int)($row['month_num'] ?? 0));
                        $csvRow[] = $this->formatCsvDetailsForRow($category, $monthPrefix, true, $cashoutDetails, $vendorDetails, $contractorDetails);
                    }
                    fputcsv($output, $csvRow);
                }
            }
        }

        // Compute summary totals matching the web view
        $summary_stats = $statementData['summary'] ?? [];

        $calc_booking_money = (float)($summary_stats['total_booking_money'] ?? 0);
        $calc_registration_fee = (float)($summary_stats['total_registration_payments'] ?? $summary_stats['total_registration'] ?? 0);
        $calc_landshare_money = (float)($summary_stats['total_landshare_payments'] ?? $summary_stats['total_landshare'] ?? 0);
        $calc_development_charge = (float)($summary_stats['total_development_charge'] ?? 0);
        $calc_cashin = (float)($summary_stats['total_cashin'] ?? 0);
        $calc_cashout = (float)($summary_stats['total_cash_out'] ?? 0);

        if (isset($statementData['daily_data']) && is_array($statementData['daily_data'])) {
            $r_booking = 0; $r_reg = 0; $r_land = 0; $r_dev = 0; $r_cashin = 0; $r_cashout = 0;
            foreach ($statementData['daily_data'] as $r) {
                $r_booking += (float)($r['booking_money'] ?? 0);
                $r_reg += (float)($r['registration'] ?? $r['registration_payments'] ?? 0);
                $r_land += (float)($r['landshare_payments'] ?? 0);
                $r_dev += (float)($r['development_charge'] ?? 0);
                $r_cashin += (float)($r['total_cashin'] ?? 0);
                $r_cashout += (float)($r['cash_out'] ?? 0);
            }
            if ($calc_booking_money == 0) $calc_booking_money = $r_booking;
            if ($calc_registration_fee == 0) $calc_registration_fee = $r_reg;
            if ($calc_landshare_money == 0) $calc_landshare_money = $r_land;
            if ($calc_development_charge == 0) $calc_development_charge = $r_dev;
            if ($calc_cashin == 0) $calc_cashin = $r_cashin;
            if ($calc_cashout == 0) $calc_cashout = $r_cashout;
        } elseif (isset($statementData['monthly_data']) && is_array($statementData['monthly_data'])) {
            $r_booking = 0; $r_reg = 0; $r_land = 0; $r_dev = 0; $r_cashin = 0; $r_cashout = 0;
            foreach ($statementData['monthly_data'] as $r) {
                $r_booking += (float)($r['booking_money'] ?? 0);
                $r_reg += (float)($r['registration_payments'] ?? $r['registration'] ?? 0);
                $r_land += (float)($r['landshare_payments'] ?? 0);
                $r_dev += (float)($r['development_charge'] ?? 0);
                $r_cashin += (float)($r['total_cashin'] ?? 0);
                $r_cashout += (float)($r['cash_out'] ?? 0);
            }
            if ($calc_booking_money == 0) $calc_booking_money = $r_booking;
            if ($calc_registration_fee == 0) $calc_registration_fee = $r_reg;
            if ($calc_landshare_money == 0) $calc_landshare_money = $r_land;
            if ($calc_development_charge == 0) $calc_development_charge = $r_dev;
            if ($calc_cashin == 0) $calc_cashin = $r_cashin;
            if ($calc_cashout == 0) $calc_cashout = $r_cashout;
        }

        $calc_net_balance = $calc_cashin - $calc_cashout;

        // Write summary
        fputcsv($output, []);
        fputcsv($output, ['PERIOD SUMMARY']);
        fputcsv($output, ['Total Cash IN', number_format($calc_cashin, 2, '.', '')]);
        fputcsv($output, ['Total Land Share Money', number_format($calc_landshare_money, 2, '.', '')]);
        fputcsv($output, ['Total Booking Money', number_format($calc_booking_money, 2, '.', '')]);
        fputcsv($output, ['Total Registration Fee', number_format($calc_registration_fee, 2, '.', '')]);
        fputcsv($output, ['Total Development Charge', number_format($calc_development_charge, 2, '.', '')]);
        fputcsv($output, ['Total Cash Out', number_format($calc_cashout, 2, '.', '')]);
        fputcsv($output, ['Net Balance', number_format($calc_net_balance, 2, '.', '')]);

        fclose($output);
        exit;
    }

    /**
     * AJAX: Get quick stats
     */
    public function getQuickStats()
    {
        header('Content-Type: application/json');

        $stats = $this->statementModel->getOverallStatistics();

        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        exit;
    }

    public function getRevenueStatistics()
    {
        header('Content-Type: application/json');

        $periodType = $_GET['period_type'] ?? 'monthly';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $projectId = $this->parseProjectId($_GET['project_id'] ?? null);

        $revenueStats = $this->statementModel->getRevenueStatistics(
            $periodType,
            $year,
            $month,
            $startDate,
            $endDate,
            $projectId
        );

        echo json_encode([
            'success' => true,
            'data' => $revenueStats
        ]);
        exit;
    }
}
