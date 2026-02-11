<?php
require_once 'api/db.php';

try {
    echo "Attempting to add 'agent_code' column to 'merchants' table...\n";
    $pdo->exec("ALTER TABLE merchants ADD COLUMN agent_code VARCHAR(255) DEFAULT NULL UNIQUE;");
    echo "SUCCESS: Added 'agent_code' column.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "NOTICE: Column 'agent_code' already exists. No changes made.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
?>
