<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id_customer = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Client introuvable.");
}

// Fetch orders linked to this customer.
$stmt = $pdo->prepare("
    SELECT id_order, order_number, total, COALESCE(NULLIF(status, ''), 'En attente') AS status, created_at
    FROM orders
    WHERE id_customer = ? OR email = ?
    ORDER BY id_order DESC
");
$stmt->execute([$id, $customer['email'] ?? '']);
$orders = $stmt->fetchAll();

$initials = strtoupper(substr($customer['nom'] ?? 'C', 0, 1) . substr($customer['prenom'] ?? '', 0, 1));

include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-user me-2" style="color:var(--color-primary);"></i>
            Profil Client
        </h1>
        <p class="page-subtitle"><?= htmlspecialchars(($customer['nom'] ?? '') . ' ' . ($customer['prenom'] ?? '')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="toggle-status.php?id=<?= $id ?>" class="btn <?= $customer['status'] ? 'btn-outline-danger' : 'btn-outline-success' ?> btn-sm">
            <i class="fa-solid <?= $customer['status'] ? 'fa-ban' : 'fa-check' ?> me-1"></i>
            <?= $customer['status'] ? 'Bloquer' : 'Activer' ?>
        </a>
        <a href="../../customers.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- Customer Card -->
    <div class="col-12 col-lg-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-id-card"></i></span>
                <h3>Informations</h3>
            </div>
            <div class="form-card-body text-center">

                <div class="customer-avatar mx-auto mb-3">
                    <?= $initials ?>
                </div>

                <h4 class="fw-bold mb-1"><?= htmlspecialchars(($customer['nom'] ?? '') . ' ' . ($customer['prenom'] ?? '')) ?></h4>
                <p class="text-muted small mb-4">Inscrit le <?= htmlspecialchars(substr($customer['created_at'] ?? '', 0, 10)) ?></p>

                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge-status <?= $customer['status'] ? 'badge-active' : 'badge-cancelled' ?>">
                        <?= $customer['status'] ? 'Compte Actif' : 'Compte Bloqué' ?>
                    </span>
                    <span class="badge-status <?= $customer['newsletter'] ? 'badge-active' : 'badge-inactive' ?>">
                        <i class="fa-solid fa-envelope me-1"></i>Newsletter
                    </span>
                </div>

                <hr>

                <div class="text-start" style="font-size:.85rem;">
                    <div class="mb-2">
                        <strong>Email :</strong>
                        <span class="text-muted d-block"><?= htmlspecialchars($customer['email'] ?? '') ?></span>
                    </div>
                    <div class="mb-2">
                        <strong>Téléphone :</strong>
                        <span class="text-muted d-block"><?= htmlspecialchars($customer['telephone'] ?? 'Non renseigné') ?></span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Orders History -->
    <div class="col-12 col-lg-8">
        <div class="admin-table-wrapper">
            <div class="admin-table-header">
                <div class="admin-table-title">
                    <i class="fa-solid fa-clock-rotate-left" style="color:var(--color-primary);"></i>
                    Historique des commandes (<?= count($orders) ?>)
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>N° Commande</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Aucune commande passée par ce client.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order):
                                $status = htmlspecialchars($order['status'] ?? '');
                                $badgeClass = match($status) {
                                    'En attente'     => 'badge-pending',
                                    'Confirmée'      => 'badge-confirmed',
                                    'En préparation' => 'badge-preparing',
                                    'Expédiée'       => 'badge-shipped',
                                    'Livrée'         => 'badge-delivered',
                                    'Annulée'        => 'badge-cancelled',
                                    default          => 'badge-inactive',
                                };
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($order['order_number'] ?? '') ?></td>
                                <td class="fw-semibold"><?= number_format((float)($order['total'] ?? 0), 2) ?> €</td>
                                <td>
                                    <span class="badge-status <?= $badgeClass ?>"><?= $status ?></span>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars(substr($order['created_at'] ?? '', 0, 10)) ?></td>
                                <td class="text-center">
                                    <a href="../orders/details.php?id=<?= (int)$order['id_order'] ?>"
                                       class="btn btn-sm btn-action view"
                                       data-bs-toggle="tooltip" title="Voir la commande">
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
    </div>

</div>

<?php include '../../includes/footer.php'; ?>
