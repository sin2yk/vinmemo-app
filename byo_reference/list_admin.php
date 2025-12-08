<?php
// list_admin.php
session_start();
require_once 'funcs.php';
loginCheck();

// ★ ここでロールチェック（幹事だけOK）
if ($_SESSION['role'] !== 'organizer') {
    exit('ACCESS DENIED: organizer only');
}

require 'db_connect.php'; // $pdo を用意するファイル
// ここから下で $pdo が使える
$pdo = get_pdo();

// 1. 削除リクエスト処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    $sql = 'DELETE FROM bottle_entries WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $delete_id, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: list_admin.php');
    exit;
}

// 2. 一覧取得
$sql = 'SELECT * FROM bottle_entries WHERE event_id = 1 ORDER BY created_at ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>BYO一覧（主催者用）</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="header">
    <div class="header-inner">
      <div class="header-title">BYOワイン会ボトル登録</div>
      <div class="header-user">
        ログイン中：
        <?php echo h($_SESSION['name']); ?> さん
        （<?php echo h($_SESSION['role']); ?>）
        | <a href="logout.php">ログアウト</a>
      </div>
    </div>
  </header>
  <!-- グローバルナビ -->
  <nav class="nav-links">
    <a href="index.php">トップ</a>
    <a href="register.php">持ち寄り登録フォーム</a>
    <a href="list.php">参加者用ワインリスト</a>
    <a href="list_admin.php">幹事用ワインリスト</a>
  </nav>

  <!-- メインコンテンツ -->
  <main class="main-container">
    <h1>BYO一覧（主催者用）</h1>

    <table class="table-byo">
      <tr>
        <th>ID</th>
        <th>名前</th>
        <th>生産者</th>
        <th>ワイン名</th>
        <th>ヴィンテージ</th>
        <th>タイプ</th>
        <th>価格帯</th>
        <th>操作</th>
      </tr>
      <?php foreach ($entries as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($row['wine_producer'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($row['wine_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($row['wine_vintage'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($row['wine_type'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($row['price_band'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <a class="btn-link" href="edit.php?id=<?= $row['id'] ?>">編集</a>
            <form action="list_admin.php" method="post" style="display:inline;">
              <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
              <button class="btn-danger" type="submit"
                      onclick="return confirm('本当に削除しますか？');">
                削除
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>

    <p class="mt-20">
      <a href="index.php">トップへ戻る</a>
    </p>
  </main>
</body>

</html>
