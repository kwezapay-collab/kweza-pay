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

echo json_encode(['success' => false, 'error' => 'Campus cafe creation is disabled.']);
exit;

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

    $cafe_name = $_POST['cafe_name'] ?? '';
    $cafe_description = $_POST['cafe_description'] ?? '';
    $airtel_money_code = $_POST['airtel_money_code'] ?? '';
    
    if (empty($cafe_name) || empty($airtel_money_code)) {
        echo json_encode(['success' => false, 'error' => 'Cafe name and Airtel Money code are required']);
        exit;
    }
    
    // Handle logo upload
    $cafe_logo = null;
    if (isset($_FILES['cafe_logo']) && $_FILES['cafe_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/cafes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['cafe_logo']['name'], PATHINFO_EXTENSION);
        $file_name = 'cafe_logo_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['cafe_logo']['tmp_name'], $file_path)) {
            $cafe_logo = 'uploads/cafes/' . $file_name;
        }
    }
    
    // Handle QR code upload
    $qr_code_image = null;
    if (isset($_FILES['qr_code_image']) && $_FILES['qr_code_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/cafes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['qr_code_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'cafe_qr_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['qr_code_image']['tmp_name'], $file_path)) {
            $qr_code_image = 'uploads/cafes/' . $file_name;
        }
    }
    
    // Insert cafe
    $stmt = $pdo->prepare("
        INSERT INTO campus_cafes (cafe_name, cafe_description, cafe_logo, 
                                  airtel_money_code, qr_code_image, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $cafe_name,
        $cafe_description,
        $cafe_logo,
        $airtel_money_code,
        $qr_code_image,
        $user['user_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Campus Cafe added successfully',
        'cafe_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
