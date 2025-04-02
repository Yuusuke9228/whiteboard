<?php
// app/controllers/UserController.php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';

class UserController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * プロフィール表示
     *
     * @return void
     */
    public function showProfile()
    {
        // 認証チェック
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $user = $this->userModel->find($userId);

        if (!$user) {
            $_SESSION['errors'] = ['general' => ['User not found']];
            return $this->redirect('/login');
        }

        $this->view('users/profile', [
            'user' => $user
        ]);
    }

    /**
     * プロフィール更新
     *
     * @return void
     */
    public function updateProfile()
    {
        // 認証チェック
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $avatarUrl = $_POST['avatar_url'] ?? '';

        // バリデーション
        $errors = $this->validate(
            [
                'username' => $username,
                'email' => $email
            ],
            [
                'username' => 'required|min:3|max:50',
                'email' => 'required|email'
            ]
        );

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return $this->redirect('/profile');
        }

        // ユーザー名の重複チェック
        $existingUser = $this->userModel->findByUsername($username);
        if ($existingUser && $existingUser['id'] != $userId) {
            $_SESSION['errors'] = ['username' => ['Username already taken']];
            return $this->redirect('/profile');
        }

        // メールアドレスの重複チェック
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser && $existingUser['id'] != $userId) {
            $_SESSION['errors'] = ['email' => ['Email already taken']];
            return $this->redirect('/profile');
        }

        // アバターURLが指定されている場合のバリデーション
        if (!empty($avatarUrl) && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
            $_SESSION['errors'] = ['avatar_url' => ['Invalid URL']];
            return $this->redirect('/profile');
        }

        $data = [
            'username' => $username,
            'email' => $email
        ];

        if (!empty($avatarUrl)) {
            $data['avatar_url'] = $avatarUrl;
        }

        $result = $this->userModel->update($userId, $data);

        if (!$result) {
            $_SESSION['errors'] = ['general' => ['Failed to update profile']];
            return $this->redirect('/profile');
        }

        // セッション情報の更新
        $_SESSION['username'] = $username;

        $_SESSION['success'] = 'Profile updated successfully';
        return $this->redirect('/profile');
    }

    /**
     * パスワード更新
     *
     * @return void
     */
    public function updatePassword()
    {
        // 認証チェック
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // バリデーション
        $errors = $this->validate(
            [
                'current_password' => $currentPassword,
                'new_password' => $newPassword,
                'confirm_password' => $confirmPassword
            ],
            [
                'current_password' => 'required',
                'new_password' => 'required|min:8',
                'confirm_password' => 'required'
            ]
        );

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return $this->redirect('/profile');
        }

        // 新しいパスワードと確認パスワードの一致を確認
        if ($newPassword !== $confirmPassword) {
            $_SESSION['errors'] = ['confirm_password' => ['Password confirmation does not match']];
            return $this->redirect('/profile');
        }

        // 現在のパスワードを確認
        $user = $this->userModel->find($userId);

        if (!password_verify($currentPassword, $user['password'])) {
            $_SESSION['errors'] = ['current_password' => ['Current password is incorrect']];
            return $this->redirect('/profile');
        }

        // パスワード更新
        $result = $this->userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);

        if (!$result) {
            $_SESSION['errors'] = ['general' => ['Failed to update password']];
            return $this->redirect('/profile');
        }

        $_SESSION['success'] = 'Password updated successfully';
        return $this->redirect('/profile');
    }
}
