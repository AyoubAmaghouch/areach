<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' - ' . t('home_about_title');
$metaDescription = t('home_about_p1');

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <?php include __DIR__ . '/includes/home/about-areach.php'; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
