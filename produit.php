<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    redirect(pageUrl('shop.php'));
}

$product = getProductById($pdo, $productId, $langCode);

if (!$product) {
    redirect(pageUrl('shop.php'));
}

$variants = getProductVariants($pdo, $productId);

if (empty($variants)) {
    redirect(pageUrl('shop.php'));
}

$defaultVariant = $variants[0];
$defaultImage = imagePath(
    'products',
    (string) ($product['product_image'] ?? $defaultVariant['images'][0]['image'] ?? '')
);

$prices = getProductDisplayPrice($defaultVariant);
$discountLabel = getProductDiscountLabel($defaultVariant);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['add_to_cart'])) {
    $variantId = filter_input(INPUT_POST, 'id_variant', FILTER_VALIDATE_INT);
    $size = trim((string) ($_POST['size'] ?? ''));
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;

    if ($quantity < 1) {
        $quantity = 1;
    }

    $selectedVariant = null;

    foreach ($variants as $variant) {
        if ((int) $variant['id_variant'] === $variantId) {
            $selectedVariant = $variant;
            break;
        }
    }

    if ($selectedVariant) {
        $variantPrices = getProductDisplayPrice($selectedVariant);
        $imageFile = $selectedVariant['images'][0]['image'] ?? $product['product_image'] ?? '';

        addToCart([
            'id_product' => $productId,
            'id_variant' => (int) $selectedVariant['id_variant'],
            'name' => $product['product_name'],
            'size' => $size,
            'quantity' => $quantity,
            'price' => $variantPrices['current'],
            'image' => $imageFile,
            'color_name' => $selectedVariant['color_name'],
        ]);

        redirect(pageUrl('panier.php'));
    }
}

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — ' . $product['product_name'];
$descriptionText = strip_tags((string) $product['description']);
$metaDescription = function_exists('mb_substr')
    ? mb_substr($descriptionText, 0, 160)
    : substr($descriptionText, 0, 160);

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-section product-detail">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <a href="<?= pageUrl('index.php') ?>">Accueil</a>
                <span aria-hidden="true">/</span>
                <a href="<?= pageUrl('shop.php') ?>">Boutique</a>
                <?php if (!empty($product['category_name'])) : ?>
                    <span aria-hidden="true">/</span>
                    <a href="<?= pageUrl('shop.php?category=' . (int) $product['id_category']) ?>">
                        <?= e($product['category_name']) ?>
                    </a>
                <?php endif; ?>
                <span aria-hidden="true">/</span>
                <span aria-current="page"><?= e($product['product_name']) ?></span>
            </nav>

            <div class="product-detail__grid">
                <div class="product-detail__gallery">
                    <?php if ($defaultImage !== '') : ?>
                        <img
                            src="<?= $defaultImage ?>"
                            alt="<?= e($product['product_name']) ?>"
                            class="product-detail__image"
                            id="product-main-image"
                            width="600"
                            height="800"
                        >
                    <?php else : ?>
                        <div class="product-detail__placeholder">
                            <i class="fa-solid fa-shirt" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="product-detail__info">
                    <?php if (!empty($product['category_name'])) : ?>
                        <p class="product-detail__category"><?= e($product['category_name']) ?></p>
                    <?php endif; ?>

                    <h1 class="product-detail__title"><?= e($product['product_name']) ?></h1>

                    <?php if (!empty($product['reference'])) : ?>
                        <p class="product-detail__reference">Réf. <?= e($product['reference']) ?></p>
                    <?php endif; ?>

                    <div class="product-detail__price">
                        <?php if ($discountLabel !== '') : ?>
                            <span class="product-card__badge"><?= e($discountLabel) ?></span>
                        <?php endif; ?>
                        <span class="product-detail__price-current<?= $prices['on_sale'] ? ' product-card__price-current--sale' : '' ?>">
                            <?= e(formatCurrency($prices['current'])) ?>
                        </span>
                        <?php if ($prices['on_sale'] && $prices['original'] !== null) : ?>
                            <span class="product-detail__price-original">
                                <?= e(formatCurrency($prices['original'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($product['description'])) : ?>
                        <div class="product-detail__description">
                            <?= nl2br(e($product['description'])) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= pageUrl('produit.php?id=' . $productId) ?>" class="product-detail__form">
                        <input type="hidden" name="add_to_cart" value="1">

                        <?php if (count($variants) > 1) : ?>
                            <div class="form-group">
                                <label for="id_variant">Couleur</label>
                                <select name="id_variant" id="id_variant" class="input" required>
                                    <?php foreach ($variants as $variant) : ?>
                                        <option value="<?= (int) $variant['id_variant'] ?>">
                                            <?= e($variant['color_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else : ?>
                            <input type="hidden" name="id_variant" value="<?= (int) $defaultVariant['id_variant'] ?>">
                        <?php endif; ?>

                        <?php if (!empty($defaultVariant['sizes'])) : ?>
                            <div class="form-group">
                                <label for="size">Taille</label>
                                <select name="size" id="size" class="input" required>
                                    <?php foreach ($defaultVariant['sizes'] as $size) : ?>
                                        <option value="<?= e($size) ?>"><?= e($size) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else : ?>
                            <input type="hidden" name="size" value="">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="quantity">Quantité</label>
                            <input type="number" name="quantity" id="quantity" class="input" value="1" min="1" max="99" required>
                        </div>

                        <button type="submit" class="btn btn--accent product-detail__submit">
                            <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                            Ajouter au panier
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
