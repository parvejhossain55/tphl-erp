<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// Replace getMonthlyCashOutQuery bindings
$monthly_old = <<<'PHP'
        // Bind parameters based on category
        $params = [];
        $types = "";

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

        $stmt->bind_param($types, ...$params);
PHP;

// Let's first check if this text matches EXACTLY in CentralStatement.php
if (strpos($content, '$stmt->bind_param($types, ...$params);') === false) {
    echo "Could not find target block for monthly bindings.\n";
} else {
    echo "Found monthly bindings block.\n";
}
