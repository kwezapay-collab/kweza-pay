<?php
// Add funds and create new tables
require_once 'api/db.php';

try {
    echo "Starting database updates...\n\n";

    // 1. Add MWK 200,000 to test accounts
    echo "1. Adding funds to test accounts...\n";
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = 200000 WHERE phone_number IN ('07001', '07777', '09999', '00000', '10101')");
    $stmt->execute();
    echo "   ✓ Added MWK 200,000 to all test accounts (including Person 10101)\n\n";

    // 2. Add budget columns to users table
    echo "2. Adding budget management columns...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN daily_limit DECIMAL(10,2) DEFAULT 50000");
        echo "   ✓ Added daily_limit column\n";
    } catch (Exception $e) {
        echo "   - daily_limit already exists\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN low_balance_alert DECIMAL(10,2) DEFAULT 1000");
        echo "   ✓ Added low_balance_alert column\n\n";
    } catch (Exception $e) {
        echo "   - low_balance_alert already exists\n\n";
    }

    // 3. Create favorites table
    echo "3. Creating favorites table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            favorite_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            merchant_id INT NOT NULL,
            last_amount DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_favorite (user_id, merchant_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (merchant_id) REFERENCES merchants(merchant_id) ON DELETE CASCADE
        )
    ");
    echo "   ✓ Created favorites table\n\n";

    // 4. Create money_requests table
   echo "4. Creating money requests table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS money_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            request_code VARCHAR(50) UNIQUE,
            requester_id INT NOT NULL,
            payer_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            note TEXT,
            status ENUM('PENDING', 'PAID', 'REJECTED', 'EXPIRED') DEFAULT 'PENDING',
            expires_at DATETIME,
            paid_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (payer_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    echo "   ✓ Created money_requests table\n\n";

    // 5. Create disputes table
    echo "5. Creating transaction disputes table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS disputes (
            dispute_id INT AUTO_INCREMENT PRIMARY KEY,
            txn_id INT NOT NULL,
            user_id INT NOT NULL,
            reason TEXT,
            status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
            admin_response TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME,
            FOREIGN KEY (txn_id) REFERENCES transactions(txn_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    echo "   ✓ Created disputes table\n\n";

    // 6. Add category column to transactions
    echo "6. Adding category to transactions...\n";
    try {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN category VARCHAR(50) DEFAULT 'General'");
        echo "   ✓ Added category column\n\n";
    } catch (Exception $e) {
        echo "   - category column already exists\n\n";
    }

    // Verify balances
    echo "7. Verifying account balances...\n";
    $stmt = $pdo->query("SELECT phone_number, full_name, wallet_balance FROM users WHERE phone_number IN ('07001', '07777', '09999', '00000')");
    while ($row = $stmt->fetch()) {
        echo "   {$row['full_name']} ({$row['phone_number']}): MWK " . number_format($row['wallet_balance'], 2) . "\n";
    }

    echo "\n✅ All database updates completed successfully!\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
?>
