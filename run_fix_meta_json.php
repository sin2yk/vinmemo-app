<?php
// run_fix_meta_json.php
require_once 'db_connect.php';
try {
    // Retry meta_json modification
    $pdo->exec("ALTER TABLE events MODIFY COLUMN meta_json JSON NULL");
    echo "Modified meta_json to NULL.\n";
} catch (PDOException $e) {
    echo "Fix Failed: " . $e->getMessage() . "\n";
}
