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
$action = $data['action'] ?? 'send';

try {
    if ($action === 'send') {
        // Send money request
        $recipientPhone = $data['recipient_phone'] ?? '';
        $amount = floatval($data['amount'] ?? 0);
        $note = $data['note'] ?? '';

        if (!$recipientPhone || $amount <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request details']);
            exit;
        }

        // Get recipient ID
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE phone_number = ?");
        $stmt->execute([$recipientPhone]);
        $recipient = $stmt->fetch();

        if (!$recipient) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        // Create money request
        $requestCode = 'REQ-' . strtoupper(substr(uniqid(), -8));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $pdo->prepare("
            INSERT INTO money_requests (request_code, requester_id, payer_id, amount, note, expires_at, status)
            VALUES (?, ?, ?, ?, ?, ?, 'PENDING')
        ");
        $stmt->execute([$requestCode, $userId, $recipient['user_id'], $amount, $note, $expiresAt]);

        echo json_encode([
            'success' => true,
            'request_code' => $requestCode,
            'message' => "Request sent to {$recipient['full_name']}"
        ]);

    } else if ($action === 'list_received') {
        // Get pending requests where user is the payer
        $stmt = $pdo->prepare("
            SELECT mr.*, u.full_name as requester_name, u.phone_number
            FROM money_requests mr
            JOIN users u ON mr.requester_id = u.user_id
            WHERE mr.payer_id = ? AND mr.status = 'PENDING' AND mr.expires_at > NOW()
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute([$userId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'requests' => $requests]);

    } else if ($action === 'pay') {
        $requestCode = $data['request_code'] ?? '';
        $pin = $data['pin'] ?? '';

        // Get request details
        $stmt = $pdo->prepare("
            SELECT mr.*, u.full_name as requester_name
            FROM money_requests mr
            JOIN users u ON mr.requester_id = u.user_id
            WHERE mr.request_code = ? AND mr.payer_id = ? AND mr.status = 'PENDING'
        ");
        $stmt->execute([$requestCode, $userId]);
        $request = $stmt->fetch();

        if (!$request) {
            http_response_code(404);
            echo json_encode(['error' => 'Request not found or already processed']);
            exit;
        }

        if (strtotime($request['expires_at']) < time()) {
            http_response_code(400);
            echo json_encode(['error' => 'Request has expired']);
            exit;
        }

        // Verify PIN and process payment
        $stmt = $pdo->prepare("SELECT wallet_balance, pin_hash FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $payer = $stmt->fetch();

        if ((!defined('SIMULATION_MODE') || !SIMULATION_MODE) && !password_verify($pin, $payer['pin_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid PIN']);
            exit;
        }

        if ((!defined('SIMULATION_MODE') || !SIMULATION_MODE) && $payer['wallet_balance'] < $request['amount']) {
            http_response_code(402);
            echo json_encode(['error' => 'Insufficient funds']);
            exit;
        }

        $pdo->beginTransaction();

        // SIMULATION: Top up payer if needed
        if (defined('SIMULATION_MODE') && SIMULATION_MODE && $payer['wallet_balance'] < $request['amount']) {
             $topUp = $request['amount'] - $payer['wallet_balance'] + 1000;
             $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?")->execute([$topUp, $userId]);
        }

        // Transfer funds
        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?")
            ->execute([$request['amount'], $userId]);
        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?")
            ->execute([$request['amount'], $request['requester_id']]);

        // Create transaction
        $ref = 'REQPAY-' . strtoupper(substr(uniqid(), -6));
        $pdo->prepare("
            INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description)
            VALUES ('REQUEST_PAY', ?, ?, ?, ?, ?)
        ")->execute([$userId, $request['requester_id'], $request['amount'], $ref, "Payment for: {$request['note']}"]);

        // Update request status
        $pdo->prepare("UPDATE money_requests SET status = 'PAID', paid_at = NOW() WHERE request_code = ?")
            ->execute([$requestCode]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'reference' => $ref,
            'message' => "Payment sent to {$request['requester_name']}"
        ]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
