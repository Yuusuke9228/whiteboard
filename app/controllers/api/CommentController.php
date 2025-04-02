<?php
// app/controllers/api/CommentController.php
require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../models/Comment.php';
require_once __DIR__ . '/../../models/Board.php';

class CommentApiController extends ApiController
{
    protected $commentModel;
    protected $boardModel;

    public function __construct()
    {
        $this->commentModel = new Comment();
        $this->boardModel = new Board();
    }

    /**
     * ボードのコメントを取得
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function boardComments($boardId)
    {
        // 権限チェック
        $userId = $this->authorize($boardId);

        // ボードのコメントを取得（関連情報付き）
        $comments = $this->commentModel->findByBoardIdWithRelations($boardId);

        return $this->success([
            'comments' => $comments
        ]);
    }

    /**
     * オブジェクトのコメントを取得
     *
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function objectComments($objectId)
    {
        // オブジェクトを取得
        require_once __DIR__ . '/../../models/BoardObject.php';
        $objectModel = new BoardObject();
        $object = $objectModel->find($objectId);

        if (!$object) {
            return $this->error('Object not found', 404);
        }

        // 権限チェック
        $userId = $this->authorize($object['board_id']);

        // オブジェクトのコメントを取得
        $comments = $this->commentModel->findByObjectId($objectId);

        // ユーザー情報を付加
        require_once __DIR__ . '/../../models/User.php';
        $userModel = new User();

        foreach ($comments as &$comment) {
            $user = $userModel->find($comment['user_id']);
            if ($user) {
                unset($user['password']); // パスワードは除外
                $comment['user'] = $user;
            }
        }

        return $this->success([
            'comments' => $comments
        ]);
    }

    /**
     * ボードにコメントを追加
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function store($boardId)
    {
        // 権限チェック
        $userId = $this->authorize($boardId);

        $content = $_POST['content'] ?? '';
        $positionX = $_POST['position_x'] ?? null;
        $positionY = $_POST['position_y'] ?? null;

        if (empty($content)) {
            return $this->error('Content is required', 400);
        }

        try {
            $commentId = $this->commentModel->create([
                'board_id' => $boardId,
                'user_id' => $userId,
                'content' => $content,
                'position_x' => $positionX,
                'position_y' => $positionY
            ]);

            $comment = $this->commentModel->findWithRelations($commentId);

            // WebSocketメッセージの送信
            $this->sendWebSocketMessage([
                'type' => 'comment_added',
                'boardId' => $boardId,
                'comment' => $comment
            ]);

            return $this->success([
                'comment' => $comment
            ], 'Comment added successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * オブジェクトにコメントを追加
     *
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function storeObjectComment($objectId)
    {
        // オブジェクトを取得
        require_once __DIR__ . '/../../models/BoardObject.php';
        $objectModel = new BoardObject();
        $object = $objectModel->find($objectId);

        if (!$object) {
            return $this->error('Object not found', 404);
        }

        // 権限チェック
        $userId = $this->authorize($object['board_id']);

        $content = $_POST['content'] ?? '';

        if (empty($content)) {
            return $this->error('Content is required', 400);
        }

        try {
            $commentId = $this->commentModel->create([
                'object_id' => $objectId,
                'board_id' => $object['board_id'],
                'user_id' => $userId,
                'content' => $content
            ]);

            $comment = $this->commentModel->findWithRelations($commentId);

            // WebSocketメッセージの送信
            $this->sendWebSocketMessage([
                'type' => 'comment_added',
                'boardId' => $object['board_id'],
                'objectId' => $objectId,
                'comment' => $comment
            ]);

            return $this->success([
                'comment' => $comment
            ], 'Comment added successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * コメントを更新
     *
     * @param int $id コメントID
     * @return void
     */
    public function update($id)
    {
        $comment = $this->commentModel->find($id);

        if (!$comment) {
            return $this->error('Comment not found', 404);
        }

        // 権限チェック（自分のコメントまたは管理者権限）
        $userId = $this->authenticate();

        if ($comment['user_id'] != $userId) {
            // 管理者権限の確認
            try {
                $this->authorize($comment['board_id'], 'admin');
            } catch (Exception $e) {
                return $this->error('You do not have permission to update this comment', 403);
            }
        }

        $content = $_POST['content'] ?? null;

        if ($content === null || empty($content)) {
            return $this->error('Content is required', 400);
        }

        try {
            $result = $this->commentModel->update($id, [
                'content' => $content
            ]);

            if (!$result) {
                return $this->error('Failed to update comment', 500);
            }

            $updatedComment = $this->commentModel->findWithRelations($id);

            // WebSocketメッセージの送信
            $this->sendWebSocketMessage([
                'type' => 'comment_updated',
                'boardId' => $comment['board_id'],
                'objectId' => $comment['object_id'] ?? null,
                'comment' => $updatedComment
            ]);

            return $this->success([
                'comment' => $updatedComment
            ], 'Comment updated successfully');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * コメントを削除
     *
     * @param int $id コメントID
     * @return void
     */
    public function destroy($id)
    {
        $comment = $this->commentModel->find($id);

        if (!$comment) {
            return $this->error('Comment not found', 404);
        }

        // 権限チェック（自分のコメントまたは管理者権限）
        $userId = $this->authenticate();

        if ($comment['user_id'] != $userId) {
            // 管理者権限の確認
            try {
                $this->authorize($comment['board_id'], 'admin');
            } catch (Exception $e) {
                return $this->error('You do not have permission to delete this comment', 403);
            }
        }

        $result = $this->commentModel->delete($id);

        if (!$result) {
            return $this->error('Failed to delete comment', 500);
        }

        // WebSocketメッセージの送信
        $this->sendWebSocketMessage([
            'type' => 'comment_deleted',
            'boardId' => $comment['board_id'],
            'objectId' => $comment['object_id'] ?? null,
            'commentId' => $id
        ]);

        return $this->success([], 'Comment deleted successfully');
    }

    /**
     * WebSocketにメッセージを送信
     *
     * @param array $message 送信するメッセージ
     * @return void
     */
    protected function sendWebSocketMessage($message)
    {
        // WebSocketサーバーへの接続
        $config = require __DIR__ . '/../../../config/config.php';
        $wsConfig = $config['websocket'];

        // この実装ではPHPからWebSocketサーバーにメッセージを直接送信することはできない
        // 実際の環境では、Redis PubSubや他のメッセージングシステムを使用して
        // PHPバックエンドとWebSocketサーバー間で通信する必要がある

        // ここではログに記録するだけ
        error_log('WebSocket message: ' . json_encode($message));
    }
}
