<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../categories.php");
    exit;
}

$id = (int) $_POST['id'];
$name = trim($_POST['name']);

// تحديث الاسم
$stmt = $pdo->prepare("
    UPDATE category_translations
    SET name = ?
    WHERE id_category = ?
");

$stmt->execute([$name, $id]);

// تحديث الصورة إذا تم اختيار صورة جديدة
if (!empty($_FILES['image']['name'])) {

    // جلب الصورة القديمة
    $stmt = $pdo->prepare("
        SELECT image
        FROM categories
        WHERE id_category = ?
    ");

    $stmt->execute([$id]);

    $category = $stmt->fetch();

    if ($category && !empty($category['image'])) {

        $oldImage = "../../../assets/uploads/categories/" . $category['image'];

        if (file_exists($oldImage)) {
            unlink($oldImage);
        }
    }

    // رفع الصورة الجديدة
    $imageName = time() . "_" . basename($_FILES['image']['name']);

    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        "../../../assets/uploads/categories/" . $imageName
    );

    // تحديث قاعدة البيانات
    $stmt = $pdo->prepare("
        UPDATE categories
        SET image = ?
        WHERE id_category = ?
    ");

    $stmt->execute([$imageName, $id]);
}

header("Location: ../../categories.php");
exit;