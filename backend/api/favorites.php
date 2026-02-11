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
$action = $data['action'] ?? 'list';

try {
    if ($action === 'list') {
        // Get favorite merchants
        $stmt = $pdo->prepare("
            SELECT m.merchant_id, m.business_name, m.qr_code_token, u.user_id,
                   f.last_amount, f.created_at as favorited_at
            FROM favorites f
            JOIN merchants m ON f.merchant_id = m.merchant_id
            JOIN users u ON m.user_id = u.user_id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$userId]);
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'favorites' => $favorites]);

    } else if ($action === 'add') {
        $merchantToken = $data['merchant_token'] ?? '';
        $lastAmount = floatval($data['last_amount'] ?? 0);

        // Get merchant ID from token
        $stmt = $pdo->prepare("SELECT merchant_id FROM merchants WHERE qr_code_token = ?");
        $stmt->execute([$merchantToken]);
        $merchantId = $stmt->fetchColumn();

        if (!$merchantId) {
            http_response_code(404);
            echo json_encode(['error' => 'Merchant not found']);
            exit;
        }

        // Add to favorites (or update if exists)
        $stmt = $pdo->prepare("
            INSERT INTO favorites (user_id, merchant_id, last_amount)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE last_amount = ?, created_at = NOW()
        ");
        $stmt->execute([$userId, $merchantId, $lastAmount, $lastAmount]);

        echo json_encode(['success' => true, 'message' => 'Added to favorites']);

    } else if ($action === 'remove') {
        $merchantId = intval($data['merchant_id'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND merchant_id = ?");
        $stmt->execute([$userId, $merchantId]);

        echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
