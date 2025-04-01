<?php
// app/controllers/api/UserController.php
require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../models/User.php';

class UserApiController extends ApiController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function me()
    {
        $userId = $this->authenticate();

        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->error('User not found', 404);
        }

        // パスワードは除外
        unset($user['password']);

        return $this->success([
            'user' => $user
        ]);
    }

    public function search()
    {
        $userId = $this->authenticate();

        $query = $_GET['q'] ?? '';

        if (empty($query) || strlen($query) < 3) {
            return $this->error('Search query must be at least 3 characters');
        }

        $db = Database::getInstance();

        $users = $db->fetchAll(
            "SELECT id, username, email, avatar_url FROM users 
            WHERE (username LIKE ? OR email LIKE ?) AND id != ? 
            LIMIT 10",
            ["%{$query}%", "%{$query}%", $userId]
        );

        return $this->success([
            'users' => $users
        ]);
    }

    public function updateProfile()
    {
        $userId = $this->authenticate();

        $username = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;
        $avatar_url = $_POST['avatar_url'] ?? null;

        $data = [];

        if ($username !== null) {
            if (strlen($username) < 3) {
                return $this->error('Username must be at least 3 characters');
            }

            // ユーザー名の重複チェック
            $existingUser = $this->userModel->findByUsername($username);
            if ($existingUser && $existingUser['id'] != $userId) {
                return $this->error('Username already taken');
            }

            $data['username'] = $username;
        }

        if ($email !== null) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('Invalid email address');
            }

            // メールアドレスの重複チェック
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser && $existingUser['id'] != $userId) {
                return $this->error('Email already taken');
            }

            $data['email'] = $email;
        }

        if ($avatar_url !== null) {
            $data['avatar_url'] = $avatar_url;
        }

        if (empty($data)) {
            return $this->error('No data to update');
        }

        $result = $this->userModel->update($userId, $data);

        if (!$result) {
            return $this->error('Failed to update profile');
        }

        $user = $this->userModel->find($userId);
        unset($user['password']);

        return $this->success([
            'user' => $user
        ], 'Profile updated successfully');
    }

    public function updatePassword()
    {
        $userId = $this->authenticate();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            return $this->error('All password fields are required');
        }

        if (strlen($newPassword) < 8) {
            return $this->error('New password must be at least 8 characters');
        }

        if ($newPassword !== $confirmPassword) {
            return $this->error('New password and confirm password do not match');
        }

        $user = $this->userModel->find($userId);

        if (!password_verify($currentPassword, $user['password'])) {
            return $this->error('Current password is incorrect');
        }

        $result = $this->userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);

        if (!$result) {
            return $this->error('Failed to update password');
        }

        return $this->success([], 'Password updated successfully');
    }
}
