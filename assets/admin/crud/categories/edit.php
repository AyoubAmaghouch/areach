<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../categories");
    exit;
}

$id = (int) $_GET['id'];

$sql = "SELECT *
        FROM categories
        WHERE id_category = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    die("Catégorie introuvable.");
}

$sql = "SELECT ct.name
        FROM category_translations ct
        INNER JOIN languages l
        ON ct.id_language = l.id_language
        WHERE ct.id_category = ?
        AND l.code='fr'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$translation = $stmt->fetch();

include '../../includes/header.php';
$imgSrc = adminImagePath('categories', $category['image']);
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-layer-group me-2" style="color:var(--color-primary);"></i>
            Modifier la catégorie
        </h1>
        <p class="page-subtitle"><?= htmlspecialchars($translation['name'] ?? '') ?></p>
    </div>
    <a href="../../categories" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">

        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-pen"></i></span>
                <h3>Détails de la catégorie</h3>
            </div>
            <div class="form-card-body">
                <form action="update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $category['id_category'] ?>">

                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">Nom de la catégorie <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($translation['name'] ?? '') ?>"
                            required>
                    </div>

                    <div class="mb-4">
                        <?php if ($imgSrc !== ''): ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Image actuelle</label>
                                <img src="<?= $imgSrc ?>" alt="Preview" style="max-height:120px; border-radius:8px; border:1px solid var(--border-color);">
                            </div>
                        <?php endif; ?>

                        <label for="image" class="form-label fw-semibold">Nouvelle image (optionnelle)</label>
                        <input
                            type="file"
                            class="form-control"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp">
                        <div class="form-text">Laissez vide pour conserver l'image actuelle.</div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn-primary-admin">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                        </button>
                        <a href="../../categories" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Annuler
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<!-- Danger Zone -->
<div class="border border-danger rounded p-4 mt-5">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="fa-solid fa-triangle-exclamation text-danger"></i>
        <h5 class="mb-0 text-danger fw-bold">Zone dangereuse</h5>
    </div>
    <p class="text-muted small mb-3">Cette action est irréversible.</p>
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
        <i class="fa-solid fa-trash me-1"></i> Supprimer
    </button>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Êtes-vous sûr de vouloir supprimer définitivement cet élément ? Cette action ne peut pas être annulée.</p>
            </div>
            <div class="modal-footer border-0 justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" data-url="delete?id=<?= $id ?>">
                    <i class="fa-solid fa-trash me-1"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
    <div id="deleteToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fa-solid fa-circle-check text-success me-2"></i>
            <strong class="me-auto" id="toastTitle">Succès</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fermer"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    var confirmBtn = document.getElementById('confirmDeleteBtn');
    var toastEl = document.getElementById('deleteToast');
    var toast = new bootstrap.Toast(toastEl, { delay: 4000 });

    confirmBtn.addEventListener('click', function() {
        var deleteUrl = this.getAttribute('data-url');
        if (!deleteUrl) return;

        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Suppression...';

        fetch(deleteUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            deleteModal.hide();
            if (data.success) {
                showToast('success', data.message);
                setTimeout(function() {
                    window.location.href = '../../categories';
                }, 2000);
            } else {
                showToast('error', data.message);
            }
        })
        .catch(function() {
            deleteModal.hide();
            showToast('error', 'Erreur lors de la suppression.');
        })
        .finally(function() {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa-solid fa-trash me-1"></i> Supprimer';
        });
    });

    document.getElementById('deleteConfirmModal').addEventListener('hidden.bs.modal', function() {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fa-solid fa-trash me-1"></i> Supprimer';
    });

    function showToast(type, message) {
        var icon = toastEl.querySelector('.toast-header i');
        var title = document.getElementById('toastTitle');
        var msg = document.getElementById('toastMessage');
        if (type === 'success') {
            icon.className = 'fa-solid fa-circle-check text-success me-2';
            title.textContent = 'Succès';
        } else {
            icon.className = 'fa-solid fa-circle-exclamation text-danger me-2';
            title.textContent = 'Erreur';
        }
        msg.textContent = message;
        toast.show();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
