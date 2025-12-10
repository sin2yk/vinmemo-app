<?php
// bottle_delete.php : 削除処理
require_once 'db_connect.php';
require_once 'helpers.php';
session_start();

// 1. Determine Access Mode
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$token = $_POST['token'] ?? '';
$bottle = null;

if ($token) {
    // === GUEST / TOKEN MODE ===
    $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE edit_token = :token");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bottle) {
        die('Invalid or expired delete token.');
    }
} elseif ($id) {
    // === ORGANIZER / ID MODE ===
    $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bottle) {
        die('Bottle not found');
    }

    // Role Check
    $currentUserId = $_SESSION['user_id'] ?? 0;
    $eventRole = getEventRole($pdo, $bottle['event_id'], $currentUserId);

    // Debug Bypass (Organizer only)
    if (isset($_POST['debug_bypass_role']) && $_POST['debug_bypass_role'] === 'organizer') {
        $eventRole = 'organizer';
    }

    $isOwner = ($currentUserId && $bottle['brought_by_user_id'] == $currentUserId);
    $isAdmin = ($eventRole === 'organizer');

    if (!$isAdmin && !$isOwner) {
        die('Access Denied: You cannot delete this bottle.');
    }
} else {
    // Fallback? No, strict.
    die('Invalid Request: ID or Token required.');
}

// 3. Execute Delete
$sql = 'DELETE FROM bottle_entries WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $bottle['id'], PDO::PARAM_INT);
$stmt->execute();

// 4. Redirect
$redirectParam = (isset($eventRole) && $eventRole === 'organizer' && isset($_POST['debug_bypass_role'])) ? '&view=organizer' : '';
header('Location: event_show.php?id=' . $bottle['event_id'] . $redirectParam);
exit;
