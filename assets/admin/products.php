<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login");
    exit;
}

// Restore original SQL to only load existing columns and table joins
$sql = "
SELECT
    p.id_product,
    p.id_category,
    p.reference,
    p.status,
    ct.name AS category_name,
    pt.name AS product_name,
    pi.image AS product_image
FROM products p
LEFT JOIN product_variants pv
    ON pv.id_variant = (
        SELECT pv_choice.id_variant
        FROM product_variants pv_choice
        WHERE pv_choice.id_product = p.id_product
        ORDER BY pv_choice.id_variant ASC
        LIMIT 1
    )
LEFT JOIN product_images pi
    ON pi.id_image = (
        SELECT pi_choice.id_image
        FROM product_images pi_choice
        WHERE pi_choice.id_variant = pv.id_variant
        ORDER BY CASE WHEN pi_choice.is_primary = 1 THEN 0 ELSE 1 END,
                 pi_choice.id_image ASC
        LIMIT 1
    )
LEFT JOIN category_translations ct
    ON p.id_category = ct.id_category
    AND ct.id_language = (
        SELECT id_language FROM languages WHERE code = 'fr' LIMIT 1
    )
LEFT JOIN product_translations pt
    ON p.id_product = pt.id_product
    AND pt.id_language = (
        SELECT id_language FROM languages WHERE code = 'fr' LIMIT 1
    )
ORDER BY p.id_product DESC
";

$stmt     = $pdo->query($sql);
$products = $stmt->fetchAll();

// Flash message
$flash = $_SESSION['product_flash'] ?? null;
unset($_SESSION['product_flash']);

// Handle GET-based flash from delete redirect
if (!$flash && isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $flash = ['type' => 'success', 'message' => 'Produit supprimé avec succès.'];
} elseif (!$flash && isset($_GET['error']) && $_GET['error'] === 'delete_failed') {
    $flash = ['type' => 'error', 'message' => 'Erreur lors de la suppression du produit.'];
} elseif (!$flash && isset($_GET['error']) && $_GET['error'] === 'has_orders') {
    $flash = ['type' => 'warning', 'message' => 'Ce produit ne peut pas être supprimé car il est lié à des commandes existantes.'];
}

include 'includes/header.php';
?>

<!-- Flash Alert -->
<?php if ($flash): ?>
    <div class="flash-alert <?= htmlspecialchars($flash['type'] ?? 'info') ?>" data-auto-dismiss role="alert">
        <i class="fa-solid <?= ($flash['type'] ?? '') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= htmlspecialchars($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-shirt me-2" style="color:var(--color-primary);"></i>
            Produits
        </h1>
        <p class="page-subtitle"><?= count($products) ?> produit<?= count($products) !== 1 ? 's' : '' ?> au total</p>
    </div>
    <a href="crud/products/create" class="btn-primary-admin">
        <i class="fa-solid fa-plus"></i> Ajouter un produit
    </a>
</div>

<!-- Table -->
<div class="admin-table-wrapper">

    <div class="admin-table-header">
        <div class="admin-table-title">
            <i class="fa-solid fa-list" style="color:var(--color-primary);"></i>
            Liste des produits
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <!-- Status Filter -->
            <select id="status-filter" class="form-select form-select-sm" style="width:auto;border-radius:8px;">
                <option value="">Tous les statuts</option>
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
            </select>
            <!-- Search -->
            <div class="table-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="product-search" placeholder="Rechercher un produit..." class="form-control">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" id="products-table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th style="width:60px;">Image</th>
                    <th>Produit</th>
                    <th>Référence</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th class="text-center" style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody id="products-tbody">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                            Aucun produit trouvé
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product):
                        $name     = htmlspecialchars($product['product_name'] ?? '');
                        $ref      = htmlspecialchars($product['reference'] ?? '');
                        $catName  = htmlspecialchars($product['category_name'] ?? '');
                        $isActive = (int)($product['status'] ?? 0) === 1;
                        
                        // Dynamically load image path using the helper function
                        $imgSrc = adminImagePath('products', $product['product_image'], (int)$product['id_category']);
                    ?>
                    <tr data-name="<?= strtolower($name) ?>"
                        data-ref="<?= strtolower($ref) ?>"
                        data-cat="<?= strtolower($catName) ?>"
                        data-status="<?= $isActive ? 'actif' : 'inactif' ?>">

                        <td class="text-muted fw-semibold">#<?= (int)$product['id_product'] ?></td>

                        <td>
                            <?php if ($imgSrc !== ''): ?>
                                <img src="<?= $imgSrc ?>" alt="<?= $name ?>" class="table-thumb">
                            <?php else: ?>
                                <div class="table-thumb-placeholder">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="fw-semibold"><?= $name ?></div>
                        </td>

                        <td>
                            <code class="text-muted" style="font-size:.78rem;"><?= $ref ?></code>
                        </td>

                        <td>
                            <span style="font-size:.82rem;"><?= $catName ?></span>
                        </td>

                        <td>
                            <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $isActive ? 'Actif' : 'Inactif' ?>
                            </span>
                        </td>

                        <td>
                            <a href="crud/products/edit?id=<?= (int)$product['id_product'] ?>"
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
    const perPage  = 15;
    let   currentPage = 1;
    const tbody    = document.getElementById('products-tbody');
    const searchInput  = document.getElementById('product-search');
    const statusFilter = document.getElementById('status-filter');
    const info     = document.getElementById('pagination-info');
    const controls = document.getElementById('pagination-controls');

    function getRows() {
        return Array.from(tbody.querySelectorAll('tr[data-name]'));
    }

    function filterRows() {
        const q      = (searchInput.value || '').toLowerCase().trim();
        const status = (statusFilter.value || '').toLowerCase().trim();
        return getRows().filter(function (row) {
            const nameMatch   = !q || row.dataset.name.includes(q) || row.dataset.ref.includes(q) || row.dataset.cat.includes(q);
            const statusMatch = !status || row.dataset.status === status;
            return nameMatch && statusMatch;
        });
    }

    function render() {
        const visible = filterRows();
        const total   = visible.length;
        const pages   = Math.max(1, Math.ceil(total / perPage));
        currentPage   = Math.min(currentPage, pages);

        const start = (currentPage - 1) * perPage;
        const end   = start + perPage;

        getRows().forEach(function (row) { row.style.display = 'none'; });
        visible.forEach(function (row, i) {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });

        info.textContent = total > 0
            ? 'Affichage ' + (start + 1) + ' – ' + Math.min(end, total) + ' sur ' + total + ' produits'
            : 'Aucun résultat';

        controls.innerHTML = '';
        
        if (total === 0) return;

        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = function () { if (currentPage > 1) { currentPage--; render(); } };
        controls.appendChild(prevBtn);

        const maxPages = Math.min(pages, 5);
        for (let i = 1; i <= maxPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.onclick = (function (p) { return function () { currentPage = p; render(); }; })(i);
            controls.appendChild(btn);
        }

        if (pages > 5) {
            const dots = document.createElement('button');
            dots.className = 'page-btn';
            dots.textContent = '…';
            dots.disabled = true;
            controls.appendChild(dots);

            if (currentPage > 5) {
                const curBtn = document.createElement('button');
                curBtn.className = 'page-btn active';
                curBtn.textContent = currentPage;
                controls.appendChild(curBtn);
            }
        }

        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        nextBtn.disabled = currentPage === pages;
        nextBtn.onclick = function () { if (currentPage < pages) { currentPage++; render(); } };
        controls.appendChild(nextBtn);
    }

    searchInput.addEventListener('input', function () { currentPage = 1; render(); });
    statusFilter.addEventListener('change', function () { currentPage = 1; render(); });

    render();
})();
</script>

<?php include 'includes/footer.php'; ?>
