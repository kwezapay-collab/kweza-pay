<?php
require_once 'api/db.php';

echo "Tables in DB:\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\nDropping all...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
foreach ($tables as $table) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Dropped $table\n";
    } catch (Exception $e) {
        echo "Failed to drop $table: " . $e->getMessage() . "\n";
    }
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\nCreating Users Table ONLY...\n";
try {
    $pdo->exec("
        CREATE TABLE users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            phone_number VARCHAR(15) UNIQUE NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            pin_hash VARCHAR(255) NOT NULL,
            user_type ENUM('Student', 'Merchant', 'Admin') NOT NULL DEFAULT 'Student',
            wallet_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00
        ) ENGINE=InnoDB;
    ");
    echo "Users table created.\n";
} catch (Exception $e) {
    echo "Users creation failed: " . $e->getMessage() . "\n";
}
?>
