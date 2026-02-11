<?php
require_once 'api/db.php';

try {
    // 1. Clear existing roles and users
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE user_roles");
    $pdo->exec("TRUNCATE TABLE merchants");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Current users and roles cleared successfully.\n";

    // 2. Create a default "Super User" with all account types
    $phone = '0123456789';
    $pin = '1234';
    $pinHash = password_hash($pin, PASSWORD_BCRYPT);
    $fullName = 'Default Super User';
    $email = 'super@example.com';
    $university = 'Kweza University';

    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (
            phone_number, full_name, email, university, pin_hash, 
            user_type, is_verified, wallet_balance
        ) VALUES (?, ?, ?, ?, ?, 'Person', 1, 1000.00)
    ");
    $stmt->execute([$phone, $fullName, $email, $university, $pinHash]);
    $userId = $pdo->lastInsertId();

    // Add public-facing roles
    $roles = ['Person', 'Student', 'Merchant'];
    foreach ($roles as $role) {
        $stmtRole = $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
        $stmtRole->execute([$userId, $role]);
    }

    // Add merchant profile for this user
    $qrToken = 'KZA-SUPER-123';
    $pdo->prepare("
        INSERT INTO merchants (user_id, business_name, qr_code_token, is_approved, fee_paid) 
        VALUES (?, 'Super Store', ?, 1, 1)
    ")->execute([$userId, $qrToken]);

    // 3. Create Dedicate Admin Account (Legacy: 00000)
    $stmt = $pdo->prepare("INSERT INTO users (phone_number, full_name, email, pin_hash, user_type, is_verified) VALUES ('00000', 'System Admin', 'admin@kweza.com', ?, 'Admin', 1)");
    $stmt->execute([$pinHash]);
    $adminId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, 'Admin')")->execute([$adminId]);

    // 4. Create Dedicated Student Union Account (Legacy: 09999)
    $stmt = $pdo->prepare("INSERT INTO users (phone_number, full_name, email, university, pin_hash, user_type, is_verified) VALUES ('09999', 'Student Union', 'su@university.edu', 'Kweza University', ?, 'StudentUnion', 1)");
    $stmt->execute([$pinHash]);
    $suId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, 'StudentUnion')")->execute([$suId]);
    
    // Add merchant record for SU (Started with no agent_code to test input flow)
    $pdo->prepare("INSERT INTO merchants (user_id, business_name, qr_code_token, agent_code, is_approved, fee_paid) VALUES (?, 'Student Union Treasury', 'KZA-SU-999', NULL, 1, 1)")->execute([$suId]);

    echo "--------------------------------------------------\n";
    echo "Test Accounts Created:\n";
    echo "1. Super User (Multi-role):\n";
    echo "   Phone: 0123456789 | PIN: 1234\n";
    echo "2. Dedicated Admin:\n";
    echo "   Phone: 00000 | PIN: 1234\n";
    echo "3. Dedicated Student Union:\n";
    echo "   Phone: 09999 | PIN: 1234\n";
    echo "--------------------------------------------------\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
