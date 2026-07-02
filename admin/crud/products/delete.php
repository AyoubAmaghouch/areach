<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../../products.php");
    exit;
}

$id = (int) $_GET['id'];

try {

    $pdo->beginTransaction();

    // حذف المقاسات
    $stmt = $pdo->prepare("
        DELETE pvs
        FROM product_variant_sizes pvs
        INNER JOIN product_variants pv
        ON pvs.id_variant = pv.id_variant
        WHERE pv.id_product = ?
    ");
    $stmt->execute([$id]);

    // حذف الصور
    $stmt = $pdo->prepare("
        SELECT image
        FROM product_images pi
        INNER JOIN product_variants pv
        ON pi.id_variant = pv.id_variant
        WHERE pv.id_product = ?
    ");
    $stmt->execute([$id]);

    while ($image = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $path = "../../../assets/uploads/products/" . $image['image'];

        if (file_exists($path)) {
            unlink($path);
        }
    }

    $stmt = $pdo->prepare("
        DELETE pi
        FROM product_images pi
        INNER JOIN product_variants pv
        ON pi.id_variant = pv.id_variant
        WHERE pv.id_product = ?
    ");
    $stmt->execute([$id]);

    // حذف الـ Variants
    $stmt = $pdo->prepare("
        DELETE FROM product_variants
        WHERE id_product = ?
    ");
    $stmt->execute([$id]);

    // حذف الترجمات
    $stmt = $pdo->prepare("
        DELETE FROM product_translations
        WHERE id_product = ?
    ");
    $stmt->execute([$id]);

    // حذف المنتج
    $stmt = $pdo->prepare("
        DELETE FROM products
        WHERE id_product = ?
    ");
    $stmt->execute([$id]);

    $pdo->commit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Redirect instead of die() to prevent broken layout
    header("Location: ../../products.php?error=delete_failed");
    exit;

}

header("Location: ../../products.php?success=deleted");
exit;