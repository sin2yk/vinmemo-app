<?php
require_once 'db_connect.php';
session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error = null;
$bottle = null;

if (!$id) {
    die('Invalid Bottle ID');
}

// データ取得と更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $owner_label = $_POST['owner_label'] ?? '';
    $wine_name = $_POST['wine_name'] ?? '';
    $producer_name = $_POST['producer_name'] ?? '';
    $country = $_POST['country'] ?? '';
    $region = $_POST['region'] ?? '';
    $appellation = $_POST['appellation'] ?? '';
    $color = $_POST['color'] ?? 'red';
    $vintage = filter_input(INPUT_POST, 'vintage', FILTER_VALIDATE_INT);
    $bottle_size_ml = filter_input(INPUT_POST, 'bottle_size_ml', FILTER_VALIDATE_INT) ?: 750;
    $est_price_yen = filter_input(INPUT_POST, 'est_price_yen', FILTER_VALIDATE_INT);
    $theme_fit_score = filter_input(INPUT_POST, 'theme_fit_score', FILTER_VALIDATE_INT);
    $is_blind = isset($_POST['is_blind']) ? 1 : 0;
    $memo = $_POST['memo'] ?? '';

    if (!$id || !$event_id) {
        $error = 'IDが不正です。';
    } elseif ($owner_label === '' || $wine_name === '') {
        $error = '持参者名とワイン名は必須です。';
    } else {
        $sql = 'UPDATE bottle_entries SET
                    owner_label = :owner_label,
                    wine_name = :wine_name,
                    producer_name = :producer_name,
                    country = :country,
                    region = :region,
                    appellation = :appellation,
                    color = :color,
                    vintage = :vintage,
                    bottle_size_ml = :bottle_size_ml,
                    est_price_yen = :est_price_yen,
                    theme_fit_score = :theme_fit_score,
                    is_blind = :is_blind,
                    memo = :memo,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':owner_label', $owner_label, PDO::PARAM_STR);
        $stmt->bindValue(':wine_name', $wine_name, PDO::PARAM_STR);
        $stmt->bindValue(':producer_name', $producer_name, PDO::PARAM_STR);
        $stmt->bindValue(':country', $country, PDO::PARAM_STR);
        $stmt->bindValue(':region', $region, PDO::PARAM_STR);
        $stmt->bindValue(':appellation', $appellation, PDO::PARAM_STR);
        $stmt->bindValue(':color', $color, PDO::PARAM_STR);
        $stmt->bindValue(':vintage', $vintage, PDO::PARAM_INT);
        $stmt->bindValue(':bottle_size_ml', $bottle_size_ml, PDO::PARAM_INT);
        $stmt->bindValue(':est_price_yen', $est_price_yen, PDO::PARAM_INT);
        $stmt->bindValue(':theme_fit_score', $theme_fit_score, PDO::PARAM_INT);
        $stmt->bindValue(':is_blind', $is_blind, PDO::PARAM_INT);
        $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        header("Location: event_show.php?id={$event_id}");
        exit;
    }

} else {
    // 初期表示：データ取得
    $stmt = $pdo->prepare('SELECT * FROM bottle_entries WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $bottle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bottle) {
        die('Bottle not found');
    }
}
?>
<!DOCTYPE html>

</html>