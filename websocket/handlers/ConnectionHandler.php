<?php
// websocket/handlers/ConnectionHandler.php
use Ratchet\ConnectionInterface;

class ConnectionHandler
{
    protected $server;
    protected $db;

    public function __construct($server)
    {
        $this->server = $server;
        $this->db = Database::getInstance();
    }

    /**
     * ユーザーがボードに参加した時の処理
     *
     * @param ConnectionInterface $conn WebSocket接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleJoin(ConnectionInterface $conn, $data)
    {
        if (!isset($data['boardId']) || !isset($data['userId'])) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Missing required fields: boardId or userId'
            ]));
            return;
        }

        $boardId = $data['boardId'];
        $userId = $data['userId'];

        // ユーザー情報を取得
        $sql = "SELECT id, username, avatar_url FROM users WHERE id = ?";
        $user = $this->db->fetch($sql, [$userId]);

        if (!$user) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'User not found'
            ]));
            return;
        }

        // ボード情報を取得
        $sql = "SELECT * FROM boards WHERE id = ?";
        $board = $this->db->fetch($sql, [$boardId]);

        if (!$board) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Board not found'
            ]));
            return;
        }

        // アクセス権のチェック
        if ($board['owner_id'] != $userId && !$board['is_public']) {
            // 共有設定を確認
            $sql = "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ?";
            $share = $this->db->fetch($sql, [$boardId, $userId]);

            if (!$share) {
                $conn->send(json_encode([
                    'type' => 'error',
                    'message' => 'Access denied'
                ]));
                return;
            }
        }

        // 接続をボードに関連付け
        if (!isset($this->server->boards[$boardId])) {
            $this->server->boards[$boardId] = new \SplObjectStorage;
        }
        $this->server->boards[$boardId]->attach($conn);

        // ユーザーとボードの関連付け
        $conn->boardId = $boardId;
        $conn->userId = $userId;
        $conn->username = $user['username'];
        $conn->avatar = $user['avatar_url'] ?? null;

        // ユーザーとボードの関連マップに追加
        if (!isset($this->server->userBoards[$userId])) {
            $this->server->userBoards[$userId] = [];
        }
        $this->server->userBoards[$userId][$boardId] = $conn;

        // ボード内の他のユーザーに参加を通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'user_joined',
            'userId' => $userId,
            'username' => $user['username'],
            'avatar' => $user['avatar_url'] ?? null
        ], $conn);

        // 接続ユーザーにボード情報を送信
        $conn->send(json_encode([
            'type' => 'board_joined',
            'boardId' => $boardId,
            'board' => $board
        ]));

        // 接続ユーザーに現在のユーザーリストを送信
        $activeUsers = [];
        if (isset($this->server->boards[$boardId])) {
            foreach ($this->server->boards[$boardId] as $client) {
                if ($client !== $conn && isset($client->userId)) {
                    $activeUsers[] = [
                        'userId' => $client->userId,
                        'username' => $client->username,
                        'avatar' => $client->avatar
                    ];
                }
            }
        }

        $conn->send(json_encode([
            'type' => 'active_users',
            'users' => $activeUsers
        ]));

        echo "User {$user['username']} (ID: {$userId}) joined board {$boardId}\n";
    }

    /**
     * ユーザーがボードを離れた時の処理
     *
     * @param ConnectionInterface $conn WebSocket接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleLeave(ConnectionInterface $conn, $data)
    {
        if (!isset($data['boardId']) || !isset($data['userId'])) {
            return;
        }

        $boardId = $data['boardId'];
        $userId = $data['userId'];

        $this->removeUserFromBoard($conn, $boardId, $userId);
    }

    /**
     * 接続が切断された時の処理
     *
     * @param ConnectionInterface $conn WebSocket接続
     * @return void
     */
    public function handleDisconnect(ConnectionInterface $conn)
    {
        // 接続しているボードから削除
        if (isset($conn->boardId) && isset($conn->userId)) {
            $this->removeUserFromBoard($conn, $conn->boardId, $conn->userId);
        }
    }

    /**
     * ユーザーをボードから削除する
     *
     * @param ConnectionInterface $conn WebSocket接続
     * @param int $boardId ボードID
     * @param int $userId ユーザーID
     * @return void
     */
    protected function removeUserFromBoard(ConnectionInterface $conn, $boardId, $userId)
    {
        // ボードから接続を削除
        if (isset($this->server->boards[$boardId])) {
            $this->server->boards[$boardId]->detach($conn);

            // ボードが空になった場合は削除
            if ($this->server->boards[$boardId]->count() === 0) {
                unset($this->server->boards[$boardId]);
            } else {
                // 他のユーザーに退出を通知
                $username = $conn->username ?? 'Unknown';
                $this->server->broadcastToBoard($boardId, [
                    'type' => 'user_left',
                    'userId' => $userId,
                    'username' => $username
                ]);
            }
        }

        // ユーザーとボードの関連を削除
        if (isset($this->server->userBoards[$userId][$boardId])) {
            unset($this->server->userBoards[$userId][$boardId]);

            // ユーザーが他のボードに接続していない場合はマップから削除
            if (empty($this->server->userBoards[$userId])) {
                unset($this->server->userBoards[$userId]);
            }
        }

        echo "User ID: {$userId} left board {$boardId}\n";
    }
}
