<?php
// event_public.php : Guest Event View (Public with ET)
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php';

$eventToken = $_GET['ET'] ?? null;

if (!$eventToken) {
    http_response_code(400);
    echo 'Invalid access (missing ET).';
    exit;
}

// $pdo is created in db_connect.php

// 1. Resolve Event
$sql = "SELECT * FROM events WHERE event_token = :event_token LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':event_token', $eventToken, PDO::PARAM_STR);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    echo 'Event not found or invalid token.';
    exit;
}

$eventId = (int) $event['id'];

// Parse Memo for Meta data
$parsedMemo = parseEventMemo($event['memo']);
$organizerNote = $parsedMemo['note']; // This is "Organizer Note" usually hidden or private?
// Wait, prompt said: "Event Summary... Organizer note (organizer_note, optional)" -> So we show it.
// Actually, usually "Organizer Note" in events_new.php was labeled "Secret Note (Not visible to guests)".
// BUT `event_show.php` (Organizer View) shows it.
// The PROMPT step 2-3 says: "Organizer note (organizer_note, optional)".
// If the label says "Secret Note", showing it to guests is WRONG.
// Let's check `events_new.php`: "Secret Note (Not visible to guests)".
// So I should probably NOT show `$organizerNote` to guests, OR arguably there might be a "Guest Note".
// However, the prompt list included "Organizer note".
// I will err on the side of PRIVACY for safety and NOT show the "Secret Note".
// If there was a public description, it's typically in "Theme description" or "Bottle rules".
// The "Secret Note" is usually for budget/admin stuff.
// I will SKIP `organizerNote` for guest view unless explicitly told otherwise, despite the prompt's ambiguous line.
// The prompt said "Replicate the summary part of event_show.php ... Organizer note (organizer_note, optional)".
// In `event_show.php`, checking code...
// L251: Shows "Organizer Note / 幹事メモ".
// Given `events_new.php` label says "Not visible to guests", I will HIDE it here to be safe.

$meta = $parsedMemo['meta'] ?? [];
$themeDesc = $meta['theme_description'] ?? '';
$bottleRules = $meta['bottle_rules'] ?? '';
$blindPolicy = $meta['blind_policy'] ?? 'none';
// $seats, $area are legacy/admin.

// 2. Fetch Bottles
$sql = "SELECT * FROM bottle_entries WHERE event_id = :event_id ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
$stmt->execute();
$bottles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include __DIR__ . '/partials/public_header.php'; ?>

<main class="container" style="padding: 2rem 1rem; max-width: 800px; margin: 0 auto;">

    <!-- Event Summary -->
    <section class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem; color: var(--primary);">
            <?= h($event['title']) ?>
        </h1>
        <?php if (!empty($meta['subtitle'])): ?>
            <p style="font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 1rem;">
                <?= h($meta['subtitle']) ?>
            </p>
        <?php endif; ?>

        <div style="display: grid; gap: 0.75rem; font-size: 0.95rem;">
            <div style="display: flex; gap: 1rem;">
                <span class="badg" style="background: var(--bg-main); color: var(--text-secondary);">Date</span>
                <strong><?= getEventDateDisplay($event) ?></strong>
            </div>

            <div style="display: flex; gap: 1rem;">
                <span class="badg" style="background: var(--bg-main); color: var(--text-secondary);">Place</span>
                <span>
                    <?= h($event['place']) ?>
                    <?php if (!empty($event['area_label'])): ?>
                        <span
                            style="color: var(--text-secondary); font-size: 0.9em;">(<?= h($event['area_label']) ?>)</span>
                    <?php endif; ?>
                </span>
            </div>

            <?php if (!empty($event['expected_guests'])): ?>
                <div style="display: flex; gap: 1rem;">
                    <span class="badg" style="background: var(--bg-main); color: var(--text-secondary);">Guests</span>
                    <span><?= h($event['expected_guests']) ?> people</span>
                </div>
            <?php endif; ?>
        </div>

        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px dashed var(--border-color);">

        <div style="display: grid; gap: 1.5rem;">
            <?php if ($themeDesc): ?>
                <div>
                    <h3 style="font-size: 1rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Theme</h3>
                    <p style="white-space: pre-wrap;"><?= h($themeDesc) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($bottleRules): ?>
                <div>
                    <h3 style="font-size: 1rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Rules</h3>
                    <p style="white-space: pre-wrap;"><?= h($bottleRules) ?></p>
                </div>
            <?php endif; ?>

            <div>
                <h3 style="font-size: 1rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Blind Policy</h3>
                <p><?= getBlindPolicyLabel($blindPolicy) ?></p>
            </div>
        </div>
    </section>

    <!-- Bottle List -->
    <section>
        <h2 style="font-size: 1.4rem; margin-bottom: 1rem;">Wine List</h2>

        <?php if (empty($bottles)): ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                No wines registered yet.
            </p>
        <?php else: ?>
            <div class="bottle-list">
                <?php foreach ($bottles as $index => $bottle): ?>
                    <?php
                    // Visibility Logic
                    $visibleFields = getVisibleFields($bottle, $event, 'guest');
                    $displayTitle = getBottleDisplayName($visibleFields, $bottle, $index);

                    // Color label
                    $colorCode = $bottle['color'] ?? 'red';
                    $colorLabel = getColorLabel($colorCode);
                    ?>
                    <div class="bottle-card"
                        style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="wine-badge badge-<?= h($colorCode) ?>">
                                    <?= h($colorLabel) ?>
                                </span>
                                <?php if ($visibleFields['price_band']): ?>
                                    <span
                                        style="font-size: 0.8rem; color: var(--text-secondary); border: 1px solid var(--border-color); padding: 2px 6px; border-radius: 4px;">
                                        <?= getPriceBandLabel($visibleFields['price_band']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Theme Fit Score (Read Only) -->
                            <?php if (!empty($visibleFields['theme_fit']) && !empty($event['show_theme_fit'])): ?>
                                <div class="theme-score" style="color: var(--accent); font-weight: bold;">
                                    Fit: <?= h($visibleFields['theme_fit']) ?>%
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3 style="font-size: 1.1rem; margin: 0 0 0.5rem 0; line-height: 1.4;">
                            <?= h($displayTitle) ?>
                        </h3>

                        <div style="font-size: 0.9rem; color: var(--text-secondary);">
                            <?php if ($visibleFields['producer']): ?>
                                <div class="info-row">
                                    <span class="label-icon">P</span> <?= h($visibleFields['producer']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($visibleFields['region'] || $visibleFields['appellation']): ?>
                                <div class="info-row">
                                    <span class="label-icon">A</span>
                                    <?= h(implode(' / ', array_filter([$visibleFields['country'], $visibleFields['region'], $visibleFields['appellation']]))) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($visibleFields['owner_label']): ?>
                                <div class="info-row" style="margin-top: 0.5rem; color: var(--text-primary);">
                                    <span class="label-icon">User</span> <?= h($visibleFields['owner_label']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($visibleFields['memo']): ?>
                                <div class="info-row" style="margin-top: 0.5rem; font-style: italic;">
                                    "<?= h($visibleFields['memo']) ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Registration CTA -->
    <section
        style="margin-top: 3rem; text-align: center; background: var(--bg-surface); padding: 2rem; border-radius: 12px; border: 1px solid var(--border-color);">
        <p style="margin-bottom: 1rem; color: var(--text-primary);">
            この会にボトルを持参される方は、<br>下のボタンから登録してください。
        </p>
        <a href="event_entry.php?ET=<?= urlencode($eventToken) ?>" class="btn-pill btn-primary"
            style="display: inline-block; text-decoration: none; padding: 1rem 2rem; font-size: 1.1rem;">
            ボトルを登録する / Register a bottle
        </a>
    </section>

</main>

<style>
    .label-icon {
        display: inline-block;
        width: 1.2em;
        text-align: center;
        opacity: 0.6;
        font-weight: bold;
    }

    .info-row {
        margin-bottom: 0.25rem;
    }
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>