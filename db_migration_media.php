<?php
require_once 'db_connect.php';

try {
    echo "Starting DB Migration for Event Media...\n";

    // 1. Add media_enabled to events if not exists
    $columns = $pdo->query("DESCRIBE events")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('media_enabled', $columns)) {
        $pdo->exec("ALTER TABLE events ADD COLUMN media_enabled TINYINT(1) NOT NULL DEFAULT 0");
        echo "Added 'media_enabled' column to events table.\n";
    } else {
        echo "'media_enabled' column already exists.\n";
    }

    // 2. Create event_media table
    $sql = "CREATE TABLE IF NOT EXISTS event_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        uploader_user_id INT NULL,
        uploader_name VARCHAR(255) NOT NULL DEFAULT '',
        uploader_email VARCHAR(255) NOT NULL DEFAULT '',
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size INT NOT NULL,
        visibility ENUM('public', 'organizer_only') NOT NULL DEFAULT 'public',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Created 'event_media' table (if it didn't exist).\n";

    echo "Migration Complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
