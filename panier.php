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

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — ' . t('cart_page_title');
$metaDescription = t('cart_meta', $settings['store_name'] ?: 'AREACH');

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title"><?= t('cart_title') ?></h1>
            <p class="page-header__subtitle"><?= t('cart_subtitle') ?></p>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <?php if (empty($cartItems)) : ?>
                <div class="page-empty">
                    <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                    <p>><?= t('cart_empty') ?></p>
                    <a href="<?= pageUrl('shop.php') ?>" class="btn btn--accent"><?= t('cart_continue') ?></a>
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
                                        <p class="cart-item__meta"><?= t('cart_color') ?> : <?= e($item['color_name']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($item['size'])) : ?>
                                        <p class="cart-item__meta"><?= t('cart_size') ?> : <?= e($item['size']) ?></p>
                                    <?php endif; ?>
                                    <p class="cart-item__meta"><?= t('cart_quantity') ?> : <?= (int) ($item['quantity'] ?? 1) ?></p>
                                    <p class="cart-item__price"><?= e(formatCurrency((float) ($item['price'] ?? 0))) ?></p>
                                </div>

                                <form method="post" action="<?= pageUrl('cart.php') ?>" class="cart-item__remove">
                                    <input type="hidden" name="cart_action" value="remove">
                                    <input type="hidden" name="cart_key" value="<?= e($key) ?>">
                                    <button type="submit" class="cart-item__remove-btn" aria-label="<?= t('cart_remove') ?>">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <aside class="cart-summary">
                        <h2 class="cart-summary__title"><?= t('cart_summary_title') ?></h2>
                        <p class="cart-summary__row">
                            <span><?= t('cart_subtotal') ?></span>
                            <strong><?= e(formatCurrency($cartTotal)) ?></strong>
                        </p>
                        <p class="cart-summary__note"><?= t('cart_shipping_note') ?></p>
                        <a href="<?= pageUrl('shop.php') ?>" class="btn btn--outline cart-summary__continue">
                            <?= t('cart_continue') ?>
                        </a>
                        <form method="post" action="<?= pageUrl('cart.php') ?>" class="cart-summary__clear">
                            <input type="hidden" name="cart_action" value="clear">
                            <button type="submit" class="btn btn--outline"><?= t('cart_clear') ?></button>
                        </form>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
