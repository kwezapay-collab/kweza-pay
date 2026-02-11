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
$cafe_id = $data['cafe_id'] ?? 0;

try {
    $stmt = $pdo->prepare("DELETE FROM campus_cafes WHERE cafe_id = ?");
    $stmt->execute([$cafe_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
