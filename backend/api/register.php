<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Extract and validate required fields
$userTypes = $data['user_types'] ?? ['Student'];
$fullName = trim($data['full_name'] ?? '');
$phone = trim($data['phone'] ?? '');
$email = trim($data['email'] ?? '');
$pin = $data['pin'] ?? '';
$university = trim($data['university'] ?? '');

// Validate required fields
if (!$fullName || !$phone || !$email || !$pin) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Validate PIN format
if (!preg_match('/^\d{4}$/', $pin)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'PIN must be exactly 4 digits']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

try {
    // Check if column exists and rename it if necessary (backward compatibility)
    try {
        $pdo->exec("ALTER TABLE users CHANGE school university VARCHAR(255) NULL");
    } catch (Exception $e) {
        // Column might already be renamed or doesn't exist
    }

    // Create user_roles table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role ENUM('Person', 'Student', 'Merchant', 'Admin', 'StudentUnion') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (user_id, role),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    // Check if phone or email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone_number = ? OR email = ?");
    $stmt->execute([$phone, $email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => "This phone number or email is already registered."]);
        exit;
    }

    // Generate 6-digit verification code
    $verificationCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Set expiration time (15 minutes from now)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Hash the PIN
    $pinHash = password_hash($pin, PASSWORD_BCRYPT);

    // Begin transaction
    $pdo->beginTransaction();

    // Insert user (using 'Person' as default user_type in users table for backward compat)
    $primaryType = in_array('Person', $userTypes) ? 'Person' : $userTypes[0];

    $stmt = $pdo->prepare("
        INSERT INTO users (
            phone_number, 
            full_name, 
            email, 
            university,
            pin_hash, 
            user_type, 
            verification_code, 
            verification_expires_at,
            is_verified,
            wallet_balance
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0.00)
    ");

    $stmt->execute([
        $phone,
        $fullName,
        $email,
        $university,
        $pinHash,
        $primaryType,
        $verificationCode,
        $expiresAt
    ]);

    $userId = $pdo->lastInsertId();

    // Insert roles into user_roles
    foreach ($userTypes as $role) {
        $stmtRole = $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
        $stmtRole->execute([$userId, $role]);

        // If merchant, create merchant record
        if ($role === 'Merchant') {
            $businessName = trim($data['business_name'] ?? $fullName);
            // Generate unique QR token
            $qrToken = '';
            do {
                $qrToken = 'KZA-' . mt_rand(10000000, 99999999);
                $check = $pdo->prepare("SELECT merchant_id FROM merchants WHERE qr_code_token = ?");
                $check->execute([$qrToken]);
            } while ($check->fetch());

            $stmtMerchant = $pdo->prepare("
                INSERT INTO merchants (user_id, business_name, qr_code_token, is_approved, fee_paid) 
                VALUES (?, ?, ?, 0, 1)
            ");
            $stmtMerchant->execute([$userId, $businessName, $qrToken]);
        }
    }

    $pdo->commit();

    // Send real verification email
    require_once 'mail_helper.php';
    sendVerificationEmail($email, $fullName, $verificationCode);

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Please check your email for the verification code.',
        'user_id' => $userId,
        // For development only - remove in production
        'verification_code' => $verificationCode
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Registration failed. Please try again later.',
        'debug' => $e->getMessage() // Remove in production
    ]);
}
?>