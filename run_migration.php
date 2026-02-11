<?php
/**
 * Database Migration Script for Event Tickets and Campus Cafe Features
 * Run this file once to create the necessary database tables
 */

require_once 'backend/api/db.php';

echo "Starting database migration for Event Tickets and Campus Cafe features...\n\n";

try {
    // Read the SQL file
    $sql = file_get_contents('backend/db_events_cafes_schema.sql');
    
    // Remove the USE database statement as we're already connected
    $sql = preg_replace('/USE\s+kweza_pay;/i', '', $sql);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $success_count++;
            
            // Extract table name for better feedback
            if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+(\w+)/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            } elseif (preg_match('/CREATE\s+INDEX\s+(\w+)/i', $statement, $matches)) {
                echo "✓ Created index: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            $error_count++;
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "===========================================\n";
    echo "Migration completed!\n";
    echo "Successful operations: $success_count\n";
    echo "Errors: $error_count\n";
    echo "===========================================\n\n";
    
    if ($error_count === 0) {
        echo "✓ All tables and indexes created successfully!\n";
        echo "\nNext steps:\n";
        echo "1. Create upload directories (if not exists):\n";
        echo "   - frontend/uploads/events/\n";
        echo "   - frontend/uploads/cafes/\n";
        echo "2. Integrate student_modals_addon.html into student.php\n";
        echo "3. Access admin dashboard at: frontend/admin.php\n";
    } else {
        echo "⚠ Some errors occurred. Please check the messages above.\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
