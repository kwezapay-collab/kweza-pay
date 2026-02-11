<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

// Ensure Login
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$refNumber = $_GET['ref'] ?? '';

if (!$refNumber) {
    http_response_code(400);
    echo json_encode(['error' => 'Reference number required']);
    exit;
}

try {
    // Search for transaction by reference code
    $stmt = $pdo->prepare("
        SELECT t.*, u.full_name, u.phone_number 
        FROM transactions t
        JOIN users u ON t.sender_id = u.user_id
        WHERE UPPER(t.reference_code) = UPPER(?) AND t.txn_type = 'SU_FEE'
    ");
    $stmt->execute([$refNumber]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['error' => 'Payment not found', 'found' => false]);
        exit;
    }

    echo json_encode([
        'found' => true,
        'reference' => $transaction['reference_code'],
        'student_name' => $transaction['full_name'],
        'phone_number' => $transaction['phone_number'],
        'amount' => $transaction['amount'],
        'date' => $transaction['created_at'],
        'description' => $transaction['description']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
