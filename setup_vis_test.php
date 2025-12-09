<?php
require_once 'db_connect.php';

// 1. Create Event
$sql = "INSERT INTO events (title, event_date, event_type, place, memo, list_field_visibility) 
        VALUES (:title, CURDATE(), 'BYO', 'Test Place', 'Test Memo', :json)";
$stmt = $pdo->prepare($sql);
// Initially behave like default: all visible (or empty JSON)
$stmt->execute([':title' => 'Visibility Test Event', ':json' => '{}']);
$eventId = $pdo->lastInsertId();

echo "Created Event ID: $eventId\n";

// 2. Create Bottles

// Bottle A: Blind, Reveal Level = None
$sqlA = "INSERT INTO bottle_entries (event_id, owner_label, wine_name, producer_name, vintage, country, est_price_yen, is_blind, blind_reveal_level)
         VALUES ($eventId, 'Owner A', 'Secret Wine A', 'Producer A', 2010, 'France', 10000, 1, 'none')";
$pdo->query($sqlA);
echo "Created Bottle A (Blind, None)\n";

// Bottle B: Blind, Reveal Level = Country
$sqlB = "INSERT INTO bottle_entries (event_id, owner_label, wine_name, producer_name, vintage, country, est_price_yen, is_blind, blind_reveal_level)
         VALUES ($eventId, 'Owner B', 'Secret Wine B', 'Producer B', 2015, 'Italy', 5000, 1, 'country')";
$pdo->query($sqlB);
echo "Created Bottle B (Blind, Country)\n";

// Bottle C: Not Blind
$sqlC = "INSERT INTO bottle_entries (event_id, owner_label, wine_name, producer_name, vintage, country, est_price_yen, is_blind, blind_reveal_level)
         VALUES ($eventId, 'Owner C', 'Open Wine C', 'Producer C', 2020, 'USA', 3000, 0, 'none')";
$pdo->query($sqlC);
echo "Created Bottle C (Not Blind)\n";

echo "Setup Complete.\nUrl: http://localhost/vinmemo-app/event_show.php?id=$eventId&view=organizer\n";
