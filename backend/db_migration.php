<?php
/**
 * Database Migration Script
 * Adds new fields to support self-registration system
 */

require_once 'api/db.php';

echo "Starting Database Migration...\n";
echo "================================\n\n";

try {
    // 1. Add email column to users table
    echo "Adding email column to users table...";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL AFTER phone_number");
        echo " ✓ Done\n";
    } else {
        echo " Already exists\n";
    }

    // 2. Add registration_number column to users table
    echo "Adding registration_number column to users table...";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'registration_number'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN registration_number VARCHAR(50) NULL AFTER email");
        echo " ✓ Done\n";
    } else {
        echo " Already exists\n";
    }

    // 3. Add verification_code column
    echo "Adding verification_code column to users table...";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_code VARCHAR(6) NULL");
        echo " ✓ Done\n";
    } else {
        echo " Already exists\n";
    }

    // 4. Add is_verified column
    echo "Adding is_verified column to users table...";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
        echo " ✓ Done\n";
    } else {
        echo " Already exists\n";
    }

    // 5. Add verification_expires_at column
    echo "Adding verification_expires_at column to users table...";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verification_expires_at'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_expires_at DATETIME NULL");
        echo " ✓ Done\n";
    } else {
        echo " Already exists\n";
    }

    // 6. Update existing users to be verified (they were created by admin)
    echo "Marking existing users as verified...";
    $pdo->exec("UPDATE users SET is_verified = 1 WHERE is_verified = 0");
    echo " ✓ Done\n";

    // 7. Check if business_name exists in merchants table
    echo "Checking business_name in merchants table...";
    $stmt = $pdo->query("SHOW COLUMNS FROM merchants LIKE 'business_name'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE merchants ADD COLUMN business_name VARCHAR(255) NOT NULL AFTER user_id");
        echo " ✓ Added\n";
    } else {
        echo " Already exists\n";
    }

    echo "\n================================\n";
    echo "Migration completed successfully!\n";
    echo "================================\n";

} catch (PDOException $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
