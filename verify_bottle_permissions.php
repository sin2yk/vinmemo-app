<?php
// verify_bottle_permissions.php
require_once 'db_connect.php';
session_start();

function test_logic($testName, $userId, $bottleId, $tokenVal = null)
{
    global $pdo;
    $_SESSION['user_id'] = $userId;

    // Simulate Logic from bottle_edit.php

    // 1. Fetch
    $token = $tokenVal ?? ''; // Simulating retrieved token

    $bottle = null;
    if ($bottleId) {
        $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE id = :id");
        $stmt->execute([':id' => $bottleId]);
        $bottle = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($token) {
        $stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE edit_token = :token");
        $stmt->execute([':token' => $token]);
        $bottle = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$bottle)
        return "[$testName] Bottle Not Found";

    // Event
    $stmt = $pdo->prepare('SELECT id, organizer_user_id FROM events WHERE id = :id');
    $stmt->execute([':id' => $bottle['event_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    // Flags
    $isLoggedIn = isset($_SESSION['user_id']);
    $currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

    $isEventOrganizer = ($isLoggedIn && $currentUserId === (int) $event['organizer_user_id']);
    $isBottleOwner = ($isLoggedIn && $bottle['brought_by_user_id'] && $currentUserId === (int) $bottle['brought_by_user_id']);
    $hasValidEditToken = ($token && !empty($bottle['edit_token']) && hash_equals($bottle['edit_token'], $token));

    $canEdit = $isEventOrganizer || $isBottleOwner || $hasValidEditToken;

    return "[$testName] Owner:{$bottle['brought_by_user_id']} Org:{$event['organizer_user_id']} User:{$userId} Token:" . ($token ? 'Yes' : 'No') . " -> CanEdit: " . ($canEdit ? 'YES' : 'NO');
}

// Setup Data
// Event 11: Owner 1.
// Bottle: Create a bottle brought by User 2.
$eventId = 11;
$ownerId = 1;
$guestId = 2; // Let's assume User 2 exists.

// Insert Test Bottle
$token = bin2hex(random_bytes(16));
$stmt = $pdo->prepare("INSERT INTO bottle_entries (event_id, brought_by_user_id, wine_name, edit_token, created_at) VALUES (?, ?, 'Test Wine', ?, NOW())");
$stmt->execute([$eventId, $guestId, $token]);
$bottleId = $pdo->lastInsertId();

echo "Created Test Bottle ID: $bottleId (Owner: User $guestId, Event Org: User $ownerId)\n";

// Run Tests
echo test_logic('Organizer Access', 1, $bottleId) . "\n"; // Should be YES
echo test_logic('Owner Access', 2, $bottleId) . "\n";     // Should be YES
echo test_logic('Random Access', 999, $bottleId) . "\n";  // Should be NO
echo test_logic('Token Access', null, null, $token) . "\n"; // Should be YES
echo test_logic('Invalid Token', null, null, 'bad') . "\n"; // Should be NO

// Cleanup
$pdo->query("DELETE FROM bottle_entries WHERE id = $bottleId");
echo "Cleaned up.\n";
