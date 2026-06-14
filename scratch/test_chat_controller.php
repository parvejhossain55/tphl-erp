<?php

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/helpers/SqlValidator.php';
require_once __DIR__ . '/../app/helpers/DbSchemaHelper.php';
require_once __DIR__ . '/../app/ai/AiBrain.php';
require_once __DIR__ . '/../app/controllers/Ai/ChatController.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config
$config = require __DIR__ . '/../app/config/config.php';

// Establish primary connection
$database = new Database($config);
$db = $GLOBALS['db'] = $database->conn;

// Mock session
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1
];

// Mock HTTP POST body
$mockInput = json_encode(['message' => 'hi']);
// We need to override php://input in PHP, but since it is read-only, we can mock it by setting input stream or overriding how handleMessage reads it.
// Wait! Let's check how ChatController reads php://input:
// $input = json_decode(file_get_contents('php://input'), true);
// Since file_get_contents('php://input') cannot be mocked easily, we can write a test method or run a local web server to test it.
// Or we can just run the code line-by-line in our script!

echo "Mocking ChatController execution:\n";
$controller = new \Ai\ChatController($db);

// Instead of calling handleMessage() directly (which reads from php://input), we can write the logic inside the controller here to see if any class resolution fails:
try {
    $message = "hi";
    $roleId = $_SESSION['user']['role_id'] ?? 0;
    echo "Calling AiBrain::handleMessage...\n";
    $reply = \ai\AiBrain::handleMessage($message, $roleId, $db);
    echo "Response: " . $reply . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
