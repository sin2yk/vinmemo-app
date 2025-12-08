<?php
// events_new.php : ワイン会新規登録フォーム＆登録処理
require_once 'db_connect.php';

// フォーム送信後の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $place = $_POST['place'] ?? '';
    $memo = $_POST['memo'] ?? '';

    $event_type = $_POST['event_type'] ?? 'BYO';

    // ざっくりバリデーション（最低限）
    if ($title === '' || $event_date === '') {
        $error = 'Title and Date are required.';
    } else {
        // Validate event_type
        $valid_types = ['BYO', 'ORG', 'VENUE'];
        if (!in_array($event_type, $valid_types, true)) {
            $event_type = 'BYO';
        }

        $sql = 'INSERT INTO events (title, event_date, place, memo, event_type)
                VALUES (:title, :event_date, :place, :memo, :event_type)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':event_date', $event_date, PDO::PARAM_STR);
        $stmt->bindValue(':place', $place, PDO::PARAM_STR);
        $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
        $stmt->bindValue(':event_type', $event_type, PDO::PARAM_STR);
        $stmt->execute();

        header('Location: events.php');
        exit;
    }
}

$page_title = 'VinMemo - New Event';
require_once 'layout/header.php';
?>

<header style="margin-bottom:20px;">
    <h1>Create New Event</h1>
    <a href="events.php">← Back to List</a>
</header>

<?php if (!empty($error)): ?>
    <div class="error-msg">
        <?= h($error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <form action="events_new.php" method="post">
        <label>Title (Required)</label>
        <input type="text" name="title" placeholder="e.g. Bordeaux Night" value="<?= isset($title) ? h($title) : '' ?>"
            required>

        <label>Date (Required)</label>
        <input type="date" name="event_date" value="<?= isset($event_date) ? h($event_date) : '' ?>" required>

        <div class="form-group">
            <label>イベント種別 / Event type</label>
            <div class="radio-row">
                <label>
                    <input type="radio" name="event_type" value="BYO" <?= (!isset($event_type) || $event_type === 'BYO') ? 'checked' : '' ?>>
                    BYO（持参ワイン会）
                </label>
                <label>
                    <input type="radio" name="event_type" value="ORG" <?= (isset($event_type) && $event_type === 'ORG') ? 'checked' : '' ?>>
                    主催者ワイン
                </label>
                <label>
                    <input type="radio" name="event_type" value="VENUE" <?= (isset($event_type) && $event_type === 'VENUE') ? 'checked' : '' ?>>
                    店舗ログ（通常営業）
                </label>
            </div>
        </div>

        <label>Place</label>
        <input type="text" name="place" placeholder="e.g. Restaurant X" value="<?= isset($place) ? h($place) : '' ?>">

        <label>Memo</label>
        <textarea name="memo" cols="50" rows="4" placeholder="Details..."><?= isset($memo) ? h($memo) : '' ?></textarea>

        <button type="submit">この内容でイベントを作成する</button>
    </form>
</div>

<?php require_once 'layout/footer.php'; ?>