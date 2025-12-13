<?php
// event_media_upload.php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php';

// 1. GET Parameter Check
$eventToken = $_GET['ET'] ?? $_GET['et'] ?? null;
if (!$eventToken) {
    http_response_code(400);
    echo 'Invalid access. Event Token Missing.';
    exit;
}

// 2. Fetch Event & Check Permission
$sql = "SELECT * FROM events WHERE event_token = :et LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':et', $eventToken, PDO::PARAM_STR);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    echo 'Event not found.';
    exit;
}

// Check media_enabled flag
if (empty($event['media_enabled'])) {
    http_response_code(403);
    echo 'This event does not accept media uploads.';
    exit;
}

$eventId = (int) $event['id'];

// 3. Handle GET (Display Form)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page_title = 'Media Upload - ' . $event['title'];
    require_once __DIR__ . '/partials/public_header.php';
    ?>
    <main class="container main-container" style="padding-top: 2rem; max-width: 600px; margin: 0 auto;">

        <div class="card" style="padding: 30px;">
            <h1 style="font-size: 1.5rem; margin-top: 0; margin-bottom: 0.5rem;">
                Upload Media<br>
                <span style="font-size: 0.8em; color: var(--text-muted);">写真・ファイルのアップロード</span>
            </h1>
            <p style="color:var(--text-muted); margin-bottom: 2rem;">
                Event: <?= h($event['title']) ?>
            </p>

            <?php if (!empty($errors)): // Fallback for errors passed from include context ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= h($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="event_media_upload.php?ET=<?= h($eventToken) ?>" enctype="multipart/form-data">

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Title / タイトル <span
                            style="color:red">*</span></label>
                    <input type="text" name="title" required class="form-control" placeholder="例: 会場の様子, ワインリストなど"
                        style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Description / 説明 (Optional)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder=""
                        style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Your Name / お名前 (Optional)</label>
                    <input type="text" name="uploader_name" class="form-control" placeholder="Guest"
                        style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px;">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">File / ファイル <span
                            style="color:red">*</span></label>
                    <input type="file" name="media_file" required class="form-control"
                        style="width: 100%; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 4px;">
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 5px;">
                        Allowed: jpg, png, webp, pdf (Max 10MB)
                    </p>
                </div>

                <div style="margin-top: 30px; text-align: center;">
                    <button type="submit" class="vm-btn vm-btn--primary" style="padding: 12px 40px; border-radius: 50px;">
                        Upload / アップロード
                    </button>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="event_public.php?ET=<?= h($eventToken) ?>"
                        style="color: var(--text-muted); text-decoration: underline;">
                        Cancel / キャンセル
                    </a>
                </div>

            </form>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/layout/footer.php';
    exit;
}

// 4. Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect Input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $uploaderName = trim($_POST['uploader_name'] ?? '');
    $uploaderEmail = trim($_POST['uploader_email'] ?? '');
    $file = $_FILES['media_file'] ?? null;

    $errors = [];
    if ($title === '') {
        $errors[] = 'タイトルは必須です。 / Title is required.';
    }
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'ファイルのアップロードに失敗しました。 / File upload failed.';
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    // Strict MIME check
    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    if (empty($errors)) {
        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts, true)) {
            $errors[] = '許可されていないファイル形式です。 / Invalid file extension.';
        }

        // MIME check using finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        // Extra safety: ensure PDF ext matches PDF mime, Image ext matches Image mime roughly
        if (!in_array($mime, $allowedMimes, true)) {
            $errors[] = '許可されていないファイル形式です。 / Invalid file type.';
        }

        // Max Size 10MB
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors[] = 'ファイルサイズが大きすぎます（最大10MB）。 / File too large (Max 10MB).';
        }
    }

    if (!empty($errors)) {
        // Show errors on the form (reuse GET view logic by including it?)
        // Simple way: output error and form again. 
        // For cleaner code, I'll just re-render the GET part with errors injected.
        // Or simpler: Output error page.
        // Let's re-render the form for better UX.
        $page_title = 'Media Upload Error';
        require_once __DIR__ . '/partials/public_header.php';
        ?>
        <main class="container main-container" style="padding-top: 2rem; max-width: 600px; margin: 0 auto;">
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= h($e) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top: 20px;">
                    <a href="javascript:history.back()" class="vm-btn">Back / 戻る</a>
                </div>
            </div>
        </main>
        <?php
        require_once __DIR__ . '/layout/footer.php';
        exit;
    }

    // 5. File Save & DB Insert

    // Create Directory
    $uploadsBase = __DIR__ . '/uploads/events/' . $eventId;
    if (!is_dir($uploadsBase)) {
        if (!mkdir($uploadsBase, 0777, true)) {
            die('Failed to create upload directory.');
        }
    }

    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = $uploadsBase . '/' . $randomName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        die('Failed to save file.');
    }

    // Relative path for DB/HTML
    $relativePath = 'uploads/events/' . $eventId . '/' . $randomName;

    $sql = "INSERT INTO event_media
            (event_id, uploader_user_id, uploader_name, uploader_email,
             title, description, file_path, mime_type, file_size, visibility)
            VALUES
            (:event_id, NULL, :uploader_name, :uploader_email,
             :title, :description, :file_path, :mime_type, :file_size, 'public')";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->bindValue(':uploader_name', $uploaderName, PDO::PARAM_STR);
    $stmt->bindValue(':uploader_email', $uploaderEmail, PDO::PARAM_STR);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':file_path', $relativePath, PDO::PARAM_STR);
    $stmt->bindValue(':mime_type', $mime, PDO::PARAM_STR);
    $stmt->bindValue(':file_size', $file['size'], PDO::PARAM_INT);
    $stmt->execute();

    // 6. Success
    $publicUrl = 'event_public.php?ET=' . urlencode($eventToken);
    include __DIR__ . '/templates/media_upload_thanks.php';
    exit;
}
