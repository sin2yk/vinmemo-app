<?php
session_start();
require_once('db_connect.php');
require_once('funcs.php');

$name = $_POST['name'];
$lid  = $_POST['lid'];
$lpw  = $_POST['lpw'];
$role = $_POST['role'] ?? 'guest';

// パスワードをハッシュ化
$hash = password_hash($lpw, PASSWORD_DEFAULT);

$pdo = db_conn();$pdo = get_pdo();  // これでもOK
// もしくは
$pdo = db_conn();  // ラッパー追加後ならこっちでもOK

$sql = 'INSERT INTO users(name, lid, lpw, role) VALUES(:name, :lid, :lpw, :role)';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':lid',  $lid,  PDO::PARAM_STR);
$stmt->bindValue(':lpw',  $hash, PDO::PARAM_STR);
$stmt->bindValue(':role', $role, PDO::PARAM_STR);

$status = $stmt->execute();

if($status==false){
  sql_error($stmt);
}else{
  header('Location: login.php');
  exit;
}

?>