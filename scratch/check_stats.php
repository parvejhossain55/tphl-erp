<?php
$_SERVER['DOCUMENT_ROOT'] = 'C:/xampp/htdocs';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Dashboard.php';
require_once __DIR__ . '/../app/models/CentralStatement.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$dashboard = new Dashboard($db);
$central = new CentralStatement($db);

$dash_stats = $dashboard->getOverviewStatistics('all_time');
$central_stats = $central->getOverallStatistics('all_time');

echo "DASHBOARD ALL TIME:\n";
echo "Total Cash IN: " . $dash_stats['total_cashin'] . "\n";
echo "Total Cash OUT: " . $dash_stats['total_cashout'] . "\n";
echo "Total Revenue: " . $dash_stats['total_revenue'] . "\n";

echo "\nCENTRAL STATEMENT ALL TIME:\n";
echo "Total Cash IN: " . $central_stats['total_cashin'] . "\n";
echo "Total Cash OUT: " . $central_stats['total_cash_out'] . "\n";
echo "Total Revenue: " . $central_stats['total_revenue'] . "\n";
