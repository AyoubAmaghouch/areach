<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['newsletter_action'])) {
    $subscribed = handleNewsletterSubscription($pdo, (string) ($_POST['email'] ?? ''));

    if ($subscribed) {
        $_SESSION['newsletter_flash'] = 'success';
    }

    redirect(pageUrl('index.php') . '#newsletter');
}

$banners = getActiveBanners($pdo);
$categories = getHomeCategories($pdo, $langCode);
$newArrivals = getNewArrivalProducts($pdo, $langCode, 5);
$promotionBanner = getPromotionBanner($pdo);
$promotionProducts = getPromotionProducts($pdo, $langCode);

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Accueil';
$metaDescription = $settings['store_name']
    ? 'Découvrez la boutique en ligne ' . $settings['store_name']
    : 'Découvrez la boutique en ligne AREACH';

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
include 'includes/home/hero-slider.php';
?>

<main id="main-content" class="main-content">
    <?php
    include __DIR__ . '/includes/home/categories.php';

    $products = $newArrivals;
    $productSectionPromo = false;
    include __DIR__ . '/includes/home/product-grid.php';

    include __DIR__ . '/includes/home/promotion-banner.php';

    $products = $promotionProducts;
    $productSectionPromo = true;
    include __DIR__ . '/includes/home/product-grid.php';

    include __DIR__ . '/includes/home/newsletter.php';
    ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
