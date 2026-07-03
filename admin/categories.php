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

// Handle flash from CRUD redirects
$flash = $_SESSION['category_flash'] ?? null;
unset($_SESSION['category_flash']);

if (!$flash && isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $flash = ['type' => 'success', 'message' => 'Catégorie supprimée avec succès.'];
} elseif (!$flash && isset($_GET['error']) && $_GET['error'] === 'delete_failed') {
    $flash = ['type' => 'error', 'message' => 'Erreur lors de la suppression.'];
} elseif (!$flash && isset($_GET['error']) && $_GET['error'] === 'has_products') {
    $flash = ['type' => 'warning', 'message' => 'Cette catégorie ne peut pas être supprimée car elle contient des produits.'];
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
        <table class="table">
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
                            <a href="crud/categories/edit.php?id=<?= (int)$category['id_category'] ?>"
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

    <!-- Pagination -->
    <div class="admin-pagination">
        <div class="pagination-info" id="pagination-info">—</div>
        <div class="pagination-controls" id="pagination-controls"></div>
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
})();
</script>

<?php include 'includes/footer.php'; ?>
