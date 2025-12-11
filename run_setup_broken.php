<?php
// run_setup_broken.php
require_once 'db_connect.php';

try {
    // Replicate the user's broken state: ADD columns as NOT NULL
    // We check if exists first or just try-catch
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN event_token VARCHAR(64) NOT NULL");
        echo "Added event_token (NOT NULL).\n";
    } catch (Exception $e) {
        echo "event_token add skip/error: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN meta_json JSON NOT NULL");
        echo "Added meta_json (NOT NULL).\n";
    } catch (Exception $e) {
        echo "meta_json add skip/error: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
}
