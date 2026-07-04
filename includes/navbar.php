<?php

declare(strict_types=1);

global $settings, $languages, $currentLang;

if (!isset($settings) || !is_array($settings)) {
    $settings = ['store_name' => 'AREACH', 'logo' => ''];
}

if (!isset($languages) || !is_array($languages)) {
    $languages = [];
}

if (!isset($currentLang) || !is_array($currentLang)) {
    $currentLang = ['code' => 'fr', 'direction' => 'ltr'];
}

$cartCount = getCartCount();
$currentPage = basename((string) ($_SERVER['PHP_SELF'] ?? ''));

function areachNavActive(string $page, string $currentPage): string
{
    return $page === $currentPage ? ' areach-navbar__link--active' : '';
}
?><header class="areach-header" id="site-header">
    <div class="areach-navbar">
        <div class="areach-navbar__inner">
            <div class="areach-navbar__left">
                <ul class="areach-navbar__menu">
                    <li><a href="<?= pageUrl('index.php') ?>" class="areach-navbar__link<?= areachNavActive('index.php', $currentPage) ?>"><?= t('nav_home') ?></a></li>
                    <li><a href="<?= pageUrl('shop.php') ?>" class="areach-navbar__link<?= areachNavActive('shop.php', $currentPage) ?>"><?= t('nav_shop') ?></a></li>
                    <li><a href="<?= pageUrl('promotions.php') ?>" class="areach-navbar__link<?= areachNavActive('promotions.php', $currentPage) ?>"><?= t('nav_promotions') ?></a></li>
                </ul>
            </div>

            <a href="<?= pageUrl('index.php') ?>" class="areach-brand" aria-label="<?= e($settings['store_name'] ?: 'AREACH') ?> — <?= t('nav_home_aria') ?>">
                <span class="areach-wordmark">AREACH</span>
                <span class="areach-tagline">Elegant Luxury Defined</span>
            </a>

            <div class="areach-navbar__right">
                <?php if (!empty($languages)) : ?>
                    <div class="areach-navbar__lang" id="language-dropdown">
                        <button type="button" class="areach-navbar__lang-toggle" id="language-toggle" aria-expanded="false" aria-haspopup="listbox" aria-controls="language-list">
                            <img src="<?= asset('images/logo/logo.png') ?>" alt="" width="18" height="18" class="areach-navbar__lang-icon" aria-hidden="true">
                            <span class="areach-navbar__lang-code"><?= e(strtoupper((string) ($currentLang['code'] ?? 'fr'))) ?></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="areach-navbar__lang-chevron" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <ul class="areach-navbar__lang-list" id="language-list" role="listbox" aria-label="<?= t('nav_choose_lang') ?>">
                            <?php foreach ($languages as $language) : ?>
                                <li role="option" aria-selected="<?= ($language['code'] ?? '') === ($currentLang['code'] ?? '') ? 'true' : 'false' ?>">
                                    <a href="<?= e(languageSwitchUrl((string) ($language['code'] ?? 'fr'))) ?>" class="areach-navbar__lang-option<?= (($language['code'] ?? '') === ($currentLang['code'] ?? '')) ? ' is-active' : '' ?>" lang="<?= e((string) ($language['code'] ?? '')) ?>">
                                        <?= e((string) ($language['name'] ?? '')) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <a href="<?= pageUrl('cart.php') ?>" class="areach-navbar__cart" aria-label="<?= t('nav_cart') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <?php if ($cartCount > 0) : ?>
                        <span class="areach-navbar__cart-count"><?= (int) $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <button type="button" class="areach-mobile-toggle" id="mobile-menu-toggle" aria-expanded="false" aria-controls="primary-navigation" aria-label="<?= t('nav_open_menu') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    <nav class="areach-mobile-nav" id="primary-navigation" aria-label="<?= t('nav_home') ?>">
        <ul class="areach-mobile-nav__menu">
            <li><a href="<?= pageUrl('index.php') ?>" class="areach-mobile-nav__link<?= areachNavActive('index.php', $currentPage) ?>"><?= t('nav_home') ?></a></li>
            <li><a href="<?= pageUrl('shop.php') ?>" class="areach-mobile-nav__link<?= areachNavActive('shop.php', $currentPage) ?>"><?= t('nav_shop') ?></a></li>
            <li><a href="<?= pageUrl('promotions.php') ?>" class="areach-mobile-nav__link<?= areachNavActive('promotions.php', $currentPage) ?>"><?= t('nav_promotions') ?></a></li>
        </ul>
    </nav>
</header>

<div class="areach-overlay" id="mobile-nav-overlay" hidden></div>
