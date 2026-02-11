<?php
// Force error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Adjust path as needed for your specific setup
require_once __DIR__ . '/api/db.php';

echo "<h2>Database Migration Tool</h2>";
echo "<p>Connected to database: " . $db . "</p>";

try {
    // Check if column exists first
    $stmt = $pdo->query("SHOW COLUMNS FROM merchants LIKE 'agent_code'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "<p style='color:orange'>Notice: Column 'agent_code' already exists.</p>";
    } else {
        echo "<p>Attempting to add column...</p>";
        $pdo->exec("ALTER TABLE merchants ADD COLUMN agent_code VARCHAR(50) DEFAULT NULL UNIQUE");
        echo "<p style='color:green'>Success: Column 'agent_code' added successfully!</p>";
    }
    
    // Check again to confirm
    $stmt = $pdo->query("SHOW COLUMNS FROM merchants LIKE 'agent_code'");
    $check = $stmt->fetch();
    
    if ($check) {
        echo "<p><strong>Verification:</strong> Column exists in table schema.</p>";
    } else {
        echo "<p style='color:red'><strong>Error:</strong> Failed to verify column creation.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
