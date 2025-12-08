<?php
// list.php

// ▼ セッション＆ログインチェックを最初に
session_start();
require_once 'funcs.php';
loginCheck();

// ▼ そのあとでDB接続
require_once 'db_connect.php';
$pdo = get_pdo();


// データ取得
$sql = 'SELECT * FROM bottle_entries WHERE event_id = 1 ORDER BY created_at ASC, id ASC';
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ブラインド用：フラグが1ならマスク、それ以外はエスケープして表示
function mask_if_blind($value, $blindFlag, $mask = '???')
{
  if ((int) $blindFlag === 1) {
    return $mask;
  }
  return h($value);
}

// 集計用
$total_count = count($rows);
$type_counts = [];
$price_counts = [];
$theme_fit_sum = 0;

foreach ($rows as $r) {
  // タイプ別
  $type = $r['wine_type'];
  if (!isset($type_counts[$type])) {
    $type_counts[$type] = 0;
  }
  $type_counts[$type]++;

  // 価格帯別
  $pb = $r['price_band'];
  if (!isset($price_counts[$pb])) {
    $price_counts[$pb] = 0;
  }
  $price_counts[$pb]++;

  // テーマ適合度
  $theme_fit_sum += (int) $r['theme_fit'];
}

$theme_fit_avg = $total_count > 0 ? round($theme_fit_sum / $total_count, 2) : 0;
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>BYO登録一覧（ブラインドビュー）</title>
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
  <hr>

  <div class="main-container">
    <h1>BYO登録一覧（ブラインドビュー）</h1>

    <div class="nav-links no-print">
      <p>
        <a href="register.php">新規登録 / </a>
        <a href="index.html">トップへ / </a>
        <button type="button" onclick="window.print()">この一覧を印刷</button>
      </p>
    </div>

    <?php if ($total_count === 0): ?>

      <p>まだ登録はありません。</p>

    <?php else: ?>

      <!-- サマリー -->
      <section>
        <h2>サマリー</h2>
        <p>登録本数：<?= $total_count ?> 本 / テーマ適合度平均：<?= $theme_fit_avg ?></p>

        <h3>タイプ別本数</h3>
        <ul>
          <?php foreach ($type_counts as $type => $cnt): ?>
            <li><?= h($type) ?>：<?= $cnt ?> 本</li>
          <?php endforeach; ?>
        </ul>

        <h3>価格帯別本数</h3>
        <ul>
          <?php foreach ($price_counts as $pb => $cnt): ?>
            <li><?= h($pb) ?>：<?= $cnt ?> 本</li>
          <?php endforeach; ?>
        </ul>
      </section>

      <!-- 一覧テーブル（blindの0/1は表示しない版） -->
      <table border="1" cellpadding="4" cellspacing="0">
        <tr>
          <th>ID</th>
          <th>名前</th>
          <th>生産者</th>
          <th>ワイン名</th>
          <th>VT</th>
          <th>産地</th>
          <th>タイプ</th>
          <th>価格帯</th>
          <th>テーマ適合</th>
          <th>コメント</th>
          <th>登録日時</th>
        </tr>
        <?php foreach ($rows as $r): ?>
          <?php
          // 産地ラベル（Other + 手入力対応）
          $region_label = $r['region'];
          if ($r['region'] === 'Other' && $r['region_other'] !== '') {
            $region_label = 'Other: ' . $r['region_other'];
          }
          ?>
          <tr>
            <td><?= h($r['id']) ?></td>
            <td><?= h($r['user_name']) ?></td>

            <!-- 生産者：ブラインドなら ??? -->
            <td><?= mask_if_blind($r['wine_producer'], $r['blind_producer'], '???') ?></td>

            <!-- ワイン名：ブラインドなら ??? -->
            <td><?= mask_if_blind($r['wine_name'], $r['blind_wine_name'], '???') ?></td>

            <!-- VT：ブラインドなら XXXX -->
            <td><?= mask_if_blind($r['wine_vintage'], $r['blind_vintage'], 'XXXX') ?></td>

            <!-- 産地：ブラインドなら ??? -->
            <td><?= mask_if_blind($region_label, $r['blind_region'], '???') ?></td>

            <!-- タイプ：今回は常に表示 -->
            <td><?= h($r['wine_type']) ?></td>

            <!-- 価格帯：ブラインドなら ??? -->
            <td><?= mask_if_blind($r['price_band'], $r['blind_price_band'], '???') ?></td>

            <!-- テーマ適合：常に表示 -->
            <td><?= h($r['theme_fit']) ?></td>

            <!-- コメント：ブラインドなら固定文言 -->
            <td>
              <?php if ((int) $r['blind_comment'] === 1): ?>
                <?= '[ブラインドコメント]' ?>
              <?php else: ?>
                <?= nl2br(h($r['comment'])) ?>
              <?php endif; ?>
            </td>

            <td><?= h($r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>

    <?php endif; ?>
  </div>
</body>

</html>