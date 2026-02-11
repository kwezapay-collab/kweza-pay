<?php
require_once 'api/db.php';

echo "Fixing user_type ENUM to include StudentUnion...\n\n";

try {
    // Alter the ENUM to include StudentUnion
    $pdo->exec("ALTER TABLE users MODIFY COLUMN user_type ENUM('Student', 'Merchant', 'Admin', 'StudentUnion') DEFAULT 'Student'");
    echo "✓ ENUM updated successfully\n\n";
    
    // Now update the Student Union account
    $pdo->exec("UPDATE users SET user_type = 'StudentUnion' WHERE phone_number = '09999'");
    echo "✓ Student Union account updated\n\n";
    
    // Verify
    $stmt = $pdo->query("SELECT phone_number, user_type, full_name FROM users WHERE phone_number = '099999'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Verification:\n";
        echo "  Phone: {$user['phone_number']}\n";
        echo "  Type: {$user['user_type']}\n";
        echo "  Name: {$user['full_name']}\n";
    }
    
    echo "\n✓ All fixes applied successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
