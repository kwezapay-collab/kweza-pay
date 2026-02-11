<?php
require_once 'api/db.php';

echo "Checking for Student Union users...\n";

$stmt = $pdo->query("SELECT * FROM users WHERE user_type = 'StudentUnion'");
$sus = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($sus) > 0) {
    echo "Found " . count($sus) . " SU users:\n";
    foreach ($sus as $u) {
        echo " - {$u['full_name']} (Phone: {$u['phone_number']})\n";
    }
} else {
    echo "No SU users found. Creating one...\n";
    // Create SU User
    $phone = '09900';
    $pin = password_hash('1234', PASSWORD_DEFAULT);
    $name = 'Student Union President';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, phone_number, pin_hash, user_type, wallet_balance) VALUES (?, ?, ?, 'StudentUnion', 500000)");
        $stmt->execute([$name, $phone, $pin]);
        echo "Created SU User: $name ($phone) / Pin: 1234\n";
    } catch (PDOException $e) {
        echo "Error creating user: " . $e->getMessage() . "\n";
    }
}
?>
