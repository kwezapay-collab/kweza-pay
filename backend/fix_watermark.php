<?php
/**
 * Quick Fix: Update Union Admin Dashboard Logo Visibility
 */

$file = 'student_union.php';
$content = file_get_contents($file);

// Fix watermark opacity from 0.05 to 0.5
$content = str_replace('opacity: 0.05;', 'opacity: 0.5;', $content);

file_put_contents($file, $content);

echo "✅ Fixed logo watermark opacity\n";
echo "Logo is now more visible like other dashboards\n";
?>
