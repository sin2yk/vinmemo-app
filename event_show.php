<?php
// event_show.php : イベント詳細表示

require_once 'db_connect.php';

// まず初期化（エラー時にも $bottles が未定義にならないように）
$bottles = [];

// IDパラメータの検証
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === null || $id === false) {
    $error = '無効なIDです。';
} else {
    // イベント本体を取得
    $sql = 'SELECT * FROM events WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $error = 'イベントが見つかりませんでした。';
    } else {
        // ★ イベントが見つかった場合だけ、紐づくボトル一覧を取得
        $sql = 'SELECT * FROM bottle_entries 
                WHERE event_id = :event_id
                ORDER BY id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':event_id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $bottles = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <!-- ★ ここに「ボトル追加」リンクを置く -->
            <p>
                <a href="bottle_new.php?event_id=<?= htmlspecialchars($id) ?>">
                    ＋ このイベントにボトルを追加
                </a>
            </p>
            <h2>ボトル一覧</h2>

            <?php if (count($bottles) === 0): ?>
                <p>まだボトルは登録されていません。</p>
            <?php else: ?>
                <?php foreach ($bottles as $index => $b): ?>
                    <div class="bottle-card" style="position:relative;">
                        <div style="font-weight:bold; font-size:1.1em;">
                            #<?= $index + 1 ?>
                            <span style="color:var(--accent); margin-left:10px;">
                                <?= htmlspecialchars($b['owner_label']) ?>
                            </span>
                        </div>

                        <div style="margin: 10px 0;">
                            <?php if ($b['is_blind']): ?>
                                <span
                                    style="background:var(--accent); color:#000; padding:2px 6px; border-radius:4px; font-size:0.8em; font-weight:bold;">BLIND</span>
                            <?php endif; ?>

                            <span style="font-size:1.2em; font-weight:bold;">
                                <?= htmlspecialchars($b['wine_name']) ?>
                            </span>
                        </div>

                        <div style="font-size:0.9em; color:var(--text-muted); line-height:1.5;">
                            <?php
                            $details = [];
                            if ($b['vintage'])
                                $details[] = $b['vintage'];
                            if ($b['producer_name'])
                                $details[] = $b['producer_name'];
                            if ($b['country'])
                                $details[] = $b['country'];
                            if ($b['region'])
                                $details[] = $b['region'];
                            if ($b['appellation'])
                                $details[] = $b['appellation'];
                            if ($b['color'])
                                $details[] = ucfirst($b['color']);
                            echo implode(' / ', array_map(function ($s) {
                                return htmlspecialchars($s); }, $details));
                            ?>
                        </div>

                        <?php if ($b['est_price_yen'] || $b['theme_fit_score']): ?>
                            <div style="font-size:0.85em; margin-top:5px; color:#aaa;">
                                <?php if ($b['est_price_yen']): ?>
                                    参考価格: ¥<?= number_format($b['est_price_yen']) ?>
                                <?php endif; ?>
                                <?php if ($b['theme_fit_score']): ?>
                                    / テーマ適合: <?= $b['theme_fit_score'] ?>/5
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($b['memo']): ?>
                            <div style="margin-top:8px; padding-top:8px; border-top:1px dashed #555; font-size:0.9em;">
                                <?= nl2br(htmlspecialchars($b['memo'])) ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top:15px; text-align:right;">
                            <a href="bottle_edit.php?id=<?= $b['id'] ?>" class="button"
                                style="padding:5px 10px; font-size:0.9em; margin-right:5px;">編集</a>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?= $b['id'] ?>)" class="button"
                                style="padding:5px 10px; font-size:0.9em; background-color:var(--danger); color:#fff;">削除</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <script>
                function confirmDelete(id) {
                    if (confirm('本当に削除しますか？\nAre you sure you want to delete this bottle?')) {
                        window.location.href = 'bottle_delete.php?id=' + id;
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</body>

</html>