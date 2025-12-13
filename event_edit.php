<?php
// event_edit.php : Edit Event Details (Organizer Only)
// Uses shared partial: partials/event_form.php
require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
$current_user_id = (int) $_SESSION['user_id'];
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$event_id) {
    http_response_code(400);
    exit('Invalid Event ID');
}

// Fetch Current Event
$stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id');
$stmt->bindValue(':id', $event_id, PDO::PARAM_INT);
$stmt->execute();
$eventData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$eventData) {
    http_response_code(404);
    exit('Event not found');
}

// Permission Check (Strict)
if ((int) $eventData['organizer_user_id'] !== $current_user_id) {
    // If admin check needed: if (!$_SESSION['is_admin'] && mismatch) ...
    http_response_code(403);
    exit('You are not allowed to edit this event.');
}

$error = null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input collection - same as events_new.php
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');

    $event_date_input = $_POST['event_date'] ?? '';
    $event_date = str_replace('T', ' ', $event_date_input);

    $start_time = '';
    if ($event_date_input) {
        $start_time = date('H:i', strtotime($event_date_input));
    }

    $place = trim($_POST['place'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $area_label = trim($_POST['area_label'] ?? '');
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
    $expected_guests = filter_input(INPUT_POST, 'expected_guests', FILTER_VALIDATE_INT);
    $event_type = $_POST['event_type'] ?? 'BYO';

    $event_style_detail = $_POST['event_style_detail'] ?? '';
    // ...

    if ($title === '' || $event_date === '') {
        $error = 'Title and Date are required.';
    } else {
        $valid_types = ['BYO', 'ORG', 'VENUE'];
        if (!in_array($event_type, $valid_types, true))
            $event_type = 'BYO';

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

        $memo_to_save = $organizer_note . "\n\n---META---\n" . json_encode($meta_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        try {
            $sql = 'UPDATE events 
                    SET title = :title, 
                        event_date = :event_date, 
                        place = :place,
                        area_label = :area_label,
                        expected_guests = :expected_guests,
                        memo = :memo, 
                        event_type = :event_type,
                        show_theme_fit = :show_theme_fit
                    WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':event_date', $event_date, PDO::PARAM_STR);
            $stmt->bindValue(':place', $place, PDO::PARAM_STR);
            $stmt->bindValue(':area_label', $area_label, PDO::PARAM_STR);
            $stmt->bindValue(':expected_guests', $expected_guests, PDO::PARAM_INT);
            $stmt->bindValue(':memo', $memo_to_save, PDO::PARAM_STR);
            $stmt->bindValue(':event_type', $event_type, PDO::PARAM_STR);
            $stmt->bindValue(':show_theme_fit', $show_theme_fit, PDO::PARAM_INT);
            $stmt->bindValue(':id', $event_id, PDO::PARAM_INT);

            $stmt->execute();

            header('Location: event_show.php?id=' . $event_id . '&updated=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Update Failed: ' . $e->getMessage();
            // Populate $event for partial
            $event = [
                // ... merge existing with POST if needed, but simple re-assign is safer
                'title' => $title,
                'subtitle' => $subtitle,
                'event_date' => $event_date_input,
                'place' => $place,
                'area' => $area,
                'seats' => $seats,
                'event_type' => $event_type,
                'event_style_detail' => $event_style_detail,
                'theme_description' => $theme_desc,
                'bottle_rules' => $bottle_rules,
                'blind_policy' => $blind_policy,
                'memo' => $organizer_note,
                'show_theme_fit' => $show_theme_fit
            ];
        }
    }
} else {
    // GET: Prepare $event from DB data
    // Parse "memo" to extract meta
    $parsedMemo = parseEventMemo($eventData['memo']); // Helper function assumed exists? 
    // Wait, helper 'parseEventMemo' was used in `event_show.php` (viewed in Step 1508).
    // Let's verify helpers.php has it.
    // If not, I'll inline the parsing.
    // event_show.php L118: $parsedMemo = parseEventMemo($event['memo']);
    // So it exists.

    $meta = $parsedMemo['meta'] ?? [];
    $note = $parsedMemo['note'] ?? '';

    // Merge DB columns and Meta columns into flat $event array for partial
    $event = [
        'title' => $eventData['title'],
        'event_date' => $eventData['event_date'], // Y-m-d H:i:s -> Partial handles conversion
        'place' => $eventData['place'],
        'area_label' => $eventData['area_label'] ?? '',
        'expected_guests' => $eventData['expected_guests'] ?? '',
        'event_type' => $eventData['event_type'],
        'show_theme_fit' => $eventData['show_theme_fit'],
        'memo' => $note, // The text part

        // Meta fields
        'subtitle' => $meta['subtitle'] ?? '',
        'start_time' => $meta['start_time'] ?? '', // overridden by event_date usually
        'area' => $meta['area'] ?? '',
        'seats' => $meta['seats'] ?? '',
        'event_style_detail' => $meta['event_style_detail'] ?? '',
        'theme_description' => $meta['theme_description'] ?? '',
        'bottle_rules' => $meta['bottle_rules'] ?? '',
        'blind_policy' => $meta['blind_policy'] ?? 'none',
    ];
}

$page_title = 'VinMemo - Edit Event';
require_once 'layout/header.php';
?>

<div class="container bottle-page">
    <header class="page-header">
        <h1>Edit Event / イベントを編集</h1>
        <a href="event_show.php?id=<?= h($event_id) ?>" class="back-link btn-secondary">
            ← Back to Event / イベントに戻る
        </a>
    </header>

    <?php if (!empty($error)): ?>
        <div class="error-msg">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/event_form.php'; ?>
</div>

<?php require_once 'layout/footer.php'; ?>