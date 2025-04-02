<?php
// app/controllers/api/HistoryController.php
require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../models/History.php';
require_once __DIR__ . '/../../models/Board.php';

class HistoryApiController extends ApiController
{
    protected $historyModel;
    protected $boardModel;

    public function __construct()
    {
        $this->historyModel = new History();
        $this->boardModel = new Board();
    }

    /**
     * ボードの履歴を取得
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function boardHistory($boardId)
    {
        // 権限チェック
        $userId = $this->authorize($boardId);

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        // ボードの履歴を取得
        $history = $this->historyModel->findByBoardId($boardId, $limit, $offset);

        return $this->success([
            'history' => $history
        ]);
    }

    /**
     * オブジェクトの履歴を取得
     *
     * @param int $objectId オブジェクトID
     * @return void
     */
    public function objectHistory($objectId)
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

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

        // オブジェクトの履歴を取得
        $history = $this->historyModel->findByObjectId($objectId, $limit);

        return $this->success([
            'history' => $history
        ]);
    }

    /**
     * 履歴を比較
     *
     * @return void
     */
    public function compare()
    {
        // リクエストパラメータの取得
        $objectId = $_GET['object_id'] ?? null;
        $historyId1 = $_GET['history_id1'] ?? null;
        $historyId2 = $_GET['history_id2'] ?? null;

        if (!$objectId || !$historyId1) {
            return $this->error('Missing required parameters', 400);
        }

        try {
            // オブジェクトを取得
            require_once __DIR__ . '/../../models/BoardObject.php';
            $objectModel = new BoardObject();
            $object = $objectModel->find($objectId);

            if (!$object) {
                return $this->error('Object not found', 404);
            }

            // 権限チェック
            $userId = $this->authorize($object['board_id']);

            // 履歴を比較
            $diff = $this->historyModel->compareObjectHistory($objectId, $historyId1, $historyId2);

            return $this->success([
                'diff' => $diff
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * 特定時点のボード状態を復元
     *
     * @param int $boardId ボードID
     * @return void
     */
    public function restore($boardId)
    {
        // 管理者権限チェック
        $userId = $this->authorize($boardId, 'admin');

        $timestamp = $_POST['timestamp'] ?? null;

        if (!$timestamp) {
            return $this->error('Missing required parameters', 400);
        }

        try {
            // ボードの状態を復元
            $objects = $this->historyModel->restoreBoardState($boardId, $timestamp);

            // 履歴に記録
            $this->historyModel->create([
                'board_id' => $boardId,
                'user_id' => $userId,
                'action' => 'restore',
                'object_id' => null,
                'before_state' => null,
                'after_state' => json_encode([
                    'timestamp' => $timestamp,
                    'count' => count($objects)
                ])
            ]);

            return $this->success([
                'objects' => $objects,
                'message' => 'Board state restored successfully'
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
