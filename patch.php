<?php
$file = 'app/models/Dashboard.php';
$content = file_get_contents($file);

// 1. Remove expense_adjustment_cashin from total_cashin
$content = str_replace(
    "+ $stats['management_cashin'] + $stats['expense_adjustment_cashin'];",
    "+ $stats['management_cashin'];",
    $content
);

// 2. Add procurement returns cashin query
$searchStr = "        $stats['expense_adjustment_cashout'] = floatval($row['total']);\n        }\n        $stmt->close();";
$replaceStr = "        $stats['expense_adjustment_cashout'] = floatval($row['total']);\n        }\n        $stmt->close();\n\n        // Get procurement returns cashin\n        $sqlProcReturns = \"\n        SELECT COALESCE(SUM(return_amount), 0) as total\n        FROM project_extra_assets\n        WHERE overall_status = 'purchased'\n        AND return_amount > 0\n        AND purchase_confirmed_at BETWEEN ? AND ?\n    \";\n        if ($projectId) {\n            $sqlProcReturns .= \" AND project_id = ?\";\n        }\n        $stmt = $this->db->prepare($sqlProcReturns);\n        if ($projectId) {\n            $stmt->bind_param(\"ssi\", $startDate, $endDate, $projectId);\n        } else {\n            $stmt->bind_param(\"ss\", $startDate, $endDate);\n        }\n        $stmt->execute();\n        $result = $stmt->get_result();\n        if ($result && $row = $result->fetch_assoc()) {\n            $stats['procurement_returns_cashin'] = floatval($row['total']);\n        }\n        $stmt->close();";

$content = str_replace(str_replace("\n", "\r\n", $searchStr), str_replace("\n", "\r\n", $replaceStr), $content);

// 3. Add project_extra_assets to sqlCashOut
$searchStr2 = "            AND lfr.accounts_status = 'Paid'\n        ) combined";
$replaceStr2 = "            AND lfr.accounts_status = 'Paid'\n            \n            UNION ALL\n            \n            SELECT paid_amount as total_amount, paid_at as date, project_id\n            FROM project_extra_assets\n            WHERE payment_status = 'paid'\n        ) combined";

$content = str_replace(str_replace("\n", "\r\n", $searchStr2), str_replace("\n", "\r\n", $replaceStr2), $content);

// 4. Subtract returns from total_cashout
$searchStr3 = "        if ($result && $row = $result->fetch_assoc()) {\n            $stats['total_cashout'] = floatval($row['total']);\n        }";
$replaceStr3 = "        if ($result && $row = $result->fetch_assoc()) {\n            $stats['total_cashout'] = floatval($row['total']) - ($stats['expense_adjustment_cashin'] ?? 0) - ($stats['procurement_returns_cashin'] ?? 0);\n        }";

$content = str_replace(str_replace("\n", "\r\n", $searchStr3), str_replace("\n", "\r\n", $replaceStr3), $content);


file_put_contents($file, $content);
echo "Dashboard updated.\n";
