<?php
// tools/verify_schema.php
// VinMemo DB スキーマのチェックツール（単体で動くように DSN を直書き）

header('Content-Type: text/plain; charset=utf-8');

// ★ここをあなたの環境に合わせる（スクショ前提ならこのままでOK）
$dsn      = 'mysql:host=127.0.0.1;dbname=vinmemo_db;charset=utf8mb4';
$db_user  = 'root';
$db_pass  = '';   // XAMPP のデフォルトなら空

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "DB connection: OK\n\n";
} catch (PDOException $e) {
    echo "DB connection FAILED: " . $e->getMessage() . "\n";
    exit;
}

$tables = ['events', 'bottle_entries'];

foreach ($tables as $table) {
    echo "=== $table ===\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . ' ' . $row['Type'] . " " . $row['Null'] . "\n";
        }
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
