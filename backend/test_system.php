<?php
// test_system.php - UPDATED
echo "=== KWEZA PAY FINANCIAL LOGIC TEST ===\n\n";

function post($url, $data) {
    $ch = curl_init('http://localhost/kweza/' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

require_once 'api/db.php';

// [SETUP] RESET DB for clean test
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE transactions; TRUNCATE TABLE merchants; TRUNCATE TABLE users; SET FOREIGN_KEY_CHECKS = 1;");
echo "[0] DB Reset Complete.\n";

// [SETUP] CREATE 'Student Union' ACCOUNT
$pdo->prepare("INSERT INTO users (full_name, phone_number, pin_hash, user_type, wallet_balance) VALUES ('Student Union', 'SU_ADMIN', 'hash', 'Admin', 0)")->execute();
echo "[0b] Created 'Student Union' Account.\n";


// [1] REGISTER STUDENT & FUND (5000 MWK)
echo "\n[1] Registering Student (John) with 5000 MWK...\n";
$res = post('admin/register.php', ['phone_number'=>'07001','full_name'=>'John','pin'=>'1234']);
$stdId = $res['body']['user_id'];
$pdo->prepare("UPDATE users SET wallet_balance = 5000 WHERE user_id = ?")->execute([$stdId]);
echo " -> Balance: 5000 MWK\n";

// [2] REGISTER MERCHANT
echo "\n[2] Registering Merchant (Cafe)...\n";
$res = post('admin/register.php', ['phone_number'=>'07777','full_name'=>'Cafe','user_type'=>'Merchant']);
$qr = $res['body']['qr_token'];
echo " -> QR: $qr\n";


// [3] TEST: MERCHANT PAYMENT (0 FEE)
echo "\n[3] TEST: Pay 1000 MWK to Merchant (Expect 0 Fee)...\n";
$res = post('api/qr_pay.php', ['sender_id'=>$stdId, 'pin'=>'1234', 'qr_token'=>$qr, 'amount'=>1000]);
echo " -> Result: " . $res['body']['status'] . "\n"; 
echo " -> New Balance should be 4000: " . $res['body']['new_balance'] . "\n";


// [4] TEST: WITHDRAWAL (20 MWK FEE)
echo "\n[4] TEST: Withdraw 1000 MWK (Expect 1020 deduction)...\n";
$res = post('api/withdraw.php', ['user_id'=>$stdId, 'pin'=>'1234', 'amount'=>1000]);
if ($res['code'] == 200) {
    echo " -> Success. New Balance: " . $res['body']['new_balance'] . " (Should be 4000 - 1020 = 2980)\n";
} else {
    echo " -> FAILED: " . json_encode($res['body']) . "\n";
}


// [5] TEST: SU FEE (FAIL CASE - LOW BALANCE)
// Current Balance ~2980. Fee = 3000. Should FAIL (402).
echo "\n[5] TEST: SU Fee (Expect FAIL - Insufficient Funds)...\n";
$res = post('api/pay_su_fee.php', ['user_id'=>$stdId, 'pin'=>'1234']);
echo " -> Code: " . $res['code'] . " (Expected 402)\n";
echo " -> Msg: " . $res['body']['error'] . "\n";

// [5b] Fund User just enough (Need 3000 Fee + 500 Buffer = 3500 Total needed).
// Current: 2980. Add 1000 -> 3980.
$pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + 1000 WHERE user_id = ?")->execute([$stdId]);
echo " -> Added 1000. Balance is now ~3980.\n";


// [6] TEST: SU FEE (SUCCESS CASE)
// 3980 >= 3500. Should PASS.
echo "\n[6] TEST: SU Fee (Expect SUCCESS - 3000 MWK)...\n";
$res = post('api/pay_su_fee.php', ['user_id'=>$stdId, 'pin'=>'1234']);
if ($res['code'] == 200) {
    echo " -> Success! Receipt: " . $res['body']['receipt_code'] . "\n";
    
    // VERIFY SU ACCOUNT RECEIVED IT
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE full_name = 'Student Union'");
    $stmt->execute();
    echo " -> Student Union Acct Balance: " . $stmt->fetchColumn() . " (Should be 3000)\n";
} else {
    echo " -> FAILED: " . json_encode($res['body']) . "\n";
}

echo "\n=== TESTS COMPLETE ===\n";
?>
