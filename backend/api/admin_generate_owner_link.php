<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

if ($_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['event_id'] ?? null;

if (!$event_id) {
    echo json_encode(['success' => false, 'error' => 'Event ID is required']);
    exit;
}

try {
    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE events SET owner_token = ? WHERE event_id = ?");
    $stmt->execute([$token, $event_id]);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'link' => "frontend/event_owner.php?token=" . $token
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
