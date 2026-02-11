<?php
require_once 'api/db.php';

echo "=== KWEZA PAY SYSTEM CHECK ===\n";

// 1. Check Database
try {
    $pdo->query("SELECT 1");
    echo "[PASS] Database Connected\n";
} catch (Exception $e) {
    die("[FAIL] Database Connection: " . $e->getMessage());
}

// 2. Check Users
$users = $pdo->query("SELECT user_type, COUNT(*) as c FROM users GROUP BY user_type")->fetchAll(PDO::FETCH_KEY_PAIR);
if (($users['Student'] ?? 0) > 0 && ($users['Merchant'] ?? 0) > 0 && ($users['Admin'] ?? 0) > 0) {
    echo "[PASS] Seed Users Exist (Student, Merchant, Admin)\n";
} else {
    echo "[FAIL] Missing Seed Users. Run init_db.php\n";
}

// 3. Check Merchant Token
$token = $pdo->query("SELECT qr_code_token FROM merchants LIMIT 1")->fetchColumn();
if ($token == 'KZA-79652656') {
    echo "[PASS] Merchant Token Correct ($token)\n";
} else {
    echo "[FAIL] Merchant Token Mismatch ($token)\n";
}

// 4. API Endpoints logic check (Mock)
// Login Check
$pinHash = $pdo->query("SELECT pin_hash FROM users WHERE phone_number='07001'")->fetchColumn();
if (password_verify('1234', $pinHash)) {
    echo "[PASS] PIN Verification Logic Working\n";
} else {
    echo "[FAIL] PIN Verification Failed\n";
}

echo "=== SYSTEM READY ===\n";
echo "Go to http://localhost/kweza/ to test.\n";
?>
