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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cafe_meals (
            meal_id INT AUTO_INCREMENT PRIMARY KEY,
            cafe_id INT NOT NULL,
            meal_name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cafe_id) REFERENCES campus_cafes(cafe_id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $is_admin = false;
    if (isset($_SESSION['user_id'])) {
        $user = getCurrentUser($pdo);
        $is_admin = ($user['user_type'] === 'Admin');
    }

    $cafe_id = isset($_GET['cafe_id']) ? (int)$_GET['cafe_id'] : 0;

    $query = "SELECT m.*, c.cafe_name FROM cafe_meals m LEFT JOIN campus_cafes c ON m.cafe_id = c.cafe_id";
    $conditions = [];
    $params = [];

    if ($cafe_id > 0) {
        $conditions[] = "m.cafe_id = ?";
        $params[] = $cafe_id;
    }

    if (!$is_admin) {
        $conditions[] = "m.is_active = 1";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY m.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $meals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'meals' => $meals]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
