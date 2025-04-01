<?php
// routes/web.php
$routes = [
    'GET' => [
        '/' => function () {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->index();
        },
        '/login' => function () {
            require_once __DIR__ . '/../app/controllers/AuthController.php';
            $controller = new AuthController();
            return $controller->showLoginForm();
        },
        '/register' => function () {
            require_once __DIR__ . '/../app/controllers/AuthController.php';
            $controller = new AuthController();
            return $controller->showRegisterForm();
        },
        '/logout' => function () {
            require_once __DIR__ . '/../app/controllers/AuthController.php';
            $controller = new AuthController();
            return $controller->logout();
        },
        '/boards' => function () {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->index();
        },
        '/boards/create' => function () {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->create();
        },
        '/boards/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->show($id);
        },
    ],
    'POST' => [
        '/login' => function () {
            require_once __DIR__ . '/../app/controllers/AuthController.php';
            $controller = new AuthController();
            return $controller->login();
        },
        '/register' => function () {
            require_once __DIR__ . '/../app/controllers/AuthController.php';
            $controller = new AuthController();
            return $controller->register();
        },
        '/boards' => function () {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->store();
        },
    ],
    'PUT' => [
        '/boards/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->update($id);
        },
    ],
    'DELETE' => [
        '/boards/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->destroy($id);
        },
    ],
];