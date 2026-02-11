<?php
require_once 'session.php';
require_once 'db.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser($pdo);
$data = json_decode(file_get_contents('php://input'), true);

$cafe_id = $data['cafe_id'] ?? 0;
$amount = $data['amount'] ?? 0;
$description = $data['description'] ?? '';
$pin = $data['pin'] ?? '';

try {
    // Verify PIN
    if ($pin !== '' && !password_verify($pin, $user['pin_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit;
    }
    
    // Get cafe details
    $stmt = $pdo->prepare("SELECT * FROM campus_cafes WHERE cafe_id = ? AND is_active = 1");
    $stmt->execute([$cafe_id]);
    $cafe = $stmt->fetch();
    
    if (!$cafe) {
        echo json_encode(['success' => false, 'error' => 'Cafe not found or inactive']);
        exit;
    }
    
    // Generate reference code
    $reference_code = 'CAFE-' . strtoupper(uniqid());
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Create cafe transaction
    $stmt = $pdo->prepare("
        INSERT INTO cafe_transactions (cafe_id, user_id, amount, reference_code, description, payment_method)
        VALUES (?, ?, ?, ?, ?, 'airtel_money')
    ");
    $stmt->execute([
        $cafe_id,
        $user['user_id'],
        $amount,
        $reference_code,
        $description ?: 'Campus Cafe Payment'
    ]);
    
    // Create general transaction record
    $stmt = $pdo->prepare("
        INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description)
        VALUES ('QR_PAY', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['user_id'],
        $cafe['created_by'] ?? 1,
        $amount,
        $reference_code,
        'Campus Cafe: ' . $cafe['cafe_name']
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment successful',
        'reference_code' => $reference_code,
        'cafe_name' => $cafe['cafe_name'],
        'amount' => $amount,
        'airtel_code' => $cafe['airtel_money_code']
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
