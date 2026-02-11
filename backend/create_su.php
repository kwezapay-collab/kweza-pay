<?php
require_once 'api/db.php';

$phone = '09900'; // Special SU number
$pin = password_hash('1234', PASSWORD_DEFAULT);

// Check if exists
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone_number = ?");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    echo "User 09900 already exists.";
    // Update type just in case
    $pdo->prepare("UPDATE users SET user_type = 'StudentUnion' WHERE phone_number = ?")->execute([$phone]);
} else {
    $stmt = $pdo->prepare("INSERT INTO users (full_name, phone_number, pin_hash, user_type, wallet_balance) VALUES (?, ?, ?, 'StudentUnion', 500000.00)");
    $stmt->execute(['Student Union Admin', $phone, $pin]);
    echo "Created SU Admin: 09900";
}
?>
