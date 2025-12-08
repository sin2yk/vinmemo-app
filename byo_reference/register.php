<?php
// register.php（BYOボトル登録フォーム）

session_start();
require_once 'funcs.php';    // ← h(), loginCheck() など
loginCheck();                // ← ログイン必須にしたくなければ一旦コメントアウトでも可

require_once 'db_connect.php';  // ← get_pdo()/db_conn() 用
$pdo = get_pdo();               // もしこのファイルでDB使うなら
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>BYO：ワイン登録フォーム</title>
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
  <div class="main-container">
    <h1>BYO：ワイン登録フォーム</h1>
    <div class="nav-links no-print">
      <a href="index.html">トップに戻る</a> / <a href="list.php">登録一覧を見る</a>
    </div>

    <hr>

    <h2>ご持参ワインの情報</h2>
    <p>※表示を伏せたい項目（ブラインド時）は、下のチェックボックスにチェックを入れてください。</p>

    <form action="insert.php" method="post">
      <!-- この会のID（当面は固定で1） -->
      <input type="hidden" name="event_id" value="1">

      <!-- お名前 -->
      <p>
        お名前（ニックネーム可）<br>
        <input type="text" name="user_name" required style="width: 300px;">
      </p>

      <!-- ブラインド：どこを隠すか -->
      <fieldset style="max-width: 600px;">
        <legend>ブラインド設定（隠したい項目）</legend>
        <label><input type="checkbox" name="blind_producer" value="1"> 生産者（Producer）を隠す</label><br>
        <label><input type="checkbox" name="blind_wine_name" value="1"> ワイン名（Cuvée）を隠す</label><br>
        <label><input type="checkbox" name="blind_vintage" value="1"> ヴィンテージを隠す</label><br>
        <label><input type="checkbox" name="blind_region" value="1"> 産地（Region）を隠す</label><br>
        <label><input type="checkbox" name="blind_price_band" value="1"> 価格帯を隠す</label><br>
        <label><input type="checkbox" name="blind_comment" value="1"> コメントを隠す</label><br>
      </fieldset>

      <hr>

      <!-- 生産者 -->
      <p>
        生産者（Producer）<br>
        <input type="text" name="wine_producer" placeholder="例：Emmanuel Rouget" required style="width: 400px;">
      </p>

      <!-- ワイン名 -->
      <p>
        ワイン名（Cuvée）<br>
        <input type="text" name="wine_name" placeholder="例：Echézeaux" required style="width: 400px;">
      </p>

      <!-- ヴィンテージ -->
      <p>
        ヴィンテージ（VT）<br>
        <select name="wine_vintage" required>
          <option value="">選択してください</option>
          <option value="NV">NV（ノンヴィンテージ）</option>
          <option value="1970以前">1970以前</option>
          <?php for ($y = 2025; $y >= 1971; $y--): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </p>

      <!-- 産地 -->
      <p>
        産地（Region）<br>
        <select name="region">
          <option value="">選択してください</option>
          <option value="Bourgogne">ブルゴーニュ</option>
          <option value="Champagne">シャンパーニュ</option>
          <option value="Bordeaux">ボルドー</option>
          <option value="Rhône">ローヌ</option>
          <option value="Loire">ロワール</option>
          <option value="Alsace">アルザス</option>
          <option value="Germany">ドイツ</option>
          <option value="Italy">イタリア</option>
          <option value="Spain">スペイン</option>
          <option value="California">カリフォルニア</option>
          <option value="Other">その他</option>
        </select>
        &nbsp;
        その他の場合：<input type="text" name="region_other" style="width: 200px;" placeholder="任意でご記入ください">
      </p>

      <!-- タイプ（プルダウンが嫌なら将来ラジオ等に変更予定。値は固定） -->
      <p>
        タイプ<br>
        <label><input type="radio" name="wine_type" value="sparkling" required> 泡</label>
        <label><input type="radio" name="wine_type" value="white"> 白</label>
        <label><input type="radio" name="wine_type" value="orange"> オレンジ</label>
        <label><input type="radio" name="wine_type" value="red_pinot"> 赤（ピノ系）</label>
        <label><input type="radio" name="wine_type" value="red_other"> 赤（その他）</label>
        <label><input type="radio" name="wine_type" value="rose"> ロゼ</label>
        <label><input type="radio" name="wine_type" value="sweet"> 甘口</label>
        <label><input type="radio" name="wine_type" value="fortified"> 酒精強化</label>
      </p>

      <!-- 価格帯（自己申告） -->
      <p>
        価格帯（自己申告）<br>
        <select name="price_band" required>
          <option value="">選択してください</option>
          <option value="casual">カジュアル（〜5,000円くらい）</option>
          <option value="bistro">ビストロ（〜10,000円くらい）</option>
          <option value="fine">しっかり（〜20,000円くらい）</option>
          <option value="luxury">ラグジュアリー（〜50,000円くらい）</option>
          <option value="icon">アイコン級（それ以上）</option>
        </select>
      </p>

      <!-- テーマ適合度 -->
      <p>
        テーマ適合度（自己評価）<br>
        <select name="theme_fit" required>
          <option value="1">1：かなりズレているかも</option>
          <option value="2">2</option>
          <option value="3" selected>3：まあまあ合っている</option>
          <option value="4">4</option>
          <option value="5">5：ドンピシャだと思う</option>
        </select>
      </p>

      <!-- コメント -->
      <p>
        コメント（任意）<br>
        <textarea name="comment" rows="4" cols="60" placeholder="例：香りが開くまで少し時間がかかりそうなので、後半に出してほしいです。"></textarea>
      </p>

      <!-- 編集・ブラインド解除用パスワード -->
      <p>
        編集・ブラインド解除用パスワード（任意）<br>
        <input type="password" name="edit_password" style="width: 200px;">
        <br><small>※将来の「内容修正」「ブラインド解除」機能で使用予定です。未設定でも構いません。</small>
      </p>

      <p>
        <button type="submit">この内容で登録する</button>
      </p>
    </form>
  </div>
</body>

</html>