<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$sql = "
SELECT
    id_order,
    order_number,
    customer_name,
    customer_lastname,
    telephone,
    total,
    status,
    created_at
FROM orders
ORDER BY id_order DESC
";

$stmt   = $pdo->query($sql);
$orders = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-bag-shopping me-2" style="color:var(--color-primary);"></i>
            Commandes
        </h1>
        <p class="page-subtitle"><?= count($orders) ?> commande<?= count($orders) !== 1 ? 's' : '' ?> au total</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="exportTableCSV()" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-file-csv me-1"></i> Exporter CSV
        </button>
    </div>
</div>

<!-- Table -->
<div class="admin-table-wrapper">

    <div class="admin-table-header">
        <div class="admin-table-title">
            <i class="fa-solid fa-list" style="color:var(--color-primary);"></i>
            Liste des commandes
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <!-- Status Filter -->
            <select id="status-filter" class="form-select form-select-sm" style="width:auto;border-radius:8px;">
                <option value="">Tous les statuts</option>
                <option value="en attente">En attente</option>
                <option value="confirmée">Confirmée</option>
                <option value="en préparation">En préparation</option>
                <option value="expédiée">Expédiée</option>
                <option value="livrée">Livrée</option>
                <option value="annulée">Annulée</option>
            </select>
            <!-- Search -->
            <div class="table-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="order-search" placeholder="Rechercher..." class="form-control">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table" id="orders-table">
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Total</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th class="text-center" style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                            Aucune commande
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order):
                        $status = htmlspecialchars($order['status'] ?? '');
                        $badgeClass = match($status) {
                            'En attente'     => 'badge-pending',
                            'Confirmée'      => 'badge-confirmed',
                            'En préparation' => 'badge-preparing',
                            'Expédiée'       => 'badge-shipped',
                            'Livrée'         => 'badge-delivered',
                            'Annulée'        => 'badge-cancelled',
                            default          => 'badge-inactive',
                        };
                        $client = htmlspecialchars(($order['customer_name'] ?? '') . ' ' . ($order['customer_lastname'] ?? ''));
                        $num    = htmlspecialchars($order['order_number'] ?? '');
                    ?>
                    <tr data-num="<?= strtolower($num) ?>"
                        data-client="<?= strtolower($client) ?>"
                        data-status="<?= strtolower($status) ?>">

                        <td class="fw-semibold"><?= $num ?></td>

                        <td>
                            <div class="fw-semibold"><?= $client ?></div>
                        </td>

                        <td class="text-muted"><?= htmlspecialchars($order['telephone'] ?? '') ?></td>

                        <td class="fw-semibold"><?= number_format((float)($order['total'] ?? 0), 2) ?> €</td>

                        <td>
                            <span class="badge-status <?= $badgeClass ?>"><?= $status ?></span>
                        </td>

                        <td class="text-muted">
                            <?= htmlspecialchars(substr($order['created_at'] ?? '', 0, 10)) ?>
                        </td>

                        <td>
                            <div class="table-actions justify-content-center">
                                <a href="crud/orders/details.php?id=<?= (int)$order['id_order'] ?>"
                                   class="btn-action view"
                                   data-bs-toggle="tooltip" title="Voir les détails">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="crud/orders/update-status.php?id=<?= (int)$order['id_order'] ?>"
                                   class="btn-action edit"
                                   data-bs-toggle="tooltip" title="Changer le statut">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button type="button"
                                        class="btn-action print"
                                        data-bs-toggle="tooltip" title="Imprimer"
                                        onclick="window.open('crud/orders/details.php?id=<?= (int)$order['id_order'] ?>&print=1','_blank')">
                                    <i class="fa-solid fa-print"></i>
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

<script>
(function () {
    const perPage  = 20;
    let   currentPage = 1;
    const tbody    = document.getElementById('orders-tbody');
    const searchInput  = document.getElementById('order-search');
    const statusFilter = document.getElementById('status-filter');
    const info     = document.getElementById('pagination-info');
    const controls = document.getElementById('pagination-controls');

    function getRows() {
        return Array.from(tbody.querySelectorAll('tr[data-num]'));
    }

    function filterRows() {
        const q      = (searchInput.value || '').toLowerCase().trim();
        const status = (statusFilter.value || '').toLowerCase().trim();
        return getRows().filter(function (row) {
            const textMatch   = !q || row.dataset.num.includes(q) || row.dataset.client.includes(q);
            const statusMatch = !status || row.dataset.status === status;
            return textMatch && statusMatch;
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
        visible.forEach(function (r, i) {
            r.style.display = (i >= start && i < end) ? '' : 'none';
        });

        info.textContent = total > 0
            ? 'Affichage ' + (start + 1) + ' – ' + Math.min(end, total) + ' sur ' + total
            : 'Aucun résultat';

        controls.innerHTML = '';
        if (pages <= 1) return;

        for (let i = 1; i <= pages; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.onclick = (function (p) { return function () { currentPage = p; render(); }; })(i);
            controls.appendChild(btn);
        }
    }

    searchInput.addEventListener('input', function () { currentPage = 1; render(); });
    statusFilter.addEventListener('change', function () { currentPage = 1; render(); });
    render();

    // CSV Export
    window.exportTableCSV = function () {
        const rows = getRows().filter(function (r) { return r.style.display !== 'none'; });
        const headers = ['N° Commande', 'Client', 'Téléphone', 'Total', 'Statut', 'Date'];
        let csv = headers.join(';') + '\n';
        rows.forEach(function (row) {
            const cells = Array.from(row.querySelectorAll('td')).slice(0, 6);
            csv += cells.map(function (c) {
                return '"' + (c.textContent || '').trim().replace(/"/g, '""') + '"';
            }).join(';') + '\n';
        });
        const a = document.createElement('a');
        a.href = 'data:text/csv;charset=utf-8,\uFEFF' + encodeURIComponent(csv);
        a.download = 'commandes.csv';
        a.click();
    };
})();
</script>

<?php include 'includes/footer.php'; ?>