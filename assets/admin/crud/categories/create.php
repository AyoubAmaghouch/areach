<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-layer-group me-2" style="color:var(--color-primary);"></i>
            Ajouter une catégorie
        </h1>
        <p class="page-subtitle">Créez une nouvelle catégorie de produits.</p>
    </div>
    <a href="../../categories" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">

        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-folder-plus"></i></span>
                <h3>Détails de la catégorie</h3>
            </div>
            <div class="form-card-body">
                <form action="store" method="POST" enctype="multipart/form-data">

                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">Nom de la catégorie <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            placeholder="Ex : Robes"
                            required>
                    </div>

                    <div class="mb-4">
                        <label for="image" class="form-label fw-semibold">Image <span class="text-danger">*</span></label>
                        <input
                            type="file"
                            class="form-control"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                            required>
                        <div class="form-text">JPG, PNG ou WEBP accepté.</div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn-primary-admin">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                        </button>
                        <a href="../../categories" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-xmark me-1"></i> Annuler
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>