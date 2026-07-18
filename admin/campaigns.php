<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login");
    exit;
}

$campaigns = $pdo->query("SELECT * FROM campaigns ORDER BY id_campaign DESC")->fetchAll();
$flash = $_SESSION['campaign_flash'] ?? null;
unset($_SESSION['campaign_flash']);

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-bullhorn me-2" style="color:var(--color-primary);"></i>
            Campagnes
        </h1>
        <p class="page-subtitle"><?= count($campaigns) ?> campagne<?= count($campaigns) !== 1 ? 's' : '' ?></p>
    </div>
    <a href="crud/campaigns/create" class="btn-primary-admin">
        <i class="fa-solid fa-plus"></i> Ajouter une campagne
    </a>
</div>

<?php if ($flash): ?>
    <div class="flash-alert <?= htmlspecialchars($flash['type'] ?? 'info') ?>" data-auto-dismiss role="alert">
        <i class="fa-solid <?= ($flash['type'] ?? '') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= htmlspecialchars($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="admin-table-wrapper">
    <div class="admin-table-header">
        <div class="admin-table-title">
            <i class="fa-solid fa-list" style="color:var(--color-primary);"></i>
            Liste des campagnes
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Sujet</th>
                    <th>Bouton</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th class="text-center" style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                            Aucune campagne
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $campaign): ?>
                        <?php $isActive = (int) ($campaign['status'] ?? 0) === 1; ?>
                        <tr>
                            <td class="text-muted fw-semibold">#<?= (int) $campaign['id_campaign'] ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($campaign['subject'] ?? '') ?></td>
                            <td><?= htmlspecialchars($campaign['button_text'] ?? '') ?></td>
                            <td>
                                <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $isActive ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars(substr($campaign['created_at'] ?? '', 0, 10)) ?></td>
                            <td>
                                <a href="crud/campaigns/create?id=<?= (int) $campaign['id_campaign'] ?>" class="btn btn-sm btn-action edit me-1" data-bs-toggle="tooltip" title="Modifier">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
