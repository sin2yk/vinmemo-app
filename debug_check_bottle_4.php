<?php
require_once 'db_connect.php';

$id = 4;
$inputToken = '3519bd1a66d5776c1bfe0cb71856daf90b0a6f91bdcf932c59c5b1263475f3e1';

// Fetch from DB
$stmt = $pdo->prepare("SELECT * FROM bottle_entries WHERE id = :id");
$stmt->execute([':id' => $id]);
$bottle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bottle) {
    echo "Bottle ID $id NOT FOUND.\n";
    exit;
}

$dbToken = $bottle['edit_token'];

echo "--- Debug Info ---\n";
echo "Bottle ID: $id\n";
echo "Input Token (Len " . strlen($inputToken) . "): [$inputToken]\n";
echo "DB Token    (Len " . strlen($dbToken) . "): [$dbToken]\n";

// Strict Comparison
if ($inputToken === $dbToken) {
    echo "Result: STRICT MATCH.\n";
} else {
    echo "Result: MISMATCH.\n";
    echo "Comparison Details:\n";
    for ($i = 0; $i < max(strlen($inputToken), strlen($dbToken)); $i++) {
        $c1 = $inputToken[$i] ?? '(null)';
        $c2 = $dbToken[$i] ?? '(null)';
        if ($c1 !== $c2) {
            echo "Mismatch at index $i: Input='$c1' vs DB='$c2'\n";
            break;
        }
    }
}

// Logic Simulation
$isMatch = hash_equals($dbToken, $inputToken);
echo "hash_equals Result: " . ($isMatch ? 'TRUE' : 'FALSE') . "\n";
