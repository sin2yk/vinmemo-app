<?php
// bottle_toggle_visibility.php
// Toggles the hide_from_list flag for a bottle.
// Only accessible by event organizers.

require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

$bottle_id = filter_input(INPUT_POST, 'bottle_id', FILTER_VALIDATE_INT);
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? ''; // 'hide' or 'show'

if (!$bottle_id || !$event_id || !in_array($action, ['hide', 'show'])) {
    die('Invalid parameters.');
}

// Check permissions
$currentUserId = $_SESSION['user_id'] ?? null;
$eventRole = getEventRole($pdo, $event_id, $currentUserId);

if ($eventRole !== 'organizer') {
    die('Access denied. Only organizers can change visibility.');
}

// Update DB
$hideValue = ($action === 'hide') ? 1 : 0;

try {
    $stmt = $pdo->prepare('UPDATE bottle_entries SET hide_from_list = :hide, updated_at = NOW() WHERE id = :id AND event_id = :event_id');
    $stmt->execute([
        ':hide' => $hideValue,
        ':id' => $bottle_id,
        ':event_id' => $event_id
    ]);

    // Redirect
    header('Location: event_show.php?id=' . $event_id);
    exit;
} catch (PDOException $e) {
    die('Database Error: ' . $e->getMessage());
}
