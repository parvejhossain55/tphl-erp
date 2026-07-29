<?php
require_once __DIR__ . '/../app/config/config.php';
$GLOBALS['base_url'] = "http://localhost/TPHL-ERP";
require_once __DIR__ . '/../app/controllers/GeneralJournalController.php';

// Mock DB and request
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$controller = new GeneralJournalController($db);
try {
    $controller->create();
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
}
