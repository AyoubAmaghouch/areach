<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' - ' . t('footer_infos_title');
$metaDescription = t('footer_brand_desc');

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="home-section">
        <div class="container">
            <div class="section-heading">
                <span class="section-heading__eyebrow">AREACH</span>
                <h1><?= t('footer_infos_title') ?></h1>
                <p><?= t('footer_brand_desc') ?></p>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
