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
$format = $_GET['format'] ?? 'json'; // json, csv, pdf
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default: first day of month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Default: today

try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               sender.full_name as sender_name,
               receiver.full_name as receiver_name,
               CASE 
                   WHEN t.sender_id = ? THEN 'DEBIT'
                   ELSE 'CREDIT'
               END as direction
        FROM transactions t
        LEFT JOIN users sender ON t.sender_id = sender.user_id
        LEFT JOIN users receiver ON t.receiver_id = receiver.user_id
        WHERE (t.sender_id = ? OR t.receiver_id = ?)
          AND DATE(t.created_at) BETWEEN ? AND ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $startDate, $endDate]);
    $transactions = $stmt->fetchAll();

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="transactions_' . date('Ymd') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Type', 'Direction', 'Party', 'Amount', 'Reference', 'Description']);
        
        foreach ($transactions as $t) {
            $party = $t['direction'] === 'DEBIT' ? $t['receiver_name'] : $t['sender_name'];
            fputcsv($output, [
                $t['created_at'],
                $t['txn_type'],
                $t['direction'],
                $party,
                $t['amount'],
                $t['reference_code'],
                $t['description']
            ]);
        }
        fclose($output);
        exit;
    }

    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'count' => count($transactions),
        'date_range' => ['start' => $startDate, 'end' => $endDate]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
