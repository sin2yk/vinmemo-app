<?php
// templates/media_upload_thanks.php
// Expected: $publicUrl (string)
include __DIR__ . '/../partials/public_header.php';
?>
<main class="container main-container" style="padding-bottom: 80px; max-width: 600px; margin: 0 auto;">

    <section class="card" style="padding: 30px; margin-top: 40px; text-align: center;">
        <h1 style="font-size: 1.5rem; margin-top: 0; margin-bottom: 1rem; color: var(--text-main);">
            Media Uploaded!<br>
            <span style="font-size: 0.8em; color: var(--text-muted);">アップロード完了</span>
        </h1>

        <p style="margin-bottom: 30px; line-height: 1.6; color: var(--text-main);">
            写真・ファイルのアップロードが完了しました。<br>
            イベントページに反映されています。
        </p>

        <?php if (!empty($publicUrl)): ?>
            <div style="text-align: center;">
                <a href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>" class="vm-btn vm-btn--primary"
                    style="display: inline-block; text-decoration: none; padding: 12px 30px; border-radius: 50px;">
                    イベントページへ戻る / Return to Event
                </a>
            </div>
        <?php endif; ?>

    </section>

</main>
<?php include __DIR__ . '/../layout/footer.php'; ?>