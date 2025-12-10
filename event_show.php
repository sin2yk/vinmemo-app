<?php
// event_show.php : Event Details and Bottle List
// Refactored to match BYO design concepts

require_once 'db_connect.php';
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

// 4. Determine Role
// layout/header.php includes helpers.php, but we need it now for logic.
require_once 'helpers.php';
$currentUserId = $_SESSION['user_id'] ?? null;
$eventRole = ($id && $currentUserId) ? getEventRole($pdo, $id, $currentUserId) : 'guest';

// --- Debug / Temporary: 手動ビュー切り替え ---
if (isset($_GET['view']) && in_array($_GET['view'], ['organizer', 'guest'], true)) {
    $eventRole = $_GET['view'];
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
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h1 style="margin:0;"><?= h($event['title']) ?></h1>
                <p style="margin:5px 0 0 0; color:var(--text-muted);">
                    <?= h($event['event_date']) ?> @ <?= h($event['place']) ?>

                    <?php if ($eventRole === 'organizer'): ?>
                        <span style="color:var(--accent); margin-left:10px;">[幹事ビュー]</span>
                        <a href="event_show.php?id=<?= h($id) ?>&view=guest" style="margin-left:10px; font-size:0.8em;">
                            ゲストビューで見る
                        </a>
                    <?php else: ?>
                        <span style="margin-left:10px;">[ゲストビュー]</span>
                        <a href="event_show.php?id=<?= h($id) ?>&view=organizer" style="margin-left:10px; font-size:0.8em;">
                            幹事ビューで見る
                        </a>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <a href="events.php" class="button" style="background:#555; font-size:0.9rem;">Back</a>
            </div>
        </div>

        <?php if ($event['memo']): ?>
            <div style="margin-top:15px; padding:15px; background:rgba(255,255,255,0.05); border-radius:8px;">
                <?= nl2br(h($event['memo'])) ?>
            </div>
        <?php endif; ?>
    </header>

    <!-- Summary Panel -->
    <section class="card" style="padding:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid var(--border); padding-bottom:10px;">
            Summary
            <?php if ($eventRole === 'organizer'): ?>
                <span style="font-size:0.8em; color:var(--accent); margin-left:10px;">(Organizer View)</span>
            <?php endif; ?>
        </h3>
        <div style="display:flex; flex-wrap:wrap; gap:20px;">
            <div>
                <strong>Total Bottles:</strong> <?= $stats['total'] ?>
            </div>
            <div>
                <strong>Avg Theme Fit:</strong> <?= $stats['theme_fit_avg'] ?>
            </div>
            <div>
                <strong>Breakdown:</strong>
                <?php foreach ($stats['types'] as $type => $count): ?>
                    <span style="margin-right:10px;"><?= ucfirst(h($type)) ?>: <?= $count ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Organizer Only: Visibility Controls -->
    <?php if ($eventRole === 'organizer'): ?>
        <?php
        $isRevealed = isEventRevealed($event);
        $listConfig = json_decode($event['list_field_visibility'] ?? '[]', true);
        // Default check logic: keys missing = true (visible), or logic in helpers relies on existing keys
        // For the form, we need to know if it's CHECKED.
        // Helper logic: if ($cfg[$key] === false) -> hidden. So if unset, it is visible.
        function isChecked($conf, $key)
        {
            return !isset($conf[$key]) || $conf[$key] !== false;
        }
        ?>
        <section class="card" style="padding:20px; border:1px solid var(--accent); background:rgba(255,167,38,0.1);">
            <h3 style="margin-top:0; color:var(--accent);">Organizer Controls</h3>

            <div style="display:flex; flex-wrap:wrap; gap:30px;">

                <!-- 1. Reveal Control -->
                <div style="flex:1; min-width:300px;">
                    <h4>Blind Reveal Status</h4>
                    <p>
                        Status:
                        <?php if ($isRevealed): ?>
                            <strong style="color:#4caf50;">REVEALED (Open)</strong>
                        <?php else: ?>
                            <strong style="color:#ff9800;">BLIND</strong>
                        <?php endif; ?>
                    </p>
                    <?php if (!$isRevealed): ?>
                        <form method="post" action="event_update_visibility.php"
                            onsubmit="return confirm('Reveal ALL bottles to guests?');">
                            <input type="hidden" name="event_id" value="<?= h($id) ?>">

                            <?php if (isset($_GET['view']) && $_GET['view'] === 'organizer'): ?>
                                <input type="hidden" name="debug_bypass_role" value="organizer">
                            <?php endif; ?>

                            <button type="submit" name="action" value="reveal_all" class="button"
                                style="background:#ff9800; color:black;">
                                ⚡ Reveal All / 答え合わせ
                            </button>
                        </form>
                    <?php else: ?>
                        <p style="font-size:0.9em; color:#aaa;">Event is fully revealed.</p>
                    <?php endif; ?>
                </div>

                <!-- 2. List Restriction Control -->
                <div style="flex:1; min-width:300px;">
                    <h4>Guest List Display Rules</h4>
                    <p style="font-size:0.85em; color:#ccc;">Uncheck fields to HIDE them from the guest list (even in Full
                        mode).</p>
                    <form method="post" action="event_update_visibility.php">
                        <input type="hidden" name="event_id" value="<?= h($id) ?>">
                        <input type="hidden" name="action" value="update_list_constraints">

                        <?php if (isset($_GET['view']) && $_GET['view'] === 'organizer'): ?>
                            <input type="hidden" name="debug_bypass_role" value="organizer">
                        <?php endif; ?>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <label><input type="checkbox" name="field_price_band" <?= isChecked($listConfig, 'price_band') ? 'checked' : '' ?>> Price Band</label>
                            <label><input type="checkbox" name="field_memo" <?= isChecked($listConfig, 'memo') ? 'checked' : '' ?>> Memo</label>
                        </div>
                        <div style="margin-top:10px;">
                            <button type="submit" class="button" style="font-size:0.8rem; background:#555;">Update
                                Rules</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    <?php endif; ?>


    <!-- Actions -->
    <div style="margin:20px 0; text-align:right;">
        <a href="bottle_new.php?event_id=<?= $id ?>" class="button">＋ Add My Bottle</a>
    </div>

    <!-- Bottle List -->
    <section>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:15px;">
            <h2 style="margin:0;">Bottle List</h2>

            <!-- Guest View Density Toggle -->
            <?php if ($eventRole === 'guest'): ?>
                <?php $viewMode = $_GET['mode'] ?? 'standard'; // simple, standard, full ?>
                <div class="view-mode-toggle" style="font-size:0.85rem;">
                    View:
                    <a href="?id=<?= $id ?>&view=guest&mode=simple"
                        style="<?= $viewMode === 'simple' ? 'font-weight:bold; color:var(--accent);' : 'color:#888;' ?>">Simple</a>
                    |
                    <a href="?id=<?= $id ?>&view=guest&mode=standard"
                        style="<?= $viewMode === 'standard' ? 'font-weight:bold; color:var(--accent);' : 'color:#888;' ?>">Standard</a>
                    |
                    <a href="?id=<?= $id ?>&view=guest&mode=full"
                        style="<?= $viewMode === 'full' ? 'font-weight:bold; color:var(--accent);' : 'color:#888;' ?>">Full</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($stats['total'] === 0): ?>
            <p>No bottles registered yet.</p>
        <?php else: ?>
            <div class="bottle-list-container">
                <?php foreach ($bottles as $index => $b): ?>
                    <?php
                    // --- 1. Get Visible Data ---
                    // use helper function
                    $visible = getVisibleFields($b, $event, $eventRole);
                    $displayName = getBottleDisplayName($visible, $b, $index);

                    // --- 2. Determine Display Mode ---
                    // If guest, use requested mode. If organizer, force 'full' (or custom organizer view).
                    $currentMode = ($eventRole === 'organizer') ? 'organizer_full' : ($viewMode ?? 'standard');

                    // --- 3. Construct Lines based on Mode ---
        
                    // Line 1: Label (#1 Owner)
                    // If owner is hidden, just show #1
                    $ownerStr = $visible['owner_label'] ? (' ' . h($visible['owner_label'])) : '';
                    $line1 = '#' . ($index + 1) . $ownerStr;

                    // Line 2 & 3: Main Bottle Info
                    // If it's a "Blind Bottle" title, we might style it differently
                    $mainTitle = h($displayName);

                    // Detailed Rendering Logic
                    // We'll build HTML parts based on $visible data directly, rather than old $line2/$line3 vars.
        
                    ?>

                    <div class="bottle-card" style="margin-bottom:20px; padding:14px 16px; border-radius:12px;
                                background:rgba(0,0,0,0.2); border-left:3px solid var(--accent); position:relative;">

                        <!-- Header Line -->
                        <div class="line-1-label"
                            style="font-size:0.9em; color:var(--text-muted); display:flex; justify-content:space-between;">
                            <span><?= $line1 ?></span>
                            <!-- Organizer: Blind Status Badge -->
                            <?php if ($eventRole === 'organizer' && $b['is_blind']): ?>
                                <span style="font-size:0.8em; color:var(--accent-gold);">
                                    BLIND (Level: <?= h($b['blind_reveal_level']) ?>)
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Main Title (Producer / Wine Name or Blind Label) -->
                        <div class="line-main" style="font-size:1.2em; font-weight:600; margin-top:4px; color:var(--text-main);">
                            <?= $mainTitle ?>
                        </div>

                        <?php if ($currentMode === 'simple'): ?>
                            <!-- SIMPLE MODE: Just Vintage if visible (and not already in title) -->
                            <!-- mostly done in title, maybe just color/size -->
                        <?php else: ?>
                            <!-- STANDARD / FULL / ORGANIZER -->

                            <!-- Specs: Color, Size -->
                            <div class="line-specs"
                                style="font-size:0.9rem; color:#ccc; display:flex; align-items:center; flex-wrap:wrap; gap:6px;">
                                <!-- Color Badge -->
                                <?php if ($visible['color']): ?>
                                    <?php
                                    $cCode = $visible['color'];
                                    $cLabel = getColorLabel($cCode);
                                    ?>
                                    <span class="wine-color-pill wine-color-<?= h($cCode) ?>">
                                        <?= h($cLabel) ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Size -->
                                <?php if ($visible['size'] != 750): ?>
                                    <span><?= h(getBottleSizeLabel($visible['size'])) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Origin: Country / Region / Appellation -->
                            <?php
                            $orgs = array_filter([$visible['country'], $visible['region'], $visible['appellation']]);
                            if (!empty($orgs)):
                                ?>
                                <div class="line-origin" style="font-size:0.9rem; color:var(--text-muted);">
                                    <?= implode(' / ', array_map('h', $orgs)) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Meta: Price, Theme Fit -->
                            <?php
                            $metas = [];
                            if ($visible['price_band'])
                                $metas[] = 'Price: ' . getPriceBandLabel($visible['price_band']);
                            if ($visible['theme_fit'])
                                $metas[] = 'Fit: ' . $visible['theme_fit'];
                            if (!empty($metas)):
                                ?>
                                <div class="line-meta" style="font-size:0.85rem; color:var(--text-muted);">
                                    <?= implode(' · ', array_map('h', $metas)) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Memo: Only in FULL or ORGANIZER -->
                            <?php if (($currentMode === 'full' || $currentMode === 'organizer_full') && !empty($visible['memo'])): ?>
                                <div class="line-memo"
                                    style="margin-top:8px; font-size:0.9em; padding-top:4px; border-top:1px dashed #555; color:#ccc;">
                                    <?= nl2br(h($visible['memo'])) ?>
                                </div>
                            <?php endif; ?>

                        <?php endif; // End Standard/Full ?>

                        <!-- Organizer Actions -->
                        <?php if ($eventRole === 'organizer'): ?>
                            <div class="bottle-actions"
                                style="margin-top:12px; padding-top:8px; border-top:1px solid rgba(255,255,255,0.1);">

                                <!-- Blind Level Control -->
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

                                <!-- Edit/Delete -->

                                <div style="text-align:right; display:flex; justify-content:flex-end; gap:8px;">
                                    <a href="bottle_edit.php?id=<?= h($b['id']) ?>" class="button btn-edit"
                                        style="font-size:0.8rem; padding:4px 10px;">Edit</a>

                                    <form method="post" action="bottle_delete.php"
                                        onsubmit="return confirm('Are you sure you want to delete this bottle?');">
                                        <input type="hidden" name="id" value="<?= h($b['id']) ?>">
                                        <input type="hidden" name="event_id" value="<?= h($id) ?>">
                                        <?php if (isset($_GET['view']) && $_GET['view'] === 'organizer'): ?>
                                            <input type="hidden" name="debug_bypass_role" value="organizer">
                                        <?php endif; ?>
                                        <button type="submit" class="button btn-danger"
                                            style="font-size:0.8rem; padding:4px 10px;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                    <!-- End Bottle Card -->
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>


<?php endif; ?>

<?php require_once 'layout/footer.php'; ?>