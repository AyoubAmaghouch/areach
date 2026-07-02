<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$products = getPromotionProducts($pdo, $langCode, 100);

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Promotions';
$metaDescription = 'Profitez de nos offres et promotions chez ' . ($settings['store_name'] ?: 'AREACH');

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title">Promotions</h1>
            <p class="page-header__subtitle">Profitez de nos offres exclusives</p>
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
                        include __DIR__ . '/includes/product-card.php';
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
