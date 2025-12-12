<?php
// partials/bottle_form.php
// Shared form for creating and editing bottles.
// Expects: $mode, $form, $errors (optional), $returnUrl, $bottleId (optional), $event (optional)

// Defaults
if (!isset($errors))
    $errors = [];
$submitLabel = $mode === 'edit' ? 'Save' : 'Add Bottle';
?>

<?php if (!empty($errors)): ?>
    <div class="error-msg">
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="bottle-form">
    <?php if ($mode === 'edit'): ?>
        <input type="hidden" name="id" value="<?= h($bottleId) ?>">
    <?php else: ?>
        <input type="hidden" name="event_id" value="<?= h($event['id'] ?? '') ?>">
    <?php endif; ?>


    <!-- SECTION A: Core bottle identity -->
    <div class="form-section">
        <h3>Core Identity / 基本情報</h3>

        <div class="form-group">
            <label>Your Name / お名前 <span style="color:var(--danger)">*</span></label>
            <input type="text" name="owner_label" value="<?= h($form['owner_label']) ?>" required
                placeholder="Your Name">
        </div>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="form-group">
                <label>Email / メールアドレス (For claiming history) <span style="color:var(--danger)">*</span></label>
                <input type="email" name="guest_email" value="<?= h($form['guest_email'] ?? '') ?>"
                    placeholder="your@email.com" required>
                <small style="color:var(--text-muted); display:block; margin-top:4px;">
                    Required to link this bottle to your account later.
                </small>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Producer / 生産者 <span style="color:var(--danger)">*</span></label>
            <input type="text" name="producer_name" value="<?= h($form['producer_name']) ?>" required
                placeholder="e.g. Domaine Leflaive">
        </div>

        <div class="form-group">
            <label>Wine Name / ワイン名 <span style="color:var(--danger)">*</span></label>
            <input type="text" name="wine_name" value="<?= h($form['wine_name']) ?>" required
                placeholder="e.g. Puligny-Montrachet">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Vintage / ヴィンテージ</label>
                <select name="vintage">
                    <option value="0" <?= $form['vintage'] == 0 ? 'selected' : '' ?>>NV / Unknown</option>
                    <option value="1970" <?= $form['vintage'] == 1970 ? 'selected' : '' ?>>1970 or earlier</option>
                    <?php
                    $currentYear = (int) date('Y');
                    for ($y = $currentYear; $y >= 1971; $y--) {
                        $selected = ($form['vintage'] == $y) ? 'selected' : '';
                        echo "<option value=\"$y\" $selected>$y</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>
                    Bottle Size / 容量
                </label>
                <!-- Using simple select for consistency between new/edit -->
                <select name="bottle_size_ml">
                    <option value="750" <?= $form['bottle_size_ml'] == 750 ? 'selected' : '' ?>>Bottle (750ml)</option>
                    <option value="1500" <?= $form['bottle_size_ml'] == 1500 ? 'selected' : '' ?>>Magnum (1500ml)</option>
                    <option value="375" <?= $form['bottle_size_ml'] == 375 ? 'selected' : '' ?>>Demi (375ml)</option>
                    <option value="3000" <?= $form['bottle_size_ml'] == 3000 ? 'selected' : '' ?>>Jeroboam (3000ml)
                    </option>
                </select>
                <span class="help-chip has-tooltip" aria-describedby="tooltip-bottle-size">
                    Other sizes / その他のサイズについて
                    <span class="tooltip-panel" id="tooltip-bottle-size">
                        If your bottle size is not listed (e.g. Mathusalem, Salmanazar),
                        leave this as “Bottle (750ml)” and write the actual size
                        in the memo field (e.g. “Mathusalem 6000ml”).<br><br>
                        選択肢にないサイズ（例：マチュザレム、サルマナザールなど）の場合は、
                        「Bottle (750ml)」のままにして、メモ欄に
                        「Mathusalem 6000ml」など実際の容量をご記入ください。
                    </span>
                </span>
                <!-- Custom size input could be added if strongly needed, keeping it simple for now as per instructions to unify -->
            </div>
        </div>
    </div>

    <!-- SECTION B: Origin -->
    <div class="form-section">
        <h3>Origin / 産地</h3>

        <div class="form-group">
            <label>Country / 国</label>
            <input type="text" name="country" value="<?= h($form['country']) ?>" placeholder="e.g. France">
        </div>

        <div class="form-group">
            <label>Region / 地域</label>
            <input type="text" name="region" value="<?= h($form['region']) ?>" placeholder="e.g. Bourgogne">
        </div>

        <div class="form-group">
            <label>Appellation / アペラシオン</label>
            <input type="text" name="appellation" value="<?= h($form['appellation']) ?>" placeholder="e.g. Meursault">
        </div>
    </div>

    <!-- SECTION C: Context -->
    <div class="form-section">
        <h3>Context / コンテキスト</h3>

        <div class="form-group">
            <label>
                Wine Type / ワインタイプ
                <span class="info-icon has-tooltip" aria-describedby="tooltip-wine-type">i
                    <span class="tooltip-panel" id="tooltip-wine-type">
                        Select the single style that best matches this bottle.
                        Use “Other” for styles not listed (e.g. orange wine, vin jaune, fortified wines)
                        and describe the style in the memo field.<br><br>
                        このボトルに最も近いスタイルを1つ選んでください。
                        オレンジワイン、ヴァン・ジョーヌ、酒精強化ワインなど
                        選択肢にないスタイルは「Other / その他」を選び、
                        メモ欄に詳細を書いてください。
                    </span>
                </span>
            </label>
            <div class="radio-row">
                <?php
                $colors = [
                    'sparkling' => 'Sparkling / スパークリング',
                    'white' => 'White / 白',
                    'rose' => 'Rosé / ロゼ',
                    'red' => 'Red / 赤',
                    'sweet' => 'Sweet / 甘口',
                    'other' => 'Other / その他'
                ];
                foreach ($colors as $k => $label) {
                    $checked = ($form['color'] === $k) ? 'checked' : '';
                    echo "<label><input type=\"radio\" name=\"color\" value=\"$k\" $checked> $label</label>";
                }
                ?>
            </div>
        </div>

        <div class="form-group">
            <p class="blind-note" style="margin-top:0;">
                Blind Mode / ブラインド設定：<br>
                下のチェックボックスにチェックを入れると、ゲストビューではこのボトルの詳細が伏せられます。<br>
                Check the box below to serve this bottle blind. Bottle details will be hidden in the guest view.
            </p>
            <label class="blind-inline" style="font-size:1rem;">
                <input type="checkbox" name="is_blind" value="1" <?= $form['is_blind'] ? 'checked' : '' ?>>
                Serve this bottle blind / このボトルをブラインドで出す
            </label>
        </div>



        <div class="form-group">
            <label>
                Price Band / 価格帯
                <span class="info-icon has-tooltip" aria-describedby="tooltip-price-band">i
                    <span class="tooltip-panel" id="tooltip-price-band">
                        These are rough retail price bands (before tax):
                        Casual ≈ $40 / Bistro $40–70 / Fine $70–150 / Luxury $150–350 / Icon $350+.<br><br>
                        店頭参考価格の目安です（税抜イメージ）：
                        Casual ～¥5,000、Bistro ¥5,000–10,000、Fine ¥10,000–20,000、
                        Luxury ¥20,000–50,000、Icon ¥50,000～。
                    </span>
                </span>
            </label>
            <div class="radio-row">
                <?php
                $bands = ['casual' => 'Casual', 'bistro' => 'Bistro', 'fine' => 'Fine', 'luxury' => 'Luxury', 'icon' => 'Icon'];
                foreach ($bands as $k => $v) {
                    $checked = ($form['price_band'] === $k) ? 'checked' : '';
                    echo "<label><input type=\"radio\" name=\"price_band\" value=\"$k\" $checked> $v</label>";
                }
                ?>
            </div>
        </div>

        <div class="form-group">
            <label>
                Theme Fit / テーマ適合度 (1–5)
                <span class="info-icon has-tooltip" aria-describedby="tooltip-theme-fit">i
                    <span class="tooltip-panel" id="tooltip-theme-fit">
                        Rate how well this bottle matches the event theme (1–5).
                        1 = mostly off-theme, 3 = reasonably on theme, 5 = perfect match.<br><br>
                        このボトルがイベントのテーマにどれくらい合っているかを
                        1〜5で自己評価してください。
                        1＝ほぼテーマ外、3＝そこそこ合っている、5＝ど真ん中の1本というイメージです。
                    </span>
                </span>
            </label>
            <div class="radio-row">
                <?php for ($i = 5; $i >= 0; $i--): ?>
                    <label>
                        <input type="radio" name="theme_fit_score" value="<?= $i ?>" <?= $form['theme_fit_score'] == $i ? 'checked' : '' ?>>
                        <?= $i ?>
                    </label>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- SECTION D: Memo -->
    <div class="form-section">
        <h3>Memo / メモ</h3>
        <label>Memo / メモ</label>
        <textarea name="memo" rows="4" placeholder="Notes..."><?= h($form['memo']) ?></textarea>
    </div>

    <div class="form-actions" style="margin-top:20px; display:flex; gap:12px; flex-wrap:wrap;">
        <button type="submit"
            class="button btn-primary"><?= $mode === 'edit' ? 'Save / 保存' : 'Add Bottle / ボトルを追加' ?></button>
        <a href="<?= h($returnUrl) ?>" class="button"
            style="background-color: transparent; border: 1px solid var(--accent); color: var(--accent);">
            Cancel / キャンセル
        </a>
    </div>

</form>