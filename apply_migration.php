<?php
require_once 'db_connect.php';

$sqlFile = 'sql/migration_v1.2.sql';
if (!file_exists($sqlFile)) {
    exit("Migration file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    echo "Migration applied successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>