<?php
require_once 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? null;
$ticket_ids_str = $data['ticket_ids'] ?? ''; // Newline separated IDs

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
    $ids = array_filter(array_map('trim', explode("\n", $ticket_ids_str)));

    if (empty($ids)) {
        echo json_encode(['success' => false, 'error' => 'No IDs provided']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT IGNORE INTO event_ticket_inventory (event_id, serial_number) VALUES (?, ?)");
    foreach ($ids as $id) {
        $stmt->execute([$event_id, $id]);
    }

    // Update max_tickets based on total (non-deleted) IDs in inventory
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_ticket_inventory WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $total_ids = $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE events SET max_tickets = ? WHERE event_id = ?");
    $stmt->execute([$total_ids, $event_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'total_ids' => $total_ids]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
