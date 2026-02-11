<?php
require_once 'api/db.php';
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = 4");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "User ID: " . $user['user_id'] . "\n";
echo "Name: " . $user['full_name'] . "\n";
echo "Type: '" . $user['user_type'] . "'\n"; // Quote to see leading/trailing spaces
echo "Phone: " . $user['phone_number'] . "\n";
?>
