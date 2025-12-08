<?php
session_start();
require_once('db_connect.php'); // get_pdo()/db_conn()
require_once('funcs.php');      // redirect(), loginCheck() など

$lid = $_POST['lid'] ?? '';
$lpw = $_POST['lpw'] ?? '';

if ($lid === '' || $lpw === '') {
    exit('IDとパスワードを入力してください');
}

$pdo = db_conn();

// ログインIDでユーザーを取得
$sql = 'SELECT * FROM users WHERE lid = :lid';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
$stmt->execute();

$val = $stmt->fetch(PDO::FETCH_ASSOC);

// ユーザーが存在し、パスワード一致ならログイン成功
if ($val && password_verify($lpw, $val['lpw'])) {
    $_SESSION['chk_ssid'] = session_id();
    $_SESSION['user_id']  = $val['id'];
    $_SESSION['name']     = $val['name'];
    $_SESSION['role']     = $val['role'];

    // ログイン後の遷移先（BYOトップ）
    redirect('index.php');  // index.php がまだなら list.php でもOK
} else {
    exit('Login Error: IDまたはパスワードが違います');
}
?>