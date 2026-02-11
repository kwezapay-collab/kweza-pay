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

$user = getCurrentUser($pdo);
if ($user['user_type'] !== 'Merchant') {
    http_response_code(403);
    echo json_encode(['error' => 'Access Denied']);
    exit;
}

// Fetch Merchant creation date
$stmt = $pdo->prepare("SELECT created_at FROM merchants WHERE user_id = ?");
$stmt->execute([$user['user_id']]);
$merchant = $stmt->fetch();

if (!$merchant) {
    echo json_encode(['eligible' => false, 'error' => 'Merchant profile not found']);
    exit;
}

$createdAt = new DateTime($merchant['created_at']);
$now = new DateTime();
$interval = $createdAt->diff($now);
$daysOld = $interval->days;

$isEligible = $daysOld >= 14;
$availableDate = $createdAt->modify('+14 days')->format('Y-m-d');

echo json_encode([
    'eligible' => $isEligible,
    'days_old' => $daysOld,
    'available_date' => $availableDate
]);
?>
