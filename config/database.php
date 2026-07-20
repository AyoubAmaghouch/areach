<?php

$host = 'localhost';
$port = '3306';
$db   = 'u184188115_areach';
$user = 'u184188115_areach';
$pass = 'Kingfb12@';

if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
    die('Database Connection Failed: required PHP extension "pdo_mysql" is not loaded.');
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (PDOException $e) {
    die("Database Connection Failed : " . $e->getMessage());
}