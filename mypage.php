<?php
require_once 'db_connect.php';
// Helper already included by header, but logic needs it? Header includes helpers.php.
// But we run logic before header. So:
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
$error = null;

// Debug simulator (preserved)
if (isset($_GET['login_as'])) {
    $user_id = (int) $_GET['login_as'];
    $_SESSION['user_id'] = $user_id;
    $_SESSION['name'] = "User{$user_id}";
    $_SESSION['role'] = 'guest'; // Default
}

$organized_events = [];
$joined_events = [];
$my_bottles = [];

if (!$user_id) {
    // Not logged in
} else {
    // 1. Fetch Events (Organized vs Joined)
    $sql = "SELECT e.*, ep.role_in_event 
            FROM events e 
            JOIN event_participants ep ON e.id = ep.event_id 
            WHERE ep.user_id = :user_id 
            ORDER BY e.event_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_events as $evt) {
        if ($evt['role_in_event'] === 'organizer') {
            $organized_events[] = $evt;
        } else {
            $joined_events[] = $evt;
        }
    }

    // 2. Fetch My Bottles
    $sql = "SELECT b.*, e.title as event_title, e.event_date, e.id as event_id
            FROM bottle_entries b 
            JOIN events e ON b.event_id = e.id 
            WHERE b.brought_by_user_id = :user_id 
            ORDER BY e.event_date DESC, b.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $my_bottles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = 'VinMemo - My Page';
require_once 'layout/header.php';
?>

<?php if (!$user_id): ?>
    <div class="card text-center">
        <h2>Login Required</h2>
        <p>Please login from Home.</p>
        <a href="home.php" class="button">ホームへ</a>

        <hr>
        <p>Debug:</p>
        <a href="mypage.php?login_as=1" class="button" style="background:#555;">ID=1としてログイン</a>
    </div>
<?php else: ?>

    <h2 class="section-title">Events I Organize</h2>
    <?php if (empty($organized_events)): ?>
        <p>No organized events.</p>
    <?php else: ?>
        <?php foreach ($organized_events as $evt): ?>
            <div class="list-item" style="border-left: 5px solid var(--accent);">
                <div>
                    <strong><a href="event_show.php?id=<?= h($evt['id']) ?>"><?= h($evt['title']) ?></a></strong>
                    <br>
                    <span style="font-size:0.9em; color:#aaa;"><?= h($evt['event_date']) ?> @ <?= h($evt['place']) ?></span>
                </div>
                <span
                    style="background:var(--accent); color:#000; padding:2px 8px; border-radius:4px; font-size:0.8em; font-weight:bold;">Organizer</span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2 class="section-title">Events I Joined</h2>
    <?php if (empty($joined_events)): ?>
        <p>No joined events.</p>
    <?php else: ?>
        <?php foreach ($joined_events as $evt): ?>
            <div class="list-item">
                <div>
                    <strong><a href="event_show.php?id=<?= h($evt['id']) ?>"><?= h($evt['title']) ?></a></strong>
                    <br>
                    <span style="font-size:0.9em; color:#aaa;"><?= h($evt['event_date']) ?> @ <?= h($evt['place']) ?></span>
                </div>
                <span style="background:#555; padding:2px 8px; border-radius:4px; font-size:0.8em;">Guest</span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2 class="section-title">My Bottles History</h2>
    <?php if (empty($my_bottles)): ?>
        <p>No bottles registered.</p>
    <?php else: ?>
        <?php foreach ($my_bottles as $b): ?>
            <div class="bottle-card">
                <div style="font-size:0.85em; color:var(--accent); margin-bottom:5px;">
                    <?= h($b['event_date']) ?> - <?= h($b['event_title']) ?>
                </div>
                <div style="font-weight:bold; font-size:1.1em;">
                    <?= h($b['wine_name']) ?>
                </div>
                <div style="font-size:0.9em; color:var(--text-muted);">
                    <?= h($b['vintage'] ?: 'NV') ?> / <?= h($b['owner_label']) ?>
                </div>
                <div style="text-align:right; margin-top:10px;">
                    <a href="event_show.php?id=<?= h($b['event_id']) ?>" style="font-size:0.9em;">イベントを表示 →</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php endif; ?>

<?php require_once 'layout/footer.php'; ?>