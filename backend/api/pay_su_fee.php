<?php
require_once 'db.php';
require_once 'session.php'; // Use helper

header('Content-Type: application/json');

// --- CONFIGURATION ---
$SU_FEE_AMOUNT = 3000.00;
$MIN_BOOK_BALANCE = 500.00;
$SU_ACCOUNT_NAME = 'Student Union';
// ---------------------

// Ensure Login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$pin = $data['pin'] ?? '';

try {
    // 1. FIND SU ACCOUNT - Use phone number for reliability
    $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE phone_number = '09999' AND user_type = 'StudentUnion'");
    $stmt->execute();
    $suAccount = $stmt->fetch();

    if (!$suAccount) {
        throw new Exception("System Error: Student Union account not found");
    }

    $suId = $suAccount['user_id'];

    // 2. CHECK ALREADY PAID (This Month)
    $currentMonth = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT txn_id FROM transactions 
        WHERE sender_id = ? 
        AND receiver_id = ?
        AND txn_type = 'SU_FEE' 
        AND DATE_FORMAT(created_at, '%Y') = ?
    ");
    $stmt->execute([$userId, $suId, date('Y')]);
    if ($stmt->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'SU Fee already paid for this year']);
        exit;
    }

    // 3. VERIFY USER & BALANCE RULE
    $stmt = $pdo->prepare("SELECT wallet_balance, pin_hash FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    if ($pin !== '' && !password_verify($pin, $user['pin_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid PIN']);
        exit;
    }

    // RULE: Balance - Fee must be >= 500
    if ((!defined('SIMULATION_MODE') || !SIMULATION_MODE) && ($user['wallet_balance'] - $SU_FEE_AMOUNT) < $MIN_BOOK_BALANCE) {
        http_response_code(402);
        echo json_encode([
            'error' => "Insufficient funds. You must maintain a minimum book balance of " . number_format($MIN_BOOK_BALANCE) . " MWK after payment."
        ]);
        exit;
    }

    // 4. ATOMIC TRANSFER
    $pdo->beginTransaction();

    // SIMULATION: Top up if needed to satisfy DB constraints
    if (defined('SIMULATION_MODE') && SIMULATION_MODE) {
         // Re-fetch balance to be safe or use calculated
         $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE user_id = ?");
         $stmt->execute([$userId]);
         $currentBal = $stmt->fetchColumn();
         
         if (($currentBal - $SU_FEE_AMOUNT) < 0) { // Constraint check (balance >= 0)
             $topUp = $SU_FEE_AMOUNT - $currentBal + 1000;
             $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?")->execute([$topUp, $userId]);
         }
    }

    // Deduct from Student
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
    $stmt->execute([$SU_FEE_AMOUNT, $userId]);

    // Add to SU Account
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?");
    $stmt->execute([$SU_FEE_AMOUNT, $suId]);

    // Unique Receipt Code
    $receiptCode = 'SU-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

    $stmt = $pdo->prepare("
        INSERT INTO transactions (txn_type, sender_id, receiver_id, amount, reference_code, description)
        VALUES ('SU_FEE', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $suId, $SU_FEE_AMOUNT, $receiptCode, "Student Union Fee - " . date('F Y')]);

    // Insert into student_union table
    $stmt = $pdo->prepare("
        INSERT INTO student_union (receipt_type, date, reference_number, student_name, student_id, program, year, university, description, amount_paid, service_fee, total_amount, recipient)
        VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, ?)
    ");
    $stmt->execute([
        $data['receiptType'],
        $receiptCode,
        $data['studentName'],
        $data['studentId'],
        $data['program'],
        $data['year'],
        $data['university'],
        $data['description'] ?: null,
        $data['amountPaid'],
        $data['amountPaid'],
        $SU_ACCOUNT_NAME
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "SU Fee Paid Successfully",
        'reference_code' => $receiptCode,
        'amount' => $SU_FEE_AMOUNT,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
