<?php
require_once 'db_connect.php';

try {
    echo "Starting migration v1.3...\n";

    // 1. Add area_label
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN area_label VARCHAR(255) NULL AFTER place");
        echo " - Added column: area_label\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo " - Column area_label already exists (skipped)\n";
        } else {
            throw $e;
        }
    }

    // 2. Add expected_guests
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN expected_guests INT NULL AFTER area_label");
        echo " - Added column: expected_guests\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo " - Column expected_guests already exists (skipped)\n";
        } else {
            throw $e;
        }
    }

    echo "Migration v1.3 completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
