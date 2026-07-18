<?php

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login");
    exit;
}

function redirectCategoryStore(string $message, string $type = 'success'): never
{
    $_SESSION['category_flash'] = ['message' => $message, 'type' => $type];
    header("Location: ../../categories");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../categories");
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));

if (empty($name)) {
    redirectCategoryStore("Le nom est obligatoire.", 'error');
}

// Upload image
$imageName = null;

if (!empty($_FILES['image']['name'])) {
    if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        redirectCategoryStore("L'image n'a pas pu etre telechargee.", 'error');
    }

    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);

    $imageName = time() . "." . $extension;

    $directory = __DIR__ . '/../../../assets/uploads/categories';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        redirectCategoryStore("Le dossier des categories est indisponible.", 'error');
    }

    if (!move_uploaded_file(
        $_FILES['image']['tmp_name'],
        $directory . "/" . $imageName
    )) {
        redirectCategoryStore("L'image n'a pas pu etre enregistree.", 'error');
    }
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

if (!$language) {
    redirectCategoryStore("Langue par defaut introuvable.", 'error');
}

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

redirectCategoryStore("Categorie enregistree avec succes.");
