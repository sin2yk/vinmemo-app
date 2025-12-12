<?php
// bottle_edit.php : Edit an existing bottle entry
// Refactored to include strict permission checks and fixed update logic.

require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. Fetch Bottle & Event (Unified Access) ---

// Determine ID and Token from GET or POST (to handle both display and submit)
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$token = $_POST['token'] ?? $_POST['et'] ?? $_GET['token'] ?? $_GET['et'] ?? '';

$bottle = null;

if ($id) {
    // Priority: Fetch by ID
    $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($token) {
    // Fallback: Fetch by Token (if ID not provided, though ID should usually be there)
    $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE edit_token = :token");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$bottle) {
    die('Bottle not found or invalid request.');
}

// Ensure ID matches if both were provided (Security check)
if ($id && $token && $bottle['edit_token'] !== $token && $bottle['id'] != $id) {
    // If fetched by ID but token mismatches? 
    // We will check token validity in permissions. 
    // Just ensure we have the bottle.
}

// Fetch Event
$stmt = $pdo->prepare('SELECT id, title, event_date, place, organizer_user_id FROM events WHERE id = :id');
$stmt->execute([':id' => $bottle['event_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die('Associated event not found.');
}


// --- 2. Permission Flags ---

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

// ① Event Organizer?
$isEventOrganizer = false;
if ($isLoggedIn && isset($event['organizer_user_id'])) {
    $isEventOrganizer = ($currentUserId === (int) $event['organizer_user_id']);
}

// ② Bottle Owner?
$isBottleOwner = false;
if ($isLoggedIn && isset($bottle['brought_by_user_id'])) {
    $isBottleOwner = ($currentUserId === (int) $bottle['brought_by_user_id']);
}

// ③ Valid Edit Token?
$hasValidEditToken = false;
if ($token && !empty($bottle['edit_token'])) {
    $hasValidEditToken = hash_equals($bottle['edit_token'], $token);
}

// --- 3. Authorization Check ---

$canEdit = $isEventOrganizer || $isBottleOwner || $hasValidEditToken;

if (!$canEdit) {
    // Debug info if needed, but keeping message simple as requested
    die('Access Denied: You do not have permission to edit this bottle.');
}


// --- 4. POST Handler (Update) ---

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    // Double check ID integrity
    if ($p_id !== $bottle['id']) {
        die('ID Mismatch Error');
    }

    // Form Processing (similar to bottle_new.php)
    $form = [
        'owner_label' => trim($_POST['owner_label'] ?? ''),
        'producer_name' => trim($_POST['producer_name'] ?? ''),
        'wine_name' => trim($_POST['wine_name'] ?? ''),
        'vintage' => (int) ($_POST['vintage'] ?? 0),
        'bottle_size_ml' => (int) ($_POST['bottle_size_ml'] ?? 750),
        'country' => trim($_POST['country'] ?? ''),
        'region' => trim($_POST['region'] ?? ''),
        'appellation' => trim($_POST['appellation'] ?? ''),
        'color' => $_POST['color'] ?? 'red',
        'price_band' => $_POST['price_band'] ?? 'fine',
        'theme_fit_score' => (int) ($_POST['theme_fit_score'] ?? 3),
        'is_blind' => isset($_POST['is_blind']) ? 1 : 0,
        'memo' => trim($_POST['memo'] ?? ''),
        'guest_email' => trim($_POST['guest_email'] ?? '')
    ];

    // Validation
    if ($form['owner_label'] === '')
        $errors[] = 'お名前 / Your Name is required.';
    if ($form['producer_name'] === '')
        $errors[] = '生産者 / Producer is required.';
    if ($form['wine_name'] === '')
        $errors[] = 'ワイン名 / Wine Name is required.';

    // Email Check: If guest (not logged in), email is required?
    // Logic: If user is logged in, email likely ignored or pre-filled. 
    // If not logged in and existing email is empty, require it? 
    // bottle_new.php requires it for guests.
    if (!$isLoggedIn && empty($bottle['brought_by_user_id']) && $form['guest_email'] === '') {
        // Should we enforce it on edit? Let's say yes if it was previously empty or user updates it.
        // But maybe they just want to edit name. Let's keep it optional on edit if already set?
        // bottle_new checks: (!isset($_SESSION['user_id']) && $form['guest_email'] === '')
        // We'll follow suit.
        $errors[] = 'メールアドレス / Email is required for guests.';
    }

    $validColors = ['sparkling', 'white', 'orange', 'rose', 'red', 'sweet', 'fortified'];
    if (!in_array($form['color'], $validColors, true))
        $form['color'] = 'red';

    $validBands = ['casual', 'bistro', 'fine', 'luxury', 'icon'];
    if (!in_array($form['price_band'], $validBands, true))
        $form['price_band'] = 'fine';

    if ($form['theme_fit_score'] < 0 || $form['theme_fit_score'] > 5)
        $form['theme_fit_score'] = 3;

    if (empty($errors)) {
        // Validation Passed: Execute Update

        // Map price_band to yen
        $bandToPrice = [
            'casual' => 3000,
            'bistro' => 7000,
            'fine' => 15000,
            'luxury' => 30000,
            'icon' => 80000,
        ];
        $est_price_yen = $bandToPrice[$form['price_band']] ?? null;

        // Prepare SQL
        $sql = "UPDATE bottle_entries SET 
                    owner_label = :owner_label,
                    producer_name = :producer_name,
                    wine_name = :wine_name,
                    vintage = :vintage,
                    bottle_size_ml = :bottle_size_ml,
                    country = :country,
                    region = :region,
                    appellation = :appellation,
                    color = :color,
                    est_price_yen = :est_price_yen,
                    theme_fit_score = :theme_fit_score,
                    is_blind = :is_blind,
                    memo = :memo,
                    guest_email = :guest_email
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':owner_label', $form['owner_label'], PDO::PARAM_STR);
        $stmt->bindValue(':producer_name', $form['producer_name'], PDO::PARAM_STR);
        $stmt->bindValue(':wine_name', $form['wine_name'], PDO::PARAM_STR);
        $stmt->bindValue(':vintage', ($form['vintage'] ?: null), PDO::PARAM_STR); // use STR to allow null
        $stmt->bindValue(':bottle_size_ml', $form['bottle_size_ml'], PDO::PARAM_INT);
        $stmt->bindValue(':country', $form['country'], PDO::PARAM_STR);
        $stmt->bindValue(':region', $form['region'], PDO::PARAM_STR);
        $stmt->bindValue(':appellation', $form['appellation'], PDO::PARAM_STR);
        $stmt->bindValue(':color', $form['color'], PDO::PARAM_STR);
        $stmt->bindValue(':est_price_yen', $est_price_yen, PDO::PARAM_STR);
        $stmt->bindValue(':theme_fit_score', $form['theme_fit_score'], PDO::PARAM_INT);
        $stmt->bindValue(':is_blind', $form['is_blind'], PDO::PARAM_INT);
        $stmt->bindValue(':memo', ($form['memo'] ?: null), PDO::PARAM_STR);
        $stmt->bindValue(':guest_email', ($form['guest_email'] ?: null), PDO::PARAM_STR);
        $stmt->bindValue(':id', $bottle['id'], PDO::PARAM_INT);

        $stmt->execute();

        // Redirect
        header('Location: event_show.php?id=' . $bottle['event_id']);
        exit;
    }
} else {
    // GET: Prepare Form Data from Bottle
    // Reverse Price Band Mapping
    $p = (int) ($bottle['est_price_yen'] ?? 0);
    $priceBand = 'fine';
    if ($p <= 4000)
        $priceBand = 'casual';
    elseif ($p <= 10000)
        $priceBand = 'bistro';
    elseif ($p <= 20000)
        $priceBand = 'fine';
    elseif ($p <= 50000)
        $priceBand = 'luxury';
    else
        $priceBand = 'icon';

    $form = [
        'owner_label' => $bottle['owner_label'] ?? '',
        'producer_name' => $bottle['producer_name'] ?? '',
        'wine_name' => $bottle['wine_name'] ?? '',
        'vintage' => (int) ($bottle['vintage'] ?? 0),
        'bottle_size_ml' => (int) ($bottle['bottle_size_ml'] ?? 750),
        'country' => $bottle['country'] ?? '',
        'region' => $bottle['region'] ?? '',
        'appellation' => $bottle['appellation'] ?? '',
        'color' => $bottle['color'] ?? 'red',
        'price_band' => $priceBand,
        'theme_fit_score' => (int) ($bottle['theme_fit_score'] ?? 3),
        'is_blind' => (int) ($bottle['is_blind'] ?? 0),
        'memo' => $bottle['memo'] ?? '',
        'guest_email' => $bottle['guest_email'] ?? ''
    ];
}


// --- 5. Display Form ---
$page_title = 'VinMemo - Edit Bottle / ボトル編集';
require_once 'layout/header.php';
?>

<div class="container bottle-page">
    <header class="page-header">
        <h1>Edit Bottle / ボトル編集</h1>
        <a class="back-link btn-secondary" href="event_show.php?id=<?= h($event['id']) ?>">← Back to the event wine list
            /
            このイベントのワインリストに戻る</a>
        <div style="margin-top:5px; color:var(--text-muted);">
            Event / イベント: <?= h($event['title'] ?? 'Unknown Event') ?>
        </div>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= h($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="bottle_edit.php?id=<?= h($bottle['id']) ?><?= $token ? '&token=' . h($token) : '' ?>"
        class="card">
        <!-- Always POST ID -->
        <input type="hidden" name="id" value="<?= h($bottle['id']) ?>">
        <!-- Pass Token if Present -->
        <?php if ($token): ?>
            <input type="hidden" name="token" value="<?= h($token) ?>">
        <?php endif; ?>

        <?php
        $mode = 'edit';
        $bottleId = $bottle['id'];
        $returnUrl = 'event_show.php?id=' . $event['id'];
        include __DIR__ . '/partials/bottle_form.php';
        ?>
    </form>

    <!-- Delete Section (Authorized) -->
    <div style="margin-top:30px; padding-top:20px; border-top:1px solid #444; text-align:right;">
        <span style="font-size:0.9rem; color:var(--text-muted); margin-right:10px;">Need to remove this entry? /
            このボトルを削除しますか？</span>
        <form method="post" action="bottle_delete.php" style="display:inline-block;"
            onsubmit="return confirm('Are you sure you want to delete this bottle? This cannot be undone.');">
            <input type="hidden" name="id" value="<?= h($bottle['id']) ?>">
            <?php if ($token): ?>
                <input type="hidden" name="token" value="<?= h($token) ?>">
            <?php endif; ?>
            <button type="submit" class="button btn-danger">Delete Bottle / ボトルを削除</button>
        </form>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>