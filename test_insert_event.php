<?php
// test_insert_event.php
require_once 'db_connect.php';

try {
    $sql = 'INSERT INTO events (title, event_date, place, memo, event_type)
            VALUES (:title, :event_date, :place, :memo, :event_type)';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':title', 'Test Fix Event ' . time(), PDO::PARAM_STR);
    $stmt->bindValue(':event_date', date('Y-m-d'), PDO::PARAM_STR);
    $stmt->bindValue(':place', 'Test Place', PDO::PARAM_STR);
    $stmt->bindValue(':memo', 'Test Memo', PDO::PARAM_STR);
    $stmt->bindValue(':event_type', 'BYO', PDO::PARAM_STR);
    $stmt->execute();
    echo "INSERT Success!\n";
} catch (PDOException $e) {
    echo "INSERT Failed: " . $e->getMessage() . "\n";
}
