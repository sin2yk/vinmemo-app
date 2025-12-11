<?php
require_once 'db_connect.php';
$id = 11;
$newOwner = 2; // Current session user
$sql = "UPDATE events SET organizer_user_id = :uid WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $newOwner, ':id' => $id]);
echo "Updated event $id owner to $newOwner";
