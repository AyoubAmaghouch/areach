<?php
require 'config/database.php';
$stmt = $pdo->query('SELECT COUNT(*) FROM products');
echo $stmt->fetchColumn();
