<?php
// edit.php
session_start();
require_once 'funcs.php';   // ← h(), loginCheck() をここから読む
loginCheck();               // ← 編集はログイン必須にする

require_once 'db_connect.php';

// ここから下で $pdo が使える
$pdo = get_pdo();

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    exit('IDが指定されていません');
}

// POSTなら更新 or 削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];

    // 削除
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $sql = 'DELETE FROM bottle_entries WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo '削除しました。<a href="index.php">トップへ戻る</a>';
        exit;
    }

    // 更新
    $user_name     = $_POST['user_name'] ?? '';
    $wine_producer = $_POST['wine_producer'] ?? '';
    $wine_name     = $_POST['wine_name'] ?? '';
    $wine_vintage  = $_POST['wine_vintage'] ?? '';
    $wine_type     = $_POST['wine_type'] ?? '';
    $price_band    = $_POST['price_band'] ?? '';
    $theme_fit     = $_POST['theme_fit'] ?? '';

    $sql = 'UPDATE bottle_entries
            SET user_name = :user_name,
                wine_producer = :wine_producer,
                wine_name = :wine_name,
                wine_vintage = :wine_vintage,
                wine_type = :wine_type,
                price_band = :price_band,
                theme_fit = :theme_fit
            WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
    $stmt->bindValue(':wine_producer', $wine_producer, PDO::PARAM_STR);
    $stmt->bindValue(':wine_name', $wine_name, PDO::PARAM_STR);
    $stmt->bindValue(':wine_vintage', $wine_vintage, PDO::PARAM_STR);
    $stmt->bindValue(':wine_type', $wine_type, PDO::PARAM_STR);
    $stmt->bindValue(':price_band', $price_band, PDO::PARAM_STR);
    $stmt->bindValue(':theme_fit', (int)$theme_fit, PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    echo '更新しました。<a href="index.php">トップへ戻る</a>';
    exit;
}

// GETなら編集フォーム表示
$id = (int)$_GET['id'];
$sql = 'SELECT * FROM bottle_entries WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$entry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entry) {
    exit('データが見つかりません');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>BYO編集</title>
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
        <?php if ($_SESSION['role'] === 'organizer'): ?>
        | <a href="list_admin.php">幹事ビュー（フル一覧）</a>
        <?php endif; ?>
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

    <h1>持ち寄りワインの編集</h1>

    <form action="edit.php" method="post" class="form-byo">
      <input type="hidden" name="id"
             value="<?= htmlspecialchars($entry['id'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="form-row">
        <label>お名前：
          <input type="text" name="user_name"
                 value="<?= htmlspecialchars($entry['user_name'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
      </div>

      <div class="form-row">
        <label>生産者：
          <input type="text" name="wine_producer"
                 value="<?= htmlspecialchars($entry['wine_producer'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
      </div>

      <div class="form-row">
        <label>ワイン名：
          <input type="text" name="wine_name"
                 value="<?= htmlspecialchars($entry['wine_name'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
      </div>

      <div class="form-row">
        <label>ヴィンテージ：
          <input type="text" name="wine_vintage"
                 value="<?= htmlspecialchars($entry['wine_vintage'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
      </div>

      <div class="form-row">
        <label>タイプ：
          <input type="text" name="wine_type"
                 value="<?= htmlspecialchars($entry['wine_type'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
      </div>

      <div class="form-row">
        <label>価格帯：
          <input type="text" name="price_band"
                 value="<?= htmlspecialchars($entry['price_band'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
      </div>

      <div class="form-row">
        <label>テーマ適合度（1〜5）：
          <input type="number" name="theme_fit" min="1" max="5"
                 value="<?= htmlspecialchars($entry['theme_fit'], ENT_QUOTES, 'UTF-8') ?>">
        </label>
      </div>

      <div class="form-actions">
        <button type="submit" name="action" value="update" class="btn-primary">
          更新する
        </button>
      </div>
    </form>

    <hr>

    <form action="edit.php" method="post"
          onsubmit="return confirm('本当に削除しますか？');">
      <input type="hidden" name="id"
             value="<?= htmlspecialchars($entry['id'], ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" name="action" value="delete" class="btn-danger">
        このエントリーを削除する
      </button>
    </form>

    <p class="mt-20">
      <a href="index.php">トップへ戻る</a>
    </p>

  </main>
</body>

</html>
