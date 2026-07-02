<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../categories.php");
    exit;
}

$name = trim($_POST['name']);

if (empty($name)) {
    die("Le nom est obligatoire.");
}

// Upload image
$imageName = null;

if (!empty($_FILES['image']['name'])) {

    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);

    $imageName = time() . "." . $extension;

    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        "../../../assets/uploads/categories/" . $imageName
    );
}

// Ajouter catégorie
$stmt = $pdo->prepare("
    INSERT INTO categories (image, status)
    VALUES (?, 1)
");

$stmt->execute([$imageName]);

$idCategory = $pdo->lastInsertId();

// Récupérer l'ID de la langue française
$stmt = $pdo->prepare("
    SELECT id_language
    FROM languages
    WHERE code = 'fr'
");

$stmt->execute();

$language = $stmt->fetch();

// Ajouter la traduction française
$stmt = $pdo->prepare("
    INSERT INTO category_translations
    (id_category, id_language, name)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $idCategory,
    $language['id_language'],
    $name
]);

header("Location: ../../categories.php");
exit;