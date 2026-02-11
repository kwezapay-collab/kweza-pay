<?php
require_once 'session.php';
require_once 'db.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser($pdo);

try {
    $stmt = $pdo->prepare("
        SELECT et.*, 
               e.event_name, 
               e.event_date, 
               e.event_location, 
               e.event_picture,
               e.airtel_money_code,
               e.airtel_money_id,
               e.airtel_qr_image,
               inv.serial_number
        FROM event_tickets et
        JOIN events e ON et.event_id = e.event_id
        LEFT JOIN event_ticket_inventory inv ON et.ticket_id = inv.ticket_id
        WHERE et.user_id = ?
        ORDER BY et.purchased_at DESC
    ");
    $stmt->execute([$user['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tickets' => $tickets
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
