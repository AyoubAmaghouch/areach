<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../settings.php");
    exit;
}

$store_name = trim($_POST['store_name']);
$email = trim($_POST['email']);
$telephone = trim($_POST['telephone']);
$whatsapp = trim($_POST['whatsapp']);
$facebook = trim($_POST['facebook']);
$instagram = trim($_POST['instagram']);
$tiktok = trim($_POST['tiktok']);
$address = trim($_POST['address']);
$delivery_price = (float)$_POST['delivery_price'];
$free_delivery = (float)$_POST['free_delivery'];

/* Vérifier si une ligne existe */

$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();

$logo = $settings['logo'] ?? "";

/* Upload Logo */

if (!empty($_FILES['logo']['name'])) {

    if (!empty($logo)) {

        $old = "../../../assets/uploads/settings/" . $logo;

        if (file_exists($old)) {
            unlink($old);
        }
    }

    $logo = time() . "_" . basename($_FILES['logo']['name']);

    move_uploaded_file(
        $_FILES['logo']['tmp_name'],
        "../../../assets/uploads/settings/" . $logo
    );
}

/* UPDATE */

if ($settings) {

    $stmt = $pdo->prepare("
    UPDATE settings SET

    store_name=?,
    logo=?,
    email=?,
    telephone=?,
    whatsapp=?,
    facebook=?,
    instagram=?,
    tiktok=?,
    address=?,
    delivery_price=?,
    free_delivery=?

    WHERE id_setting=?
    ");

    $stmt->execute([

        $store_name,
        $logo,
        $email,
        $telephone,
        $whatsapp,
        $facebook,
        $instagram,
        $tiktok,
        $address,
        $delivery_price,
        $free_delivery,
        $settings['id_setting']

    ]);

} else {

    $stmt = $pdo->prepare("
    INSERT INTO settings(

    store_name,
    logo,
    email,
    telephone,
    whatsapp,
    facebook,
    instagram,
    tiktok,
    address,
    delivery_price,
    free_delivery

    )

    VALUES(?,?,?,?,?,?,?,?,?,?,?)

    ");

    $stmt->execute([

        $store_name,
        $logo,
        $email,
        $telephone,
        $whatsapp,
        $facebook,
        $instagram,
        $tiktok,
        $address,
        $delivery_price,
        $free_delivery

    ]);

}

header("Location: ../../settings.php");
exit;