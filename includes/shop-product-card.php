<?php

declare(strict_types=1);

if (empty($product)) {
    return;
}

$prices = getProductDisplayPrice($product);
$productImage = imagePath('products', $product['product_image'] ?? '');
$discountLabel = getProductDiscountLabel($product);
$inStock = isProductInStock($product);
$stockLabel = getProductStockStatus($product);
$productLink = productUrl((int) $product['id_product']);
?>

<article class="product-card product-card--shop">
    <div class="product-card__media">
        <a href="<?= $productLink ?>" class="product-card__image-link" aria-label="<?= e($product['product_name']) ?>">
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
        </a>

        <?php if ($discountLabel !== '') : ?>
            <span class="product-card__badge"><?= e($discountLabel) ?></span>
        <?php endif; ?>

        <span class="product-card__stock product-card__stock--<?= $inStock ? 'in' : 'out' ?>">
            <?= e($stockLabel) ?>
        </span>
    </div>

    <div class="product-card__body">
        <h3 class="product-card__name">
            <a href="<?= $productLink ?>"><?= e($product['product_name']) ?></a>
        </h3>

        <div class="product-card__price">
            <span class="product-card__price-current<?= $prices['on_sale'] ? ' product-card__price-current--sale' : '' ?>">
                <?= e(formatCurrency($prices['current'])) ?>
            </span>
            <?php if ($prices['on_sale'] && $prices['original'] !== null) : ?>
                <span class="product-card__price-original">
                    <?= e(formatCurrency($prices['original'])) ?>
                </span>
            <?php endif; ?>
        </div>

        <a href="<?= $productLink ?>" class="btn btn--outline product-card__btn">
            <?= t('product_view') ?>
        </a>
    </div>
</article>
