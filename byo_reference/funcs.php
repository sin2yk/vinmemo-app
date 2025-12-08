<?php
// XSS対策用のエスケープ関数
function h($str){
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// SQLエラー時に内容を表示して止める
function sql_error($stmt){
  $error = $stmt->errorInfo();
  exit('SQLError:'.$error[2]);
}

// リダイレクトの共通関数
function redirect($file_name){
  header('Location: '.$file_name);
  exit();
}

// ログインチェック（あとで使う用の枠だけ用意）
function loginCheck(){
  if(!isset($_SESSION["chk_ssid"]) || $_SESSION["chk_ssid"] != session_id()){
    exit("LOGIN ERROR");
  } else {
    session_regenerate_id(true);
    $_SESSION["chk_ssid"] = session_id();
  }
}
?>