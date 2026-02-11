<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$amount = floatval($data['amount'] ?? 0);
$provider = $data['provider'] ?? '';
$account = $data['account'] ?? '';
$pin = $data['pin'] ?? '';

if ($amount <= 0 || !$provider || !$account || !$pin) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // 1. Verify Merchant & Eligibility
    $stmt = $pdo->prepare("SELECT m.*, u.pin_hash, u.full_name FROM merchants m JOIN users u ON m.user_id = u.user_id WHERE m.user_id = ?");
    $stmt->execute([$userId]);
    $merchant = $stmt->fetch();

    if (!$merchant) {
        http_response_code(403);
        echo json_encode(['error' => 'Not a merchant account']);
        exit;
    }

    if (!password_verify($pin, $merchant['pin_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid PIN']);
        exit;
    }

    // Check 14-day rule
    $createdAt = new DateTime($merchant['created_at']);
    $now = new DateTime();
    $daysOld = $createdAt->diff($now)->days;

    if ($daysOld < 14) {
        http_response_code(403);
        echo json_encode(['error' => 'Commission withdrawals restricted for 14 days. Account age: ' . $daysOld . ' days.']);
        exit;
    }

    if ($merchant['commission_balance'] < $amount) {
        http_response_code(400);
        echo json_encode(['error' => 'Insufficient commission balance']);
        exit;
    }

    // 2. Process Withdrawal
    $pdo->beginTransaction();

    // Deduct Commission Balance
    $stmt = $pdo->prepare("UPDATE merchants SET commission_balance = commission_balance - ? WHERE merchant_id = ?");
    $stmt->execute([$amount, $merchant['merchant_id']]);

    // Record Transaction (Commission Withdrawal)
    $ref = 'CW-' . strtoupper(substr(uniqid(), -8));
    $stmt = $pdo->prepare("INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description) VALUES ('COMMISSION_WITHDRAWAL', ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $userId, $amount, $ref, "Commission Withdrawal to " . $provider . " (" . $account . ")"]);

    $pdo->commit();

    echo json_encode(['success' => true, 'reference' => $ref]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
