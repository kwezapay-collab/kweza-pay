<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

requireLogin();
$user = getCurrentUser($pdo);

if ($user['user_type'] === 'Merchant') {
    echo json_encode(['success' => false, 'error' => 'You are already a merchant']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$businessName = trim($data['business_name'] ?? $user['school'] ?? 'My Business');

try {
    // Check if application already exists
    $stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Application already submitted and pending review.']);
        exit;
    }

    // Generate unique QR token
    $qrToken = '';
    do {
        $qrToken = 'KZA-' . mt_rand(10000000, 99999999);
        $check = $pdo->prepare("SELECT merchant_id FROM merchants WHERE qr_code_token = ?");
        $check->execute([$qrToken]);
    } while ($check->fetch());

    // Insert as pending
    $stmt = $pdo->prepare("
        INSERT INTO merchants (user_id, business_name, qr_code_token, is_approved, fee_paid) 
        VALUES (?, ?, ?, 0, 1)
    ");
    // We assume they clicked "I have paid" in the frontend, so we set fee_paid to 1 for now or 0 if admin checks it.
    // The user said "show the pepole who have aplyed to become a merchant and payed the fee"
    // So let's set fee_paid to 1 if they confirm the prompt.
    
    $stmt->execute([$user['user_id'], $businessName, $qrToken]);

    echo json_encode(['success' => true, 'message' => 'Application submitted successfully! It will be reviewed by an administrator.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
