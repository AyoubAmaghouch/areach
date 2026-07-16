<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    $redirect = "../../products.php";
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Produit introuvable.']);
        exit;
    }
    header("Location: $redirect");
    exit;
}

$id = (int) $_GET['id'];
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Check if product is linked to any orders
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM order_items oi
    INNER JOIN product_variants pv ON oi.id_variant = pv.id_variant
    WHERE pv.id_product = ?
");
$stmt->execute([$id]);
$orderCount = (int) $stmt->fetchColumn();

if ($orderCount > 0) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Ce produit ne peut pas être supprimé car il est lié à des commandes existantes.']);
        exit;
    }
    header("Location: ../../products.php?error=has_orders");
    exit;
}

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
        $filename = basename((string) $image['image']);

        if ($filename === '') {
            continue;
        }

        $paths = [
            "../../../assets/uploads/products/" . $filename,
            "../../../assets/images/products/" . $filename,
        ];

        $productRoot = "../../../assets/images/products";
        if (is_dir($productRoot)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($productRoot, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() === $filename) {
                    $paths[] = $file->getPathname();
                }
            }
        }

        foreach (array_unique($paths) as $path) {
            if (is_file($path)) {
                unlink($path);
            }
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
    $msg = 'Erreur lors de la suppression du produit.';
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    header("Location: ../../products.php?error=delete_failed");
    exit;

}

if ($isAjax) {
    echo json_encode(['success' => true, 'message' => 'Produit supprimé avec succès.']);
    exit;
}
header("Location: ../../products.php?success=deleted");
exit;
