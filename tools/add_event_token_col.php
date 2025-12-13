<?php
// tools/add_event_token_col.php

require_once __DIR__ . '/../db_connect.php';

try {
    // Check if column exists first to be safe
    $checkSql = "SHOW COLUMNS FROM events LIKE 'event_token'";
    $stmt = $pdo->query($checkSql);
    if ($stmt->fetch()) {
        echo "INFO: event_token column already exists.\n";
    } else {
        $sql = "
            ALTER TABLE events
            ADD COLUMN event_token VARCHAR(64) NULL UNIQUE COMMENT 'Public guest access token (ET)'
        ";
        $pdo->exec($sql);
        echo "OK: Added event_token column to events table.\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
