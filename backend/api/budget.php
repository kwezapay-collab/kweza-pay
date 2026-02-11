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
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? 'get';

try {
    if ($action === 'get') {
        // Get current budget settings
        $stmt = $pdo->prepare("SELECT daily_limit, weekly_limit, monthly_limit, low_balance_alert FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'budget' => [
                'daily_limit' => $settings['daily_limit'] ?? 50000,
                'weekly_limit' => $settings['weekly_limit'] ?? null,
                'monthly_limit' => $settings['monthly_limit'] ?? null,
                'low_balance_alert' => $settings['low_balance_alert'] ?? 1000
            ]
        ]);
    } else if ($action === 'set') {
        $dailyLimit = floatval($data['daily_limit'] ?? 0);
        $lowBalanceAlert = floatval($data['low_balance_alert'] ?? 1000);

        $stmt = $pdo->prepare("UPDATE users SET daily_limit = ?, low_balance_alert = ? WHERE user_id = ?");
        $stmt->execute([$dailyLimit, $lowBalanceAlert, $userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Budget settings updated'
        ]);
    } else if ($action === 'check_daily') {
        // Check today's spending
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as spent_today
            FROM transactions
            WHERE sender_id = ? AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$userId]);
        $spentToday = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT daily_limit FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $dailyLimit = $stmt->fetchColumn() ?: 50000;

        echo json_encode([
            'success' => true,
            'spent_today' => $spentToday,
            'daily_limit' => $dailyLimit,
            'remaining' => max(0, $dailyLimit - $spentToday),
            'percentage_used' => min(100, ($spentToday / $dailyLimit) * 100)
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
