<?php
require_once 'api/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role ENUM('Person', 'Student', 'Merchant', 'Admin', 'StudentUnion') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (user_id, role),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    // Migrate existing users to user_roles table
    $stmt = $pdo->query("SELECT user_id, user_type FROM users");
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        $insertStmt = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role) VALUES (?, ?)");
        $insertStmt->execute([$user['user_id'], $user['user_type']]);
    }

    // Update users table to allow 'Person' in the enum if it's not already there
    // and potentially remove the user_type column later once everything is migrated.
    // For now, we keep user_type for backward compatibility but use user_roles for the new logic.
    
    // Check if 'Person' exists in enum
    $pdo->exec("ALTER TABLE users MODIFY COLUMN user_type ENUM('Student', 'Merchant', 'Admin', 'StudentUnion', 'Person') NOT NULL DEFAULT 'Student'");

    echo "Migration successful: Created user_roles and migrated existing users.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
