<?php
require_once 'session.php';
require_once 'db.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser($pdo);

// Check if user is admin
if ($user['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

try {
    $event_name = $_POST['event_name'] ?? '';
    $event_description = $_POST['event_description'] ?? '';
    $ticket_price = $_POST['ticket_price'] ?? 0;
    $event_date = $_POST['event_date'] ?? null;
    $event_location = $_POST['event_location'] ?? '';
    $airtel_money_code = $_POST['airtel_money_code'] ?? '';
    $airtel_money_id = $_POST['airtel_money_id'] ?? '';
    $max_tickets = $_POST['max_tickets'] ?? null;
    $ticket_template = $_POST['ticket_template'] ?? '';
    
    if (empty($event_name) || $ticket_price <= 0) {
        echo json_encode(['success' => false, 'error' => 'Event name and valid ticket price are required']);
        exit;
    }
    
    // Handle file upload
    $event_picture = null;
    if (isset($_FILES['event_picture']) && $_FILES['event_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/events/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['event_picture']['name'], PATHINFO_EXTENSION);
        $file_name = 'event_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['event_picture']['tmp_name'], $file_path)) {
            $event_picture = 'uploads/events/' . $file_name;
        }
    }

    // Generate Airtel Money QR (stored as image) when code is provided
    $airtel_qr_image = null;
    if (!empty($airtel_money_code)) {
        $qr_dir = '../uploads/events/qr/';
        if (!file_exists($qr_dir)) {
            mkdir($qr_dir, 0777, true);
        }

        $qr_file = 'event_qr_' . time() . '_' . uniqid() . '.png';
        $qr_path = $qr_dir . $qr_file;
        $qr_source = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($airtel_money_code);
        $qr_contents = @file_get_contents($qr_source);
        if ($qr_contents !== false) {
            file_put_contents($qr_path, $qr_contents);
            $airtel_qr_image = 'uploads/events/qr/' . $qr_file;
        }
    }
    
    // Insert event
    $stmt = $pdo->prepare("
        INSERT INTO events (event_name, event_description, event_picture, ticket_price, 
                           ticket_template, event_date, event_location, airtel_money_code, airtel_money_id, airtel_qr_image, max_tickets, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $event_name,
        $event_description,
        $event_picture,
        $ticket_price,
        $ticket_template,
        $event_date ?: null,
        $event_location,
        $airtel_money_code ?: null,
        $airtel_money_id ?: null,
        $airtel_qr_image,
        $max_tickets ?: null,
        $user['user_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Event created successfully',
        'event_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
