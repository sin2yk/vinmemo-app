<?php
// auth_sync.php
require_once 'db_connect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['uid'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$email = mb_strtolower(trim($input['email']));
$firebaseUid = $input['uid'];
$displayName = $input['displayName'] ?? substr($email, 0, strpos($email, '@'));

try {
    // 1. Find or Create User
    $stmt = $pdo->prepare("SELECT id, display_name FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (email, display_name, created_at) VALUES (:email, :name, NOW())");
        $stmt->execute([':email' => $email, ':name' => $displayName]);
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $user['id'];
        $displayName = $user['display_name'];
    }

    // 2. Set Session
    $_SESSION['user_id'] = $userId;
    $_SESSION['email'] = $email;
    $_SESSION['display_name'] = $displayName;

    // 3. Claim Bottles
    $claimedCount = claim_guest_bottles_for_user($pdo, $userId, $email);

    echo json_encode([
        'success' => true,
        'userId' => $userId,
        'claimed' => $claimedCount
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
