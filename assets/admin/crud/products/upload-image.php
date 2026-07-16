<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

const MAX_VARIANT_IMAGE_SIZE = 5 * 1024 * 1024;

function redirectAfterImageUpload(int $productId, string $message): never
{
    $_SESSION['variant_flash'] = ['message' => $message, 'type' => 'error'];
    header('Location: edit.php?id=' . $productId);
    exit;
}

function redirectAfterImageUploadSuccess(int $productId, string $message): never
{
    $_SESSION['variant_flash'] = ['message' => $message, 'type' => 'success'];
    header('Location: edit.php?id=' . $productId);
    exit;
}

function normalizedImageUploads(array $files): array
{
    if (
        !isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])
        || !is_array($files['name'])
    ) {
        throw new InvalidArgumentException('No images were selected.');
    }

    $uploads = [];
    foreach ($files['name'] as $index => $name) {
        $uploads[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
            'error' => (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($files['size'][$index] ?? 0),
        ];
    }

    return $uploads;
}

function validatedImageUpload(array $upload, finfo $finfo): array
{
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('One of the images failed to upload.');
    }
    if ($upload['size'] < 1 || $upload['size'] > MAX_VARIANT_IMAGE_SIZE) {
        throw new InvalidArgumentException('Each image must be no larger than 5 MB.');
    }
    if (!is_uploaded_file($upload['tmp_name'])) {
        throw new InvalidArgumentException('Invalid uploaded image.');
    }

    $mimeType = $finfo->file($upload['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
        throw new InvalidArgumentException('Only JPG, PNG, and WEBP images are allowed.');
    }
    if (@getimagesize($upload['tmp_name']) === false) {
        throw new InvalidArgumentException('The uploaded file is not a valid image.');
    }

    return [
        'tmp_name' => $upload['tmp_name'],
        'filename' => bin2hex(random_bytes(20)) . '.' . $extensions[$mimeType],
    ];
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
    redirectAfterImageUpload($productId, 'Invalid request token.');
}

try {
    $variantStatement = $pdo->prepare(
        'SELECT id_variant
         FROM product_variants
         WHERE id_variant = :id_variant AND id_product = :id_product'
    );
    $variantStatement->execute(['id_variant' => $variantId, 'id_product' => $productId]);

    if (!$variantStatement->fetch()) {
        throw new InvalidArgumentException('Variant not found.');
    }

    if (($_POST['action'] ?? '') === 'set_primary') {
        $imageId = filter_var($_POST['id_image'] ?? null, FILTER_VALIDATE_INT);
        if (!$imageId || $imageId < 1) {
            throw new InvalidArgumentException('Invalid image.');
        }

        $pdo->beginTransaction();

        $imageStatement = $pdo->prepare(
            'SELECT id_image
             FROM product_images
             WHERE id_image = :id_image AND id_variant = :id_variant
             FOR UPDATE'
        );
        $imageStatement->execute(['id_image' => $imageId, 'id_variant' => $variantId]);

        if (!$imageStatement->fetch()) {
            throw new InvalidArgumentException('Image not found.');
        }

        $clearPrimary = $pdo->prepare(
            'UPDATE product_images
             SET is_primary = 0
             WHERE id_variant IN (
                 SELECT id_variant
                 FROM product_variants
                 WHERE id_product = :id_product
             )'
        );
        $clearPrimary->execute(['id_product' => $productId]);

        $setPrimary = $pdo->prepare(
            'UPDATE product_images
             SET is_primary = 1
             WHERE id_image = :id_image AND id_variant = :id_variant'
        );
        $setPrimary->execute(['id_image' => $imageId, 'id_variant' => $variantId]);

        $pdo->commit();
        redirectAfterImageUploadSuccess($productId, 'Primary image updated.');
    }

    $uploads = normalizedImageUploads($_FILES['images'] ?? []);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $validatedUploads = [];

    foreach ($uploads as $upload) {
        $validatedUploads[] = validatedImageUpload($upload, $finfo);
    }

    if (!$validatedUploads) {
        throw new InvalidArgumentException('No images were selected.');
    }

    $uploadDirectory = dirname(__DIR__, 3) . '/assets/uploads/products';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('The image upload directory could not be created.');
    }
    if (!is_writable($uploadDirectory)) {
        throw new RuntimeException('The image upload directory is not writable.');
    }

    $movedPaths = [];
    $pdo->beginTransaction();

    $primaryStatement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM product_images pi
         INNER JOIN product_variants pv ON pv.id_variant = pi.id_variant
         WHERE pv.id_product = :id_product
           AND pi.is_primary = 1'
    );
    $primaryStatement->execute(['id_product' => $productId]);
    $hasPrimary = (int) $primaryStatement->fetchColumn() > 0;
    $makePrimary = isset($_POST['make_primary']) && $_POST['make_primary'] === '1';

    if ($makePrimary) {
        $clearPrimary = $pdo->prepare(
            'UPDATE product_images
             SET is_primary = 0
             WHERE id_variant IN (
                 SELECT id_variant
                 FROM product_variants
                 WHERE id_product = :id_product
             )'
        );
        $clearPrimary->execute(['id_product' => $productId]);
    }

    $insertImage = $pdo->prepare(
        'INSERT INTO product_images (id_variant, image, is_primary)
         VALUES (:id_variant, :image, :is_primary)'
    );

    foreach ($validatedUploads as $index => $upload) {
        $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $upload['filename'];

        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            throw new RuntimeException('An image could not be stored.');
        }
        $movedPaths[] = $destination;

        $isPrimary = $index === 0 && ($makePrimary || !$hasPrimary) ? 1 : 0;
        $insertImage->execute([
            'id_variant' => $variantId,
            'image' => $upload['filename'],
            'is_primary' => $isPrimary,
        ]);
    }

    $pdo->commit();
    redirectAfterImageUploadSuccess($productId, 'Images uploaded.');
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($movedPaths)) {
        foreach ($movedPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
    redirectAfterImageUpload($productId, $exception->getMessage());
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($movedPaths)) {
        foreach ($movedPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
    error_log($exception->getMessage());
    redirectAfterImageUpload($productId, 'The images could not be updated.');
}
