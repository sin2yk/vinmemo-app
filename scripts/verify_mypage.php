<?php
// Verify My Page Flow
$base_url = 'http://localhost/vinmemo-app';
$cookie_file = __DIR__ . '/cookie.txt';

if (file_exists($cookie_file))
    unlink($cookie_file);

// 1. Create Guest Bottle
echo "1. Creating Guest Bottle...\n";
$email = "curl" . time() . "@test.com";
$wine = "CurlWine" . time();
$postData = [
    'event_id' => 6,
    'owner_label' => 'CurlGuest',
    'guest_email' => $email,
    'producer_name' => 'CurlProd',
    'wine_name' => $wine,
    'color' => 'red',
    'price_band' => 'casual',
    'theme_fit_score' => 3
];

$ch = curl_init("$base_url/bottle_new.php?event_id=6");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($info['http_code'] == 302 && strpos($info['redirect_url'], 'bottle_created.php') !== false) {
    echo "   Success: Redirected to bottle_created.php\n";
} else {
    echo "   Failed: HTTP " . $info['http_code'] . "\n";
    // echo "Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

// 2. Simulate Login (Auth Sync)
echo "2. Simulating Login Sync ($email)...\n";
$syncData = json_encode([
    'email' => $email,
    'uid' => 'firebase_uid_' . time(),
    'displayName' => 'CurlUser'
]);

$ch = curl_init("$base_url/auth_sync.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $syncData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
$response = curl_exec($ch);
curl_close($ch);
echo "   Response: $response\n";

$json = json_decode($response, true);
if ($json['success'] && $json['claimed'] > 0) {
    echo "   Success: Synced and claimed " . $json['claimed'] . " bottles.\n";
} else {
    echo "   Warning: Claimed count is " . ($json['claimed'] ?? 'null') . "\n";
}

// 3. Verify My Page
echo "3. Verifying My Page...\n";
$ch = curl_init("$base_url/mypage.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
$response = curl_exec($ch);
curl_close($ch);

if (strpos($response, $wine) !== false) {
    echo "   SUCCESS: Found wine '$wine' on My Page.\n";
} else {
    echo "   FAILURE: Wine '$wine' NOT found on My Page.\n";
    file_put_contents(__DIR__ . '/mypage_fail_dump.html', $response);
    echo "   Dumped response to mypage_fail_dump.html\n";
    exit(1);
}
