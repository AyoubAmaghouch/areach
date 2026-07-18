<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login');
    exit;
}

function redirectAfterImageDelete(int $productId, string $message): never
{
    $_SESSION['variant_flash'] = ['message' => $message, 'type' => 'error'];
    header('Location: edit?id=' . $productId);
    exit;
}

function redirectAfterImageDeleteSuccess(int $productId, string $message): never
{
    $_SESSION['variant_flash'] = ['message' => $message, 'type' => 'success'];
    header('Location: edit?id=' . $productId);
    exit;
}

function deleteVariantImageFile(string $filename): void
{
    $safeFilename = basename($filename);
    if ($safeFilename !== $filename || $safeFilename === '') {
        return;
    }

    $projectRoot = dirname(__DIR__, 3);
    $paths = [
        $projectRoot . '/assets/uploads/products/' . $safeFilename,
        $projectRoot . '/assets/images/products/' . $safeFilename,
    ];

    $productRoot = $projectRoot . '/assets/images/products';
    if (is_dir($productRoot)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($productRoot, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $safeFilename) {
                $paths[] = $file->getPathname();
            }
        }
    }

    foreach (array_unique($paths) as $path) {
        if (is_file($path) && !unlink($path)) {
            error_log('Unable to delete product image: ' . $path);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../products');
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
    header('Location: ../../products');
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
            'SELECT pi.id_image, pi.id_variant
             FROM product_images pi
             INNER JOIN product_variants pv ON pv.id_variant = pi.id_variant
             WHERE pv.id_product = :id_product
             ORDER BY pi.id_image ASC
             LIMIT 1'
        );
        $nextImageStatement->execute(['id_product' => $productId]);
        $nextImage = $nextImageStatement->fetch();

        if ($nextImage) {
            $setPrimaryStatement = $pdo->prepare(
                'UPDATE product_images
                 SET is_primary = 1
                 WHERE id_image = :id_image AND id_variant = :id_variant'
            );
            $setPrimaryStatement->execute([
                'id_image' => (int) $nextImage['id_image'],
                'id_variant' => (int) $nextImage['id_variant'],
            ]);
        }
    }

    $pdo->commit();
    deleteVariantImageFile((string) $image['image']);
    redirectAfterImageDeleteSuccess($productId, 'Image deleted.');
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
