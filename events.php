<?php
require_once 'db_connect.php';
require_once 'helpers.php';

// イベント一覧を新しい日付順で取得
$sql = 'SELECT * FROM events ORDER BY event_date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'VinMemo - Event List';
require_once 'layout/header.php';
?>

<header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
  <h1 style="margin:0;">イベント一覧 / Event List</h1>
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
            <td>
              <?php
              $parsed = parseEventMemo($event['memo']);
              echo nl2br(h($parsed['note']));
              ?>
              <?php if (!empty($parsed['meta'])): ?>
                <div style="margin-top:5px; font-size:0.8rem;">
                  <?php if (!empty($parsed['meta']['event_style_detail'])): ?>
                    <span
                      style="display:inline-block; padding:2px 6px; background:#444; color:#fff; border-radius:4px; margin-right:4px;">
                      <?= h(getEventStyleLabel($parsed['meta']['event_style_detail'])) ?>
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($parsed['meta']['blind_policy'])): ?>
                    <span style="display:inline-block; padding:2px 6px; background:#553333; color:#ffdddd; border-radius:4px;">
                      <?= h(getBlindPolicyLabel($parsed['meta']['blind_policy'])) ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>
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
              <span class="value">
                <?php
                $parsed = parseEventMemo($event['memo']);
                echo nl2br(h($parsed['note']));
                ?>
                <?php if (!empty($parsed['meta'])): ?>
                  <div style="margin-top:8px; display:flex; gap:5px; flex-wrap:wrap;">
                    <?php if (!empty($parsed['meta']['event_style_detail'])): ?>
                      <span style="font-size:0.75rem; padding:2px 8px; border-radius:10px; background:#444; color:#fff;">
                        <?= h(getEventStyleLabel($parsed['meta']['event_style_detail'])) ?>
                      </span>
                    <?php endif; ?>
                    <?php if (!empty($parsed['meta']['blind_policy'])): ?>
                      <span style="font-size:0.75rem; padding:2px 8px; border-radius:10px; background:#553333; color:#ffdddd;">
                        <?= h(getBlindPolicyLabel($parsed['meta']['blind_policy'])) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require_once 'layout/footer.php'; ?>