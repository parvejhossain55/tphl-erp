<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\models\Dashboard.php';
$content = file_get_contents($file);

$pattern = "/(SELECT STR_TO_DATE\(CONCAT\(payslip_month, '-01'\), '%Y-%m-%d'\) as date,\s*net_salary as amount, NULL as project_id\s*FROM payslips\s*WHERE status = 'paid'\s*)\) combined/s";

$replace = "$1
                    UNION ALL
                    
                    SELECT COALESCE(updated_at, created_at) as date, COALESCE(approved_amount, bill_amount) as amount, project_id
                    FROM contractor_bill_requests
                    WHERE status IN ('approved', 'paid', 'partially_paid', 'fully_paid')
                    
                    UNION ALL
                    
                    SELECT COALESCE(updated_at, created_at) as date, COALESCE(approved_amount, net_payable) as amount, project_id
                    FROM vendor_payment_requests
                    WHERE status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid')
                ) combined";

$new_content = preg_replace($pattern, $replace, $content);

file_put_contents($file, $new_content);
echo "Replaced pattern.\n";
