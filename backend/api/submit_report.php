<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

requireLogin();
$user = getCurrentUser($pdo);

$input = json_decode(file_get_contents('php://input'), true);
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');

if ($subject === '' || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Subject and message are required.']);
    exit;
}

if (strlen($subject) > 255) {
    echo json_encode(['success' => false, 'error' => 'Subject is too long.']);
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS help_reports (
            report_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_type VARCHAR(50) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('NEW', 'VIEWED', 'RESOLVED') DEFAULT 'NEW',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $stmt = $pdo->prepare("INSERT INTO help_reports (user_id, user_type, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['user_id'], $user['user_type'], $subject, $message]);

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to submit report.']);
}
?>
