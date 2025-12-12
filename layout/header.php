<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../helpers.php';
// Note: db_connect.php is usually required by the main page before this, 
// or we can require it here if not already. 
// For now, we assume the page handles DB connection if needed, 
// but we might need it for user name display if not in session.
// VinMemo seems to store 'name' in session on login.
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? h($page_title) : 'VinMemo' ?></title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Analytics GA4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-6BXQJQF1K5"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() {
            dataLayer.push(arguments);
        }
        gtag("js", new Date());
        gtag("config", "G-6BXQJQF1K5");
    </script>
    <style>
        /* Additional header styles adapting BYO concepts */
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-user {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .nav-links {
            margin: 10px 0 20px 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }

        .nav-links a {
            margin-right: 15px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <header class="site-header">
        <div class="header-inner">
            <div class="site-title">
                <h1><a href="index.html">VinMemo</a></h1>
            </div>

            <!-- Navigation (Always visible for V1 usability, ignoring strict session check) -->
            <nav class="main-nav">
                <a href="home.php">Home / ホーム</a>
                <a href="events.php">Event List / イベント一覧</a>
                <a href="mypage.php">My Page / マイページ</a>
            </nav>

            <!-- Login info suspended until Firebase/PHP session integration is complete -->
            <!-- 
            <div class="login-info">
                <?php if (isset($_SESSION['user_id'])): ?>
                    Login: <?= isset($_SESSION['name']) ? h($_SESSION['name']) : 'User' ?>
                    | <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="index.html">Login</a>
                <?php endif; ?>
            </div>
            -->
        </div>
    </header>

    <div class="container main-container">