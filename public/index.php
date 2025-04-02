// public/index.php
<?php
// bootstrap.phpを読み込み、環境変数とオートローダーを初期化
require_once __DIR__ . '/../bootstrap.php';

// オートローダーの簡易実装（実際の開発ではComposerを使用）
spl_autoload_register(function ($className) {
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// APIリクエストか判定
$isApiRequest = strpos($uri, '/api/') === 0;

// APIリクエストの場合
if ($isApiRequest) {
    require_once __DIR__ . '/../routes/api.php';

    // Content-Typeヘッダーの取得
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

    // JSONリクエストの処理
    if (strpos($contentType, 'application/json') !== false) {
        $jsonInput = file_get_contents('php://input');
        $_POST = json_decode($jsonInput, true) ?? [];
    }

    // PUT/DELETE メソッドの処理
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }

    $routeFound = false;

    if (isset($apiRoutes[$method])) {
        foreach ($apiRoutes[$method] as $route => $callback) {
            $pattern = '#^' . str_replace('/', '\/', $route) . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // 最初の要素（完全マッチ）を削除
                call_user_func_array($callback, $matches);
                $routeFound = true;
                break;
            }
        }
    }

    if (!$routeFound) {
        header('Content-Type: application/json');
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'API route not found']);
    }

    exit;
}

// ルーティング処理
require_once __DIR__ . '/../routes/web.php';

$routeFound = false;

// PUT/DELETE メソッドの処理
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

if (isset($routes[$method])) {
    foreach ($routes[$method] as $route => $callback) {
        $pattern = '#^' . str_replace('/', '\/', $route) . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // 最初の要素（完全マッチ）を削除
            call_user_func_array($callback, $matches);
            $routeFound = true;
            break;
        }
    }
}

if (!$routeFound) {
    header('HTTP/1.1 404 Not Found');
    echo '404 Not Found';
}
