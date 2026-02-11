<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        echo json_encode(['error' => 'Fatal Error: ' . $error['message']]);
    }
});

require_once 'db.php';
require_once 'session.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Disable ONLY_FULL_GROUP_BY for this session to ensure compatibility
    $pdo->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

    // Fetch recent P2P receivers for this user, distinct by receiver_id
    // Limit to 10 most recent
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, u.avatar_url, u.phone_number, MAX(t.created_at) as last_txn
        FROM transactions t
        JOIN users u ON t.receiver_id = u.user_id
        WHERE t.sender_id = ? 
        AND t.txn_type = 'P2P'
        AND u.user_type = 'Student'
        GROUP BY u.user_id, u.full_name, u.avatar_url, u.phone_number
        ORDER BY last_txn DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no beneficiaries, maybe return some random students (for demo/testing purposes if requested, but for now empty is fine)
    // Actually, for the "Redesign" visual effect, if there are none, we might want to show some mock data or just empty.
    // Let's stick to real data for now.

    echo json_encode(['success' => true, 'beneficiaries' => $beneficiaries]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
