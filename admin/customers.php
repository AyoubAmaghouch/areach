<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("
SELECT
    c.id_customer,
    c.nom,
    c.prenom,
    c.email,
    c.telephone,
    c.newsletter,
    c.status,
    c.created_at,
    COUNT(o.id_order) AS order_count,
    COALESCE(SUM(o.total), 0) AS total_spent
FROM customers c
LEFT JOIN orders o ON o.id_customer = c.id_customer
GROUP BY c.id_customer, c.nom, c.prenom, c.email, c.telephone, c.newsletter, c.status, c.created_at
ORDER BY c.id_customer DESC
");


$customers = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-users me-2" style="color:var(--color-primary);"></i>
            Clients
        </h1>
        <p class="page-subtitle"><?= count($customers) ?> client<?= count($customers) !== 1 ? 's' : '' ?> inscrits</p>
    </div>
</div>

<!-- Table -->
<div class="admin-table-wrapper">

    <div class="admin-table-header">
        <div class="admin-table-title">
            <i class="fa-solid fa-list" style="color:var(--color-primary);"></i>
            Liste des clients
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <select id="status-filter" class="form-select form-select-sm" style="width:auto;border-radius:8px;">
                <option value="">Tous</option>
                <option value="actif">Actifs</option>
                <option value="bloqué">Bloqués</option>
            </select>
            <div class="table-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="cust-search" placeholder="Nom, email..." class="form-control">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th class="text-center">Newsletter</th>
                    <th>Statut</th>
                    <th>Inscription</th>
                    <th class="text-center" style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody id="cust-tbody">
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                            Aucun client
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer):
                        $nom      = htmlspecialchars($customer['nom'] ?? '');
                        $prenom   = htmlspecialchars($customer['prenom'] ?? '');
                        $email    = htmlspecialchars($customer['email'] ?? '');
                        $tel      = htmlspecialchars($customer['telephone'] ?? '');
                        $isActive = (int)($customer['status'] ?? 0) === 1;
                        $hasNewsletter = (int)($customer['newsletter'] ?? 0) === 1;
                        $initials = strtoupper(substr($customer['nom'] ?? 'C', 0, 1) . substr($customer['prenom'] ?? '', 0, 1));
                    ?>
                    <tr data-name="<?= strtolower($nom . ' ' . $prenom) ?>"
                        data-email="<?= strtolower($email) ?>"
                        data-status="<?= $isActive ? 'actif' : 'bloqué' ?>">

                        <td class="text-muted fw-semibold">#<?= (int)$customer['id_customer'] ?></td>

                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--color-primary),var(--color-purple));display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700;flex-shrink:0;">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= $nom . ' ' . $prenom ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="text-muted" style="font-size:.82rem;"><?= $email ?></td>

                        <td class="text-muted"><?= $tel ?></td>

                        <td class="text-center">
                            <?php if ($hasNewsletter): ?>
                                <span class="badge-status badge-active" style="font-size:.68rem;">
                                    <i class="fa-solid fa-check"></i> Oui
                                </span>
                            <?php else: ?>
                                <span class="badge-status badge-inactive" style="font-size:.68rem;">Non</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-cancelled' ?>">
                                <?= $isActive ? 'Actif' : 'Bloqué' ?>
                            </span>
                        </td>

                        <td class="text-muted" style="font-size:.8rem;">
                            <?= htmlspecialchars(substr($customer['created_at'] ?? '', 0, 10)) ?>
                        </td>

                        <td>
                            <a href="crud/customers/details.php?id=<?= (int)$customer['id_customer'] ?>"
                               class="btn btn-sm btn-action view me-1"
                               data-bs-toggle="tooltip" title="Voir le profil">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="crud/customers/toggle-status.php?id=<?= (int)$customer['id_customer'] ?>"
                               class="btn btn-sm btn-action toggle"
                               data-bs-toggle="tooltip" title="<?= $isActive ? 'Bloquer' : 'Activer' ?>">
                                <i class="fa-solid <?= $isActive ? 'fa-ban' : 'fa-check' ?>"></i>
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
    const perPage = 20;
    let currentPage = 1;
    const tbody   = document.getElementById('cust-tbody');
    const search  = document.getElementById('cust-search');
    const filter  = document.getElementById('status-filter');
    const info    = document.getElementById('pagination-info');
    const controls = document.getElementById('pagination-controls');

    function getRows() { return Array.from(tbody.querySelectorAll('tr[data-name]')); }
    function filterRows() {
        const q  = (search.value || '').toLowerCase().trim();
        const st = (filter.value || '').toLowerCase().trim();
        return getRows().filter(function (r) {
            const nm = !q || r.dataset.name.includes(q) || r.dataset.email.includes(q);
            const sm = !st || r.dataset.status === st;
            return nm && sm;
        });
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
        for (let i = 1; i <= pages; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.onclick = (function (p) { return function () { currentPage = p; render(); }; })(i);
            controls.appendChild(btn);
        }
    }
    search.addEventListener('input', function () { currentPage = 1; render(); });
    filter.addEventListener('change', function () { currentPage = 1; render(); });
    render();
})();
</script>

<?php include 'includes/footer.php'; ?>
