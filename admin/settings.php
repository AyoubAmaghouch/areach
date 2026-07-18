<?php

require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login");
    exit;
}

$stmt     = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();
$flash = $_SESSION['settings_flash'] ?? null;
unset($_SESSION['settings_flash']);

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fa-solid fa-sliders me-2" style="color:var(--color-primary);"></i>
            Paramètres du magasin
        </h1>
        <p class="page-subtitle">Configurez les informations de votre boutique.</p>
    </div>
</div>

<?php if ($flash): ?>
    <div class="flash-alert <?= htmlspecialchars($flash['type'] ?? 'info') ?>" data-auto-dismiss role="alert">
        <i class="fa-solid <?= ($flash['type'] ?? '') === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= htmlspecialchars($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<form action="crud/settings/update" method="POST" enctype="multipart/form-data">

    <div class="row g-4">

        <!-- Informations générales -->
        <div class="col-12 col-lg-6">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="card-section-icon"><i class="fa-solid fa-store"></i></span>
                    <h3>Informations générales</h3>
                </div>
                <div class="form-card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-tag me-2 text-muted"></i>Nom du magasin
                        </label>
                        <input type="text" class="form-control" name="store_name"
                               value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-envelope me-2 text-muted"></i>Email
                        </label>
                        <input type="email" class="form-control" name="email"
                               value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-phone me-2 text-muted"></i>Téléphone
                        </label>
                        <input type="text" class="form-control" name="telephone"
                               value="<?= htmlspecialchars($settings['telephone'] ?? '') ?>">
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-location-dot me-2 text-muted"></i>Adresse
                        </label>
                        <textarea class="form-control" name="address" rows="3"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- Logo -->
        <div class="col-12 col-lg-6">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="card-section-icon"><i class="fa-solid fa-image"></i></span>
                    <h3>Logo</h3>
                </div>
                <div class="form-card-body">

                    <?php if (!empty($settings['logo'])): ?>
                        <div class="mb-3 text-center">
                            <img src="../assets/uploads/settings/<?= htmlspecialchars($settings['logo']) ?>"
                                 alt="Logo" style="max-height:80px; border-radius:8px; border:1px solid var(--border-color);">
                        </div>
                    <?php endif; ?>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-upload me-2 text-muted"></i>Nouveau logo
                        </label>
                        <input type="file" class="form-control" name="logo" accept=".jpg,.jpeg,.png,.webp">
                        <div class="form-text">JPG, PNG ou WEBP recommandé</div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Réseaux sociaux -->
        <div class="col-12 col-lg-6">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="card-section-icon"><i class="fa-solid fa-share-nodes"></i></span>
                    <h3>Réseaux sociaux</h3>
                </div>
                <div class="form-card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-brands fa-whatsapp me-2 text-success"></i>WhatsApp
                        </label>
                        <input type="text" class="form-control" name="whatsapp"
                               value="<?= htmlspecialchars($settings['whatsapp'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-brands fa-facebook me-2 text-primary"></i>Facebook
                        </label>
                        <input type="text" class="form-control" name="facebook"
                               value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-brands fa-instagram me-2 text-danger"></i>Instagram
                        </label>
                        <input type="text" class="form-control" name="instagram"
                               value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>">
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">
                            <i class="fa-brands fa-tiktok me-2"></i>TikTok
                        </label>
                        <input type="text" class="form-control" name="tiktok"
                               value="<?= htmlspecialchars($settings['tiktok'] ?? '') ?>">
                    </div>

                </div>
            </div>
        </div>

        <!-- Livraison -->
        <div class="col-12 col-lg-6">
            <div class="form-card">
                <div class="form-card-header">
                    <span class="card-section-icon"><i class="fa-solid fa-truck"></i></span>
                    <h3>Livraison</h3>
                </div>
                <div class="form-card-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-euro-sign me-2 text-muted"></i>Prix de livraison (€)
                        </label>
                        <input type="number" step="0.01" class="form-control" name="delivery_price"
                               value="<?= htmlspecialchars((string)($settings['delivery_price'] ?? 0)) ?>">
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-box me-2 text-muted"></i>Livraison gratuite à partir de (€)
                        </label>
                        <input type="number" step="0.01" class="form-control" name="free_delivery"
                               value="<?= htmlspecialchars((string)($settings['free_delivery'] ?? 0)) ?>">
                        <div class="form-text">Laissez 0 pour désactiver la livraison gratuite</div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Sticky Save Bar -->
    <div class="sticky-form-footer mt-4">
        <span class="text-muted small">
            <i class="fa-solid fa-circle-info me-1"></i>
            Les modifications sont enregistrées immédiatement.
        </span>
        <button type="submit" class="btn-primary-admin">
            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
        </button>
    </div>

</form>

<?php include 'includes/footer.php'; ?>
