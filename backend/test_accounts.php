<?php
// Quick debugging script to test login
require_once 'api/db.php';

echo "<h2>Testing Kweza Pay Accounts</h2>";

$accounts = ['07001', '07777', '09999', '00000'];

foreach ($accounts as $phone) {
    $stmt = $pdo->prepare("SELECT user_id, full_name, phone_number, user_type, wallet_balance, pin_hash FROM users WHERE phone_number = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h3>{$user['full_name']} ({$phone})</h3>";
        echo "User ID: {$user['user_id']}<br>";
        echo "Type: {$user['user_type']}<br>";
        echo "Balance: MWK " . number_format($user['wallet_balance'], 2) . "<br>";
        
        //Test PIN
        $testPin = '1234';
        $pinWorks = password_verify($testPin, $user['pin_hash']);
        echo "PIN '1234' works: " . ($pinWorks ? '<span style="color:green">✓ YES</span>' : '<span style="color:red">✗ NO</span>') . "<br>";
        echo "PIN Hash: " . substr($user['pin_hash'], 0, 30) . "...<br>";
        echo "<hr>";
    }
}

// Test session
echo "<h3>Session Test</h3>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "Session active for user ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "No active session<br>";
}

// Test direct login
echo "<h3>Test Direct Login (07001 / 1234)</h3>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = '07001'");
$stmt->execute();
$user = $stmt->fetch();

if ($user && password_verify('1234', $user['pin_hash'])) {
    echo '<span style="color:green">✓ Login would succeed!</span><br>';
    echo "Redirect would be: " . ($user['user_type'] === 'Merchant' ? 'merchant.php' : ($user['user_type'] === 'Admin' ? 'admin/index.php' : 'student.php')) . "<br>";
} else {
    echo '<span style="color:red">✗ Login would fail</span><br>';
}
?>
