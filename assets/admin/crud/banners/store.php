<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

function redirectBannerStore(string $message, string $type = 'success'): never
{
    $_SESSION['banner_flash'] = ['message' => $message, 'type' => $type];
    header("Location: ../../banners.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../banners.php");
    exit;
}

$title = trim($_POST['title']);
$subtitle = trim($_POST['subtitle']);
$button_text = trim($_POST['button_text']);
$button_link = trim($_POST['button_link']);
$display_order = (int) $_POST['display_order'];
$status = (int) $_POST['status'];

if (empty($title)) {
    redirectBannerStore("Le titre est obligatoire.", 'error');
}

$directory = __DIR__ . '/../../../assets/uploads/banners';
if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    redirectBannerStore("Le dossier des bannieres est indisponible.", 'error');
}

/* Upload Desktop Image */

$desktopImage = "";

if (!empty($_FILES['desktop_image']['name'])) {

    $desktopImage = time() . "_desktop_" . basename($_FILES['desktop_image']['name']);

    if (!move_uploaded_file(
        $_FILES['desktop_image']['tmp_name'],
        $directory . "/" . $desktopImage
    )) {
        redirectBannerStore("L'image desktop n'a pas pu etre enregistree.", 'error');
    }
}

/* Upload Mobile Image */

$mobileImage = "";

if (!empty($_FILES['mobile_image']['name'])) {

    $mobileImage = time() . "_mobile_" . basename($_FILES['mobile_image']['name']);

    if (!move_uploaded_file(
        $_FILES['mobile_image']['tmp_name'],
        $directory . "/" . $mobileImage
    )) {
        redirectBannerStore("L'image mobile n'a pas pu etre enregistree.", 'error');
    }
}

/* Insert */

$stmt = $pdo->prepare("
INSERT INTO banners
(
title,
subtitle,
desktop_image,
mobile_image,
button_text,
button_link,
display_order,
status
)
VALUES
(
?,?,?,?,?,?,?,?
)
");

$stmt->execute([
    $title,
    $subtitle,
    $desktopImage,
    $mobileImage,
    $button_text,
    $button_link,
    $display_order,
    $status
]);

redirectBannerStore("Banniere enregistree avec succes.");
