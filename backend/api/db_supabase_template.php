<?php
/**
 * Supabase Database Connection Template
 * 
 * To switch to Supabase:
 * 1. Get your Connection String from Supabase Dashboard -> Project Settings -> Database -> Connection String (PHP/PDO)
 * 2. Fill in the credentials below.
 * 3. Rename/Update 'db.php' to use this logic.
 */

$host = 'db.[YOUR-PROJECT-REF].supabase.co';
$port = '5432';
$db   = 'postgres';
$user = 'postgres';
$pass = '[YOUR-PASSWORD]';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connected to Supabase!";
} catch (\PDOException $e) {
    // throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
