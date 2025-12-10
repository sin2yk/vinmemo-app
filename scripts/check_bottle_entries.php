<?php
require_once __DIR__ . '/../db_connect.php';

try {
    $stmt = $pdo->query("DESCRIBE bottle_entries");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Table 'bottle_entries' exists. Columns:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
