<?php
require_once 'db.php';

try {
    // Check if columns exist first to avoid errors
    $pdo->exec("ALTER TABLE merchants ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE merchants ADD COLUMN IF NOT EXISTS fee_paid TINYINT(1) DEFAULT 0");
    
    // Also add school to merchants if not exists (it was mentioned in register.php but maybe it's only in users table)
    // Actually register.php inserts school into users.
    
    echo "Database updated successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
