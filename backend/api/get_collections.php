<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $search = $_GET['search'] ?? '';
    $program = $_GET['program'] ?? '';
    $year = $_GET['year'] ?? '';

    $query = "SELECT * FROM student_union WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (student_name LIKE ? OR student_id LIKE ? OR reference_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($program) {
        $query .= " AND program = ?";
        $params[] = $program;
    }

    if ($year) {
        $query .= " AND year = ?";
        $params[] = $year;
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $collections = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $collections
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
