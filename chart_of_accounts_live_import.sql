-- ==========================================
-- PERFECT CHART OF ACCOUNTS EXPORT FOR LIVE DB
-- Generated: 2026-08-03 14:47:58
-- ==========================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE chart_of_accounts;

INSERT INTO `chart_of_accounts` (`id`, `parent_id`, `project_id`, `account_code`, `account_name`, `account_type`, `level`, `root_type`, `status`, `is_system_account`) VALUES
(1, NULL, NULL, '1000', 'Assets', 'Asset', 'main', 'asset', 'active', 1),
(2, NULL, NULL, '2000', 'Liabilities', 'Liability', 'main', 'liability', 'active', 1),
(3, NULL, NULL, '3000', 'Equity', 'Equity', 'main', 'equity', 'active', 1),
(4, NULL, NULL, '4000', 'Revenue', 'Revenue', 'main', 'income', 'active', 1),
(5, NULL, NULL, '5000', 'Expenses', 'Expense', 'main', 'expense', 'active', 1),
(10, 1, NULL, '1100', 'Current Assets', 'Asset', 'sub', 'asset', 'active', 1),
(11, 1, NULL, '1200', 'Non-Current & Fixed Assets', 'Asset', 'sub', 'asset', 'active', 1),
(12, 1, NULL, '1300', 'Construction Work-In-Progress (WIP)', 'Asset', 'sub', 'asset', 'active', 1),
(20, 2, NULL, '2100', 'Current Liabilities', 'Liability', 'sub', 'liability', 'active', 1),
(21, 2, NULL, '2200', 'Unearned Revenue & Customer Advances', 'Liability', 'sub', 'liability', 'active', 1),
(30, 3, NULL, '3100', 'Owner\'s Capital & Retained Earnings', 'Equity', 'sub', 'equity', 'active', 1),
(40, 4, NULL, '4100', 'Real Estate Operating Revenue', 'Revenue', 'sub', 'income', 'active', 1),
(41, 4, NULL, '4200', 'Other Operating Income', 'Revenue', 'sub', 'income', 'active', 1),
(50, 5, NULL, '5100', 'Direct Construction Expenses', 'Expense', 'sub', 'expense', 'active', 1),
(51, 5, NULL, '5200', 'Administrative & Payroll Expenses', 'Expense', 'sub', 'expense', 'active', 1),
(101, 10, NULL, '1110', 'Cash and Bank Accounts', 'Asset', 'parent', 'asset', 'active', 1),
(102, 10, NULL, '1120', 'Accounts & Client Receivable', 'Asset', 'parent', 'asset', 'active', 1),
(103, 12, NULL, '1310', 'Project Civil Construction WIP', 'Asset', 'parent', 'asset', 'active', 1),
(104, 12, NULL, '1320', 'Project Material Inventory WIP', 'Asset', 'parent', 'asset', 'active', 1),
(201, 20, NULL, '2110', 'Accounts Payable - Vendors', 'Liability', 'parent', 'liability', 'active', 1),
(202, 20, NULL, '2120', 'Accounts Payable - Contractors', 'Liability', 'parent', 'liability', 'active', 1),
(203, 20, NULL, '2130', 'Employee Advances & Fund Payable', 'Liability', 'parent', 'liability', 'active', 1),
(204, 21, NULL, '2210', 'Client Land Share Advances', 'Liability', 'parent', 'liability', 'active', 1),
(205, 21, NULL, '2220', 'Client Unit Booking Advances', 'Liability', 'parent', 'liability', 'active', 1),
(301, 30, NULL, '3110', 'Paid-up Capital', 'Equity', 'parent', 'equity', 'active', 1),
(302, 30, NULL, '3120', 'Retained Earnings', 'Equity', 'parent', 'equity', 'active', 1),
(401, 40, NULL, '4110', 'Company Service Charge Revenue (10%)', 'Revenue', 'parent', 'income', 'active', 1),
(402, 40, NULL, '4120', 'Land & Unit Share Revenue', 'Revenue', 'parent', 'income', 'active', 1),
(501, 50, NULL, '5110', 'Contractor Construction Expense', 'Expense', 'parent', 'expense', 'active', 1),
(502, 50, NULL, '5120', 'Raw Material Purchase Expense', 'Expense', 'parent', 'expense', 'active', 1),
(503, 51, NULL, '5210', 'Salaries & Payroll Expense', 'Expense', 'parent', 'expense', 'active', 1),
(504, 51, NULL, '5220', 'Office & Utilities Expense', 'Expense', 'parent', 'expense', 'active', 1),
(1001, 101, NULL, '1110-001', 'Petty Cash Account', 'Asset', 'ledger', 'asset', 'active', 1),
(1002, 101, NULL, '1110-002', 'Dutch Bangla Bank Main Account', 'Asset', 'ledger', 'asset', 'active', 1),
(1003, 101, NULL, '1110-003', 'Islami Bank Main Account', 'Asset', 'ledger', 'asset', 'active', 1),
(2001, 204, NULL, '2210-001', 'Client Land Share Booking Deposit Fund', 'Liability', 'ledger', 'liability', 'active', 1),
(2002, 205, NULL, '2220-001', 'Client EMI Installment Deposit Fund (90%)', 'Liability', 'ledger', 'liability', 'active', 1),
(4001, 401, NULL, '4110-001', 'Company 10% Service Fee Income', 'Revenue', 'ledger', 'income', 'active', 1),
(5001, 503, NULL, '5210-001', 'Monthly Employee Salaries Expense', 'Expense', 'ledger', 'expense', 'active', 1),
(5002, 501, NULL, '5110-001', 'Site Contractor Bill WIP Expense', 'Expense', 'ledger', 'expense', 'active', 1),
(5003, 502, NULL, '5120-001', 'Material Purchase WIP Expense', 'Expense', 'ledger', 'expense', 'active', 1),
(5004, 21, NULL, '2230', 'Client Development Charge Advances', 'Liability', 'parent', 'liability', 'active', 0),
(5005, 5004, NULL, '2230-001', 'Client Development Charge Deposit Fund', 'Liability', 'ledger', 'liability', 'active', 0),
(5007, 101, NULL, '1110-004', 'City Bank Main Account', 'Asset', 'ledger', 'asset', 'active', 0),
(5008, 101, NULL, '1110-10', 'EBL', 'Asset', 'ledger', 'asset', 'active', 0),
(5009, 11, NULL, '1200-001', 'Land & Land Development', 'Asset', 'ledger', 'asset', 'active', 0),
(5010, 11, NULL, '1200-002', 'Building & Office Properties', 'Asset', 'ledger', 'asset', 'active', 0),
(5011, 11, NULL, '1200-003', 'Vehicles & Heavy Equipment', 'Asset', 'ledger', 'asset', 'active', 0),
(5012, 30, NULL, '3100-001', 'Management & Directors Investment', 'Equity', 'ledger', 'equity', 'active', 0);

SET FOREIGN_KEY_CHECKS = 1;
