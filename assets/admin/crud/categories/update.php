<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

function redirectCategoryUpdate(string $message, string $type = 'success'): never
{
    $_SESSION['category_flash'] = ['message' => $message, 'type' => $type];
    header("Location: ../../categories");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../../categories");
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));

if ($id < 1 || $name === '') {
    redirectCategoryUpdate("Veuillez remplir les champs obligatoires.", 'error');
}

// تحديث الاسم
$stmt = $pdo->prepare("
    UPDATE category_translations
    SET name = ?
    WHERE id_category = ?
");

$stmt->execute([$name, $id]);

// تحديث الصورة إذا تم اختيار صورة جديدة
if (!empty($_FILES['image']['name'])) {
    if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectCategoryUpdate("L'image n'a pas pu etre telechargee.", 'error');
    }

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

    $directory = __DIR__ . '/../../../assets/uploads/categories';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        redirectCategoryUpdate("Le dossier des categories est indisponible.", 'error');
    }

    if (!move_uploaded_file(
        $_FILES['image']['tmp_name'],
        $directory . "/" . $imageName
    )) {
        redirectCategoryUpdate("L'image n'a pas pu etre enregistree.", 'error');
    }

    // تحديث قاعدة البيانات
    $stmt = $pdo->prepare("
        UPDATE categories
        SET image = ?
        WHERE id_category = ?
    ");

    $stmt->execute([$imageName, $id]);
}

redirectCategoryUpdate("Categorie modifiee avec succes.");
