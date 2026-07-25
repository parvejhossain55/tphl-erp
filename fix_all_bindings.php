<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// Let's replace the bindings for getDailyCashOut, getMonthlyCashOutQuery, getCustomCashOutQuery, and getAccountsData

// 1. getDailyCashOut (around line 2670)
$daily_old = <<<'PHP'
        if ($categoryId) {
            // With category ID (for management_cashout with specific category)
            $params[] = $date;
            $params[] = $categoryId;
            $types = "si";
        } else if (($category === 'all' || $category === 'cashout') && $projectId) {
            // All categories with project filter: date, date, projectId, projectId, date, date, projectId
            $params = [$date, $date, $projectId, $projectId, $date, $date, $projectId];
            $types = "ssiissi";
        } else if ($category === 'all') {
            // All categories: date, date, date, date
            $params = [$date, $date, $date, $date];
            $types = "ssss";
        } else {
            // Other categories (emi, landshare, service_fee, etc.) - no cashout for these
            // Just management cashout with no category filter
            $params = [$date];
            $types = "s";
        }
PHP;
$daily_new = <<<'PHP'
        if ($categoryId) {
            $params[] = $date;
            $params[] = $categoryId;
            $types = "si";
        } else if (($category === 'all' || $category === 'cashout') && $projectId) {
            $params = [$date, $date, $projectId, $projectId, $date, $date, $projectId, $date, $projectId, $date, $projectId, $date, $projectId];
            $types = "ssiissisisisi";
        } else if ($category === 'all' || $category === 'cashout') {
            $params = [$date, $date, $date, $date, $date, $date, $date];
            $types = "sssssss";
        } else {
            $params = [$date];
            $types = "s";
        }
PHP;
$content = str_replace($daily_old, $daily_new, $content);

// 2. getMonthlyCashOutQuery (around line 2960)
$monthly_old = <<<'PHP'
        if ($categoryId) {
            // With category ID: start, end, categoryId
            $params = [$startDate, $endDate, $categoryId];
            $types = "ssi";
        } else if (($category === 'all' || $category === 'cashout') && $projectId) {
            // All categories with project filter
            $params = [$startDate, $endDate, $startDate, $endDate, $projectId, $projectId, $startDate, $endDate, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId];
            $types = "ssssiiissssissssissssi";
        } else if ($category === 'all') {
            // All categories: start, end, start, end, start, end, start, end, start, end, start, end
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $types = "ssssssssssss";
        } else {
            // Other categories (emi, landshare, service_fee, etc.) - just management cashout
            $params = [$startDate, $endDate];
            $types = "ss";
        }
PHP;
$monthly_new = <<<'PHP'
        if ($categoryId) {
            $params = [$startDate, $endDate, $categoryId];
            $types = "ssi";
        } else if (($category === 'all' || $category === 'cashout') && $projectId) {
            $params = [$startDate, $endDate, $startDate, $endDate, $projectId, $projectId, $startDate, $endDate, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId];
            $types = "ssssiisssississississi";
        } else if ($category === 'all' || $category === 'cashout') {
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $types = "ssssssssssssss";
        } else {
            $params = [$startDate, $endDate];
            $types = "ss";
        }
PHP;
$content = str_replace($monthly_old, $monthly_new, $content);

// 3. getCustomCashOutQuery (around 3270)
$custom_old = <<<'PHP'
        if ($categoryId) {
            // With category ID: start, end, categoryId
            $params = [$startDate, $endDate, $categoryId];
            $types = "ssi";
        } else if (($category === 'all' || $category === 'cashout') && $projectId) {
            // All categories with project filter
            $params = [$startDate, $endDate, $startDate, $endDate, $projectId, $projectId, $startDate, $endDate, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId];
            $types = "ssssiiissssissssissssi";
        } else if ($category === 'all') {
            // All categories: start, end, start, end, start, end, start, end, start, end, start, end
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $types = "ssssssssssss";
        } else {
            // Other categories (emi, landshare, service_fee, etc.) - just management cashout
            $params = [$startDate, $endDate];
            $types = "ss";
        }
PHP;
$custom_new = <<<'PHP'
        if ($categoryId) {
            $params = [$startDate, $endDate, $categoryId];
            $types = "ssi";
        } else if (($category === 'all' || $category === 'cashout') && $projectId) {
            $params = [$startDate, $endDate, $startDate, $endDate, $projectId, $projectId, $startDate, $endDate, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId, $startDate, $endDate, $projectId];
            $types = "ssssiisssississississi";
        } else if ($category === 'all' || $category === 'cashout') {
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $types = "ssssssssssssss";
        } else {
            $params = [$startDate, $endDate];
            $types = "ss";
        }
PHP;
$content = str_replace($custom_old, $custom_new, $content);

file_put_contents('temp_bindings.php', $content);
