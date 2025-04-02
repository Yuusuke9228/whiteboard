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
        '/boards/(\d+)/edit' => function ($id) {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->edit($id);
        },
        '/boards/(\d+)/objects' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/ObjectController.php';
            $controller = new ObjectController();
            return $controller->index($boardId);
        },
        '/boards/(\d+)/objects/create' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/ObjectController.php';
            $controller = new ObjectController();
            return $controller->create($boardId);
        },
        '/boards/(\d+)/objects/(\d+)' => function ($boardId, $objectId) {
            require_once __DIR__ . '/../app/controllers/ObjectController.php';
            $controller = new ObjectController();
            return $controller->show($boardId, $objectId);
        },
        '/boards/(\d+)/objects/(\d+)/edit' => function ($boardId, $objectId) {
            require_once __DIR__ . '/../app/controllers/ObjectController.php';
            $controller = new ObjectController();
            return $controller->edit($boardId, $objectId);
        },
        '/profile' => function () {
            require_once __DIR__ . '/../app/controllers/UserController.php';
            $controller = new UserController();
            return $controller->showProfile();
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
        '/boards/(\d+)/objects' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/ObjectController.php';
            $controller = new ObjectController();
            return $controller->store($boardId);
        },
        '/boards/(\d+)/share' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->share($boardId);
        },
        '/profile/update' => function () {
            require_once __DIR__ . '/../app/controllers/UserController.php';
            $controller = new UserController();
            return $controller->updateProfile();
        },
        '/profile/update-password' => function () {
            require_once __DIR__ . '/../app/controllers/UserController.php';
            $controller = new UserController();
            return $controller->updatePassword();
        },
    ],
    'PUT' => [
        '/boards/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->update($id);
        },
        '/boards/(\d+)/objects/(\d+)' => function ($boardId, $objectId) {
            require_once __DIR__ . '/../app/controllers/ObjectController.php';
            $controller = new ObjectController();
            return $controller->update($boardId, $objectId);
        },
    ],
    'DELETE' => [
        '/boards/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->destroy($id);
        },
        '/boards/(\d+)/objects/(\d+)' => function ($boardId, $objectId) {
            require_once __DIR__ . '/../app/controllers/ObjectController.php';
            $controller = new ObjectController();
            return $controller->destroy($boardId, $objectId);
        },
        '/boards/(\d+)/share/(\d+)' => function ($boardId, $userId) {
            require_once __DIR__ . '/../app/controllers/BoardController.php';
            $controller = new BoardController();
            return $controller->unshare($boardId, $userId);
        },
    ],
];
