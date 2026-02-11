<?php
// Quick fix for Student Union account
require_once 'api/db.php';

echo "Fixing Student Union Account Type...\n";

// Update the user type directly
$pdo->exec("UPDATE users SET user_type = 'StudentUnion' WHERE phone_number = '09999'");

// Verify
$stmt = $pdo->query("SELECT phone_number, user_type, full_name, is_verified FROM users WHERE phone_number = '09999'");
$user = $stmt->fetch();

echo "\nStudent Union Account Status:\n";
echo "Phone: " . $user['phone_number'] . "\n";
echo "Type: " . $user['user_type'] . "\n";
echo "Name: " . $user['full_name'] . "\n";
echo "Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "\n";

if ($user['user_type'] === 'StudentUnion') {
    echo "\n✓ Student Union account fixed successfully!\n";
} else {
    echo "\n✗ Issue persists!\n";
}
?>
