<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

function redirectBanner(string $message, string $type = 'success'): never
{
    $_SESSION['banner_flash'] = ['message' => $message, 'type' => $type];
    header("Location: ../../banners");
    exit;
}

function bannerUpload(string $field, string $current): string
{
    if (empty($_FILES[$field]['name'])) {
        return $current;
    }

    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Erreur lors du telechargement de l'image.");
    }

    $tmp = $_FILES[$field]['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp) || getimagesize($tmp) === false) {
        throw new RuntimeException("Le fichier telecharge n'est pas une image valide.");
    }

    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Format image non supporte.');
    }

    $directory = __DIR__ . '/../../../assets/uploads/banners';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException("Le dossier des bannieres est indisponible.");
    }

    $name = time() . '_' . $field . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    if (!move_uploaded_file($tmp, $directory . '/' . $name)) {
        throw new RuntimeException("L'image n'a pas pu etre enregistree.");
    }

    if ($current !== '') {
        $old = $directory . '/' . basename($current);
        if (is_file($old)) {
            unlink($old);
        }
    }

    return $name;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../banners");
    exit;
}

$id = (int) ($_POST['id_banner'] ?? 0);
$title = trim((string) ($_POST['title'] ?? ''));
$subtitle = trim((string) ($_POST['subtitle'] ?? ''));
$buttonText = trim((string) ($_POST['button_text'] ?? ''));
$buttonLink = trim((string) ($_POST['button_link'] ?? ''));
$displayOrder = max(1, (int) ($_POST['display_order'] ?? 1));
$status = (int) ($_POST['status'] ?? 1);
$status = in_array($status, [0, 1], true) ? $status : 1;

if ($id < 1 || $title === '') {
    redirectBanner('Veuillez remplir les champs obligatoires.', 'error');
}

try {
    $stmt = $pdo->prepare("SELECT desktop_image, mobile_image FROM banners WHERE id_banner = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();

    if (!$banner) {
        redirectBanner('Banniere introuvable.', 'error');
    }

    $desktopImage = bannerUpload('desktop_image', (string) ($banner['desktop_image'] ?? ''));
    $mobileImage = bannerUpload('mobile_image', (string) ($banner['mobile_image'] ?? ''));

    $stmt = $pdo->prepare("
        UPDATE banners
        SET title = ?,
            subtitle = ?,
            desktop_image = ?,
            mobile_image = ?,
            button_text = ?,
            button_link = ?,
            display_order = ?,
            status = ?
        WHERE id_banner = ?
    ");

    $stmt->execute([
        $title,
        $subtitle,
        $desktopImage,
        $mobileImage,
        $buttonText,
        $buttonLink,
        $displayOrder,
        $status,
        $id,
    ]);

    redirectBanner('Banniere modifiee avec succes.');
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    redirectBanner("La banniere n'a pas pu etre modifiee.", 'error');
}
