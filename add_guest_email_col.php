<?php
require_once __DIR__ . '/db_connect.php';

try {
    $pdo->exec("ALTER TABLE bottle_entries ADD COLUMN guest_email VARCHAR(255) DEFAULT NULL AFTER brought_by_user_id");
    echo "Column 'guest_email' added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'guest_email' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
