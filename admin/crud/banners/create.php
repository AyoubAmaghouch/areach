<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

include '../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-rectangle-ad me-2" style="color:var(--color-primary);"></i>
            Ajouter une bannière
        </h1>
        <p class="page-subtitle">Créez une nouvelle bannière promotionnelle pour la page d'accueil.</p>
    </div>
    <a href="../../banners.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Retour
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-xl-8">

        <div class="form-card">
            <div class="form-card-header">
                <span class="card-section-icon"><i class="fa-solid fa-plus"></i></span>
                <h3>Détails de la bannière</h3>
            </div>
            <div class="form-card-body">
                <form action="store.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Texte du bouton</label>
                            <input type="text" class="form-control" name="button_text">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Sous-titre</label>
                            <textarea class="form-control" name="subtitle" rows="3"></textarea>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Image Desktop <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="desktop_image" accept="image/*" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Image Mobile <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="mobile_image" accept="image/*" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Lien du bouton</label>
                            <input type="text" class="form-control" name="button_link">
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Ordre d'affichage</label>
                            <input type="number" class="form-control" name="display_order" value="1" min="1">
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Statut</label>
                            <select class="form-select" name="status">
                                <option value="1">Actif</option>
                                <option value="0">Inactif</option>
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