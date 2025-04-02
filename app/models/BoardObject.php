<?php
// app/models/BoardObject.php
require_once __DIR__ . '/Model.php';

class BoardObject extends Model
{
    protected $table = 'objects';
    protected $fillable = ['board_id', 'user_id', 'type', 'content', 'properties'];

    /**
     * オブジェクトの作成時のフック処理
     *
     * @param array $data 作成データ
     * @return int 作成されたレコードのID
     */
    public function create(array $data)
    {
        // プロパティがJSONでない場合はJSON化
        if (isset($data['properties']) && !$this->isJson($data['properties'])) {
            $data['properties'] = json_encode($data['properties']);
        }

        // 履歴の記録
        $objectId = parent::create($data);

        if ($objectId && isset($data['board_id']) && isset($data['user_id'])) {
            require_once __DIR__ . '/History.php';
            $historyModel = new History();
            $historyModel->create([
                'board_id' => $data['board_id'],
                'user_id' => $data['user_id'],
                'action' => 'create',
                'object_id' => $objectId,
                'before_state' => null,
                'after_state' => json_encode($data)
            ]);
        }

        return $objectId;
    }

    /**
     * オブジェクトの更新時のフック処理
     *
     * @param int $id オブジェクトID
     * @param array $data 更新データ
     * @return bool 更新が成功したかどうか
     */
    public function update($id, array $data)
    {
        // 更新前のデータを保存
        $beforeObject = $this->find($id);

        // プロパティがJSONでない場合はJSON化
        if (isset($data['properties']) && !$this->isJson($data['properties'])) {
            $data['properties'] = json_encode($data['properties']);
        }

        // 更新処理
        $result = parent::update($id, $data);

        // 履歴の記録
        if ($result && $beforeObject) {
            require_once __DIR__ . '/History.php';
            $historyModel = new History();
            $afterObject = $this->find($id);

            $historyModel->create([
                'board_id' => $beforeObject['board_id'],
                'user_id' => $afterObject['user_id'],
                'action' => 'update',
                'object_id' => $id,
                'before_state' => json_encode($beforeObject),
                'after_state' => json_encode($afterObject)
            ]);
        }

        return $result;
    }

    /**
     * オブジェクトの削除時のフック処理
     *
     * @param int $id オブジェクトID
     * @return bool 削除が成功したかどうか
     */
    public function delete($id)
    {
        // 削除前のデータを保存
        $beforeObject = $this->find($id);

        if (!$beforeObject) {
            return false;
        }

        // 履歴の記録
        require_once __DIR__ . '/History.php';
        $historyModel = new History();
        $historyModel->create([
            'board_id' => $beforeObject['board_id'],
            'user_id' => $beforeObject['user_id'],
            'action' => 'delete',
            'object_id' => $id,
            'before_state' => json_encode($beforeObject),
            'after_state' => null
        ]);

        // 削除処理
        return parent::delete($id);
    }

    /**
     * ボードに関連するオブジェクトを取得
     *
     * @param int $boardId ボードID
     * @return array オブジェクトの配列
     */
    public function findByBoardId($boardId)
    {
        return $this->where('board_id', $boardId);
    }

    /**
     * オブジェクトの内容をデコードして取得
     *
     * @param int $id オブジェクトID
     * @return array|null デコードされたオブジェクト
     */
    public function findAndDecode($id)
    {
        $object = $this->find($id);

        if (!$object) {
            return null;
        }

        if (isset($object['properties']) && $this->isJson($object['properties'])) {
            $object['properties'] = json_decode($object['properties'], true);
        }

        return $object;
    }

    /**
     * ボードに関連するオブジェクトをデコードして取得
     *
     * @param int $boardId ボードID
     * @return array デコードされたオブジェクトの配列
     */
    public function findByBoardIdAndDecode($boardId)
    {
        $objects = $this->where('board_id', $boardId);

        foreach ($objects as &$object) {
            if (isset($object['properties']) && $this->isJson($object['properties'])) {
                $object['properties'] = json_decode($object['properties'], true);
            }
        }

        return $objects;
    }

    /**
     * 文字列がJSON形式かどうかを判定
     *
     * @param string $string 判定する文字列
     * @return bool JSON形式ならtrue
     */
    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * コネクタの接続先オブジェクトを更新
     *
     * @param int $oldObjectId 古いオブジェクトID
     * @param int $newObjectId 新しいオブジェクトID
     * @return bool 更新が成功したかどうか
     */
    public function updateConnectors($oldObjectId, $newObjectId)
    {
        // コネクタを検索
        $connectors = $this->where('type', '=', 'connector');
        $updated = false;

        foreach ($connectors as $connector) {
            $properties = json_decode($connector['properties'], true);
            $needsUpdate = false;

            if (isset($properties['startObjectId']) && $properties['startObjectId'] == $oldObjectId) {
                $properties['startObjectId'] = $newObjectId;
                $needsUpdate = true;
            }

            if (isset($properties['endObjectId']) && $properties['endObjectId'] == $oldObjectId) {
                $properties['endObjectId'] = $newObjectId;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $this->update($connector['id'], [
                    'properties' => json_encode($properties)
                ]);
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * オブジェクトのクローンを作成
     *
     * @param int $id オブジェクトID
     * @param int $newBoardId 新しいボードID（省略時は同じボード）
     * @param int $userId ユーザーID
     * @return int|bool 新しいオブジェクトID または 失敗時はfalse
     */
    public function cloneObject($id, $newBoardId = null, $userId = null)
    {
        $object = $this->find($id);

        if (!$object) {
            return false;
        }

        $cloneData = [
            'board_id' => $newBoardId ?? $object['board_id'],
            'user_id' => $userId ?? $object['user_id'],
            'type' => $object['type'],
            'content' => $object['content'],
            'properties' => $object['properties']
        ];

        return $this->create($cloneData);
    }
}
