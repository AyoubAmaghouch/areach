<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

$sql = "
SELECT
    c.id_category,
    ct.name
FROM categories c
INNER JOIN category_translations ct
    ON c.id_category = ct.id_category
INNER JOIN languages l
    ON ct.id_language = l.id_language
WHERE l.code = 'fr'
AND c.status = 1
ORDER BY ct.name ASC
";

$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll();

// Check if columns exist in the database
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

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h4 mb-1">Ajouter un produit</h2>
                            <p class="text-muted mb-0">Créez un produit complet avec prix, stock, images et options.</p>
                        </div>
                        <a href="../../products.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left me-1"></i> Retour
                        </a>
                    </div>

                    <form id="product-create-form" action="store.php" method="POST" enctype="multipart/form-data" class="row g-4" novalidate>
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <div id="form-alert" class="alert alert-danger d-none" role="alert"></div>
                                    <h3 class="h6 mb-3">Informations générales</h3>
                                    <div class="row g-3">
                                        <div class="col-12 col-lg-6">
                                            <label for="name" class="form-label fw-semibold">Nom du produit <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <label for="reference" class="form-label fw-semibold">SKU / Référence <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="reference" name="reference" required>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <label for="id_category" class="form-label fw-semibold">Catégorie <span class="text-danger">*</span></label>
                                            <select class="form-select" id="id_category" name="id_category" required>
                                                <option value="">-- Choisir une catégorie --</option>
                                                <?php foreach ($categories as $category) : ?>
                                                    <option value="<?= (int) $category['id_category']; ?>">
                                                        <?= htmlspecialchars((string) $category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <label for="status" class="form-label fw-semibold">Statut</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="1">Actif</option>
                                                <option value="0">Inactif</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="description" class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <h3 class="h6 mb-3">Prix</h3>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="price" class="form-label fw-semibold">Prix régulier <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="promotion_price" class="form-label fw-semibold">Prix promo (optionnel)</label>
                                            <input type="number" step="0.01" min="0" class="form-control" id="promotion_price" name="promotion_price">
                                            <div class="form-text">Un pourcentage de réduction sera calculé automatiquement si un prix promo est renseigné.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h3 class="h6 mb-3">Variantes</h3>
                                    <p class="text-muted small mb-3">Ajoutez des couleurs, sélectionnez les tailles disponibles et définissez le stock par variante.</p>
                                    <div id="variant-form-alert" class="alert alert-danger d-none" role="alert"></div>
                                    <div id="variant-builder"></div>
                                    <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="add-variant-group">
                                        <i class="fa-solid fa-plus me-1"></i> Ajouter une couleur
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h3 class="h6 mb-3">Images</h3>
                                    <div class="form-text mb-3">Chaque couleur peut avoir sa propre image principale et sa galerie (jusqu'à 5 images).</div>
                                    <div id="variant-images-container"></div>
                                </div>
                            </div>
                        </div>

                        <?php if ($hasFeatured || $hasNew): ?>
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h3 class="h6 mb-3">Options produit</h3>
                                    <?php if ($hasFeatured): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" value="1" id="is_featured" name="is_featured">
                                        <label class="form-check-labelfw-semibold" for="is_featured">Produit à la une</label>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($hasNew): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="is_new_arrival" name="is_new_arrival">
                                        <label class="form-check-label fw-semibold" for="is_new_arrival">Nouvelle arrivée</label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <a href="../../products.php" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-xmark me-1"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer le produit
                            </button>
                        </div>

                        <input type="hidden" name="stock" value="0">
                        <input type="hidden" name="low_stock_alert" value="0">
                        <input type="file" class="d-none" name="main_image" accept="image/jpeg,image/png,image/webp">
                        <input type="file" class="d-none" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('product-create-form');
    const formAlert = document.getElementById('form-alert');
    const variantBuilder = document.getElementById('variant-builder');
    const variantAlert = document.getElementById('variant-form-alert');
    const addVariantButton = document.getElementById('add-variant-group');
    const sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    const maxGallery = 5;
    const maxSize = 5 * 1024 * 1024;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    let variantIndex = 0;

    function showFormAlert(message) {
        if (formAlert) {
            formAlert.textContent = message;
            formAlert.classList.remove('d-none');
        }
    }

    function hideFormAlert() {
        if (formAlert) {
            formAlert.textContent = '';
            formAlert.classList.add('d-none');
        }
    }

    // Function to add variant group
    function addVariantGroup() {
        const group = document.createElement('div');
        group.className = 'variant-group border rounded p-3 mb-3 bg-white';
        group.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h6 mb-0">Couleur ${variantIndex + 1}</h4>
                <button type="button" class="btn btn-outline-danger btn-sm remove-variant">Supprimer</button>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label fw-semibold">Nom de la couleur <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="variant_colors[${variantIndex}][name]">
                    <div class="invalid-feedback variant-field-error"></div>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label fw-semibold">Image principale</label>
                    <input type="file" class="form-control variant-main-image" name="variant_colors[${variantIndex}][main_image]" accept="image/jpeg,image/png,image/webp">
                    <div class="variant-main-preview mt-2"></div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Galerie</label>
                    <input type="file" class="form-control variant-gallery-images" name="variant_colors[${variantIndex}][gallery_images][]" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="variant-gallery-preview row g-2 mt-2"></div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Tailles disponibles</label>
                    <div class="variant-sizes-container d-flex flex-wrap gap-2">
                        ${sizes.map(function (size) {
                            return `<div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="variant_colors[${variantIndex}][sizes][]" value="${size}">
                                <label class="form-check-label">${size}</label>
                            </div>`;
                        }).join('')}
                    </div>
                    <div class="invalid-feedback variant-field-error"></div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Stock par taille</label>
                    <div class="row g-2 variant-stock-grid">
                        ${sizes.map(function (size) {
                            return `<div class="col-6 col-md-4 col-lg-2">
                                <label class="form-label small">${size}</label>
                                <input type="number" min="0" class="form-control form-control-sm" name="variant_colors[${variantIndex}][stock][${size}]" value="0">
                                <div class="invalid-feedback variant-field-error"></div>
                            </div>`;
                        }).join('')}
                    </div>
                </div>
                <div class="variant-group-error invalid-feedback d-block mt-2"></div>
            </div>`;

        variantBuilder.appendChild(group);
        variantIndex += 1;
        bindVariantEvents(group);
    }

    function bindVariantEvents(group) {
        const mainInput = group.querySelector('.variant-main-image');
        const galleryInput = group.querySelector('.variant-gallery-images');
        const mainPreview = group.querySelector('.variant-main-preview');
        const galleryPreview = group.querySelector('.variant-gallery-preview');
        const nameInput = group.querySelector('input[name$="[name]"]');
        const sizeCheckboxes = Array.from(group.querySelectorAll('input[type="checkbox"][name$="[sizes][]"]'));

        if (mainInput) {
            mainInput.addEventListener('change', function () {
                const [file] = this.files || [];
                if (!file) {
                    mainPreview.innerHTML = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (event) {
                    mainPreview.innerHTML = '<img src="' + event.target.result + '" class="img-fluid rounded" style="height: 120px; object-fit: cover; width: 100%;">';
                };
                reader.readAsDataURL(file);
            });
        }

        if (galleryInput) {
            galleryInput.addEventListener('change', function () {
                const files = Array.from(this.files || []);
                galleryPreview.innerHTML = '';
                files.forEach(function (file) {
                    const col = document.createElement('div');
                    col.className = 'col-6';
                    const wrapper = document.createElement('div');
                    wrapper.className = 'border rounded p-1 bg-white';
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        wrapper.innerHTML = '<img src="' + event.target.result + '" class="img-fluid rounded" style="height: 70px; object-fit: cover; width: 100%;">';
                        col.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                    galleryPreview.appendChild(col);
                });
            });
        }

        const removeButton = group.querySelector('.remove-variant');
        if (removeButton) {
            removeButton.addEventListener('click', function () {
                group.remove();
            });
        }
    }

    addVariantButton.addEventListener('click', addVariantGroup);
    addVariantGroup();
});
</script>

<?php include '../../includes/footer.php'; ?>