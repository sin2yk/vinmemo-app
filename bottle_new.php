<?php
// bottle_new.php : Add new bottle (uses partials/bottle_form.php)

require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. イベントID取得（GET優先、POSTは再送信用）
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
}
if (!$event_id) {
    die('Event ID is required.');
}

// 2. イベント取得（タイトル表示用）
$stmt = $pdo->prepare('SELECT id, title, event_date, place FROM events WHERE id = :id');
$stmt->execute([':id' => $event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    die('Event not found.');
}

// 3. フォーム用の初期値
$form = [
    'owner_label' => $_SESSION['display_name'] ?? '',
    'producer_name' => '',
    'wine_name' => '',
    'vintage' => 0,
    'bottle_size_ml' => 750,
    'country' => '',
    'region' => '',
    'appellation' => '',
    'color' => 'red',        // デフォルト赤
    'price_band' => 'fine',       // casual / bistro / fine / luxury / icon
    'theme_fit_score' => 3,            // 0〜5
    'is_blind' => 0,
    'memo' => '',
];

$errors = [];

// 4. POST されたら値詰め＋バリデーション＋INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 入力値を $form に詰め直す（編集時と同じ構造）
    $form['owner_label'] = trim($_POST['owner_label'] ?? '');
    $form['producer_name'] = trim($_POST['producer_name'] ?? '');
    $form['wine_name'] = trim($_POST['wine_name'] ?? '');
    $form['vintage'] = (int) ($_POST['vintage'] ?? 0);
    $form['bottle_size_ml'] = (int) ($_POST['bottle_size_ml'] ?? 750);
    $form['country'] = trim($_POST['country'] ?? '');
    $form['region'] = trim($_POST['region'] ?? '');
    $form['appellation'] = trim($_POST['appellation'] ?? '');
    $form['color'] = $_POST['color'] ?? 'red';
    $form['price_band'] = $_POST['price_band'] ?? 'fine';
    $form['theme_fit_score'] = (int) ($_POST['theme_fit_score'] ?? 3);
    $form['is_blind'] = isset($_POST['is_blind']) ? 1 : 0;
    $form['memo'] = trim($_POST['memo'] ?? '');

    // --- バリデーション ---
    if ($form['owner_label'] === '') {
        $errors[] = 'お名前 / Your Name は必須です。';
    }
    if ($form['producer_name'] === '') {
        $errors[] = '生産者 / Producer は必須です。';
    }
    if ($form['wine_name'] === '') {
        $errors[] = 'ワイン名 / Wine Name は必須です。';
    }

    $validColors = ['sparkling', 'white', 'orange', 'rose', 'red', 'sweet', 'fortified'];
    if (!in_array($form['color'], $validColors, true)) {
        $form['color'] = 'red';
    }

    $validBands = ['casual', 'bistro', 'fine', 'luxury', 'icon'];
    if (!in_array($form['price_band'], $validBands, true)) {
        $form['price_band'] = 'fine';
    }

    if ($form['theme_fit_score'] < 0 || $form['theme_fit_score'] > 5) {
        $form['theme_fit_score'] = 3;
    }

    // エラーなしなら DB へ INSERT
    if (empty($errors)) {

        // price_band → est_price_yen のざっくりマッピング
        $bandToPrice = [
            'casual' => 3000,
            'bistro' => 7000,
            'fine' => 15000,
            'luxury' => 30000,
            'icon' => 80000,
        ];
        $est_price_yen = $bandToPrice[$form['price_band']] ?? null;

        $sql = "INSERT INTO bottle_entries (
                    event_id,
                    owner_label,
                    wine_name,
                    producer_name,
                    country,
                    region,
                    appellation,
                    color,
                    vintage,
                    bottle_size_ml,
                    est_price_yen,
                    theme_fit_score,
                    is_blind,
                    memo,
                    created_at,
                    updated_at
                ) VALUES (
                    :event_id,
                    :owner_label,
                    :wine_name,
                    :producer_name,
                    :country,
                    :region,
                    :appellation,
                    :color,
                    :vintage,
                    :bottle_size_ml,
                    :est_price_yen,
                    :theme_fit_score,
                    :is_blind,
                    :memo,
                    NOW(),
                    NOW()
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':event_id' => $event_id,
            ':owner_label' => $form['owner_label'],
            ':wine_name' => $form['wine_name'],
            ':producer_name' => $form['producer_name'],
            ':country' => $form['country'] ?: null,
            ':region' => $form['region'] ?: null,
            ':appellation' => $form['appellation'] ?: null,
            ':color' => $form['color'],
            ':vintage' => $form['vintage'] ?: null,
            ':bottle_size_ml' => $form['bottle_size_ml'] ?: 750,
            ':est_price_yen' => $est_price_yen,
            ':theme_fit_score' => $form['theme_fit_score'],
            ':is_blind' => $form['is_blind'],
            ':memo' => $form['memo'] ?: null,
        ]);

        header('Location: event_show.php?id=' . $event_id);
        exit;
    }
}

// 5. 画面描画
$page_title = 'VinMemo - ボトル登録 / Register Bottle';
require_once 'layout/header.php';
?>
<div class="page-container">
    <header class="page-header">
        <h1>ボトル登録 / Register Bottle</h1>
        <a class="back-link" href="event_show.php?id=<?= h($event_id) ?>">
            ← このイベントのワインリストに戻る / Back to the event wine list
        </a>
        <div style="margin-top:5px; color:var(--text-muted);">
            イベント / Event:
            <?= h($event['title']) ?>
            (<?= h($event['event_date']) ?>)
            <?php if (!empty($event['place'])): ?>
                @ <?= h($event['place']) ?>
            <?php endif; ?>
        </div>
    </header>

    <?php
    $mode = 'new';
    $returnUrl = 'event_show.php?id=' . $event_id;
    include __DIR__ . '/partials/bottle_form.php';
    ?>
</div>

<?php require_once 'layout/footer.php'; ?>