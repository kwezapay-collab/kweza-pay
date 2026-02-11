<?php
require_once 'session.php';
require_once 'db.php';
requireLogin();

header('Content-Type: application/json');

$user = getCurrentUser($pdo);
if ($user['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

try {
    $event_id = $_POST['event_id'] ?? 0;
    $event_name = $_POST['event_name'] ?? '';
    $event_description = $_POST['event_description'] ?? '';
    $ticket_price = $_POST['ticket_price'] ?? 0;
    $event_date = $_POST['event_date'] ?? null;
    $event_location = $_POST['event_location'] ?? '';
    $airtel_money_code = $_POST['airtel_money_code'] ?? '';
    $airtel_money_id = $_POST['airtel_money_id'] ?? '';
    $max_tickets = $_POST['max_tickets'] ?? null;
    $ticket_template = $_POST['ticket_template'] ?? '';

    if (empty($event_id)) {
        echo json_encode(['success' => false, 'error' => 'Missing event id']);
        exit;
    }

    if (empty($event_name) || $ticket_price <= 0) {
        echo json_encode(['success' => false, 'error' => 'Event name and valid ticket price are required']);
        exit;
    }

    // Fetch existing event
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['success' => false, 'error' => 'Event not found']);
        exit;
    }

    $event_picture = $existing['event_picture'];
    $airtel_qr_image = $existing['airtel_qr_image'];

    // Replace event picture if a new file is provided
    if (isset($_FILES['event_picture']) && $_FILES['event_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/events/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['event_picture']['name'], PATHINFO_EXTENSION);
        $file_name = 'event_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['event_picture']['tmp_name'], $file_path)) {
            // Delete previous picture if present
            if (!empty($event_picture)) {
                $old_path = dirname(__DIR__) . '/' . $event_picture;
                if (file_exists($old_path)) {
                    @unlink($old_path);
                }
            }
            $event_picture = 'uploads/events/' . $file_name;
        }
    }

    // Regenerate QR image when Airtel code changes or when a new code is supplied
    $code_changed = $airtel_money_code !== ($existing['airtel_money_code'] ?? '');
    if ($code_changed && !empty($airtel_money_code)) {
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
            // Remove old qr if present
            if (!empty($airtel_qr_image)) {
                $old_qr_path = dirname(__DIR__) . '/' . $airtel_qr_image;
                if (file_exists($old_qr_path)) {
                    @unlink($old_qr_path);
                }
            }
            $airtel_qr_image = 'uploads/events/qr/' . $qr_file;
        }
    }

    // Clear QR when code is removed
    if (empty($airtel_money_code)) {
        if (!empty($airtel_qr_image)) {
            $old_qr_path = dirname(__DIR__) . '/' . $airtel_qr_image;
            if (file_exists($old_qr_path)) {
                @unlink($old_qr_path);
            }
        }
        $airtel_qr_image = null;
    }

    $stmt = $pdo->prepare("
        UPDATE events SET
            event_name = ?,
            event_description = ?,
            event_picture = ?,
            ticket_price = ?,
            ticket_template = ?,
            event_date = ?,
            event_location = ?,
            airtel_money_code = ?,
            airtel_money_id = ?,
            airtel_qr_image = ?,
            max_tickets = ?,
            updated_at = NOW()
        WHERE event_id = ?
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
        $event_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Event updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
