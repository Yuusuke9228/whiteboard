<?php
// app/controllers/ObjectController.php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/BoardObject.php';
require_once __DIR__ . '/../models/Board.php';

class ObjectController extends Controller
{
    private $objectModel;
    private $boardModel;

    public function __construct()
    {
        $this->objectModel = new BoardObject();
        $this->boardModel = new Board();
    }

    /**
     * オブジェクトの一覧を表示
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function index($boardId)
    {
        // 権限チェック
        $userId = $this->authenticateAndAuthorize($boardId);
        if (!$userId) return;

        $board = $this->boardModel->find($boardId);
        $objects = $this->objectModel->where('board_id', $boardId);

        $this->view('objects/index', [
            'board' => $board,
            'objects' => $objects
        ]);
    }

    /**
     * オブジェクトの詳細表示
     *
     * @param int $boardId ボードID
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function show($boardId, $objectId)
    {
        // 権限チェック
        $userId = $this->authenticateAndAuthorize($boardId);
        if (!$userId) return;

        $board = $this->boardModel->find($boardId);
        $object = $this->objectModel->find($objectId);

        if (!$object || $object['board_id'] != $boardId) {
            $_SESSION['errors'] = ['general' => ['Object not found']];
            return $this->redirect("/boards/{$boardId}");
        }

        $this->view('objects/show', [
            'board' => $board,
            'object' => $object
        ]);
    }

    /**
     * オブジェクト作成フォームの表示
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function create($boardId)
    {
        // 編集権限チェック
        $userId = $this->authenticateAndAuthorize($boardId, 'edit');
        if (!$userId) return;

        $board = $this->boardModel->find($boardId);

        $this->view('objects/create', [
            'board' => $board
        ]);
    }

    /**
     * オブジェクトの保存処理
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function store($boardId)
    {
        // 編集権限チェック
        $userId = $this->authenticateAndAuthorize($boardId, 'edit');
        if (!$userId) return;

        $type = $_POST['type'] ?? '';
        $content = $_POST['content'] ?? null;
        $properties = $_POST['properties'] ?? '{}';

        // バリデーション
        $errors = $this->validate(
            [
                'type' => $type
            ],
            [
                'type' => 'required'
            ]
        );

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return $this->redirect("/boards/{$boardId}/objects/create");
        }

        // 型チェック
        if (!in_array($type, ['text', 'note', 'shape', 'line', 'connector'])) {
            $_SESSION['errors'] = ['type' => ['Invalid object type']];
            return $this->redirect("/boards/{$boardId}/objects/create");
        }

        $objectId = $this->objectModel->create([
            'board_id' => $boardId,
            'user_id' => $userId,
            'type' => $type,
            'content' => $content,
            'properties' => $properties
        ]);

        if (!$objectId) {
            $_SESSION['errors'] = ['general' => ['Failed to create object']];
            return $this->redirect("/boards/{$boardId}/objects/create");
        }

        // WebSocketメッセージの送信ロジックはフロントエンドで処理

        return $this->redirect("/boards/{$boardId}");
    }

    /**
     * オブジェクト編集フォームの表示
     *
     * @param int $boardId ボードID
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function edit($boardId, $objectId)
    {
        // 編集権限チェック
        $userId = $this->authenticateAndAuthorize($boardId, 'edit');
        if (!$userId) return;

        $board = $this->boardModel->find($boardId);
        $object = $this->objectModel->find($objectId);

        if (!$object || $object['board_id'] != $boardId) {
            $_SESSION['errors'] = ['general' => ['Object not found']];
            return $this->redirect("/boards/{$boardId}");
        }

        $this->view('objects/edit', [
            'board' => $board,
            'object' => $object
        ]);
    }

    /**
     * オブジェクトの更新処理
     *
     * @param int $boardId ボードID
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function update($boardId, $objectId)
    {
        // 編集権限チェック
        $userId = $this->authenticateAndAuthorize($boardId, 'edit');
        if (!$userId) return;

        $object = $this->objectModel->find($objectId);

        if (!$object || $object['board_id'] != $boardId) {
            $_SESSION['errors'] = ['general' => ['Object not found']];
            return $this->redirect("/boards/{$boardId}");
        }

        $type = $_POST['type'] ?? null;
        $content = $_POST['content'] ?? null;
        $properties = $_POST['properties'] ?? null;

        $data = [];

        if ($type !== null) {
            if (!in_array($type, ['text', 'note', 'shape', 'line', 'connector'])) {
                $_SESSION['errors'] = ['type' => ['Invalid object type']];
                return $this->redirect("/boards/{$boardId}/objects/{$objectId}/edit");
            }
            $data['type'] = $type;
        }

        if ($content !== null) {
            $data['content'] = $content;
        }

        if ($properties !== null) {
            $data['properties'] = $properties;
        }

        if (empty($data)) {
            $_SESSION['errors'] = ['general' => ['No data to update']];
            return $this->redirect("/boards/{$boardId}/objects/{$objectId}/edit");
        }

        $result = $this->objectModel->update($objectId, $data);

        if (!$result) {
            $_SESSION['errors'] = ['general' => ['Failed to update object']];
            return $this->redirect("/boards/{$boardId}/objects/{$objectId}/edit");
        }

        // WebSocketメッセージの送信ロジックはフロントエンドで処理

        return $this->redirect("/boards/{$boardId}");
    }

    /**
     * オブジェクトの削除処理
     *
     * @param int $boardId ボードID
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function destroy($boardId, $objectId)
    {
        // 編集権限チェック
        $userId = $this->authenticateAndAuthorize($boardId, 'edit');
        if (!$userId) return;

        $object = $this->objectModel->find($objectId);

        if (!$object || $object['board_id'] != $boardId) {
            $_SESSION['errors'] = ['general' => ['Object not found']];
            return $this->redirect("/boards/{$boardId}");
        }

        $result = $this->objectModel->delete($objectId);

        if (!$result) {
            $_SESSION['errors'] = ['general' => ['Failed to delete object']];
            return $this->redirect("/boards/{$boardId}");
        }

        // WebSocketメッセージの送信ロジックはフロントエンドで処理

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
