<?php
// mypage.php
require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserId = $_SESSION['user_id'] ?? 0;

if (!$currentUserId) {
    header('Location: index.html');
    exit;
}

$page_title = 'VinMemo - My Page';
require_once 'layout/header.php';

// Claim on load (Idempotent check)
$userEmail = $_SESSION['email'] ?? '';
if ($userEmail) {
    claim_guest_bottles_for_user($pdo, $currentUserId, $userEmail);
}

// 1. Events I Organized
$stmt = $pdo->prepare("SELECT * FROM events WHERE organizer_user_id = :uid ORDER BY event_date DESC");
$stmt->execute([':uid' => $currentUserId]);
$myEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Events I Participated In (Guest participation)
$sqlParticipated = "SELECT DISTINCT e.* 
                    FROM events e 
                    JOIN bottle_entries b ON e.id = b.event_id 
                    WHERE b.brought_by_user_id = :uid 
                      AND (e.organizer_user_id != :uid OR e.organizer_user_id IS NULL)
                    ORDER BY e.event_date DESC";
$stmt = $pdo->prepare($sqlParticipated);
$stmt->execute([':uid' => $currentUserId]);
$participatedEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. My Bottles (Timeline)
// Assuming created_at exists, prioritizing event date then bottle creation
$sqlBottles = "SELECT b.*, e.title AS event_title, e.event_date 
               FROM bottle_entries b 
               JOIN events e ON b.event_id = e.id 
               WHERE b.brought_by_user_id = :uid 
               ORDER BY e.event_date DESC, b.created_at DESC";
$stmt = $pdo->prepare($sqlBottles);
$stmt->execute([':uid' => $currentUserId]);
$myBottles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="page-container">
    <header class="page-header">
        <h1>My Page / マイページ</h1>
        <p>Welcome, <?= h($_SESSION['display_name'] ?? 'User') ?></p>
    </header>

    <!-- SECTION 1: Organized Events -->
    <section class="mypage-section">
        <h2>🎪 Events I Organized / 自分が主催したイベント</h2>
        <?php if (empty($myEvents)): ?>
            <p class="empty-state">No events organized yet.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($myEvents as $ev): ?>
                    <a href="event_show.php?id=<?= h($ev['id']) ?>" class="event-card-link">
                        <div class="card event-card">
                            <h3><?= h($ev['title']) ?></h3>
                            <div class="meta">
                                <span>📅 <?= h($ev['event_date']) ?></span>
                                📍 <?= h($ev['place']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- SECTION 2: Participated Events -->
    <section class="mypage-section">
        <h2>🍷 Events I Participated In / 自分が参加したイベント</h2>
        <?php if (empty($participatedEvents)): ?>
            <p class="empty-state">No participation history yet.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($participatedEvents as $ev): ?>
                    <a href="event_show.php?id=<?= h($ev['id']) ?>" class="event-card-link">
                        <div class="card event-card">
                            <h3><?= h($ev['title']) ?></h3>
                            <div class="meta">
                                <span>📅 <?= h($ev['event_date']) ?></span>
                                📍 <?= h($ev['place']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- SECTION 3: Bottle Timeline -->
    <section class="mypage-section">
        <h2>🍾 My Bottles Timeline / 自分のボトル履歴</h2>
        <?php if (empty($myBottles)): ?>
            <p class="empty-state">You haven't registered any bottles yet.</p>
        <?php else: ?>
            <div class="bottle-list">
                <?php foreach ($myBottles as $b): ?>
                    <div class="card bottle-card-slim">
                        <div class="bottle-header">
                            <span class="wine-name"><?= h($b['wine_name']) ?></span>
                            <span class="vintage"><?= $b['vintage'] ? h($b['vintage']) : 'NV' ?></span>
                        </div>
                        <div class="bottle-meta">
                            Producer: <?= h($b['producer_name']) ?> |
                            Event: <a href="event_show.php?id=<?= h($b['event_id']) ?>"><?= h($b['event_title']) ?></a>
                            (<?= h($b['event_date']) ?>)
                        </div>
                        <?php if ($b['edit_token']): ?>
                            <div class="bottle-actions" style="margin-top:5px;">
                                <a href="bottle_edit.php?token=<?= h($b['edit_token']) ?>" class="btn-sm">Edit</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
    .mypage-section {
        margin-bottom: 40px;
    }

    .mypage-section h2 {
        border-bottom: 2px solid var(--accent);
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }

    .event-card-link {
        text-decoration: none;
        color: inherit;
    }

    .event-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent);
        transition: all 0.2s;
    }

    .bottle-card-slim {
        padding: 15px;
        border-left: 4px solid var(--accent);
    }

    .bottle-header {
        display: flex;
        justify-content: space-between;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .bottle-meta {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-top: 5px;
    }

    .btn-sm {
        font-size: 0.8rem;
        padding: 2px 8px;
        background: #444;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
    }
</style>

<?php require_once 'layout/footer.php'; ?>