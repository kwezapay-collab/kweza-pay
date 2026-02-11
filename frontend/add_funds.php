<?php
// Quick script to add funds to account 23344
require_once '../backend/api/db.php';

$account = '23344';
$amountToAdd = 50000; // MWK 50,000 for testing

// Check if user exists
$stmt = $pdo->prepare("SELECT user_id, full_name, wallet_balance FROM users WHERE phone_number = ?");
$stmt->execute([$account]);
$user = $stmt->fetch();

if ($user) {
    // Add funds
    $newBalance = $user['wallet_balance'] + $amountToAdd;
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE user_id = ?");
    $stmt->execute([$newBalance, $user['user_id']]);

    echo "<h2>✅ Funds Added Successfully!</h2>";
    echo "<p><strong>Account:</strong> {$account} ({$user['full_name']})</p>";
    echo "<p><strong>Old Balance:</strong> MWK " . number_format($user['wallet_balance'], 2) . "</p>";
    echo "<p><strong>Added:</strong> MWK " . number_format($amountToAdd, 2) . "</p>";
    echo "<p><strong>New Balance:</strong> MWK " . number_format($newBalance, 2) . "</p>";
    echo "<hr>";
    echo "<p><a href='index.php'>Go to Login</a> | <a href='test_accounts.php'>View All Accounts</a></p>";
} else {
    echo "<h2>❌ Account Not Found</h2>";
    echo "<p>Account {$account} does not exist in the database.</p>";
    echo "<hr>";
    echo "<h3>Available Accounts:</h3>";

    $stmt = $pdo->query("SELECT phone_number, full_name, wallet_balance FROM users ORDER BY phone_number");
    while ($u = $stmt->fetch()) {
        echo "<p><strong>{$u['phone_number']}</strong> - {$u['full_name']} (MWK " . number_format($u['wallet_balance'], 2) . ")</p>";
    }
}
?>