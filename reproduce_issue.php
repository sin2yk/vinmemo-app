<?php
// reproduce_issue.php
require_once 'db_connect.php';

try {
    $sql = 'INSERT INTO events (title, event_date, place, memo, event_type)
            VALUES (:title, :event_date, :place, :memo, :event_type)';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':title', 'Test Event', PDO::PARAM_STR);
    $stmt->bindValue(':event_date', '2025-12-31', PDO::PARAM_STR);
    $stmt->bindValue(':place', 'Test Place', PDO::PARAM_STR);
    $stmt->bindValue(':memo', 'Test Memo', PDO::PARAM_STR);
    $stmt->bindValue(':event_type', 'BYO', PDO::PARAM_STR);
    $stmt->execute();
    echo "Success!";
} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
}
