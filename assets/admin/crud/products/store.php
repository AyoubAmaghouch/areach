<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

function redirectWithMessage(string $message, string $type = 'success'): never
{
    $_SESSION['product_flash'] = ['message' => $message, 'type' => $type];
    header('Location: ../../products.php');
    exit;
}

function dumpQueryError(PDO $pdo, string $query, array $params = [], ?Throwable $exception = null): never
{
    $errorInfo = $pdo->errorInfo();
    $message = $exception ? $exception->getMessage() : 'Unknown error';
    $file = $exception ? $exception->getFile() : __FILE__;
    $line = $exception ? $exception->getLine() : __LINE__;

    echo '<pre>';
    echo 'SQL QUERY: ' . $query . PHP_EOL;
    echo 'PARAMS: ' . json_encode($params, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo 'PDO ERROR INFO: ' . json_encode($errorInfo, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo 'EXCEPTION: ' . $message . PHP_EOL;
    echo 'FILE: ' . $file . PHP_EOL;
    echo 'LINE: ' . $line . PHP_EOL;
    echo '</pre>';
    exit;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'product';
}

function createDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException('Le dossier de tÃ©lÃ©chargement ne peut pas Ãªtre crÃ©Ã©.');
    }
}

function getLanguageId(PDO $pdo, string $code): int
{
    $stmt = $pdo->prepare('SELECT id_language FROM languages WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $language = $stmt->fetch();

    return (int) ($language['id_language'] ?? 0);
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE ?');
    $stmt->execute([$column]);

    return (bool) $stmt->fetch();
}

function validateImageFile(array $file, bool $required = true): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_PARTIAL) {
        return [];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Une erreur est survenue lors du téléchargement des images.');
    }

    if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || trim($file['tmp_name']) === '' || !is_uploaded_file($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return [];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $mime = $file['type'] ?? '';

    if (!in_array($mime, $allowedTypes, true)) {
        throw new InvalidArgumentException('Seuls les formats JPG, PNG et WEBP sont acceptés.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Chaque image doit faire moins de 5 Mo.');
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new InvalidArgumentException('Le fichier téléchargé n\'est pas une image valide.');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $extension = strtolower($extension);

    return [
        'name' => $file['name'],
        'tmp_name' => $file['tmp_name'],
        'extension' => $extension,
    ];
}

function saveUploadedImage(array $file, string $baseDirectory, string $prefix, bool $required = true): ?string
{
    if (!$required && (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_PARTIAL)) {
        return null;
    }

    $validated = validateImageFile($file, $required);
    if ($validated === []) {
        return null;
    }

    $baseName = slugify(pathinfo($validated['name'], PATHINFO_FILENAME));
    $targetName = $prefix . '-' . $baseName . '-' . uniqid('', true) . '.' . $validated['extension'];
    $targetPath = $baseDirectory . '/' . $targetName;
    if (!move_uploaded_file($validated['tmp_name'], $targetPath)) {
        throw new RuntimeException('Une image n\'a pas pu Ãªtre enregistrÃ©e.');
    }

    return $targetName;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('La méthode de requête est invalide.', 'danger');
}

$name = trim((string) ($_POST['name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$reference = trim((string) ($_POST['reference'] ?? ''));
$idCategory = (int) ($_POST['id_category'] ?? 0);
$status = (int) ($_POST['status'] ?? 1);
$price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
$promotionPrice = trim((string) ($_POST['promotion_price'] ?? ''));
$promotionValue = $promotionPrice === '' ? null : filter_var($promotionPrice, FILTER_VALIDATE_FLOAT);
$stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
$stock = $stock === false ? 0 : $stock;
$lowStockAlert = trim((string) ($_POST['low_stock_alert'] ?? ''));
$lowStockValue = $lowStockAlert === '' ? null : filter_var($lowStockAlert, FILTER_VALIDATE_INT);
$isFeatured = isset($_POST['is_featured']) ? 1 : 0;
$isNewArrival = isset($_POST['is_new_arrival']) ? 1 : 0;

if ($name === '' || $description === '' || $reference === '' || $idCategory <= 0 || $price === false) {
    redirectWithMessage('Veuillez remplir tous les champs obligatoires.', 'danger');
}

if ($price < 0) {
    redirectWithMessage('Le prix doit être positif.', 'danger');
}

if ($promotionValue !== null && ($promotionValue < 0 || $promotionValue >= $price)) {
    redirectWithMessage('Le prix promo doit être inférieur au prix régulier.', 'danger');
}

if ($lowStockValue !== null && $lowStockValue < 0) {
    redirectWithMessage('L\'alerte de stock faible doit être positive.', 'danger');
}

$existingProduct = $pdo->prepare('SELECT id_product FROM products WHERE reference = ? LIMIT 1');
$existingProduct->execute([$reference]);
if ($existingProduct->fetch()) {
    redirectWithMessage('Cette référence existe déjà.', 'danger');
}

$productId = 0;
$languageId = getLanguageId($pdo, 'fr');
$discountPercentage = null;
$variantId = 0;
$size = '';
$colorName = '';
$stockValue = 0;
$mainImageName = null;
if ($languageId === 0) {
    redirectWithMessage('Langue par défaut introuvable.', 'danger');
}

$baseImageDirectory = __DIR__ . '/../../../assets/images/products/' . $idCategory;
createDirectory($baseImageDirectory);

$pdo->beginTransaction();

try {
    $productStatement = $pdo->prepare('INSERT INTO products (id_category, reference, status, created_at) VALUES (?, ?, ?, NOW())');
    $productStatement->execute([$idCategory, $reference, $status]);
    $productId = (int) $pdo->lastInsertId();

    $translationStatement = $pdo->prepare('INSERT INTO product_translations (id_product, id_language, name, description) VALUES (?, ?, ?, ?)');
    $translationStatement->execute([$productId, $languageId, $name, $description]);

    $discountPercentage = null;
    if ($promotionValue !== null && $price > 0) {
        $discountPercentage = (int) round((($price - $promotionValue) / $price) * 100);
    }

    if (columnExists($pdo, 'products', 'is_featured')) {
        $pdo->prepare('UPDATE products SET is_featured = ? WHERE id_product = ?')->execute([$isFeatured, $productId]);
    }

    if (columnExists($pdo, 'products', 'is_new_arrival')) {
        $pdo->prepare('UPDATE products SET is_new_arrival = ? WHERE id_product = ?')->execute([$isNewArrival, $productId]);
    }

    $variantGroups = $_POST['variant_colors'] ?? [];
    $variantGroups = is_array($variantGroups) ? $variantGroups : [];
    $createdVariants = 0;
    $imageStatement = $pdo->prepare('INSERT INTO product_images (id_variant, image, is_primary) VALUES (?, ?, ?)');

    if (!empty($variantGroups)) {
        foreach ($variantGroups as $groupIndex => $groupData) {
            $colorName = trim((string) ($groupData['name'] ?? ''));
            if ($colorName === '') {
                continue;
            }

            $sizes = array_values(array_unique(array_filter(array_map('trim', (array) ($groupData['sizes'] ?? [])), static fn ($value): bool => $value !== '')));
            $sizeStocks = $groupData['stock'] ?? [];

            if ($sizes === []) {
                $sizes = ['ONE SIZE'];
            }

            $variantStock = 0;
            foreach ($sizes as $size) {
                $stockValue = filter_var($sizeStocks[$size] ?? 0, FILTER_VALIDATE_INT);
                $stockValue = $stockValue === false ? 0 : $stockValue;
                $variantStock += max(0, $stockValue);
            }

            $variantStatement = $pdo->prepare('INSERT INTO product_variants (id_product, color_name, color_code, price, promotion_price, promotion_start, promotion_end, discount_percentage, stock, status) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?, 1)');
            $variantStatement->execute([
                $productId,
                $colorName,
                null,
                number_format($price, 2, '.', ''),
                $promotionValue === null ? null : number_format($promotionValue, 2, '.', ''),
                $discountPercentage,
                $variantStock,
            ]);
            $variantId = (int) $pdo->lastInsertId();
            $createdVariants++;

            if (columnExists($pdo, 'product_variants', 'low_stock_alert')) {
                $pdo->prepare('UPDATE product_variants SET low_stock_alert = ? WHERE id_variant = ?')->execute([$lowStockValue, $variantId]);
            }

            $sizeStatement = $pdo->prepare('INSERT INTO product_variant_sizes (id_variant, size) VALUES (?, ?)');
            foreach ($sizes as $size) {
                if ($size !== 'ONE SIZE') {
                    $sizeStatement->execute([$variantId, $size]);
                }
            }

            $variantFiles = $_FILES['variant_colors'] ?? [];
            $groupFileEntry = $variantFiles['name'][$groupIndex] ?? [];
            $mainImageFile = [
                'name' => $groupFileEntry['main_image'] ?? '',
                'tmp_name' => $variantFiles['tmp_name'][$groupIndex]['main_image'] ?? '',
                'size' => $variantFiles['size'][$groupIndex]['main_image'] ?? 0,
                'error' => $variantFiles['error'][$groupIndex]['main_image'] ?? UPLOAD_ERR_NO_FILE,
                'type' => $variantFiles['type'][$groupIndex]['main_image'] ?? '',
            ];

            $hasPrimaryImage = false;
            $mainImageName = saveUploadedImage($mainImageFile, $baseImageDirectory, 'variant-main', false);
            if ($mainImageName !== null) {
                $imageStatement->execute([$variantId, $mainImageName, 1]);
                $hasPrimaryImage = true;
            }

            $galleryFiles = [];
            $galleryNames = $variantFiles['name'][$groupIndex]['gallery_images'] ?? [];
            if (!empty($galleryNames) && is_array($galleryNames)) {
                foreach ($galleryNames as $galleryIndex => $galleryName) {
                    if ($galleryName === '') {
                        continue;
                    }
                    $galleryFiles[] = [
                        'name' => $galleryName,
                        'tmp_name' => $variantFiles['tmp_name'][$groupIndex]['gallery_images'][$galleryIndex] ?? '',
                        'size' => $variantFiles['size'][$groupIndex]['gallery_images'][$galleryIndex] ?? 0,
                        'error' => $variantFiles['error'][$groupIndex]['gallery_images'][$galleryIndex] ?? UPLOAD_ERR_NO_FILE,
                        'type' => $variantFiles['type'][$groupIndex]['gallery_images'][$galleryIndex] ?? '',
                    ];
                }
            }

            foreach (array_slice($galleryFiles, 0, 5) as $galleryFile) {
                $galleryImageName = saveUploadedImage($galleryFile, $baseImageDirectory, 'variant-gallery', false);
                if ($galleryImageName !== null) {
                    $imageStatement->execute([$variantId, $galleryImageName, $hasPrimaryImage ? 0 : 1]);
                    $hasPrimaryImage = true;
                }
            }
        }
    }

    if ($createdVariants === 0) {
        $variantStatement = $pdo->prepare('INSERT INTO product_variants (id_product, price, promotion_price, promotion_start, promotion_end, discount_percentage, stock, status) VALUES (?, ?, ?, NULL, NULL, ?, ?, 1)');
        $variantStatement->execute([$productId, number_format($price, 2, '.', ''), $promotionValue === null ? null : number_format($promotionValue, 2, '.', ''), $discountPercentage, $stock]);
        $variantId = (int) $pdo->lastInsertId();

        if (columnExists($pdo, 'product_variants', 'low_stock_alert')) {
            $pdo->prepare('UPDATE product_variants SET low_stock_alert = ? WHERE id_variant = ?')->execute([$lowStockValue, $variantId]);
        }

        $mainImageName = saveUploadedImage($_FILES['main_image'] ?? [], $baseImageDirectory, 'main', false);
        if ($mainImageName !== null) {
            $imageStatement->execute([$variantId, $mainImageName, 1]);
        }

        $galleryImages = [];
        if (!empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
            $galleryFiles = [];
            foreach ($_FILES['gallery_images']['name'] as $index => $name) {
                if ($name === '') {
                    continue;
                }
                $galleryFiles[] = [
                    'name' => $name,
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$index],
                    'size' => $_FILES['gallery_images']['size'][$index],
                    'error' => $_FILES['gallery_images']['error'][$index],
                    'type' => $_FILES['gallery_images']['type'][$index],
                ];
            }

            $galleryImages = array_slice($galleryFiles, 0, 5);
            foreach ($galleryImages as $galleryFile) {
                $galleryImageName = saveUploadedImage($galleryFile, $baseImageDirectory, 'gallery', false);
                if ($galleryImageName !== null) {
                    $imageStatement->execute([$variantId, $galleryImageName, $mainImageName === null ? 1 : 0]);
                    $mainImageName ??= $galleryImageName;
                }
            }
        }
    }

    $pdo->commit();
    redirectWithMessage('Produit enregistré avec succès.', 'success');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $failedQuery = '';
    $failedParams = [];

    if (isset($productStatement)) {
        $failedQuery = 'INSERT INTO products (id_category, reference, status, created_at) VALUES (?, ?, ?, NOW())';
        $failedParams = [$idCategory, $reference, $status];
    } elseif (isset($translationStatement)) {
        $failedQuery = 'INSERT INTO product_translations (id_product, id_language, name, description) VALUES (?, ?, ?, ?)';
        $failedParams = [$productId, $languageId, $name, $description];
    } elseif (isset($variantStatement)) {
        $failedQuery = 'INSERT INTO product_variants (id_product, color_name, color_code, price, promotion_price, promotion_start, promotion_end, discount_percentage, stock, status) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?, 1)';
        $failedParams = [$productId, $colorName ?? null, null, number_format($price, 2, '.', ''), $promotionValue === null ? null : number_format($promotionValue, 2, '.', ''), $discountPercentage, $stockValue ?? 0];
    } elseif (isset($sizeStatement)) {
        $failedQuery = 'INSERT INTO product_variant_sizes (id_variant, size) VALUES (?, ?)';
        $failedParams = [$variantId ?? null, $size ?? null];
    } elseif (isset($imageStatement)) {
        $failedQuery = 'INSERT INTO product_images (id_variant, image, is_primary) VALUES (?, ?, ?)';
        $failedParams = [$variantId ?? null, $mainImageName ?? null, 1];
    }

    error_log($exception->getMessage() . ' | ' . $failedQuery . ' | ' . json_encode($failedParams));
    redirectWithMessage('Le produit n\'a pas pu Ãªtre enregistrÃ©.', 'error');
}
