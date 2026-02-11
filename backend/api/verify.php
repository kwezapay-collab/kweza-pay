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
$code = trim($data['verification_code'] ?? '');
$userType = trim($data['user_type'] ?? ''); // New field to disambiguate

if (!$phone || !$code) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Phone number and verification code are required']);
    exit;
}

try {
    // Get user with verification code - check user_type if provided
    $sql = "SELECT user_id, verification_code, verification_expires_at, is_verified FROM users WHERE phone_number = ?";
    $params = [$phone];
    
    if ($userType) {
        $sql .= " AND user_type = ?";
        $params[] = $userType;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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

    // Check if code matches
    if ($user['verification_code'] !== $code) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
        exit;
    }

    // Check if code has expired
    if (strtotime($user['verification_expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Verification code has expired. Please request a new one.']);
        exit;
    }

    // Mark user as verified
    $stmt = $pdo->prepare("
        UPDATE users 
        SET is_verified = 1, 
            verification_code = NULL, 
            verification_expires_at = NULL 
        WHERE user_id = ?
    ");
    $stmt->execute([$user['user_id']]);

    // Begin session for auto-login
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_type'] = $userType; // Assuming user_type was successfully matched
    
    // Fallback if userType wasn't explicitly passed but we found a user
    if (!$userType) {
        $check = $pdo->prepare("SELECT user_type FROM users WHERE user_id = ?");
        $check->execute([$user['user_id']]);
        $_SESSION['user_type'] = $check->fetchColumn();
    }

    $redirect = 'student.php';
    if ($_SESSION['user_type'] === 'Merchant') $redirect = 'merchant.php';
    elseif ($_SESSION['user_type'] === 'Admin') $redirect = 'admin/index.php';
    elseif ($_SESSION['user_type'] === 'StudentUnion') $redirect = 'student_union.php';
    elseif ($_SESSION['user_type'] === 'Person') $redirect = 'person.php';

    echo json_encode([
        'success' => true,
        'message' => 'Account verified successfully! Logging you in...',
        'redirect' => $redirect
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Verification failed. Please try again.',
        'debug' => $e->getMessage() // Remove in production
    ]);
}
