<?php
require_once '../../backend/api/db.php';

header('Content-Type: application/json');

// Only allow POST requests (In real app, add Admin Auth check here)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$phone = $data['phone_number'] ?? '';
$fullName = $data['full_name'] ?? '';
$initialPin = $data['pin'] ?? '1234'; // Default PIN if not provided
$type = $data['user_type'] ?? 'Student'; // Student or Merchant

if (!$phone || !$fullName) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// 1. Check if user exists
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone_number = ?");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'User already registered']);
    exit;
}

// 2. Hash PIN
$pinHash = password_hash($initialPin, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    // 3. Create User Record
    $stmt = $pdo->prepare("INSERT INTO users (phone_number, full_name, pin_hash, user_type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$phone, $fullName, $pinHash, $type]);
    $userId = $pdo->lastInsertId();

    $response = [
        'message' => "$type registered successfully",
        'user_id' => $userId,
        'initial_pin' => $initialPin 
    ];

    // 4. Handle Merchant Specifics
    if ($type === 'Merchant') {
        $businessName = $data['business_name'] ?? $fullName;
        
        // Generate Unique Token
        $token = '';
        do {
            $token = 'KZA-' . mt_rand(10000000, 99999999);
            $check = $pdo->prepare("SELECT merchant_id FROM merchants WHERE qr_code_token = ?");
            $check->execute([$token]);
        } while ($check->fetch());

        $stmt = $pdo->prepare("INSERT INTO merchants (user_id, business_name, qr_code_token) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $businessName, $token]);

        $response['qr_token'] = $token;
    }

    $pdo->commit();
    echo json_encode($response);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
?>
