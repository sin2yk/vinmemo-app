<?php
// tools/fix_backfill_event_tokens.php

require_once __DIR__ . '/../db_connect.php';

/**
 * Generate a random event token (ET) for public guest access.
 * Copied from events_new.php for standalone usage.
 *
 * @return string 32-characters hex string
 */
function generateEventToken(): string
{
    // 16 bytes → 32 chars hex
    return bin2hex(random_bytes(16));
}

try {
    $sql = "SELECT id FROM events WHERE event_token IS NULL";
    $stmt = $pdo->query($sql);
    $events = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($events)) {
        echo "No events without event_token. Nothing to do.\n";
        exit(0);
    }

    $updateSql = "UPDATE events SET event_token = :event_token WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);

    $count = 0;
    foreach ($events as $eventId) {
        $token = generateEventToken();

        $updateStmt->bindValue(':event_token', $token, PDO::PARAM_STR);
        $updateStmt->bindValue(':id', $eventId, PDO::PARAM_INT);
        $updateStmt->execute();

        $count++;
        echo "Updated event_id={$eventId} with token={$token}\n";
    }

    echo "Done. Updated {$count} events.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
