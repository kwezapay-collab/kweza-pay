<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'kweza_pay';

try {
    // 1. Connect without Database to create it
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    $pdo->exec("USE `$db`");
    
    echo "Database '$db' checked/created successfully.\n";

    // 2. Load Schema
    $sql = file_get_contents('db_schema.sql');
    if (!$sql) {
        die("Error: Could not read db_schema.sql");
    }

    // Disable FK checks for clean slate if needed
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    // We don't drop tables blindly here unless we want a hard reset. 
    // The user's query had IF NOT EXISTS, so let's respect that or just drop to be safe for a fresh init.
    // Given 'init_db.php' usually implies a fresh start or migration, let's keep the existing drop logic but safely.
    
    // The schema file now has "USE kweza_pay", but we already selected it. 
    // We should parse the statements carefully.

    // Remove "CREATE DATABASE" and "USE" lines from SQL to avoid errors if we run individual statements
    // OR just rely on the split. "USE kweza_pay" is a statement.
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            // Skip CREATE DATABASE / USE if we did it manually, or let it run (it's harmless if correct)
            try {
                $pdo->exec($stmt);
                echo "Executed: " . substr(str_replace("\n", " ", $stmt), 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Ignore "database exists" errors specifically if they happen
                echo "Note: " . substr(str_replace("\n", " ", $stmt), 0, 50) . "... -> " . $e->getMessage() . "\n";
            }
        }
    }
    // ... table creation above ...

    // 3. SEED DEFAULT DATA
    $pinHash = password_hash('1234', PASSWORD_DEFAULT);

    // 3.1 Student
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone_number = '07001'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO users (phone_number, full_name, pin_hash, user_type, wallet_balance, is_verified) VALUES ('07001', 'John Doe', ?, 'Student', 5000.00, 1)")->execute([$pinHash]);
        echo "Seeded Student: 07001 / 1234\n";
    } else {
        $pdo->prepare("UPDATE users SET pin_hash = ?, is_verified = 1 WHERE phone_number = '07001'")->execute([$pinHash]);
        echo "Reset Student PIN to 1234\n";
    }

    // 3.2 Student Union (Fee Receiver - Type: StudentUnion)
    // This account is DISTINCT from regular Admin accounts
    
    // Union Account (09999)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone_number = '09999'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO users (phone_number, full_name, pin_hash, user_type, wallet_balance, is_verified) VALUES ('09999', 'Student Union', ?, 'StudentUnion', 0.00, 1)")->execute([$pinHash]);
        echo "Seeded Student Union: 09999 / 1234\n";
    } else {
        $pdo->prepare("UPDATE users SET full_name = 'Student Union', user_type = 'StudentUnion', pin_hash = ?, is_verified = 1 WHERE phone_number = '09999'")->execute([$pinHash]);
        echo "Updated Student Union Account: 09999\n";
    }

    // System Admin Account (00000)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone_number = '00000'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO users (phone_number, full_name, pin_hash, user_type, wallet_balance, is_verified) VALUES ('00000', 'System Admin', ?, 'Admin', 0.00, 1)")->execute([$pinHash]);
        echo "Seeded System Admin: 00000 / 1234\n";
    } else {
         $pdo->prepare("UPDATE users SET pin_hash = ?, is_verified = 1 WHERE phone_number = '00000'")->execute([$pinHash]);
         echo "Updated System Admin: 00000\n";
    }

    // 3.3 Merchant
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone_number = '07777'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO users (phone_number, full_name, pin_hash, user_type, wallet_balance, is_verified) VALUES ('07777', 'Cafe Java Owner', ?, 'Merchant', 0.00, 1)")->execute([$pinHash]);
        $uid = $pdo->lastInsertId();
        
        $pdo->prepare("INSERT INTO merchants (user_id, business_name, qr_code_token) VALUES (?, 'Cafe Java', 'KZA-79652656')")->execute([$uid]);
        echo "Seeded Merchant: 07777 / 1234\n";
    } else {
        $pdo->prepare("UPDATE users SET pin_hash = ?, is_verified = 1 WHERE phone_number = '07777'")->execute([$pinHash]);
        echo "Reset Merchant PIN to 1234\n";
    }

    echo "Database Initialized & Seeded Successfully.\n";

} catch (PDOException $e) {
    die("DB Init Failed: " . $e->getMessage());
}
?>
