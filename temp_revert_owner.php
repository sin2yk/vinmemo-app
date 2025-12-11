<?php
require_once 'db_connect.php';
$id = 11;
$oldOwner = 1; // Original owner
$sql = "UPDATE events SET organizer_user_id = :uid WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $oldOwner, ':id' => $id]);
echo "Reverted event $id owner to $oldOwner";
