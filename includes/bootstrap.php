<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$frontOffice = initFrontOffice($pdo);
$settings = $frontOffice['settings'];
$languages = $frontOffice['languages'];
$currentLang = $frontOffice['currentLang'];
$langCode = $currentLang['code'];

function renderLayoutStart(string $pageTitle, string $metaDescription = ''): void
{
    global $settings, $languages, $currentLang;

    $metaDescription = $metaDescription ?: ($settings['store_name'] ?: 'AREACH');

    include __DIR__ . '/header.php';
    include __DIR__ . '/navbar.php';

    echo '<main id="main-content" class="main-content">';
}

function renderLayoutEnd(): void
{
    echo '</main>';
    include __DIR__ . '/footer.php';
}
