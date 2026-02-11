<?php
require_once 'api/db.php';

$phone = '09900';
$newPin = '1234';

echo "--- Debugging SU Account ($phone) ---\n";

// 1. Check current status
$stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "ERROR: User $phone does not exist!\n";
    // Create if missing
    $hash = password_hash($newPin, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, phone_number, pin_hash, user_type, wallet_balance) VALUES ('Student Union', ?, ?, 'StudentUnion', 500000)");
    $stmt->execute([$phone, $hash]);
    echo "Created user $phone with PIN $newPin.\n";
} else {
    echo "User found. ID: {$user['user_id']}, Type: {$user['user_type']}\n";
    
    // 2. Force Update PIN and Type
    $hash = password_hash($newPin, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET pin_hash = ?, user_type = 'StudentUnion' WHERE user_id = ?");
    $stmt->execute([$hash, $user['user_id']]);
    echo "UPDATED: User type set to 'StudentUnion' and PIN reset to '$newPin'.\n";
}

// 3. Verify Login Logic Locally
$stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
$stmt->execute([$phone]);
$updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (password_verify($newPin, $updatedUser['pin_hash'])) {
    echo "VERIFICATION: Login with '$newPin' SUCCESS.\n";
} else {
    echo "VERIFICATION: Login FAILED (Hash mismatch).\n";
}
?>
