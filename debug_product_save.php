<?php
session_start();
$_SESSION['admin_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'name' => 'Test Product',
    'description' => 'Desc',
    'reference' => 'TEST-001',
    'id_category' => 1,
    'status' => 1,
    'price' => 19.99,
    'promotion_price' => '',
    'is_featured' => 1,
    'is_new_arrival' => 1,
    'variant_colors' => []
];
$_FILES = [];
require 'admin/crud/products/store.php';
