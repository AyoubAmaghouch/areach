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

$logoPath = imagePath('settings', $settings['logo'] ?? '');
$storeName = $settings['store_name'] ?: 'AREACH';
$cartCount = getCartCount();
$currentPage = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
$customerSession = $_SESSION['customer'] ?? null;

function isNavActive(string $page, string $currentPage): string
{
    return $page === $currentPage ? ' is-active' : '';
}
?>

<header class="site-header" id="site-header">
    <div class="container navbar">
        <a href="<?= pageUrl('index.php') ?>" class="navbar__brand" aria-label="<?= e($storeName) ?> — <?= t('nav_home') ?>">

            <?php if ($logoPath !== '') : ?>
                <img
                    src="<?= $logoPath ?>"
                    alt="<?= e($storeName) ?>"
                    class="navbar__logo"
                    width="140"
                    height="48"
                >
            <?php else : ?>
                <span class="navbar__brand-text"><?= e($storeName) ?></span>
            <?php endif; ?>

        </a>

        <button
            type="button"
            class="navbar__toggle"
            id="mobile-menu-toggle"
            aria-expanded="false"
            aria-controls="primary-navigation"
            aria-label="<?= t('nav_open_menu') ?>"
        >
            <span class="navbar__toggle-bar"></span>
            <span class="navbar__toggle-bar"></span>
            <span class="navbar__toggle-bar"></span>
        </button>

        <nav class="navbar__nav" id="primary-navigation" aria-label="<?= t('nav_home') ?>">
            <ul class="navbar__menu">
                <li><a href="<?= pageUrl('index.php') ?>" class="navbar__link<?= isNavActive('index.php', $currentPage) ?>"><?= t('nav_home') ?></a></li>
                <li><a href="<?= pageUrl('shop.php') ?>" class="navbar__link<?= isNavActive('shop.php', $currentPage) ?>"><?= t('nav_shop') ?></a></li>
                <li><a href="<?= pageUrl('promotions.php') ?>" class="navbar__link<?= isNavActive('promotions.php', $currentPage) ?>"><?= t('nav_promotions') ?></a></li>
            </ul>
        </nav>

        <div class="navbar__actions">

            <button
                type="button"
                class="navbar__action-btn"
                id="search-toggle"
                aria-expanded="false"
                aria-controls="search-panel"
                aria-label="<?= t('nav_search') ?>"
            >
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            </button>

            <?php if (!empty($languages)) : ?>
                <div class="navbar__lang" id="language-dropdown">
                    <button
                        type="button"
                        class="navbar__action-btn navbar__lang-toggle"
                        id="language-toggle"
                        aria-expanded="false"
                        aria-haspopup="listbox"
                        aria-controls="language-list"
                    >
                        <i class="fa-solid fa-globe" aria-hidden="true"></i>
                        <span class="navbar__lang-code"><?= e(strtoupper((string) ($currentLang['code'] ?? 'fr'))) ?></span>
                        <i class="fa-solid fa-chevron-down navbar__lang-chevron" aria-hidden="true"></i>
                    </button>

                    <ul class="navbar__lang-list" id="language-list" role="listbox" aria-label="<?= t('nav_choose_lang') ?>">
                        <?php foreach ($languages as $language) : ?>
                            <li role="option" aria-selected="<?= ($language['code'] ?? '') === ($currentLang['code'] ?? '') ? 'true' : 'false' ?>">
                                <a
                                    href="<?= e(languageSwitchUrl((string) ($language['code'] ?? 'fr'))) ?>"
                                    class="navbar__lang-option<?= (($language['code'] ?? '') === ($currentLang['code'] ?? '')) ? ' is-active' : '' ?>"
                                    lang="<?= e((string) ($language['code'] ?? '')) ?>"
                                >
                                    <?= e((string) ($language['name'] ?? '')) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <a href="<?= pageUrl('cart.php') ?>" class="navbar__action-btn navbar__cart<?= isNavActive('cart.php', $currentPage) ?>" aria-label="<?= t('nav_cart') ?>">
                <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                <?php if ($cartCount > 0) : ?>
                    <span class="navbar__cart-count"><?= (int) $cartCount ?></span>
                <?php endif; ?>
            </a>

            <?php if (!empty($customerSession)) : ?>
                <a href="<?= pageUrl('profile.php') ?>" class="navbar__action-btn<?= isNavActive('profile.php', $currentPage) ?>" aria-label="<?= t('nav_profile') ?>">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                </a>
            <?php else : ?>
                <a href="<?= pageUrl('login.php') ?>" class="navbar__action-btn<?= isNavActive('login.php', $currentPage) ?>" aria-label="<?= t('nav_login') ?>">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                </a>
            <?php endif; ?>

        </div>
    </div>

    <div class="search-panel" id="search-panel" hidden>
        <div class="container">
            <form action="<?= pageUrl('shop.php') ?>" method="get" class="search-panel__form" role="search">
                <label for="search-input" class="visually-hidden"><?= t('nav_search_product') ?></label>
                <input
                    type="search"
                    id="search-input"
                    name="q"
                    class="search-panel__input"
                    placeholder="<?= t('nav_search_placeholder') ?>"
                    autocomplete="off"
                >
                <button type="submit" class="btn btn--primary search-panel__submit">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span><?= t('nav_search') ?></span>
                </button>
            </form>
        </div>
    </div>
</header>

<div class="mobile-nav-overlay" id="mobile-nav-overlay" hidden></div>
