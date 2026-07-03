<?php

declare(strict_types=1);

if (!isset($settings, $currentLang)) {
    throw new RuntimeException('Front office context is not initialized.');
}

$pageTitle = $pageTitle ?? ($settings['store_name'] ?: 'AREACH');
$metaDescription = $metaDescription ?? ($settings['store_name'] ?: 'AREACH');
$htmlLang = e($currentLang['code']);
$htmlDir = strtolower($currentLang['direction'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= $htmlLang ?>" dir="<?= e($htmlDir) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($metaDescription) ?>">
    <title><?= e($pageTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>

<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?>>

<a class="skip-link" href="#main-content">Aller au contenu principal</a>

<div class="site-wrapper">
