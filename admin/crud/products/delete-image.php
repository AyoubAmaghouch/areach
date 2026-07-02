<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

function redirectAfterImageDelete(int $productId, string $message): never
{
    $_SESSION['variant_flash'] = ['message' => $message];
    header('Location: manage-variants.php?id=' . $productId);
    exit;
}

function deleteVariantImageFile(string $filename): void
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
$imageId = filter_var($_POST['id_image'] ?? null, FILTER_VALIDATE_INT);

if (
    !$productId || $productId < 1
    || !$variantId || $variantId < 1
    || !$imageId || $imageId < 1
) {
    header('Location: ../../products.php');
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['variant_csrf_token'])
    || !hash_equals($_SESSION['variant_csrf_token'], (string) $_POST['csrf_token'])
) {
    redirectAfterImageDelete($productId, 'Invalid request token.');
}

try {
    $pdo->beginTransaction();

    $imageStatement = $pdo->prepare(
        'SELECT pi.image, pi.is_primary
         FROM product_images pi
         INNER JOIN product_variants pv ON pv.id_variant = pi.id_variant
         WHERE pi.id_image = :id_image
           AND pi.id_variant = :id_variant
           AND pv.id_product = :id_product
         FOR UPDATE'
    );
    $imageStatement->execute([
        'id_image' => $imageId,
        'id_variant' => $variantId,
        'id_product' => $productId,
    ]);
    $image = $imageStatement->fetch();

    if (!$image) {
        throw new InvalidArgumentException('Image not found.');
    }

    $deleteStatement = $pdo->prepare(
        'DELETE FROM product_images
         WHERE id_image = :id_image AND id_variant = :id_variant'
    );
    $deleteStatement->execute(['id_image' => $imageId, 'id_variant' => $variantId]);

    if ((int) $image['is_primary'] === 1) {
        $nextImageStatement = $pdo->prepare(
            'SELECT id_image
             FROM product_images
             WHERE id_variant = :id_variant
             ORDER BY id_image ASC
             LIMIT 1'
        );
        $nextImageStatement->execute(['id_variant' => $variantId]);
        $nextImageId = $nextImageStatement->fetchColumn();

        if ($nextImageId !== false) {
            $setPrimaryStatement = $pdo->prepare(
                'UPDATE product_images
                 SET is_primary = 1
                 WHERE id_image = :id_image AND id_variant = :id_variant'
            );
            $setPrimaryStatement->execute([
                'id_image' => $nextImageId,
                'id_variant' => $variantId,
            ]);
        }
    }

    $pdo->commit();
    deleteVariantImageFile((string) $image['image']);
    redirectAfterImageDelete($productId, 'Image deleted.');
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirectAfterImageDelete($productId, $exception->getMessage());
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($exception->getMessage());
    redirectAfterImageDelete($productId, 'The image could not be deleted.');
}
