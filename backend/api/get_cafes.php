<?php
require_once 'session.php';
require_once 'db.php';

header('Content-Type: application/json');

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS campus_cafes (
            cafe_id INT AUTO_INCREMENT PRIMARY KEY,
            cafe_name VARCHAR(255) NOT NULL,
            cafe_description TEXT,
            cafe_logo VARCHAR(255),
            airtel_money_code VARCHAR(100) NOT NULL,
            qr_code_image VARCHAR(255),
            created_by INT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $is_admin = false;
    if (isset($_SESSION['user_id'])) {
        $user = getCurrentUser($pdo);
        $is_admin = ($user['user_type'] === 'Admin');
    }
    
    $query = "SELECT * FROM campus_cafes";
    if (!$is_admin) {
        $query .= " WHERE is_active = 1";
    }
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->query($query);
    $cafes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'cafes' => $cafes
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
