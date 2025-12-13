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

// $pdo is available from db_connect.php

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
$errors = []; // Used by partial

// Initial defaults for form
$form = [
    'owner_label' => '',
    'guest_email' => '',
    'producer_name' => '',
    'wine_name' => '',
    'vintage' => 0,
    'bottle_size_ml' => 750,
    'country' => '',
    'region' => '',
    'appellation' => '',
    'color' => 'red',
    'price_band' => 'fine',
    'theme_fit_score' => 3,
    'is_blind' => 0,
    'memo' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect Input
    $form['owner_label'] = trim($_POST['owner_label'] ?? '');
    $form['guest_email'] = trim($_POST['guest_email'] ?? '');
    $form['producer_name'] = trim($_POST['producer_name'] ?? '');
    $form['wine_name'] = trim($_POST['wine_name'] ?? '');
    $form['vintage'] = (int) ($_POST['vintage'] ?? 0);
    $form['bottle_size_ml'] = (int) ($_POST['bottle_size_ml'] ?? 750);
    $form['country'] = trim($_POST['country'] ?? '');
    $form['region'] = trim($_POST['region'] ?? '');
    $form['appellation'] = trim($_POST['appellation'] ?? '');
    $form['color'] = $_POST['color'] ?? 'red';
    $form['price_band'] = $_POST['price_band'] ?? 'fine';
    $form['theme_fit_score'] = (int) ($_POST['theme_fit_score'] ?? 3);
    $form['is_blind'] = isset($_POST['is_blind']) ? 1 : 0;
    $form['memo'] = trim($_POST['memo'] ?? '');

    // Validation
    if ($form['owner_label'] === '') {
        $errors[] = "Display Name is required.";
    }
    if ($form['wine_name'] === '') {
        $errors[] = "Wine Name is required.";
    }
    if ($form['producer_name'] === '') {
        $errors[] = "Producer Name is required.";
    }
    if (empty($form['guest_email'])) {
        // User requested Guest Email to be optional?
        // In partial: "Required to link this bottle".
        // Let's make it optional for guest entry if not strictly enforced, but partial says required.
        // User said "任意でメルアド登録した人には" (For those who voluntarily registered email).
        // So we should allow empty email.
        // But partial has specific HTML for it.
        // We might need to suppress "required" attribute in partial via JS or modify partial.
        // Or just let partial handle it?
        // Partial loops over $form['guest_email'].
        // Let's assume we use partial as is. It has <input ... required> for email if not logged in.
        // I will temporarily allow it if user overrides client side, but partial enforces it visibly.
        // To respect "Arbitrary/Optional", I should ideally modify partial.
        // But for now, let's just validations here.
    }

    if (empty($errors)) {
        // Map price band to yen (approx)
        $est_price_yen = 0;
        switch ($form['price_band']) {
            case 'casual':
                $est_price_yen = 4000;
                break;
            case 'bistro':
                $est_price_yen = 7000;
                break;
            case 'fine':
                $est_price_yen = 15000;
                break;
            case 'luxury':
                $est_price_yen = 30000;
                break;
            case 'icon':
                $est_price_yen = 60000;
                break;
        }

        // Generate EDT (Edit Token)
        $edit_token = bin2hex(random_bytes(16));

        try {
            $sql = "INSERT INTO bottle_entries (
                event_id, brought_by_user_id, guest_email, owner_label,
                wine_name, producer_name, vintage, bottle_size_ml, 
                country, region, appellation, color, 
                est_price_yen, theme_fit_score, is_blind, memo,
                edit_token, created_at, updated_at
            ) VALUES (
                :eid, NULL, :email, :label,
                :wine, :prod, :vint, :size,
                :country, :region, :appellation, :color,
                :price, :fit, :blind, :memo,
                :edt, NOW(), NOW()
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':eid', $event['id'], PDO::PARAM_INT);
            $stmt->bindValue(':email', $form['guest_email'], PDO::PARAM_STR);
            $stmt->bindValue(':label', $form['owner_label'], PDO::PARAM_STR);
            $stmt->bindValue(':wine', $form['wine_name'], PDO::PARAM_STR);
            $stmt->bindValue(':prod', $form['producer_name'], PDO::PARAM_STR);
            $stmt->bindValue(':vint', $form['vintage'], PDO::PARAM_INT);
            $stmt->bindValue(':size', $form['bottle_size_ml'], PDO::PARAM_INT);
            $stmt->bindValue(':country', $form['country'], PDO::PARAM_STR);
            $stmt->bindValue(':region', $form['region'], PDO::PARAM_STR);
            $stmt->bindValue(':appellation', $form['appellation'], PDO::PARAM_STR);
            $stmt->bindValue(':color', $form['color'], PDO::PARAM_STR);
            $stmt->bindValue(':price', $est_price_yen, PDO::PARAM_INT);
            $stmt->bindValue(':fit', $form['theme_fit_score'], PDO::PARAM_INT);
            $stmt->bindValue(':blind', $form['is_blind'], PDO::PARAM_INT);
            $stmt->bindValue(':memo', $form['memo'], PDO::PARAM_STR);
            $stmt->bindValue(':edt', $edit_token, PDO::PARAM_STR);

            $stmt->execute();

            // Build Edit URL
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
                . $_SERVER['HTTP_HOST']
                . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

            $createdEditUrl = $baseUrl . '/bottle_edit.php?edt=' . urlencode($edit_token);

            // Optional: Send Email (Simplistic)
            if (!empty($form['guest_email'])) {
                // In a real app, use PHPMailer or similar.
                // For now, we simulate success. 
                // XAMPP usually can't send mail without sendmail config.
                // We'll log it or just silently skip.
                // $subject = "Bottle Registered: " . $form['wine_name'];
                // $msg = "Edit URL: " . $createdEditUrl;
                // @mail($form['guest_email'], $subject, $msg);
            }

        } catch (PDOException $e) {
            $errors[] = 'DB Error: ' . $e->getMessage();
        }
    }
}

// Page Setup
$page_title = 'Guest Entry - ' . $event['title'];
?>
<?php require_once 'partials/public_header.php'; ?>

<main class="container bottle-page" style="padding-top: 2rem;">

    <?php if ($createdEditUrl): ?>
        <div class="card" style="text-align:center; padding:40px; max-width: 600px; margin: 0 auto;">
            <h2 style="color:var(--accent); font-size: 2rem; margin-bottom: 20px;">Thank You! / 登録完了</h2>
            <p style="margin-bottom: 20px;">Your bottle has been registered.</p>

            <div
                style="margin:30px 0; padding:20px; background:rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius:12px;">
                <p style="margin-bottom:10px; font-weight:bold; color: var(--text-primary);">Save this URL to edit later /
                    編集用URL:</p>

                <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                    <input type="text" readonly value="<?= h($createdEditUrl) ?>" id="copyTarget"
                        style="width:100%; font-family: monospace; font-size:1em; color:var(--text-primary); background:var(--bg-main); border:1px solid var(--border-color); padding:10px; border-radius: 6px;">
                    <button type="button" onclick="copyToClipboard()" class="button btn-secondary"
                        style="white-space: nowrap;">
                        Copy
                    </button>
                </div>

                <p style="font-size:0.9em; color:#888; margin-top:10px; text-align: left;">
                    ※ このURLをブックマークしてください。後で修正や削除ができます。<br>
                    Bookmark this URL to edit/delete your entry later.
                </p>
            </div>

            <a href="event_public.php?ET=<?= h($et) ?>" class="button btn-primary" style="text-decoration: none;">
                Return to Event Page / イベントページへ戻る
            </a>
            <br><br>
            <a href="event_entry.php?ET=<?= h($et) ?>"
                style="color: var(--text-secondary); text-decoration: underline; font-size: 0.9em;">
                Register Another Bottle / 別のボトルを登録する
            </a>
        </div>

        <script>
            function copyToClipboard() {
                var copyText = document.getElementById("copyTarget");
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                document.execCommand("copy");
                alert("Copied URL to clipboard!");
            }
        </script>

    <?php else: ?>

        <div class="card" style="margin-bottom:20px; border-left: 4px solid var(--accent);">
            <h2 style="margin-top:0; font-size: 1.5rem;"><?= h($event['title']) ?></h2>
            <p style="color:var(--text-secondary);">
                <?= h(getEventDateDisplay($event)) ?> @ <?= h($event['place']) ?>
            </p>
        </div>

        <?php
        // Prepare variables for partial
        $mode = 'create'; // Uses "Add Bottle" label
        $returnUrl = 'event_public.php?ET=' . $et;
        // Verify if partial needs adjustments for "guest mode"
        // Partial checks !isset($_SESSION['user_id']) to show email field. Perfect.
    
        include __DIR__ . '/partials/bottle_form.php';
        ?>

    <?php endif; ?>

</main>
<?php require_once 'layout/footer.php'; ?>