<?php
require_once 'db.php';
require_once 'session.php';
require_once __DIR__ . '/../services/PaymentGatewayService.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$provider = $data['provider'] ?? '';
$account = $data['account'] ?? '';
$amount = floatval($data['amount'] ?? 0);
$pin = $data['pin'] ?? '';

if (!$provider || !$account || $amount <= 0 || !$pin) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // 1. Verify User & PIN
    $stmt = $pdo->prepare("SELECT wallet_balance, pin_hash FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pin, $user['pin_hash'])) {
        throw new Exception('Invalid PIN');
    }

    $WITHDRAWAL_FEE = 50.00;
    $totalDeduction = $amount + $WITHDRAWAL_FEE;

    if ((!defined('SIMULATION_MODE') || !SIMULATION_MODE) && $user['wallet_balance'] < $totalDeduction) {
        throw new Exception('Insufficient funds (Amount + 50 MWK Fee)');
    }

    // 2. Process Withdrawal
    $pdo->beginTransaction();
    
    // SIMULATION: Top up to avoid constraint violation
    if (defined('SIMULATION_MODE') && SIMULATION_MODE && $user['wallet_balance'] < $totalDeduction) {
        $topUp = $totalDeduction - $user['wallet_balance'] + 1000;
        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?")->execute([$topUp, $userId]);
    }

    // Deduct from User
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
    $stmt->execute([$totalDeduction, $userId]);

    // Credit Fee to System Admin (User 4)
    $SYSTEM_ADMIN_ID = 4;
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
    $stmt->execute([$WITHDRAWAL_FEE, $SYSTEM_ADMIN_ID]);

    // Record Main Withdrawal Transaction
    $ref = 'WD-' . strtoupper(substr(uniqid(), -6));
    $stmt = $pdo->prepare("INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description) VALUES ('WITHDRAWAL', ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $userId, $amount, $ref, "Withdrawal to $provider ($account)"]);

    // Record System Fee Transaction
    $feeRef = 'FEE-' . strtoupper(substr(uniqid(), -6));
    $stmt = $pdo->prepare("INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description) VALUES ('SYSTEM_FEE', ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $SYSTEM_ADMIN_ID, $WITHDRAWAL_FEE, $feeRef, "Fee for Withdrawal $ref"]);

    $pdo->commit();

    echo json_encode(['success' => true, 'reference' => $ref, 'fee' => $WITHDRAWAL_FEE]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
