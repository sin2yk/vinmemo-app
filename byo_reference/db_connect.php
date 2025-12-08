<?php
// DB接続共通ファイル
// 必要に応じて DB名・ユーザー名・パスワードを自分の環境に合わせて修正

function get_pdo()
{
    $db_name = 'gs_wineparty';   // データベース名
    $db_host = 'localhost';      // ホスト名
    $db_user = 'root';           // ユーザー名（XAMPPデフォルト）
    $db_pass = '';               // パスワード（XAMPPデフォルトは空）

    $dsn = "mysql:dbname={$db_name};charset=utf8;host={$db_host}";
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        exit('DBConnectError: ' . $e->getMessage());
    }
}
// G's教材互換用のラッパー関数（db_conn → get_pdo の別名）
function db_conn()
{
    return get_pdo();
}
