<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../products.php");
    exit;
}

$id = (int) $_GET['id'];

// Produit
$stmt = $pdo->prepare("SELECT * FROM products WHERE id_product = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produit introuvable.");
}

// Traduction française
$stmt = $pdo->prepare("
SELECT pt.name, pt.description
FROM product_translations pt
INNER JOIN languages l ON pt.id_language = l.id_language
WHERE pt.id_product = ? AND l.code = 'fr'
");
$stmt->execute([$id]);
$translation = $stmt->fetch();

// Catégories
$stmt = $pdo->query("
SELECT c.id_category, ct.name
FROM categories c
INNER JOIN category_translations ct ON c.id_category = ct.id_category
INNER JOIN languages l ON ct.id_language = l.id_language
WHERE l.code = 'fr'
ORDER BY ct.name
");
$categories = $stmt->fetchAll();

// Check if is_featured/is_new_arrival exist to avoid Undefined Index warnings
$hasFeatured = false;
$hasNew = false;
try {
    $cStmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_featured'");
    $hasFeatured = (bool) $cStmt->fetch();
    $cStmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_new_arrival'");
    $hasNew = (bool) $cStmt->fetch();
} catch (Exception $e) {}

include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-pen me-2" style="color:var(--color-primary);"></i>
            Modifier le produit
        </h1>
        <p class="page-subtitle">
            <code style="font-size:.8rem;"><?= htmlspecialchars($product['reference'] ?? '') ?></code>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="manage-variants.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-palette me-1"></i> Variantes & Images
        </a>
        <a href="../../products.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>

<form action="update.php" method="POST">

    <input type="hidden" name="id_product" value="<?= $product['id_product'] ?>">

    <div class="row g-4">

        <!-- Informations générales -->
        <div class="col-12">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="card-section-icon"><i class="fa-solid fa-circle-info"></i></span>
                    <h3>Informations générales</h3>
                </div>
                <div class="form-card-body">
                    <div class="row g-3">

                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-tag me-1 text-muted"></i>Nom du produit <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="name"
                                   value="<?= htmlspecialchars($translation['name'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-barcode me-1 text-muted"></i>SKU / Référence <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="reference"
                                   value="<?= htmlspecialchars($product['reference'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-layer-group me-1 text-muted"></i>Catégorie <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="id_category" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id_category'] ?>"
                                        <?= (int)$category['id_category'] === (int)$product['id_category'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-toggle-on me-1 text-muted"></i>Statut
                            </label>
                            <select class="form-select" name="status">
                                <option value="1" <?= (int)($product['status'] ?? 0) === 1 ? 'selected' : '' ?>>Actif</option>
                                <option value="0" <?= (int)($product['status'] ?? 0) === 0 ? 'selected' : '' ?>>Inactif</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-align-left me-1 text-muted"></i>Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="description" rows="5" required><?= htmlspecialchars($translation['description'] ?? '') ?></textarea>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <?php if ($hasFeatured || $hasNew): ?>
        <!-- Options produit -->
        <div class="col-12 col-lg-6">
            <div class="form-card h-100">
                <div class="form-card-header">
                    <span class="card-section-icon"><i class="fa-solid fa-star"></i></span>
                    <h3>Options produit</h3>
                </div>
                <div class="form-card-body">
                    <?php if ($hasFeatured): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"
                                   <?= !empty($product['is_featured']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="is_featured">
                                <i class="fa-solid fa-star me-1 text-warning"></i> Produit à la une
                            </label>
                            <div class="form-text">Affiché dans la section "À la une"</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($hasNew): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_new_arrival" value="1" id="is_new_arrival"
                                   <?= !empty($product['is_new_arrival']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="is_new_arrival">
                                <i class="fa-solid fa-sparkles me-1 text-success"></i> Nouvelle arrivée
                            </label>
                            <div class="form-text">Affiché dans "Nouveautés"</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lien vers variantes -->
        <div class="col-12 col-lg-6">
            <div class="form-card h-100" style="border:2px dashed var(--border-color);">
                <div class="form-card-body d-flex flex-column align-items-center justify-content-center text-center py-5 gap-3">
                    <div style="width:52px;height:52px;background:var(--color-primary-light);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;color:var(--color-primary);font-size:1.3rem;">
                        <i class="fa-solid fa-palette"></i>
                    </div>
                    <div>
                        <div class="fw-semibold mb-1">Variantes & Images</div>
                        <div class="text-muted small">Gérez les couleurs, tailles, stock et photos</div>
                    </div>
                    <a href="manage-variants.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-arrow-right me-1"></i> Gérer les variantes
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Sticky Footer -->
    <div class="sticky-form-footer mt-4">
        <a href="../../products.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-xmark me-1"></i> Annuler
        </a>
        <button type="submit" class="btn-primary-admin">
            <i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications
        </button>
    </div>

</form>

<?php include '../../includes/footer.php'; ?>