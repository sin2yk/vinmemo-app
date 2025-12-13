<?php
// bottle_new.php : Add new bottle (uses partials/bottle_form.php)

require_once __DIR__ . '/auth_required.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php';

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
    $form['guest_email'] = trim($_POST['guest_email'] ?? '');

    // --- バリデーション ---
    if ($form['owner_label'] === '') {
        $errors[] = 'お名前 / Your Name は必須です。';
    }
    // Guest Email Check
    if (!isset($_SESSION['user_id']) && $form['guest_email'] === '') {
        $errors[] = 'メールアドレス / Email is required for guests.';
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

        // Generate Edit Token
        $editToken = bin2hex(random_bytes(32));

        // Determine brought_by_user_id
        $broughtByUserId = $_SESSION['user_id'] ?? null;
        // Normalize Guest Email
        $guestEmail = $broughtByUserId ? null : mb_strtolower($form['guest_email']);

        $sql = "INSERT INTO bottle_entries (
                    event_id, brought_by_user_id, guest_email, owner_label,
                    producer_name, wine_name, vintage, bottle_size_ml,
                    color, est_price_yen, theme_fit_score, memo,
                    is_blind, blind_reveal_level, edit_token, created_at
                ) VALUES (
                    :event_id, :brought_by_user_id, :guest_email, :owner_label,
                    :producer_name, :wine_name, :vintage, :bottle_size_ml,
                    :color, :est_price_yen, :theme_fit_score, :memo,
                    :is_blind, 'none', :edit_token, NOW()
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->bindValue(':brought_by_user_id', $broughtByUserId, PDO::PARAM_INT); // NULL allowed
        $stmt->bindValue(':guest_email', $guestEmail, PDO::PARAM_STR); // NULL allowed
        $stmt->bindValue(':owner_label', $form['owner_label'], PDO::PARAM_STR);
        $stmt->bindValue(':producer_name', $form['producer_name'], PDO::PARAM_STR);
        $stmt->bindValue(':wine_name', $form['wine_name'], PDO::PARAM_STR);
        $stmt->bindValue(':vintage', ($form['vintage'] ?: null), PDO::PARAM_STR);
        $stmt->bindValue(':bottle_size_ml', $form['bottle_size_ml'], PDO::PARAM_INT);
        $stmt->bindValue(':color', $form['color'], PDO::PARAM_STR);
        $stmt->bindValue(':est_price_yen', $est_price_yen, PDO::PARAM_STR);
        $stmt->bindValue(':theme_fit_score', $form['theme_fit_score'], PDO::PARAM_INT);
        $stmt->bindValue(':memo', ($form['memo'] ?: null), PDO::PARAM_STR);
        $stmt->bindValue(':is_blind', $form['is_blind'], PDO::PARAM_INT);
        $stmt->bindValue(':edit_token', $editToken, PDO::PARAM_STR);

        $stmt->execute();

        // Redirect logic
        if ($broughtByUserId) {
            // Organizer / Registered User
            header('Location: event_show.php?id=' . $event_id);
        } else {
            // Guest -> Success Page with Link
            header('Location: bottle_created.php?event_id=' . $event_id . '&token=' . $editToken);
        }
        exit;
    }
}



// 5. 画面描画
$page_title = 'VinMemo - Register Bottle / ボトル登録';
require_once 'layout/header.php';
?>
<div class="page-container">
    <header class="page-header">
        <h1>Register Bottle / ボトル登録</h1>
        <a class="back-link btn-secondary" href="event_show.php?id=<?= h($event_id) ?>">
            ← Back to the event wine list / このイベントのワインリストに戻る
        </a>
        <div style="margin-top:5px; color:var(--text-muted);">
            Event / イベント:
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