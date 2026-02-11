<?php
require_once 'api/db.php';
try {
    $pdo->exec("ALTER TABLE merchants ADD COLUMN agent_code VARCHAR(255) DEFAULT NULL UNIQUE;");
    echo "Added agent_code column successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column agent_code already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
