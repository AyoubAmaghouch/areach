<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login");
    exit;
}

$stmt    = $pdo->query("SELECT * FROM banners ORDER BY display_order ASC");
$banners = $stmt->fetchAll();

// Handle flash from CRUD redirects
$flash = $_SESSION['banner_flash'] ?? null;
unset($_SESSION['banner_flash']);

if (!$flash && isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $flash = ['type' => 'success', 'message' => 'Bannière supprimée avec succès.'];
} elseif (!$flash && isset($_GET['error']) && $_GET['error'] === 'delete_failed') {
    $flash = ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-rectangle-ad me-2" style="color:var(--color-primary);"></i>
            Bannières
        </h1>
        <p class="page-subtitle"><?= count($banners) ?> bannière<?= count($banners) !== 1 ? 's' : '' ?></p>
    </div>
    <a href="crud/banners/create" class="btn-primary-admin">
        <i class="fa-solid fa-plus"></i> Ajouter une bannière
    </a>
</div>

<!-- Flash Alert -->
<?php if ($flash): ?>
    <div class="flash-alert <?= htmlspecialchars($flash['type'] ?? 'info') ?>" data-auto-dismiss role="alert">
        <i class="fa-solid <?= ($flash['type'] ?? '') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= htmlspecialchars($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<!-- Table -->
<div class="admin-table-wrapper">

    <div class="admin-table-header">
        <div class="admin-table-title">
            <i class="fa-solid fa-list" style="color:var(--color-primary);"></i>
            Liste des bannières
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Titre</th>
                    <th class="text-center">Ordre</th>
                    <th>Statut</th>
                    <th class="text-center" style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($banners)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                            Aucune bannière
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($banners as $banner):
                        $isActive = (int)($banner['status'] ?? 0) === 1;
                    ?>
                    <tr>
                        <td class="text-muted fw-semibold">#<?= (int)$banner['id_banner'] ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($banner['title'] ?? '') ?></td>
                        <td class="text-center">
                            <span class="badge bg-secondary"><?= (int)$banner['display_order'] ?></span>
                        </td>
                        <td>
                            <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $isActive ? 'Actif' : 'Inactif' ?>
                            </span>
                        </td>
                        <td>
                            <a href="crud/banners/edit?id=<?= (int)$banner['id_banner'] ?>"
                               class="btn btn-sm btn-action edit me-1"
                               data-bs-toggle="tooltip" title="Modifier">
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
