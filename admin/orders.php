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
    COALESCE(NULLIF(status, ''), 'En attente') AS status,
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
        <table class="table" id="orders-table">
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
                            <select class="form-select form-select-sm order-status-select"
                                    data-order-id="<?= (int)$order['id_order'] ?>"
                                    style="width:auto;min-width:120px;border-radius:6px;">
                                <option value="En attente" <?= $status === 'En attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="Confirmée" <?= $status === 'Confirmée' ? 'selected' : '' ?>>Confirmée</option>
                                <option value="En préparation" <?= $status === 'En préparation' ? 'selected' : '' ?>>En préparation</option>
                                <option value="Expédiée" <?= $status === 'Expédiée' ? 'selected' : '' ?>>Expédiée</option>
                                <option value="Livrée" <?= $status === 'Livrée' ? 'selected' : '' ?>>Livrée</option>
                                <option value="Annulée" <?= $status === 'Annulée' ? 'selected' : '' ?>>Annulée</option>
                            </select>
                        </td>

                        <td class="text-muted">
                            <?= htmlspecialchars(substr($order['created_at'] ?? '', 0, 10)) ?>
                        </td>

                        <td>
                            <a href="crud/orders/details.php?id=<?= (int)$order['id_order'] ?>"
                               class="btn btn-sm btn-action view me-1"
                               data-bs-toggle="tooltip" title="Voir les détails">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="crud/orders/update-status.php?id=<?= (int)$order['id_order'] ?>"
                               class="btn btn-sm btn-action edit me-1"
                               data-bs-toggle="tooltip" title="Changer le statut">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-action print"
                                    data-bs-toggle="tooltip" title="Imprimer"
                                    onclick="window.open('crud/orders/details.php?id=<?= (int)$order['id_order'] ?>&print=1','_blank')">
                                <i class="fa-solid fa-print"></i>
                            </button>
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

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
    <div id="orderToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fa-solid fa-circle-check text-success me-2"></i>
            <strong class="me-auto" id="toastTitle">Succès</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fermer"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
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
    const toastEl  = document.getElementById('orderToast');

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

    // Inline status update
    document.addEventListener('DOMContentLoaded', function () {
        var toast = new bootstrap.Toast(toastEl, { delay: 3000 });

        tbody.addEventListener('change', function (e) {
            var select = e.target.closest('.order-status-select');
            if (!select) return;

            var orderId = select.getAttribute('data-order-id');
            var newStatus = select.value;
            var previousStatus = select.dataset.previous;

            if (!previousStatus || previousStatus === newStatus) return;

            var row = select.closest('tr');

            fetch('crud/orders/update-status.php?id=' + orderId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'status=' + encodeURIComponent(newStatus)
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    showToast('success', '\u2713 Statut mis \u00e0 jour.');
                    row.dataset.status = newStatus.toLowerCase();
                    delete select.dataset.previous;
                } else {
                    select.value = previousStatus;
                    showToast('error', data.message || 'Erreur lors de la mise \u00e0 jour.');
                }
            })
            .catch(function () {
                select.value = previousStatus;
                showToast('error', 'Erreur lors de la mise \u00e0 jour.');
            });
        });

        tbody.addEventListener('focusin', function (e) {
            var select = e.target.closest('.order-status-select');
            if (select) {
                select.dataset.previous = select.value;
            }
        });
    });

    function showToast(type, message) {
        var icon = toastEl.querySelector('.toast-header i');
        var title = document.getElementById('toastTitle');
        var msg = document.getElementById('toastMessage');
        if (type === 'success') {
            icon.className = 'fa-solid fa-circle-check text-success me-2';
            title.textContent = 'Succ\u00e8s';
        } else {
            icon.className = 'fa-solid fa-circle-exclamation text-danger me-2';
            title.textContent = 'Erreur';
        }
        msg.textContent = message;
        var toast = bootstrap.Toast.getInstance(toastEl) || new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }
})();
</script>

<?php include 'includes/footer.php'; ?>
