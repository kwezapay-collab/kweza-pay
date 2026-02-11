<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1';
$db   = 'kweza_pay';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    echo "Connection to MySQL OK\n";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    echo "Database check OK\n";
    
    $pdo->exec("USE `$db`");
    echo "Using database OK\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
