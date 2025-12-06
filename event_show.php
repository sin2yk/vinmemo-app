<?php
// event_show.php : イベント詳細表示

require_once 'db_connect.php';

// IDパラメータの検証
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === null || $id === false) {
    $error = '無効なIDです。';
} else {
    $sql = 'SELECT * FROM events WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $error = 'イベントが見つかりませんでした。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>VinMemo v1 – Event Details</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>イベント詳細</h1>
            <a href="events.php">← イベント一覧に戻る</a>
        </header>

        <?php if (isset($error)): ?>
            <div class="error-msg">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                <br><br>
                <a href="events.php">一覧に戻る</a>
            </div>
        <?php else: ?>

            <!-- イベント情報カード -->
            <div class="card">
                <h2><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></h2>

                <table style="margin-bottom: 20px;">
                    <tr>
                        <th style="width: 30%;">開催日</th>
                        <td><?= htmlspecialchars($event['event_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th>場所</th>
                        <td><?= htmlspecialchars($event['place'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th>メモ</th>
                        <td><?= nl2br(htmlspecialchars($event['memo'], ENT_QUOTES, 'UTF-8')) ?></td>
                    </tr>
                    <tr>
                        <th>作成日時</th>
                        <td><?= htmlspecialchars($event['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                </table>
            </div>

            <!-- ボトル登録プレースホルダー -->
            <div class="card" style="opacity: 0.7;">
                <h3>Event Bottles</h3>
                <p>（Bottle entries will be shown here in the future.）</p>
            </div>

        <?php endif; ?>
    </div>
</body>

</html>