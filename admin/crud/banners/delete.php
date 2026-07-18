<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

if (!isset($_GET['id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Bannière introuvable.']);
        exit;
    }
    header("Location: ../../banners");
    exit;
}

$id = (int) $_GET['id'];
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

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
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
        exit;
    }
    header("Location: ../../banners?error=delete_failed");
    exit;
}

if ($isAjax) {
    echo json_encode(['success' => true, 'message' => 'Bannière supprimée avec succès.']);
    exit;
}
header("Location: ../../banners?success=deleted");
exit;