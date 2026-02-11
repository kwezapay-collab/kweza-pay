<?php
require_once 'api/db.php';
$phone = '09900';

// 1. Force Update
$stmt = $pdo->prepare("UPDATE users SET user_type = 'StudentUnion' WHERE phone_number = ?");
$stmt->execute([$phone]);

// 2. Check
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE phone_number = ?");
$stmt->execute([$phone]);
$res = $stmt->fetch();

echo "User Type for 09900 is now: [" . $res['user_type'] . "]";
?>
