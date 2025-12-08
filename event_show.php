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
            <?php foreach ($bottles as $index => $b): ?>
                <?php
                // Check permissions
                $isOwner = ($currentUserId && $b['brought_by_user_id'] == $currentUserId);
                $isAdmin = ($eventRole === 'organizer');
                $canEdit = ($isAdmin || $isOwner);

                // Blind Logic: Mask if blind AND (not admin AND not owner)
                $shouldMask = ($b['is_blind'] && !$isAdmin && !$isOwner);
                ?>
                <div class="bottle-card"
                    style="border-left: 5px solid <?= $b['color'] === 'white' ? '#eee' : ($b['color'] === 'sparkling' ? 'gold' : '#800020') ?>;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div style="font-size:0.9em; color:var(--text-muted); margin-bottom:5px;">
                                #<?= $index + 1 ?>
                                <span style="color:var(--accent); font-weight:bold; margin-left:5px;">
                                    <?= h($b['owner_label']) ?>
                                </span>
                            </div>

                            <div style="font-size:1.3rem; font-weight:bold;">
                                <?php if ($b['is_blind']): ?>
                                    <span
                                        style="background:var(--accent); color:#000; padding:2px 6px; border-radius:4px; font-size:0.6em; vertical-align:middle;">BLIND</span>
                                <?php endif; ?>
                                <?= mask_if_blind($b['wine_name'], $shouldMask) ?>
                            </div>

                            <div style="margin-top:5px; color:#ccc;">
                                <?= mask_if_blind($b['vintage'] ?: 'NV', $shouldMask, 'XXXX') ?> |
                                <?= mask_if_blind($b['producer_name'], $shouldMask) ?> |
                                <?= mask_if_blind($b['region'], $shouldMask) ?>
                            </div>
                        </div>

                        <?php if ($canEdit): ?>
                            <div style="min-width:120px; text-align:right;">
                                <a href="bottle_edit.php?id=<?= $b['id'] ?>" style="font-size:0.9em; margin-right:10px;">Edit</a>
                                <?php if ($isAdmin || $isOwner): // Redundant check but clear intent ?>
                                    <a href="bottle_delete.php?id=<?= $b['id'] ?>" onclick="return confirm('Delete this bottle?');"
                                        style="color:var(--danger); font-size:0.9em;">Delete</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Details Row -->
                    <div style="margin-top:10px; font-size:0.9em; color:#aaa; border-top:1px dashed #555; padding-top:10px;">
                        Price:
                        <?= mask_if_blind($b['est_price_yen'] ? '¥' . number_format($b['est_price_yen']) : '-', $shouldMask) ?>
                        | Theme Fit: <?= h($b['theme_fit_score'] ?: '-') ?>/5

                        <?php if ($b['memo']): ?>
                            <div style="margin-top:5px; color:#ddd;">
                                Memo: <br>
                                <?= mask_if_blind(nl2br($b['memo']), $shouldMask, 'Matches Blind Mode') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

<?php endif; ?>

<?php require_once 'layout/footer.php'; ?>