<?php
// simulate_bottle_posts.php

$eventId = 6; // Set from previous step

$bottles = [
    [
        'owner_label' => 'Mr. A',
        'wine_name' => 'Chateau Margaux',
        'producer_name' => 'Chateau Margaux',
        'vintage' => '2010',
        'country' => 'France',
        'est_price_yen' => '100000',
        'is_blind' => '1',
        'color' => 'red',
        'event_id' => $eventId,
        'action' => 'add'
    ],
    [
        'owner_label' => 'Ms. B',
        'wine_name' => 'Opus One',
        'producer_name' => 'Opus One',
        'vintage' => '2015',
        'country' => 'USA',
        'est_price_yen' => '50000',
        'is_blind' => '1',
        'color' => 'red',
        'event_id' => $eventId,
        'action' => 'add'
    ],
    [
        'owner_label' => 'Mr. C',
        'wine_name' => 'Grande Cuvee',
        'producer_name' => 'Krug',
        'vintage' => '',
        'country' => 'France',
        'est_price_yen' => '30000',
        'is_blind' => '0',
        'color' => 'sparkling',
        'event_id' => $eventId,
        'action' => 'add'
    ]
];

foreach ($bottles as $data) {
    $ch = curl_init('http://localhost/vinmemo-app/bottle_new.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Follow redirects to ensure we land on the event page (or at least process the logic)
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    echo "Added bottle for {$data['owner_label']}. Http Code: " . $info['http_code'] . "\n";
}
