<?php
require_once 'session.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    $is_admin = false;
    if (isset($_SESSION['user_id'])) {
        $user = getCurrentUser($pdo);
        $is_admin = ($user['user_type'] === 'Admin');
    }
    
    $query = "SELECT * FROM events";
    if (!$is_admin) {
        $query .= " WHERE is_active = 1";
    }
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->query($query);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Attach QR image URL if missing but Airtel code exists
    foreach ($events as &$event) {
        if (!empty($event['airtel_money_code']) && empty($event['airtel_qr_image'])) {
            $event['airtel_qr_image'] = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($event['airtel_money_code']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
