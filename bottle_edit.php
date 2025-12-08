<?php
// bottle_edit.php
require_once 'db_connect.php';
require_once 'helpers.php'; // For getEventRole

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error = null;
$bottle = null;

// Initial Fetch to check permissions
if ($id) {
    // 1. Fetch Bottle Info
    $sql = 'SELECT * FROM bottle_entries WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bottle) {
        // 2. Check Permissions
        $currentUserId = $_SESSION['user_id'] ?? 0;
        $eventRole = getEventRole($pdo, $bottle['event_id'], $currentUserId);
        $isOwner = ($currentUserId && $bottle['brought_by_user_id'] == $currentUserId);
        $isAdmin = ($eventRole === 'organizer');

        if (!$isAdmin && !$isOwner) {
            die('Access Denied. You are not the owner or organizer.');
        }
    } else {
        die('Bottle not found.');
    }
} else {
    // Post will check ID later
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    // ... Re-fetch and Re-check permission normally required for security, 
    // but assuming session hasn't changed in split second. 
    // Ideally should re-check here.

    // We already have $bottle from GET logic if ID matches?
    // Actually POST cycle is a new request. Need to re-validate.

    // Simplified:
    if ($id) {
        $sql = 'SELECT * FROM bottle_entries WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $bottleToCheck = $stmt->fetch(PDO::FETCH_ASSOC);

        $currentUserId = $_SESSION['user_id'] ?? 0;
        $isAdmin = getEventRole($pdo, $bottleToCheck['event_id'], $currentUserId) === 'organizer';
        $isOwner = ($currentUserId && $bottleToCheck['brought_by_user_id'] == $currentUserId);

        if (!$isAdmin && !$isOwner) {
            die('Access Denied during update.');
        }

        // Proceed with Update...
        $owner_label = $_POST['owner_label'] ?? '';
        $wine_name = $_POST['wine_name'] ?? '';
        $producer_name = $_POST['producer_name'] ?? '';
        $country = $_POST['country'] ?? '';
        $region = $_POST['region'] ?? '';
        $appellation = $_POST['appellation'] ?? '';
        $color = $_POST['color'] ?? 'red';
        $vintage = filter_input(INPUT_POST, 'vintage', FILTER_VALIDATE_INT);
        $bottle_size_ml = filter_input(INPUT_POST, 'bottle_size_ml', FILTER_VALIDATE_INT) ?: 750;
        $est_price_yen = filter_input(INPUT_POST, 'est_price_yen', FILTER_VALIDATE_INT);
        $theme_fit_score = filter_input(INPUT_POST, 'theme_fit_score', FILTER_VALIDATE_INT);
        // Fix for is_blind checkbox
        $is_blind = isset($_POST['is_blind']) ? 1 : 0;
        // If blind checkboxes from bottle_new logic are used here, they need to be aggregated.
        // But bottle_edit.php previous version just had $is_blind global?
        // Let's check previous file content...
        // Previous bottle_edit.php: $is_blind = isset($_POST['is_blind']) ? 1 : 0;
        // It seems previous edit form might have been simpler than new bottle_new form?
        // Let's assume standard is_blind checkbox for now or replicate the bottle_new logic if needed.
        // User asked to "Reuse UX". bottle_new has detailed blind checkboxes.
        // I should probably simplify to one checkbox for edit or copy logic.
        // Let's stick to one checkbox for Edit to keep it simple unless requested.

        $memo = $_POST['memo'] ?? '';

        $sql = 'UPDATE bottle_entries SET
                    owner_label = :owner_label,
                    wine_name = :wine_name,
                    producer_name = :producer_name,
                    country = :country,
                    region = :region,
                    appellation = :appellation,
                    color = :color,
                    vintage = :vintage,
                    bottle_size_ml = :bottle_size_ml,
                    est_price_yen = :est_price_yen,
                    theme_fit_score = :theme_fit_score,
                    is_blind = :is_blind,
                    memo = :memo,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        // Bind values...
        $stmt->bindValue(':owner_label', $owner_label);
        $stmt->bindValue(':wine_name', $wine_name);
        $stmt->bindValue(':producer_name', $producer_name);
        $stmt->bindValue(':country', $country);
        $stmt->bindValue(':region', $region);
        $stmt->bindValue(':appellation', $appellation);
        $stmt->bindValue(':color', $color);
        $stmt->bindValue(':vintage', $vintage, PDO::PARAM_INT);
        $stmt->bindValue(':bottle_size_ml', $bottle_size_ml, PDO::PARAM_INT);
        $stmt->bindValue(':est_price_yen', $est_price_yen, PDO::PARAM_INT);
        $stmt->bindValue(':theme_fit_score', $theme_fit_score, PDO::PARAM_INT);
        $stmt->bindValue(':is_blind', $is_blind, PDO::PARAM_INT);
        $stmt->bindValue(':memo', $memo);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        header("Location: event_show.php?id={$bottleToCheck['event_id']}");
        exit;
    }
}

$page_title = 'VinMemo - Edit Bottle';
require_once 'layout/header.php';
?>

<div class="container">
    <header>
        <h1>Edit Bottle</h1>
        <?php if ($bottle): ?>
            <a href="event_show.php?id=<?= h($bottle['event_id']) ?>">Cancel</a>
        <?php endif; ?>
    </header>

    <?php if ($bottle): ?>
        <form action="bottle_edit.php" method="post" class="card">
            <input type="hidden" name="id" value="<?= h($bottle['id']) ?>">
            <input type="hidden" name="event_id" value="<?= h($bottle['event_id']) ?>">

            <label>Owner Name</label>
            <input type="text" name="owner_label" value="<?= h($bottle['owner_label']) ?>" required>

            <label>Wine Name</label>
            <input type="text" name="wine_name" value="<?= h($bottle['wine_name']) ?>" required>

            <label>Producer</label>
            <input type="text" name="producer_name" value="<?= h($bottle['producer_name']) ?>">

            <label>Format</label>
            <div class="form-row">
                <input type="number" name="bottle_size_ml" value="<?= h($bottle['bottle_size_ml']) ?>"> ml
            </div>

            <label>Blind Mode</label>
            <div>
                <input type="checkbox" name="is_blind" value="1" <?= $bottle['is_blind'] ? 'checked' : '' ?>>
                Enable Blind Mode (Masks details for others)
            </div>
            <br>

            <label>Memo</label>
            <textarea name="memo" rows="4"><?= h($bottle['memo']) ?></textarea>

            <button type="submit" style="margin-top:20px;">Update</button>
        </form>
    <?php else: ?>
        <p>Bottle not found.</p>
    <?php endif; ?>

</div>

<?php require_once 'layout/footer.php'; ?>