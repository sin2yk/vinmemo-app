<?php
require_once 'db_connect.php';
require_once 'helpers.php';

// Test Setup: Use Event 6
$id = 6;
$pdo->exec("UPDATE events SET show_theme_fit = 1 WHERE id = $id");

function check_output($id)
{
    // We can't easily capture output of event_show.php without web server, 
    // but we can query the DB and helpers.
    // Actually, curl to localhost is best.
    $url = "http://localhost/vinmemo-app/event_show.php?id=$id&view=guest";
    $html = file_get_contents($url);
    if ($html === false)
        return "Error fetching URL";

    $hasSummaryFit = strpos($html, 'Avg Theme Fit / 平均テーマ適合度');
    $hasCardFit = strpos($html, 'Theme Fit / テーマ適合度');

    return [
        'summary_fit' => ($hasSummaryFit !== false),
        'card_fit' => ($hasCardFit !== false)
    ];
}

echo "--- Test 1: show_theme_fit = 1 ---\n";
$res1 = check_output($id);
echo "Summary Fit Visible: " . ($res1['summary_fit'] ? 'YES' : 'NO') . "\n";
echo "Card Fit Visible:    " . ($res1['card_fit'] ? 'YES' : 'NO') . "\n";

echo "\n--- Test 2: show_theme_fit = 0 ---\n";
$pdo->exec("UPDATE events SET show_theme_fit = 0 WHERE id = $id");
$res2 = check_output($id);
echo "Summary Fit Visible: " . ($res2['summary_fit'] ? 'YES' : 'NO (Expected)') . "\n";
echo "Card Fit Visible:    " . ($res2['card_fit'] ? 'YES' : 'NO (Expected)') . "\n";

// Reset
$pdo->exec("UPDATE events SET show_theme_fit = 1 WHERE id = $id");
