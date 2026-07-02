<?php
require_once __DIR__ . '/../../config/session.php';

if (!isset($_SESSION['admin_id'])) {
    // Redirect to login using correct relative path
    $loginUrl = 'login.php';
    if (basename(dirname($_SERVER['PHP_SELF'])) !== 'admin') {
        $loginUrl = '../../login.php';
    }
    header("Location: " . $loginUrl);
    exit;
}

// Calculate relative path to project root dynamically
$projectRoot = str_replace('\\', '/', realpath(dirname(dirname(__DIR__))));
$currentScript = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
$relativePathToRoot = '';
$tempPath = str_replace('\\', '/', dirname($currentScript));

while (strlen($tempPath) > strlen($projectRoot) && str_starts_with($tempPath, $projectRoot)) {
    $relativePathToRoot .= '../';
    $tempPath = str_replace('\\', '/', dirname($tempPath));
}

// Store globally for named function access
$GLOBALS['relativePathToRoot'] = $relativePathToRoot;

// Global path and URL helpers for Admin Panel
if (!function_exists('rootUrl')) {
    function rootUrl(string $path): string {
        return $GLOBALS['relativePathToRoot'] . ltrim($path, '/');
    }
}

if (!function_exists('adminUrl')) {
    function adminUrl(string $path): string {
        return $GLOBALS['relativePathToRoot'] . 'admin/' . ltrim($path, '/');
    }
}

if (!function_exists('adminImagePath')) {
    function adminImagePath(string $folder, ?string $filename, ?int $categoryId = null): string {
        if ($filename === null || trim($filename) === '') {
            return '';
        }
        
        $folder = trim(str_replace('\\', '/', $folder), '/');
        $filename = ltrim(str_replace('\\', '/', $filename), '/');

        $projectRoot = str_replace('\\', '/', realpath(dirname(dirname(__DIR__))));

        // 1. Try category specific path for products
        if ($categoryId !== null && $folder === 'products') {
            $candidate = "assets/images/products/{$categoryId}/{$filename}";
            if (file_exists($projectRoot . '/' . $candidate)) {
                return rootUrl($candidate);
            }
        }

        // 2. Try standard candidate paths
        $candidates = [
            "assets/images/{$folder}/{$filename}",
            "assets/uploads/{$folder}/{$filename}"
        ];
        foreach ($candidates as $c) {
            if (file_exists($projectRoot . '/' . $c)) {
                return rootUrl($c);
            }
        }

        // 3. Fallback search inside assets/images/products recursively
        if ($folder === 'products') {
            $productRoot = $projectRoot . '/assets/images/products';
            if (is_dir($productRoot)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($productRoot, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getFilename() === $filename) {
                        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($projectRoot . '/')));
                        return rootUrl($rel);
                    }
                }
            }
        }

        return rootUrl("assets/uploads/{$folder}/{$filename}");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AREACH Admin</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= rootUrl('assets/css/admin.css') ?>">
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<div id="admin-wrapper">

<?php include __DIR__ . '/sidebar.php'; ?>

<div id="admin-main">

<?php include __DIR__ . '/navbar.php'; ?>

<div id="admin-content">