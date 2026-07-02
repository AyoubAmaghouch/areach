<?php
require 'config/database.php';
$tables = ['products','product_translations','product_variants','product_images','product_variant_sizes'];
foreach ($tables as $table) {
    echo "TABLE $table\n";
    $stmt = $pdo->query("DESCRIBE `$table`");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . ' | ' . $row['Type'] . "\n";
    }
    echo "\n";
}
