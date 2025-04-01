<?php
// app/models/User.php
require_once __DIR__ . '/Model.php';

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['username', 'email', 'password', 'avatar_url'];

    public function findByEmail($email)
    {
        return $this->db->fetch("SELECT * FROM {$this->table} WHERE email = ?", [$email]);
    }

    public function findByUsername($username)
    {
        return $this->db->fetch("SELECT * FROM {$this->table} WHERE username = ?", [$username]);
    }

    public function boards()
    {
        $boardModel = new Board();
        return $boardModel->where('owner_id', $this->id);
    }

    public function sharedBoards()
    {
        $db = $this->db->getConnection();
        $userId = $this->id;

        $sql = "SELECT b.* FROM boards b 
                JOIN board_shares bs ON b.id = bs.board_id 
                WHERE bs.user_id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function authenticate($password)
    {
        return password_verify($password, $this->password);
    }

    public function create(array $data)
    {
        // パスワードをハッシュ化
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return parent::create($data);
    }
}
