<?php
// templates/guest_edit_thanks.php
// Expected variables:
// $editUrl (string)
// $publicUrl (string|null)
include __DIR__ . '/../partials/public_header.php';
?>
<main class="container main-container" style="padding-bottom: 80px; max-width: 600px; margin: 0 auto;">

    <section class="card" style="padding: 30px; margin-top: 40px; text-align: left;">
        <h1 style="font-size: 1.5rem; margin-top: 0; margin-bottom: 1rem; color: var(--text-main);">
            ボトル情報を更新しました<br>
            <span style="font-size: 0.8em; color: var(--text-muted);">Bottle Updated</span>
        </h1>

        <p style="margin-bottom: 20px; line-height: 1.6; color: var(--text-main);">
            編集内容が保存されました。<br>
            以下の編集用URLは、今後の変更に使えます。<br>
            <span style="font-size: 0.9em; color: var(--text-muted);">ブックマーク登録やメモアプリへの保存をおすすめします。</span>
        </p>

        <?php if (!empty($editUrl)): ?>
            <div style="background: rgba(0,0,0,0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <label
                    style="display: block; font-size: 0.8rem; font-weight: bold; margin-bottom: 5px; color: var(--text-muted);">
                    Edit URL / 編集用URL
                </label>
                <div style="display: flex; gap: 8px;">
                    <input id="edit-url-input" type="text" readonly
                        style="flex: 1; font-family: monospace; font-size: 0.9rem; padding: 8px; border: 1px solid var(--border); border-radius: 4px; background: #fff; color: #333;"
                        value="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>" onclick="this.select();">

                    <button type="button" class="vm-btn" onclick="copyEditUrl()"
                        style="padding: 8px 16px; font-size: 0.9rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: 4px; color: var(--text-main); cursor: pointer;">
                        Copy
                    </button>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px; margin-bottom: 0;">
                    ※クリックまたは「Copy」ボタンでURL全体をコピーできます。
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($publicUrl)): ?>
            <div style="text-align: center; margin-top: 30px;">
                <a href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>" class="vm-btn vm-btn--primary"
                    style="display: inline-block; text-decoration: none; padding: 12px 30px; border-radius: 50px;">
                    イベントページへ戻る / Return to Event
                </a>
            </div>
        <?php endif; ?>

    </section>

</main>

<script>
    function copyEditUrl() {
        const input = document.getElementById('edit-url-input');
        if (!input) return;
        input.select();

        // Modern API
        if (navigator.clipboard) {
            navigator.clipboard.writeText(input.value).then(() => {
                alert('編集用URLをコピーしました。\nCopied to clipboard.');
            }).catch(err => {
                console.error('Failed to copy: ', err);
                // Fallback
                document.execCommand('copy');
                alert('編集用URLをコピーしました。\nCopied to clipboard.');
            });
        } else {
            // Legacy
            document.execCommand('copy');
            alert('編集用URLをコピーしました。\nCopied to clipboard.');
        }
    }
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>