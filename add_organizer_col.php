<?php
require_once __DIR__ . '/db_connect.php';

try {
    $pdo->exec("ALTER TABLE events ADD COLUMN organizer_user_id INT DEFAULT NULL AFTER id");
    echo "Column 'organizer_user_id' added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'organizer_user_id' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
