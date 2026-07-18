<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../products");
    exit;
}

$id = (int) $_GET['id'];

if (empty($_SESSION['variant_csrf_token'])) {
    $_SESSION['variant_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['variant_csrf_token'];
$allowedSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

function productEditEscape(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function productEditCsrfField(string $token): string
{
    return '<input type="hidden" name="csrf_token" value="' . productEditEscape($token) . '">';
}

// Produit
$stmt = $pdo->prepare("SELECT * FROM products WHERE id_product = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produit introuvable.");
}

// Traduction francaise
$stmt = $pdo->prepare("
SELECT pt.name, pt.description
FROM product_translations pt
INNER JOIN languages l ON pt.id_language = l.id_language
WHERE pt.id_product = ? AND l.code = 'fr'
");
$stmt->execute([$id]);
$translation = $stmt->fetch();

// Categories
$stmt = $pdo->query("
SELECT c.id_category, ct.name
FROM categories c
INNER JOIN category_translations ct ON c.id_category = ct.id_category
INNER JOIN languages l ON ct.id_language = l.id_language
WHERE l.code = 'fr'
ORDER BY ct.name
");
$categories = $stmt->fetchAll();

$variantStatement = $pdo->prepare(
    'SELECT id_variant
     FROM product_variants
     WHERE id_product = :id_product
     ORDER BY id_variant ASC'
);
$variantStatement->execute(['id_product' => $id]);
$variants = $variantStatement->fetchAll();
$variantIds = array_map(static fn (array $variant): int => (int) $variant['id_variant'], $variants);
$uploadVariantId = $variantIds[0] ?? 0;

$selectedSizes = [];
if ($variantIds !== []) {
    $sizePlaceholders = implode(',', array_fill(0, count($variantIds), '?'));
    $sizeStatement = $pdo->prepare(
        'SELECT DISTINCT size
         FROM product_variant_sizes
         WHERE id_variant IN (' . $sizePlaceholders . ')'
    );
    $sizeStatement->execute($variantIds);
    $selectedSizes = array_column($sizeStatement->fetchAll(), 'size');
}

$productImages = [];
if ($variantIds !== []) {
    $imagePlaceholders = implode(',', array_fill(0, count($variantIds), '?'));
    $imageStatement = $pdo->prepare(
        'SELECT pi.id_image, pi.id_variant, pi.image, pi.is_primary
         FROM product_images pi
         WHERE pi.id_variant IN (' . $imagePlaceholders . ')
         ORDER BY pi.is_primary DESC, pi.id_image ASC'
    );
    $imageStatement->execute($variantIds);
    $productImages = $imageStatement->fetchAll();
}

$primaryImage = $productImages[0] ?? null;
foreach ($productImages as $image) {
    if ((int) $image['is_primary'] === 1) {
        $primaryImage = $image;
        break;
    }
}

$flash = $_SESSION['variant_flash'] ?? $_SESSION['product_flash'] ?? null;
unset($_SESSION['variant_flash'], $_SESSION['product_flash']);

include '../../includes/header.php';

$primaryImageSrc = $primaryImage
    ? adminImagePath('products', $primaryImage['image'], (int) $product['id_category'])
    : '';
?>

<?php if (is_array($flash) && isset($flash['message'])): ?>
    <div class="flash-alert <?= ($flash['type'] ?? 'success') === 'success' ? 'success' : 'error' ?>" data-auto-dismiss>
        <i class="fa-solid <?= ($flash['type'] ?? 'success') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= productEditEscape($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-pen me-2" style="color:var(--color-primary);"></i>
            Modifier le produit
        </h1>
        <p class="page-subtitle">
            <code style="font-size:.8rem;"><?= productEditEscape($product['reference'] ?? '') ?></code>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="../../products" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Retour
        </a>
    </div>
</div>

<form action="update" method="POST">
    <input type="hidden" name="id_product" value="<?= (int) $product['id_product'] ?>">

    <div class="row g-4">
        <!-- Informations generales -->
        <div class="col-12">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="card-section-icon"><i class="fa-solid fa-circle-info"></i></span>
                    <h3>Informations generales</h3>
                </div>
                <div class="form-card-body">
                    <div class="row g-3">

                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-tag me-1 text-muted"></i>Nom du produit <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="name"
                                   value="<?= productEditEscape($translation['name'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-barcode me-1 text-muted"></i>SKU / Reference <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="reference"
                                   value="<?= productEditEscape($product['reference'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-layer-group me-1 text-muted"></i>Categorie <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="id_category" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id_category'] ?>"
                                        <?= (int)$category['id_category'] === (int)$product['id_category'] ? 'selected' : '' ?>>
                                        <?= productEditEscape($category['name'] ?? '') ?>
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
                            <textarea class="form-control" name="description" rows="5" required><?= productEditEscape($translation['description'] ?? '') ?></textarea>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Sizes -->
        <div class="col-12">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="card-section-icon"><i class="fa-solid fa-ruler"></i></span>
                    <h3>Sizes</h3>
                </div>
                <div class="form-card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($allowedSizes as $size): ?>
                            <div class="form-check form-check-inline me-0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="sizes[]"
                                    value="<?= productEditEscape($size) ?>"
                                    id="size-<?= productEditEscape($size) ?>"
                                    <?= in_array($size, $selectedSizes, true) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label fw-semibold" for="size-<?= productEditEscape($size) ?>">
                                    <?= productEditEscape($size) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Footer -->
    <div class="sticky-form-footer mt-4">
        <a href="../../products" class="btn btn-outline-secondary">
            <i class="fa-solid fa-xmark me-1"></i> Annuler
        </a>
        <button type="submit" class="btn-primary-admin">
            <i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications
        </button>
    </div>
</form>

<!-- Images -->
<div class="form-card mt-4">
    <div class="form-card-header">
        <span class="card-section-icon"><i class="fa-solid fa-images"></i></span>
        <h3>Images</h3>
    </div>
    <div class="form-card-body">
        <div class="row g-4">
            <div class="col-12 col-lg-3">
                <?php if ($primaryImageSrc !== ''): ?>
                    <img src="<?= productEditEscape($primaryImageSrc) ?>" alt="Image principale" class="rounded border w-100" style="aspect-ratio:1/1; object-fit:cover;">
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center bg-light border rounded text-muted" style="aspect-ratio:1/1;">
                        <i class="fa-solid fa-image fa-3x opacity-25 mb-2"></i>
                        <span class="small">Aucune image</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12 col-lg-9">
                <?php if (!empty($productImages)): ?>
                    <div class="image-gallery-grid">
                        <?php foreach ($productImages as $image):
                            $thumbSrc = adminImagePath('products', $image['image'], (int) $product['id_category']);
                            $isPrimary = (int) $image['is_primary'] === 1;
                        ?>
                            <div class="image-gallery-item<?= $isPrimary ? ' primary' : '' ?>">
                                <?php if ($thumbSrc !== ''): ?>
                                    <img src="<?= productEditEscape($thumbSrc) ?>" alt="Image produit">
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
                                        <form action="upload-image" method="post" class="m-0">
                                            <?= productEditCsrfField($csrfToken) ?>
                                            <input type="hidden" name="action" value="set_primary">
                                            <input type="hidden" name="id_product" value="<?= (int) $id ?>">
                                            <input type="hidden" name="id_variant" value="<?= (int) $image['id_variant'] ?>">
                                            <input type="hidden" name="id_image" value="<?= (int) $image['id_image'] ?>">
                                            <button type="submit" class="bg-success text-white" title="Principale">
                                                <i class="fa-solid fa-star"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="delete-image" method="post" class="m-0" onsubmit="return confirm('Supprimer l\'image ?');">
                                        <?= productEditCsrfField($csrfToken) ?>
                                        <input type="hidden" name="id_product" value="<?= (int) $id ?>">
                                        <input type="hidden" name="id_variant" value="<?= (int) $image['id_variant'] ?>">
                                        <input type="hidden" name="id_image" value="<?= (int) $image['id_image'] ?>">
                                        <button type="submit" class="bg-danger text-white" title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted small mb-3">Aucune image dans la galerie.</div>
                <?php endif; ?>

                <?php if ($uploadVariantId > 0): ?>
                    <form action="upload-image" method="post" enctype="multipart/form-data" class="bg-light p-3 border rounded mt-3">
                        <?= productEditCsrfField($csrfToken) ?>
                        <input type="hidden" name="id_product" value="<?= (int) $id ?>">
                        <input type="hidden" name="id_variant" value="<?= (int) $uploadVariantId ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-lg-7">
                                <label class="form-label small fw-semibold text-secondary">Ajouter photos</label>
                                <input type="file" class="form-control form-control-sm" name="images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple required>
                            </div>
                            <div class="col-12 col-lg-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="make_primary" value="1" id="make-primary-upload">
                                    <label class="form-check-label small" for="make-primary-upload">1ere principale</label>
                                </div>
                            </div>
                            <div class="col-12 col-lg-2">
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Uploader
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        Aucun support image disponible pour ce produit.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Danger Zone -->
<div class="border border-danger rounded p-4 mt-5">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="fa-solid fa-triangle-exclamation text-danger"></i>
        <h5 class="mb-0 text-danger fw-bold">Zone dangereuse</h5>
    </div>
    <p class="text-muted small mb-3">Cette action est irréversible.</p>
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
        <i class="fa-solid fa-trash me-1"></i> Supprimer
    </button>
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
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" data-url="delete?id=<?= $id ?>">
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
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    var confirmBtn = document.getElementById('confirmDeleteBtn');
    var toastEl = document.getElementById('deleteToast');
    var toast = new bootstrap.Toast(toastEl, { delay: 4000 });

    confirmBtn.addEventListener('click', function() {
        var deleteUrl = this.getAttribute('data-url');
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
                    window.location.href = '../../products';
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

<?php include '../../includes/footer.php'; ?>
