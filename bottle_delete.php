<?php
// bottle_delete.php : 削除処理
require_once 'db_connect.php';
require_once 'helpers.php';
session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Invalid ID');
}

// 1. Fetch Bottle to check event & owner
$sql = 'SELECT * FROM bottle_entries WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$bottle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bottle) {
    die('Bottle not found');
}

// 2. Permission Check
$currentUserId = $_SESSION['user_id'] ?? 0;
$eventRole = getEventRole($pdo, $bottle['event_id'], $currentUserId);
$isOwner = ($currentUserId && $bottle['brought_by_user_id'] == $currentUserId);
$isAdmin = ($eventRole === 'organizer');

if (!$isAdmin && !$isOwner) {
    die('Access Denied: You cannot delete this bottle.');
}

// 3. Execute Delete
$sql = 'DELETE FROM bottle_entries WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// 4. Redirect
header('Location: event_show.php?id=' . $bottle['event_id']);
exit;
