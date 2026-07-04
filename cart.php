<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['cart_action'] ?? '');
    $key = (string) ($_POST['cart_key'] ?? '');

    if ($action === 'update' && $key !== '') {
        $quantity = max(0, (int) ($_POST['quantity'] ?? 0));
        if ($quantity <= 0) {
            removeFromCart($key);
        } elseif (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity'] = $quantity;
        }
    }

    if ($action === 'remove' && $key !== '') {
        removeFromCart($key);
    }

    if ($action === 'clear') {
        clearCart();
    }

    redirect(pageUrl('cart.php'));
}

$cartItems = getCartItems();
$subtotal = getCartTotal();
$deliveryPrice = (float) ($settings['delivery_price'] ?? 0);
$freeDeliveryThreshold = (float) ($settings['free_delivery'] ?? 0);
$shipping = $freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold ? 0.0 : $deliveryPrice;
$total = $subtotal + $shipping;

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
                    <p><?= t('cart_empty') ?></p>
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
                                    <h2 class="cart-item__name"><a href="<?= productUrl((int) ($item['id_product'] ?? 0)) ?>"><?= e($item['name'] ?? '') ?></a></h2>
                                    <?php if (!empty($item['color_name'])) : ?><p class="cart-item__meta"><?= t('cart_color') ?> : <?= e($item['color_name']) ?></p><?php endif; ?>
                                    <?php if (!empty($item['size'])) : ?><p class="cart-item__meta"><?= t('cart_size') ?> : <?= e($item['size']) ?></p><?php endif; ?>
                                    <p class="cart-item__meta"><?= t('cart_quantity') ?> : <?= (int) ($item['quantity'] ?? 1) ?></p>
                                    <p class="cart-item__price"><?= e(formatCurrency((float) ($item['price'] ?? 0))) ?></p>
                                </div>
                                <form method="post" action="<?= pageUrl('cart.php') ?>" class="cart-item__actions">
                                    <input type="hidden" name="cart_action" value="update">
                                    <input type="hidden" name="cart_key" value="<?= e($key) ?>">
                                    <label class="visually-hidden" for="quantity-<?= e($key) ?>"><?= t('cart_qty_label') ?></label>
                                    <input type="number" id="quantity-<?= e($key) ?>" name="quantity" class="input" min="1" max="10" value="<?= (int) ($item['quantity'] ?? 1) ?>">
                                    <button type="submit" class="btn btn--outline"><?= t('cart_update') ?></button>
                                </form>
                                <form method="post" action="<?= pageUrl('cart.php') ?>" class="cart-item__remove">
                                    <input type="hidden" name="cart_action" value="remove">
                                    <input type="hidden" name="cart_key" value="<?= e($key) ?>">
                                    <button type="submit" class="cart-item__remove-btn" aria-label="<?= t('cart_remove') ?>"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <aside class="cart-summary">
                        <h2 class="cart-summary__title"><?= t('cart_summary_title') ?></h2>
                        <p class="cart-summary__row"><span><?= t('cart_subtotal') ?></span><strong><?= e(formatCurrency($subtotal)) ?></strong></p>
                        <p class="cart-summary__row"><span><?= t('cart_delivery') ?></span><strong><?= e(formatCurrency($shipping)) ?></strong></p>
                        <p class="cart-summary__row cart-summary__row--total"><span><?= t('cart_total') ?></span><strong><?= e(formatCurrency($total)) ?></strong></p>
                        <a href="<?= pageUrl('checkout.php') ?>" class="btn btn--primary cart-summary__checkout"><?= t('cart_checkout') ?></a>
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