<?php

// XSS対策：htmlspecialchars
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Get the user's role in a specific event.
 * 
 * @param PDO $pdo
 * @param int $eventId
 * @param int|null $userId
 * @return string 'organizer' | 'guest'
 */
function getEventRole(PDO $pdo, int $eventId, ?int $userId): string
{
    if (!$userId) {
        return 'guest';
    }

    // Check event_participants table
    // Assuming table structure: id, event_id, user_id, role_in_event, ...
    $sql = "SELECT role_in_event FROM event_participants 
            WHERE event_id = :event_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['role_in_event'] === 'organizer') {
        return 'organizer';
    }

    return 'guest';
}

/**
 * Mask value if blind mode is on.
 * 
 * @param string $value Original value
 * @param mixed $blindFlag 1 or true or '1' if blind
 * @param string $mask Replacement string
 * @return string
 */
function mask_if_blind($value, $blindFlag, $mask = '???')
{
    if ((int) $blindFlag === 1) {
        return $mask;
    }
    return h($value);
}
