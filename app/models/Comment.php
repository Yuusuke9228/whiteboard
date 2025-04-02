<?php
// app/models/Comment.php
require_once __DIR__ . '/Model.php';

class Comment extends Model
{
    protected $table = 'comments';
    protected $fillable = ['object_id', 'board_id', 'user_id', 'content', 'position_x', 'position_y'];

    /**
     * コメントの作成時のフック処理
     *
     * @param array $data 作成データ
     * @return int 作成されたレコードのID
     */
    public function create(array $data)
    {
        if (!isset($data['content']) || empty($data['content'])) {
            throw new Exception('Comment content cannot be empty');
        }

        // 必須フィールドの確認
        $requiredFields = ['board_id', 'user_id', 'content'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && empty($data[$field]))) {
                throw new Exception("Field '{$field}' is required");
            }
        }

        // 数値フィールドの確認と変換
        $numericFields = ['position_x', 'position_y'];
        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = floatval($data[$field]);
            }
        }

        return parent::create($data);
    }

    /**
     * コメントの更新時のフック処理
     *
     * @param int $id コメントID
     * @param array $data 更新データ
     * @return bool 更新が成功したかどうか
     */
    public function update($id, array $data)
    {
        // 内容が空の場合はエラー
        if (isset($data['content']) && empty($data['content'])) {
            throw new Exception('Comment content cannot be empty');
        }

        // 数値フィールドの確認と変換
        $numericFields = ['position_x', 'position_y'];
        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = floatval($data[$field]);
            }
        }

        return parent::update($id, $data);
    }

    /**
     * ボードに関連するコメントを取得
     *
     * @param int $boardId ボードID
     * @return array コメントの配列
     */
    public function findByBoardId($boardId)
    {
        return $this->where('board_id', $boardId);
    }

    /**
     * オブジェクトに関連するコメントを取得
     *
     * @param int $objectId オブジェクトID
     * @return array コメントの配列
     */
    public function findByObjectId($objectId)
    {
        return $this->where('object_id', $objectId);
    }

    /**
     * ボードの位置に関連するコメントを取得
     *
     * @param int $boardId ボードID
     * @param float $x X座標
     * @param float $y Y座標
     * @param float $radius 検索半径
     * @return array コメントの配列
     */
    public function findByPosition($boardId, $x, $y, $radius = 50.0)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM {$this->table} 
                WHERE board_id = ? 
                AND object_id IS NULL
                AND SQRT(POW(position_x - ?, 2) + POW(position_y - ?, 2)) <= ?";

        return $db->fetchAll($sql, [$boardId, $x, $y, $radius]);
    }

    /**
     * ユーザーのコメントを取得
     *
     * @param int $userId ユーザーID
     * @param int $limit 取得件数上限
     * @return array コメントの配列
     */
    public function findByUserId($userId, $limit = 20)
    {
        $db = Database::getInstance();
        $sql = "SELECT c.*, b.title as board_title 
                FROM {$this->table} c
                JOIN boards b ON c.board_id = b.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
                LIMIT ?";

        return $db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * コメントと関連情報を取得
     *
     * @param int $id コメントID
     * @return array|null コメントデータ
     */
    public function findWithRelations($id)
    {
        $comment = $this->find($id);

        if (!$comment) {
            return null;
        }

        // ユーザー情報を取得
        require_once __DIR__ . '/User.php';
        $userModel = new User();
        $user = $userModel->find($comment['user_id']);

        if ($user) {
            unset($user['password']); // パスワードは除外
            $comment['user'] = $user;
        }

        // オブジェクト情報を取得
        if (!empty($comment['object_id'])) {
            require_once __DIR__ . '/BoardObject.php';
            $objectModel = new BoardObject();
            $object = $objectModel->find($comment['object_id']);

            if ($object) {
                // プロパティをデコード
                if (isset($object['properties'])) {
                    $object['properties'] = json_decode($object['properties'], true);
                }
                $comment['object'] = $object;
            }
        }

        return $comment;
    }

    /**
     * ボードのコメントを取得（関連情報付き）
     *
     * @param int $boardId ボードID
     * @return array コメントの配列
     */
    public function findByBoardIdWithRelations($boardId)
    {
        $comments = $this->findByBoardId($boardId);
        $result = [];

        foreach ($comments as $comment) {
            $result[] = $this->findWithRelations($comment['id']);
        }

        return array_filter($result); // nullを除外
    }
}
