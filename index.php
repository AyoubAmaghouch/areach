<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['newsletter_action'])) {
    $subscribed = handleNewsletterSubscription($pdo, (string) ($_POST['email'] ?? ''));

    if ($subscribed) {
        $_SESSION['newsletter_flash'] = 'success';
    }

    redirect(pageUrl('index.php') . '#footer');
}

$banners = getActiveBanners($pdo);
$categories = getHomeCategories($pdo, $langCode);
$newArrivals = getNewArrivalProducts($pdo, $langCode, 5);
$newArrivalVariantIds = array_column($newArrivals, 'id_variant');
$newArrivalImages = getProductImagesForVariants($pdo, $newArrivalVariantIds);
$promotionBanner = getPromotionBanner($pdo);
$promotionProducts = getPromotionProducts($pdo, $langCode);
$promoVariantIds = array_column($promotionProducts, 'id_variant');
$promoImages = getProductImagesForVariants($pdo, $promoVariantIds);

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Accueil';
$metaDescription = $settings['store_name']
    ? 'Découvrez la boutique en ligne ' . $settings['store_name']
    : 'Découvrez la boutique en ligne AREACH';

$bodyClass = 'page-home';

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
include 'includes/home/hero-slider.php';
?>

<main id="main-content" class="main-content">
    <?php
    include __DIR__ . '/includes/home/about-areach.php';
    include __DIR__ . '/includes/home/categories.php';

    $products = $newArrivals;
    $productSectionPromo = false;
    include __DIR__ . '/includes/home/product-grid.php';

    include __DIR__ . '/includes/home/promotion-banner.php';

    $products = $promotionProducts;
    $productSectionPromo = true;
    include __DIR__ . '/includes/home/product-grid.php';

    ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
