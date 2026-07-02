<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$sql = "SELECT
            c.id_category,
            c.status,
            c.image,
            ct.name
        FROM categories c
        LEFT JOIN category_translations ct
            ON c.id_category = ct.id_category
        LEFT JOIN languages l
            ON ct.id_language = l.id_language
        WHERE l.code = 'fr'
        ORDER BY c.id_category DESC";

$stmt       = $pdo->query($sql);
$categories = $stmt->fetchAll();

// Handle GET-based flash from delete redirect
$flash = null;
if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $flash = ['type' => 'success', 'message' => 'Catégorie supprimée avec succès.'];
} elseif (isset($_GET['error']) && $_GET['error'] === 'delete_failed') {
    $flash = ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-layer-group me-2" style="color:var(--color-primary);"></i>
            Catégories
        </h1>
        <p class="page-subtitle"><?= count($categories) ?> catégorie<?= count($categories) !== 1 ? 's' : '' ?> au total</p>
    </div>
    <a href="crud/categories/create.php" class="btn-primary-admin">
        <i class="fa-solid fa-plus"></i> Ajouter une catégorie
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
            Liste des catégories
        </div>
        <div class="table-search">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="cat-search" placeholder="Rechercher..." class="form-control">
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th style="width:70px;">Image</th>
                    <th>Nom</th>
                    <th>Statut</th>
                    <th class="text-center" style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody id="cat-tbody">
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                            Aucune catégorie
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category):
                        $catName  = htmlspecialchars($category['name'] ?? '');
                        $isActive = (int)($category['status'] ?? 0) === 1;
                        $imgSrc   = adminImagePath('categories', $category['image']);
                    ?>
                    <tr data-name="<?= strtolower($catName) ?>">

                        <td class="text-muted fw-semibold">#<?= (int)$category['id_category'] ?></td>

                        <td>
                            <?php if ($imgSrc !== ''): ?>
                                <img src="<?= $imgSrc ?>" alt="<?= $catName ?>" class="table-thumb">
                            <?php else: ?>
                                <div class="table-thumb-placeholder">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="fw-semibold"><?= $catName ?></td>

                        <td>
                            <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>

                        <td>
                            <div class="table-actions justify-content-center">
                                <a href="crud/categories/edit.php?id=<?= (int)$category['id_category'] ?>"
                                   class="btn-action edit"
                                   data-bs-toggle="tooltip" title="Modifier">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <button
                                    type="button"
                                    class="btn-action delete"
                                    data-bs-toggle="tooltip" title="Supprimer"
                                    onclick="confirmDelete(<?= (int)$category['id_category'] ?>, '<?= addslashes($catName) ?>')">
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

    <!-- Pagination -->
    <div class="admin-pagination">
        <div class="pagination-info" id="pagination-info">—</div>
        <div class="pagination-controls" id="pagination-controls"></div>
    </div>

</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-delete">
            <div class="modal-header border-0 pb-0">
                <div class="modal-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="modal-body text-center pt-0">
                <h5 class="fw-bold mb-2">Supprimer cette catégorie ?</h5>
                <p class="text-muted mb-0">
                    Vous allez supprimer <strong id="delete-cat-name"></strong>. Cette action est irréversible.
                </p>
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
(function () {
    const perPage = 15;
    let currentPage = 1;
    const tbody   = document.getElementById('cat-tbody');
    const search  = document.getElementById('cat-search');
    const info    = document.getElementById('pagination-info');
    const controls = document.getElementById('pagination-controls');

    function getRows() { return Array.from(tbody.querySelectorAll('tr[data-name]')); }
    function filterRows() {
        const q = (search.value || '').toLowerCase().trim();
        return getRows().filter(function (r) { return !q || r.dataset.name.includes(q); });
    }
    function render() {
        const visible = filterRows();
        const total   = visible.length;
        const pages   = Math.max(1, Math.ceil(total / perPage));
        currentPage   = Math.min(currentPage, pages);
        const start   = (currentPage - 1) * perPage;
        const end     = start + perPage;

        getRows().forEach(function (r) { r.style.display = 'none'; });
        visible.forEach(function (r, i) { r.style.display = (i >= start && i < end) ? '' : 'none'; });

        info.textContent = total > 0
            ? 'Affichage ' + (start + 1) + ' – ' + Math.min(end, total) + ' sur ' + total
            : 'Aucun résultat';

        controls.innerHTML = '';
        if (total === 0) return;
        
        for (let i = 1; i <= pages; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.onclick = (function (p) { return function () { currentPage = p; render(); }; })(i);
            controls.appendChild(btn);
        }
    }

    search.addEventListener('input', function () { currentPage = 1; render(); });
    render();

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    window.confirmDelete = function (id, name) {
        const btn = document.getElementById('delete-confirm-btn');
        if (btn) {
            btn.classList.remove('disabled');
            btn.innerHTML = '<i class="fa-solid fa-trash me-1"></i> Oui, supprimer';
            btn.href = 'crud/categories/delete.php?id=' + id;
        }
        document.getElementById('delete-cat-name').textContent = name;
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
})();
</script>

<?php include 'includes/footer.php'; ?>