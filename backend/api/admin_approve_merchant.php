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
$adminUser = getCurrentUser($pdo);
if ($adminUser['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$targetUserId = $data['user_id'] ?? null;
$action = $data['action'] ?? 'approve'; // approve or reject

if (!$targetUserId) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        // Update merchant status
        $stmt = $pdo->prepare("UPDATE merchants SET is_approved = 1 WHERE user_id = ?");
        $stmt->execute([$targetUserId]);

        // Change user type to Merchant (in case they were a student)
        $stmt = $pdo->prepare("UPDATE users SET user_type = 'Merchant' WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        
        $message = "Merchant application approved successfully.";
    } else {
        // Reject - maybe just delete the merchant record or mark as rejected
        // For simplicity, let's keep the user as student (or whatever they were) and remove the merchant record
        $stmt = $pdo->prepare("DELETE FROM merchants WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        $message = "Merchant application rejected.";
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
