<?php
// bootstrap.php
require_once __DIR__ . '/vendor/autoload.php';

// 環境変数の読み込み
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// エラー表示の設定
if ($_ENV['APP_DEBUG'] === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
}

// タイムゾーンの設定
date_default_timezone_set('Asia/Tokyo');

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false, // 本番環境ではtrueに設定
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true
    ]);
}

// データベース接続の初期化
require_once __DIR__ . '/config/database.php';
