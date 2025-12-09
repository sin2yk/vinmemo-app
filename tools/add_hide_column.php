<?php
require_once __DIR__ . '/../db_connect.php';

try {
    // Check if column exists
    $checkSql = "SHOW COLUMNS FROM bottle_entries LIKE 'hide_from_list'";
    $stmt = $pdo->query($checkSql);
    $exists = $stmt->fetch();

    if (!$exists) {
        $sql = "ALTER TABLE bottle_entries ADD COLUMN hide_from_list TINYINT(1) NOT NULL DEFAULT 0 AFTER is_blind";
        $pdo->exec($sql);
        echo "Column 'hide_from_list' added successfully.\n";
    } else {
        echo "Column 'hide_from_list' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
