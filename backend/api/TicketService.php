<?php
// backend/api/TicketService.php

class TicketService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function ensureEventTicketInventoryTable(): void {
        $this->pdo->exec("
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

    public function issueTicket($event_id, $user_id, $amount, $payment_method = 'QR_PAY', $reference_code = null) {
        // Start transaction if not already started
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            // Get event details
            $stmt = $this->pdo->prepare("SELECT * FROM events WHERE event_id = ? AND is_active = 1");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch();
            
            if (!$event) {
                throw new Exception("Event not found or inactive");
            }
            
            // Check if max tickets reached
            if ($event['max_tickets'] && $event['tickets_sold'] >= $event['max_tickets']) {
                throw new Exception("Event is sold out");
            }

            // Ensure optional ticket inventory table exists before inventory lookup.
            $this->ensureEventTicketInventoryTable();
            
            // Generate unique ticket code
            $ticket_code = 'TKT-' . strtoupper(uniqid());
            if (!$reference_code) {
                $reference_code = 'REF-' . strtoupper(uniqid());
            }
            
            // Pick an unassigned ticket ID if available
            $stmt = $this->pdo->prepare("SELECT inventory_id, serial_number FROM event_ticket_inventory WHERE event_id = ? AND is_assigned = 0 LIMIT 1 FOR UPDATE");
            $stmt->execute([$event_id]);
            $inv_item = $stmt->fetch();
            
            // Create ticket JSON data for QR
            $qr_data = json_encode([
                'ticket_code' => $ticket_code,
                'event_id' => $event_id,
                'user_id' => $user_id,
                'serial_number' => $inv_item['serial_number'] ?? null,
                'issued_at' => time()
            ]);

            // Create ticket
            $stmt = $this->pdo->prepare("
                INSERT INTO event_tickets (event_id, user_id, ticket_code, purchase_amount, qr_code_data)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$event_id, $user_id, $ticket_code, $amount, $qr_data]);
            $new_ticket_id = $this->pdo->lastInsertId();
            
            // Assign inventory item if found
            if ($inv_item) {
                $stmt = $this->pdo->prepare("UPDATE event_ticket_inventory SET is_assigned = 1, assigned_at = CURRENT_TIMESTAMP, ticket_id = ? WHERE inventory_id = ?");
                $stmt->execute([$new_ticket_id, $inv_item['inventory_id']]);
            }
            
            // Update tickets sold count
            $stmt = $this->pdo->prepare("UPDATE events SET tickets_sold = tickets_sold + 1 WHERE event_id = ?");
            $stmt->execute([$event_id]);
            
            // Create transaction record
            $stmt = $this->pdo->prepare("
                INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $payment_method, // 'QR_PAY' or 'PAYCHANGU'
                $user_id,
                $event['created_by'] ?? 1,
                $amount,
                $reference_code,
                'Event Ticket: ' . $event['event_name']
            ]);
            
            if (!$inTransaction) {
                $this->pdo->commit();
            }
            
            return [
                'success' => true,
                'ticket_code' => $ticket_code,
                'serial_number' => $inv_item['serial_number'] ?? null,
                'reference_code' => $reference_code,
                'event_name' => $event['event_name'],
                'amount' => $amount,
                'event_date' => $event['event_date'],
                'event_location' => $event['event_location']
            ];

        } catch (Exception $e) {
            if (!$inTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
?>
