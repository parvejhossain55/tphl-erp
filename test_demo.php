<?php
$config = require 'app/config/config.php';
require 'app/services/IncentiveCalculationEngine.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$engine = new IncentiveCalculationEngine($db);

$userId = 29; // Shafia
$month = date('Y-m');

echo "--- INDIVIDUAL STATS ---\n";
$stats = $engine->getExecutiveMonthlyStats($userId, $month);
print_r($stats);

echo "\n--- TEAM STATS ---\n";
$teamStats = $engine->getTeamMonthlyStats($userId, $month);
print_r($teamStats);
