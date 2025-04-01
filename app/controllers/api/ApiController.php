<?php
// app/controllers/api/ApiController.php
class ApiController
{
    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function error($message, $statusCode = 400)
    {
        return $this->json(['error' => $message], $statusCode);
    }

    protected function success($data = [], $message = 'Success')
    {
        return $this->json(array_merge(['success' => true, 'message' => $message], $data));
    }

    protected function authenticate()
    {
        // セッションベースの認証
        session_start();

        if (!isset($_SESSION['user_id'])) {
            $this->error('Unauthorized', 401);
        }

        return $_SESSION['user_id'];
    }

    protected function authorize($boardId, $permission = 'view')
    {
        $userId = $this->authenticate();

        // ボードモデルの読み込み
        require_once __DIR__ . '/../../models/Board.php';

        $boardModel = new Board();
        $board = $boardModel->find($boardId);

        if (!$board) {
            $this->error('Board not found', 404);
        }

        // 所有者の場合は常に許可
        if ($board['owner_id'] == $userId) {
            return $userId;
        }

        // 共有設定の確認
        $db = Database::getInstance();

        if ($permission === 'view') {
            // 公開ボードの場合は閲覧を許可
            if ($board['is_public']) {
                return $userId;
            }

            // 共有設定を確認
            $share = $db->fetch(
                "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ?",
                [$boardId, $userId]
            );

            if ($share) {
                return $userId;
            }
        } else if ($permission === 'edit') {
            // 編集権限の確認
            $share = $db->fetch(
                "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ? AND permission IN ('edit', 'admin')",
                [$boardId, $userId]
            );

            if ($share) {
                return $userId;
            }
        } else if ($permission === 'admin') {
            // 管理者権限の確認
            $share = $db->fetch(
                "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ? AND permission = 'admin'",
                [$boardId, $userId]
            );

            if ($share) {
                return $userId;
            }
        }

        $this->error('Unauthorized', 403);
    }
}
