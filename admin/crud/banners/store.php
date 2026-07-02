<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
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
    die("Le titre est obligatoire.");
}

/* Upload Desktop Image */

$desktopImage = "";

if (!empty($_FILES['desktop_image']['name'])) {

    $desktopImage = time() . "_desktop_" . basename($_FILES['desktop_image']['name']);

    move_uploaded_file(
        $_FILES['desktop_image']['tmp_name'],
        "../../../assets/uploads/banners/" . $desktopImage
    );
}

/* Upload Mobile Image */

$mobileImage = "";

if (!empty($_FILES['mobile_image']['name'])) {

    $mobileImage = time() . "_mobile_" . basename($_FILES['mobile_image']['name']);

    move_uploaded_file(
        $_FILES['mobile_image']['tmp_name'],
        "../../../assets/uploads/banners/" . $mobileImage
    );
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

header("Location: ../../banners.php");
exit;