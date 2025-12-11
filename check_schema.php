<?php
// check_schema.php
require_once 'db_connect.php';

try {
    $stmt = $pdo->query("DESCRIBE events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " | " . $col['Type'] . " | Null:" . $col['Null'] . " | Default:" . $col['Default'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
