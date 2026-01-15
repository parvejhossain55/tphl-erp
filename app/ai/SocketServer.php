<?php
namespace ai;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class SocketServer implements MessageComponentInterface {

    private \SplObjectStorage $clients;
    private ContextManager $contextManager;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
        $this->contextManager = new ContextManager();
        echo "SocketServer started...\n";
    }

    /**
     * @param ConnectionInterface|\Ratchet\WebSocket\WsConnection $conn
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);

        // Resource ID safe handle
        $resourceId = $conn->resourceId ?? spl_object_id($conn);
        echo "New connection: ({$resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $sessionId = $from->resourceId ?? spl_object_id($from);

        $userMessage = trim($data['message'] ?? '');
        if (!$userMessage) return;

        // Add user message to context
        $this->contextManager::addMessage($sessionId, 'user', $userMessage);

        // ====== Call your AI logic / ChatController here ======
        // For now placeholder:
        $reply = "AI Response: " . $userMessage;

        // Add AI reply to context
        $this->contextManager::addMessage($sessionId, 'bot', $reply);

        // Send to all clients (broadcast)
        foreach ($this->clients as $client) {
            $client->send(json_encode(['reply' => $reply]));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $resourceId = $conn->resourceId ?? spl_object_id($conn);
        $this->clients->detach($conn);
        echo "Connection {$resourceId} has disconnected\n";

        $this->contextManager::clearContext($resourceId);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $resourceId = $conn->resourceId ?? spl_object_id($conn);
        echo "Error on connection {$resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }
}
