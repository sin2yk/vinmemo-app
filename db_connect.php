<?php
$dsn = 'mysql:dbname=vinmemo_db;host=localhost;charset=utf8mb4';
$user = 'root';
$pwd  = '';

try {
  $pdo = new PDO($dsn, $user, $pwd);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  exit('DB Connection Error: ' . $e->getMessage());
}
