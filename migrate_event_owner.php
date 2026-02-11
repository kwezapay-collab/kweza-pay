<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    // Connect without database first
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS kweza_pay");
    $pdo->exec("USE kweza_pay");

    // 1. Add owner_token to events table
    $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS owner_token VARCHAR(100) UNIQUE NULL AFTER max_tickets");
    
    // 2. Create event_ticket_inventory table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_ticket_inventory (
            inventory_id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            serial_number VARCHAR(100) NOT NULL,
            is_assigned TINYINT(1) DEFAULT 0,
            assigned_at TIMESTAMP NULL,
            ticket_id INT NULL, 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_event_serial (event_id, serial_number),
            FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    echo "Migration successful!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
