<?php
/*
 * Railway-compatible database connection.
 *
 * Credentials are read from Railway's MySQL environment variables so the
 * same code works in local development and on Railway without changes.
 *  - MYSQLHOST     : database host (e.g. roundhouse.proxy.rlwy.net)
 *  - MYSQLPORT     : database port (e.g. 12345)
 *  - MYSQLDATABASE : database name
 *  - MYSQLUSER     : database user
 *  - MYSQLPASSWORD : database password
 *
 * Falls back to sensible defaults when a variable is not set (e.g. local dev),
 * using "127.0.0.1" with a non-empty port which avoids PDO URL warnings.
 */

$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$port = getenv('MYSQLPORT') ?: '3306';
$db   = getenv('MYSQLDATABASE') ?: 'areach';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';

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
