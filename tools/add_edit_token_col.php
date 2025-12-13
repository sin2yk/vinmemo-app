<?php
// tools/add_edit_token_col.php

require_once __DIR__ . '/../db_connect.php';

try {
    // Check if column exists first to be safe
    $checkSql = "SHOW COLUMNS FROM bottle_entries LIKE 'edit_token'";
    $stmt = $pdo->query($checkSql);
    if ($stmt->fetch()) {
        echo "INFO: edit_token column already exists.\n";
    } else {
        $sql = "
            ALTER TABLE bottle_entries
            ADD COLUMN edit_token VARCHAR(64) NULL UNIQUE COMMENT 'Secret token for guest edit access'
        ";
        $pdo->exec($sql);
        echo "OK: Added edit_token column to bottle_entries table.\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
