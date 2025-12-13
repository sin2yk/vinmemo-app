<?php
// tools/fix_missing_event_cols.php
require_once __DIR__ . '/../db_connect.php';

$columnsToAdd = [
    'area_label' => "VARCHAR(100) NULL COMMENT 'Area/Region Name'",
    'expected_guests' => "INT NULL COMMENT 'Expected guest count'",
    'event_type' => "VARCHAR(50) DEFAULT 'BYO' COMMENT 'Event Type (BYO, Restaurant, etc)'",
    'show_theme_fit' => "TINYINT(1) DEFAULT 1 COMMENT 'Show Theme Fit score?'"
];

try {
    foreach ($columnsToAdd as $col => $def) {
        // Check if exists
        $check = $pdo->query("SHOW COLUMNS FROM events LIKE '$col'");
        if ($check->fetch()) {
            echo "INFO: Column '$col' already exists.\n";
        } else {
            $sql = "ALTER TABLE events ADD COLUMN $col $def";
            $pdo->exec($sql);
            echo "OK: Added column '$col'.\n";
        }
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
