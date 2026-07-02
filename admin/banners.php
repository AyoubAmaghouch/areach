<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$stmt    = $pdo->query("SELECT * FROM banners ORDER BY display_order ASC");
$banners = $stmt->fetchAll();

// Handle GET-based flash from delete redirect
$flash = null;
if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $flash = ['type' => 'success', 'message' => 'Bannière supprimée avec succès.'];
} elseif (isset($_GET['error']) && $_GET['error'] === 'delete_failed') {
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
    <a href="crud/banners/create.php" class="btn-primary-admin">
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
        <table class="admin-table">
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
                            <div class="table-actions justify-content-center">
                                <a href="crud/banners/edit.php?id=<?= (int)$banner['id_banner'] ?>"
                                   class="btn-action edit"
                                   data-bs-toggle="tooltip" title="Modifier">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button
                                    type="button"
                                    class="btn-action delete"
                                    data-bs-toggle="tooltip" title="Supprimer"
                                    onclick="confirmDelete(<?= (int)$banner['id_banner'] ?>, '<?= addslashes(htmlspecialchars($banner['title'] ?? '')) ?>')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-delete">
            <div class="modal-header border-0 pb-0">
                <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
            <div class="modal-body text-center pt-0">
                <h5 class="fw-bold mb-2">Supprimer cette bannière ?</h5>
                <p class="text-muted mb-0">Vous allez supprimer <strong id="delete-banner-name"></strong>.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Annuler
                </button>
                <a href="#" id="delete-confirm-btn" class="btn btn-danger">
                    <i class="fa-solid fa-trash me-1"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
window.confirmDelete = function (id, name) {
    const btn = document.getElementById('delete-confirm-btn');
    if (btn) {
        btn.classList.remove('disabled');
        btn.innerHTML = '<i class="fa-solid fa-trash me-1"></i> Supprimer';
        btn.href = 'crud/banners/delete.php?id=' + id;
    }
    document.getElementById('delete-banner-name').textContent = name;
    deleteModal.show();
};

const confirmBtn = document.getElementById('delete-confirm-btn');
if (confirmBtn) {
    confirmBtn.addEventListener('click', function(e) {
        if (this.classList.contains('disabled')) {
            e.preventDefault();
            return;
        }
        this.classList.add('disabled');
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Suppression...';
    });
}
</script>

<?php include 'includes/footer.php'; ?>