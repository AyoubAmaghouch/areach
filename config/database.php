<?php
/*
 * AwardSpace production database configuration.
 *
 * Hosting details:
 *   Host: fdb1029.awardspace.net
 *   Port: 3306
 *   DB:   4772751_areach
 *   User: 4772751_areach
 */

$host = 'fdb1029.awardspace.net';
$port = '3306';
$db   = '4772751_areach';
$user = '4772751_areach';
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
