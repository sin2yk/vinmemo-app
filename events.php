<?php
require_once 'db_connect.php';

// イベント一覧を新しい日付順で取得
$sql = 'SELECT * FROM events ORDER BY event_date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>VinMemo v1 – Events</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <header>
      <h1>ワイン会一覧（テスト版）</h1>
      <a href="home.php">← Homeに戻る</a>
    </header>

    <p style="text-align: right;">
      <a href="events_new.php" class="button">＋ ワイン会を新規登録する</a>
    </p>

    <hr>

    <?php if (empty($events)) : ?>
      <p>まだイベントは登録されていません。</p>
    <?php else : ?>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>タイトル</th>
              <th>開催日</th>
              <th>場所</th>
              <th>メモ</th>
              <th>作成日時</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $event) : ?>
              <tr>
                <td><?= htmlspecialchars($event['id'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($event['event_date'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($event['place'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= nl2br(htmlspecialchars($event['memo'], ENT_QUOTES, 'UTF-8')) ?></td>
                <td><?= htmlspecialchars($event['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</body>
</html>
