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

/**
 * Get display label for bottle size.
 * 
 * @param int $ml
 * @return string
 */
function getBottleSizeLabel(int $ml): string
{
    switch ($ml) {
        case 375:
            return 'Demi';
        case 620:
            return 'Clavelin';
        case 750:
            return 'Bottle'; // Usually not shown if standard
        case 1500:
            return 'Magnum';
        case 3000:
            return 'Jeroboam';
        default:
            return $ml . 'ml';
    }
}

/**
 * Get display label for price band (Title Case).
 * 
 * @param string $band
 * @return string
 */
function getPriceBandLabel(string $band): string
{
    $map = [
        'casual' => 'Casual',
        'bistro' => 'Bistro',
        'fine' => 'Fine',
        'luxury' => 'Luxury',
        'icon' => 'Icon',
    ];
    return $map[$band] ?? ucfirst($band);
}

/**
 * Check if the event is fully revealed for guests.
 * 
 * @param array $event
 * @return bool
 */
function isEventRevealed(array $event): bool
{
    // 1. Explicitly revealed
    if (!empty($event['revealed_at'])) {
        return true;
    }

    // 2. Auto-reveal after N days (e.g., 7 days after event_date)
    // Note: event_date is YYYY-MM-DD. We assume 23:59:59 of that day as "end of event".
    if (!empty($event['event_date'])) {
        try {
            $evtDate = new DateTime($event['event_date']);
            $evtDate->modify('+7 days'); // Reveal 7 days after
            $now = new DateTime();
            if ($now >= $evtDate) {
                return true;
            }
        } catch (Exception $e) {
            // date parse error, ignore
        }
    }

    return false;
}

/**
 * Validatable fields for list visibility
 */
const VISIBLE_FIELD_KEYS = [
    'owner_label',
    'country',
    'region',
    'appellation',
    'price_band',
    'theme_fit',
    'memo'
];

/**
 * Get the visible fields for a bottle based on Role, Event Config, and Blind State.
 * 
 * @param array $bottle
 * @param array $event
 * @param string $role 'organizer' | 'guest'
 * @return array Key-value map of visible data. Null values mean hidden.
 */
function getVisibleFields(array $bottle, array $event, string $role): array
{
    // Base data
    $data = [
        // Always visible core fields (unless blind masks them)
        'producer' => $bottle['producer_name'] ?? '',
        'wine_name' => $bottle['wine_name'] ?? '',
        'vintage' => $bottle['vintage'] ?? '',
        'size' => $bottle['bottle_size_ml'] ?? 750,
        'color' => $bottle['color'] ?? '',

        // Controllable fields
        'owner_label' => $bottle['owner_label'] ?? '',
        'country' => $bottle['country'] ?? '',
        'region' => $bottle['region'] ?? '',
        'appellation' => $bottle['appellation'] ?? '',
        'price_band' => $bottle['est_price_yen'] ?? '', // simplified mapping
        'theme_fit' => $bottle['theme_fit_score'] ?? '',
        'memo' => $bottle['memo'] ?? '',
    ];

    // --- 1. Organizer sees everything ---
    if ($role === 'organizer') {
        return $data;
    }

    // --- 2. Guest: Apply Event-Level Disclosure Rules ---
    $eventConfig = [];
    if (!empty($event['list_field_visibility'])) {
        $eventConfig = json_decode($event['list_field_visibility'], true);
    }

    // If config is null/empty, default is TRUE (visible) for backward compat, 
    // OR default FALSE if we want strictness. The spec says:
    // "If a field key is missing, you can treat it as true by default"

    foreach (VISIBLE_FIELD_KEYS as $key) {
        // If explicitly set to false in event config, hide it.
        // Default (unset) is allowed.
        if (isset($eventConfig[$key]) && $eventConfig[$key] === false) {
            $data[$key] = null;
        }
    }

    // --- 3. Guest: Apply Blind Logic ---
    $isBlind = !empty($bottle['is_blind']);
    $eventRevealed = isEventRevealed($event);

    if (!$eventRevealed && $isBlind) {
        $revealLevel = $bottle['blind_reveal_level'] ?? 'none';

        // 'full' -> No masking (but event rules still apply, handled above)
        if ($revealLevel !== 'full') {
            // Mask core fields
            $data['producer'] = null;
            $data['wine_name'] = null;
            // $data['vintage'] -> depends on level

            // Mask detailed origin
            $data['region'] = null;
            $data['appellation'] = null;

            // 'none': hide almost everything
            if ($revealLevel === 'none') {
                $data['country'] = null;
                $data['vintage'] = null;
            }
            // 'country': show country, hide vintage
            elseif ($revealLevel === 'country') {
                $data['vintage'] = null;
            }
            // 'country_vintage': show both (already in data)
        }
    }

    return $data;
}

/**
 * Get the main display title for a bottle.
 * 
 * @param array $visibleFields Result from getVisibleFields
 * @param array $originalBottle For fallback or ID
 * @param int   $index          Bottle index (0-based)
 * @return string
 */
function getBottleDisplayName(array $visibleFields, array $originalBottle, int $index): string
{
    // If producer or wine_name are visible (non-null), use them
    if ($visibleFields['producer'] !== null && $visibleFields['wine_name'] !== null) {
        $vint = $visibleFields['vintage'] ? ' ' . $visibleFields['vintage'] : '';
        return $visibleFields['producer'] . ' : ' . $visibleFields['wine_name'] . $vint;
    }

    // Otherwise it is BLIND
    // We can show "Blind Bottle #N"
    // Plus "Country" if visible

    $title = 'Blind Bottle #' . ($index + 1);

    $extras = [];
    if (!empty($visibleFields['country'])) {
        $extras[] = $visibleFields['country'];
    }
    if (!empty($visibleFields['vintage'])) {
        $extras[] = $visibleFields['vintage'];
    }

    if (!empty($extras)) {
        $title .= ' (' . implode(', ', $extras) . ')';
    }

    return $title;
}
