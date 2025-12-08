<?php
session_start();
require_once('funcs.php');
loginCheck();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BYO：ワイン登録ページ</title>
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
  <header>
    <h1>BYO：ワイン登録ページ</h1>
  </header>

  <main>
    <!-- 会の概要 -->
    <section>
      <h2>恒例ワイン会のご案内</h2>
      <p>
        <strong>1.日付：</strong>2025年12月23日（火） 19:00〜<br>
        <strong>2.会場：</strong>Ăn Đi 神宮前<br>
        <strong>3.住所：</strong>東京都渋谷区神宮前3-42-12 JPR神宮前ビルB1F<br>
        <strong>4.TEL：</strong>03-5775-4560<br>
        <strong>5.URL：</strong><a href="https://an-di.jp/" target="_blank" rel="noopener">https://an-di.jp/</a><br>
        <strong>6.会費：</strong>15,500円（税・サ込）<br>
        <strong>7.テーマ：</strong>世界のピノ・ノワール中心に、多様なワインを楽しむ会<br>
        <strong>8.参加予定人数：</strong>10名
      </p>
      <p style="font-size:14px; line-height:1.7;">
        ▽お手数ですがご持参ワインの登録をお願いします。<br>
        ▽会の趣旨からしてピノ・ノワールが多くなるとは存じますが、泡・白・ピノ・ノワール以外の赤も少量ならOKです。<br>
        ▽目安としては <strong>泡２：白２：赤（PN）５：赤（その他）１</strong> を考えています。<br>
        ▽登録内容を見ながらバランス調整を行うため、エントリーは原則先着順となります。<br>
        ▽目安の比率から大きく逸脱した場合には、幹事より変更のご相談をさせていただくことがあります。<br>
      </p>
    </section>

    <!-- アクション -->
    <section>
      <h2>持ち寄りワインのエントリー</h2>
      <div class="nav-links actions">
        <a href="register.php" class="btn-main">▶ ワイン登録フォーム</a>
        <a href="list.php" class="btn-sub">現在のワインリストを見る</a>
        <!-- 幹事専用：URLを直接伝える想定 -->
        <!-- <a href="host_list.php" class="btn-sub">幹事用サマリー</a> -->
      </div>
      <p class="note">
        * 幹事の方はこのページのURLを参加者全員に共有してください。<br>
        * 1人で複数本の登録も可能です（登録完了画面から続きを登録）。<br>
        * ブラインド希望の項目は、フォーム内でチェックを入れてください。
      </p>
    </section>
  </main>
  </div>
</body>
</html>
