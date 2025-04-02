<?php
// app/controllers/api/ObjectController.php
require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../models/BoardObject.php';

class ObjectApiController extends ApiController
{
    protected $objectModel;

    public function __construct()
    {
        $this->objectModel = new BoardObject();
    }

    /**
     * オブジェクトの一覧を取得
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function index($boardId)
    {
        $userId = $this->authorize($boardId);

        $objects = $this->objectModel->where('board_id', $boardId);

        return $this->success([
            'objects' => $objects
        ]);
    }

    /**
     * 特定のオブジェクトを取得
     *
     * @param int $boardId ボードID
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function show($boardId, $objectId)
    {
        $userId = $this->authorize($boardId);

        $object = $this->objectModel->find($objectId);

        if (!$object || $object['board_id'] != $boardId) {
            return $this->error('Object not found', 404);
        }

        return $this->success([
            'object' => $object
        ]);
    }

    /**
     * 新しいオブジェクトを作成
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function store($boardId)
    {
        $userId = $this->authorize($boardId, 'edit');

        $type = $_POST['type'] ?? '';
        $content = $_POST['content'] ?? null;
        $properties = $_POST['properties'] ?? '{}';

        if (empty($type)) {
            return $this->error('Type is required');
        }

        if (!in_array($type, ['text', 'note', 'shape', 'line', 'connector'])) {
            return $this->error('Invalid object type');
        }

        $objectId = $this->objectModel->create([
            'board_id' => $boardId,
            'user_id' => $userId,
            'type' => $type,
            'content' => $content,
            'properties' => $properties
        ]);

        if (!$objectId) {
            return $this->error('Failed to create object');
        }

        // WebSocketメッセージの送信
        $this->sendWebSocketMessage([
            'type' => 'object_created',
            'boardId' => $boardId,
            'object' => [
                'id' => $objectId,
                'board_id' => $boardId,
                'user_id' => $userId,
                'type' => $type,
                'content' => $content,
                'properties' => $properties
            ]
        ]);

        return $this->success([
            'objectId' => $objectId
        ], 'Object created successfully');
    }

    /**
     * オブジェクトを更新
     *
     * @param int $boardId ボードID
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function update($boardId, $objectId)
    {
        $userId = $this->authorize($boardId, 'edit');

        $object = $this->objectModel->find($objectId);

        if (!$object || $object['board_id'] != $boardId) {
            return $this->error('Object not found', 404);
        }

        $type = $_POST['type'] ?? null;
        $content = $_POST['content'] ?? null;
        $properties = $_POST['properties'] ?? null;

        $data = [];

        if ($type !== null) {
            if (!in_array($type, ['text', 'note', 'shape', 'line', 'connector'])) {
                return $this->error('Invalid object type');
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
            return $this->error('No data to update');
        }

        $result = $this->objectModel->update($objectId, $data);

        if (!$result) {
            return $this->error('Failed to update object');
        }

        // 更新されたオブジェクトを取得
        $updatedObject = $this->objectModel->find($objectId);

        // WebSocketメッセージの送信
        $this->sendWebSocketMessage([
            'type' => 'object_updated',
            'boardId' => $boardId,
            'object' => $updatedObject
        ]);

        return $this->success([
            'object' => $updatedObject
        ], 'Object updated successfully');
    }

    /**
     * オブジェクトを削除
     *
     * @param int $boardId ボードID
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function destroy($boardId, $objectId)
    {
        $userId = $this->authorize($boardId, 'edit');

        $object = $this->objectModel->find($objectId);

        if (!$object || $object['board_id'] != $boardId) {
            return $this->error('Object not found', 404);
        }

        $result = $this->objectModel->delete($objectId);

        if (!$result) {
            return $this->error('Failed to delete object');
        }

        // WebSocketメッセージの送信
        $this->sendWebSocketMessage([
            'type' => 'object_deleted',
            'boardId' => $boardId,
            'objectId' => $objectId,
            'userId' => $userId
        ]);

        return $this->success([], 'Object deleted successfully');
    }

    /**
     * WebSocketにメッセージを送信
     *
     * @param array $message メッセージデータ
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
