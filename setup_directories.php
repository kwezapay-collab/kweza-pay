<?php
/**
 * Setup Script - Creates necessary directories for file uploads
 */

echo "Creating upload directories...\n\n";

$directories = [
    'frontend/uploads',
    'frontend/uploads/events',
    'frontend/uploads/cafes'
];

$created = 0;
$existing = 0;
$errors = 0;

foreach ($directories as $dir) {
    if (file_exists($dir)) {
        echo "✓ Directory already exists: $dir\n";
        $existing++;
    } else {
        if (mkdir($dir, 0777, true)) {
            echo "✓ Created directory: $dir\n";
            $created++;
        } else {
            echo "✗ Failed to create directory: $dir\n";
            $errors++;
        }
    }
}

echo "\n";
echo "===========================================\n";
echo "Setup completed!\n";
echo "Created: $created directories\n";
echo "Already existed: $existing directories\n";
echo "Errors: $errors\n";
echo "===========================================\n\n";

if ($errors === 0) {
    echo "✓ All directories are ready!\n";
    echo "\nNext steps:\n";
    echo "1. Run database migration: http://localhost/kweza-app/run_migration.php\n";
    echo "2. Integrate student_modals_addon.html into student.php\n";
    echo "3. Access admin dashboard: http://localhost/kweza-app/frontend/admin.php\n";
} else {
    echo "⚠ Some errors occurred. You may need to create directories manually.\n";
}
