<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../products.php");
    exit;
}

$id_product = (int) $_POST['id_product'];
$name = trim($_POST['name']);
$description = trim($_POST['description']);
$reference = trim($_POST['reference']);
$id_category = (int) $_POST['id_category'];
$status = (int) $_POST['status'];

if (
    empty($name) ||
    empty($description) ||
    empty($reference) ||
    empty($id_category)
) {
    die("Tous les champs sont obligatoires.");
}

// Vérifier si la référence existe déjà
$stmt = $pdo->prepare("
SELECT id_product
FROM products
WHERE reference = ?
AND id_product <> ?
");

$stmt->execute([$reference, $id_product]);

if ($stmt->fetch()) {
    die("Cette référence existe déjà.");
}

// Update products
$stmt = $pdo->prepare("
UPDATE products
SET
id_category = ?,
reference = ?,
status = ?
WHERE id_product = ?
");

$stmt->execute([
    $id_category,
    $reference,
    $status,
    $id_product
]);

// Update traduction française
$stmt = $pdo->prepare("
UPDATE product_translations
SET
name = ?,
description = ?
WHERE id_product = ?
AND id_language = (
    SELECT id_language
    FROM languages
    WHERE code='fr'
)
");

$stmt->execute([
    $name,
    $description,
    $id_product
]);

header("Location: ../../products.php");
exit;