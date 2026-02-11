<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Only StudentUnion or Admin can reset collections
if ($_SESSION['user_type'] !== 'StudentUnion' && $_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Ensure table exists (safe no-op if already present)
    $pdo->exec("CREATE TABLE IF NOT EXISTS collection_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reset_at DATETIME NOT NULL,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $pdo->prepare("INSERT INTO collection_resets (user_id, reset_at) VALUES (?, NOW())");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
