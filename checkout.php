<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// If the cart is not empty, clear any completed checkout session state to start fresh
$cartItems = getCartItems();
if (!empty($cartItems)) {
    $sessionOrderIdForCheck = (int) ($_SESSION['checkout_order_id'] ?? 0);
    if ($sessionOrderIdForCheck > 0) {
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE id_order = ? LIMIT 1');
        $stmt->execute([$sessionOrderIdForCheck]);
        $orderCheck = $stmt->fetch();
        $orderCheckStatus = (string) ($orderCheck['status'] ?? '');
        if ($orderCheckStatus !== 'En attente' && $orderCheckStatus !== '') {
            unset($_SESSION['checkout_order_id']);
            unset($_SESSION['order_id']);
            unset($_SESSION['checkout_confirm_token']);
        }
    }
}

function checkoutAvailableColumns(PDO $pdo, string $table): array
{
    return array_keys(checkoutColumnMeta($pdo, $table));
}

/**
 * Cached SHOW COLUMNS FROM `<table>` metadata.
 * Returns an associative array keyed by column name; each entry holds:
 *   - 'key'   : the value of the "Key" column ('PRI' for primary keys)
 *   - 'extra' : lower-cased value of the "Extra" column (may contain 'auto_increment')
 *
 * Used by checkoutInsertAllowed() to detect the primary-key column so it can
 * be excluded from INSERTs even when AUTO_INCREMENT is missing on the table
 * (a known issue when a MySQL dump is imported with NO_AUTO_VALUE_ON_ZERO).
 */
function checkoutColumnMeta(PDO $pdo, string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $meta = [];
    foreach ($rows as $row) {
        $meta[(string) $row['Field']] = [
            'key'   => (string) ($row['Key'] ?? ''),
            'extra' => strtolower((string) ($row['Extra'] ?? '')),
        ];
    }

    $cache[$table] = $meta;

    return $meta;
}

/**
 * Returns the single-column primary key of a table, or null when the table
 * has a composite PK or no PK at all.
 */
function checkoutPrimaryKeyColumn(PDO $pdo, string $table): ?string
{
    $meta = checkoutColumnMeta($pdo, $table);

    $priColumns = array_keys(array_filter(
        $meta,
        static fn (array $info): bool => ($info['key'] ?? '') === 'PRI'
    ));

    return count($priColumns) === 1 ? (string) $priColumns[0] : null;
}

function checkoutInsertAllowed(PDO $pdo, string $table, array $data): int
{
    $columns = checkoutAvailableColumns($pdo, $table);
    $primaryKey = checkoutPrimaryKeyColumn($pdo, $table);

    $filtered = [];
    foreach ($data as $key => $value) {
        if (!in_array($key, $columns, true)) {
            // Column doesn't exist on this table — skip it (existing behaviour).
            continue;
        }

        // Defensive: never insert an explicit value into the auto-increment
        // primary-key column, even if a caller accidentally passes one. This
        // prevents "Duplicate entry '0' for key '<table>.PRIMARY'" when the
        // PK column was imported without AUTO_INCREMENT after a schema
        // dump/restore (the AwardSpace MySQL 8 import issue).
        if ($key === $primaryKey) {
            continue;
        }

        $filtered[$key] = $value;
    }

    if ($filtered === []) {
        return 0;
    }

    $columnList = implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', array_keys($filtered)));
    $placeholders = implode(', ', array_fill(0, count($filtered), '?'));
    $stmt = $pdo->prepare('INSERT INTO `' . $table . '` (' . $columnList . ') VALUES (' . $placeholders . ')');
    $stmt->execute(array_values($filtered));

    return (int) $pdo->lastInsertId();
}

function checkoutNormalizeWhatsappPhone(?string $phone): string
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return '';
    }

    $phone = preg_replace('/[^0-9+]+/', '', $phone) ?? '';
    if (str_starts_with($phone, '+')) {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    if (str_starts_with($phone, '00')) {
        return substr(preg_replace('/\D+/', '', $phone) ?? '', 2);
    }

    return preg_replace('/\D+/', '', $phone) ?? '';
}

function checkoutGetOrder(PDO $pdo, int $orderId, string $langCode): ?array
{
    if ($orderId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT *, COALESCE(NULLIF(status, ""), "En attente") AS status FROM orders WHERE id_order = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT
            oi.*,
            pv.price AS regular_price,
            pv.promotion_price,
            pv.promotion_start,
            pv.promotion_end,
            pv.color_name,
            p.id_product,
            COALESCE(pt.name, pt_fr.name) AS product_name,
            pi.image AS product_image
         FROM order_items oi
         INNER JOIN product_variants pv ON oi.id_variant = pv.id_variant
         INNER JOIN products p ON pv.id_product = p.id_product
         LEFT JOIN languages l ON l.code = ?
         LEFT JOIN product_translations pt ON pt.id_product = p.id_product AND pt.id_language = l.id_language
         LEFT JOIN languages l_fr ON l_fr.code = "fr"
         LEFT JOIN product_translations pt_fr ON pt_fr.id_product = p.id_product AND pt_fr.id_language = l_fr.id_language
         LEFT JOIN product_images pi ON pi.id_image = (
             SELECT pi2.id_image FROM product_images pi2
             WHERE pi2.id_variant = oi.id_variant
             ORDER BY CASE WHEN pi2.is_primary = 1 THEN 0 ELSE 1 END, pi2.id_image ASC
             LIMIT 1
         )
         WHERE oi.id_order = ?
         ORDER BY oi.id_item ASC'
    );
    $stmt->execute([$langCode, $orderId]);
    $items = $stmt->fetchAll();

    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1);
    }

    $total = (float) ($order['total'] ?? $subtotal);

    return [
        'order' => $order,
        'items' => $items,
        'subtotal' => $subtotal,
        'shipping' => max(0.0, $total - $subtotal),
        'total' => $total,
    ];
}

function checkoutBuildCartSummaryItems(PDO $pdo, array $cartItems): array
{
    $variantIds = [];
    foreach ($cartItems as $item) {
        $variantId = (int) ($item['id_variant'] ?? 0);
        if ($variantId > 0) {
            $variantIds[] = $variantId;
        }
    }

    $variants = [];
    if ($variantIds !== []) {
        $variantIds = array_values(array_unique($variantIds));
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $stmt = $pdo->prepare(
            'SELECT id_variant, price AS regular_price, promotion_price, promotion_start, promotion_end, color_name
             FROM product_variants
             WHERE id_variant IN (' . $placeholders . ')'
        );
        $stmt->execute($variantIds);
        foreach ($stmt->fetchAll() as $variant) {
            $variants[(int) $variant['id_variant']] = $variant;
        }
    }

    $summaryItems = [];
    foreach ($cartItems as $item) {
        $variant = $variants[(int) ($item['id_variant'] ?? 0)] ?? [];
        $price = (float) ($item['price'] ?? 0);
        $regularPrice = (float) ($variant['regular_price'] ?? $price);
        $showRegularPrice = $regularPrice > $price && isPromotionActive($variant);

        $summaryItems[] = [
            'name' => (string) ($item['name'] ?? ''),
            'color_name' => (string) ($item['color_name'] ?? ($variant['color_name'] ?? '')),
            'size' => (string) ($item['size'] ?? ''),
            'quantity' => (int) ($item['quantity'] ?? 1),
            'price' => $price,
            'regular_price' => $showRegularPrice ? $regularPrice : null,
            'image' => (string) ($item['image'] ?? ''),
            'id_product' => (int) ($item['id_product'] ?? 0),
        ];
    }

    return $summaryItems;
}

function checkoutBuildOrderSummaryItems(array $orderItems): array
{
    $summaryItems = [];

    foreach ($orderItems as $item) {
        $price = (float) ($item['price'] ?? 0);
        $regularPrice = (float) ($item['regular_price'] ?? $price);
        $showRegularPrice = $regularPrice > $price && isPromotionActive($item);

        $summaryItems[] = [
            'name' => (string) ($item['product_name'] ?? ''),
            'color_name' => (string) ($item['color_name'] ?? ''),
            'size' => (string) ($item['size'] ?? ''),
            'quantity' => (int) ($item['quantity'] ?? 1),
            'price' => $price,
            'regular_price' => $showRegularPrice ? $regularPrice : null,
            'image' => (string) ($item['product_image'] ?? ''),
            'id_product' => (int) ($item['id_product'] ?? 0),
        ];
    }

    return $summaryItems;
}

function checkoutBuildWhatsappMessage(array $order, array $items): string
{
    $lines = [];
    $lines[] = 'Bonjour AREACH 👋';
    $lines[] = '';
    $lines[] = 'Je souhaite confirmer ma commande.';
    $lines[] = '';

    $orderNumber = html_entity_decode((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8');
    $customerName = trim(html_entity_decode((string) ($order['customer_name'] ?? '') . ' ' . (string) ($order['customer_lastname'] ?? ''), ENT_QUOTES, 'UTF-8'));
    $phone = html_entity_decode((string) ($order['telephone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $total = (float) ($order['total'] ?? 0);

    if ($orderNumber !== '') {
        $lines[] = "🧾 Commande : #$orderNumber";
    }
    if ($customerName !== '') {
        $lines[] = "👤 Client : $customerName";
    }
    if ($phone !== '') {
        $lines[] = "📞 Téléphone : $phone";
    }

    if ($items !== []) {
        $lines[] = '';
        $lines[] = 'Produits :';
        foreach ($items as $item) {
            $parts = [];
            $productName = html_entity_decode((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $parts[] = $productName !== '' ? $productName : 'Produit #' . (int) ($item['id_variant'] ?? 0);
            $parts[] = 'Qté : ' . (int) ($item['quantity'] ?? 1);
            if (!empty($item['size'])) {
                $parts[] = 'Taille : ' . $item['size'];
            }
            if (!empty($item['color_name'])) {
                $parts[] = 'Couleur : ' . $item['color_name'];
            }
            $lines[] = '- ' . implode(' — ', $parts);
        }
    }

    $lines[] = '';
    $lines[] = '💰 Total : ' . number_format($total, 2, ',', ' ') . ' €';
    $lines[] = '';
    $lines[] = 'Merci de confirmer ma commande.';

    return implode("\n", $lines);
}

if (empty($_SESSION['checkout_confirm_token'])) {
    $_SESSION['checkout_confirm_token'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $cartItemsForOrder = getCartItems();
        if (empty($cartItemsForOrder)) {
            redirect(pageUrl('cart.php'));
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $customerEmail = trim((string) ($_POST['email'] ?? ''));
        $customerPhone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));

        if ($firstName === '' || $lastName === '' || $customerEmail === '' || $customerPhone === '' || $address === '' || $city === '') {
            $_SESSION['checkout_error'] = t('checkout_required_error');
            redirect(pageUrl('checkout.php'));
        }

        $customerId = 0;
        if (!empty($_SESSION['customer']['id_customer'])) {
            $customerId = (int) $_SESSION['customer']['id_customer'];
        } else {
            $stmt = $pdo->prepare('SELECT id_customer FROM customers WHERE email = ? LIMIT 1');
            $stmt->execute([$customerEmail]);
            $existing = $stmt->fetch();
            if ($existing) {
                $customerId = (int) $existing['id_customer'];
            } else {
                $customerId = checkoutInsertAllowed($pdo, 'customers', [
                    'nom' => $lastName,
                    'prenom' => $firstName,
                    'email' => $customerEmail,
                    'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
                    'newsletter' => 0,
                    'status' => 1,
                    'telephone' => $customerPhone,
                    'address' => $address,
                    'city' => $city,
                ]);
            }
        }

    $subtotal = getCartTotal();
    $deliveryPrice = (float) ($settings['delivery_price'] ?? 0);
    $freeDeliveryThreshold = (float) ($settings['free_delivery'] ?? 0);
    $shipping = $freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold ? 0.0 : $deliveryPrice;
    $total = $subtotal + $shipping;

    // Order number must be generated BEFORE the INSERT so that, if PDO's
    // lastInsertId() returns 0 (because the `orders` table is missing the
    // AUTO_INCREMENT attribute after the dump/restore), we can still recover
    // the real id of the just-inserted order by re-reading it via its
    // unique order_number. This keeps checkout working even on a partially
    // imported schema, while the SQL migration restores AUTO_INCREMENT.
    $orderNumber = 'CMD-' . date('Ymd-His') . '-' . random_int(100, 999);

    $pdo->beginTransaction();
    try {
        // Insert into `orders`. checkoutInsertAllowed() defensively omits
        // the PK column from the column list, so MySQL uses its default.
        $orderId = checkoutInsertAllowed($pdo, 'orders', [
            'order_number' => $orderNumber,
            'id_customer' => $customerId,
            'customer_name' => $firstName,
            'customer_lastname' => $lastName,
            'email' => $customerEmail,
            'telephone' => $customerPhone,
            'address' => $address,
            'city' => $city,
            'notes' => '',
            'total' => $total,
            'status' => 'En attente',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // PDO::lastInsertId() returns 0 when the underlying column has no
        // AUTO_INCREMENT (a known import-from-dump issue on AwardSpace).
        // Recover the real id by reading the row back via its unique
        // order_number — without this, the "if ($orderId > 0)" check below
        // would silently misfire and the order would be orphaned in the DB
        // while the visitor sees an error.
        if ($orderId <= 0) {
            $stmt = $pdo->prepare('SELECT id_order FROM orders WHERE order_number = ? ORDER BY id_order DESC LIMIT 1');
            $stmt->execute([$orderNumber]);
            $row = $stmt->fetch();
            $orderId = (int) ($row['id_order'] ?? 0);
        }

        if ($orderId <= 0) {
            // Still no id — surface as a recoverable error and roll back so
            // the order row is NOT left orphaned in the database.
            throw new RuntimeException('Unable to resolve the new order id for order ' . $orderNumber);
        }

        // Insert every cart line as an order_items row. If the
        // `order_items.id_item` column is missing AUTO_INCREMENT (the known
        // import issue), the FIRST row may succeed but the SECOND row will
        // throw "Duplicate entry '0' for key 'PRIMARY'". Catching that here
        // and rolling back the transaction guarantees no partial order is
        // persisted — the user re-submits and we re-attempt atomically.
        // The permanent fix is the SQL migration that restores AUTO_INCREMENT.
        foreach ($cartItemsForOrder as $item) {
            checkoutInsertAllowed($pdo, 'order_items', [
                'id_order' => $orderId,
                'id_variant' => (int) ($item['id_variant'] ?? 0),
                'size' => (string) ($item['size'] ?? ''),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'price' => (float) ($item['price'] ?? 0),
            ]);
        }

        $pdo->commit();
    } catch (Throwable $innerException) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Re-throw so the outer catch (which logs to error_log + shows the
        // user-friendly message) handles the response uniformly.
        throw $innerException;
    }

    clearCart();
    $_SESSION['order_id'] = $orderId;
    $_SESSION['checkout_order_id'] = $orderId;
    redirect(pageUrl('checkout.php?step=confirm&order=' . $orderId));
} catch (Throwable $exception) {
    // Log the real exception server-side only — never expose PDO
    // exceptions or stack traces to the visitor in production.
    error_log(
        'Checkout failed: ' . get_class($exception) . ': '
        . $exception->getMessage() . ' in ' . $exception->getFile()
        . ' on line ' . $exception->getLine()
    );

    // Preserve the existing user-friendly error flow.
    $_SESSION['checkout_error'] = t('checkout_save_error');
    redirect(pageUrl('checkout.php'));
}
}

$requestedStep = (string) ($_GET['step'] ?? 'info');
$requestedOrderId = filter_input(INPUT_GET, 'order', FILTER_VALIDATE_INT);
$requestedOrderId = $requestedOrderId !== false && $requestedOrderId > 0 ? $requestedOrderId : (int) ($_SESSION['checkout_order_id'] ?? 0);
$orderContext = $requestedOrderId > 0 ? checkoutGetOrder($pdo, $requestedOrderId, $langCode) : null;
$sessionOrderId = (int) ($_SESSION['checkout_order_id'] ?? 0);

if ($orderContext && $sessionOrderId !== $requestedOrderId) {
    $orderContext = null;
}

$cartItems = getCartItems();
if (!$orderContext && empty($cartItems)) {
    redirect(pageUrl('cart.php'));
}

$order = $orderContext['order'] ?? null;
$orderItems = $orderContext['items'] ?? [];
$orderStatus = (string) ($order['status'] ?? '');

if ($orderContext) {
    $currentStep = $requestedStep === 'done' || $orderStatus === 'Confirmée' ? 'done' : 'confirm';
    $summaryItems = checkoutBuildOrderSummaryItems($orderItems);
    $subtotal = (float) $orderContext['subtotal'];
    $shipping = (float) $orderContext['shipping'];
    $total = (float) $orderContext['total'];
} else {
    $currentStep = 'info';
    $summaryItems = checkoutBuildCartSummaryItems($pdo, $cartItems);
    $subtotal = getCartTotal();
    $deliveryPrice = (float) ($settings['delivery_price'] ?? 0);
    $freeDeliveryThreshold = (float) ($settings['free_delivery'] ?? 0);
    $shipping = $freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold ? 0.0 : $deliveryPrice;
    $total = $subtotal + $shipping;
}

$checkoutError = (string) ($_SESSION['checkout_error'] ?? '');
unset($_SESSION['checkout_error']);

$whatsappPhone = checkoutNormalizeWhatsappPhone(($settings['whatsapp'] ?? '') ?: ($settings['telephone'] ?? ''));
$whatsappMessage = $order ? checkoutBuildWhatsappMessage($order, $orderItems) : '';

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' - ' . t('checkout_title');
$metaDescription = t('checkout_subtitle');

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content checkout-page">
    <section class="checkout-hero">
        <div class="container">
            <div class="checkout-progress" aria-label="<?= e(t('checkout_progress_label')) ?>">
                <?php
                $steps = [
                    'cart' => t('checkout_step_cart'),
                    'info' => t('checkout_step_info'),
                    'confirm' => t('checkout_step_confirm'),
                    'done' => t('checkout_step_done'),
                ];
                $stepOrder = array_keys($steps);
                $activeIndex = array_search($currentStep, $stepOrder, true);
                foreach ($steps as $stepKey => $stepLabel) :
                    $stepIndex = array_search($stepKey, $stepOrder, true);
                    $stepClass = $stepIndex < $activeIndex ? ' is-complete' : ($stepKey === $currentStep ? ' is-active' : '');
                ?>
                    <div class="checkout-progress__item<?= $stepClass ?>" data-step="<?= e($stepKey) ?>">
                        <span class="checkout-progress__dot"><?= $stepIndex + 1 ?></span>
                        <span class="checkout-progress__label"><?= e($stepLabel) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="checkout-shell">
        <div class="container checkout-layout-luxe">
            <div class="checkout-main-panel">
                <div class="checkout-title-block">
                    <span class="checkout-eyebrow">AREACH</span>
                    <h1><?= e(t('checkout_title')) ?></h1>
                    <p><?= e(t('checkout_subtitle')) ?></p>
                </div>

                <?php if ($checkoutError !== '') : ?>
                    <div class="checkout-alert" role="alert"><?= e($checkoutError) ?></div>
                <?php endif; ?>

                <?php if ($currentStep === 'info') : ?>
                    <form method="post" action="<?= pageUrl('checkout.php') ?>" class="checkout-luxe-form">
                        <section class="checkout-card">
                            <div class="checkout-card__heading">
                                <span>01</span>
                                <h2><?= e(t('checkout_customer_information')) ?></h2>
                            </div>
                            <div class="checkout-form-grid">
                                <div class="checkout-field">
                                    <label for="first_name"><?= e(t('checkout_first_name')) ?></label>
                                    <input type="text" id="first_name" name="first_name" class="input" required autocomplete="given-name">
                                </div>
                                <div class="checkout-field">
                                    <label for="last_name"><?= e(t('checkout_last_name')) ?></label>
                                    <input type="text" id="last_name" name="last_name" class="input" required autocomplete="family-name">
                                </div>
                                <div class="checkout-field">
                                    <label for="phone"><?= e(t('checkout_phone')) ?></label>
                                    <input type="tel" id="phone" name="phone" class="input" required autocomplete="tel">
                                </div>
                                <div class="checkout-field">
                                    <label for="email"><?= e(t('checkout_email')) ?></label>
                                    <input type="email" id="email" name="email" class="input" required autocomplete="email">
                                </div>
                            </div>
                        </section>

                        <section class="checkout-card">
                            <div class="checkout-card__heading">
                                <span>02</span>
                                <h2><?= e(t('checkout_delivery_information')) ?></h2>
                            </div>
                            <div class="checkout-form-grid">
                                <div class="checkout-field">
                                    <label for="city"><?= e(t('checkout_city')) ?></label>
                                    <input type="text" id="city" name="city" class="input" required autocomplete="address-level2">
                                </div>
                                <div class="checkout-field checkout-field--full">
                                    <label for="address"><?= e(t('checkout_address')) ?></label>
                                    <input type="text" id="address" name="address" class="input" required autocomplete="street-address">
                                </div>
                            </div>
                        </section>

                        <section class="checkout-card checkout-payment-card">
                            <div class="checkout-card__heading">
                                <span>03</span>
                                <h2><?= e(t('checkout_payment_information')) ?></h2>
                            </div>
                            <div class="checkout-payment-note">
                                <i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i>
                                <div>
                                    <strong><?= e(t('checkout_payment_cod_title')) ?></strong>
                                    <p><?= e(t('checkout_payment_cod_text')) ?></p>
                                </div>
                            </div>
                        </section>

                        <button type="submit" class="checkout-primary-action">
                            <?= e(t('checkout_continue_to_confirm')) ?>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>
                <?php elseif ($currentStep === 'confirm' && $order) : ?>
                    <section class="checkout-card checkout-confirm-panel" id="checkout-confirm-panel">
                        <div class="checkout-card__heading">
                            <span>03</span>
                            <h2><?= e(t('checkout_confirmation_title')) ?></h2>
                        </div>
                        <p class="checkout-confirm-text"><?= e(t('checkout_confirmation_text')) ?></p>
                        <p class="checkout-order-number"><?= e(t('checkout_order_number')) ?> <strong>#<?= e((string) ($order['order_number'] ?? $requestedOrderId)) ?></strong></p>
                        <button type="button"
                                class="checkout-whatsapp-button"
                                id="checkout-whatsapp-confirm"
                                data-order-id="<?= (int) $requestedOrderId ?>"
                                data-token="<?= e((string) $_SESSION['checkout_confirm_token']) ?>"
                                data-phone="<?= e($whatsappPhone) ?>"
                                data-message="<?= e($whatsappMessage) ?>"
                                data-confirm-url="<?= pageUrl('confirm-order.php') ?>"
                                <?= $whatsappPhone === '' ? 'disabled' : '' ?>>
                            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                            <span><?= e(t('checkout_whatsapp_confirm')) ?></span>
                        </button>
                        <p class="checkout-confirm-error" id="checkout-confirm-error" hidden><?= e(t('checkout_confirm_error')) ?></p>
                    </section>
                <?php endif; ?>

                <section class="checkout-done-panel<?= $currentStep === 'done' ? ' is-visible' : '' ?>" id="checkout-done-panel" <?= $currentStep === 'done' ? '' : 'hidden' ?>>
                    <div class="checkout-success-icon"><i class="fa-solid fa-check" aria-hidden="true"></i></div>
                    <span class="checkout-eyebrow">AREACH</span>
                    <h2><?= e(t('checkout_confirmed_title')) ?></h2>
                    <?php if ($order) : ?>
                        <p class="checkout-order-number"><?= e(t('checkout_order_number')) ?> <strong>#<?= e((string) ($order['order_number'] ?? $requestedOrderId)) ?></strong></p>
                    <?php endif; ?>
                    <p><?= e(t('checkout_confirmed_text')) ?></p>
                    <a href="<?= pageUrl('shop.php') ?>" class="checkout-secondary-action"><?= e(t('checkout_continue_shopping')) ?></a>
                </section>
            </div>

            <aside class="checkout-summary-luxe" aria-labelledby="checkout-summary-title">
                <h2 id="checkout-summary-title"><?= e(t('checkout_order_summary')) ?></h2>
                <div class="checkout-summary-items">
                    <?php foreach ($summaryItems as $item) : ?>
                        <?php
                        $itemImage = imagePath('products', $item['image'] ?? '');
                        $quantity = max(1, (int) ($item['quantity'] ?? 1));
                        $price = (float) ($item['price'] ?? 0);
                        $lineTotal = $price * $quantity;
                        ?>
                        <article class="checkout-summary-item">
                            <a href="<?= productUrl((int) ($item['id_product'] ?? 0)) ?>" class="checkout-summary-item__media">
                                <?php if ($itemImage !== '') : ?>
                                    <img src="<?= e($itemImage) ?>" alt="<?= e((string) ($item['name'] ?? '')) ?>" loading="lazy">
                                <?php else : ?>
                                    <span><i class="fa-solid fa-shirt" aria-hidden="true"></i></span>
                                <?php endif; ?>
                            </a>
                            <div class="checkout-summary-item__body">
                                <h3><?= e((string) ($item['name'] ?? '')) ?></h3>
                                <p>
                                    <?php if (!empty($item['color_name'])) : ?><?= e(t('checkout_color')) ?>: <?= e((string) $item['color_name']) ?><?php endif; ?>
                                    <?php if (!empty($item['size'])) : ?><?= !empty($item['color_name']) ? ' - ' : '' ?><?= e(t('checkout_size')) ?>: <?= e((string) $item['size']) ?><?php endif; ?>
                                </p>
                                <div class="checkout-summary-item__meta">
                                    <span><?= e(t('checkout_quantity')) ?> <?= $quantity ?></span>
                                    <span>
                                        <?php if (!empty($item['regular_price'])) : ?>
                                            <del><?= e(formatCurrency((float) $item['regular_price'])) ?></del>
                                        <?php endif; ?>
                                        <?= e(formatCurrency($price)) ?>
                                    </span>
                                </div>
                            </div>
                            <strong><?= e(formatCurrency($lineTotal)) ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="checkout-summary-totals">
                    <p><span><?= e(t('checkout_subtotal')) ?></span><strong><?= e(formatCurrency($subtotal)) ?></strong></p>
                    <p><span><?= e(t('checkout_delivery')) ?></span><strong><?= e(formatCurrency($shipping)) ?></strong></p>
                    <p class="checkout-summary-totals__final"><span><?= e(t('checkout_total')) ?></span><strong><?= e(formatCurrency($total)) ?></strong></p>
                </div>
            </aside>
        </div>
    </section>
</main>

<script>
(function () {
    const button = document.getElementById('checkout-whatsapp-confirm');
    if (!button) {
        return;
    }

    const errorEl = document.getElementById('checkout-confirm-error');
    const donePanel = document.getElementById('checkout-done-panel');
    const confirmPanel = document.getElementById('checkout-confirm-panel');
    let locked = false;

    function setStepDone(orderId) {
        document.querySelectorAll('.checkout-progress__item').forEach(function (item) {
            const step = item.dataset.step;
            item.classList.toggle('is-active', step === 'done');
            item.classList.toggle('is-complete', ['cart', 'info', 'confirm'].includes(step));
        });

        if (confirmPanel) {
            confirmPanel.hidden = true;
        }
        if (donePanel) {
            donePanel.hidden = false;
            donePanel.classList.add('is-visible');
        }

        if (window.history && orderId) {
            window.history.replaceState(null, '', '/checkout?step=done&order=' + encodeURIComponent(orderId));
        }
    }

    function openWhatsapp(whatsappWindow, phone, message) {
        if (!whatsappWindow) {
            return;
        }
        if (!phone || !message) {
            try {
                whatsappWindow.close();
            } catch (e) {}
            return;
        }

        const encodedMessage = encodeURIComponent(message);
        const nativeUrl = 'whatsapp://send?phone=' + encodeURIComponent(phone) + '&text=' + encodedMessage;
        const fallbackUrl = 'https://web.whatsapp.com/send?phone=' + encodeURIComponent(phone) + '&text=' + encodedMessage;
        let appOpened = false;

        const markOpened = function () {
            appOpened = true;
        };

        document.addEventListener('visibilitychange', function onVisibilityChange() {
            if (document.hidden) {
                markOpened();
                document.removeEventListener('visibilitychange', onVisibilityChange);
            }
        });
        window.addEventListener('pagehide', markOpened, { once: true });

        try {
            whatsappWindow.location.href = nativeUrl;
        } catch (e) {
            try {
                whatsappWindow.location.href = fallbackUrl;
            } catch (err) {}
            return;
        }

        window.setTimeout(function () {
            if (!appOpened && !document.hidden) {
                try {
                    whatsappWindow.location.href = fallbackUrl;
                } catch (e) {}
            }
        }, 1600);
    }

    button.addEventListener('click', async function () {
        if (locked) {
            return;
        }

        // Open blank tab/window synchronously inside the user's click handler to prevent popup blocking
        const whatsappWindow = window.open('', '_blank');

        locked = true;
        button.disabled = true;
        button.classList.add('is-loading');
        const buttonText = button.querySelector('span');
        const previousText = buttonText ? buttonText.textContent : '';
        if (buttonText) {
            buttonText.textContent = '<?= e(t('checkout_confirming')) ?>';
        }
        if (errorEl) {
            errorEl.hidden = true;
        }

        const orderId = button.dataset.orderId || '';
        const token = button.dataset.token || '';
        const body = new URLSearchParams();
        body.set('order_id', orderId);
        body.set('token', token);

        try {
            const response = await fetch(button.dataset.confirmUrl || '/confirm-order', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'Accept': 'application/json'
                },
                body: body.toString()
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Confirmation failed');
            }

            setStepDone(orderId);
            openWhatsapp(whatsappWindow, button.dataset.phone || '', button.dataset.message || '');
        } catch (error) {
            if (whatsappWindow) {
                try {
                    whatsappWindow.close();
                } catch (e) {}
            }
            locked = false;
            button.disabled = false;
            button.classList.remove('is-loading');
            if (buttonText) {
                buttonText.textContent = previousText;
            }
            if (errorEl) {
                errorEl.hidden = false;
            }
        }
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
