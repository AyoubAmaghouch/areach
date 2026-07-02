<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../categories.php");
    exit;
}

$id = (int) $_GET['id'];

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
    header("Location: ../../categories.php?error=delete_failed");
    exit;
}

header("Location: ../../categories.php?success=deleted");
exit;