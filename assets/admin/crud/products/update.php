<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../products");
    exit;
}

$id_product = (int) ($_POST['id_product'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$reference = trim((string) ($_POST['reference'] ?? ''));
$id_category = (int) ($_POST['id_category'] ?? 0);
$status = (int) ($_POST['status'] ?? 0);
$allowedSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
$selectedSizes = array_values(array_unique(array_map('strval', (array) ($_POST['sizes'] ?? []))));

if (array_diff($selectedSizes, $allowedSizes)) {
    die("Selection de taille invalide.");
}

$selectedSizes = array_values(array_intersect($allowedSizes, $selectedSizes));

if (
    empty($name) ||
    empty($description) ||
    empty($reference) ||
    empty($id_category)
) {
    die("Tous les champs sont obligatoires.");
}

// Verifier si la reference existe deja
$stmt = $pdo->prepare("
SELECT id_product
FROM products
WHERE reference = ?
AND id_product <> ?
");

$stmt->execute([$reference, $id_product]);

if ($stmt->fetch()) {
    die("Cette reference existe deja.");
}

$pdo->beginTransaction();

try {
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

    // Update traduction francaise
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

    $variantStatement = $pdo->prepare("
    SELECT id_variant
    FROM product_variants
    WHERE id_product = ?
    ");
    $variantStatement->execute([$id_product]);
    $variantIds = array_map(static fn (array $variant): int => (int) $variant['id_variant'], $variantStatement->fetchAll());

    if ($variantIds !== []) {
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $deleteSizes = $pdo->prepare("DELETE FROM product_variant_sizes WHERE id_variant IN ($placeholders)");
        $deleteSizes->execute($variantIds);

        if ($selectedSizes !== []) {
            $insertSize = $pdo->prepare("INSERT INTO product_variant_sizes (id_variant, size) VALUES (?, ?)");
            foreach ($variantIds as $variantId) {
                foreach ($selectedSizes as $size) {
                    $insertSize->execute([$variantId, $size]);
                }
            }
        }
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($exception->getMessage());
    die("Le produit n'a pas pu etre modifie.");
}

$_SESSION['product_flash'] = ['type' => 'success', 'message' => 'Produit modifie avec succes.'];
header("Location: edit?id=" . $id_product);
exit;
