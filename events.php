<?php
require_once 'db_connect.php';

// イベント一覧を新しい日付順で取得
$sql = 'SELECT * FROM events ORDER BY event_date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'VinMemo - Event List';
require_once 'layout/header.php';
?>

<header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
  <h1 style="margin:0;">Event List</h1>
  <a href="events_new.php" class="button">＋ New Event</a>
</header>

<?php if (empty($events)): ?>
  <p>No events found.</p>
<?php else: ?>
  <div class="card">
    <table class="event-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Date</th>
          <th>Place</th>
          <th>Memo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $event): ?>
          <tr>
            <td>
              <a href="event_show.php?id=<?= h($event['id']) ?>">
                <?= h($event['title']) ?>
              </a>
            </td>
            <td><?= h($event['event_date']) ?></td>
            <td><?= h($event['place']) ?></td>
            <td><?= nl2br(h($event['memo'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="event-list-cards">
      <?php foreach ($events as $event): ?>
        <div class="event-card">
          <div class="event-card-title">
            <a href="event_show.php?id=<?= h($event['id']) ?>">
              <?= h($event['title']) ?>
            </a>
          </div>
          <div class="event-card-meta">
            <div class="event-card-row">
              <span class="label">Date</span>
              <span class="value"><?= h($event['event_date']) ?></span>
            </div>
            <div class="event-card-row">
              <span class="label">Place</span>
              <span class="value"><?= h($event['place']) ?></span>
            </div>
            <div class="event-card-row">
              <span class="label">Memo</span>
              <span class="value"><?= nl2br(h($event['memo'])) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require_once 'layout/footer.php'; ?>