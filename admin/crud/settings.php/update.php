<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

function redirectSettings(string $message, string $type = 'success'): never
{
    $_SESSION['settings_flash'] = ['message' => $message, 'type' => $type];
    header("Location: ../../settings");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../settings");
    exit;
}

$store_name = trim((string) ($_POST['store_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$telephone = trim((string) ($_POST['telephone'] ?? ''));
$whatsapp = trim((string) ($_POST['whatsapp'] ?? ''));
$facebook = trim((string) ($_POST['facebook'] ?? ''));
$instagram = trim((string) ($_POST['instagram'] ?? ''));
$tiktok = trim((string) ($_POST['tiktok'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));
$delivery_price = (float) ($_POST['delivery_price'] ?? 0);
$free_delivery = (float) ($_POST['free_delivery'] ?? 0);

/* Vérifier si une ligne existe */

$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();

$logo = $settings['logo'] ?? "";

/* Upload Logo */

if (!empty($_FILES['logo']['name'])) {
    if (($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectSettings("Le logo n'a pas pu etre telecharge.", 'error');
    }

    if (!empty($logo)) {

        $old = "../../../assets/uploads/settings/" . $logo;

        if (file_exists($old)) {
            unlink($old);
        }
    }

    $logo = time() . "_" . basename($_FILES['logo']['name']);

    $directory = __DIR__ . '/../../../assets/uploads/settings';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        redirectSettings("Le dossier des parametres est indisponible.", 'error');
    }

    if (!move_uploaded_file(
        $_FILES['logo']['tmp_name'],
        $directory . "/" . $logo
    )) {
        redirectSettings("Le logo n'a pas pu etre enregistre.", 'error');
    }
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

redirectSettings("Parametres enregistres avec succes.");
