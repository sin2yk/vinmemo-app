<?php
// bottle_delete.php : Delete processing
require_once 'db_connect.php';
require_once 'helpers.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. Fetch Bottle & Event ---

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$token = $_POST['token'] ?? $_POST['et'] ?? $_GET['token'] ?? $_GET['et'] ?? '';
$bottle = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($token) {
    $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE edit_token = :token");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$bottle) {
    die('Bottle not found or invalid request.');
}

// Fetch Event (for Organizer check)
$stmt = $pdo->prepare('SELECT id, organizer_user_id FROM events WHERE id = :id');
$stmt->execute([':id' => $bottle['event_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die('Associated event not found.');
}

// --- 2. Permission Flags ---

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

// ① Event Organizer?
$isEventOrganizer = false;
if ($isLoggedIn && isset($event['organizer_user_id'])) {
    $isEventOrganizer = ($currentUserId === (int) $event['organizer_user_id']);
}

// ② Bottle Owner?
$isBottleOwner = false;
if ($isLoggedIn && isset($bottle['brought_by_user_id'])) {
    $isBottleOwner = ($currentUserId === (int) $bottle['brought_by_user_id']);
}

// ③ Valid Edit Token?
$hasValidEditToken = false;
if ($token && !empty($bottle['edit_token'])) {
    $hasValidEditToken = hash_equals($bottle['edit_token'], $token);
}

// --- 3. Authorization Check ---

$canDelete = $isEventOrganizer || $isBottleOwner || $hasValidEditToken;

if (!$canDelete) {
    die('Access Denied: You cannot delete this bottle.');
}

// --- 4. Execute Delete ---
$sql = 'DELETE FROM bottle_entries WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $bottle['id'], PDO::PARAM_INT);
$stmt->execute();

// --- 5. Redirect ---
// If Organizer, maybe preserve view mode? The user didn't strictly request it here, but nice to have.
// We'll just return to event_show.
header('Location: event_show.php?id=' . $bottle['event_id']);
exit;
