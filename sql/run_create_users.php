<?php
require_once __DIR__ . '/../db_connect.php';
$sql = file_get_contents(__DIR__ . '/create_users.sql');
try {
    $pdo->exec($sql);
    echo "Table 'users' created.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
