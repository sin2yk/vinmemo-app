<?php
// run_fix_meta_json_v2.php
require_once 'db_connect.php';
try {
    // Try LONGTEXT explicitly, and DEFAULT NULL
    $pdo->exec("ALTER TABLE events MODIFY COLUMN meta_json LONGTEXT NULL DEFAULT NULL");
    echo "Modified meta_json to LONGTEXT NULL.\n";
} catch (PDOException $e) {
    echo "Fix V2 Failed: " . $e->getMessage() . "\n";
}
