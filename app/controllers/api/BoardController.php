<?php
// app/controllers/api/BoardController.php
require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../models/Board.php';
require_once __DIR__ . '/../../models/User.php';

class BoardApiController extends ApiController
{
    protected $boardModel;
    protected $userModel;

    public function __construct()
    {
        $this->boardModel = new Board();
        $this->userModel = new User();
    }

    public function index()
    {
        $userId = $this->authenticate();

        // 所有しているボードを取得
        $ownedBoards = $this->boardModel->where('owner_id', $userId);

        // 共有されているボードを取得
        $db = Database::getInstance();
        $sharedBoards = $db->fetchAll(
            "SELECT b.* FROM boards b 
            JOIN board_shares bs ON b.id = bs.board_id 
            WHERE bs.user_id = ?",
            [$userId]
        );

        return $this->success([
            'owned_boards' => $ownedBoards,
            'shared_boards' => $sharedBoards
        ]);
    }

    public function show($id)
    {
        $userId = $this->authorize($id);

        $board = $this->boardModel->find($id);

        if (!$board) {
            return $this->error('Board not found', 404);
        }

        // 所有者情報を取得
        $owner = $this->userModel->find($board['owner_id']);

        // 共有ユーザー情報を取得
        $db = Database::getInstance();
        $sharedUsers = $db->fetchAll(
            "SELECT u.id, u.username, u.email, bs.permission 
            FROM users u 
            JOIN board_shares bs ON u.id = bs.user_id 
            WHERE bs.board_id = ?",
            [$id]
        );

        return $this->success([
            'board' => $board,
            'owner' => [
                'id' => $owner['id'],
                'username' => $owner['username'],
                'email' => $owner['email']
            ],
            'shared_users' => $sharedUsers
        ]);
    }

    public function store()
    {
        $userId = $this->authenticate();

        $title = $_POST['title'] ?? '';
        $backgroundColor = $_POST['background_color'] ?? '#F5F5F5';
        $isPublic = isset($_POST['is_public']) ? (bool)$_POST['is_public'] : false;

        if (empty($title)) {
            return $this->error('Title is required');
        }

        $boardId = $this->boardModel->create([
            'title' => $title,
            'owner_id' => $userId,
            'background_color' => $backgroundColor,
            'is_public' => $isPublic
        ]);

        if (!$boardId) {
            return $this->error('Failed to create board');
        }

        $board = $this->boardModel->find($boardId);

        return $this->success([
            'board' => $board,
            'board_id' => $boardId
        ], 'Board created successfully');
    }

    public function update($id)
    {
        $userId = $this->authorize($id, 'edit');

        $title = $_POST['title'] ?? null;
        $backgroundColor = $_POST['background_color'] ?? null;
        $isPublic = isset($_POST['is_public']) ? (bool)$_POST['is_public'] : null;

        $data = [];

        if ($title !== null) {
            $data['title'] = $title;
        }

        if ($backgroundColor !== null) {
            $data['background_color'] = $backgroundColor;
        }

        if ($isPublic !== null) {
            $data['is_public'] = $isPublic;
        }

        if (empty($data)) {
            return $this->error('No data to update');
        }

        $result = $this->boardModel->update($id, $data);

        if (!$result) {
            return $this->error('Failed to update board');
        }

        $board = $this->boardModel->find($id);

        return $this->success([
            'board' => $board
        ], 'Board updated successfully');
    }

    public function destroy($id)
    {
        // 所有者のみ削除可能
        $userId = $this->authenticate();

        $board = $this->boardModel->find($id);

        if (!$board) {
            return $this->error('Board not found', 404);
        }

        if ($board['owner_id'] != $userId) {
            return $this->error('Unauthorized', 403);
        }

        $result = $this->boardModel->delete($id);

        if (!$result) {
            return $this->error('Failed to delete board');
        }

        return $this->success([], 'Board deleted successfully');
    }

    public function share($boardId)
    {
        // 所有者または管理者のみ共有可能
        $userId = $this->authorize($boardId, 'admin');

        $targetEmail = $_POST['email'] ?? '';
        $permission = $_POST['permission'] ?? 'view';

        if (empty($targetEmail)) {
            return $this->error('Email is required');
        }

        if (!in_array($permission, ['view', 'edit', 'admin'])) {
            return $this->error('Invalid permission');
        }

        // ユーザーの検索
        $targetUser = $this->userModel->findByEmail($targetEmail);

        if (!$targetUser) {
            return $this->error('User not found');
        }

        // 自分自身には共有できない
        if ($targetUser['id'] == $userId) {
            return $this->error('Cannot share with yourself');
        }

        // ボードの所有者には共有できない
        $board = $this->boardModel->find($boardId);
        if ($targetUser['id'] == $board['owner_id']) {
            return $this->error('Cannot share with the owner');
        }

        // 既に共有されているか確認
        $db = Database::getInstance();
        $existingShare = $db->fetch(
            "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ?",
            [$boardId, $targetUser['id']]
        );

        if ($existingShare) {
            // 権限の更新
            $result = $db->update(
                'board_shares',
                ['permission' => $permission],
                'board_id = ? AND user_id = ?',
                [$boardId, $targetUser['id']]
            );

            if (!$result) {
                return $this->error('Failed to update share permission');
            }

            return $this->success([], 'Share permission updated successfully');
        } else {
            // 新規共有
            $result = $db->insert('board_shares', [
                'board_id' => $boardId,
                'user_id' => $targetUser['id'],
                'permission' => $permission
            ]);

            if (!$result) {
                return $this->error('Failed to share board');
            }

            return $this->success([
                'user' => [
                    'id' => $targetUser['id'],
                    'username' => $targetUser['username'],
                    'email' => $targetUser['email'],
                    'permission' => $permission
                ]
            ], 'Board shared successfully');
        }
    }

    public function unshare($boardId, $targetUserId)
    {
        // 所有者または管理者のみ共有解除可能
        $userId = $this->authorize($boardId, 'admin');

        // 存在確認
        $db = Database::getInstance();
        $share = $db->fetch(
            "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ?",
            [$boardId, $targetUserId]
        );

        if (!$share) {
            return $this->error('Share not found', 404);
        }

        // 共有解除
        $result = $db->delete(
            'board_shares',
            'board_id = ? AND user_id = ?',
            [$boardId, $targetUserId]
        );

        if (!$result) {
            return $this->error('Failed to unshare board');
        }

        return $this->success([], 'Board unshared successfully');
    }
}