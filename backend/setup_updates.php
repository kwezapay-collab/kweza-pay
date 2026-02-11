<?php
require_once 'api/db.php';

try {
    echo "Applying Database Updates...\n";

    // 1. Add commission_balance to merchants
    try {
        $pdo->exec("ALTER TABLE merchants ADD COLUMN commission_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00");
        echo "✅ Added commission_balance to merchants.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ commission_balance already exists.\n";
        } else {
            echo "❌ Error adding commission_balance: " . $e->getMessage() . "\n";
        }
    }

    // 2. Add created_at to merchants
    try {
        $pdo->exec("ALTER TABLE merchants ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Added created_at to merchants.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ created_at already exists.\n";
        } else {
            echo "❌ Error adding created_at: " . $e->getMessage() . "\n";
        }
    }

    // 3. Add pin_hash to users
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN pin_hash VARCHAR(255) NOT NULL DEFAULT ''");
         echo "✅ Added pin_hash to users.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
             echo "ℹ️ pin_hash already exists.\n";
        } else {
             echo "❌ Error adding pin_hash: " . $e->getMessage() . "\n";
        }
    }

    echo "Database updates complete.\n";

} catch (Exception $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>
