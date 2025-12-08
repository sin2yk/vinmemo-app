<?php
require_once 'db_connect.php';
session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Invalid ID');
}

// 削除対象のイベントIDを取得しておく（リダイレクト用）
$stmt = $pdo->prepare('SELECT event_id FROM bottle_entries WHERE id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$bottle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bottle) {
    die('Bottle not found');
}

$event_id = $bottle['event_id'];

// 削除実行
$stmt = $pdo->prepare('DELETE FROM bottle_entries WHERE id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// 元のイベントページへ戻る
header("Location: event_show.php?id={$event_id}");
exit;
