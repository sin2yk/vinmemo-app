<?php
// event_public.php : Guest Event View (Public with ET)
// Refactored to match event_show.php design
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php';

$eventToken = $_GET['ET'] ?? null;

if (!$eventToken) {
    http_response_code(400);
    echo 'Invalid access (missing ET).';
    exit;
}

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
$m = $parsedMemo['meta'] ?? [];
$themeDesc = $m['theme_description'] ?? '';
$bottleRules = $m['bottle_rules'] ?? '';
$blindPolicy = $m['blind_policy'] ?? 'none';


// 2. Fetch Bottles
$sql = "SELECT * FROM bottle_entries WHERE event_id = :event_id ORDER BY created_at ASC";
// Note: event_show.php orders by created_at. Aligning here.
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
$stmt->execute();
$bottles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Media (if enabled)
$mediaItems = [];
if (!empty($event['media_enabled'])) {
    $sql = "SELECT * FROM event_media WHERE event_id = :event_id AND visibility = 'public' ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $mediaItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if registration is allowed
// 1. Explicit style "no_byo"
// 2. "持ちより不要" (No BYO required) in text
$eventStyle = $m['event_style_detail'] ?? '';
$isNoByo = ($eventStyle === 'no_byo')
    || (strpos($bottleRules, '持ちより不要') !== false)
    || (strpos($bottleRules, '持参不要') !== false)
    || (strpos($bottleRules, '持ち込み不要') !== false);

$allowRegistration = !$isNoByo;

?>
<?php include __DIR__ . '/partials/public_header.php'; ?>

<main class="container main-container" style="padding-bottom: 80px;">

    <!-- 5.5 Event Info Card (Matching event_show.php) -->
    <section class="card event-info-card" style="margin-bottom:20px; padding:20px;">
        <h1 class="event-title" style="margin-top:0; line-height:1.2; font-size:1.8rem;">
            <?= h($event['title']) ?>
        </h1>

        <?php if (!empty($m['subtitle'])): ?>
            <p class="event-subtitle" style="font-size:1.1rem; color:var(--text-muted); margin:0.2rem 0 0.8rem;">
                <?= h($m['subtitle']) ?>
            </p>
        <?php endif; ?>

        <!-- Subtle Divider -->
        <div style="border-bottom:1px solid var(--border); margin: 15px 0 15px 0;"></div>

        <!-- Meta Rows -->
        <p class="event-meta-row">
            📅 <?= h(getEventDateDisplay($event)) ?>
        </p>
        <p class="event-meta-row">
            📍 <?php
            if (!empty($event['area_label'])) {
                echo h($event['area_label']) . ' · ' . h($event['place']);
            } else {
                echo h($event['place']);
            }
            ?>
        </p>
        <?php if (!empty($event['expected_guests'])): ?>
            <p class="event-meta-row">
                👥 Expected Guests / 想定参加人数:
                <?= (int) $event['expected_guests'] ?> guests / <?= (int) $event['expected_guests'] ?>名
            </p>
        <?php endif; ?>

        <?php if ($themeDesc): ?>
            <p class="event-meta-row">
                🎯 Theme / テーマ:<br>
                <span style="display:block; margin-top:4px; font-size:0.95em; line-height:1.5;">
                    <?= nl2br(h($themeDesc)) ?>
                </span>
            </p>
        <?php endif; ?>

        <?php if ($bottleRules): ?>
            <p class="event-meta-row" style="margin-top:10px;">
                📜 Bottle Rules / ボトルルール:<br>
                <span style="display:block; margin-top:4px; font-size:0.95em; line-height:1.5;">
                    <?= nl2br(h($bottleRules)) ?>
                </span>
            </p>
        <?php endif; ?>

        <div class="event-meta-row" style="margin-top:10px;">
            👁 Blind Policy / ブラインド方針:
            <span style="display:inline-block; margin-left:8px;">
                <?= getBlindPolicyLabel($blindPolicy) ?>
            </span>
        </div>
    </section>

    <!-- Registration CTA Top/Middle -->
    <?php if ($allowRegistration): ?>
        <div style="text-align:center; margin: 30px 0;">
            <a href="event_entry.php?ET=<?= urlencode($eventToken) ?>" class="vm-btn vm-btn--primary"
                style="padding: 12px 30px; font-size: 1.1rem; border-radius: 50px;">
                ボトルを登録する / Register a bottle
            </a>
        </div>
    <?php endif; ?>

    <!-- Bottle List -->
    <section>
        <div class="section-header">
            <h2 class="section-title">
                Wine List / ワインリスト
            </h2>
            <div class="section-view-switch">
                <span style="color:var(--text-muted); font-size:0.9rem;">View Only / 閲覧専用</span>
            </div>
        </div>

        <?php if (empty($bottles)): ?>
            <p style="text-align:center; color:var(--text-muted); padding:20px;">
                No wines registered yet. / ワインの登録がありません。
            </p>
        <?php else: ?>
            <div class="bottle-list-container">
                <?php foreach ($bottles as $index => $b): ?>
                    <?php
                    // Visibility Logic
                    $visible = getVisibleFields($b, $event, 'guest'); // 'guest' mode logic from helpers
                    $displayName = getBottleDisplayName($visible, $b, $index);

                    $ownerStr = $visible['owner_label'] ? (' ' . h($visible['owner_label'])) : '';
                    $line1 = '#' . ($index + 1) . $ownerStr;
                    $mainTitle = h($displayName);
                    ?>
                    <div class="bottle-card bottle-card--<?= h($visible['color'] ?? 'red') ?>" style="margin-bottom:20px; padding:14px 16px; border-radius:12px;
                                background:rgba(0,0,0,0.2); position:relative;">
                        <div class="line-1-label"
                            style="font-size:0.9em; color:var(--text-muted); display:flex; justify-content:space-between;">
                            <span><?= $line1 ?></span>
                        </div>
                        <div class="line-main"
                            style="font-size:1.2em; font-weight:600; margin-top:4px; color:var(--text-main);">
                            <?= $mainTitle ?>
                        </div>

                        <!-- Specs Line -->
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
                        // Origin Line
                        $orgs = array_filter([$visible['country'], $visible['region'], $visible['appellation']]);
                        if (!empty($orgs)):
                            ?>
                            <div class="line-origin" style="font-size:0.9rem; color:var(--text-muted);">
                                <?= implode(' / ', array_map('h', $orgs)) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Metas (Price, Theme, Memo) -->
                        <div class="line-metas" style="margin-top:8px; font-size:0.85rem; color:var(--text-muted);">
                            <?php if ($visible['price_band']): ?>
                                <div>価格帯: <?= getPriceBandLabel($visible['price_band']) ?></div>
                            <?php endif; ?>

                            <?php if ($visible['theme_fit'] && !empty($event['show_theme_fit'])): ?>
                                <div style="color:var(--accent);">Theme Fit: <?= $visible['theme_fit'] ?>/5</div>
                            <?php endif; ?>

                            <?php if ($visible['memo']): ?>
                                <div style="margin-top:4px; font-style:italic;">
                                    "<?= mb_strimwidth(h($visible['memo']), 0, 100, '...') ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Media Gallery Section -->
    <?php if (!empty($event['media_enabled'])): ?>
        <section style="margin-top: 3rem;">
            <div class="section-header">
                <h2 class="section-title">Media Gallery / メディア</h2>
            </div>

            <?php if (empty($mediaItems)): ?>
                <p style="color:var(--text-muted); text-align:center; padding:20px;">
                    まだ写真やファイルは登録されていません。<br>
                    No media uploaded yet.
                </p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                    <?php foreach ($mediaItems as $media): ?>
                        <?php
                        $path = h($media['file_path']);
                        $title = h($media['title']);
                        $isImage = (strpos($media['mime_type'], 'image/') === 0);
                        ?>
                        <div
                            style="border: 1px solid var(--border); border-radius: 8px; padding: 10px; background: rgba(0,0,0,0.2);">
                            <?php if ($isImage): ?>
                                <a href="<?= $path ?>" target="_blank" style="display:block; margin-bottom:8px;">
                                    <img src="<?= $path ?>" alt="<?= $title ?>"
                                        style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px;">
                                </a>
                            <?php else: ?>
                                <div
                                    style="height:120px; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.05); border-radius:4px; margin-bottom:8px;">
                                    <span style="font-family:monospace; font-weight:bold;">PDF</span>
                                </div>
                            <?php endif; ?>

                            <div
                                style="font-size: 0.85rem; font-weight: bold; margin-bottom: 4px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">
                                <?= $title ?>
                            </div>

                            <?php if (!$isImage): ?>
                                <a href="<?= $path ?>" target="_blank"
                                    style="font-size: 0.8rem; text-decoration: underline; color: var(--text-secondary);">
                                    View File
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px; text-align: center;">
                <a href="event_media_upload.php?ET=<?= urlencode($eventToken) ?>" class="vm-btn"
                    style="border:1px solid var(--accent); color:var(--accent);">
                    📷 Upload Photos/Files
                </a>
            </div>
        </section>
    <?php endif; ?>

    <!-- Registration CTA Bottom -->
    <?php if ($allowRegistration): ?>
        <section
            style="margin-top: 3rem; text-align: center; background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 12px; border: 1px solid var(--border-color);">
            <p style="margin-bottom: 1rem; color: var(--text-primary);">
                この会にボトルを持参される方は、<br>下のボタンから登録してください。
            </p>
            <a href="event_entry.php?ET=<?= urlencode($eventToken) ?>" class="vm-btn vm-btn--primary"
                style="display: inline-block; text-decoration: none; padding: 12px 30px; font-size: 1.1rem; border-radius: 50px;">
                ボトルを登録する / Register a bottle
            </a>
        </section>
    <?php endif; ?>

</main>
<?php require_once __DIR__ . '/layout/footer.php'; ?>