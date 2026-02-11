<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'kweza_pay';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "Connected successfully to $db";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
