<?php
require_once 'helpers.php';

$eventId = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$token = $_GET['token'] ?? '';

// Basic valid check
if (!$eventId || !$token) {
    header('Location: home.php');
    exit;
}

// Generate the secret link
// Assuming standard setup, we can use relative path or build full URL.
// Relative is safer for localhost/deployment portability.
$editLink = "bottle_edit.php?token=" . h($token);
$fullEditLink = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
// Provide a cleaner absolute URL for copying
$baseDir = dirname($_SERVER['PHP_SELF']);
$cleanLink = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . $_SERVER['HTTP_HOST'] . $baseDir . "/bottle_edit.php?token=" . h($token);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bottle Added - VinMemo</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php require_once 'layout/header.php'; ?>

    <div class="container" style="max-width:600px; margin-top:40px;">
        <div class="card" style="text-align:center; padding:40px 20px;">
            <h2 style="color:var(--accent); margin-bottom:20px;">ボトルを登録しました！ / Bottle Added!</h2>
            <p>Thank you for your contribution.</p>

            <div
                style="background:rgba(255,152,0,0.1); border:1px solid var(--accent); padding:20px; border-radius:8px; margin:30px 0;">
                <h4 style="margin-top:0; color:var(--text-main);">🔑 Save Your Secret Edit Link</h4>
                <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:15px;">
                    This link is the <strong>ONLY</strong> way to edit or delete your bottle later without logging in.
                </p>

                <input type="text" id="secretLink" value="<?= h($cleanLink) ?>" readonly
                    style="width:100%; padding:10px; border-radius:4px; border:1px solid #555; background:#222; color:#fff; font-family:monospace; margin-bottom:10px;">

                <button onclick="copyLink()" class="button" style="background:#555; font-size:0.9rem;">Copy
                    Link</button>
                <span id="copyMsg" style="margin-left:10px; color:#4caf50; display:none;">Copied!</span>
            </div>

            <div style="margin-top:30px;">
                <a href="event_show.php?id=<?= h($eventId) ?>" class="button">イベントに戻る / Return to Event</a>
            </div>
        </div>
    </div>

    <script>
        function copyLink() {
            var copyText = document.getElementById("secretLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile devices
            navigator.clipboard.writeText(copyText.value);

            var msg = document.getElementById("copyMsg");
            msg.style.display = "inline";
            setTimeout(function () { msg.style.display = "none"; }, 2000);
        }
    </script>

    <?php require_once 'layout/footer.php'; ?>
</body>

</html>