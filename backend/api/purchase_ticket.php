<?php
require_once 'session.php';
require_once 'db.php';
requireLogin();

header('Content-Type: application/json');

function ensureEventTicketInventoryTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_ticket_inventory (
            inventory_id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            serial_number VARCHAR(100) NOT NULL,
            is_assigned TINYINT(1) NOT NULL DEFAULT 0,
            assigned_at DATETIME NULL,
            ticket_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_event_serial (event_id, serial_number),
            UNIQUE KEY uniq_inventory_ticket (ticket_id),
            INDEX idx_event_assignment (event_id, is_assigned),
            FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
            FOREIGN KEY (ticket_id) REFERENCES event_tickets(ticket_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

$user = getCurrentUser($pdo);
$data = json_decode(file_get_contents('php://input'), true);

$event_id = $data['event_id'] ?? 0;
$pin = $data['pin'] ?? '';

try {
    // Verify PIN
    if ($pin !== '' && !password_verify($pin, $user['pin_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
        exit;
    }
    
    // Get event details
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ? AND is_active = 1");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        echo json_encode(['success' => false, 'error' => 'Event not found or inactive']);
        exit;
    }
    
    // Check if max tickets reached
    if ($event['max_tickets'] && $event['tickets_sold'] >= $event['max_tickets']) {
        echo json_encode(['success' => false, 'error' => 'Event is sold out']);
        exit;
    }

    // Ensure optional ticket inventory table exists before inventory lookup.
    ensureEventTicketInventoryTable($pdo);
    
    // Generate unique ticket code
    $ticket_code = 'TKT-' . strtoupper(uniqid());
    $reference_code = 'REF-' . strtoupper(uniqid());
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Pick an unassigned ticket ID if available
    $stmt = $pdo->prepare("SELECT inventory_id, serial_number FROM event_ticket_inventory WHERE event_id = ? AND is_assigned = 0 LIMIT 1 FOR UPDATE");
    $stmt->execute([$event_id]);
    $inv_item = $stmt->fetch();
    
    // Create ticket
    $stmt = $pdo->prepare("
        INSERT INTO event_tickets (event_id, user_id, ticket_code, purchase_amount, qr_code_data)
        VALUES (?, ?, ?, ?, ?)
    ");
    $qr_data = json_encode([
        'ticket_code' => $ticket_code,
        'event_id' => $event_id,
        'user_id' => $user['user_id'],
        'serial_number' => $inv_item['serial_number'] ?? null
    ]);
    $stmt->execute([$event_id, $user['user_id'], $ticket_code, $event['ticket_price'], $qr_data]);
    $new_ticket_id = $pdo->lastInsertId();
    
    // Assign inventory item if found
    if ($inv_item) {
        $stmt = $pdo->prepare("UPDATE event_ticket_inventory SET is_assigned = 1, assigned_at = CURRENT_TIMESTAMP, ticket_id = ? WHERE inventory_id = ?");
        $stmt->execute([$new_ticket_id, $inv_item['inventory_id']]);
    }
    
    // Update tickets sold count
    $stmt = $pdo->prepare("UPDATE events SET tickets_sold = tickets_sold + 1 WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    // Create transaction record
    $stmt = $pdo->prepare("
        INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description)
        VALUES ('QR_PAY', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['user_id'],
        $event['created_by'] ?? 1,
        $event['ticket_price'],
        $reference_code,
        'Event Ticket: ' . $event['event_name']
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Ticket purchased successfully',
        'ticket_code' => $ticket_code,
        'serial_number' => $inv_item['serial_number'] ?? null,
        'reference_code' => $reference_code,
        'event_name' => $event['event_name'],
        'amount' => $event['ticket_price'],
        'event_date' => $event['event_date'],
        'event_location' => $event['event_location']
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
