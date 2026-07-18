<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$campaign = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id_campaign = ?");
    $stmt->execute([$id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        $_SESSION['campaign_flash'] = ['type' => 'error', 'message' => 'Campagne introuvable.'];
        header("Location: ../../campaigns");
        exit;
    }
}

include '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-bullhorn me-2" style="color:var(--color-primary);"></i>
            <?= $campaign ? 'Modifier la campagne' : 'Ajouter une campagne' ?>
        </h1>
        <p class="page-subtitle"><?= htmlspecialchars($campaign['subject'] ?? 'Nouvelle campagne') ?></p>
    </div>
    <a href="../../campaigns" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-pen"></i></span>
                <h3>Details de la campagne</h3>
            </div>
            <div class="form-card-body">
                <form action="send" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_campaign" value="<?= (int) ($campaign['id_campaign'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Sujet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" value="<?= htmlspecialchars($campaign['subject'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Contenu</label>
                            <textarea class="form-control" name="content" rows="5"><?= htmlspecialchars($campaign['content'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Image</label>
                            <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Statut</label>
                            <select class="form-select" name="status">
                                <option value="1" <?= (int) ($campaign['status'] ?? 1) === 1 ? 'selected' : '' ?>>Actif</option>
                                <option value="0" <?= (int) ($campaign['status'] ?? 1) === 0 ? 'selected' : '' ?>>Inactif</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Texte du bouton</label>
                            <input type="text" class="form-control" name="button_text" value="<?= htmlspecialchars($campaign['button_text'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Lien du bouton</label>
                            <input type="text" class="form-control" name="button_link" value="<?= htmlspecialchars($campaign['button_link'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn-primary-admin">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                        </button>
                        <a href="../../campaigns" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($campaign): ?>
<!-- Danger Zone -->
<div class="row justify-content-center mt-5">
    <div class="col-12 col-xl-8">
        <div class="border border-danger rounded p-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                <h5 class="mb-0 text-danger fw-bold">Zone dangereuse</h5>
            </div>
            <p class="text-muted small mb-3">Cette action est irréversible.</p>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                <i class="fa-solid fa-trash me-1"></i> Supprimer
            </button>
        </div>
    </div>
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
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" data-url="delete?id=<?= (int) $campaign['id_campaign'] ?>">
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
                    window.location.href = '../../campaigns';
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
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
