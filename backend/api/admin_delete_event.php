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

try {
    // Remove assets linked to the event
    $fetch = $pdo->prepare("SELECT event_picture, airtel_qr_image FROM events WHERE event_id = ?");
    $fetch->execute([$event_id]);
    $event = $fetch->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        foreach (['event_picture', 'airtel_qr_image'] as $key) {
            if (!empty($event[$key])) {
                $path = dirname(__DIR__) . '/' . $event[$key];
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }

    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
