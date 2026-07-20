<?php

declare(strict_types=1);

require_once '../../../config/session.php';
require_once '../../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../login');
    exit;
}

if (($_GET['run'] ?? '') !== '1') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Add ?run=1 to repair product image filenames.';
    exit;
}

function imageOriginalKey(string $filename): string
{
    $basename = basename(str_replace('\\', '/', $filename));
    $stem = pathinfo($basename, PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
    $stem = preg_replace('/-[0-9a-f]{13,}\.\d+$/i', '', $stem) ?? $stem;

    return strtolower($stem . '.' . $extension);
}

$projectRoot = dirname(__DIR__, 3);
$productRoot = $projectRoot . '/assets/images/products';

if (!is_dir($productRoot)) {
    http_response_code(500);
    echo 'Product image directory not found.';
    exit;
}

$filesByOriginalKey = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($productRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $key = imageOriginalKey($file->getFilename());

    if ($key === '') {
        continue;
    }

    $filesByOriginalKey[$key][] = $file->getFilename();
}

$rows = $pdo->query(
    'SELECT id_image, image
     FROM product_images
     ORDER BY id_image ASC'
)->fetchAll();

$updated = 0;
$missing = [];
$ambiguous = [];
$update = $pdo->prepare('UPDATE product_images SET image = ? WHERE id_image = ?');

foreach ($rows as $row) {
    $idImage = (int) $row['id_image'];
    $dbImage = basename((string) $row['image']);
    $dbKey = imageOriginalKey($dbImage);
    $matches = $filesByOriginalKey[$dbKey] ?? [];

    if ($matches === []) {
        $missing[] = $dbImage;
        continue;
    }

    $uniqueMatches = array_values(array_unique($matches));

    if (count($uniqueMatches) !== 1) {
        $ambiguous[] = $dbImage;
        continue;
    }

    if ($uniqueMatches[0] !== $dbImage) {
        $update->execute([$uniqueMatches[0], $idImage]);
        $updated++;
    }
}

header('Content-Type: text/plain; charset=UTF-8');
echo 'Updated rows: ' . $updated . PHP_EOL;
echo 'Missing matches: ' . count($missing) . PHP_EOL;
echo 'Ambiguous matches: ' . count($ambiguous) . PHP_EOL;

if ($missing !== []) {
    echo PHP_EOL . 'Missing:' . PHP_EOL;
    echo implode(PHP_EOL, $missing) . PHP_EOL;
}

if ($ambiguous !== []) {
    echo PHP_EOL . 'Ambiguous:' . PHP_EOL;
    echo implode(PHP_EOL, $ambiguous) . PHP_EOL;
}
