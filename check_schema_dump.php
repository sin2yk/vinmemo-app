<?php
// check_schema_dump.php
require_once 'db_connect.php';

try {
    $stmt = $pdo->query("DESCRIBE events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $fp = fopen('schema_dump.txt', 'w');
    foreach ($columns as $col) {
        fwrite($fp, sprintf(
            "Field: %-20s | Type: %-15s | Null: %-3s | Default: %-10s | Extra: %s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'],
            $col['Default'] ?? 'NULL',
            $col['Extra']
        ));
    }
    fclose($fp);
    echo "Schema dumped to schema_dump.txt";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
