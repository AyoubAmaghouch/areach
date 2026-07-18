<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/database.php';

// Fix: keep PDO in exception mode in this entrypoint too. The database config
// already does it, but setting it here makes this debugging file self-contained
// on AwardSpace if another config is temporarily included during tests.
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login');
    exit;
}

function redirectWithMessage(string $message, string $type = 'success'): never
{
    $_SESSION['product_flash'] = ['message' => $message, 'type' => $type];
    header('Location: ../../products');
    exit;
}

function dumpQueryError(PDO $pdo, string $query, array $params = [], ?Throwable $exception = null, ?array $statementErrorInfo = null): never
{
    // Fix: show both the connection-level errorInfo() and, when available, the
    // PDOStatement-level errorInfo(). MySQL often keeps the exact SQL error on
    // the statement, while $pdo->errorInfo() can be empty after rollback.
    $errorInfo = $pdo->errorInfo();
    $message = $exception ? $exception->getMessage() : 'Unknown error';
    $file = $exception ? $exception->getFile() : __FILE__;
    $line = $exception ? $exception->getLine() : __LINE__;

    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');

    echo '<pre>';
    echo 'SQL QUERY: ' . $query . PHP_EOL;
    echo 'PARAMS: ' . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    echo 'PDO ERROR INFO: ' . json_encode($errorInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    echo 'STATEMENT ERROR INFO: ' . json_encode($statementErrorInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    echo 'EXCEPTION CLASS: ' . ($exception ? get_class($exception) : 'None') . PHP_EOL;
    echo 'EXCEPTION: ' . $message . PHP_EOL;
    echo 'FILE: ' . $file . PHP_EOL;
    echo 'LINE: ' . $line . PHP_EOL;
    echo '</pre>';
    exit;
}

function executeSql(PDO $pdo, string $query, array $params, array &$debugSql): PDOStatement
{
    // Fix: record the query before prepare(), not only before execute(). If a
    // syntax error happens during native prepare on MySQL 8/AwardSpace, the
    // debugger now reports the real failing statement.
    $debugSql = [
        'query' => $query,
        'params' => $params,
        'statement_error_info' => null,
    ];

    try {
        $statement = $pdo->prepare($query);
        $statement->execute($params);
        $debugSql['statement_error_info'] = $statement->errorInfo();

        return $statement;
    } catch (Throwable $exception) {
        if (isset($statement) && $statement instanceof PDOStatement) {
            $debugSql['statement_error_info'] = $statement->errorInfo();
        }

        throw $exception;
    }
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
    if (is_dir($path)) {
        return;
    }

    // AwardSpace / shared hosting: 0777 is often blocked by suPHP / suEXEC.
    // Use 0755 which is the highest safe permission on most shared hosts.
    if (!@mkdir($path, 0755, true) && !is_dir($path)) {
        $lastError = error_get_last();
        $msg = $lastError['message'] ?? 'unknown mkdir failure';
        error_log('Product creation: failed to create directory ' . $path . ' - ' . $msg);
        throw new RuntimeException('Le dossier de téléchargement ne peut pas être créé.');
    }
}

function getLanguageId(PDO $pdo, string $code, array &$debugSql): int
{
    $stmt = executeSql(
        $pdo,
        'SELECT id_language FROM languages WHERE code = ? LIMIT 1',
        [$code],
        $debugSql
    );
    $language = $stmt->fetch();

    return (int) ($language['id_language'] ?? 0);
}

function columnExists(PDO $pdo, string $table, string $column, array &$debugSql): bool
{
    // Fix/root cause: the previous version used "SHOW COLUMNS FROM ... LIKE ?".
    // With PDO::ATTR_EMULATE_PREPARES=false, some MySQL/AwardSpace setups reject
    // placeholders in SHOW statements and raise SQLSTATE[42000] 1064. Because
    // columnExists() did not update the debug query, the debugger falsely showed
    // the previous INSERT INTO product_translations as the failing SQL.
    // INFORMATION_SCHEMA is valid MySQL 8 SQL and safely supports placeholders.
    $stmt = executeSql(
        $pdo,
        'SELECT COUNT(*) AS column_count
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?
         LIMIT 1',
        [$table, $column],
        $debugSql
    );

    return (int) ($stmt->fetch()['column_count'] ?? 0) > 0;
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
        throw new RuntimeException('Une image n\'a pas pu être enregistrée.');
    }

    return $targetName;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('La méthode de requête est invalide.', 'danger');
}

$debugSql = [
    'query' => 'Aucune requête SQL exécutée',
    'params' => [],
    'statement_error_info' => null,
];

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

$productId = 0;
$languageId = 0;
$discountPercentage = null;
$variantId = 0;
$size = '';
$colorName = '';
$stockValue = 0;
$mainImageName = null;
$transactionStarted = false;

try {
    $existingProduct = executeSql(
        $pdo,
        'SELECT id_product FROM products WHERE reference = ? LIMIT 1',
        [$reference],
        $debugSql
    );
    if ($existingProduct->fetch()) {
        redirectWithMessage('Cette référence existe déjà.', 'danger');
    }

    $languageId = getLanguageId($pdo, 'fr', $debugSql);
    if ($languageId === 0) {
        redirectWithMessage('Langue par défaut introuvable.', 'danger');
    }

    $baseImageDirectory = __DIR__ . '/../../../assets/images/products/' . $idCategory;
    createDirectory($baseImageDirectory);

    // Fix: beginTransaction() is inside try, so transaction errors are displayed
    // by the same complete debugger instead of causing a blank/generic failure.
    $pdo->beginTransaction();
    $transactionStarted = true;

    $productStatement = executeSql(
        $pdo,
        'INSERT INTO products (id_category, reference, status, created_at) VALUES (?, ?, ?, NOW())',
        [$idCategory, $reference, $status],
        $debugSql
    );
    $productId = (int) $pdo->lastInsertId();

    // Fix: keep the fallback for AwardSpace imports where AUTO_INCREMENT was
    // missing from products.id_product and PDO::lastInsertId() returned 0.
    if ($productId <= 0) {
        $stmt = executeSql(
            $pdo,
            'SELECT id_product FROM products WHERE reference = ? ORDER BY id_product DESC LIMIT 1',
            [$reference],
            $debugSql
        );
        $row = $stmt->fetch();
        $productId = (int) ($row['id_product'] ?? 0);
    }

    if ($productId <= 0) {
        throw new RuntimeException('Unable to resolve the new product id for reference ' . $reference);
    }

    executeSql(
        $pdo,
        'INSERT INTO product_translations (id_product, id_language, name, description) VALUES (?, ?, ?, ?)',
        [$productId, $languageId, $name, $description],
        $debugSql
    );

    $discountPercentage = 0;
    if ($promotionValue !== null && $price > 0) {
        $discountPercentage = (int) round((($price - $promotionValue) / $price) * 100);
    }

    if (columnExists($pdo, 'products', 'is_featured', $debugSql)) {
        executeSql(
            $pdo,
            'UPDATE products SET is_featured = ? WHERE id_product = ?',
            [$isFeatured, $productId],
            $debugSql
        );
    }

    if (columnExists($pdo, 'products', 'is_new_arrival', $debugSql)) {
        executeSql(
            $pdo,
            'UPDATE products SET is_new_arrival = ? WHERE id_product = ?',
            [$isNewArrival, $productId],
            $debugSql
        );
    }

    $variantGroups = $_POST['variant_colors'] ?? [];
    $variantGroups = is_array($variantGroups) ? $variantGroups : [];
    $createdVariants = 0;
    $imageSqlQuery = 'INSERT INTO product_images (id_variant, image, is_primary) VALUES (?, ?, ?)';

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

            executeSql(
                $pdo,
                'INSERT INTO product_variants (id_product, color_name, color_code, price, promotion_price, promotion_start, promotion_end, discount_percentage, stock, status) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?, 1)',
                [
                    $productId,
                    $colorName,
                    '#000000',
                    number_format($price, 2, '.', ''),
                    $promotionValue === null ? null : number_format($promotionValue, 2, '.', ''),
                    $discountPercentage,
                    $variantStock,
                ],
                $debugSql
            );
            $variantId = (int) $pdo->lastInsertId();

            // Fix: same AUTO_INCREMENT-import fallback as products. This covers
            // product_variants.id_variant before inserting sizes/images that
            // depend on its foreign key.
            if ($variantId <= 0) {
                $stmt = executeSql(
                    $pdo,
                    'SELECT id_variant FROM product_variants WHERE id_product = ? ORDER BY id_variant DESC LIMIT 1',
                    [$productId],
                    $debugSql
                );
                $row = $stmt->fetch();
                $variantId = (int) ($row['id_variant'] ?? 0);
            }

            if ($variantId <= 0) {
                throw new RuntimeException('Unable to resolve the new variant id for product ' . $productId);
            }
            $createdVariants++;

            if (columnExists($pdo, 'product_variants', 'low_stock_alert', $debugSql)) {
                executeSql(
                    $pdo,
                    'UPDATE product_variants SET low_stock_alert = ? WHERE id_variant = ?',
                    [$lowStockValue, $variantId],
                    $debugSql
                );
            }

            $sizeSqlQuery = 'INSERT INTO product_variant_sizes (id_variant, size) VALUES (?, ?)';
            foreach ($sizes as $size) {
                if ($size !== 'ONE SIZE') {
                    executeSql($pdo, $sizeSqlQuery, [$variantId, $size], $debugSql);
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
                executeSql($pdo, $imageSqlQuery, [$variantId, $mainImageName, 1], $debugSql);
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
                    executeSql($pdo, $imageSqlQuery, [$variantId, $galleryImageName, $hasPrimaryImage ? 0 : 1], $debugSql);
                    $hasPrimaryImage = true;
                }
            }
        }
    }

    if ($createdVariants === 0) {
        executeSql(
            $pdo,
            'INSERT INTO product_variants (id_product, price, promotion_price, promotion_start, promotion_end, discount_percentage, stock, status) VALUES (?, ?, ?, NULL, NULL, ?, ?, 1)',
            [$productId, number_format($price, 2, '.', ''), $promotionValue === null ? null : number_format($promotionValue, 2, '.', ''), $discountPercentage, $stock],
            $debugSql
        );
        $variantId = (int) $pdo->lastInsertId();

        // Fix: the original no-color/default-variant path used lastInsertId()
        // without the AwardSpace fallback. If product_variants.id_variant had
        // lost AUTO_INCREMENT, images and low_stock_alert updates used id 0.
        if ($variantId <= 0) {
            $stmt = executeSql(
                $pdo,
                'SELECT id_variant FROM product_variants WHERE id_product = ? ORDER BY id_variant DESC LIMIT 1',
                [$productId],
                $debugSql
            );
            $row = $stmt->fetch();
            $variantId = (int) ($row['id_variant'] ?? 0);
        }

        if ($variantId <= 0) {
            throw new RuntimeException('Unable to resolve the new default variant id for product ' . $productId);
        }

        if (columnExists($pdo, 'product_variants', 'low_stock_alert', $debugSql)) {
            executeSql(
                $pdo,
                'UPDATE product_variants SET low_stock_alert = ? WHERE id_variant = ?',
                [$lowStockValue, $variantId],
                $debugSql
            );
        }

        $mainImageName = saveUploadedImage($_FILES['main_image'] ?? [], $baseImageDirectory, 'main', false);
        if ($mainImageName !== null) {
            executeSql($pdo, $imageSqlQuery, [$variantId, $mainImageName, 1], $debugSql);
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
                    executeSql($pdo, $imageSqlQuery, [$variantId, $galleryImageName, $mainImageName === null ? 1 : 0], $debugSql);
                    $mainImageName ??= $galleryImageName;
                }
            }
        }
    }

    $pdo->commit();
    $transactionStarted = false;
    redirectWithMessage('Produit enregistré avec succès.', 'success');
} catch (Throwable $exception) {
    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        get_class($exception) . ': ' . $exception->getMessage()
        . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine()
    );

    $pdoError = $pdo->errorInfo();
    if (!empty($pdoError[2])) {
        error_log('PDO errorInfo: ' . $pdoError[2]);
    }

    dumpQueryError(
        $pdo,
        $debugSql['query'] ?? 'Requête SQL inconnue',
        $debugSql['params'] ?? [],
        $exception,
        $debugSql['statement_error_info'] ?? null
    );
}
