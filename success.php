<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$orderId = filter_input(INPUT_GET, 'order', FILTER_VALIDATE_INT);
$orderId = $orderId !== false && $orderId > 0 ? $orderId : (int) ($_SESSION['order_id'] ?? 0);

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Confirmation';
$metaDescription = 'Commande confirmée';

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title">Commande confirmée</h1>
            <p class="page-header__subtitle">Merci pour votre commande</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container form-shell">
            <div class="checkout-form">
                <p>Votre commande a bien été enregistrée.</p>
                <?php if ($orderId > 0) : ?>
                    <p>Numéro de commande : #<?= (int) $orderId ?></p>
                <?php endif; ?>
                <a href="<?= pageUrl('shop.php') ?>" class="btn btn--primary">Continuer mes achats</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>