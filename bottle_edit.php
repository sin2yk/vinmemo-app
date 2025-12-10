<?php
// bottle_edit.php : Edit an existing bottle entry (Refactored to use partials/bottle_form.php)

require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Determine Access Mode (Token vs ID)
$token = $_GET['token'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$bottle = null;
$eventRole = 'guest';

if ($token) {
    // === GUEST / TOKEN MODE ===
    $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE edit_token = :token");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bottle) {
        die('Invalid or expired edit link.');
    }
    // Access Granted via Token
} elseif ($id) {
    // === ORGANIZER / ID MODE ===
    $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bottle) {
        die('Bottle not found.');
    }

    // Role Check
    $currentUserId = $_SESSION['user_id'] ?? 0;
    if (!$currentUserId) {
        die('Access Denied: Login required for ID-based access.');
    }

    $eventRole = getEventRole($pdo, $bottle['event_id'], $currentUserId);
    // Organizers can edit any bottle. Owners can edit their own (if logged in).
    $isOwner = ($bottle['brought_by_user_id'] == $currentUserId);

    if ($eventRole !== 'organizer' && !$isOwner) {
        die('Access Denied: You do not have permission to edit this bottle.');
    }

    // === POST HANDLER ===
    // We need to re-validate permissions carefully
    $p_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $p_token = $_POST['token'] ?? '';

    // Fetch bottle again to be sure
    if ($p_token) {
        // Token access
        $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE id = :id AND edit_token = :token");
        $stmt->bindValue(':id', $p_id, PDO::PARAM_INT);
        $stmt->bindValue(':token', $p_token, PDO::PARAM_STR);
        $stmt->execute();
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target)
            die('Invalid Token or ID match');
    } else {
        // ID access (Organizer)
        $currentUserId = $_SESSION['user_id'] ?? 0;
        if (!$currentUserId)
            die('Login required');

        $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE id = :id");
        $stmt->bindValue(':id', $p_id, PDO::PARAM_INT);
        $stmt->execute();
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target)
            die('Bottle not found');

        $role = getEventRole($pdo, $target['event_id'], $currentUserId);
        $isOwner = ($target['brought_by_user_id'] == $currentUserId);
        if ($role !== 'organizer' && !$isOwner)
            die('Access Denied');
    }

    // Proceed with Update
    // ... (rest of update logic)
} else {
    die('Invalid Request: ID or Token required.');
}

$id = $bottle['id'];
$eventId = (int) $bottle['event_id'];
$currentUserId = $_SESSION['user_id'] ?? null;

// 2. Determine Price Band from amount (Reverse Mapping)
$priceBand = 'fine'; // default
$p = (int) ($bottle['est_price_yen'] ?? 0);
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

// 3. Prepare Form Data
$form = [
    'owner_label' => $bottle['owner_label'] ?? '',
    'wine_name' => $bottle['wine_name'] ?? '',
    'producer_name' => $bottle['producer_name'] ?? '',
    'country' => $bottle['country'] ?? '',
    'region' => $bottle['region'] ?? '',
    'appellation' => $bottle['appellation'] ?? '',
    'color' => $bottle['color'] ?? 'red',
    'vintage' => (int) ($bottle['vintage'] ?? 0),
    'bottle_size_ml' => (int) ($bottle['bottle_size_ml'] ?? 750),
    'price_band' => $priceBand,
    'theme_fit_score' => (int) ($bottle['theme_fit_score'] ?? 3),
    'is_blind' => (int) ($bottle['is_blind'] ?? 0),
    'memo' => $bottle['memo'] ?? '',
];

$errors = [];

// 4. Fetch Event Details (for Header)
$stmt = $pdo->prepare('SELECT id, title, event_date, place FROM events WHERE id = :id');
$stmt->execute([':id' => $eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'VinMemo - Edit Bottle';
require_once 'layout/header.php';
?>

<div class="container bottle-page">
    <header class="page-header">
        <h1>Edit Bottle</h1>
        <a class="back-link" href="event_show.php?id=<?= h($eventId) ?>">← Back to the event wine list</a>
        <div style="margin-top:5px; color:var(--text-muted);">
            Event: <?= h($event['title'] ?? 'Unknown Event') ?>
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
        <input type="hidden" name="id" value="<?= h($bottle['id']) ?>">
        <?php if ($token): ?>
            <input type="hidden" name="token" value="<?= h($token) ?>">
        <?php endif; ?>
        <?php
        $mode = 'edit';
        $bottleId = $id;
        $returnUrl = 'event_show.php?id=' . $eventId;
        include __DIR__ . '/partials/bottle_form.php';
        ?>
    </form>

    <!-- Delete Section (Available to Token Users or Organizers) -->
    <div style="margin-top:30px; padding-top:20px; border-top:1px solid #444; text-align:right;">
        <span style="font-size:0.9rem; color:var(--text-muted); margin-right:10px;">Need to remove this entry?</span>
        <form method="post" action="bottle_delete.php" style="display:inline-block;"
            onsubmit="return confirm('Are you sure you want to delete this bottle? This cannot be undone.');">
            <input type="hidden" name="id" value="<?= h($bottle['id']) ?>">
            <?php if ($token): ?>
                <input type="hidden" name="token" value="<?= h($token) ?>">
            <?php endif; ?>
            <button type="submit" class="button btn-danger">Delete Bottle</button>
        </form>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>