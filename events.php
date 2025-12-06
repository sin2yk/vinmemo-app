<?php
require_once 'db_connect.php'; // ここでエラーならDB接続失敗

echo "DB接続OKっぽいよ<br>";

// １件だけSELECTしてみる
$sql = 'SELECT COUNT(*) AS cnt FROM events';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "eventsテーブルの件数: " . $row['cnt'];
?>
