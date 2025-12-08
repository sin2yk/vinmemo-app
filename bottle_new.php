<?php
require_once 'db_connect.php';
session_start();

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // フォームからの入力値取得
  $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
  $owner_label = $_POST['owner_label'] ?? '';
  $wine_name = $_POST['wine_name'] ?? '';
  $producer_name = $_POST['producer_name'] ?? '';
  $country = $_POST['country'] ?? '';
  $region = $_POST['region'] ?? '';
  $appellation = $_POST['appellation'] ?? '';
  $color = $_POST['color'] ?? 'red';
  $vintage = filter_input(INPUT_POST, 'vintage', FILTER_VALIDATE_INT);
  $bottle_size_ml = filter_input(INPUT_POST, 'bottle_size_ml', FILTER_VALIDATE_INT) ?: 750;
  $est_price_yen = filter_input(INPUT_POST, 'est_price_yen', FILTER_VALIDATE_INT);
  $theme_fit_score = filter_input(INPUT_POST, 'theme_fit_score', FILTER_VALIDATE_INT);
  $is_blind = isset($_POST['is_blind']) ? 1 : 0;
  $memo = $_POST['memo'] ?? '';

  // バリデーション
  if (!$event_id) {
    $error = 'イベントIDが無効です。';
  } elseif ($owner_label === '' || $wine_name === '') {
    $error = '持参者名とワイン名は必須です。';
  } else {
    // ログインユーザーIDがあれば記録
    $brought_by_user_id = $_SESSION['user_id'] ?? null;
    $brought_by_type = $brought_by_user_id ? 'guest' : null; // 仮: ログインしていればguest扱い

    $sql = 'INSERT INTO bottle_entries (
                    event_id, owner_label, wine_name, producer_name, 
                    country, region, appellation, color, vintage, 
                    bottle_size_ml, est_price_yen, theme_fit_score, 
                    is_blind, memo, brought_by_type, brought_by_user_id,
                    created_at, updated_at
                ) VALUES (
                    :event_id, :owner_label, :wine_name, :producer_name, 
                    :country, :region, :appellation, :color, :vintage, 
                    :bottle_size_ml, :est_price_yen, :theme_fit_score, 
                    :is_blind, :memo, :brought_by_type, :brought_by_user_id,
                    NOW(), NOW()
                )';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_label', $owner_label, PDO::PARAM_STR);
    $stmt->bindValue(':wine_name', $wine_name, PDO::PARAM_STR);
    $stmt->bindValue(':producer_name', $producer_name, PDO::PARAM_STR);
    $stmt->bindValue(':country', $country, PDO::PARAM_STR);
    $stmt->bindValue(':region', $region, PDO::PARAM_STR);
    $stmt->bindValue(':appellation', $appellation, PDO::PARAM_STR);
    $stmt->bindValue(':color', $color, PDO::PARAM_STR);
    $stmt->bindValue(':vintage', $vintage, PDO::PARAM_INT); // NULL if false
    $stmt->bindValue(':bottle_size_ml', $bottle_size_ml, PDO::PARAM_INT);
    $stmt->bindValue(':est_price_yen', $est_price_yen, PDO::PARAM_INT);
    $stmt->bindValue(':theme_fit_score', $theme_fit_score, PDO::PARAM_INT);
    $stmt->bindValue(':is_blind', $is_blind, PDO::PARAM_INT);
    $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
    $stmt->bindValue(':brought_by_type', $brought_by_type, PDO::PARAM_STR);
    $stmt->bindValue(':brought_by_user_id', $brought_by_user_id, PDO::PARAM_INT);

    $stmt->execute();

    header("Location: event_show.php?id={$event_id}");
    exit;
  }
}
?>
<!DOCTYPE html>
<?php
require_once 'db_connect.php';
session_start();

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // フォームからの入力値取得
  $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
  $owner_label = $_POST['owner_label'] ?? '';
  $wine_name = $_POST['wine_name'] ?? '';
  $producer_name = $_POST['producer_name'] ?? '';
  $country = $_POST['country'] ?? '';
  $region = $_POST['region'] ?? '';
  $appellation = $_POST['appellation'] ?? '';
  $color = $_POST['color'] ?? 'red';
  $vintage = filter_input(INPUT_POST, 'vintage', FILTER_VALIDATE_INT);
  $bottle_size_ml = filter_input(INPUT_POST, 'bottle_size_ml', FILTER_VALIDATE_INT) ?: 750;
  $est_price_yen = filter_input(INPUT_POST, 'est_price_yen', FILTER_VALIDATE_INT);
  $theme_fit_score = filter_input(INPUT_POST, 'theme_fit_score', FILTER_VALIDATE_INT);
  $is_blind = isset($_POST['is_blind']) ? 1 : 0;
  $memo = $_POST['memo'] ?? '';

  // バリデーション
  if (!$event_id) {
    $error = 'イベントIDが無効です。';
  } elseif ($owner_label === '' || $wine_name === '') {
    $error = '持参者名とワイン名は必須です。';
  } else {
    // ログインユーザーIDがあれば記録
    $brought_by_user_id = $_SESSION['user_id'] ?? null;
    $brought_by_type = $brought_by_user_id ? 'guest' : null; // 仮: ログインしていればguest扱い

    $sql = 'INSERT INTO bottle_entries (
                    event_id, owner_label, wine_name, producer_name, 
                    country, region, appellation, color, vintage, 
                    bottle_size_ml, est_price_yen, theme_fit_score, 
                    is_blind, memo, brought_by_type, brought_by_user_id,
                    created_at, updated_at
                ) VALUES (
                    :event_id, :owner_label, :wine_name, :producer_name, 
                    :country, :region, :appellation, :color, :vintage, 
                    :bottle_size_ml, :est_price_yen, :theme_fit_score, 
                    :is_blind, :memo, :brought_by_type, :brought_by_user_id,
                    NOW(), NOW()
                )';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
    $stmt->bindValue(':owner_label', $owner_label, PDO::PARAM_STR);
    $stmt->bindValue(':wine_name', $wine_name, PDO::PARAM_STR);
    $stmt->bindValue(':producer_name', $producer_name, PDO::PARAM_STR);
    $stmt->bindValue(':country', $country, PDO::PARAM_STR);
    $stmt->bindValue(':region', $region, PDO::PARAM_STR);
    $stmt->bindValue(':appellation', $appellation, PDO::PARAM_STR);
    $stmt->bindValue(':color', $color, PDO::PARAM_STR);
    $stmt->bindValue(':vintage', $vintage, PDO::PARAM_INT); // NULL if false
    $stmt->bindValue(':bottle_size_ml', $bottle_size_ml, PDO::PARAM_INT);
    $stmt->bindValue(':est_price_yen', $est_price_yen, PDO::PARAM_INT);
    $stmt->bindValue(':theme_fit_score', $theme_fit_score, PDO::PARAM_INT);
    $stmt->bindValue(':is_blind', $is_blind, PDO::PARAM_INT);
    $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
    $stmt->bindValue(':brought_by_type', $brought_by_type, PDO::PARAM_STR);
    $stmt->bindValue(':brought_by_user_id', $brought_by_user_id, PDO::PARAM_INT);

    $stmt->execute();

    header("Location: event_show.php?id={$event_id}");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>VinMemo - ボトル登録</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container">
    <header>
      <h1>ボトルを登録</h1>
      <a href="event_show.php?id=<?= htmlspecialchars($event_id) ?>">← イベントに戻る</a>
    </header>

    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="bottle_new.php" method="post">
      <input type="hidden" name="event_id" value="<?= htmlspecialchars($event_id) ?>">

      <!-- 基本情報 -->
      <div class="form-section">
        <h3>基本情報 / Basic Info</h3>

        <div class="form-group">
          <label>持参者名 / Owner Name <span style="color:var(--danger)">*</span></label>
          <input type="text" name="owner_label" required placeholder="例: Yamada">
        </div>

        <div class="form-group">
          <label>ワイン名 / Wine Name <span style="color:var(--danger)">*</span></label>
          <input type="text" name="wine_name" required placeholder="例: Château Margaux">
        </div>

        <div class="form-group">
          <label>生産者 / Producer</label>
          <input type="text" name="producer_name" placeholder="例: Château Margaux">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>国 / Country</label>
            <input type="text" name="country" placeholder="France">
          </div>
          <div class="form-group">
            <label>地域 / Region</label>
            <input type="text" name="region" placeholder="Bordeaux">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>AOC等 / Appellation</label>
            <input type="text" name="appellation" placeholder="Margaux">
          </div>
          <div class="form-group">
            <label>タイプ / Color <span style="color:var(--danger)">*</span></label>
            <select name="color">
              <option value="sparkling">Sparkling (泡)</option>
              <option value="white">White (白)</option>
              <option value="orange">Orange (オレンジ)</option>
              <option value="rose">Rosé (ロゼ)</option>
              <option value="red" selected>Red (赤)</option>
              <option value="sweet">Sweet (甘口)</option>
              <option value="fortified">Fortified (酒精強化)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 詳細スペック -->
      <div class="form-section">
        <h3>詳細 / Specs</h3>

        <div class="form-row">
          <div class="form-group">
            <label>ヴィンテージ / Vintage (Year)</label>
            <input type="number" name="vintage" placeholder="2015" min="1900" max="2100">
            <small>NVの場合は空欄</small>
          </div>
          <div class="form-group">
            <label>容量(ml) / Size</label>
            <input type="number" name="bottle_size_ml" value="750">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>参考価格(円) / Est. Price</label>
            <input type="number" name="est_price_yen" placeholder="10000">
          </div>
          <div class="form-group">
            <label>テーマ適合度 / Theme Score (1-5)</label>
            <input type="number" name="theme_fit_score" min="1" max="5" value="3">
          </div>
        </div>
      </div>

      <!-- ブラインド設定 & メモ -->
      <div class="form-section">
        <h3>設定 & メモ / Options</h3>

        <div class="form-group">
          <input type="checkbox" id="is_blind" name="is_blind" value="1" style="width:auto; margin:0;">
          <label for="is_blind" style="margin:0; cursor:pointer;">ブラインドで登録する (Blind)</label>
        </div>
        <small style="margin-bottom:20px; display:block;">チェックを入れると、一覧画面でワイン名等が伏せ字になります。</small>

        <div class="form-group">
          <label>メモ / Memo</label>
          <textarea name="memo" rows="4" placeholder="コメントなど"></textarea>
        </div>
      </div>

      <button type="submit" class="button" style="width:100%">登録する</button>
    </form>
  </div>
</body>

</html>