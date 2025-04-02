<?php
// app/models/History.php
require_once __DIR__ . '/Model.php';

class History extends Model
{
    protected $table = 'history';
    protected $fillable = ['board_id', 'user_id', 'action', 'object_id', 'before_state', 'after_state'];

    /**
     * 履歴の作成時のフック処理
     *
     * @param array $data 作成データ
     * @return int 作成されたレコードのID
     */
    public function create(array $data)
    {
        // 必須フィールドの確認
        $requiredFields = ['board_id', 'user_id', 'action'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Field '{$field}' is required for history");
            }
        }

        // アクションの確認
        if (!in_array($data['action'], ['create', 'update', 'delete'])) {
            throw new Exception("Invalid action '{$data['action']}' for history");
        }

        // 状態のJSON化
        foreach (['before_state', 'after_state'] as $field) {
            if (isset($data[$field]) && !empty($data[$field]) && !is_string($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        return parent::create($data);
    }

    /**
     * ボードの履歴を取得
     *
     * @param int $boardId ボードID
     * @param int $limit 取得件数上限
     * @param int $offset 取得開始位置
     * @return array 履歴の配列
     */
    public function findByBoardId($boardId, $limit = 100, $offset = 0)
    {
        $db = Database::getInstance();
        $sql = "SELECT h.*, u.username 
                FROM {$this->table} h
                JOIN users u ON h.user_id = u.id
                WHERE h.board_id = ?
                ORDER BY h.created_at DESC
                LIMIT ? OFFSET ?";

        $histories = $db->fetchAll($sql, [$boardId, $limit, $offset]);

        // JSONデータをデコード
        foreach ($histories as &$history) {
            foreach (['before_state', 'after_state'] as $field) {
                if (isset($history[$field]) && !empty($history[$field])) {
                    $history[$field] = json_decode($history[$field], true);
                }
            }
        }

        return $histories;
    }

    /**
     * オブジェクトの履歴を取得
     *
     * @param int $objectId オブジェクトID
     * @param int $limit 取得件数上限
     * @return array 履歴の配列
     */
    public function findByObjectId($objectId, $limit = 20)
    {
        $db = Database::getInstance();
        $sql = "SELECT h.*, u.username 
                FROM {$this->table} h
                JOIN users u ON h.user_id = u.id
                WHERE h.object_id = ?
                ORDER BY h.created_at DESC
                LIMIT ?";

        $histories = $db->fetchAll($sql, [$objectId, $limit]);

        // JSONデータをデコード
        foreach ($histories as &$history) {
            foreach (['before_state', 'after_state'] as $field) {
                if (isset($history[$field]) && !empty($history[$field])) {
                    $history[$field] = json_decode($history[$field], true);
                }
            }
        }

        return $histories;
    }

    /**
     * ユーザーの履歴を取得
     *
     * @param int $userId ユーザーID
     * @param int $limit 取得件数上限
     * @return array 履歴の配列
     */
    public function findByUserId($userId, $limit = 20)
    {
        $db = Database::getInstance();
        $sql = "SELECT h.*, b.title as board_title 
                FROM {$this->table} h
                JOIN boards b ON h.board_id = b.id
                WHERE h.user_id = ?
                ORDER BY h.created_at DESC
                LIMIT ?";

        $histories = $db->fetchAll($sql, [$userId, $limit]);

        // JSONデータをデコード
        foreach ($histories as &$history) {
            foreach (['before_state', 'after_state'] as $field) {
                if (isset($history[$field]) && !empty($history[$field])) {
                    $history[$field] = json_decode($history[$field], true);
                }
            }
        }

        return $histories;
    }

    /**
     * 最新の履歴を取得
     *
     * @param int $boardId ボードID
     * @param int $count 取得件数
     * @return array 履歴の配列
     */
    public function getLatestHistory($boardId, $count = 10)
    {
        return $this->findByBoardId($boardId, $count);
    }

    /**
     * オブジェクトの変更履歴を比較
     *
     * @param int $objectId オブジェクトID
     * @param int $historyId1 比較する履歴ID1
     * @param int $historyId2 比較する履歴ID2（省略時は最新）
     * @return array 差分情報
     */
    public function compareObjectHistory($objectId, $historyId1, $historyId2 = null)
    {
        // 1つ目の履歴を取得
        $history1 = $this->find($historyId1);
        if (!$history1 || $history1['object_id'] != $objectId) {
            throw new Exception("Invalid history ID {$historyId1}");
        }

        // 2つ目の履歴を取得（指定がない場合は最新）
        if ($historyId2 === null) {
            $db = Database::getInstance();
            $sql = "SELECT * FROM {$this->table} 
                    WHERE object_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1";
            $history2 = $db->fetch($sql, [$objectId]);
        } else {
            $history2 = $this->find($historyId2);
        }

        if (!$history2 || $history2['object_id'] != $objectId) {
            throw new Exception("Invalid history ID {$historyId2}");
        }

        // 状態をデコード
        $state1 = json_decode($history1['after_state'] ?? '{}', true);
        $state2 = json_decode($history2['after_state'] ?? '{}', true);

        // 差分を計算
        $diff = [
            'history1' => [
                'id' => $history1['id'],
                'action' => $history1['action'],
                'user_id' => $history1['user_id'],
                'created_at' => $history1['created_at']
            ],
            'history2' => [
                'id' => $history2['id'],
                'action' => $history2['action'],
                'user_id' => $history2['user_id'],
                'created_at' => $history2['created_at']
            ],
            'differences' => []
        ];

        // プロパティを比較
        if (isset($state1['properties']) && isset($state2['properties'])) {
            $props1 = is_string($state1['properties']) ? json_decode($state1['properties'], true) : $state1['properties'];
            $props2 = is_string($state2['properties']) ? json_decode($state2['properties'], true) : $state2['properties'];

            foreach ($props2 as $key => $value) {
                if (!isset($props1[$key]) || $props1[$key] !== $value) {
                    $diff['differences'][$key] = [
                        'old' => $props1[$key] ?? null,
                        'new' => $value
                    ];
                }
            }

            // 削除されたプロパティ
            foreach ($props1 as $key => $value) {
                if (!isset($props2[$key])) {
                    $diff['differences'][$key] = [
                        'old' => $value,
                        'new' => null,
                        'deleted' => true
                    ];
                }
            }
        }

        // 内容を比較
        if (isset($state1['content']) && isset($state2['content']) && $state1['content'] !== $state2['content']) {
            $diff['differences']['content'] = [
                'old' => $state1['content'],
                'new' => $state2['content']
            ];
        }

        return $diff;
    }

    /**
     * 特定時点のボードの状態を復元
     *
     * @param int $boardId ボードID
     * @param string $timestamp 復元する時点のタイムスタンプ
     * @return array 復元されたオブジェクトの配列
     */
    public function restoreBoardState($boardId, $timestamp)
    {
        // タイムスタンプ以前の最新の状態を取得
        $db = Database::getInstance();
        $sql = "SELECT * FROM {$this->table} 
                WHERE board_id = ? AND created_at <= ? 
                ORDER BY created_at DESC";

        $histories = $db->fetchAll($sql, [$boardId, $timestamp]);

        // オブジェクトIDごとに最新の状態を保持
        $objectStates = [];

        foreach ($histories as $history) {
            $objectId = $history['object_id'];

            if (!isset($objectStates[$objectId])) {
                if ($history['action'] === 'delete') {
                    // 削除されたオブジェクト
                    $objectStates[$objectId] = null;
                } else {
                    // 作成または更新されたオブジェクト
                    $afterState = json_decode($history['after_state'], true);
                    $objectStates[$objectId] = $afterState;
                }
            }
        }

        // nullのエントリ（削除されたオブジェクト）を除外
        $objectStates = array_filter($objectStates);

        return array_values($objectStates);
    }
}
