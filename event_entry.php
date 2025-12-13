<?php
// event_entry.php : Guest Entry Point via Event Token (ET)
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php';

// 1. GET (Initial Access)
$et = $_GET['ET'] ?? null;
if (!$et) {
    echo "Access Denied: Missing Event Token."; // Simple 403
    exit;
}

// Fetch Event by Token
$sql = "SELECT * FROM events WHERE event_token = :token";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':token', $et, PDO::PARAM_STR);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "Event Not Found or Invalid Token.";
    exit;
}

// 2. Handle POST (Submit Bottle)
$error = null;
$createdEditUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_label = trim($_POST['owner_label'] ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');

    // Wine Fields
    $wine_name = trim($_POST['wine_name'] ?? '');
    $producer = trim($_POST['producer'] ?? '');
    $vintage = trim($_POST['vintage'] ?? '');
    $color = $_POST['color'] ?? '';
    // Optional
    $memo = trim($_POST['memo'] ?? '');
    $price_band = $_POST['price_band'] ?? '';
    $theme_fit = filter_input(INPUT_POST, 'theme_fit', FILTER_VALIDATE_INT);

    if ($owner_label === '') {
        $error = "Display Name is required.";
    } elseif ($wine_name === '') {
        $error = "Wine Name is required.";
    } else {
        // Generate EDT (Edit Token)
        $edit_token = bin2hex(random_bytes(16));

        try {
            $sql = "INSERT INTO bottle_entries (
                event_id, user_id, guest_email, owner_label,
                wine_name, producer, vintage, color, memo, price_band, theme_fit_score,
                edit_token, created_at, updated_at
            ) VALUES (
                :eid, NULL, :email, :label,
                :wine, :prod, :vint, :color, :memo, :price, :fit,
                :edt, NOW(), NOW()
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':eid', $event['id'], PDO::PARAM_INT);
            $stmt->bindValue(':email', $guest_email, PDO::PARAM_STR);
            $stmt->bindValue(':label', $owner_label, PDO::PARAM_STR);
            $stmt->bindValue(':wine', $wine_name, PDO::PARAM_STR);
            $stmt->bindValue(':prod', $producer, PDO::PARAM_STR);
            $stmt->bindValue(':vint', $vintage, PDO::PARAM_STR);
            $stmt->bindValue(':color', $color, PDO::PARAM_STR);
            $stmt->bindValue(':memo', $memo, PDO::PARAM_STR);
            $stmt->bindValue(':price', $price_band, PDO::PARAM_STR);
            $stmt->bindValue(':fit', $theme_fit, PDO::PARAM_INT); // NULL allowed if empty? filter returns false/null.
            $stmt->bindValue(':edt', $edit_token, PDO::PARAM_STR);

            $stmt->execute();

            // Build Edit URL
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
                . $_SERVER['HTTP_HOST']
                . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

            $createdEditUrl = $baseUrl . '/bottle_edit.php?edt=' . urlencode($edit_token);

        } catch (PDOException $e) {
            $error = 'DB Error: ' . $e->getMessage();
        }
    }
}

// Page Setup
$page_title = 'Guest Entry - ' . $event['title'];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header class="site-header">
        <div class="header-inner">
            <div class="site-title">
                <h1>VinMemo Guest Entry</h1>
            </div>
        </div>
    </header>

    <div class="container main-container bottle-page">

        <?php if ($createdEditUrl): ?>
            <div class="card" style="text-align:center; padding:40px;">
                <h2 style="color:var(--accent);">Thank You! / 登録完了</h2>
                <p>Your bottle has been registered.</p>
                <div style="margin:30px 0; padding:20px; background:rgba(255,255,255,0.1); border-radius:8px;">
                    <p style="margin-bottom:10px; font-weight:bold;">Safe-keep this URL to edit/delete later:</p>
                    <input type="text" readonly value="<?= h($createdEditUrl) ?>"
                        style="width:100%; text-align:center; font-size:1.1em; color:#fff; background:#333; border:none; padding:10px;">
                    <p style="font-size:0.9em; color:#ccc; margin-top:10px;">
                        ※ このURLをブックマークしてください。後で修正や削除ができます。
                    </p>
                </div>
                <!-- Optional: Add link to register another bottle (reloads page with ET) -->
                <a href="event_entry.php?ET=<?= h($et) ?>" class="vm-btn vm-btn--secondary">Register Another Bottle</a>
            </div>

        <?php else: ?>

            <!-- Event Info Block -->
            <div class="card" style="margin-bottom:20px;">
                <h2 style="margin-top:0;"><?= h($event['title']) ?></h2>
                <p style="color:var(--text-muted);">
                    <?= h(getEventDateDisplay($event)) ?> @ <?= h($event['place']) ?>
                </p>
                <?php
                $m = parseEventMemo($event['memo'])['meta'] ?? [];
                if (!empty($m['theme_description'])): ?>
                    <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #555;">
                        <strong>Theme:</strong> <?= nl2br(h($m['theme_description'])) ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post" action="event_entry.php?ET=<?= h($et) ?>" class="bottle-form card">
                <h3>Register Your Bottle / ワインを登録</h3>

                <div class="form-group">
                    <label>Display Name / 表示名 (必須)</label>
                    <input type="text" name="owner_label" required placeholder="Taro.Y / ニックネーム"
                        value="<?= h($_POST['owner_label'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Email / 連絡用メール (任意)</label>
                    <input type="email" name="guest_email" placeholder="For notifications (optional)"
                        value="<?= h($_POST['guest_email'] ?? '') ?>">
                </div>

                <hr>

                <!-- Simplified Wine Fields -->
                <div class="form-group">
                    <label>Wine Name / ワイン名 (必須)</label>
                    <input type="text" name="wine_name" required value="<?= h($_POST['wine_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Producer / 生産者</label>
                    <input type="text" name="producer" value="<?= h($_POST['producer'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Vintage / ヴィンテージ</label>
                        <input type="text" name="vintage" placeholder="NV / 2020" value="<?= h($_POST['vintage'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Color / タイプ</label>
                        <select name="color">
                            <option value="red">Red / 赤</option>
                            <option value="white">White / 白</option>
                            <option value="sparkling">Sparkling /泡</option>
                            <option value="rose">Rose / ロゼ</option>
                            <option value="orange">Orange / オレンジ</option>
                            <option value="sweet">Sweet / 甘口</option>
                            <option value="fortified">Fortified /酒精強化</option>
                            <option value="other">Other / その他</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Price Band / 価格帯</label>
                    <select name="price_band">
                        <option value="">- Select -</option>
                        <option value="under_3k">Under 3k</option>
                        <option value="3k_5k">3k - 5k</option>
                        <option value="5k_10k">5k - 10k</option>
                        <option value="10k_20k">10k - 20k</option>
                        <option value="over_20k">Over 20k</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Memo / コメント</label>
                    <textarea name="memo" rows="3"><?= h($_POST['memo'] ?? '') ?></textarea>
                </div>

                <div style="margin-top:20px; text-align:center;">
                    <button type="submit" class="vm-btn vm-btn--primary" style="width:100%; padding:15px; font-size:1.2em;">
                        Register Bottle
                    </button>
                </div>
            </form>
        <?php endif; ?>

    </div>
    <?php require_once 'layout/footer.php'; ?>
</body>

</html>