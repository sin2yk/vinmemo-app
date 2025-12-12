<?php
require_once 'db_connect.php';

$id = 4;
$targetToken = '3519bd1a66d5776c1bfe0cb71856daf90b0a6f91bdcf932c59c5b1263475f3e1';

// 1. Check who owns this token
$stmt = $pdo->prepare("SELECT id FROM bottle_entries WHERE edit_token = :token");
$stmt->execute([':token' => $targetToken]);
$conflict = $stmt->fetch(PDO::FETCH_ASSOC);

if ($conflict) {
    if ($conflict['id'] == $id) {
        echo "Token already belongs to ID 4. All good.\n";
        exit;
    }
    echo "Conflict detected! Token belongs to ID " . $conflict['id'] . ". Removing it from there...\n";
    // Clear conflict
    $start = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE bottle_entries SET edit_token = :new_token WHERE id = :id");
    $stmt->execute([':new_token' => $start, ':id' => $conflict['id']]);
}

// 2. Update ID 4
$stmt = $pdo->prepare("UPDATE bottle_entries SET edit_token = :token WHERE id = :id");
$stmt->execute([':token' => $targetToken, ':id' => $id]);

echo "Success: Updated Bottle ID $id with target token.\n";
