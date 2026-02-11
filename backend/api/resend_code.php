<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$phone = trim($data['phone'] ?? '');

if (!$phone) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Phone number is required']);
    exit;
}

try {
    // Get user
    $stmt = $pdo->prepare("SELECT user_id, is_verified, email, full_name FROM users WHERE phone_number = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    if ($user['is_verified']) {
        echo json_encode(['success' => false, 'error' => 'Account is already verified']);
        exit;
    }

    // Generate new verification code
    $verificationCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Update user with new code
    $stmt = $pdo->prepare("
        UPDATE users 
        SET verification_code = ?, 
            verification_expires_at = ? 
        WHERE user_id = ?
    ");
    $stmt->execute([$verificationCode, $expiresAt, $user['user_id']]);

    // Send real verification email
    require_once 'mail_helper.php';
    sendVerificationEmail($user['email'], $user['full_name'], $verificationCode);

    echo json_encode([
        'success' => true,
        'message' => 'Verification code has been resent to your email',
        // For development only - remove in production
        'verification_code' => $verificationCode
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to resend code. Please try again.',
        'debug' => $e->getMessage() // Remove in production
    ]);
}
?>
