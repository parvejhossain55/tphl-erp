<?php
namespace ai;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use AiBrain;

// Include Database & services (no namespace)
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/EmployeeService.php';
require_once __DIR__ . '/../services/ProjectService.php';
require_once __DIR__ . '/../services/AccountService.php';

class SocketServer implements MessageComponentInterface
{
    private \SplObjectStorage $clients;
    private \mysqli $db;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();

        $config = require __DIR__ . '/../config/config.php';
        $database = new \Database($config); // no namespace
        $this->db = $database->conn;

        // ⚡ Services are namespace-free, use global namespace
        // \EmployeeService::setDb($this->db);
        // \ProjectService::setDb($this->db);
        // \AccountService::setDb($this->db);

        echo "✅ SocketServer started...\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $id = $conn->resourceId ?? spl_object_id($conn);
        echo "🔌 New connection: {$id}\n";
    }

public function onMessage(ConnectionInterface $from, $msg)
{
    $data = json_decode($msg, true);
    $question = trim($data['message'] ?? '');
    if (!$question) return;

    $roleId = $data['role_id'] ?? 0; // CLI/WebSocket cannot use $_SESSION

    // Typing indicator
    $from->send(json_encode(['reply' => 'typing...']));

    // ⚡ AI Brain call (pass db!)
    $reply = \ai\AiBrain::handleMessage($question, $roleId, $this->db);

    // Send reply only to sender
    $from->send(json_encode(['reply' => $reply]));
}


public function onClose(ConnectionInterface $conn) {
    $this->clients->detach($conn);
    $id = $conn->resourceId ?? spl_object_id($conn);

    if (class_exists('\ai\ContextManager')) {
        \ai\ContextManager::clearContext($id);
    }

    echo "❌ Connection closed: {$id}\n";
}


    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $id = $conn->resourceId ?? spl_object_id($conn);
        echo "🔥 Error {$id}: {$e->getMessage()}\n";
        $conn->close();
    }
}
