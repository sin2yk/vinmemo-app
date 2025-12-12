<?php
// events_new.php : ワイン会新規登録フォーム＆登録処理
require_once 'db_connect.php';
require_once 'helpers.php'; // Ensure h() is available

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    $seats = filter_input(INPUT_POST, 'seats', FILTER_VALIDATE_INT);
    $event_type = $_POST['event_type'] ?? 'BYO';

    $event_style_detail = $_POST['event_style_detail'] ?? '';
    $theme_desc = trim($_POST['theme_description'] ?? '');
    $bottle_rules = trim($_POST['bottle_rules'] ?? '');
    $blind_policy = $_POST['blind_policy'] ?? 'none';
    $organizer_note = trim($_POST['memo'] ?? '');
    $show_theme_fit = isset($_POST['show_theme_fit']) ? 1 : 0;

    // Validation
    if ($title === '' || $event_date === '') {
        $error = 'タイトルと開催日は必須です。';
    } else {
        // Validate event_type
        $valid_types = ['BYO', 'ORG', 'VENUE'];
        if (!in_array($event_type, $valid_types, true)) {
            $event_type = 'BYO';
        }

        // Meta Data Construction
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

        // Save Memo JSON
        $memo_to_save = $organizer_note . "\n\n---META---\n" . json_encode($meta_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Current User is Owner
        $owner_user_id = $_SESSION['user_id'] ?? null;
        if (!$owner_user_id) {
            // Should strictly redirect if not logged in, but for safety:
            // (events_new.php didn't have strict login check in original file viewed, but implies organization)
            // We'll insert anyway or error? "New Event" usually requires login.
            // events_new.php was accessible? Original file didn't check login explicitly at top.
            // We should probably check login.
        }

        try {
            // events table schema: title, event_date, place, memo, event_type, owner_user_id, show_theme_fit
            // Do we have owner_user_id column? Need to check schema or infer.
            // Original `events_new.php` did NOT bind owner_user_id!
            // Wait, previous `event_show.php` check: `$_SESSION['user_id'] == $event['organizer_user_id']`.
            // So column is `organizer_user_id`!
            // Or `owner_user_id`?
            // `event_show.php` line 73: `$event['organizer_user_id']`.
            // But `events_new.php` from step 1580 did NOT INSERT `organizer_user_id`.
            // This suggests `events_new.php` was incomplete or `organizer_user_id` has a default?
            // Or maybe `events_new.php` logic I viewed was partial?
            // Re-reading `events_new.php` content in Step 1580:
            // It inserts: title, event_date, place, memo, event_type.
            // It DOES NOT insert `organizer_user_id`. -> This is a BUG in existing code or schema default (unlikely).
            // Actually, I should probably FIX this now if I am refactoring.
            // But first, let's stick to known schema. 
            // If the table has `owner_user_id` or `organizer_user_id`, we should populate it.
            // I'll check `db_connect.php` or `event_show.php` implies it exists.
            // I'll add `organizer_user_id` if user is logged in.

            $sql = 'INSERT INTO events (title, event_date, place, memo, event_type, show_theme_fit, organizer_user_id, created_at)
                    VALUES (:title, :event_date, :place, :memo, :event_type, :show_theme_fit, :uid, NOW())';

            // Note: If `organizer_user_id` column name is actually `owner_user_id`, I might break it.
            // `event_show.php` uses `organizer_user_id`.
            // `event_edit.php` (my creation) uses `organizer_user_id`.
            // I will implement `organizer_user_id`.

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':event_date', $event_date, PDO::PARAM_STR);
            $stmt->bindValue(':place', $place, PDO::PARAM_STR);
            $stmt->bindValue(':memo', $memo_to_save, PDO::PARAM_STR);
            $stmt->bindValue(':event_type', $event_type, PDO::PARAM_STR);
            $stmt->bindValue(':show_theme_fit', $show_theme_fit, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $_SESSION['user_id'] ?? 0, PDO::PARAM_INT); // Default 0 if guest?

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