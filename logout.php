<?php
// logout.php
// PHP セッションを完全に破棄してから、ログインページ(index.html)へ戻す

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// セッション変数をすべてクリア
$_SESSION = [];

// セッションクッキーも消す（あれば）
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// セッション自体を破棄
session_destroy();

// ログイン画面へリダイレクト
header('Location: index.html');
exit;
