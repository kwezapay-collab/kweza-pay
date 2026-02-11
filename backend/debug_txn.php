<?php
require_once 'api/db.php';
$stmt = $pdo->prepare("SELECT txn_type, amount, sender_id, receiver_id, created_at FROM transactions WHERE sender_id = ? OR receiver_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([6, 6]);
while ($row = $stmt->fetch()) {
    echo $row['txn_type'] . ': ' . $row['amount'] . ' (from ' . $row['sender_id'] . ' to ' . $row['receiver_id'] . ') at ' . $row['created_at'] . "\n";
}
?>