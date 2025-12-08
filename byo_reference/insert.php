<?php
// insert.php
require_once 'db_connect.php';

$pdo = get_pdo();

// POSTデータ取得
$event_id        = $_POST['event_id']        ?? 1;
$user_name       = $_POST['user_name']       ?? '';
$wine_producer   = $_POST['wine_producer']   ?? '';
$wine_name       = $_POST['wine_name']       ?? '';
$wine_vintage    = $_POST['wine_vintage']    ?? '';
$region          = $_POST['region']          ?? '';
$region_other    = $_POST['region_other']    ?? '';
$wine_type       = $_POST['wine_type']       ?? '';
$price_band      = $_POST['price_band']      ?? '';
$theme_fit       = (int)($_POST['theme_fit'] ?? 3);
$comment         = $_POST['comment']         ?? '';

// ブラインド用フラグ（チェックされていれば1、なければ0）
$blind_producer   = isset($_POST['blind_producer'])   ? 1 : 0;
$blind_wine_name  = isset($_POST['blind_wine_name'])  ? 1 : 0;
$blind_vintage    = isset($_POST['blind_vintage'])    ? 1 : 0;
$blind_region     = isset($_POST['blind_region'])     ? 1 : 0;
$blind_price_band = isset($_POST['blind_price_band']) ? 1 : 0;
$blind_comment    = isset($_POST['blind_comment'])    ? 1 : 0;

// パスワード（任意）→ ハッシュ化して保存
$edit_password      = $_POST['edit_password'] ?? '';
$blind_password_hash = $edit_password !== ''
  ? password_hash($edit_password, PASSWORD_DEFAULT)
  : null;

// 必須チェック（ざっくり）
if (
  $user_name === '' ||
  $wine_producer === '' ||
  $wine_name === '' ||
  $wine_vintage === '' ||
  $wine_type === '' ||
  $price_band === ''
) {
  exit('必須項目が未入力です。<a href="register.php">戻る</a>');
}

// INSERT文
$sql = 'INSERT INTO bottle_entries (
            event_id,
            user_name,
            wine_producer,
            wine_name,
            wine_vintage,
            region,
            region_other,
            wine_type,
            price_band,
            theme_fit,
            comment,
            blind_producer,
            blind_wine_name,
            blind_vintage,
            blind_region,
            blind_price_band,
            blind_comment,
            blind_password_hash
        ) VALUES (
            :event_id,
            :user_name,
            :wine_producer,
            :wine_name,
            :wine_vintage,
            :region,
            :region_other,
            :wine_type,
            :price_band,
            :theme_fit,
            :comment,
            :blind_producer,
            :blind_wine_name,
            :blind_vintage,
            :blind_region,
            :blind_price_band,
            :blind_comment,
            :blind_password_hash
        )';

$stmt = $pdo->prepare($sql);

$stmt->bindValue(':event_id',          $event_id,         PDO::PARAM_INT);
$stmt->bindValue(':user_name',         $user_name,        PDO::PARAM_STR);
$stmt->bindValue(':wine_producer',     $wine_producer,    PDO::PARAM_STR);
$stmt->bindValue(':wine_name',         $wine_name,        PDO::PARAM_STR);
$stmt->bindValue(':wine_vintage',      $wine_vintage,     PDO::PARAM_STR);
$stmt->bindValue(':region',            $region,           PDO::PARAM_STR);
$stmt->bindValue(':region_other',      $region_other,     PDO::PARAM_STR);
$stmt->bindValue(':wine_type',         $wine_type,        PDO::PARAM_STR);
$stmt->bindValue(':price_band',        $price_band,       PDO::PARAM_STR);
$stmt->bindValue(':theme_fit',         $theme_fit,        PDO::PARAM_INT);
$stmt->bindValue(':comment',           $comment,          PDO::PARAM_STR);
$stmt->bindValue(':blind_producer',    $blind_producer,   PDO::PARAM_INT);
$stmt->bindValue(':blind_wine_name',   $blind_wine_name,  PDO::PARAM_INT);
$stmt->bindValue(':blind_vintage',     $blind_vintage,    PDO::PARAM_INT);
$stmt->bindValue(':blind_region',      $blind_region,     PDO::PARAM_INT);
$stmt->bindValue(':blind_price_band',  $blind_price_band, PDO::PARAM_INT);
$stmt->bindValue(':blind_comment',     $blind_comment,    PDO::PARAM_INT);
$stmt->bindValue(':blind_password_hash', $blind_password_hash, $blind_password_hash === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

try {
  $stmt->execute();
} catch (PDOException $e) {
  exit('InsertError: ' . $e->getMessage());
}

// 登録後は一覧へリダイレクト
header('Location: list.php');
exit;
