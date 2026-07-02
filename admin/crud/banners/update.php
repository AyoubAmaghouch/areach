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

include '../../includes/header.php';

?>

<h2>Modifier une bannière</h2>

<form action="update.php" method="POST" enctype="multipart/form-data">

    <input
        type="hidden"
        name="id_banner"
        value="<?= $banner['id_banner']; ?>">

    <label>Titre</label><br>
    <input
        type="text"
        name="title"
        value="<?= htmlspecialchars($banner['title']); ?>"
        required>

    <br><br>

    <label>Sous-titre</label><br>
    <textarea
        name="subtitle"
        rows="4"><?= htmlspecialchars($banner['subtitle']); ?></textarea>

    <br><br>

    <label>Image Desktop actuelle</label><br>

    <?php if(!empty($banner['desktop_image'])): ?>

        <img
            src="../../../assets/uploads/banners/<?= $banner['desktop_image']; ?>"
            width="200">

    <?php endif; ?>

    <br><br>

    <label>Nouvelle image Desktop</label><br>
    <input type="file" name="desktop_image">

    <br><br>

    <label>Image Mobile actuelle</label><br>

    <?php if(!empty($banner['mobile_image'])): ?>

        <img
            src="../../../assets/uploads/banners/<?= $banner['mobile_image']; ?>"
            width="120">

    <?php endif; ?>

    <br><br>

    <label>Nouvelle image Mobile</label><br>
    <input type="file" name="mobile_image">

    <br><br>

    <label>Texte du bouton</label><br>
    <input
        type="text"
        name="button_text"
        value="<?= htmlspecialchars($banner['button_text']); ?>">

    <br><br>

    <label>Lien du bouton</label><br>
    <input
        type="text"
        name="button_link"
        value="<?= htmlspecialchars($banner['button_link']); ?>">

    <br><br>

    <label>Ordre</label><br>
    <input
        type="number"
        name="display_order"
        value="<?= $banner['display_order']; ?>">

    <br><br>

    <label>Statut</label><br>

    <select name="status">

        <option value="1" <?= $banner['status']==1 ? 'selected' : ''; ?>>
            Actif
        </option>

        <option value="0" <?= $banner['status']==0 ? 'selected' : ''; ?>>
            Inactif
        </option>

    </select>

    <br><br>

    <button type="submit">
        Modifier
    </button>

    <a href="../../banners.php">
        Retour
    </a>

</form>

<?php include '../../includes/footer.php'; ?>