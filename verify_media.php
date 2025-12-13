<?php
require_once 'db_connect.php';

try {
    // 1. Find the 12/23 event
    // Looking for event_date matching 2024-12-23 or similar
    // Or just list events to see
    $stmt = $pdo->query("SELECT id, title, event_date FROM events WHERE event_date LIKE '%12-23%' OR event_date LIKE '%12/23%' OR event_date LIKE '%Dec 23%' LIMIT 1");
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        // Fallback: Enable for the most recent event
        echo "Specific event not found. Enabling for latest event...\n";
        $stmt = $pdo->query("SELECT id, title FROM events ORDER BY id DESC LIMIT 1");
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($event) {
        echo "Target Event: [{$event['id']}] {$event['title']} ({$event['event_date']})\n";

        // 2. Enable Media
        $pdo->prepare("UPDATE events SET media_enabled = 1 WHERE id = ?")->execute([$event['id']]);
        echo "Updated media_enabled = 1.\n";

        // 3. Insert Dummy Media (Visual Check Helper)
        // We won't create actual file, but DB entry will ensure logic doesn't crash
        // $pdo->prepare("INSERT INTO event_media (event_id, title, file_path, mime_type, file_size) VALUES (?, 'Test Image', 'img/logo.png', 'image/png', 1024)")->execute([$event['id']]);
        // echo "Inserted dummy media entry.\n";

        echo "Verification Setup Complete.\n";
    } else {
        echo "No events found to update.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
