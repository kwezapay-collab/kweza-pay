<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL");
    echo "Users table updated";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
