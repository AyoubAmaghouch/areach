<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login");
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editNotification = null;

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id_notification = ?");
    $stmt->execute([$editId]);
    $editNotification = $stmt->fetch();
}

$notifications = $pdo->query("SELECT * FROM notifications ORDER BY id_notification DESC")->fetchAll();
$flash = $_SESSION['notification_flash'] ?? null;
unset($_SESSION['notification_flash']);

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-bell me-2" style="color:var(--color-primary);"></i>
            Notifications
        </h1>
        <p class="page-subtitle"><?= count($notifications) ?> notification<?= count($notifications) !== 1 ? 's' : '' ?></p>
    </div>
</div>

<?php if ($flash): ?>
    <div class="flash-alert <?= htmlspecialchars($flash['type'] ?? 'info') ?>" data-auto-dismiss role="alert">
        <i class="fa-solid <?= ($flash['type'] ?? '') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= htmlspecialchars($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-pen"></i></span>
                <h3><?= $editNotification ? 'Modifier' : 'Ajouter' ?></h3>
            </div>
            <div class="form-card-body">
                <form action="crud/notifications/read" method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id_notification" value="<?= (int) ($editNotification['id_notification'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($editNotification['title'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message</label>
                        <textarea class="form-control" name="message" rows="4"><?= htmlspecialchars($editNotification['message'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type</label>
                        <input type="text" class="form-control" name="type" value="<?= htmlspecialchars($editNotification['type'] ?? 'info') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Statut</label>
                        <select class="form-select" name="is_read">
                            <option value="0" <?= (int) ($editNotification['is_read'] ?? 0) === 0 ? 'selected' : '' ?>>Non lue</option>
                            <option value="1" <?= (int) ($editNotification['is_read'] ?? 0) === 1 ? 'selected' : '' ?>>Lue</option>
                        </select>
                    </div>
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn-primary-admin">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                        </button>
                        <?php if ($editNotification): ?>
                            <a href="notifications" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-xmark me-1"></i> Annuler
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($editNotification): ?>
                <!-- Danger Zone -->
                <hr class="my-4">
                <div class="border border-danger rounded p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                        <h5 class="mb-0 text-danger fw-bold">Zone dangereuse</h5>
                    </div>
                    <p class="text-muted small mb-3">Cette action est irréversible.</p>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                        <i class="fa-solid fa-trash me-1"></i> Supprimer
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="admin-table-wrapper">
            <div class="admin-table-header">
                <div class="admin-table-title">
                    <i class="fa-solid fa-list" style="color:var(--color-primary);"></i>
                    Liste des notifications
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:50px;">ID</th>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-center" style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifications)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                    Aucune notification
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <?php $isRead = (int) ($notification['is_read'] ?? 0) === 1; ?>
                                <tr>
                                    <td class="text-muted fw-semibold">#<?= (int) $notification['id_notification'] ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($notification['title'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($notification['type'] ?? '') ?></td>
                                    <td>
                                        <span class="badge-status <?= $isRead ? 'badge-active' : 'badge-pending' ?>">
                                            <?= $isRead ? 'Lue' : 'Non lue' ?>
                                        </span>
                                    </td>
                                    <td class="text-muted"><?= htmlspecialchars(substr($notification['created_at'] ?? '', 0, 10)) ?></td>
                                    <td>
                                        <a href="notifications?edit=<?= (int) $notification['id_notification'] ?>" class="btn btn-sm btn-action edit me-1" data-bs-toggle="tooltip" title="Modifier">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="crud/notifications/read?id=<?= (int) $notification['id_notification'] ?>" class="btn btn-sm btn-action view me-1" data-bs-toggle="tooltip" title="Marquer lue">
                                            <i class="fa-solid fa-check"></i>
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
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
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
    <?php if ($editNotification): ?>
    var deleteUrl = 'crud/notifications/delete.php?id=<?= (int) $editNotification['id_notification'] ?>';
    <?php else: ?>
    var deleteUrl = null;
    <?php endif; ?>

    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    var confirmBtn = document.getElementById('confirmDeleteBtn');
    var toastEl = document.getElementById('deleteToast');
    var toast = new bootstrap.Toast(toastEl, { delay: 4000 });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
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
                        window.location.href = 'notifications';
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
    }

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

<?php include 'includes/footer.php'; ?>
