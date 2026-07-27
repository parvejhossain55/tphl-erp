<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// Replace getMonthlyCashOutQuery bindings dynamically via regex
$pattern = '/\/\/ Bind parameters based on category.*\$stmt->bind_param\(\$types, \.\.\.\$params\);/s';
$replacement = <<<'PHP'
        // Build parameters dynamically
        $params = [];
        $types = "";

        if ($categoryId) {
            $params = [$startDate, $endDate, $categoryId];
            $types = "ssi";
        } else {
            // 1. management_cashout
            $params = array_merge($params, [$startDate, $endDate]);
            $types .= "ss";

            if ($category === 'all' || $category === 'cashout') {
                // 2. cashout_payments
                $params = array_merge($params, [$startDate, $endDate]);
                $types .= "ss";
                if ($projectId) {
                    $params = array_merge($params, [$projectId, $projectId]);
                    $types .= "ii";
                }

                // 3. payslips
                $params = array_merge($params, [$startDate, $endDate]);
                $types .= "ss";

                // 4. con_financial_transactions
                $params = array_merge($params, [$startDate, $endDate]);
                $types .= "ss";
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }

                // 5. fund_requisitions
                if ($startDate && $endDate) {
                    $params = array_merge($params, [$startDate, $endDate]);
                    $types .= "ss";
                }
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }

                // 6. land_fund_requisitions
                if ($startDate && $endDate) {
                    $params = array_merge($params, [$startDate, $endDate]);
                    $types .= "ss";
                }
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }

                // 7. project_extra_assets
                if ($startDate && $endDate) {
                    $params = array_merge($params, [$startDate, $endDate]);
                    $types .= "ss";
                }
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }
            }
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
PHP;

$content = preg_replace($pattern, $replacement, $content);

// For Custom Cashout Query:
$pattern_custom = '/\/\/ Build parameters array.*?\$stmt->bind_param\(\$bindTypes, \.\.\.\$bindParams\);\s*\}/s';
$replacement_custom = <<<'PHP'
        // Build parameters dynamically
        $bindParams = [];
        $bindTypes = "";

        if ($categoryId) {
            if ($startDate && $endDate) {
                $bindParams = [$startDate, $endDate, $categoryId];
                $bindTypes = "ssi";
            } else {
                $bindParams = [$categoryId];
                $bindTypes = "i";
            }
        } else {
            // 1. management_cashout
            if ($startDate && $endDate) {
                $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                $bindTypes .= "ss";
            }

            if ($category === 'all' || $category === 'cashout') {
                // 2. cashout_payments
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId, $projectId]);
                    $bindTypes .= "ii";
                }

                // 3. payslips
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }

                // 4. con_financial_transactions
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId]);
                    $bindTypes .= "i";
                }

                // 5. fund_requisitions
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId]);
                    $bindTypes .= "i";
                }

                // 6. land_fund_requisitions
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId]);
                    $bindTypes .= "i";
                }

                // 7. project_extra_assets
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId]);
                    $bindTypes .= "i";
                }
            }
        }

        if (!empty($bindParams)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }
PHP;

$content = preg_replace($pattern_custom, $replacement_custom, $content);

// Fix the undefined array key "total_landshare_payments" around line 1835
$content = str_replace("'total_landshare' => \$totals['total_landshare_payments']", "'total_landshare' => \$totals['landshare_payments']", $content);

file_put_contents('temp_fix_all.php', $content);
