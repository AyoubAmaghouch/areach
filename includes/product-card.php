<?php

declare(strict_types=1);

if (empty($product)) {
    return;
}

$prices = getProductDisplayPrice($product);
$productImage = imagePath('products', $product['product_image'] ?? '');
$discountLabel = getProductDiscountLabel($product);
$showSalePrice = ($productCardPromo ?? false) || $prices['on_sale'];
$productLink = productUrl((int) $product['id_product']);
$cartActionUrl = pageUrl('product.php?id=' . (int) $product['id_product']);
?>

<article class="product-card<?= ($productCardPromo ?? false) ? ' product-card--promo' : '' ?>">
    <div class="product-card__media">
        <a href="<?= $productLink ?>" class="product-card__link" aria-label="<?= e($product['product_name']) ?>">
            <?php if ($productImage !== '') : ?>
                <img
                    src="<?= $productImage ?>"
                    alt="<?= e($product['product_name']) ?>"
                    class="product-card__image"
                    width="300"
                    height="400"
                    loading="lazy"
                >
            <?php else : ?>
                <span class="product-card__placeholder" aria-hidden="true">
                    <i class="fa-solid fa-shirt"></i>
                </span>
            <?php endif; ?>

            <?php if ($discountLabel !== '') : ?>
                <span class="product-card__badge"><?= e($discountLabel) ?></span>
            <?php endif; ?>

            <span class="product-card__overlay">
                <span class="product-card__overlay-text"><?= t('product_view') ?></span>
            </span>
        </a>

        <button type="button" class="product-card__wishlist" aria-label="<?= t('product_wishlist') ?>">
            <i class="fa-regular fa-heart" aria-hidden="true"></i>
        </button>
    </div>

    <div class="product-card__body">
        <a href="<?= $productLink ?>" class="product-card__body-link">
            <h3 class="product-card__name"><?= e($product['product_name']) ?></h3>
            <div class="product-card__price">
                <span class="product-card__price-current<?= $showSalePrice && $prices['original'] !== null ? ' product-card__price-current--sale' : '' ?>">
                    <?= e(formatCurrency($prices['current'])) ?>
                </span>
                <?php if ($showSalePrice && $prices['original'] !== null) : ?>
                    <span class="product-card__price-original">
                        <?= e(formatCurrency($prices['original'])) ?>
                    </span>
                <?php endif; ?>
            </div>
        </a>

        <a href="<?= e($cartActionUrl) ?>" class="btn btn--outline product-card__btn">
            <?= t('product_add_cart') ?>
        </a>
    </div>
</article>
