<?php
// websocket/server.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/handlers/ConnectionHandler.php';
require_once __DIR__ . '/handlers/BoardHandler.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $boards;
    protected $userBoards;
    protected $connectionHandler;
    protected $boardHandler;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->boards = [];
        $this->userBoards = [];
        $this->connectionHandler = new ConnectionHandler($this);
        $this->boardHandler = new BoardHandler($this);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (!isset($data['type'])) {
            echo "Message without type: {$msg}\n";
            return;
        }

        switch ($data['type']) {
            case 'join':
                $this->connectionHandler->handleJoin($from, $data);
                break;

            case 'leave':
                $this->connectionHandler->handleLeave($from, $data);
                break;

            case 'object_created':
                $this->boardHandler->handleObjectCreated($from, $data);
                break;

            case 'object_updated':
                $this->boardHandler->handleObjectUpdated($from, $data);
                break;

            case 'object_deleted':
                $this->boardHandler->handleObjectDeleted($from, $data);
                break;

            case 'cursor_move':
                $this->boardHandler->handleCursorMove($from, $data);
                break;

            default:
                echo "Unknown message type: {$data['type']}\n";
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connectionHandler->handleDisconnect($conn);
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function getClients()
    {
        return $this->clients;
    }

    public function getBoards()
    {
        return $this->boards;
    }

    public function getUserBoards()
    {
        return $this->userBoards;
    }

    // ボードに対するメッセージ送信（送信元を除く）
    public function broadcastToBoardExcept($boardId, $message, ConnectionInterface $except)
    {
        if (!isset($this->boards[$boardId])) {
            return;
        }

        foreach ($this->boards[$boardId] as $client) {
            if ($client !== $except) {
                $client->send(json_encode($message));
            }
        }
    }

    // ボードに対するメッセージ送信（全員）
    public function broadcastToBoard($boardId, $message)
    {
        if (!isset($this->boards[$boardId])) {
            return;
        }

        foreach ($this->boards[$boardId] as $client) {
            $client->send(json_encode($message));
        }
    }
}

// WebSocketサーバーの起動
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    8080
);

echo "WebSocket Server started on port 8080\n";

$server->run();
