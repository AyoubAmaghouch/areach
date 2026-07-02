<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../orders.php");
    exit;
}

$id = (int) $_GET['id'];

// Commande
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id_order = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die("Commande introuvable.");
}

// Produits de la commande — with image
$stmt = $pdo->prepare("
SELECT
    oi.quantity,
    oi.size,
    oi.price,
    pt.name AS product_name,
    pv.color_name,
    pi.image AS product_image,
    p.id_category
FROM order_items oi
INNER JOIN product_variants pv ON oi.id_variant = pv.id_variant
INNER JOIN products p ON pv.id_product = p.id_product
INNER JOIN product_translations pt ON p.id_product = pt.id_product
INNER JOIN languages l ON pt.id_language = l.id_language
LEFT JOIN product_images pi ON pi.id_image = (
    SELECT pi2.id_image FROM product_images pi2
    WHERE pi2.id_variant = oi.id_variant
    ORDER BY CASE WHEN pi2.is_primary = 1 THEN 0 ELSE 1 END, pi2.id_image ASC
    LIMIT 1
)
WHERE oi.id_order = ?
AND l.code = 'fr'
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Status config
$statusConfig = [
    'En attente'     => ['badge' => 'badge-pending',   'icon' => 'fa-clock'],
    'Confirmée'      => ['badge' => 'badge-confirmed',  'icon' => 'fa-circle-check'],
    'En préparation' => ['badge' => 'badge-preparing',  'icon' => 'fa-box-open'],
    'Expédiée'       => ['badge' => 'badge-shipped',    'icon' => 'fa-truck'],
    'Livrée'         => ['badge' => 'badge-delivered',  'icon' => 'fa-circle-check'],
    'Annulée'        => ['badge' => 'badge-cancelled',  'icon' => 'fa-xmark'],
];

$currentStatus  = $order['status'] ?? '';
$statusBadge    = $statusConfig[$currentStatus]['badge'] ?? 'badge-inactive';
$statusIcon     = $statusConfig[$currentStatus]['icon']  ?? 'fa-question';
$isPrintMode    = isset($_GET['print']) && $_GET['print'] === '1';

include '../../includes/header.php';

if ($isPrintMode): ?>
<style>
    #admin-sidebar, #admin-topbar { display: none !important; }
    #admin-main { margin-left: 0 !important; }
    .sticky-form-footer, .page-header .d-flex { display: none !important; }
    @media print { body { background: #fff; } }
</style>
<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-receipt me-2" style="color:var(--color-primary);"></i>
            Commande #<?= htmlspecialchars($order['order_number'] ?? '') ?>
        </h1>
        <p class="page-subtitle">
            Passée le <?= htmlspecialchars(substr($order['created_at'] ?? '', 0, 10)) ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-print me-1"></i> Imprimer
        </button>
        <a href="update-status.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-pen-to-square me-1"></i> Changer le statut
        </a>
        <a href="../../orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- Customer Info -->
    <div class="col-12 col-lg-5">
        <div class="form-card h-100">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-user"></i></span>
                <h3>Informations client</h3>
            </div>
            <div class="form-card-body">

                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="customer-avatar" style="width:56px;height:56px;font-size:1.2rem;">
                        <?= strtoupper(substr($order['customer_name'] ?? 'C', 0, 1) . substr($order['customer_lastname'] ?? '', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold fs-6">
                            <?= htmlspecialchars(($order['customer_name'] ?? '') . ' ' . ($order['customer_lastname'] ?? '')) ?>
                        </div>
                        <div class="text-muted small"><?= htmlspecialchars($order['email'] ?? '') ?></div>
                    </div>
                </div>

                <table class="w-100" style="font-size:.85rem;">
                    <tr class="border-bottom">
                        <td class="py-2 text-muted fw-semibold" style="width:40%;">
                            <i class="fa-solid fa-phone me-2"></i>Téléphone
                        </td>
                        <td class="py-2"><?= htmlspecialchars($order['telephone'] ?? '') ?></td>
                    </tr>
                    <tr class="border-bottom">
                        <td class="py-2 text-muted fw-semibold">
                            <i class="fa-solid fa-city me-2"></i>Ville
                        </td>
                        <td class="py-2"><?= htmlspecialchars($order['city'] ?? '') ?></td>
                    </tr>
                    <tr class="border-bottom">
                        <td class="py-2 text-muted fw-semibold">
                            <i class="fa-solid fa-location-dot me-2"></i>Adresse
                        </td>
                        <td class="py-2"><?= nl2br(htmlspecialchars($order['address'] ?? '')) ?></td>
                    </tr>
                    <?php if (!empty($order['notes'])): ?>
                    <tr>
                        <td class="py-2 text-muted fw-semibold">
                            <i class="fa-solid fa-note-sticky me-2"></i>Notes
                        </td>
                        <td class="py-2"><?= nl2br(htmlspecialchars($order['notes'] ?? '')) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

            </div>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="col-12 col-lg-7">
        <div class="form-card h-100">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-bag-shopping"></i></span>
                <h3>Récapitulatif</h3>
                <span class="badge-status <?= $statusBadge ?> ms-auto">
                    <i class="fa-solid <?= $statusIcon ?> me-1"></i><?= htmlspecialchars($currentStatus) ?>
                </span>
            </div>
            <div class="form-card-body p-0">

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width:50px;"></th>
                                <th>Produit</th>
                                <th>Couleur</th>
                                <th>Taille</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">Prix unit.</th>
                                <th class="text-end">Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Aucun produit</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item):
                                    $price    = (float)($item['price'] ?? 0);
                                    $qty      = (int)($item['quantity'] ?? 0);
                                    $subtotal = $price * $qty;
                                    $imgSrc   = adminImagePath('products', $item['product_image'], (int)$item['id_category']);
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($imgSrc !== ''): ?>
                                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['product_name'] ?? '') ?>" class="table-thumb">
                                        <?php else: ?>
                                            <div class="table-thumb-placeholder">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold"><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($item['color_name'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($item['size'] ?? '') ?></span>
                                    </td>
                                    <td class="text-center"><?= $qty ?></td>
                                    <td class="text-end"><?= number_format($price, 2) ?> €</td>
                                    <td class="text-end fw-semibold"><?= number_format($subtotal, 2) ?> €</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f8fafc;">
                                <td colspan="5"></td>
                                <td class="text-end fw-bold py-3">Total</td>
                                <td class="text-end fw-bold py-3 fs-6" style="color:var(--color-primary);">
                                    <?= number_format((float)($order['total'] ?? 0), 2) ?> €
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

<style>
@media print {
    #admin-sidebar, #admin-topbar, .page-header .d-flex, .sticky-form-footer { display: none !important; }
    #admin-main { margin-left: 0 !important; }
    body { background: white !important; }
    .form-card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>

<?php include '../../includes/footer.php'; ?>