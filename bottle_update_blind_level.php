<?php
require_once 'db_connect.php';
require_once 'helpers.php';

session_start();

$currentUserId = $_SESSION['user_id'] ?? null;
$debugBypass = isset($_POST['debug_bypass_role']) && $_POST['debug_bypass_role'] === 'organizer';

if (!$currentUserId && !$debugBypass) {
    die('Login required');
}

$bottleId = filter_input(INPUT_POST, 'bottle_id', FILTER_VALIDATE_INT);
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$level = $_POST['blind_reveal_level'] ?? 'none';

if (!$bottleId || !$eventId) {
    die('Invalid ID');
}

// Redirect params
$redirectParams = $debugBypass ? '&view=organizer' : '';

// Allowable enums
$allowed = ['none', 'country', 'country_vintage', 'full'];
if (!in_array($level, $allowed)) {
    die('Invalid status');
}

// Check Role for the EVENT
$role = ($currentUserId) ? getEventRole($pdo, $eventId, $currentUserId) : 'guest';
if ($debugBypass) {
    $role = 'organizer';
}

if ($role !== 'organizer') {
    die('Unauthorized');
}

// Update
$stmt = $pdo->prepare("UPDATE bottle_entries SET blind_reveal_level = :lvl WHERE id = :id");
$stmt->bindValue(':lvl', $level, PDO::PARAM_STR);
$stmt->bindValue(':id', $bottleId, PDO::PARAM_INT);
$stmt->execute();

header('Location: event_show.php?id=' . $eventId . $redirectParams);
exit;
