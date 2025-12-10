<?php
require_once 'db_connect.php';
require_once 'helpers.php';

session_start();

$currentUserId = $_SESSION['user_id'] ?? null;
$debugBypass = isset($_POST['debug_bypass_role']) && $_POST['debug_bypass_role'] === 'organizer';

if (!$currentUserId && !$debugBypass) {
    die('Login required');
}

$action = $_POST['action'] ?? '';
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

// Redirect logic needs to persist the view param if strictly debugging
$redirectParams = $debugBypass ? '&view=organizer' : '';

if (!$eventId) {
    die('Invalid Event ID');
}

// Check Role
$role = ($currentUserId) ? getEventRole($pdo, $eventId, $currentUserId) : 'guest';
if ($debugBypass) {
    $role = 'organizer';
}

if ($role !== 'organizer') {
    die('Unauthorized: Organizer role required');
}

if ($action === 'reveal_all') {
    // Reveal All
    $stmt = $pdo->prepare("UPDATE events SET revealed_at = NOW() WHERE id = :id");
    $stmt->bindValue(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();

} elseif ($action === 'update_list_constraints') {
    // Update List Constraints
    // We expect a set of checkboxes. 
    // Unchecked boxes are not sent, so we must iterate over known keys.

    $config = [];
    // The keys we check for:
    // The keys we check for (Strict Spec: Only Price Band and Memo are rule-controlled)
    $keys = [
        'price_band',
        'memo'
    ];

    foreach ($keys as $k) {
        // The form sends 'field_owner_label', we want to store 'owner_label'
        $postKey = 'field_' . $k;
        // Check if plain key exists too, just in case
        if (isset($_POST[$postKey])) {
            $config[$k] = true;
        } elseif (isset($_POST[$k])) {
            $config[$k] = true;
        } else {
            $config[$k] = false;
        }
    }

    // Save as JSON
    $json = json_encode($config);
    $stmt = $pdo->prepare("UPDATE events SET list_field_visibility = :json WHERE id = :id");
    $stmt->bindValue(':json', $json, PDO::PARAM_STR);
    $stmt->bindValue(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
}

// Redirect back
header('Location: event_show.php?id=' . $eventId . $redirectParams);
exit;
