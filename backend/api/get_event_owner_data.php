<?php
require_once 'db.php';

header('Content-Type: application/json');

$token = $_GET['token'] ?? null;

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Token is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE owner_token = ?");
    $stmt->execute([$token]);
    $event = $stmt->fetch();

    if (!$event) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }

    $event_id = $event['event_id'];

    // Get sales stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as sold_count, SUM(purchase_amount) as total_revenue FROM event_tickets WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $stats = $stmt->fetch();

    // Get ticket inventory stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_ids, SUM(CASE WHEN is_assigned = 1 THEN 1 ELSE 0 END) as assigned_ids FROM event_ticket_inventory WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $inv_stats = $stmt->fetch();

    // Get recent sales
    $stmt = $pdo->prepare("
        SELECT t.*, u.full_name, i.serial_number 
        FROM event_tickets t 
        JOIN users u ON t.user_id = u.user_id 
        LEFT JOIN event_ticket_inventory i ON t.ticket_id = i.ticket_id
        WHERE t.event_id = ? 
        ORDER BY t.purchased_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$event_id]);
    $recent_sales = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'event' => $event,
        'stats' => [
            'sold' => (int)$stats['sold_count'],
            'revenue' => (float)$stats['total_revenue'],
            'total_ids' => (int)$inv_stats['total_ids'],
            'assigned_ids' => (int)$inv_stats['assigned_ids'],
            'remaining_ids' => (int)$inv_stats['total_ids'] - (int)$inv_stats['assigned_ids']
        ],
        'recent_sales' => $recent_sales
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
