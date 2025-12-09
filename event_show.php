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

    <!-- Actions -->
    <div style="margin:20px 0; text-align:right;">
        <a href="bottle_new.php?event_id=<?= $id ?>" class="button">＋ Add My Bottle</a>
    </div>

    <!-- Bottle List -->
    <section>
        <h2>Bottle List</h2>

        <?php if ($stats['total'] === 0): ?>
            <p>No bottles registered yet.</p>
        <?php else: ?>
            <div class="bottle-list-container">
                <?php foreach ($bottles as $index => $b): ?>
                    <?php
                    // --- hide_from_list: ゲストビューでは非表示 ---
                    $hideFromList = !empty($b['hide_from_list']);
                    if ($eventRole !== 'organizer' && $hideFromList) {
                        continue;
                    }

                    // --- ブラインド判定（カラム名は安全に参照） ---
                    $isBlind = !empty($b['is_blind']) || !empty($b['blind_flag']);

                    // 基本情報
                    $no = $index + 1;
                    $owner = $b['owner_label'] ?? '';
                    $producer = $b['producer_name'] ?? '';
                    $wineName = $b['wine_name'] ?? '';
                    $vintage = $b['vintage'] ?? '';
                    $country = $b['country'] ?? '';
                    $region = $b['region'] ?? '';
                    $appellation = $b['appellation'] ?? '';
                    $color = $b['color'] ?? '';
                    $sizeCode = $b['bottle_size'] ?? null;
                    $themeFit = $b['theme_fit_score'] ?? null;
                    $priceBand = $b['price_band_label'] ?? ($b['price_band'] ?? '');
                    $memo = $b['memo'] ?? '';

                    // line1: 「#1 吉川」
                    $line1 = '#' . $no . ' ' . h($owner);

                    // ブラインド時の表示制御
                    if ($isBlind && $eventRole === 'guest') {
                        $line2 = 'Blind Bottle';
                        $line3 = '※ ブラインド表示中 / Hidden for blind tasting';
                        $origin = '';
                    } else {
                        $line2 = $producer;
                        $line3 = $wineName;
                        if ($vintage !== '') {
                            $line3 .= ' ' . h($vintage);
                        }
                        $originParts = array_filter([$country, $region, $appellation]);
                        $origin = implode(' / ', $originParts);
                    }

                    // line4: タイプ・容量など
                    $specParts = [];
                    if ($color !== '') {
                        $specParts[] = ucfirst($color);
                    }
                    if ($sizeCode) {
                        // helpers.php の getBottleSizeLabel() を想定
                        $specParts[] = getBottleSizeLabel($sizeCode);
                    }
                    $line4 = implode(' · ', $specParts);

                    // line5: 産地
                    $line5 = $origin;

                    // line6: 価格帯・テーマフィット
                    $metaParts = [];
                    if ($priceBand !== '') {
                        $metaParts[] = 'Price ' . h($priceBand);
                    }
                    if ($themeFit) {
                        $metaParts[] = 'Theme Fit ' . h($themeFit);
                    }
                    $line6 = implode(' · ', $metaParts);

                    // line7: メモ
                    $line7 = $memo;
                    ?>

                    <div class="bottle-card" style="margin-bottom:20px; padding:14px 16px; border-radius:12px;
                                background:rgba(0,0,0,0.2); border-left:3px solid var(--accent);">
                        <!-- line 1 -->
                        <div class="line-1-label" style="font-size:0.9em; color:var(--text-muted);">
                            <?= h($line1) ?>
                        </div>

                        <!-- line 2 -->
                        <?php if ($line2 !== ''): ?>
                            <div class="line-2-producer" style="font-weight:bold; color:var(--accent-gold); margin-top:4px;">
                                <?= h($line2) ?>
                            </div>
                        <?php endif; ?>

                        <!-- line 3 -->
                        <?php if ($line3 !== ''): ?>
                            <div class="line-3-wine" style="font-size:1.2em; font-weight:600;
                                        margin-top:4px; margin-bottom:2px; color:var(--text-main);">
                                <?= h($line3) ?>
                            </div>
                        <?php endif; ?>

                        <!-- line 4 -->
                        <?php if ($line4 !== ''): ?>
                            <div class="line-4-specs" style="margin-top:2px;">
                                <?= h($line4) ?>
                            </div>
                        <?php endif; ?>

                        <!-- line 5 -->
                        <?php if ($line5 !== ''): ?>
                            <div class="line-5-origin" style="color:var(--text-muted); font-size:0.9em;">
                                <?= h($line5) ?>
                            </div>
                        <?php endif; ?>

                        <!-- line 6 -->
                        <?php if ($line6 !== ''): ?>
                            <div class="line-6-meta" style="color:var(--text-muted); font-size:0.85em; margin-top:2px;">
                                <?= h($line6) ?>
                            </div>
                        <?php endif; ?>

                        <!-- line 7 -->
                        <?php if ($line7 !== ''): ?>
                            <div class="line-7-memo" style="margin-top:8px; font-size:0.9em; padding-top:4px;
                                        border-top:1px dashed #555; color:#ccc;">
                                <?= nl2br(h($line7)) ?>
                            </div>
                        <?php endif; ?>

                        <!-- 幹事だけ Edit / Delete / Toggle Visibility -->
                        <?php if ($eventRole === 'organizer'): ?>
                            <div class="bottle-actions"
                                style="margin-top:12px; padding-top:8px; border-top:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center;">

                                <!-- Toggle Visibility Form -->
                                <form method="post" action="bottle_toggle_visibility.php" style="margin:0;">
                                    <input type="hidden" name="bottle_id" value="<?= h($b['id']) ?>">
                                    <input type="hidden" name="event_id" value="<?= h($id) ?>">

                                    <?php if ($hideFromList): ?>
                                        <button type="submit" name="action" value="show" class="button"
                                            style="background-color:#4a90e2; color:white; font-size:0.8rem; padding:4px 8px;">
                                            ワインリストに表示する / Show
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="hide" class="button"
                                            style="background-color:rgba(255,255,255,0.1); color:#ccc; font-size:0.8rem; padding:4px 8px;">
                                            ワインリストに表示しない / Hide
                                        </button>
                                    <?php endif; ?>
                                </form>

                                <div>
                                    <a href="bottle_edit.php?id=<?= h($b['id']) ?>" class="button btn-edit"
                                        style="font-size:0.8rem; padding:4px 10px; margin-right:4px;">Edit</a>
                                    <a href="bottle_delete.php?id=<?= h($b['id']) ?>" class="button btn-danger"
                                        style="font-size:0.8rem; padding:4px 10px;" onclick="return confirm('Delete this bottle?');">
                                        Delete
                                    </a>
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