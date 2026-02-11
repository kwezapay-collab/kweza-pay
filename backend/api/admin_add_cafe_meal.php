<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

requireLogin();
$user = getCurrentUser($pdo);
if ($user['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cafe_id = (int)($input['cafe_id'] ?? 0);
$meal_name = trim($input['meal_name'] ?? '');
$price = $input['price'] ?? null;
$description = trim($input['description'] ?? '');

if ($meal_name === '' || $price === null || $price === '') {
    echo json_encode(['success' => false, 'error' => 'Meal name and price are required.']);
    exit;
}

if (!is_numeric($price) || $price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid price.']);
    exit;
}

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

    if ($cafe_id <= 0) {
        $stmt = $pdo->query("SELECT cafe_id FROM campus_cafes ORDER BY cafe_id ASC LIMIT 1");
        $firstCafe = $stmt->fetch(PDO::FETCH_ASSOC);
        $cafe_id = (int)($firstCafe['cafe_id'] ?? 0);
    }

    if ($cafe_id <= 0) {
        $stmt = $pdo->prepare("
            INSERT INTO campus_cafes (cafe_name, cafe_description, airtel_money_code, created_by, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            'Campus Cafe',
            'Default campus cafe',
            'N/A',
            $user['user_id']
        ]);
        $cafe_id = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("INSERT INTO cafe_meals (cafe_id, meal_name, price, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$cafe_id, $meal_name, $price, $description]);

    echo json_encode(['success' => true, 'message' => 'Meal added successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to add meal.']);
}
?>
