<?php
// bottle_edit.php : Edit an existing bottle entry (Refactored to use partials/bottle_form.php)

require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Get Bottle ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}
if (!$id) {
    die('Invalid bottle id.');
}

// 2. Fetch Bottle
$stmt = $pdo->prepare('SELECT * FROM bottle_entries WHERE id = :id');
$stmt->execute([':id' => $id]);
$bottle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bottle) {
    die('Bottle not found.');
}

$eventId = (int) $bottle['event_id'];
$currentUserId = $_SESSION['user_id'] ?? null;

$form = [
    'owner_label'     => $bottle['owner_label'] ?? '',
    'wine_name'       => $bottle['wine_name'] ?? '',
    'producer_name'   => $bottle['producer_name'] ?? '',
    'country'         => $bottle['country'] ?? '',
    'region'          => $bottle['region'] ?? '',
    'appellation'     => $bottle['appellation'] ?? '',
    'color'           => $bottle['color'] ?? 'red',
    'vintage'         => (int) ($bottle['vintage'] ?? 0),
    'bottle_size_ml'  => (int) ($bottle['bottle_size_ml'] ?? 750),
    'price_band'      => $priceBand,                     // ← 既存コードの変数
    'theme_fit_score' => (int) ($bottle['theme_fit_score'] ?? 3),
    'is_blind'        => (int) ($bottle['is_blind'] ?? 0),
    'memo'            => $bottle['memo'] ?? '',
];

$errors = [];


// 3. Permission Check
$eventRole = function_exists('getEventRole') ? getEventRole($pdo, $eventId, $currentUserId) : 'guest';
$isOwner = ($currentUserId && isset($bottle['brought_by_user_id']) && (int) $bottle['brought_by_user_id'] === (int) $currentUserId);
$isAdmin = ($eventRole === 'organizer');

if (!$isAdmin && !$isOwner) {
    http_response_code(403);
    die('Access denied.');
}

// 4. Determine Price Band from amount
$priceBand = 'fine'; // default fallback
$p = (int) $bottle['est_price_yen'];
if ($p <= 5000)
    $priceBand = 'casual';
elseif ($p <= 10000)
    $priceBand = 'bistro';
elseif ($p <= 20000)
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

    <?php
    $mode = 'edit';
    $bottleId = $id;
    $returnUrl = 'event_show.php?id=' . $eventId;
    include __DIR__ . '/partials/bottle_form.php';
    ?>
</div>

<?php require_once 'layout/footer.php'; ?>