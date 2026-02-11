<?php
require_once 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? null;

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Token is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT event_id FROM events WHERE owner_token = ?");
    $stmt->execute([$token]);
    $event = $stmt->fetch();

    if (!$event) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }

    $event_id = $event['event_id'];

    $pdo->beginTransaction();

    // 1. Clear inventory (Only those not assigned, or all? User said "reset so that new data can be added")
    // If we clear assigned ones, we might break receipt viewing history.
    // Better to just clear EVERYTHING for that event if it's a "reset for new event" scenario.
    // But usually one event_id = one event instance.
    
    // Deleting tickets might be dangerous for accounting.
    // Let's just clear the inventory and reset tickets_sold.
    
    $pdo->prepare("DELETE FROM event_ticket_inventory WHERE event_id = ?")->execute([$event_id]);
    $pdo->prepare("UPDATE events SET tickets_sold = 0, max_tickets = 0 WHERE event_id = ?")->execute([$event_id]);
    
    // Note: event_tickets are NOT deleted to preserve transaction history, 
    // but they will no longer be linked to inventory.

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
