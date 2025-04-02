<?php
// websocket/handlers/BoardHandler.php
use Ratchet\ConnectionInterface;

class BoardHandler
{
    protected $server;
    protected $db;

    public function __construct($server)
    {
        $this->server = $server;
        $this->db = Database::getInstance();
    }

    /**
     * オブジェクト作成イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleObjectCreated(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['object'])) {
            return;
        }

        $boardId = $data['boardId'];
        $object = $data['object'];

        // 権限チェック
        if (!$this->checkPermission($from, $boardId, 'edit')) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Permission denied'
            ]));
            return;
        }

        // 他のクライアントに通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'object_created',
            'boardId' => $boardId,
            'object' => $object,
            'userId' => $from->userId
        ], $from);
    }

    /**
     * オブジェクト更新イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleObjectUpdated(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['object'])) {
            return;
        }

        $boardId = $data['boardId'];
        $object = $data['object'];

        // 権限チェック
        if (!$this->checkPermission($from, $boardId, 'edit')) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Permission denied'
            ]));
            return;
        }

        // 他のクライアントに通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'object_updated',
            'boardId' => $boardId,
            'object' => $object,
            'userId' => $from->userId
        ], $from);
    }

    /**
     * オブジェクト削除イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleObjectDeleted(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['objectId'])) {
            return;
        }

        $boardId = $data['boardId'];
        $objectId = $data['objectId'];

        // 権限チェック
        if (!$this->checkPermission($from, $boardId, 'edit')) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Permission denied'
            ]));
            return;
        }

        // 他のクライアントに通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'object_deleted',
            'boardId' => $boardId,
            'objectId' => $objectId,
            'userId' => $from->userId
        ], $from);
    }

    /**
     * カーソル移動イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleCursorMove(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['position'])) {
            return;
        }

        $boardId = $data['boardId'];
        $position = $data['position'];

        // 他のクライアントに通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'cursor_move',
            'boardId' => $boardId,
            'userId' => $from->userId,
            'username' => $from->username,
            'position' => $position
        ], $from);
    }

    /**
     * コメント追加イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleCommentAdded(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['comment'])) {
            return;
        }

        $boardId = $data['boardId'];
        $comment = $data['comment'];

        // 権限チェック
        if (!$this->checkPermission($from, $boardId, 'view')) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Permission denied'
            ]));
            return;
        }

        // 他のクライアントに通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'comment_added',
            'boardId' => $boardId,
            'comment' => $comment,
            'userId' => $from->userId,
            'username' => $from->username
        ], $from);
    }

    /**
     * コメント削除イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleCommentDeleted(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['commentId'])) {
            return;
        }

        $boardId = $data['boardId'];
        $commentId = $data['commentId'];

        // 権限チェック（自分のコメントまたは管理者権限）
        $sql = "SELECT user_id FROM comments WHERE id = ?";
        $comment = $this->db->fetch($sql, [$commentId]);

        if (!$comment) {
            return;
        }

        if ($comment['user_id'] != $from->userId && !$this->checkPermission($from, $boardId, 'admin')) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Permission denied'
            ]));
            return;
        }

        // 他のクライアントに通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'comment_deleted',
            'boardId' => $boardId,
            'commentId' => $commentId,
            'userId' => $from->userId
        ], $from);
    }

    /**
     * ズーム変更イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleZoomChanged(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['scale'])) {
            return;
        }

        $boardId = $data['boardId'];
        $scale = $data['scale'];
        $offsetX = $data['offsetX'] ?? 0;
        $offsetY = $data['offsetY'] ?? 0;

        // ユーザー自身のみに関連するデータなので、他のユーザーには送信しない
        // ただし、将来的に「講師のビューを共有」などの機能を実装する場合は
        // この情報を他のユーザーに送ることもある
    }

    /**
     * 編集セッション開始イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleEditSessionStart(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['objectId'])) {
            return;
        }

        $boardId = $data['boardId'];
        $objectId = $data['objectId'];

        // 権限チェック
        if (!$this->checkPermission($from, $boardId, 'edit')) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Permission denied'
            ]));
            return;
        }

        // 他のクライアントに通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'edit_session_start',
            'boardId' => $boardId,
            'objectId' => $objectId,
            'userId' => $from->userId,
            'username' => $from->username
        ], $from);
    }

    /**
     * 編集セッション終了イベントの処理
     *
     * @param ConnectionInterface $from 送信元の接続
     * @param array $data メッセージデータ
     * @return void
     */
    public function handleEditSessionEnd(ConnectionInterface $from, $data)
    {
        if (!isset($data['boardId']) || !isset($data['objectId'])) {
            return;
        }

        $boardId = $data['boardId'];
        $objectId = $data['objectId'];

        // 他のクライアントに通知
        $this->server->broadcastToBoardExcept($boardId, [
            'type' => 'edit_session_end',
            'boardId' => $boardId,
            'objectId' => $objectId,
            'userId' => $from->userId
        ], $from);
    }

    /**
     * 権限をチェックする
     *
     * @param ConnectionInterface $conn 接続
     * @param int $boardId ボードID
     * @param string $permission 必要な権限
     * @return bool 権限があるかどうか
     */
    protected function checkPermission(ConnectionInterface $conn, $boardId, $permission = 'view')
    {
        if (!isset($conn->userId)) {
            return false;
        }

        $userId = $conn->userId;

        // ボード情報を取得
        $sql = "SELECT * FROM boards WHERE id = ?";
        $board = $this->db->fetch($sql, [$boardId]);

        if (!$board) {
            return false;
        }

        // 所有者の場合は常に許可
        if ($board['owner_id'] == $userId) {
            return true;
        }

        // 公開ボードで閲覧権限のみ必要な場合
        if ($permission === 'view' && $board['is_public']) {
            return true;
        }

        // 共有設定の確認
        if ($permission === 'view') {
            $sql = "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ?";
            $share = $this->db->fetch($sql, [$boardId, $userId]);

            if ($share) {
                return true;
            }
        } else if ($permission === 'edit') {
            $sql = "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ? AND permission IN ('edit', 'admin')";
            $share = $this->db->fetch($sql, [$boardId, $userId]);

            if ($share) {
                return true;
            }
        } else if ($permission === 'admin') {
            $sql = "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ? AND permission = 'admin'";
            $share = $this->db->fetch($sql, [$boardId, $userId]);

            if ($share) {
                return true;
            }
        }

        return false;
    }
}
