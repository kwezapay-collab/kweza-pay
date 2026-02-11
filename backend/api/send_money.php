<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$senderId = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

$recipientPhone = trim((string)($data['recipient_phone'] ?? ''));
$amount = (float)($data['amount'] ?? 0);
$pin = (string)($data['pin'] ?? '');
$isSimulation = defined('SIMULATION_MODE') && SIMULATION_MODE;

if ($recipientPhone === '' || $amount <= 0 || (!$isSimulation && $pin === '')) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // 1. Verify Sender & PIN
    $stmt = $pdo->prepare("SELECT user_id, phone_number, wallet_balance, pin_hash, full_name, user_type FROM users WHERE user_id = ?");
    $stmt->execute([$senderId]);
    $sender = $stmt->fetch();

    if (!$sender) {
        http_response_code(404);
        echo json_encode(['error' => 'Sender not found']);
        exit;
    }

    if ($recipientPhone === $sender['phone_number']) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot send money to yourself']);
        exit;
    }

    if (!$isSimulation && !password_verify($pin, $sender['pin_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid PIN']);
        exit;
    }

    if (!$isSimulation && $sender['wallet_balance'] < $amount) {
        http_response_code(402);
        echo json_encode(['error' => 'Insufficient Funds']);
        exit;
    }

    // 2. Identify Recipient
    $stmt = $pdo->prepare("SELECT user_id, full_name, user_type FROM users WHERE phone_number = ?");
    $stmt->execute([$recipientPhone]);
    $recipient = $stmt->fetch();

    if (!$recipient && !$isSimulation) {
        http_response_code(404);
        echo json_encode(['error' => 'Recipient not found']);
        exit;
    }

    if ($recipient && (int)$recipient['user_id'] === $senderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot send money to yourself']);
        exit;
    }

    // 3. Process Transaction (Free)
    $pdo->beginTransaction();

    // SIMULATION: Create recipient account on the fly for unknown numbers
    if (!$recipient && $isSimulation) {
        $lastDigits = preg_replace('/\D+/', '', $recipientPhone);
        $lastDigits = substr($lastDigits, -4);
        $generatedName = $lastDigits ? ("Sim Recipient " . $lastDigits) : "Sim Recipient";
        $generatedPin = password_hash(uniqid('sim-', true), PASSWORD_DEFAULT);

        try {
            $createRecipient = $pdo->prepare("
                INSERT INTO users (phone_number, full_name, pin_hash, user_type, wallet_balance, is_verified)
                VALUES (?, ?, ?, 'Person', 0.00, 1)
            ");
            $createRecipient->execute([$recipientPhone, $generatedName, $generatedPin]);
        } catch (PDOException $e) {
            // If a concurrent request created it first, continue and fetch it below.
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }

        $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE phone_number = ?");
        $stmt->execute([$recipientPhone]);
        $recipient = $stmt->fetch();

        if (!$recipient) {
            throw new Exception('Unable to create simulation recipient');
        }
    }

    // SIMULATION: Top up to avoid constraint violation
    if ($isSimulation && $sender['wallet_balance'] < $amount) {
         $topUp = $amount - $sender['wallet_balance'] + 1000;
         $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?")->execute([$topUp, $senderId]);
    }

    // Deduct Sender
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
    $stmt->execute([$amount, $senderId]);

    // Add to Recipient
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
    $stmt->execute([$amount, $recipient['user_id']]);

    // Record Txn
    $ref = 'P2P-' . strtoupper(substr(uniqid(), -6));
    $description = "Money sent to {$recipient['full_name']} ({$recipientPhone})";
    $stmt = $pdo->prepare("INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description) VALUES ('P2P', ?, ?, ?, ?, ?)");
    $stmt->execute([$senderId, $recipient['user_id'], $amount, $ref, $description]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'reference_code' => $ref,
        'recipient_name' => $recipient['full_name'],
        'recipient_phone' => $recipientPhone,
        'amount' => $amount,
        'simulation_mode' => $isSimulation,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
