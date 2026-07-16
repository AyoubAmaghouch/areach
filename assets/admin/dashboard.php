<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Stats queries
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$totalProducts = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$totalCategories = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$totalOrders = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM customers");
$totalCustomers = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM banners");
$totalBanners = (int) $stmt->fetchColumn();

// Revenue total
$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'Livrée'");
$totalRevenue = (float) $stmt->fetchColumn();

// Recent orders (last 5)
$stmt = $pdo->query("
    SELECT id_order, order_number, customer_name, customer_lastname, total, status, created_at
    FROM orders
    ORDER BY id_order DESC
    LIMIT 5
");
$recentOrders = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-gauge me-2" style="color:var(--color-primary);"></i>
            Tableau de bord
        </h1>
        <p class="page-subtitle">Bienvenue, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?> — voici un aperçu de votre boutique.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-bag-shopping me-1"></i> Commandes
        </a>
        <a href="crud/products/create.php" class="btn-primary-admin">
            <i class="fa-solid fa-plus"></i> Nouveau produit
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-4 mb-4">

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon bg-primary-soft">
                <i class="fa-solid fa-shirt"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($totalProducts) ?></div>
                <div class="stat-label">Produits</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon bg-success-soft">
                <i class="fa-solid fa-euro-sign"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($totalRevenue, 2) ?> €</div>
                <div class="stat-label">Chiffre d'affaires (Livrées)</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon bg-warning-soft">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($totalOrders) ?></div>
                <div class="stat-label">Commandes</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon bg-info-soft">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($totalCustomers) ?></div>
                <div class="stat-label">Clients</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon bg-purple-soft">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($totalCategories) ?></div>
                <div class="stat-label">Catégories</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="stat-card">
            <div class="stat-icon bg-danger-soft">
                <i class="fa-solid fa-rectangle-ad"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($totalBanners) ?></div>
                <div class="stat-label">Bannières</div>
            </div>
        </div>
    </div>

</div>

<!-- Recent Orders -->
<div class="admin-table-wrapper mb-4">
    <div class="admin-table-header">
        <div class="admin-table-title">
            <i class="fa-solid fa-clock-rotate-left" style="color:var(--color-primary);"></i>
            Commandes récentes
        </div>
        <a href="orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-right me-1"></i> Voir tout
        </a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Client</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            Aucune commande
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order):
                        $status = htmlspecialchars($order['status'] ?? '');
                        $badgeClass = match($status) {
                            'En attente'    => 'badge-pending',
                            'Confirmée'     => 'badge-confirmed',
                            'En préparation'=> 'badge-preparing',
                            'Expédiée'      => 'badge-shipped',
                            'Livrée'        => 'badge-delivered',
                            'Annulée'       => 'badge-cancelled',
                            default         => 'badge-inactive',
                        };
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($order['order_number'] ?? '') ?></td>
                        <td><?= htmlspecialchars(($order['customer_name'] ?? '') . ' ' . ($order['customer_lastname'] ?? '')) ?></td>
                        <td class="fw-semibold"><?= number_format((float)($order['total'] ?? 0), 2) ?> €</td>
                        <td><span class="badge-status <?= $badgeClass ?>"><?= $status ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars(substr($order['created_at'] ?? '', 0, 10)) ?></td>
                        <td class="text-center">
                            <a href="crud/orders/details.php?id=<?= (int)$order['id_order'] ?>"
                               class="btn btn-sm btn-action view"
                               data-bs-toggle="tooltip" title="Voir">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <span class="card-icon"><i class="fa-solid fa-bolt"></i></span>
                    Actions rapides
                </h2>
            </div>
            <div class="admin-card-body d-flex flex-wrap gap-3">
                <a href="crud/products/create.php" class="btn btn-outline-primary">
                    <i class="fa-solid fa-plus me-2"></i> Ajouter un produit
                </a>
                <a href="crud/categories/create.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-folder-plus me-2"></i> Ajouter une catégorie
                </a>
                <a href="orders.php" class="btn btn-outline-warning">
                    <i class="fa-solid fa-bags-shopping me-2"></i> Voir les commandes
                </a>
                <a href="customers.php" class="btn btn-outline-info">
                    <i class="fa-solid fa-users me-2"></i> Voir les clients
                </a>
                <a href="settings.php" class="btn btn-outline-dark">
                    <i class="fa-solid fa-sliders me-2"></i> Paramètres
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>