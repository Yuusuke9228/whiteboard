<?php
// app/models/Board.php
require_once __DIR__ . '/Model.php';

class Board extends Model
{
    protected $table = 'boards';
    protected $fillable = ['title', 'owner_id', 'background_color', 'is_public'];

    public function owner()
    {
        $userModel = new User();
        return $userModel->find($this->owner_id);
    }

    public function objects()
    {
        $objectModel = new Object();
        return $objectModel->where('board_id', $this->id);
    }

    public function sharedUsers()
    {
        $db = $this->db->getConnection();
        $boardId = $this->id;

        $sql = "SELECT u.*, bs.permission FROM users u 
                JOIN board_shares bs ON u.id = bs.user_id 
                WHERE bs.board_id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$boardId]);
        return $stmt->fetchAll();
    }

    public function share($userId, $permission = 'view')
    {
        $data = [
            'board_id' => $this->id,
            'user_id' => $userId,
            'permission' => $permission
        ];

        return $this->db->insert('board_shares', $data);
    }

    public function unshare($userId)
    {
        return $this->db->delete(
            'board_shares',
            'board_id = ? AND user_id = ?',
            [$this->id, $userId]
        );
    }
}
