<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$cartItems = getCartItems();
if (empty($cartItems)) {
    redirect(pageUrl('cart.php'));
}

function getAvailableColumns(PDO $pdo, string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $cache[$table] = $columns;

    return $columns;
}

function insertAllowed(PDO $pdo, string $table, array $data): int
{
    $columns = getAvailableColumns($pdo, $table);
    $filtered = [];

    foreach ($data as $key => $value) {
        if (in_array($key, $columns, true)) {
            $filtered[$key] = $value;
        }
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

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $customerName = trim((string) ($_POST['customer_name'] ?? ''));
    $customerEmail = trim((string) ($_POST['email'] ?? ''));
    $customerPhone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $newsletter = !empty($_POST['newsletter']) ? 1 : 0;
    $password = trim((string) ($_POST['password'] ?? ''));

    if ($customerName === '' || $customerEmail === '' || $address === '' || $city === '') {
        $_SESSION['checkout_error'] = 'Veuillez remplir tous les champs requis.';
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
            $customerData = [
                'nom' => $customerName,
                'prenom' => '',
                'email' => $customerEmail,
                'password' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
                'newsletter' => $newsletter,
                'status' => 1,
                'telephone' => $customerPhone,
                'address' => $address,
                'city' => $city,
            ];
            $customerId = insertAllowed($pdo, 'customers', $customerData);
        }
    }

    $subtotal = getCartTotal();
    $deliveryPrice = (float) ($settings['delivery_price'] ?? 0);
    $freeDeliveryThreshold = (float) ($settings['free_delivery'] ?? 0);
    $shipping = $freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold ? 0.0 : $deliveryPrice;
    $total = $subtotal + $shipping;

    $orderData = [
        'id_customer' => $customerId,
        'customer_name' => $customerName,
        'email' => $customerEmail,
        'phone' => $customerPhone,
        'address' => $address,
        'city' => $city,
        'notes' => $notes,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $total,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $orderId = insertAllowed($pdo, 'orders', $orderData);

    if ($orderId > 0) {
        foreach ($cartItems as $item) {
            insertAllowed($pdo, 'order_items', [
                'id_order' => $orderId,
                'id_product' => (int) ($item['id_product'] ?? 0),
                'id_variant' => (int) ($item['id_variant'] ?? 0),
                'product_name' => $item['name'] ?? '',
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['price'] ?? 0),
                'total_price' => (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1),
            ]);
        }

        clearCart();
        $_SESSION['order_id'] = $orderId;
        redirect(pageUrl('success.php?order=' . $orderId));
    }

    $_SESSION['checkout_error'] = 'La commande n’a pas pu être enregistrée.';
    redirect(pageUrl('checkout.php'));
}

$subtotal = getCartTotal();
$deliveryPrice = (float) ($settings['delivery_price'] ?? 0);
$freeDeliveryThreshold = (float) ($settings['free_delivery'] ?? 0);
$shipping = $freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold ? 0.0 : $deliveryPrice;
$total = $subtotal + $shipping;

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Checkout';
$metaDescription = 'Finaliser votre commande';

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title">Checkout</h1>
            <p class="page-header__subtitle">Finalisez votre commande</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container checkout-layout">
            <form method="post" action="<?= pageUrl('checkout.php') ?>" class="checkout-form">
                <h2 class="section-title">Informations client</h2>
                <div class="checkout-grid">
                    <div class="product-detail__field">
                        <label for="customer_name">Nom complet</label>
                        <input type="text" id="customer_name" name="customer_name" class="input" required>
                    </div>
                    <div class="product-detail__field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="input" required>
                    </div>
                    <div class="product-detail__field">
                        <label for="phone">Téléphone</label>
                        <input type="tel" id="phone" name="phone" class="input">
                    </div>
                    <div class="product-detail__field">
                        <label for="address">Adresse</label>
                        <input type="text" id="address" name="address" class="input" required>
                    </div>
                    <div class="product-detail__field">
                        <label for="city">Ville</label>
                        <input type="text" id="city" name="city" class="input" required>
                    </div>
                    <div class="product-detail__field">
                        <label for="password">Mot de passe (optionnel)</label>
                        <input type="password" id="password" name="password" class="input">
                    </div>
                    <div class="product-detail__field checkout-full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="input" rows="4"></textarea>
                    </div>
                    <label class="checkout-checkbox">
                        <input type="checkbox" name="newsletter" value="1">
                        <span>S’inscrire à la newsletter</span>
                    </label>
                </div>
                <button type="submit" class="btn btn--primary">Créer la commande</button>
            </form>

            <aside class="cart-summary">
                <h2 class="cart-summary__title">Résumé de la commande</h2>
                <?php foreach ($cartItems as $item) : ?>
                    <p class="cart-summary__row"><span><?= e($item['name'] ?? '') ?> × <?= (int) ($item['quantity'] ?? 1) ?></span><strong><?= e(formatCurrency((float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 1))) ?></strong></p>
                <?php endforeach; ?>
                <p class="cart-summary__row"><span>Sous-total</span><strong><?= e(formatCurrency($subtotal)) ?></strong></p>
                <p class="cart-summary__row"><span>Livraison</span><strong><?= e(formatCurrency($shipping)) ?></strong></p>
                <p class="cart-summary__row cart-summary__row--total"><span>Total</span><strong><?= e(formatCurrency($total)) ?></strong></p>
            </aside>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>