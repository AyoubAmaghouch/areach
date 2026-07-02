<?php
require 'config/database.php';
$tables = ['product_translations','product_variants','product_images','product_variant_sizes'];
foreach ($tables as $table) {
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . $table);
    echo $table . ': ' . $stmt->fetchColumn() . PHP_EOL;
}
