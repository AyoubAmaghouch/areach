<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../orders.php");
    exit;
}

$id = (int) $_GET['id'];

// Modifier le statut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $status = $_POST['status'];
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id_order = ?");
    $stmt->execute([$status, $id]);

    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Statut mis à jour.']);
        exit;
    }
    header("Location: ../../orders.php");
    exit;
}

// Récupérer la commande
$stmt = $pdo->prepare("
    SELECT orders.*, COALESCE(NULLIF(status, ''), 'En attente') AS status
    FROM orders
    WHERE id_order = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die("Commande introuvable.");
}

$statuses = [
    'En attente'     => ['icon' => 'fa-clock',         'color' => 'warning'],
    'Confirmée'      => ['icon' => 'fa-circle-check',   'color' => 'info'],
    'En préparation' => ['icon' => 'fa-box-open',       'color' => 'purple'],
    'Expédiée'       => ['icon' => 'fa-truck',          'color' => 'primary'],
    'Livrée'         => ['icon' => 'fa-circle-check',   'color' => 'success'],
    'Annulée'        => ['icon' => 'fa-xmark',          'color' => 'danger'],
];

include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-pen-to-square me-2" style="color:var(--color-primary);"></i>
            Changer le statut
        </h1>
        <p class="page-subtitle">
            Commande #<?= htmlspecialchars($order['order_number'] ?? '') ?>
            — <?= htmlspecialchars(($order['customer_name'] ?? '') . ' ' . ($order['customer_lastname'] ?? '')) ?>
        </p>
    </div>
    <a href="details.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Retour aux détails
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">

        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-arrow-right-arrow-left"></i></span>
                <h3>Nouveau statut</h3>
            </div>
            <div class="form-card-body">

                <p class="text-muted mb-4">
                    Statut actuel :
                    <strong><?= htmlspecialchars($order['status'] ?? '') ?></strong>
                </p>

                <form method="POST">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-list me-1 text-muted"></i> Sélectionner un statut
                        </label>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($statuses as $label => $config): ?>
                                <label class="d-flex align-items-center gap-3 p-3 rounded border cursor-pointer"
                                       style="cursor:pointer;transition:all .2s;<?= $order['status'] === $label ? 'border-color:var(--color-primary) !important;background:var(--color-primary-light);' : '' ?>"
                                       onmouseover="this.style.borderColor='var(--color-primary)';this.style.background='var(--color-primary-light)'"
                                       onmouseout="this.style.borderColor='';this.style.background=''">
                                    <input type="radio" name="status" value="<?= htmlspecialchars($label) ?>"
                                           class="form-check-input mt-0"
                                           <?= $order['status'] === $label ? 'checked' : '' ?>>
                                    <i class="fa-solid <?= $config['icon'] ?> text-<?= $config['color'] ?>"></i>
                                    <span class="fw-semibold"><?= htmlspecialchars($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn-primary-admin">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                        </button>
                        <a href="../../orders.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Annuler
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>
