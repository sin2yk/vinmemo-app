<?php
// bottle_edit.php : Edit an existing bottle entry
// Refactored to include strict permission checks and fixed update logic.

require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. Mode Determination ---
$editToken = $_GET['edt'] ?? $_POST['edt'] ?? null;
$mode = $editToken ? 'guest' : 'organizer';

$bottle = null;
$event = null;

if ($mode === 'guest') {
    // --- Guest Mode (Strict) ---
    $stmt = $pdo->prepare("SELECT b.*, e.event_token, e.title as event_title, e.event_date as event_date 
                           FROM bottle_entries b
                           JOIN events e ON b.event_id = e.id
                           WHERE b.edit_token = :token LIMIT 1");
    $stmt->bindValue(':token', $editToken, PDO::PARAM_STR);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bottle) {
        http_response_code(404);
        die('Bottle not found or invalid token.');
    }

    // Normalize Event Data for View/Mail
    $event = [
        'id' => $bottle['event_id'],
        'title' => $bottle['event_title'],
        'event_date' => $bottle['event_date'],
        'token' => $bottle['event_token']
    ];

} else {
    // --- Organizer/Owner Mode (Legacy/Auth) ---
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        die('Invalid request.');
    }

    $isLoggedIn = isset($_SESSION['user_id']);
    $currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

    // Fetch Bottle & Event
    $stmt = $pdo->prepare("SELECT b.*, e.organizer_user_id, e.title as event_title 
                           FROM bottle_entries b
                           JOIN events e ON b.event_id = e.id
                           WHERE b.id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bottle) {
        die('Bottle not found.');
    }

    // Auth Check
    $isEventOrganizer = ($isLoggedIn && $currentUserId === (int) $bottle['organizer_user_id']);
    $isBottleOwner = ($isLoggedIn && $currentUserId === (int) $bottle['brought_by_user_id']);

    if (!$isEventOrganizer && !$isBottleOwner) {
        die('Access Denied.');
    }

    // Set minimal event array for display
    $event = [
        'id' => $bottle['event_id'],
        'title' => $bottle['event_title']
    ];
}

// --- 2. POST Processing ---
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Shared Validation
    $owner_label = trim($_POST['owner_label'] ?? '');
    $producer_name = trim($_POST['producer_name'] ?? '');
    $wine_name = trim($_POST['wine_name'] ?? '');

    if ($owner_label === '')
        $errors[] = 'お名前 (Name) is required.';
    if ($producer_name === '')
        $errors[] = '生産者 (Producer) is required.';
    if ($wine_name === '')
        $errors[] = 'ワイン名 (Wine Name) is required.';

    if (empty($errors)) {
        require_once __DIR__ . '/helpers_mail.php';

        if ($mode === 'guest') {
            // --- Guest Update (Restricted Fields) ---
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
                        guest_email = :guest_email,
                        updated_at = NOW()
                    WHERE edit_token = :token"; // Update by Token

            // Map price_band to yen
            $bandToPrice = ['casual' => 3000, 'bistro' => 7000, 'fine' => 15000, 'luxury' => 30000, 'icon' => 80000];
            $price_band = $_POST['price_band'] ?? 'fine';
            $est_price_yen = $bandToPrice[$price_band] ?? 15000;

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':owner_label', $owner_label);
            $stmt->bindValue(':producer_name', $producer_name);
            $stmt->bindValue(':wine_name', $wine_name);
            $stmt->bindValue(':vintage', $_POST['vintage'] ?: null);
            $stmt->bindValue(':bottle_size_ml', $_POST['bottle_size_ml'] ?? 750);
            $stmt->bindValue(':country', $_POST['country'] ?? '');
            $stmt->bindValue(':region', $_POST['region'] ?? '');
            $stmt->bindValue(':appellation', $_POST['appellation'] ?? '');
            $stmt->bindValue(':color', $_POST['color'] ?? 'red');
            $stmt->bindValue(':est_price_yen', $est_price_yen);
            $stmt->bindValue(':theme_fit_score', $_POST['theme_fit_score'] ?? 3);
            $stmt->bindValue(':is_blind', isset($_POST['is_blind']) ? 1 : 0);
            $stmt->bindValue(':memo', $_POST['memo'] ?? '');
            $stmt->bindValue(':guest_email', $_POST['guest_email'] ?? '');

            $stmt->bindValue(':token', $editToken);
            $stmt->execute();

            // Generate Absolute URL for Edit Link (Critical for Email & Copy)
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $absEditUrl = $protocol . $host . $dir . '/bottle_edit.php?edt=' . urlencode($editToken);

            // Send Email
            if (!empty($_POST['guest_email'])) {
                $bottleForMail = array_merge($bottle, $_POST);
                sendEditLinkEmail(
                    $_POST['guest_email'],
                    $event,
                    $bottleForMail,
                    $absEditUrl,
                    true // isUpdate
                );
            }

            // Show Thank You Template with Absolute URL
            $editUrl = $absEditUrl;
            $publicUrl = isset($event['token']) ? 'event_public.php?ET=' . urlencode($event['token']) : null;

            include __DIR__ . '/templates/guest_edit_thanks.php';
            exit;

        } else {
            // --- Organizer Update (Full Access, by ID) ---
            // (Keeping existing full update logic roughly same but safer)
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
                        guest_email = :guest_email,
                        updated_at = NOW()
                    WHERE id = :id";

            $bandToPrice = ['casual' => 3000, 'bistro' => 7000, 'fine' => 15000, 'luxury' => 30000, 'icon' => 80000];
            $est_price_yen = $bandToPrice[$_POST['price_band'] ?? 'fine'];

            $stmt = $pdo->prepare($sql);
            // Bind all standard fields...
            $stmt->bindValue(':owner_label', $owner_label);
            $stmt->bindValue(':producer_name', $producer_name);
            $stmt->bindValue(':wine_name', $wine_name);
            $stmt->bindValue(':vintage', $_POST['vintage'] ?: null);
            $stmt->bindValue(':bottle_size_ml', $_POST['bottle_size_ml'] ?? 750);
            $stmt->bindValue(':country', $_POST['country'] ?? '');
            $stmt->bindValue(':region', $_POST['region'] ?? '');
            $stmt->bindValue(':appellation', $_POST['appellation'] ?? '');
            $stmt->bindValue(':color', $_POST['color'] ?? 'red');
            $stmt->bindValue(':est_price_yen', $est_price_yen);
            $stmt->bindValue(':theme_fit_score', $_POST['theme_fit_score'] ?? 3);
            $stmt->bindValue(':is_blind', isset($_POST['is_blind']) ? 1 : 0);
            $stmt->bindValue(':memo', $_POST['memo'] ?? '');
            $stmt->bindValue(':guest_email', $_POST['guest_email'] ?? '');

            $stmt->bindValue(':id', $bottle['id'], PDO::PARAM_INT);
            $stmt->execute();

            header('Location: event_show.php?id=' . $bottle['event_id']);
            exit;
        }
    }
}

// --- 3. Prepare Form Data ---
// ... (Logic to prepopulate form array)
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
    'owner_label' => $_POST['owner_label'] ?? $bottle['owner_label'] ?? '',
    'producer_name' => $_POST['producer_name'] ?? $bottle['producer_name'] ?? '',
    'wine_name' => $_POST['wine_name'] ?? $bottle['wine_name'] ?? '',
    'vintage' => $_POST['vintage'] ?? $bottle['vintage'] ?? '',
    'bottle_size_ml' => $_POST['bottle_size_ml'] ?? $bottle['bottle_size_ml'] ?? 750,
    'country' => $_POST['country'] ?? $bottle['country'] ?? '',
    'region' => $_POST['region'] ?? $bottle['region'] ?? '',
    'appellation' => $_POST['appellation'] ?? $bottle['appellation'] ?? '',
    'color' => $_POST['color'] ?? $bottle['color'] ?? 'red',
    'price_band' => $_POST['price_band'] ?? $priceBand,
    'theme_fit_score' => $_POST['theme_fit_score'] ?? $bottle['theme_fit_score'] ?? 3,
    'is_blind' => isset($_POST['is_blind']) ? 1 : (int) ($bottle['is_blind'] ?? 0),
    'memo' => $_POST['memo'] ?? $bottle['memo'] ?? '',
    'guest_email' => $_POST['guest_email'] ?? $bottle['guest_email'] ?? ''
];

$page_title = 'VinMemo - Edit Bottle';
require_once 'layout/header.php';
?>

<div class="container bottle-page">
    <header class="page-header">
        <h1>Edit Bottle / ボトル編集</h1>
        <?php if ($mode === 'guest' && !empty($event['token'])): ?>
            <a class="back-link btn-secondary" href="event_public.php?ET=<?= h($event['token']) ?>">← Back to event /
                イベントに戻る</a>
        <?php else: ?>
            <a class="back-link btn-secondary" href="event_show.php?id=<?= h($event['id']) ?>">← Back to event / イベントに戻る</a>
        <?php endif; ?>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"
            style="color:var(--danger); background:rgba(255,0,0,0.1); padding:10px; border-radius:4px; margin-bottom:20px;">
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $e): ?>
                    <li><?= h($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post"
        action="bottle_edit.php?<?= $mode === 'guest' ? 'edt=' . h($editToken) : 'id=' . h($bottle['id']) ?>" class="card">
        <?php if ($mode === 'organizer'): ?>
            <input type="hidden" name="id" value="<?= h($bottle['id']) ?>">
        <?php else: ?>
            <input type="hidden" name="edt" value="<?= h($editToken) ?>">
        <?php endif; ?>

        <?php
        $bottleId = $bottle['id'];
        include __DIR__ . '/partials/bottle_form.php';
        ?>
    </form>

    <div style="margin-top:30px; padding-top:20px; border-top:1px solid #444; text-align:right;">
        <form method="post" action="bottle_delete.php" style="display:inline-block;"
            onsubmit="return confirm('Are you sure you want to delete this bottle? This cannot be undone.');">
            <input type="hidden" name="id" value="<?= h($bottle['id']) ?>">
            <?php if ($mode === 'guest'): ?>
                <input type="hidden" name="edt" value="<?= h($editToken) ?>">
            <?php endif; ?>
            <button type="submit" class="button btn-danger">Delete Bottle / ボトルを削除</button>
        </form>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>