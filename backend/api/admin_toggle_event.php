<?php
require_once 'session.php';
require_once 'db.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser($pdo);
if ($user['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['event_id'] ?? 0;
$is_active = $data['is_active'] ?? 0;

try {
    $stmt = $pdo->prepare("UPDATE events SET is_active = ? WHERE event_id = ?");
    $stmt->execute([$is_active ? 1 : 0, $event_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
