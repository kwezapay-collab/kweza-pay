<?php
require_once 'db.php';
require_once 'session.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$period = $_GET['period'] ?? 'month'; // day, week, month, year

try {
    // Get date range based on period
    $dateFilter = match($period) {
        'day' => "DATE(t.created_at) = CURDATE()",
        'week' => "YEARWEEK(t.created_at) = YEARWEEK(NOW())",
        'month' => "YEAR(t.created_at) = YEAR(NOW()) AND MONTH(t.created_at) = MONTH(NOW())",
        'year' => "YEAR(t.created_at) = YEAR(NOW())",
        default => "YEAR(t.created_at) = YEAR(NOW()) AND MONTH(t.created_at) = MONTH(NOW())"
    };

    // Total spent (debits)
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total
        FROM transactions t
        WHERE t.sender_id = ? AND $dateFilter
    ");
    $stmt->execute([$userId]);
    $totalSpent = $stmt->fetchColumn() ?: 0;

    // Total received (credits)
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total
        FROM transactions t
        WHERE t.receiver_id = ? AND $dateFilter
    ");
    $stmt->execute([$userId]);
    $totalReceived = $stmt->fetchColumn() ?: 0;

    // Spending by type
    $stmt = $pdo->prepare("
        SELECT txn_type, SUM(amount) as total, COUNT(*) as count
        FROM transactions t
        WHERE t.sender_id = ? AND $dateFilter
        GROUP BY txn_type
    ");
    $stmt->execute([$userId]);
    $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top merchants (for students)
    $stmt = $pdo->prepare("
        SELECT u.full_name as merchant, SUM(t.amount) as total, COUNT(*) as count
        FROM transactions t
        JOIN users u ON t.receiver_id = u.user_id
        WHERE t.sender_id = ? AND t.txn_type = 'QR_PAY' AND $dateFilter
        GROUP BY u.full_name
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $topMerchants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Daily spending trend (last 7 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, SUM(amount) as total
        FROM transactions
        WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$userId]);
    $dailyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'period' => $period,
        'summary' => [
            'total_spent' => $totalSpent,
            'total_received' => $totalReceived,
            'net' => $totalReceived - $totalSpent
        ],
        'by_type' => $byType,
        'top_merchants' => $topMerchants,
        'daily_trend' => $dailyTrend
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
