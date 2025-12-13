<?php
// auth_required.php
// ログインが必須のページでインクルードして使う共通ガード

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    // 未ログインならログイン画面へリダイレクト
    header('Location: index.html'); // Firebase Auth のトップページ
    exit;
}
