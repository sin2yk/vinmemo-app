<?php
require_once 'db_connect.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

// 簡易的なログインシミュレーション（デバッグ用）
// ?login_as=1 で強制ログイン
if (isset($_GET['login_as'])) {
    $user_id = (int) $_GET['login_as'];
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = "user{$user_id}@example.com";
}

if (!$user_id) {
    // ログインしていない場合
    $message = "ログインしていません。";
} else {
    // 1. 参加イベント (Organizer含む)
    // event_participants テーブル経由で取得
    $sql = "SELECT e.*, ep.role_in_event 
            FROM events e 
            JOIN event_participants ep ON e.id = ep.event_id 
            WHERE ep.user_id = :user_id 
            ORDER BY e.event_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $my_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. 登録したボトル
    $sql = "SELECT b.*, e.title as event_title, e.event_date 
            FROM bottle_entries b 
            JOIN events e ON b.event_id = e.id 
            WHERE b.brought_by_user_id = :user_id 
            ORDER BY e.event_date DESC, b.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $my_bottles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>

        <?php if (!$user_id): ?>
            <div class="card text-center">
                <p>ログインしていません。<br>Please login from Home.</p>
                <a href="home.php" class="button">Homeへ</a>

                <hr>
                <p>【開発用デバッグ】</p>
                <a href="mypage.php?login_as=1" class="button" style="background:#555;">User ID=1 として表示</a>
            </div>
        <?php else: ?>
            <div style="text-align:right; color:var(--text-muted);">
                Logged in as ID: <?= htmlspecialchars($user_id) ?>
            </div>

            <h2 class="section-title">参加したイベント / My Events</h2>
            <?php if (empty($my_events)): ?>
                <p>参加予定のイベントはありません。</p>
            <?php else: ?>
                <?php foreach ($my_events as $evt): ?>
                    <div class="list-item">
                        <div>
                            <div style="font-weight:bold; font-size:1.1em;">
                                <a href="event_show.php?id=<?= $evt['id'] ?>">
                                    <?= htmlspecialchars($evt['title']) ?>
                                </a>
                            </div>
                            <div style="font-size:0.9em; color:var(--text-muted);">
                                <?= htmlspecialchars($evt['event_date']) ?> @ <?= htmlspecialchars($evt['place']) ?>
                            </div>
                        </div>
                        <div>
                            <span
                                style="background:var(--accent); color:#000; padding:2px 8px; border-radius:10px; font-size:0.8em;">
                                <?= htmlspecialchars($evt['role_in_event']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <h2 class="section-title">登録したボトル / My Bottles</h2>
            <?php if (empty($my_bottles)): ?>
                <p>登録したボトルはありません。</p>
            <?php else: ?>
                <?php foreach ($my_bottles as $b): ?>
                    <div class="bottle-card">
                        <div style="font-size:0.85em; color:var(--accent); margin-bottom:5px;">
                            <?= htmlspecialchars($b['event_date']) ?> - <?= htmlspecialchars($b['event_title']) ?>
                        </div>
                        <div style="font-weight:bold; font-size:1.1em;">
                            <?= htmlspecialchars($b['wine_name']) ?>
                        </div>
                        <div style="font-size:0.9em; color:var(--text-muted);">
                            <?= htmlspecialchars($b['vintage'] ?: 'NV') ?> /
                            <?= htmlspecialchars($b['owner_label']) ?>
                        </div>
                        <div style="text-align:right; margin-top:10px;">
                            <a href="event_show.php?id=<?= $b['event_id'] ?>" style="font-size:0.9em;">イベントを見る →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>

</html>