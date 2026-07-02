<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

function redirectAfterVariantDelete(int $productId, string $message): never
{
    $_SESSION['variant_flash'] = ['message' => $message];
    header('Location: manage-variants.php?id=' . $productId);
    exit;
}

function removeProductFile(string $filename): void
{
    $safeFilename = basename($filename);
    if ($safeFilename !== $filename || $safeFilename === '') {
        return;
    }

    $uploadDirectory = dirname(__DIR__, 3) . '/assets/uploads/products';
    $path = $uploadDirectory . DIRECTORY_SEPARATOR . $safeFilename;

    if (is_file($path) && !unlink($path)) {
        error_log('Unable to delete product image: ' . $path);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../products.php');
    exit;
}

$productId = filter_var($_POST['id_product'] ?? null, FILTER_VALIDATE_INT);
$variantId = filter_var($_POST['id_variant'] ?? null, FILTER_VALIDATE_INT);

if (!$productId || $productId < 1 || !$variantId || $variantId < 1) {
    header('Location: ../../products.php');
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['variant_csrf_token'])
    || !hash_equals($_SESSION['variant_csrf_token'], (string) $_POST['csrf_token'])
) {
    redirectAfterVariantDelete($productId, 'Invalid request token.');
}

try {
    $pdo->beginTransaction();

    $variantStatement = $pdo->prepare(
        'SELECT id_variant
         FROM product_variants
         WHERE id_variant = :id_variant AND id_product = :id_product
         FOR UPDATE'
    );
    $variantStatement->execute(['id_variant' => $variantId, 'id_product' => $productId]);

    if (!$variantStatement->fetch()) {
        throw new InvalidArgumentException('Variant not found.');
    }

    $imageStatement = $pdo->prepare(
        'SELECT image FROM product_images WHERE id_variant = :id_variant FOR UPDATE'
    );
    $imageStatement->execute(['id_variant' => $variantId]);
    $filenames = array_column($imageStatement->fetchAll(), 'image');

    $deleteSizes = $pdo->prepare('DELETE FROM product_variant_sizes WHERE id_variant = :id_variant');
    $deleteSizes->execute(['id_variant' => $variantId]);

    $deleteImages = $pdo->prepare('DELETE FROM product_images WHERE id_variant = :id_variant');
    $deleteImages->execute(['id_variant' => $variantId]);

    $deleteVariant = $pdo->prepare(
        'DELETE FROM product_variants
         WHERE id_variant = :id_variant AND id_product = :id_product'
    );
    $deleteVariant->execute(['id_variant' => $variantId, 'id_product' => $productId]);

    $pdo->commit();

    foreach ($filenames as $filename) {
        removeProductFile((string) $filename);
    }

    redirectAfterVariantDelete($productId, 'Variant deleted.');
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirectAfterVariantDelete($productId, $exception->getMessage());
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($exception->getMessage());
    redirectAfterVariantDelete($productId, 'The variant could not be deleted.');
}
