<?php
require_once 'db_connect.php';
require_once 'helpers.php';

session_start();

$currentUserId = $_SESSION['user_id'] ?? null;
if (!$currentUserId) {
    die('Login required');
}

$bottleId = filter_input(INPUT_POST, 'bottle_id', FILTER_VALIDATE_INT);
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$level = $_POST['blind_reveal_level'] ?? 'none';

if (!$bottleId || !$eventId) {
    die('Invalid ID');
}

// Allowable enums
$allowed = ['none', 'country', 'country_vintage', 'full'];
if (!in_array($level, $allowed)) {
    die('Invalid status');
}

// Check Role for the EVENT
$role = getEventRole($pdo, $eventId, $currentUserId);
if ($role !== 'organizer') {
    die('Unauthorized');
}

// Update
$stmt = $pdo->prepare("UPDATE bottle_entries SET blind_reveal_level = :lvl WHERE id = :id");
$stmt->bindValue(':lvl', $level, PDO::PARAM_STR);
$stmt->bindValue(':id', $bottleId, PDO::PARAM_INT);
$stmt->execute();

header('Location: event_show.php?id=' . $eventId);
exit;
