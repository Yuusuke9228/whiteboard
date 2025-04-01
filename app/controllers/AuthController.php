<?php
// app/controllers/AuthController.php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showLoginForm()
    {
        return $this->view('auth/login');
    }

/*************  ✨ Codeium Command ⭐  *************/
    /**
     * Handles the login process for a user.
     *
     * Validates the email and password provided in the POST request. If validation fails,
     * it redirects back to the login page with error messages. If the credentials are valid,
     * the user is authenticated and redirected to the boards page.
     *
     * Sets session variables for authenticated users.
     *
     * @return void Redirects the user to the appropriate page based on the authentication result.
     */

/******  630ba3ad-8c9b-4f89-b3cd-0afa8bf530fe  *******/
    public function login()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $errors = $this->validate(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return $this->redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['errors'] = ['auth' => ['Invalid credentials']];
            return $this->redirect('/login');
        }

        // ユーザー認証成功
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        return $this->redirect('/boards');
    }

    public function showRegisterForm()
    {
        return $this->view('auth/register');
    }

    public function register()
    {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        $errors = $this->validate(
            [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation
            ],
            [
                'username' => 'required|min:3|max:50',
                'email' => 'required|email',
                'password' => 'required|min:8',
                'password_confirmation' => 'required'
            ]
        );

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'][] = 'Password confirmation does not match';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return $this->redirect('/register');
        }

        // メールアドレスとユーザー名の重複チェック
        if ($this->userModel->findByEmail($email)) {
            $_SESSION['errors'] = ['email' => ['Email already taken']];
            return $this->redirect('/register');
        }

        if ($this->userModel->findByUsername($username)) {
            $_SESSION['errors'] = ['username' => ['Username already taken']];
            return $this->redirect('/register');
        }

        // ユーザー作成
        $userId = $this->userModel->create([
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);

        if (!$userId) {
            $_SESSION['errors'] = ['general' => ['Failed to create user']];
            return $this->redirect('/register');
        }

        // 自動ログイン
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;

        return $this->redirect('/boards');
    }

    public function logout()
    {
        session_unset();
        session_destroy();

        return $this->redirect('/login');
    }
}
