<?php
// event_show.php : Event Details and Bottle List
// Refactored to match BYO design concepts

require_once 'db_connect.php';
require_once 'helpers.php';
// session_start() will be handled in layout/header.php if not already, 
// but we might need session values for logic before header.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error = null;
$event = null;
$bottles = [];
$stats = [
    'total' => 0,
    'theme_fit_avg' => 0,
    'types' => [],
    'prices' => []
];

if (!$id) {
    $error = 'Invalid Event ID.';
} else {
    // 1. Get Event
    $sql = 'SELECT * FROM events WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $error = 'Event not found.';
    } else {
        // 2. Get Bottles
        $sql = 'SELECT * FROM bottle_entries 
                WHERE event_id = :event_id
                ORDER BY created_at ASC'; // BYO uses created_at/ID order usually
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':event_id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $bottles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Stats Calculation
        $stats['total'] = count($bottles);
        $sumFit = 0;
        $countFit = 0;
        foreach ($bottles as $b) {
            // Type Count
            $type = $b['color'] ?: 'Unknown';
            if (!isset($stats['types'][$type]))
                $stats['types'][$type] = 0;
            $stats['types'][$type]++;

            // Theme Fit (if set)
            if ($b['theme_fit_score']) {
                $sumFit += $b['theme_fit_score'];
                $countFit++;
            }
        }
        if ($countFit > 0) {
            $stats['theme_fit_avg'] = round($sumFit / $countFit, 2);
        }
    }
}

// 4. Determine View Mode (Strict)
$isLoggedIn = isset($_SESSION['user_id']);
$isOwner = $isLoggedIn
    && isset($event['organizer_user_id'])
    && ($_SESSION['user_id'] == $event['organizer_user_id']);

$view = 'guest'; // Default to guest view

// Review: Only switch to organizer view if owner requests it explicitly
if ($isOwner && isset($_GET['view']) && $_GET['view'] === 'organizer') {
    $view = 'organizer';
}

// Page Setup
$page_title = $event ? 'VinMemo - ' . $event['title'] : 'VinMemo - Error';
require_once 'layout/header.php';
?>

<?php if ($error): ?>
    <div class="error-msg"><?= h($error) ?></div>
    <p><a href="events.php">Back to List</a></p>
<?php else: ?>

    <header style="margin-bottom:20px;">
        <!-- 2. View Toggle -->
        <div class="event-view-toggle">
            <?php if ($view === 'guest'): ?>
                <div class="view-toggle-item">
                    <span>View as Guest / ゲストビューで見る</span>
                </div>
                <?php if ($isOwner): ?>
                    <div class="view-toggle-item">
                        <a href="event_show.php?id=<?= h($id) ?>&view=organizer">[Organizer View / 幹事ビュー]</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="view-toggle-item">
                    <a href="event_show.php?id=<?= h($id) ?>">[Guest View / ゲストビュー]</a>
                </div>
                <div class="view-toggle-item">
                    <span>Organizer View / 幹事ビューで見る</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Actions -->
        <div class="event-header-controls">
            <?php if ($view === 'organizer'): ?>
                <div class="event-header-actions">
                    <a href="event_edit.php?id=<?= h($id) ?>" class="vm-btn vm-btn--primary">
                        Edit Event / イベントを編集
                    </a>
                    <a href="events.php" class="vm-btn vm-btn--secondary">
                        Back to Event List / イベント一覧に戻る
                    </a>
                </div>
            <?php elseif ($isLoggedIn): ?>
                <div class="event-header-actions event-header-actions--single">
                    <a href="events.php" class="vm-btn vm-btn--secondary">
                        Back to Event List / イベント一覧に戻る
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php
        $parsedMemo = parseEventMemo($event['memo']);
        $m = $parsedMemo['meta'] ?? [];
        ?>

        <!-- 4. Title -->
        <h1 class="event-title"><?= h($event['title']) ?></h1>

        <!-- 4b. Subtitle -->
        <?php if (!empty($m['subtitle'])): ?>
            <p class="event-subtitle"
                style="font-size:1.1rem; color:var(--text-muted); margin-top:-0.5rem; margin-bottom:0.8rem;">
                <?= h($m['subtitle']) ?>
            </p>
        <?php endif; ?>

        <!-- 5. Meta -->
        <p class="event-meta" style="color:var(--text-muted);">
            <?= h($event['event_date']) ?> @ <?= h($event['place']) ?>
        </p>
    </header>

    <!-- 6. Wine List (With Add Button) -->
    <!-- Add My Wine Button (Implicitly needed here) -->
    <div style="margin:20px 0; text-align:right;">
        <?php
        $allow_byo = true;
        if (isset($parsedMemo['meta']['event_style_detail']) && $parsedMemo['meta']['event_style_detail'] === 'no_byo') {
            $allow_byo = false;
        }
        if ($allow_byo):
            ?>
            <a href="bottle_new.php?event_id=<?= $id ?>" class="btn-pill btn-primary">＋ Add My Wine / 自分のワインを登録</a>
        <?php endif; ?>
    </div>

    <section>
        <div class="section-header section-header--with-view">
            <h2 class="section-title">
                Wine List / ワインリスト
            </h2>
            <div class="section-view-switch">
                <?php if ($view === 'guest'): ?>
                    <?php $viewMode = $_GET['mode'] ?? 'standard'; ?>
                    View:
                    <a href="?id=<?= $id ?>&view=guest&mode=simple"
                        style="<?= $viewMode === 'simple' ? 'font-weight:bold; color:var(--accent);' : 'color:#888;' ?>">Simple
                        / 簡易</a>
                    |
                    <a href="?id=<?= $id ?>&view=guest&mode=standard"
                        style="<?= $viewMode === 'standard' ? 'font-weight:bold; color:var(--accent);' : 'color:#888;' ?>">Standard
                        / 標準</a>
                    |
                    <a href="?id=<?= $id ?>&view=guest&mode=full"
                        style="<?= $viewMode === 'full' ? 'font-weight:bold; color:var(--accent);' : 'color:#888;' ?>">Full /
                        詳細</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($stats['total'] === 0): ?>
            <p>No wines registered yet.</p>
        <?php else: ?>
            <div class="bottle-list-container">
                <?php foreach ($bottles as $index => $b): ?>
                    <?php
                    $visible = getVisibleFields($b, $event, $view);
                    $displayName = getBottleDisplayName($visible, $b, $index);
                    $currentMode = ($view === 'organizer') ? 'organizer_full' : ($viewMode ?? 'standard');
                    $ownerStr = $visible['owner_label'] ? (' ' . h($visible['owner_label'])) : '';
                    $line1 = '#' . ($index + 1) . $ownerStr;
                    $mainTitle = h($displayName);
                    ?>

                    <div class="bottle-card bottle-card--<?= h($visible['color'] ?? 'red') ?>" style="margin-bottom:20px; padding:14px 16px; border-radius:12px;
                                background:rgba(0,0,0,0.2); position:relative;">
                        <div class="line-1-label"
                            style="font-size:0.9em; color:var(--text-muted); display:flex; justify-content:space-between;">
                            <span><?= $line1 ?></span>
                            <?php if ($view === 'organizer' && $b['is_blind']): ?>
                                <span style="font-size:0.8em; color:var(--accent-gold);">
                                    BLIND (Level: <?= h($b['blind_reveal_level']) ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="line-main" style="font-size:1.2em; font-weight:600; margin-top:4px; color:var(--text-main);">
                            <?= $mainTitle ?>
                        </div>
                        <?php if ($currentMode !== 'simple'): ?>
                            <div class="line-specs"
                                style="font-size:0.9rem; color:#ccc; display:flex; align-items:center; flex-wrap:wrap; gap:6px;">
                                <?php if ($visible['color']): ?>
                                    <?php
                                    $cCode = $visible['color'];
                                    $cLabel = getColorLabel($cCode);
                                    ?>
                                    <span class="wine-color-pill wine-color-<?= h($cCode) ?>">
                                        <?= h($cLabel) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($visible['size'] != 750): ?>
                                    <span><?= h(getBottleSizeLabel($visible['size'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $orgs = array_filter([$visible['country'], $visible['region'], $visible['appellation']]);
                            if (!empty($orgs)):
                                ?>
                                <div class="line-origin" style="font-size:0.9rem; color:var(--text-muted);">
                                    <?= implode(' / ', array_map('h', $orgs)) ?>
                                </div>
                            <?php endif; ?>
                            <?php
                            $metas = [];
                            if ($visible['price_band'])
                                $metas[] = '価格帯 / Price Band: ' . getPriceBandLabel($visible['price_band']);
                            if ($visible['theme_fit'] && !empty($event['show_theme_fit']))
                                $metas[] = 'Theme Fit / テーマ適合度: ' . $visible['theme_fit'];
                            if (!empty($metas)):
                                ?>
                                <div class="line-meta" style="font-size:0.85rem; color:var(--text-muted);">
                                    <?= implode(' · ', array_map('h', $metas)) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (($currentMode === 'full' || $currentMode === 'organizer_full') && !empty($visible['memo'])): ?>
                                <div class="line-memo"
                                    style="margin-top:8px; font-size:0.9em; padding-top:4px; border-top:1px dashed #555; color:#ccc;">
                                    <?= nl2br(h($visible['memo'])) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($view === 'organizer'): ?>
                            <div class="bottle-actions"
                                style="margin-top:12px; padding-top:8px; border-top:1px solid rgba(255,255,255,0.1);">
                                <?php if ($b['is_blind']): ?>
                                    <form method="post" action="bottle_update_blind_level.php"
                                        style="margin-bottom:10px; display:flex; align-items:center; gap:10px;">
                                        <input type="hidden" name="event_id" value="<?= h($id) ?>">
                                        <input type="hidden" name="bottle_id" value="<?= h($b['id']) ?>">
                                        <?php if (isset($_GET['view']) && $_GET['view'] === 'organizer'): ?>
                                            <input type="hidden" name="debug_bypass_role" value="organizer">
                                        <?php endif; ?>
                                        <label style="font-size:0.8rem; color:var(--accent-gold);">Reveal Level:</label>
                                        <select name="blind_reveal_level" onchange="this.form.submit()"
                                            style="padding:2px; font-size:0.8rem;">
                                            <option value="none" <?= $b['blind_reveal_level'] === 'none' ? 'selected' : '' ?>>None (Blind)
                                            </option>
                                            <option value="country" <?= $b['blind_reveal_level'] === 'country' ? 'selected' : '' ?>>+Country
                                            </option>
                                            <option value="country_vintage" <?= $b['blind_reveal_level'] === 'country_vintage' ? 'selected' : '' ?>>+Country/Vint</option>
                                            <option value="full" <?= $b['blind_reveal_level'] === 'full' ? 'selected' : '' ?>>Full Reveal
                                            </option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                                <div style="text-align:right; display:flex; justify-content:flex-end; gap:8px;">
                                    <a href="bottle_edit.php?id=<?= h($b['id']) ?>" class="bottle-action-link">
                                        Edit
                                    </a>
                                    <form method="post" action="bottle_delete.php"
                                        onsubmit="return confirm('Are you sure you want to delete this bottle?');">
                                        <input type="hidden" name="id" value="<?= h($b['id']) ?>">
                                        <input type="hidden" name="event_id" value="<?= h($id) ?>">
                                        <?php if (isset($_GET['view']) && $_GET['view'] === 'organizer'): ?>
                                            <input type="hidden" name="debug_bypass_role" value="organizer">
                                        <?php endif; ?>
                                        <button type="submit" class="bottle-action-link bottle-action-link--danger">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- 7. Theme (Standalone Paragraph) -->
    <?php if (!empty($m['theme_description'])): ?>
        <p class="event-theme" style="margin-top:20px;">
            Theme / テーマ: <?= nl2br(h($m['theme_description'])) ?>
        </p>
    <?php endif; ?>

    <!-- 8. Summary Panel -->
    <section class="card" style="padding:20px; margin-top:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid var(--border); padding-bottom:10px;">
            Summary / サマリー
            <?php if ($view === 'organizer'): ?>
                <span style="font-size:0.8em; color:var(--accent); margin-left:10px;">(Organizer View)</span>
            <?php endif; ?>
        </h3>
        <div style="display:flex; flex-wrap:wrap; gap:20px;">
            <div>
                <strong>Total Bottles / 登録ボトル数:</strong> <?= $stats['total'] ?>
            </div>
            <?php if (!empty($event['show_theme_fit'])): ?>
                <div>
                    <strong>Avg Theme Fit / 平均テーマ適合度:</strong> <?= $stats['theme_fit_avg'] ?>
                </div>
            <?php endif; ?>
            <div>
                <strong>Breakdown / 内訳:</strong>
                <?php foreach ($stats['types'] as $type => $count): ?>
                    <span style="margin-right:10px;"><?= ucfirst(h($type)) ?>: <?= $count ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 9 (Extra). Organizer Note & Details (Below Summary) -->
    <?php if ($parsedMemo['note']): ?>
        <div style="margin-top:15px; padding:15px; background:rgba(255,255,255,0.05); border-radius:8px;">
            <?= nl2br(h($parsedMemo['note'])) ?>
        </div>
    <?php endif; ?>

    <?php if (
        !empty($m) && (
            !empty($m['event_style_detail']) ||
            !empty($m['blind_policy']) ||
            !empty($m['bottle_rules'])
        )
    ): ?>
        <div class="card" style="margin-top:20px; padding:20px; border-left:4px solid var(--accent);">
            <h4
                style="margin-top:0; color:var(--text-main); border-bottom:1px solid #444; padding-bottom:10px; margin-bottom:15px;">
                Event Details / イベント詳細情報
            </h4>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <?php if (!empty($m['event_style_detail'])): ?>
                        <div style="margin-bottom:15px;">
                            <div style="font-size:0.85rem; color:var(--text-muted);">Style / スタイル</div>
                            <div style="font-size:1.1rem; font-weight:bold;">
                                <?= h(getEventStyleLabel($m['event_style_detail'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($m['blind_policy'])): ?>
                        <div style="margin-bottom:15px;">
                            <div style="font-size:0.85rem; color:var(--text-muted);">Blind Policy / ブラインド</div>
                            <div><?= h(getBlindPolicyLabel($m['blind_policy'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!empty($m['bottle_rules'])): ?>
                        <div style="margin-bottom:15px;">
                            <div style="font-size:0.85rem; color:var(--text-muted);">Bottle Rules / 持ち寄りルール</div>
                            <div style="background:rgba(0,0,0,0.2); padding:10px; border-radius:4px;">
                                <?= nl2br(h($m['bottle_rules'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Control Panel -->
    <?php if ($view === 'organizer'): ?>
        <?php
        $isRevealed = isEventRevealed($event);
        $listConfig = json_decode($event['list_field_visibility'] ?? '[]', true);
        function isChecked($conf, $key)
        {
            return !isset($conf[$key]) || $conf[$key] !== false;
        }
        ?>
        <section class="card"
            style="padding:20px; border:1px solid var(--accent); background:rgba(255,167,38,0.1); margin-top:20px;">
            <h3 style="margin-top:0; color:var(--accent);">Organizer Controls</h3>
            <div style="display:flex; flex-wrap:wrap; gap:30px;">
                <div style="flex:1; min-width:300px;">
                    <h4>Blind Reveal Status / ブラインド状況</h4>
                    <p>Status: <?php if ($isRevealed): ?><strong style="color:#4caf50;">REVEALED
                                (Open)</strong><?php else: ?><strong style="color:#ff9800;">BLIND</strong><?php endif; ?></p>
                    <?php if (!$isRevealed): ?>
                        <form method="post" action="event_update_visibility.php"
                            onsubmit="return confirm('Reveal ALL bottles to guests?');">
                            <input type="hidden" name="event_id" value="<?= h($id) ?>">
                            <?php if (isset($_GET['view']) && $_GET['view'] === 'organizer'): ?><input type="hidden"
                                    name="debug_bypass_role" value="organizer"><?php endif; ?>
                            <button type="submit" name="action" value="reveal_all" class="button"
                                style="background:#ff9800; color:black;">⚡ Reveal All / 答え合わせ</button>
                        </form>
                    <?php else: ?>
                        <p style="font-size:0.9em; color:#aaa;">Event is fully revealed.</p>
                    <?php endif; ?>
                </div>
                <!-- List Rules Form (omitted for brevity, same as before) -->
                <div style="flex:1; min-width:300px;">
                    <h4>Guest List Display Rules / ゲスト表示ルール</h4>
                    <form method="post" action="event_update_visibility.php">
                        <input type="hidden" name="event_id" value="<?= h($id) ?>">
                        <input type="hidden" name="action" value="update_list_constraints">
                        <?php if (isset($_GET['view']) && $_GET['view'] === 'organizer'): ?><input type="hidden"
                                name="debug_bypass_role" value="organizer"><?php endif; ?>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <label><input type="checkbox" name="field_theme_fit" <?= !empty($event['show_theme_fit']) ? 'checked' : '' ?>> Theme Fit / テーマ適合度</label>
                            <label><input type="checkbox" name="field_price_band" <?= isChecked($listConfig, 'price_band') ? 'checked' : '' ?>> Price Band / 価格帯</label>
                            <label><input type="checkbox" name="field_memo" <?= isChecked($listConfig, 'memo') ? 'checked' : '' ?>> Memo / メモ</label>
                        </div>
                        <div style="margin-top:10px;">
                            <button type="submit" class="btn btn-primary">Update Rules</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    <?php endif; ?>


<?php endif; ?>

<?php require_once 'layout/footer.php'; ?>