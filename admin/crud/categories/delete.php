<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Catégorie introuvable.']);
        exit;
    }
    header("Location: ../../categories.php");
    exit;
}

$id = (int) $_GET['id'];
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if any products belong to this category
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE id_category = ?");
$stmt->execute([$id]);
$productCount = (int) $stmt->fetchColumn();

if ($productCount > 0) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Cette catégorie ne peut pas être supprimée car elle contient des produits.']);
        exit;
    }
    header("Location: ../../categories.php?error=has_products");
    exit;
}

try {
    $pdo->beginTransaction();

    // جلب الصورة
    $stmt = $pdo->prepare("
        SELECT image
        FROM categories
        WHERE id_category = ?
    ");

    $stmt->execute([$id]);
    $category = $stmt->fetch();

    if ($category) {

        // حذف الصورة
        if (!empty($category['image'])) {

            $imagePath = "../../../assets/uploads/categories/" . $category['image'];

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // حذف الترجمات
        $stmt = $pdo->prepare("
            DELETE FROM category_translations
            WHERE id_category = ?
        ");

        $stmt->execute([$id]);

        // حذف الفئة
        $stmt = $pdo->prepare("
            DELETE FROM categories
            WHERE id_category = ?
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
    header("Location: ../../categories.php?error=delete_failed");
    exit;
}

if ($isAjax) {
    echo json_encode(['success' => true, 'message' => 'Catégorie supprimée avec succès.']);
    exit;
}
header("Location: ../../categories.php?success=deleted");
exit;