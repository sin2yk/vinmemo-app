<?php
require_once 'db_connect.php';

try {
    echo "Adding show_theme_fit column to events table...\n";
    $sql = "ALTER TABLE events ADD COLUMN show_theme_fit TINYINT(1) NOT NULL DEFAULT 1";
    $pdo->exec($sql);
    echo "Success: Column added.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column already exists. Skipping.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
