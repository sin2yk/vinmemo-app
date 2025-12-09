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

    <p class="blind-note">
        ※ Blind Mode: Check "Serve this bottle blind" below to mask details.
    </p>

    <!-- SECTION A: Core bottle identity -->
    <div class="form-section">
        <h3>基本情報 / Core Identity</h3>

        <div class="form-group">
            <label>お名前 / Your Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="owner_label" value="<?= h($form['owner_label']) ?>" required
                placeholder="Your Name">
        </div>

        <div class="form-group">
            <label>生産者 / Producer <span style="color:var(--danger)">*</span></label>
            <input type="text" name="producer_name" value="<?= h($form['producer_name']) ?>" required
                placeholder="e.g. Domaine Leflaive">
        </div>

        <div class="form-group">
            <label>ワイン名 / Wine Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="wine_name" value="<?= h($form['wine_name']) ?>" required
                placeholder="e.g. Puligny-Montrachet">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>ヴィンテージ / Vintage</label>
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
                <label>容量 / Bottle Size</label>
                <!-- Using simple select for consistency between new/edit -->
                <select name="bottle_size_ml">
                    <option value="750" <?= $form['bottle_size_ml'] == 750 ? 'selected' : '' ?>>Bottle (750ml)</option>
                    <option value="1500" <?= $form['bottle_size_ml'] == 1500 ? 'selected' : '' ?>>Magnum (1500ml)</option>
                    <option value="375" <?= $form['bottle_size_ml'] == 375 ? 'selected' : '' ?>>Demi (375ml)</option>
                    <option value="620" <?= $form['bottle_size_ml'] == 620 ? 'selected' : '' ?>>Clavelin (620ml)</option>
                    <option value="3000" <?= $form['bottle_size_ml'] == 3000 ? 'selected' : '' ?>>Jeroboam (3000ml)
                    </option>
                </select>
                <!-- Custom size input could be added if strongly needed, keeping it simple for now as per instructions to unify -->
            </div>
        </div>
    </div>

    <!-- SECTION B: Origin -->
    <div class="form-section">
        <h3>産地 / Origin</h3>

        <div class="form-group">
            <label>国 / Country</label>
            <input type="text" name="country" value="<?= h($form['country']) ?>" placeholder="e.g. France">
        </div>

        <div class="form-group">
            <label>地域 / Region</label>
            <input type="text" name="region" value="<?= h($form['region']) ?>" placeholder="e.g. Bourgogne">
        </div>

        <div class="form-group">
            <label>アペラシオン / Appellation</label>
            <input type="text" name="appellation" value="<?= h($form['appellation']) ?>" placeholder="e.g. Meursault">
        </div>
    </div>

    <!-- SECTION C: Context -->
    <div class="form-section">
        <h3>コンテキスト / Context</h3>

        <div class="form-group">
            <label>ワインタイプ / Wine Type</label>
            <div class="radio-row">
                <?php
                $colors = ['sparkling', 'white', 'orange', 'rose', 'red', 'sweet', 'fortified'];
                foreach ($colors as $c) {
                    $checked = ($form['color'] === $c) ? 'checked' : '';
                    echo "<label><input type=\"radio\" name=\"color\" value=\"$c\" $checked> " . ucfirst($c) . "</label>";
                }
                ?>
            </div>
        </div>

        <div class="form-group">
            <label class="blind-inline" style="font-size:1rem;">
                <input type="checkbox" name="is_blind" value="1" <?= $form['is_blind'] ? 'checked' : '' ?>>
                ブラインド設定 / Blind mode
            </label>
        </div>



        <div class="form-group">
            <label>価格帯 / Price Band</label>
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
            <label>テーマ適合度 / Theme fit</label>
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
        <h3>メモ / Memo</h3>
        <label>メモ / Memo</label>
        <textarea name="memo" rows="4" placeholder="Notes..."><?= h($form['memo']) ?></textarea>
    </div>

    <div class="form-actions" style="margin-top:20px; display:flex; gap:12px; flex-wrap:wrap;">
        <button type="submit"
            class="button btn-primary"><?= $mode === 'edit' ? '保存 / Save' : 'ボトルを追加 / Add Bottle' ?></button>
        <a href="<?= h($returnUrl) ?>" class="button"
            style="background-color: transparent; border: 1px solid var(--accent); color: var(--accent);">
            保存せずに戻る / Cancel
        </a>
    </div>

</form>