<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['cart_action'] ?? '';
    $key = (string) ($_POST['cart_key'] ?? '');

    if ($action === 'remove' && $key !== '') {
        removeFromCart($key);
    }

    if ($action === 'clear') {
        clearCart();
    }

    redirect(pageUrl('cart.php'));
}

$cartItems = getCartItems();
$cartTotal = getCartTotal();

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Panier';
$metaDescription = 'Votre panier chez ' . ($settings['store_name'] ?: 'AREACH');

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title">Panier</h1>
            <p class="page-header__subtitle">Vos articles sélectionnés</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <?php if (empty($cartItems)) : ?>
                <div class="page-empty">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                    <p>Votre panier est vide.</p>
                    <a href="<?= pageUrl('shop.php') ?>" class="btn btn--accent">Continuer vos achats</a>
                </div>
            <?php else : ?>
                <div class="cart-layout">
                    <div class="cart-items">
                        <?php foreach ($cartItems as $key => $item) : ?>
                            <?php $itemImage = imagePath('products', $item['image'] ?? ''); ?>
                            <article class="cart-item">
                                <a href="<?= productUrl((int) ($item['id_product'] ?? 0)) ?>" class="cart-item__media">
                                    <?php if ($itemImage !== '') : ?>
                                        <img src="<?= $itemImage ?>" alt="" width="100" height="130" loading="lazy">
                                    <?php else : ?>
                                        <span class="cart-item__placeholder"><i class="fa-solid fa-shirt"></i></span>
                                    <?php endif; ?>
                                </a>

                                <div class="cart-item__body">
                                    <h2 class="cart-item__name">
                                        <a href="<?= productUrl((int) ($item['id_product'] ?? 0)) ?>">
                                            <?= e($item['name'] ?? '') ?>
                                        </a>
                                    </h2>
                                    <?php if (!empty($item['color_name'])) : ?>
                                        <p class="cart-item__meta">Couleur : <?= e($item['color_name']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($item['size'])) : ?>
                                        <p class="cart-item__meta">Taille : <?= e($item['size']) ?></p>
                                    <?php endif; ?>
                                    <p class="cart-item__meta">Quantité : <?= (int) ($item['quantity'] ?? 1) ?></p>
                                    <p class="cart-item__price"><?= e(formatCurrency((float) ($item['price'] ?? 0))) ?></p>
                                </div>

                                <form method="post" action="<?= pageUrl('cart.php') ?>" class="cart-item__remove">
                                    <input type="hidden" name="cart_action" value="remove">
                                    <input type="hidden" name="cart_key" value="<?= e($key) ?>">
                                    <button type="submit" class="cart-item__remove-btn" aria-label="Supprimer">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <aside class="cart-summary">
                        <h2 class="cart-summary__title">Récapitulatif</h2>
                        <p class="cart-summary__row">
                            <span>Sous-total</span>
                            <strong><?= e(formatCurrency($cartTotal)) ?></strong>
                        </p>
                        <p class="cart-summary__note">Les frais de livraison seront calculés à l'étape suivante.</p>
                        <a href="<?= pageUrl('shop.php') ?>" class="btn btn--outline cart-summary__continue">
                            Continuer vos achats
                        </a>
                        <form method="post" action="<?= pageUrl('cart.php') ?>" class="cart-summary__clear">
                            <input type="hidden" name="cart_action" value="clear">
                            <button type="submit" class="btn btn--outline">Vider le panier</button>
                        </form>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
