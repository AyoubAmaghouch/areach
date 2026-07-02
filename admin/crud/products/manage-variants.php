<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/app.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    header('Location: ../../products.php');
    exit;
}

if (empty($_SESSION['variant_csrf_token'])) {
    $_SESSION['variant_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken    = $_SESSION['variant_csrf_token'];
$allowedSizes = range(36, 45);

$productStatement = $pdo->prepare(
    'SELECT id_product, reference, status FROM products WHERE id_product = :id_product'
);
$productStatement->execute(['id_product' => $productId]);
$product = $productStatement->fetch();

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

// Get French name
$nameStmt = $pdo->prepare("
    SELECT pt.name FROM product_translations pt
    INNER JOIN languages l ON pt.id_language = l.id_language
    WHERE pt.id_product = :id AND l.code = 'fr'
");
$nameStmt->execute(['id' => $productId]);
$productName = $nameStmt->fetchColumn() ?: ('REF: ' . ($product['reference'] ?? ''));

$variantStatement = $pdo->prepare(
    'SELECT id_variant, color_name, color_code, price, promotion_price,
            promotion_start, promotion_end, stock, discount_percentage, status
     FROM product_variants
     WHERE id_product = :id_product
     ORDER BY id_variant ASC'
);
$variantStatement->execute(['id_product' => $productId]);
$variants = $variantStatement->fetchAll();

$sizeStatement = $pdo->prepare(
    'SELECT size FROM product_variant_sizes
     WHERE id_variant = :id_variant
     ORDER BY CAST(size AS UNSIGNED), size'
);

$imageStatement = $pdo->prepare(
    'SELECT id_image, image, is_primary FROM product_images
     WHERE id_variant = :id_variant
     ORDER BY is_primary DESC, id_image ASC'
);

foreach ($variants as &$variant) {
    $sizeStatement->execute(['id_variant' => $variant['id_variant']]);
    $variant['sizes'] = array_column($sizeStatement->fetchAll(), 'size');

    $imageStatement->execute(['id_variant' => $variant['id_variant']]);
    $variant['images'] = $imageStatement->fetchAll();
}
unset($variant);

$flash = $_SESSION['variant_flash'] ?? null;
unset($_SESSION['variant_flash']);

$showNewVariant = isset($_GET['new']) && $_GET['new'] === '1';

function variantEscape(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function variantCsrfField(string $token): string
{
    return '<input type="hidden" name="csrf_token" value="' . variantEscape($token) . '">';
}

include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-palette me-2" style="color:var(--color-primary);"></i>
            Variantes & Images
        </h1>
        <p class="page-subtitle">
            <?= variantEscape($productName) ?>
            <code class="ms-2" style="font-size:.75rem;"><?= variantEscape($product['reference'] ?? '') ?></code>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?= variantEscape($productId) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-pen me-1"></i> Modifier le produit
        </a>
        <a href="../../products.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>

<!-- Flash Message -->
<?php if (is_array($flash) && isset($flash['message'])): ?>
    <div class="flash-alert <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" data-auto-dismiss>
        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= variantEscape($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Responsive Bootstrap Grid for Variants -->
<div class="row g-4 mb-4">
    <?php if (empty($variants)): ?>
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-palette fa-3x mb-3 d-block opacity-25"></i>
                <p class="mb-0">Aucune variante pour ce produit.</p>
                <a href="manage-variants.php?id=<?= variantEscape($productId) ?>&new=1" class="btn btn-primary mt-3">
                    <i class="fa-solid fa-plus me-1"></i> Ajouter une variante
                </a>
            </div>
        </div>
    <?php else: ?>
    <?php foreach ($variants as $variant):
        $colorCode = variantEscape($variant['color_code'] ?: '#cccccc');

        $inStock   = (int)($variant['stock'] ?? 0) > 0;
        $isActive  = (int)($variant['status'] ?? 0) === 1;

        // Find primary image or use the first one
        $primaryImgSrc = '';
        foreach ($variant['images'] as $img) {
            if ((int)$img['is_primary'] === 1) {
                $primaryImgSrc = adminImagePath('products', $img['image']);
                break;
            }
        }
        if ($primaryImgSrc === '' && !empty($variant['images'])) {
            $primaryImgSrc = adminImagePath('products', $variant['images'][0]['image']);
        }
    ?>
    <div class="col-12 col-md-6 col-xxl-4">
        <div class="variant-card p-4 bg-white d-flex flex-column justify-content-between h-100" 
             style="border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-sm); gap: 24px;">
            
            <!-- Card Header -->
            <div class="d-flex align-items-center justify-content-between pb-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="variant-color-swatch" style="background:<?= $colorCode ?>; width: 16px; height: 16px; border-radius: 50%; display: inline-block;"></span>
                    <h3 class="h6 mb-0 fw-semibold text-truncate" style="max-width: 130px;"><?= variantEscape($variant['color_name']) ?></h3>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                        <?= $isActive ? 'Actif' : 'Inactif' ?>
                    </span>
                    <span class="badge-status <?= $inStock ? 'badge-active' : 'badge-cancelled' ?>">
                        <?= $inStock ? 'En stock' : 'Rupture' ?>
                    </span>
                    <!-- Delete button in Header -->
                    <form action="delete-variant.php" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette variante ?');">
                        <?= variantCsrfField($csrfToken) ?>
                        <input type="hidden" name="id_product" value="<?= variantEscape($productId) ?>">
                        <input type="hidden" name="id_variant" value="<?= variantEscape($variant['id_variant']) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm p-1" style="line-height:1; border:none; background:transparent;" title="Supprimer variante">
                            <i class="fa-solid fa-trash-can text-danger"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Card Body / Edit Form -->
            <form action="update-variant.php" method="post" class="d-flex flex-column gap-3">
                <?= variantCsrfField($csrfToken) ?>
                <input type="hidden" name="id_product" value="<?= variantEscape($productId) ?>">
                <input type="hidden" name="id_variant" value="<?= variantEscape($variant['id_variant']) ?>">

                <!-- General Details -->
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Nom couleur</label>
                        <input type="text" class="form-control form-control-sm" name="color_name" value="<?= variantEscape($variant['color_name']) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Code HEX</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color p-0 border-end-0" name="color_code" value="<?= $colorCode ?>" style="max-width:34px;height:31px;">
                            <input type="text" class="form-control form-control-sm" value="<?= $colorCode ?>" readonly style="font-family:monospace;font-size:0.75rem;">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Prix (€)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="price" value="<?= variantEscape($variant['price']) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Promo (€)</label>
                        <input type="number" step="0.01" class="form-control form-control-sm" name="promotion_price" value="<?= variantEscape($variant['promotion_price']) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Début promo</label>
                        <input type="date" class="form-control form-control-sm" name="promotion_start" value="<?= variantEscape($variant['promotion_start']) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Fin promo</label>
                        <input type="date" class="form-control form-control-sm" name="promotion_end" value="<?= variantEscape($variant['promotion_end']) ?>">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold text-secondary">Stock</label>
                        <input type="number" class="form-control form-control-sm" name="stock" value="<?= variantEscape($variant['stock']) ?>" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold text-secondary">Remise (%)</label>
                        <input type="number" class="form-control form-control-sm" name="discount_percentage" value="<?= variantEscape($variant['discount_percentage'] ?? 0) ?>" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold text-secondary">Statut</label>
                        <select class="form-select form-select-sm" name="status" required>
                            <option value="1" <?= (int)$variant['status'] === 1 ? 'selected' : '' ?>>Actif</option>
                            <option value="0" <?= (int)$variant['status'] === 0 ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>
                </div>

                <!-- Sizes Display & Selection -->
                <div class="py-2 border-top border-bottom">
                    <label class="form-label small fw-semibold text-secondary mb-1">Tailles actives</label>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php if (empty($variant['sizes'])): ?>
                            <span class="text-muted small">Aucune taille</span>
                        <?php else: ?>
                            <?php foreach ($variant['sizes'] as $sz): ?>
                                <span class="badge bg-secondary" style="font-size:0.7rem; font-weight:500; background-color: var(--color-primary-light) !important; color: var(--color-primary) !important;"><?= variantEscape($sz) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Editable Checkboxes -->
                    <label class="form-label small fw-semibold text-secondary mb-1">Modifier les tailles</label>
                    <div class="d-flex flex-wrap gap-2" style="font-size: 0.8rem;">
                        <?php foreach ($allowedSizes as $sz): ?>
                            <div class="form-check form-check-inline me-0">
                                <input class="form-check-input" type="checkbox" name="sizes[]" value="<?= $sz ?>" id="sz-<?= $variant['id_variant'] ?>-<?= $sz ?>" <?= in_array((string)$sz, $variant['sizes'], true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sz-<?= $variant['id_variant'] ?>-<?= $sz ?>"><?= $sz ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100 mt-1">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer
                </button>
            </form>

            <!-- Image preview and gallery section -->
            <div class="border-top pt-3 d-flex flex-column gap-3">
                <label class="form-label small fw-semibold text-secondary mb-0">Images de la variante</label>
                
                <!-- Primary Image Display -->
                <div class="d-flex justify-content-center">
                    <?php if ($primaryImgSrc !== ''): ?>
                        <img src="<?= $primaryImgSrc ?>" alt="Primary image" class="rounded border" style="width: 200px; height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <!-- Bootstrap placeholder -->
                        <div class="d-flex flex-column align-items-center justify-content-center bg-light border rounded text-muted" style="width: 200px; height: 200px;">
                            <i class="fa-solid fa-image fa-3x opacity-25 mb-2"></i>
                            <span class="small">Aucune image</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gallery Thumbnails -->
                <?php if (!empty($variant['images'])): ?>
                    <div class="image-gallery-grid">
                        <?php foreach ($variant['images'] as $image):
                            $thumbSrc  = adminImagePath('products', $image['image']);
                            $isPrimary = (int)$image['is_primary'] === 1;
                        ?>
                            <div class="image-gallery-item<?= $isPrimary ? ' primary' : '' ?>">
                                <?php if ($thumbSrc !== ''): ?>
                                    <img src="<?= $thumbSrc ?>" alt="Image variante">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center bg-light h-100 text-muted" style="font-size:1.5rem;">
                                        <i class="fa-solid fa-image opacity-25"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if ($isPrimary): ?>
                                    <span class="primary-badge">Principale</span>
                                <?php endif; ?>

                                <div class="image-gallery-actions">
                                    <?php if (!$isPrimary): ?>
                                        <form action="upload-image.php" method="post" class="m-0">
                                            <?= variantCsrfField($csrfToken) ?>
                                            <input type="hidden" name="action" value="set_primary">
                                            <input type="hidden" name="id_product" value="<?= variantEscape($productId) ?>">
                                            <input type="hidden" name="id_variant" value="<?= variantEscape($variant['id_variant']) ?>">
                                            <input type="hidden" name="id_image" value="<?= variantEscape($image['id_image']) ?>">
                                            <button type="submit" class="bg-success text-white" title="Principale">
                                                <i class="fa-solid fa-star"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="delete-image.php" method="post" class="m-0" onsubmit="return confirm('Supprimer l\'image ?');">
                                        <?= variantCsrfField($csrfToken) ?>
                                        <input type="hidden" name="id_product" value="<?= variantEscape($productId) ?>">
                                        <input type="hidden" name="id_variant" value="<?= variantEscape($variant['id_variant']) ?>">
                                        <input type="hidden" name="id_image" value="<?= variantEscape($image['id_image']) ?>">
                                        <button type="submit" class="bg-danger text-white" title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>


                <!-- Upload Form -->
                <form action="upload-image.php" method="post" enctype="multipart/form-data" class="bg-light p-3 border rounded">
                    <?= variantCsrfField($csrfToken) ?>
                    <input type="hidden" name="id_product" value="<?= variantEscape($productId) ?>">
                    <input type="hidden" name="id_variant" value="<?= variantEscape($variant['id_variant']) ?>">

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Ajouter photos</label>
                        <input type="file" class="form-control form-control-sm" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple required>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="make_primary" value="1" id="mk-prim-<?= $variant['id_variant'] ?>">
                        <label class="form-check-label small" for="mk-prim-<?= $variant['id_variant'] ?>">Définir 1ère photo principale</label>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Uploader
                    </button>
                </form>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add New Variant (Original Logic) -->
<?php if ($showNewVariant): ?>
    <div class="form-card">
        <div class="form-card-header">
            <span class="card-section-icon"><i class="fa-solid fa-plus"></i></span>
            <h3>Nouvelle variante</h3>
        </div>
        <div class="form-card-body">
            <form action="save-variant.php" method="post">
                <?= variantCsrfField($csrfToken) ?>
                <input type="hidden" name="id_product" value="<?= variantEscape($productId) ?>">

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Nom de la couleur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="color_name" maxlength="100" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Code couleur</label>
                        <input type="color" class="form-control form-control-color" name="color_code" value="#000000" required style="width: 100%; max-width: 60px;">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Prix régulier <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                            <span class="input-group-text">€</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Prix promo</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="promotion_price" min="0" step="0.01">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Début promo</label>
                        <input type="date" class="form-control" name="promotion_start">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Fin promo</label>
                        <input type="date" class="form-control" name="promotion_end">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Stock <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="stock" min="0" step="1" value="0" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Remise (%)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="discount_percentage" min="0" max="100" step="1" value="0" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Statut</label>
                        <select class="form-select" name="status" required>
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Tailles disponibles</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($allowedSizes as $sz): ?>
                                <div class="form-check form-check-inline me-0">
                                    <input class="form-check-input" type="checkbox" name="sizes[]" value="<?= $sz ?>" id="new-sz-<?= $sz ?>">
                                    <label class="form-check-label" for="new-sz-<?= $sz ?>"><?= $sz ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer
                        </button>
                        <a href="manage-variants.php?id=<?= variantEscape($productId) ?>" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="text-center py-4">
        <a href="manage-variants.php?id=<?= variantEscape($productId) ?>&new=1" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Ajouter une nouvelle variante
        </a>
    </div>
<?php endif; ?>

<!-- Inline Hover Style helper for actions overlay -->
<style>
.hover-opacity-100:hover {
    opacity: 1 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prevent double submission on all forms
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                if (btn.disabled) {
                    e.preventDefault();
                    return;
                }
                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>';
                
                // Fallback timeout to re-enable button if something goes wrong
                setTimeout(function() {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }, 10000);
            }
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>

