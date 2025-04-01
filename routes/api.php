<?php
// routes/api.php
$apiRoutes = [
    'GET' => [
        '/api/boards' => function () {
            require_once __DIR__ . '/../app/controllers/api/BoardController.php';
            $controller = new BoardApiController();
            return $controller->index();
        },
        '/api/boards/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/api/BoardController.php';
            $controller = new BoardApiController();
            return $controller->show($id);
        },
        '/api/boards/(\d+)/objects' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/api/ObjectController.php';
            $controller = new ObjectApiController();
            return $controller->index($boardId);
        },
        '/api/boards/(\d+)/objects/(\d+)' => function ($boardId, $objectId) {
            require_once __DIR__ . '/../app/controllers/api/ObjectController.php';
            $controller = new ObjectApiController();
            return $controller->show($boardId, $objectId);
        },
        '/api/users/me' => function () {
            require_once __DIR__ . '/../app/controllers/api/UserController.php';
            $controller = new UserApiController();
            return $controller->me();
        },
    ],
    'POST' => [
        '/api/boards' => function () {
            require_once __DIR__ . '/../app/controllers/api/BoardController.php';
            $controller = new BoardApiController();
            return $controller->store();
        },
        '/api/boards/(\d+)/objects' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/api/ObjectController.php';
            $controller = new ObjectApiController();
            return $controller->store($boardId);
        },
        '/api/boards/(\d+)/share' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/api/BoardController.php';
            $controller = new BoardApiController();
            return $controller->share($boardId);
        },
    ],
    'PUT' => [
        '/api/boards/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/api/BoardController.php';
            $controller = new BoardApiController();
            return $controller->update($id);
        },
        '/api/boards/(\d+)/objects/(\d+)' => function ($boardId, $objectId) {
            require_once __DIR__ . '/../app/controllers/api/ObjectController.php';
            $controller = new ObjectApiController();
            return $controller->update($boardId, $objectId);
        },
    ],
    'DELETE' => [
        '/api/boards/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/api/BoardController.php';
            $controller = new BoardApiController();
            return $controller->destroy($id);
        },
        '/api/boards/(\d+)/objects/(\d+)' => function ($boardId, $objectId) {
            require_once __DIR__ . '/../app/controllers/api/ObjectController.php';
            $controller = new ObjectApiController();
            return $controller->destroy($boardId, $objectId);
        },
        '/api/boards/(\d+)/share/(\d+)' => function ($boardId, $userId) {
            require_once __DIR__ . '/../app/controllers/api/BoardController.php';
            $controller = new BoardApiController();
            return $controller->unshare($boardId, $userId);
        },
    ],
];
