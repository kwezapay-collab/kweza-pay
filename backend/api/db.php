<?php
// Production ready db.php using Environment Variables
// Works locally if you just hardcode defaults or use .env (not implemented here)
// Recommended for Vercel

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'kweza_pay'; 
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306'; // Default MySQL port, changes to 5432 for Postgres if env set

// Determine Driver
// If port is 5432, usually Postgres (Supabase)
$driver = ($port == '5432') ? 'pgsql' : 'mysql';

$dsn = "$driver:host=$host;port=$port;dbname=$db";
if ($driver == 'mysql') $dsn .= ";charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, log this error instead of showing it
    // But for debugging deployment:
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
