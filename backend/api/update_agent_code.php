<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$agentCode = $data['agent_code'] ?? null;
$userId = $_SESSION['user_id'];

try {
    // Check if user has a merchant record
    $stmt = $pdo->prepare("SELECT merchant_id FROM merchants WHERE user_id = ?");
    $stmt->execute([$userId]);
    $merchant = $stmt->fetch();

    if (!$merchant) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Merchant profile not found']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE merchants SET agent_code = ? WHERE user_id = ?");
    $stmt->execute([$agentCode, $userId]);

    echo json_encode([
        'success' => true, 
        'message' => $agentCode ? 'Merchant ID updated successfully' : 'Merchant ID cleared'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
