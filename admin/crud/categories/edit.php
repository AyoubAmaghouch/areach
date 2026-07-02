<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../categories.php");
    exit;
}

$id = (int) $_GET['id'];

$sql = "SELECT *
        FROM categories
        WHERE id_category = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    die("Catégorie introuvable.");
}

$sql = "SELECT ct.name
        FROM category_translations ct
        INNER JOIN languages l
        ON ct.id_language = l.id_language
        WHERE ct.id_category = ?
        AND l.code='fr'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$translation = $stmt->fetch();

$imgSrc = adminImagePath('categories', $category['image']);

include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-layer-group me-2" style="color:var(--color-primary);"></i>
            Modifier la catégorie
        </h1>
        <p class="page-subtitle"><?= htmlspecialchars($translation['name'] ?? '') ?></p>
    </div>
    <a href="../../categories.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">

        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-pen"></i></span>
                <h3>Détails de la catégorie</h3>
            </div>
            <div class="form-card-body">
                <form action="update.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $category['id_category'] ?>">

                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">Nom de la catégorie <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($translation['name'] ?? '') ?>"
                            required>
                    </div>

                    <div class="mb-4">
                        <?php if ($imgSrc !== ''): ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Image actuelle</label>
                                <img src="<?= $imgSrc ?>" alt="Preview" style="max-height:120px; border-radius:8px; border:1px solid var(--border-color);">
                            </div>
                        <?php endif; ?>

                        <label for="image" class="form-label fw-semibold">Nouvelle image (optionnelle)</label>
                        <input
                            type="file"
                            class="form-control"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp">
                        <div class="form-text">Laissez vide pour conserver l'image actuelle.</div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn-primary-admin">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                        </button>
                        <a href="../../categories.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Annuler
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>