<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$orderId = filter_input(INPUT_GET, 'order', FILTER_VALIDATE_INT);
$orderId = $orderId !== false && $orderId > 0 ? $orderId : (int) ($_SESSION['order_id'] ?? 0);

$order = null;
$items = [];

if ($orderId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id_order = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if ($order) {
        $stmt = $pdo->prepare(
            'SELECT
                oi.*,
                pv.color_name,
                pv.price AS variant_price,
                COALESCE(pt.name, pt_fr.name) AS product_name
             FROM order_items oi
             INNER JOIN product_variants pv ON pv.id_variant = oi.id_variant
             INNER JOIN products p ON p.id_product = pv.id_product
             LEFT JOIN languages l ON l.code = ?
             LEFT JOIN product_translations pt ON pt.id_product = p.id_product AND pt.id_language = l.id_language
             LEFT JOIN languages l_fr ON l_fr.code = "fr"
             LEFT JOIN product_translations pt_fr ON pt_fr.id_product = p.id_product AND pt_fr.id_language = l_fr.id_language
             WHERE oi.id_order = ?
             ORDER BY oi.id_item ASC'
        );
        $stmt->execute([$langCode, $orderId]);
        $items = $stmt->fetchAll();
    }
}

$whatsappNumber = ($settings['whatsapp'] ?? '') ?: ($settings['telephone'] ?? '');
$whatsappPhone = preg_replace('/\D+/', '', $whatsappNumber);

$whatsappUrl = '';
if ($order && $whatsappPhone !== '') {
    $lines = [];
    $lines[] = 'Bonjour AREACH 👋';
    $lines[] = '';
    $lines[] = 'Je souhaite confirmer ma commande.';
    $lines[] = '';

    $orderNumber = html_entity_decode($order['order_number'] ?? '');
    $customerName = html_entity_decode($order['customer_name'] ?? '');
    $phone = html_entity_decode($order['telephone'] ?? '');
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

    if (!empty($items)) {
        $lines[] = '';
        $lines[] = 'Produits :';
        foreach ($items as $item) {
            $parts = [];
            $productName = html_entity_decode($item['product_name'] ?? '');
            $parts[] = $productName !== '' ? $productName : 'Produit #' . (int) $item['id_variant'];
            $parts[] = 'Qté : ' . (int) ($item['quantity'] ?? 1);
            $size = $item['size'] ?? '';
            if ($size !== '') {
                $parts[] = 'Taille : ' . $size;
            }
            $color = $item['color_name'] ?? '';
            if ($color !== '') {
                $parts[] = 'Couleur : ' . $color;
            }
            $lines[] = '- ' . implode(' — ', $parts);
        }
    }

    $lines[] = '';
    $lines[] = '💰 Total : ' . number_format($total, 2, ',', ' ') . ' €';
    $lines[] = '';
    $lines[] = 'Merci de confirmer ma commande.';

    $message = implode("\n", $lines);
    $whatsappUrl = 'https://web.whatsapp.com/send?phone=' . $whatsappPhone . '&text=' . rawurlencode($message);
}

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
                <?php if ($order && !empty($order['order_number'])) : ?>
                    <p>Numéro de commande : <strong>#<?= e($order['order_number']) ?></strong></p>
                <?php elseif ($orderId > 0) : ?>
                    <p>Numéro de commande : #<?= (int) $orderId ?></p>
                <?php endif; ?>

                <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1.5rem;">
                    <a href="<?= pageUrl('shop.php') ?>" class="btn btn--primary">Continuer mes achats</a>
                    <?php if ($whatsappUrl !== '') : ?>
                        <a href="<?= e($whatsappUrl) ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn btn--accent">
                            <i class="fa-brands fa-whatsapp"></i> Confirmer via WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
