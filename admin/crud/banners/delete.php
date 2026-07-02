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

try {
    $pdo->beginTransaction();

    // Récupérer les images
    $stmt = $pdo->prepare("
    SELECT desktop_image, mobile_image
    FROM banners
    WHERE id_banner = ?
    ");

    $stmt->execute([$id]);
    $banner = $stmt->fetch();

    if ($banner) {
        // Supprimer image Desktop
        if (!empty($banner['desktop_image'])) {

            $path = "../../../assets/uploads/banners/" . $banner['desktop_image'];

            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Supprimer image Mobile
        if (!empty($banner['mobile_image'])) {

            $path = "../../../assets/uploads/banners/" . $banner['mobile_image'];

            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Supprimer la bannière
        $stmt = $pdo->prepare("
        DELETE FROM banners
        WHERE id_banner = ?
        ");

        $stmt->execute([$id]);
    }

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../../banners.php?error=delete_failed");
    exit;
}

header("Location: ../../banners.php?success=deleted");
exit;