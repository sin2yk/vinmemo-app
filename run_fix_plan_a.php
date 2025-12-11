<?php
// run_fix_plan_a.php
require_once 'db_connect.php';

try {
    // Execute Plan A: Relax constraints to NULL
    $pdo->exec("ALTER TABLE events MODIFY COLUMN event_token VARCHAR(64) NULL");
    echo "Modified event_token to NULL.\n";

    $pdo->exec("ALTER TABLE events MODIFY COLUMN meta_json JSON NULL");
    echo "Modified meta_json to NULL.\n";

} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
}
