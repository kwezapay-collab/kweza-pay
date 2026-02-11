<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

requireLogin();
$user = getCurrentUser($pdo);

if (!isset($_FILES['profile_pic'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['profile_pic'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG and WEBP are allowed.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File is too large. Max size is 5MB.']);
    exit;
}

if (!is_dir('../../frontend/assets/uploads/profiles/')) {
    mkdir('../../frontend/assets/uploads/profiles/', 0777, true);
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'profile_' . $user['user_id'] . '_' . time() . '.' . $extension;
$targetPath = '../../frontend/assets/uploads/profiles/' . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Update database
    $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
    $stmt->execute(['assets/uploads/profiles/' . $filename, $user['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully!', 'path' => 'assets/uploads/profiles/' . $filename]);
} else {
    $error = error_get_last();
    echo json_encode(['success' => false, 'error' => 'Failed to save file. ' . ($error['message'] ?? '')]);
}
?>
