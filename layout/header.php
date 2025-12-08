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
    <header class="header">
        <div class="container" style="margin: 0 auto; padding: 20px 0;">
            <div class="header-inner">
                <div class="header-title">
                    <h1 style="margin:0; font-size:1.5rem;">
                        <a href="index.php" style="color:white; text-decoration:none;">VinMemo</a>
                    </h1>
                </div>
                <div class="header-user">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        Login: <?= isset($_SESSION['name']) ? h($_SESSION['name']) : 'User' ?>
                        | <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="index.php">Login</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <nav class="nav-links">
                    <a href="home.php">Home</a>
                    <a href="events.php">Event List</a>
                    <a href="mypage.php">My Page</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <div class="container main-container">