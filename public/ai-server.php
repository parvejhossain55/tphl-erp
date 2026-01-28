<?php
require __DIR__ . '/../vendor/autoload.php';   // Composer autoload
require __DIR__ . '/../app/bootstrap.php';      // bootstrap
require __DIR__ . '/../app/ai/SocketServer.php';   // SocketServer
require __DIR__ . '/../app/ai/ContextManager.php'; // ContextManager

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use ai\SocketServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SocketServer()
        )
    ),
    8081
);

echo "AI WebSocket running at //localhost:8080\n";
$server->run();


