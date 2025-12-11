<?php
// check_schema_simple.php
require_once 'db_connect.php';
try {
    $stmt = $pdo->query("DESCRIBE events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        // filter for interesting columns
        if (in_array($col['Field'], ['event_token', 'meta_json'])) {
            echo $col['Field'] . " | " . $col['Type'] . " | Null:" . $col['Null'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}
