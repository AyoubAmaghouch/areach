<?php

declare(strict_types=1);

if (empty($products)) {
    return;
}

$isPromoSection = $productSectionPromo ?? false;
$sectionId = $isPromoSection ? 'promotion-products-title' : 'new-arrivals-title';
$sectionTitle = $isPromoSection ? t('home_promotions') : t('home_new_arrivals');
$sectionSubtitle = $isPromoSection
    ? t('home_promotions_subtitle')
    : t('home_new_arrivals_subtitle');
$sectionClass = $isPromoSection ? 'home-products--promo' : 'home-products--new';
$viewAllUrl = $isPromoSection ? pageUrl('promotions.php') : pageUrl('shop.php');
?>

<section class="home-section home-products <?= e($sectionClass) ?>"<?= !$isPromoSection ? ' id="nouveautes"' : '' ?> aria-labelledby="<?= e($sectionId) ?>">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" id="<?= e($sectionId) ?>"><?= e($sectionTitle) ?></h2>
            <p class="section-subtitle"><?= e($sectionSubtitle) ?></p>
            <a href="<?= $viewAllUrl ?>" class="section-link"><?= t('home_view_all') ?></a>
        </div>

        <div class="product-grid">
            <?php foreach ($products as $product) : ?>
                <?php
                $productCardPromo = $isPromoSection;
                $variantId = (int) ($product['id_variant'] ?? 0);
                $cardImages = [];
                if ($isPromoSection && isset($promoImages)) {
                    $cardImages = $promoImages[$variantId] ?? [];
                } elseif (!$isPromoSection && isset($newArrivalImages)) {
                    $cardImages = $newArrivalImages[$variantId] ?? [];
                }
                include __DIR__ . '/../product-card.php';
                ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
