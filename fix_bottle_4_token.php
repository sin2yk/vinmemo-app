<?php
require_once 'db_connect.php';

$id = 4;
$targetToken = '3519bd1a66d5776c1bfe0cb71856daf90b0a6f91bdcf932c59c5b1263475f3e1';

// Update DB
$stmt = $pdo->prepare("UPDATE bottle_entries SET edit_token = :token WHERE id = :id");
$stmt->execute([':token' => $targetToken, ':id' => $id]);

echo "Updated Bottle ID $id with token: $targetToken\n";

// Verify
$stmt = $pdo->prepare("SELECT edit_token FROM bottle_entries WHERE id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Verification Fetch: [" . $row['edit_token'] . "]\n";
echo "Match? " . ($row['edit_token'] === $targetToken ? "YES" : "NO") . "\n";
