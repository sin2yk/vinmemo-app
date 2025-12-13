<?php
// events_new.php : ワイン会新規登録フォーム＆登録処理
require_once __DIR__ . '/auth_required.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php'; // Ensure h() is available

$error = null;
// Initialize empty event array for partial
$event = [];

// フォーム送信後の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect data (Same logic as before, just updated to match partial inputs)
    // Partial uses 'title', 'subtitle', 'event_date' (DATETIME-LOCAL), 'place', 'area', 'seats',
    // 'event_type', 'event_style_detail', 'theme_description', 'bottle_rules', 'blind_policy', 'memo', 'show_theme_fit'

    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');

    // event_date from partial is "Y-m-d\TH:i". We need to store it suitable for DB (usually DATETIME)
    // MySQL accepts 'Y-m-d H:i:s' or 'Y-m-d T H:i' usually.
    $event_date_input = $_POST['event_date'] ?? '';
    // Basic validation / conversion
    $event_date = str_replace('T', ' ', $event_date_input);
    // If empty or invalid, validation below catches it.

    // Start Time is merged into event_date in new form, but we can keep separate start_time in meta if really needed.
    // Ideally we just use event_date time component. 
    // Backward compatibility: If we really want 'start_time' separate in JSON, we extract it.
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

    // Validation
    if ($title === '' || $event_date === '') {
        $error = 'タイトルと開催日は必須です。';
    } else {
        // ... (validation logic)

        // Meta Data Construction
        $meta_data = [
            'subtitle' => $subtitle,
            'start_time' => $start_time,
            'area' => $area, // existing 'area' stays in meta for fallback/compatibility
            'seats' => $seats,
            // area_label and expected_guests are now columns, so strictly speaking don't need to be in meta,
            // but keeping them in meta too doesn't hurt, or we just rely on columns.
            // Let's rely on columns as per request "Add columns".
            'event_style_detail' => $event_style_detail,
            'theme_description' => $theme_desc,
            'bottle_rules' => $bottle_rules,
            'blind_policy' => $blind_policy,
        ];

        // ...

        try {
            // INSERT with new columns
            $sql = 'INSERT INTO events (title, event_date, place, area_label, expected_guests, memo, event_type, show_theme_fit, organizer_user_id, created_at)
                    VALUES (:title, :event_date, :place, :area_label, :expected_guests, :memo, :event_type, :show_theme_fit, :uid, NOW())';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':event_date', $event_date, PDO::PARAM_STR);
            $stmt->bindValue(':place', $place, PDO::PARAM_STR);
            $stmt->bindValue(':area_label', $area_label, PDO::PARAM_STR);
            $stmt->bindValue(':expected_guests', $expected_guests, PDO::PARAM_INT);
            $stmt->bindValue(':memo', $memo_to_save, PDO::PARAM_STR);
            $stmt->bindValue(':event_type', $event_type, PDO::PARAM_STR);
            $stmt->bindValue(':show_theme_fit', $show_theme_fit, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $_SESSION['user_id'] ?? 0, PDO::PARAM_INT);

            $stmt->execute();
            $newId = $pdo->lastInsertId();

            header('Location: event_show.php?id=' . $newId);
            exit;
        } catch (PDOException $e) {
            $error = '登録エラー: ' . $e->getMessage();
            // Populate $event for re-display
            $event = [
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
}

$page_title = 'VinMemo - New Event';
require_once 'layout/header.php';
?>
<div class="container bottle-page">
    <header class="page-header">
        <h1>Create New Event</h1>
        <a class="back-link btn-secondary" href="events.php">← Back to List</a>
    </header>

    <?php if (!empty($error)): ?>
        <div class="error-msg">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/event_form.php'; ?>
</div>

<?php require_once 'layout/footer.php'; ?>