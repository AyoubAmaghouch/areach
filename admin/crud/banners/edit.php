<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../banners.php");
    exit;
}

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("
SELECT *
FROM banners
WHERE id_banner = ?
");

$stmt->execute([$id]);
$banner = $stmt->fetch();

if (!$banner) {
    die("Bannière introuvable.");
}

$desktopImg = adminImagePath('banners', $banner['desktop_image']);
$mobileImg  = adminImagePath('banners', $banner['mobile_image']);

include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-rectangle-ad me-2" style="color:var(--color-primary);"></i>
            Modifier la bannière
        </h1>
        <p class="page-subtitle"><?= htmlspecialchars($banner['title'] ?? '') ?></p>
    </div>
    <a href="../../banners.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-xl-8">

        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-pen"></i></span>
                <h3>Détails de la bannière</h3>
            </div>
            <div class="form-card-body">
                <form action="update.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_banner" value="<?= $banner['id_banner'] ?>">

                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($banner['title'] ?? '') ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Texte du bouton</label>
                            <input type="text" class="form-control" name="button_text" value="<?= htmlspecialchars($banner['button_text'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Sous-titre</label>
                            <textarea class="form-control" name="subtitle" rows="3"><?= htmlspecialchars($banner['subtitle'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12 col-md-6">
                            <?php if ($desktopImg !== ''): ?>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold d-block">Image Desktop actuelle</label>
                                    <img src="<?= $desktopImg ?>" alt="Desktop Preview" style="max-height:80px; border-radius:8px; border:1px solid var(--border-color);">
                                </div>
                            <?php endif; ?>
                            <label class="form-label fw-semibold">Nouvelle image Desktop</label>
                            <input type="file" class="form-control" name="desktop_image" accept="image/*">
                        </div>

                        <div class="col-12 col-md-6">
                            <?php if ($mobileImg !== ''): ?>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold d-block">Image Mobile actuelle</label>
                                    <img src="<?= $mobileImg ?>" alt="Mobile Preview" style="max-height:80px; border-radius:8px; border:1px solid var(--border-color);">
                                </div>
                            <?php endif; ?>
                            <label class="form-label fw-semibold">Nouvelle image Mobile</label>
                            <input type="file" class="form-control" name="mobile_image" accept="image/*">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Lien du bouton</label>
                            <input type="text" class="form-control" name="button_link" value="<?= htmlspecialchars($banner['button_link'] ?? '') ?>">
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Ordre</label>
                            <input type="number" class="form-control" name="display_order" value="<?= (int)$banner['display_order'] ?>" min="1">
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Statut</label>
                            <select class="form-select" name="status">
                                <option value="1" <?= (int)$banner['status'] === 1 ? 'selected' : '' ?>>Actif</option>
                                <option value="0" <?= (int)$banner['status'] === 0 ? 'selected' : '' ?>>Inactif</option>
                            </select>
                        </div>

                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn-primary-admin">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                        </button>
                        <a href="../../banners.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Annuler
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>