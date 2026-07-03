<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$productId = $productId !== false && $productId > 0 ? $productId : 0;

if ($productId === 0) {
    redirect(pageUrl('shop.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $variantId = filter_input(INPUT_POST, 'variant_id', FILTER_VALIDATE_INT);
    $size = trim((string) ($_POST['size'] ?? ''));
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

    $product = getProductById($pdo, $productId, $langCode);
    $variants = getProductVariants($pdo, $productId);
    $selectedVariant = null;

    foreach ($variants as $colorVariant) {
        foreach ($colorVariant['size_options'] ?? [] as $sizeOption) {
            if (
                (int) $sizeOption['id_variant'] === $variantId
                && (string) $sizeOption['size'] === $size
            ) {
                $selectedVariant = array_merge($sizeOption, [
                    'color_name' => $colorVariant['color_name'] ?? '',
                    'images' => $colorVariant['images'] ?? [],
                ]);
                break 2;
            }
        }
    }

    if ($product && $selectedVariant && (int) $selectedVariant['stock'] > 0) {
        $price = (float) $selectedVariant['price'];
        $promotionPrice = $selectedVariant['promotion_price'] !== null ? (float) $selectedVariant['promotion_price'] : null;
        $promotionStart = $selectedVariant['promotion_start'];
        $promotionEnd = $selectedVariant['promotion_end'];
        $today = date('Y-m-d');

        if ($promotionPrice !== null && $promotionStart !== null && $promotionEnd !== null && $today >= $promotionStart && $today <= $promotionEnd) {
            $price = $promotionPrice;
        }

        $image = '';
        foreach ($selectedVariant['images'] ?? [] as $imageData) {
            if (!empty($imageData['is_primary'])) {
                $image = (string) $imageData['image'];
                break;
            }
        }

        if ($image === '' && !empty($selectedVariant['images'])) {
            $image = (string) $selectedVariant['images'][0]['image'];
        }

        addToCart([
            'id_product' => (int) $product['id_product'],
            'id_variant' => (int) $selectedVariant['id_variant'],
            'name' => $product['product_name'],
            'color_name' => $selectedVariant['color_name'] ?? '',
            'size' => $size,
            'price' => $price,
            'image' => $image,
            'quantity' => $quantity,
        ]);
    }

    redirect(pageUrl('cart.php'));
}

$product = getProductById($pdo, $productId, $langCode);

if (!$product) {
    redirect(pageUrl('shop.php'));
}

$variants = getProductVariants($pdo, $productId);
$selectedColorIndex = 0;
foreach ($variants as $colorIndex => $variant) {
    if ((int) ($variant['stock'] ?? 0) > 0) {
        $selectedColorIndex = $colorIndex;
        break;
    }
}

$selectedVariant = $variants[$selectedColorIndex] ?? null;
$selectedVariantId = $selectedVariant['id_variant'] ?? 0;
$selectedSize = '';
$selectedStock = 0;

foreach ($selectedVariant['size_options'] ?? [] as $sizeOption) {
    if ((int) $sizeOption['id_variant'] === (int) $selectedVariantId) {
        $selectedSize = (string) $sizeOption['size'];
        $selectedStock = (int) $sizeOption['stock'];
        break;
    }
}

$selectedImages = $selectedVariant['images'] ?? [];
$selectedPrimaryImage = '';

foreach ($selectedImages as $imageData) {
    if (!empty($imageData['is_primary'])) {
        $selectedPrimaryImage = (string) $imageData['image'];
        break;
    }
}

if ($selectedPrimaryImage === '' && !empty($selectedImages)) {
    $selectedPrimaryImage = (string) $selectedImages[0]['image'];
}

if ($selectedPrimaryImage === '') {
    $selectedPrimaryImage = (string) ($product['product_image'] ?? '');
}

$selectedPrice = (float) ($selectedVariant['price'] ?? 0);
$selectedPromotionPrice = $selectedVariant['promotion_price'] !== null ? (float) $selectedVariant['promotion_price'] : null;
$selectedPromotionStart = $selectedVariant['promotion_start'] ?? null;
$selectedPromotionEnd = $selectedVariant['promotion_end'] ?? null;
$today = date('Y-m-d');

if ($selectedPromotionPrice !== null && $selectedPromotionStart !== null && $selectedPromotionEnd !== null && $today >= $selectedPromotionStart && $today <= $selectedPromotionEnd) {
    $selectedPrice = $selectedPromotionPrice;
}

$variantClientData = [];
foreach ($variants as $variant) {
    $clientSizes = [];

    foreach ($variant['size_options'] ?? [] as $sizeOption) {
        $originalPrice = (float) $sizeOption['price'];
        $currentPrice = $originalPrice;
        $promotionPrice = $sizeOption['promotion_price'] !== null
            ? (float) $sizeOption['promotion_price']
            : null;
        $promotionStart = $sizeOption['promotion_start'] ?? null;
        $promotionEnd = $sizeOption['promotion_end'] ?? null;

        if (
            $promotionPrice !== null
            && $promotionStart !== null
            && $promotionEnd !== null
            && $today >= $promotionStart
            && $today <= $promotionEnd
        ) {
            $currentPrice = $promotionPrice;
        }

        $clientSizes[] = [
            'id_variant' => (int) $sizeOption['id_variant'],
            'size' => (string) $sizeOption['size'],
            'stock' => (int) $sizeOption['stock'],
            'price' => $currentPrice,
            'original_price' => $originalPrice,
            'on_sale' => $currentPrice !== $originalPrice,
        ];
    }

    $variantClientData[] = [
        'color_name' => (string) ($variant['color_name'] ?? ''),
        'stock' => (int) ($variant['stock'] ?? 0),
        'sizes' => $clientSizes,
        'images' => array_values(array_map(
            static fn (array $image): string =>
                imagePath('products', (string) ($image['image'] ?? '')),
            $variant['images'] ?? []
        )),
    ];
}

$relatedProducts = [];
if (!empty($product['id_category'])) {
    $relatedProducts = getRelatedProducts(
        $pdo,
        $productId,
        (int) $product['id_category'],
        $langCode,
        4
    );
}

$relatedVariantIds = array_column($relatedProducts, 'id_variant');
$relatedImages = getProductImagesForVariants($pdo, $relatedVariantIds);

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — ' . ($product['product_name'] ?? 'Produit');
$metaDescription = $product['product_name'] ?? 'Produit';

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title"><?= e($product['product_name'] ?? '') ?></h1>
            <p class="page-header__subtitle"><?= e($product['category_name'] ?? '') ?></p>
        </div>
    </section>

    <section class="page-section">
        <div class="container product-detail">
            <div class="product-detail__gallery" id="product-gallery">
                <div class="product-gallery__main" id="product-gallery-main">
                    <?php if ($selectedPrimaryImage !== '') : ?>
                        <img src="<?= e(imagePath('products', $selectedPrimaryImage)) ?>" alt="<?= e($product['product_name'] ?? '') ?>" class="product-detail__image" id="product-main-image">
                    <?php else : ?>
                        <div class="product-detail__placeholder" id="product-image-placeholder" aria-hidden="true">
                            <i class="fa-solid fa-shirt"></i>
                        </div>
                    <?php endif; ?>
                    <button class="product-gallery__arrow product-gallery__arrow--prev" id="gallery-arrow-prev" type="button" aria-label="Image précédente">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4A2412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button class="product-gallery__arrow product-gallery__arrow--next" id="gallery-arrow-next" type="button" aria-label="Image suivante">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4A2412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
                <div class="product-gallery__thumbs" id="product-image-thumbs" <?= empty($selectedImages) ? 'hidden' : '' ?>></div>
            </div>

            <div class="product-detail__info">
                <h2 class="product-detail__name"><?= e($product['product_name'] ?? '') ?></h2>
                <p class="product-detail__description"><?= e($product['description'] ?? '') ?></p>

                <div class="product-detail__price-block">
                    <span class="product-detail__price" id="product-current-price"><?= e(formatCurrency($selectedPrice)) ?></span>
                    <?php if (!empty($selectedVariant['promotion_price']) && $selectedVariant['promotion_price'] != $selectedVariant['price']) : ?>
                        <span class="product-detail__price-old" id="product-original-price"><?= e(formatCurrency((float) ($selectedVariant['price'] ?? 0))) ?></span>
                    <?php else : ?>
                        <span class="product-detail__price-old" id="product-original-price" hidden></span>
                    <?php endif; ?>
                </div>

                <form method="post" action="<?= pageUrl('product.php?id=' . $productId) ?>" class="product-detail__form">
                    <div class="product-detail__field">
                        <label for="product-color">Couleur</label>
                        <select id="product-color" class="input">
                            <?php foreach ($variants as $colorIndex => $variant) : ?>
                                <option
                                    value="<?= (int) $colorIndex ?>"
                                    <?= $colorIndex === $selectedColorIndex ? 'selected' : '' ?>
                                    <?= (int) ($variant['stock'] ?? 0) <= 0 ? 'disabled' : '' ?>
                                ><?= e($variant['color_name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="variant_id" id="variant_id" value="<?= (int) $selectedVariantId ?>">
                    </div>

                    <div class="product-detail__field" id="product-size-field" <?= empty($selectedVariant['size_options']) ? 'hidden' : '' ?>>
                        <label for="size">Taille</label>
                        <select name="size" id="size" class="input">
                            <?php foreach ($selectedVariant['size_options'] ?? [] as $sizeOption) : ?>
                                <option
                                    value="<?= e((string) $sizeOption['size']) ?>"
                                    data-variant-id="<?= (int) $sizeOption['id_variant'] ?>"
                                    <?= (int) $sizeOption['id_variant'] === (int) $selectedVariantId ? 'selected' : '' ?>
                                    <?= (int) $sizeOption['stock'] <= 0 ? 'disabled' : '' ?>
                                ><?= e((string) $sizeOption['size']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="product-detail__field">
                        <label for="quantity">Quantité</label>
                        <input type="number" name="quantity" id="quantity" class="input" min="1" max="<?= max(1, min(10, $selectedStock)) ?>" value="1">
                    </div>

                    <div class="product-detail__stock" id="product-stock">
                        <?php if ($selectedStock > 0) : ?>
                            <span class="product-detail__stock--in">En stock</span>
                        <?php else : ?>
                            <span class="product-detail__stock--out">Rupture de stock</span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn--primary" id="product-add-to-cart" <?= $selectedStock <= 0 ? 'disabled' : '' ?>>Ajouter au panier</button>
                </form>
            </div>
        </div>
    </section>

    <?php if (!empty($relatedProducts)) : ?>
        <section class="page-section page-section--alt">
            <div class="container">
                <h2 class="section-title">Produits similaires</h2>
                <div class="product-grid">
                    <?php foreach ($relatedProducts as $relatedProduct) : ?>
                        <?php
                        $product = $relatedProduct;
                        $productCardPromo = false;
                        $variantId = (int) ($product['id_variant'] ?? 0);
                        $cardImages = $relatedImages[$variantId] ?? [];
                        include __DIR__ . '/includes/product-card.php';
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<script type="application/json" id="product-variant-data"><?= json_encode(
    [
        'product_name' => (string) ($product['product_name'] ?? ''),
        'currency' => '€',
        'variants' => $variantClientData,
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
) ?></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
