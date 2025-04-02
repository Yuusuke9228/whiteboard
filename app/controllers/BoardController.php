<?php
// app/controllers/BoardController.php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Board.php';
require_once __DIR__ . '/../models/User.php';

class BoardController extends Controller
{
    private $boardModel;
    private $userModel;

    public function __construct()
    {
        $this->boardModel = new Board();
        $this->userModel = new User();
    }

    /**
     * ボードの一覧表示
     *
     * @return void
     */
    public function index()
    {
        // 認証チェック
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }

        $userId = $_SESSION['user_id'];

        // 所有しているボードを取得
        $ownedBoards = $this->boardModel->where('owner_id', $userId);

        // 共有されているボードを取得
        $db = Database::getInstance();
        $sharedBoards = $db->fetchAll(
            "SELECT b.*, u.username as owner_username, bs.permission 
            FROM boards b 
            JOIN board_shares bs ON b.id = bs.board_id 
            JOIN users u ON b.owner_id = u.id
            WHERE bs.user_id = ?",
            [$userId]
        );

        $this->view('boards/index', [
            'ownedBoards' => $ownedBoards,
            'sharedBoards' => $sharedBoards
        ]);
    }

    /**
     * ボードの詳細表示
     *
     * @param int $id ボードID
     * @return void
     */
    public function show($id)
    {
        // 認証と権限チェック
        $userId = $this->authenticateAndAuthorize($id);
        if (!$userId) return;

        $board = $this->boardModel->find($id);
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

        $this->view('boards/show', [
            'board' => $board,
            'owner' => $owner,
            'sharedUsers' => $sharedUsers
        ]);
    }

    /**
     * ボード作成フォームの表示
     *
     * @return void
     */
    public function create()
    {
        // 認証チェック
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }

        $this->view('boards/create');
    }

    /**
     * ボードの保存処理
     *
     * @return void
     */
    public function store()
    {
        // 認証チェック
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $title = $_POST['title'] ?? '';
        $backgroundColor = $_POST['background_color'] ?? '#F5F5F5';
        $isPublic = isset($_POST['is_public']) ? (bool)$_POST['is_public'] : false;

        // バリデーション
        $errors = $this->validate(
            ['title' => $title],
            ['title' => 'required|min:3|max:255']
        );

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return $this->redirect('/boards/create');
        }

        $boardId = $this->boardModel->create([
            'title' => $title,
            'owner_id' => $userId,
            'background_color' => $backgroundColor,
            'is_public' => $isPublic
        ]);

        if (!$boardId) {
            $_SESSION['errors'] = ['general' => ['Failed to create board']];
            return $this->redirect('/boards/create');
        }

        return $this->redirect("/boards/{$boardId}");
    }

    /**
     * ボード編集フォームの表示
     *
     * @param int $id ボードID
     * @return void
     */
    public function edit($id)
    {
        // 管理者権限チェック
        $userId = $this->authenticateAndAuthorize($id, 'admin');
        if (!$userId) return;

        $board = $this->boardModel->find($id);

        $this->view('boards/edit', [
            'board' => $board
        ]);
    }

    /**
     * ボードの更新処理
     *
     * @param int $id ボードID
     * @return void
     */
    public function update($id)
    {
        // 管理者権限チェック
        $userId = $this->authenticateAndAuthorize($id, 'admin');
        if (!$userId) return;

        $title = $_POST['title'] ?? null;
        $backgroundColor = $_POST['background_color'] ?? null;
        $isPublic = isset($_POST['is_public']) ? (bool)$_POST['is_public'] : null;

        $data = [];

        if ($title !== null) {
            $errors = $this->validate(
                ['title' => $title],
                ['title' => 'required|min:3|max:255']
            );

            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                return $this->redirect("/boards/{$id}/edit");
            }

            $data['title'] = $title;
        }

        if ($backgroundColor !== null) {
            $data['background_color'] = $backgroundColor;
        }

        if ($isPublic !== null) {
            $data['is_public'] = $isPublic;
        }

        if (empty($data)) {
            $_SESSION['errors'] = ['general' => ['No data to update']];
            return $this->redirect("/boards/{$id}/edit");
        }

        $result = $this->boardModel->update($id, $data);

        if (!$result) {
            $_SESSION['errors'] = ['general' => ['Failed to update board']];
            return $this->redirect("/boards/{$id}/edit");
        }

        return $this->redirect("/boards/{$id}");
    }

    /**
     * ボードの削除処理
     *
     * @param int $id ボードID
     * @return void
     */
    public function destroy($id)
    {
        // 所有者のみ削除可能
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $board = $this->boardModel->find($id);

        if (!$board) {
            $_SESSION['errors'] = ['general' => ['Board not found']];
            return $this->redirect('/boards');
        }

        if ($board['owner_id'] != $userId) {
            $_SESSION['errors'] = ['auth' => ['You do not have permission to delete this board']];
            return $this->redirect('/boards');
        }

        $result = $this->boardModel->delete($id);

        if (!$result) {
            $_SESSION['errors'] = ['general' => ['Failed to delete board']];
            return $this->redirect("/boards/{$id}");
        }

        $_SESSION['success'] = 'Board deleted successfully';
        return $this->redirect('/boards');
    }

    /**
     * ボードの共有処理
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function share($boardId)
    {
        // 管理者権限チェック
        $userId = $this->authenticateAndAuthorize($boardId, 'admin');
        if (!$userId) return;

        $email = $_POST['email'] ?? '';
        $permission = $_POST['permission'] ?? 'view';

        if (empty($email)) {
            $_SESSION['errors'] = ['email' => ['Email is required']];
            return $this->redirect("/boards/{$boardId}");
        }

        if (!in_array($permission, ['view', 'edit', 'admin'])) {
            $_SESSION['errors'] = ['permission' => ['Invalid permission']];
            return $this->redirect("/boards/{$boardId}");
        }

        // ユーザーの検索
        $targetUser = $this->userModel->findByEmail($email);

        if (!$targetUser) {
            $_SESSION['errors'] = ['email' => ['User not found']];
            return $this->redirect("/boards/{$boardId}");
        }

        // 自分自身には共有できない
        if ($targetUser['id'] == $userId) {
            $_SESSION['errors'] = ['email' => ['Cannot share with yourself']];
            return $this->redirect("/boards/{$boardId}");
        }

        // ボードの所有者には共有できない
        $board = $this->boardModel->find($boardId);
        if ($targetUser['id'] == $board['owner_id']) {
            $_SESSION['errors'] = ['email' => ['Cannot share with the owner']];
            return $this->redirect("/boards/{$boardId}");
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
                $_SESSION['errors'] = ['general' => ['Failed to update share permission']];
                return $this->redirect("/boards/{$boardId}");
            }

            $_SESSION['success'] = 'Share permission updated successfully';
        } else {
            // 新規共有
            $result = $db->insert('board_shares', [
                'board_id' => $boardId,
                'user_id' => $targetUser['id'],
                'permission' => $permission
            ]);

            if (!$result) {
                $_SESSION['errors'] = ['general' => ['Failed to share board']];
                return $this->redirect("/boards/{$boardId}");
            }

            $_SESSION['success'] = 'Board shared successfully';
        }

        return $this->redirect("/boards/{$boardId}");
    }

    /**
     * ボードの共有解除処理
     *
     * @param int $boardId ボードID
     * @param int $targetUserId 対象ユーザーID
     * @return void
     */
    public function unshare($boardId, $targetUserId)
    {
        // 管理者権限チェック
        $userId = $this->authenticateAndAuthorize($boardId, 'admin');
        if (!$userId) return;

        // 存在確認
        $db = Database::getInstance();
        $share = $db->fetch(
            "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ?",
            [$boardId, $targetUserId]
        );

        if (!$share) {
            $_SESSION['errors'] = ['general' => ['Share not found']];
            return $this->redirect("/boards/{$boardId}");
        }

        // 共有解除
        $result = $db->delete(
            'board_shares',
            'board_id = ? AND user_id = ?',
            [$boardId, $targetUserId]
        );

        if (!$result) {
            $_SESSION['errors'] = ['general' => ['Failed to unshare board']];
            return $this->redirect("/boards/{$boardId}");
        }

        $_SESSION['success'] = 'Board unshared successfully';
        return $this->redirect("/boards/{$boardId}");
    }

    /**
     * 認証と権限チェックを行う
     *
     * @param int $boardId ボードID
     * @param string $permission 必要な権限 (view/edit/admin)
     * @return int|false ユーザーIDまたはfalse
     */
    private function authenticateAndAuthorize($boardId, $permission = 'view')
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['errors'] = ['auth' => ['Please login to continue']];
            $this->redirect('/login');
            return false;
        }

        $userId = $_SESSION['user_id'];
        $board = $this->boardModel->find($boardId);

        if (!$board) {
            $_SESSION['errors'] = ['general' => ['Board not found']];
            $this->redirect('/boards');
            return false;
        }

        // 所有者の場合は常に許可
        if ($board['owner_id'] == $userId) {
            return $userId;
        }

        // 公開ボードで閲覧権限のみ必要な場合
        if ($permission === 'view' && $board['is_public']) {
            return $userId;
        }

        // 共有設定の確認
        $db = Database::getInstance();

        if ($permission === 'view') {
            $share = $db->fetch(
                "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ?",
                [$boardId, $userId]
            );

            if ($share) {
                return $userId;
            }
        } else if ($permission === 'edit') {
            $share = $db->fetch(
                "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ? AND permission IN ('edit', 'admin')",
                [$boardId, $userId]
            );

            if ($share) {
                return $userId;
            }
        } else if ($permission === 'admin') {
            $share = $db->fetch(
                "SELECT * FROM board_shares WHERE board_id = ? AND user_id = ? AND permission = 'admin'",
                [$boardId, $userId]
            );

            if ($share) {
                return $userId;
            }
        }

        $_SESSION['errors'] = ['auth' => ['You do not have permission to access this resource']];
        $this->redirect('/boards');
        return false;
    }
}
