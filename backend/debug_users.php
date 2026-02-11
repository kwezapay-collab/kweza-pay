<?php
require_once 'api/db.php';

echo "Checking users table structure...\n\n";

// Show column details
$stmt = $pdo->query("SHOW COLUMNS FROM users");
$columns = $stmt->fetchAll();

echo "Columns in 'users' table:\n";
foreach ($columns as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}

echo "\n\nChecking all users:\n";
$stmt = $pdo->query("SELECT user_id, phone_number, full_name, user_type, wallet_balance, is_verified FROM users");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "\nID: {$user['user_id']}\n";
    echo "  Phone: {$user['phone_number']}\n";
    echo "  Name: {$user['full_name']}\n";
    echo "  Type: '{$user['user_type']}'\n";
    echo "  Balance: MWK {$user['wallet_balance']}\n";
    echo "  Verified: " . ($user['is_verified'] ? 'Yes' : 'No') . "\n";
}
?>
