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
        '/api/users/search' => function () {
            require_once __DIR__ . '/../app/controllers/api/UserController.php';
            $controller = new UserApiController();
            return $controller->search();
        },
        '/api/boards/(\d+)/history' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/api/HistoryController.php';
            $controller = new HistoryApiController();
            return $controller->boardHistory($boardId);
        },
        '/api/objects/(\d+)/history' => function ($objectId) {
            require_once __DIR__ . '/../app/controllers/api/HistoryController.php';
            $controller = new HistoryApiController();
            return $controller->objectHistory($objectId);
        },
        '/api/boards/(\d+)/comments' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/api/CommentController.php';
            $controller = new CommentApiController();
            return $controller->boardComments($boardId);
        },
        '/api/objects/(\d+)/comments' => function ($objectId) {
            require_once __DIR__ . '/../app/controllers/api/CommentController.php';
            $controller = new CommentApiController();
            return $controller->objectComments($objectId);
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
        '/api/users/profile' => function () {
            require_once __DIR__ . '/../app/controllers/api/UserController.php';
            $controller = new UserApiController();
            return $controller->updateProfile();
        },
        '/api/users/password' => function () {
            require_once __DIR__ . '/../app/controllers/api/UserController.php';
            $controller = new UserApiController();
            return $controller->updatePassword();
        },
        '/api/boards/(\d+)/comments' => function ($boardId) {
            require_once __DIR__ . '/../app/controllers/api/CommentController.php';
            $controller = new CommentApiController();
            return $controller->store($boardId);
        },
        '/api/objects/(\d+)/comments' => function ($objectId) {
            require_once __DIR__ . '/../app/controllers/api/CommentController.php';
            $controller = new CommentApiController();
            return $controller->storeObjectComment($objectId);
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
        '/api/comments/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/api/CommentController.php';
            $controller = new CommentApiController();
            return $controller->update($id);
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
        '/api/comments/(\d+)' => function ($id) {
            require_once __DIR__ . '/../app/controllers/api/CommentController.php';
            $controller = new CommentApiController();
            return $controller->destroy($id);
        },
    ],
];
