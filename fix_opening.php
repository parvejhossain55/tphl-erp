<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// Find getOpeningCashOutQuery
$query_to_replace = <<<'SQL'
            -- Vendor payments & contractor bills
            SELECT amount_paid as total_amount
            FROM con_financial_transactions
            WHERE DATE(transaction_date) < ?
            AND ref_type IN ('vendor_payment', 'contractor_bill')
        ) combined
SQL;

$query_replacement = <<<'SQL'
            -- Vendor payments & contractor bills
            SELECT amount_paid as total_amount
            FROM con_financial_transactions
            WHERE DATE(transaction_date) < ?
            AND ref_type IN ('vendor_payment', 'contractor_bill')
            
            UNION ALL
            
            -- Salary payments
            SELECT net_salary as total_amount
            FROM payslips
            WHERE DATE(updated_at) < ?
            AND status = 'paid'
            
            UNION ALL
            
            -- Fund Requisitions
            SELECT amount as total_amount
            FROM fund_requisitions
            WHERE status = 'paid'
            AND DATE(paid_at) < ?
            
            UNION ALL
            
            -- Land Fund Requisitions
            SELECT lfr.requested_amount as total_amount
            FROM land_fund_requisitions lfr
            WHERE lfr.mgmt_status = 'Approved'
            AND lfr.accounts_status = 'Paid'
            AND DATE(lfr.accounts_paid_at) < ?
            
            UNION ALL
            
            -- Office Procurement Assets
            SELECT paid_amount as total_amount
            FROM project_extra_assets
            WHERE payment_status = 'paid'
            AND DATE(paid_at) < ?
        ) combined
SQL;

$content = str_replace($query_to_replace, $query_replacement, $content);
file_put_contents('temp_fix2.php', $content);
