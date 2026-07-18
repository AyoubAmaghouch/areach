<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/app.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login');
    exit;
}

function redirectToVariants(int $productId, string $message): never
{
    $_SESSION['variant_flash'] = ['message' => $message, 'type' => 'error'];
    header('Location: edit?id=' . $productId);
    exit;
}

function redirectToVariantsSuccess(int $productId, string $message): never
{
    $_SESSION['variant_flash'] = ['message' => $message, 'type' => 'success'];
    header('Location: edit?id=' . $productId);
    exit;
}

function validVariantData(array $input): array
{
    $colorName = trim((string) ($input['color_name'] ?? ''));
    $colorCode = strtoupper(trim((string) ($input['color_code'] ?? '')));
    $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT);
    $promotionRaw = trim((string) ($input['promotion_price'] ?? ''));
    $promotionPrice = $promotionRaw === '' ? null : filter_var($promotionRaw, FILTER_VALIDATE_FLOAT);
    $promotionStartRaw = trim((string) ($input['promotion_start'] ?? ''));
    $promotionEndRaw = trim((string) ($input['promotion_end'] ?? ''));
    $stock = filter_var(
        $input['stock'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0, 'max_range' => 2147483647]]
    );
    $discount = filter_var($input['discount_percentage'] ?? null, FILTER_VALIDATE_INT);
    $status = filter_var($input['status'] ?? null, FILTER_VALIDATE_INT);

    if ($colorName === '' || mb_strlen($colorName) > 100) {
        throw new InvalidArgumentException('Color name is required and must not exceed 100 characters.');
    }
    if (!preg_match('/^#[0-9A-F]{6}$/', $colorCode)) {
        throw new InvalidArgumentException('Invalid color value.');
    }
    if ($price === false || $price < 0 || $price > 99999999.99) {
        throw new InvalidArgumentException('Invalid price.');
    }
    if ($promotionRaw !== '' && ($promotionPrice === false || $promotionPrice < 0 || $promotionPrice >= $price)) {
        throw new InvalidArgumentException('Promotion price must be lower than the normal price.');
    }
    if ($stock === false) {
        throw new InvalidArgumentException('Stock must be a non-negative whole number.');
    }
    if ($discount === false || $discount < 0 || $discount > 100) {
        throw new InvalidArgumentException('Discount percentage must be between 0 and 100.');
    }
    if (!in_array($status, [0, 1], true)) {
        throw new InvalidArgumentException('Invalid status.');
    }

    $promotionStart = null;
    $promotionEnd = null;

    if ($promotionPrice !== null) {
        if ($promotionStartRaw === '' || $promotionEndRaw === '') {
            throw new InvalidArgumentException('Promotion start and end dates are required when a promotion price is set.');
        }

        $promotionStartDate = DateTimeImmutable::createFromFormat('!Y-m-d', $promotionStartRaw);
        $promotionEndDate = DateTimeImmutable::createFromFormat('!Y-m-d', $promotionEndRaw);

        if (
            !$promotionStartDate
            || $promotionStartDate->format('Y-m-d') !== $promotionStartRaw
            || !$promotionEndDate
            || $promotionEndDate->format('Y-m-d') !== $promotionEndRaw
        ) {
            throw new InvalidArgumentException('Invalid promotion date.');
        }
        if ($promotionEndDate < $promotionStartDate) {
            throw new InvalidArgumentException('Promotion end date cannot be before its start date.');
        }

        $promotionStart = $promotionStartRaw;
        $promotionEnd = $promotionEndRaw;
    }

    $sizes = array_values(array_unique(array_map('strval', (array) ($input['sizes'] ?? []))));
    $allowedSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    if (array_diff($sizes, $allowedSizes)) {
        throw new InvalidArgumentException('Invalid size selection.');
    }

    $sizes = array_values(array_intersect($allowedSizes, $sizes));

    return [
        'color_name' => $colorName,
        'color_code' => $colorCode,
        'price' => number_format((float) $price, 2, '.', ''),
        'promotion_price' => $promotionPrice === null ? null : number_format((float) $promotionPrice, 2, '.', ''),
        'promotion_start' => $promotionStart,
        'promotion_end' => $promotionEnd,
        'stock' => $stock,
        'discount_percentage' => $discount,
        'status' => $status,
        'sizes' => $sizes,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../products');
    exit;
}

$productId = filter_var($_POST['id_product'] ?? null, FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    header('Location: ../../products');
    exit;
}

if (
    !isset($_POST['csrf_token'], $_SESSION['variant_csrf_token'])
    || !hash_equals($_SESSION['variant_csrf_token'], (string) $_POST['csrf_token'])
) {
    redirectToVariants($productId, 'Invalid request token.');
}

try {
    $data = validVariantData($_POST);

    $productStatement = $pdo->prepare('SELECT id_product FROM products WHERE id_product = :id_product');
    $productStatement->execute(['id_product' => $productId]);

    if (!$productStatement->fetch()) {
        redirectToVariants($productId, 'Product not found.');
    }

    $pdo->beginTransaction();

    $variantStatement = $pdo->prepare(
        'INSERT INTO product_variants
            (id_product, color_name, color_code, price, promotion_price, promotion_start,
             promotion_end, stock, discount_percentage, status)
         VALUES
            (:id_product, :color_name, :color_code, :price, :promotion_price, :promotion_start,
             :promotion_end, :stock, :discount_percentage, :status)'
    );
    $variantStatement->execute([
        'id_product' => $productId,
        'color_name' => $data['color_name'],
        'color_code' => $data['color_code'],
        'price' => $data['price'],
        'promotion_price' => $data['promotion_price'],
        'promotion_start' => $data['promotion_start'],
        'promotion_end' => $data['promotion_end'],
        'stock' => $data['stock'],
        'discount_percentage' => $data['discount_percentage'],
        'status' => $data['status'],
    ]);

    $variantId = (int) $pdo->lastInsertId();
    $sizeStatement = $pdo->prepare(
        'INSERT INTO product_variant_sizes (id_variant, size) VALUES (:id_variant, :size)'
    );

    foreach ($data['sizes'] as $size) {
        $sizeStatement->execute(['id_variant' => $variantId, 'size' => $size]);
    }

    $pdo->commit();
    redirectToVariantsSuccess($productId, 'Variant saved.');
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirectToVariants($productId, $exception->getMessage());
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($exception->getMessage());
    redirectToVariants($productId, 'The variant could not be saved.');
}
