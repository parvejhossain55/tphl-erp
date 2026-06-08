<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/helper.php';

$root = dirname(__DIR__);
$config = require $root . "/app/config/config.php";

spl_autoload_register(function ($class) use ($root) {
    $class = str_replace('\\', '/', $class);
    $paths = [
        $root . "/app/core/$class.php",
        $root . "/app/models/$class.php",
        $root . "/app/controllers/$class.php",
        $root . "/app/controllers/Phase2/$class.php",
        $root . "/app/services/$class.php",
        $root . "/app/helpers/$class.php",
        $root . "/app/ai/$class.php"
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

$database = new Database($config);
$db = $database->conn;

echo "Reading seed file...\n";
$sqlPath = $root . "/schema/plan/seed_work_types_and_templates.sql";
if (!file_exists($sqlPath)) {
    die("Seed file not found at: $sqlPath\n");
}

$sql = file_get_contents($sqlPath);

echo "Executing SQL statements...\n";
// Multi-query execution since it contains multiple statements
if ($db->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $db->store_result()) {
            $result->free();
        }
    } while ($db->next_result());
    echo "SUCCESS: Seed SQL imported successfully!\n";
} else {
    echo "ERROR: " . $db->error . "\n";
}
