<?php
echo "<h1>Kweza Pay Diagnostics</h1>";
echo "<p><strong>Current Script:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

$files = [
    'frontend/index.php',
    'frontend/student.php',
    'backend/api/db.php',
    'backend/api/session.php'
];

echo "<h3>File Check:</h3><ul>";
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path) ? "✅ EXISTS" : "❌ MISSING";
    echo "<li>$file: $exists ($path)</li>";
}
echo "</ul>";

echo "<h3>Session check:</h3>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User ID in Session: " . ($_SESSION['user_id'] ?? 'NONE') . "</p>";
?>
