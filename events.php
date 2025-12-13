<?php
require_once __DIR__ . '/auth_required.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/helpers.php';

// イベント一覧を新しい日付順で取得
$sql = 'SELECT * FROM events ORDER BY event_date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'VinMemo - Event List';
require_once 'layout/header.php';
?>

<div style="margin-bottom:20px; text-align:right;">
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="events_new.php" class="vm-btn vm-btn--primary">+ New Event</a>
  <?php endif; ?>
</div>
<?php if (empty($events)): ?>
  <p>No events found.</p>
<?php else: ?>
  <div class="event-list-cards">
    <?php foreach ($events as $event): ?>
      <?php
      $parsed = parseEventMemo($event['memo']);
      $m = $parsed['meta'] ?? [];
      ?>
      <div class="card event-card" style="margin-bottom:15px; padding:15px;">
        <!-- 1. Title -->
        <h3 class="event-title-main">
          <a href="event_show.php?id=<?= h($event['id']) ?>" style="text-decoration:none; color:inherit;">
            <?= h($event['title']) ?>
          </a>
        </h3>

        <!-- 2. Date -->
        <p class="event-meta-row">
          📅 <?= h(getEventDateDisplay($event)) ?>
        </p>

        <!-- 3. Place -->
        <p class="event-meta-row">
          📍 <?php
          if (!empty($event['area_label'])) {
            echo h($event['area_label']) . ' · ' . h($event['place']);
          } else {
            echo h($event['place']);
          }
          ?>
        </p>

        <!-- 3b. Expected Guests -->
        <?php if (!empty($event['expected_guests'])): ?>
          <p class="event-meta-row">
            👥 Expected Guests / 想定参加人数:
            <?= (int) $event['expected_guests'] ?> guests / <?= (int) $event['expected_guests'] ?>名
          </p>
        <?php endif; ?>

        <!-- 4. Style -->
        <p class="event-meta-row">
          🎯 Style / スタイル:
          <?php
          if (!empty($m['event_style_detail'])) {
            echo h(getEventStyleLabel($m['event_style_detail']));
          } elseif (!empty($event['event_type']) && $event['event_type'] === 'BYO') {
            echo 'BYO';
          } elseif (!empty($event['event_type']) && $event['event_type'] === 'no_byo') {
            echo 'No BYO';
          } else {
            echo 'BYO'; // Default
          }
          ?>
        </p>

        <!-- 5. Theme (Optional) -->
        <?php if (!empty($m['theme_description'])): ?>
          <p class="event-meta-row">
            Theme / テーマ: <?= mb_strimwidth(h($m['theme_description']), 0, 50, '...') ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once 'layout/footer.php'; ?>