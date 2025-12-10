<?php
require_once 'db_connect.php';

try {
    echo "Adding edit_token column...\n";
    $sql = "ALTER TABLE bottle_entries 
            ADD COLUMN edit_token VARCHAR(64) NULL AFTER blind_reveal_level,
            ADD UNIQUE KEY idx_edit_token (edit_token);";
    $pdo->exec($sql);
    echo "Success: Column edit_token added.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Notice: Column edit_token already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
