<?php
// events_new.php : ワイン会新規登録フォーム＆登録処理

require_once 'db_connect.php';

// フォーム送信後の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = $_POST['title'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $place      = $_POST['place'] ?? '';
    $memo       = $_POST['memo'] ?? '';

    // ざっくりバリデーション（最低限）
    if ($title === '' || $event_date === '') {
        $error = 'タイトルと開催日は必須です。';
    } else {
        $sql = 'INSERT INTO events (title, event_date, place, memo)
                VALUES (:title, :event_date, :place, :memo)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':event_date', $event_date, PDO::PARAM_STR);
        $stmt->bindValue(':place', $place, PDO::PARAM_STR);
        $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
        $stmt->execute();

        // 登録が終わったら一覧へ
        header('Location: events.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>VinMemo v1 – ワイン会新規登録</title>
</head>
<body>
  <h1>ワイン会 新規登録</h1>
  <p><a href="events.php">← ワイン会一覧に戻る</a></p>
  <hr>

  <?php if (!empty($error)) : ?>
    <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form action="events_new.php" method="post">
    <p>
      タイトル（必須）：<br>
      <input type="text" name="title" size="40"
             value="<?= isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : '' ?>">
    </p>

    <p>
      開催日（必須）：<br>
      <input type="date" name="event_date"
             value="<?= isset($event_date) ? htmlspecialchars($event_date, ENT_QUOTES, 'UTF-8') : '' ?>">
    </p>

    <p>
      場所：<br>
      <input type="text" name="place" size="40"
             value="<?= isset($place) ? htmlspecialchars($place, ENT_QUOTES, 'UTF-8') : '' ?>">
    </p>

    <p>
      メモ：<br>
      <textarea name="memo" cols="50" rows="4"><?= isset($memo) ? htmlspecialchars($memo, ENT_QUOTES, 'UTF-8') : '' ?></textarea>
    </p>

    <p>
      <button type="submit">登録する</button>
    </p>
  </form>
</body>
</html>
