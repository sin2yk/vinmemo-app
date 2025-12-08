<?php
// verify_schema.php
require_once 'db_connect.php';

/**
 * Print DESCRIBE result for a given table.
 *
 * @param PDO    $pdo
 * @param string $tableName
 */
function describeTable(PDO $pdo, string $tableName): void
{
    $output = "--- Table: {$tableName} ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE {$tableName}");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            $field = $col['Field'] ?? '';
            $type = $col['Type'] ?? '';
            $null = $col['Null'] ?? '';
            $default = $col['Default'] ?? '';
            $output .= "{$field} {$type} NULL={$null} DEFAULT=" . ($default === null ? 'NULL' : $default) . "\n";
        }
    } catch (PDOException $e) {
        $output .= "Table {$tableName} not found or error: " . $e->getMessage() . "\n";
    }
    $output .= "\n";
    file_put_contents('schema_output.txt', $output, FILE_APPEND);
}

describeTable($pdo, 'events');
describeTable($pdo, 'venues');
describeTable($pdo, 'bottle_entries');
describeTable($pdo, 'event_participants');
