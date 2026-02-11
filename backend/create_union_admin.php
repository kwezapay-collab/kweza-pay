<?php
/**
 * Create Union Admin Account
 * This account is for the Student Union administrators
 */

require_once 'api/db.php';

echo "Creating Union Admin Account...\n";
echo "================================\n\n";

$pinHash = password_hash('1234', PASSWORD_DEFAULT);

try {
    // Check if union admin account exists
    $stmt = $pdo->prepare("SELECT user_id, phone_number, full_name, user_type FROM users WHERE phone_number = '08888'");
    $stmt->execute();
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "Union Admin account already exists:\n";
        echo "  Phone: {$existing['phone_number']}\n";
        echo "  Name: {$existing['full_name']}\n";
        echo "  Type: {$existing['user_type']}\n\n";
        
        // Update to ensure correct settings
        $pdo->exec("UPDATE users SET 
            full_name = 'Union Admin', 
            user_type = 'StudentUnion',
            pin_hash = '$pinHash',
            is_verified = 1
            WHERE phone_number = '08888'");
        echo "✓ Updated Union Admin account\n";
        
    } else {
        // Create new Union Admin account
        $stmt = $pdo->prepare("
            INSERT INTO users (
                phone_number, 
                full_name, 
                pin_hash, 
                user_type, 
                wallet_balance,
                is_verified
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            '08888',                // Phone number
            'Union Admin',          // Full name
            $pinHash,              // PIN hash
            'StudentUnion',        // User type (same as SU but different account)
            0.00,                  // Starting balance
            1                      // Verified
        ]);
        
        echo "✓ Created Union Admin account\n\n";
    }
    
    echo "================================\n";
    echo "Union Admin Account Details:\n";
    echo "================================\n";
    echo "Phone: 08888\n";
    echo "PIN: 1234\n";
    echo "Name: Union Admin\n";
    echo "Type: StudentUnion\n";
    echo "\nLogin at: http://localhost/kweza/\n";
    echo "Dashboard: student_union.php\n";
    
    echo "\n\n================================\n";
    echo "All Student Union Accounts:\n";
    echo "================================\n";
    
    $stmt = $pdo->query("SELECT phone_number, full_name FROM users WHERE user_type = 'StudentUnion' ORDER BY phone_number");
    while ($account = $stmt->fetch()) {
        echo "  {$account['phone_number']} - {$account['full_name']}\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
