<?php
// events_new.php : ワイン会新規登録フォーム＆登録処理
require_once 'db_connect.php';

$error = null;

// フォーム送信後の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';

    $place = trim($_POST['place'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
    $event_type = $_POST['event_type'] ?? 'BYO'; // DBカラム用 (BYO/ORG/VENUE)

    // 詳細な開催スタイル (Full BYO / Half BYO / No BYO) - メタデータ用
    $event_style_detail = $_POST['event_style_detail'] ?? '';

    $theme_desc = trim($_POST['theme_description'] ?? '');
    $bottle_rules = trim($_POST['bottle_rules'] ?? '');
    $blind_policy = $_POST['blind_policy'] ?? 'none';

    $organizer_note = trim($_POST['memo'] ?? ''); // 幹事メモ

    // ざっくりバリデーション（最低限）
    if ($title === '' || $event_date === '') {
        $error = 'タイトルと開催日は必須です。';
    } else {
        // Validate event_type
        $valid_types = ['BYO', 'ORG', 'VENUE'];
        if (!in_array($event_type, $valid_types, true)) {
            $event_type = 'BYO';
        }

        // メタデータの構築
        // TODO: イベント追加項目を正式カラムに切り出すまでの暫定対応
        $meta_data = [
            'subtitle' => $subtitle,
            'start_time' => $start_time,
            'area' => $area,
            'seats' => $seats,
            'event_style_detail' => $event_style_detail,
            'theme_description' => $theme_desc,
            'bottle_rules' => $bottle_rules,
            'blind_policy' => $blind_policy,
        ];

        // メモカラムに JSON を埋め込む
        // ユーザー入力が見やすいように、ノート本文 + 区切り線 + JSON の形式にする
        $memo_to_save = $organizer_note . "\n\n---META---\n" . json_encode($meta_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        try {
            $sql = 'INSERT INTO events (title, event_date, place, memo, event_type)
                    VALUES (:title, :event_date, :place, :memo, :event_type)';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':event_date', $event_date, PDO::PARAM_STR);
            $stmt->bindValue(':place', $place, PDO::PARAM_STR);
            $stmt->bindValue(':memo', $memo_to_save, PDO::PARAM_STR);
            $stmt->bindValue(':event_type', $event_type, PDO::PARAM_STR);
            $stmt->execute();

            header('Location: events.php');
            exit;
        } catch (PDOException $e) {
            $error = '登録エラー: ' . $e->getMessage();
        }
    }
}

$page_title = 'VinMemo - New Event';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container bottle-page"> <!-- style.css の bottle-page 用スタイルを流用 -->
        <header class="page-header">
            <h1>Create New Event</h1>
            <a class="back-link" href="events.php">← Back to List</a>
        </header>

        <?php if (!empty($error)): ?>
            <div class="error-msg">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="events_new.php" method="post" class="bottle-form">

            <!-- 1. 基本情報 / Basic info -->
            <div class="form-section">
                <h3>基本情報 / Basic info</h3>

                <div class="form-group">
                    <label>イベント名 / Event title <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="title" placeholder="例：第5回 ブルゴーニュ会"
                        value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>サブタイトル / Subtitle</label>
                    <input type="text" name="subtitle" placeholder="例：〜ジュヴレ・シャンベルタンの魅力〜"
                        value="<?= isset($_POST['subtitle']) ? htmlspecialchars($_POST['subtitle']) : '' ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>開催日 / Date <span style="color:var(--danger)">*</span></label>
                        <input type="date" name="event_date"
                            value="<?= isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : '' ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label>開始時間 / Start time</label>
                        <input type="time" name="start_time"
                            value="<?= isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : '' ?>">
                    </div>
                </div>
            </div>

            <!-- 2. 会場・スタイル / Venue & style -->
            <div class="form-section">
                <h3>会場・スタイル / Venue & style</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>会場名 / Venue name</label>
                        <input type="text" name="place" placeholder="例：Restaurant Vin"
                            value="<?= isset($_POST['place']) ? htmlspecialchars($_POST['place']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label>エリア / Area</label>
                        <input type="text" name="area" placeholder="例：六本木"
                            value="<?= isset($_POST['area']) ? htmlspecialchars($_POST['area']) : '' ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>想定人数 / Seats</label>
                        <input type="number" name="seats" placeholder="例：8"
                            value="<?= isset($_POST['seats']) ? htmlspecialchars($_POST['seats']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>DB登録タイプ / DB Type</label>
                        <div class="radio-row">
                            <label><input type="radio" name="event_type" value="BYO" checked> BYO (持参)</label>
                            <label><input type="radio" name="event_type" value="ORG"> ORG (主催)</label>
                            <label><input type="radio" name="event_type" value="VENUE"> VENUE (店舗)</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>イベントスタイル / Style Detail</label>
                    <select name="event_style_detail">
                        <option value="">選択してください</option>
                        <option value="full_byo">Full BYO（全員持ち寄り）</option>
                        <option value="half_byo">Half BYO（店ワイン＋持ち寄り）</option>
                        <option value="no_byo">No BYO（主催者セレクト/ペアリング）</option>
                    </select>
                </div>
            </div>

            <!-- 3. テーマ・ルール / Theme & rules -->
            <div class="form-section">
                <h3>テーマ・ルール / Theme & rules</h3>

                <div class="form-group">
                    <label>テーマ詳細 / Theme description</label>
                    <textarea name="theme_description" rows="3"
                        placeholder="テーマについての詳しい説明"><?= isset($_POST['theme_description']) ? htmlspecialchars($_POST['theme_description']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label>持ち寄りルール / Bottle rules</label>
                    <textarea name="bottle_rules" rows="3"
                        placeholder="例：1人1本、予算1万円以上、2015年以降など"><?= isset($_POST['bottle_rules']) ? htmlspecialchars($_POST['bottle_rules']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label>ブラインド方針 / Blind policy</label>
                    <select name="blind_policy">
                        <option value="none">Label Open（ラベル出し）</option>
                        <option value="semi">Semi Blind（一部ブラインド）</option>
                        <option value="full">Full Blind（完全ブラインド）</option>
                    </select>
                </div>
            </div>

            <!-- 4. 幹事メモ / Organizer note -->
            <div class="form-section">
                <h3>幹事メモ / Organizer note</h3>
                <div class="form-group">
                    <label>管理者用メモ / Secret Note (Not visible to guests)</label>
                    <textarea name="memo" rows="4"
                        placeholder="予算管理、連絡事項など。ここに入力した内容は参加者には表示されません（※将来的な実装で制御予定）。"><?= isset($_POST['memo']) ? htmlspecialchars($_POST['memo']) : '' ?></textarea>
                </div>
            </div>

            <button type="submit" class="button" style="width:100%">この内容でイベントを作成する</button>
        </form>
    </div>
</body>

</html>