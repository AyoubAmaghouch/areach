<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = getPromotionProducts($pdo, $langCode, 100);
$variantIds = array_column($products, 'id_variant');
$allImages = getProductImagesForVariants($pdo, $variantIds);

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Promotions';
$metaDescription = 'Profitez de nos offres et promotions chez ' . ($settings['store_name'] ?: 'AREACH');

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header page-header--hero page-header--promotions">
        <div class="container">
            <h1 class="page-header__title">
                <span class="page-header__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#C89B52" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                        <line x1="7" y1="7" x2="7.01" y2="7"/>
                    </svg>
                </span>
                <?= t('promotions_title') ?>
            </h1>
            <p class="page-header__subtitle"><?= t('promotions_subtitle') ?></p>
        </div>
    </section>

    <section class="page-section page-section--alt">
        <div class="container">
            <?php if (empty($products)) : ?>
                <div class="page-empty">
                    <i class="fa-solid fa-tags" aria-hidden="true"></i>
                    <p>Aucune promotion active pour le moment.</p>
                    <a href="<?= pageUrl('shop.php') ?>" class="btn btn--accent">Voir la boutique</a>
                </div>
            <?php else : ?>
                <div class="product-grid">
                    <?php foreach ($products as $product) : ?>
                        <?php
                        $productCardPromo = true;
                        $variantId = (int) ($product['id_variant'] ?? 0);
                        $cardImages = $allImages[$variantId] ?? [];
                        include __DIR__ . '/includes/product-card.php';
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
