<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$categoryId = ($categoryId !== false && $categoryId > 0) ? $categoryId : 0;
$search = trim((string) ($_GET['q'] ?? ''));
$promoOnly = isset($_GET['promo']) && $_GET['promo'] === '1';
$sort = (string) ($_GET['sort'] ?? 'newest');
$page = max(1, (int) filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT));
$perPage = 12;

$categories = getHomeCategories($pdo, $langCode);
$listing = getShopProductsListing($pdo, $langCode, [
    'category' => $categoryId,
    'search'   => $search,
    'promo'    => $promoOnly,
    'sort'     => $sort,
    'page'     => $page,
    'per_page' => 12,
]);

$products = $listing['products'];
$currentPage = (int) ($listing['page'] ?? 1);
$totalPages = (int) ($listing['total_pages'] ?? 1);
$totalProducts = (int) ($listing['total'] ?? 0);
$activeCategoryName = '';

foreach ($categories as $category) {
    if ((int) $category['id_category'] === $categoryId) {
        $activeCategoryName = $category['name'];
        break;
    }
}

$variantIds = array_column($products, 'id_variant');
$allImages = getProductImagesForVariants($pdo, $variantIds);

$filterParams = [
    'q'        => $search !== '' ? $search : null,
    'category' => $categoryId > 0 ? $categoryId : null,
    'promo'    => $promoOnly ? '1' : null,
    'sort'     => $sort !== 'newest' ? $sort : null,
];

$storeName = $settings['store_name'] ?: 'AREACH';
$pageTitle = $storeName . ' — ' . t('shop_page_title');
$metaDescription = t('meta_shop', $storeName);

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header page-header--hero page-header--shop">
        <div class="container">
            <h1 class="page-header__title"><?= t('shop_title') ?></h1>
            <?php if ($activeCategoryName !== '') : ?>
                <p class="page-header__subtitle"><?= e($activeCategoryName) ?></p>
            <?php elseif ($search !== '') : ?>
                <p class="page-header__subtitle"><?= t('shop_search_results', e($search)) ?></p>
            <?php elseif ($promoOnly) : ?>
                <p class="page-header__subtitle"><?= t('shop_active_promos') ?></p>
            <?php else : ?>
                <p class="page-header__subtitle"><?= t('shop_discover_collection') ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="page-section page-section--alt">
        <div class="container shop-layout">

            <?php
            $currentPage = max(1, (int) $currentPage);
            $perPage = max(1, (int) $perPage);
            $totalProducts = max(0, (int) $totalProducts);
            $start = ($currentPage - 1) * $perPage + 1;
            $end = min($start + $perPage - 1, $totalProducts);
            ?>

            <!-- Mobile filter toggle -->
            <button class="shop-filter-toggle" id="shop-filter-toggle" aria-controls="shop-sidebar" aria-expanded="false" type="button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="20" y2="12"/><line x1="12" y1="18" x2="20" y2="18"/></svg>
                <span><?= t('shop_filter_btn') ?></span>
            </button>

            <!-- Sidebar -->
            <aside class="shop-sidebar" id="shop-sidebar" aria-label="<?= t('shop_filter_btn') ?>">

                <!-- Search -->
                <div class="shop-sidebar__section">
                    <form method="get" action="<?= pageUrl('shop.php') ?>" role="search">
                        <div class="shop-search">
                            <svg class="shop-search__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4A2412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input
                                type="search"
                                name="q"
                                class="shop-search__input"
                                placeholder="<?= t('shop_search_placeholder') ?>"
                                value="<?= e($search) ?>"
                                aria-label="<?= t('shop_search_label') ?>"
                            >
                        </div>
                        <?php if ($categoryId > 0) : ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
                        <?php if ($promoOnly) : ?><input type="hidden" name="promo" value="1"><?php endif; ?>
                        <?php if ($sort !== 'newest') : ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
                    </form>
                </div>

                <!-- Categories -->
                <div class="shop-sidebar__section">
                    <h3 class="shop-sidebar__title"><?= t('shop_category_label') ?></h3>
                    <ul class="shop-categories">
                        <li class="shop-categories__item<?= $categoryId === 0 ? ' is-active' : '' ?>">
                            <a href="<?= shopUrl(array_merge($filterParams, ['category' => null, 'page' => null])) ?>"><?= t('shop_all_categories') ?></a>
                        </li>
                        <?php foreach ($categories as $category) : ?>
                            <?php $catId = (int) $category['id_category']; ?>
                            <li class="shop-categories__item<?= $catId === $categoryId ? ' is-active' : '' ?>">
                                <a href="<?= shopUrl(array_merge($filterParams, ['category' => $catId, 'page' => null])) ?>"><?= e($category['name']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Promo filter -->
                <div class="shop-sidebar__section">
                    <a
                        href="<?= shopUrl(array_merge($filterParams, ['promo' => $promoOnly ? null : '1', 'page' => null])) ?>"
                        class="shop-promo<?= $promoOnly ? ' is-active' : '' ?>"
                        role="checkbox"
                        aria-checked="<?= $promoOnly ? 'true' : 'false' ?>"
                    >
                        <span class="shop-promo__check">
                            <?php if ($promoOnly) : ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#FFFDFC" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php endif; ?>
                        </span>
                        <span><?= t('shop_promo_only') ?></span>
                    </a>
                </div>
            </aside>

            <!-- Main content -->
            <div class="shop-main">

                <!-- Top bar -->
                <div class="shop-topbar">
                    <p class="shop-topbar__count">
                        <?php if ($totalProducts > 0) : ?>
                            <?= sprintf(t('shop_display_count'), $start, $end, $totalProducts) ?>
                        <?php else : ?>
                            <?= t('shop_no_products') ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($totalProducts > 0) : ?>
                        <form method="get" action="<?= pageUrl('shop.php') ?>" class="shop-topbar__sort">
                            <label for="shop-sort" class="visually-hidden"><?= t('shop_sort_label') ?></label>
                            <select name="sort" id="shop-sort" class="shop-topbar__select" onchange="this.form.submit()">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= t('shop_sort_newest') ?></option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>><?= t('shop_sort_price_asc') ?></option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= t('shop_sort_price_desc') ?></option>
                            </select>
                            <?php if ($search !== '') : ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
                            <?php if ($categoryId > 0) : ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
                            <?php if ($promoOnly) : ?><input type="hidden" name="promo" value="1"><?php endif; ?>
                            <noscript><button type="submit" class="btn btn--primary"><?= t('shop_sort_label') ?></button></noscript>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($products)) : ?>
                    <div class="page-empty">
                        <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                        <p><?= t('shop_no_products') ?></p>
                        <a href="<?= shopUrl([]) ?>" class="btn btn--accent"><?= t('shop_view_all_products') ?></a>
                    </div>
                <?php else : ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product) : ?>
                            <?php
                            $productCardPromo = $promoOnly;
                            $variantId = (int) ($product['id_variant'] ?? 0);
                            $cardImages = $allImages[$variantId] ?? [];
                            include __DIR__ . '/includes/product-card.php';
                            ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1) : ?>
                        <nav class="pagination" aria-label="Pagination">
                            <?php if ($currentPage > 1) : ?>
                                <a href="<?= shopUrl(array_merge($filterParams, ['page' => $currentPage - 1])) ?>" class="pagination__link pagination__link--prev"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> <?= t('shop_prev') ?></a>
                            <?php endif; ?>
                            <ul class="pagination__list">
                                <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                    <li>
                                        <a href="<?= shopUrl(array_merge($filterParams, ['page' => $i])) ?>" class="pagination__link<?= $i === $currentPage ? ' is-active' : '' ?>" <?= $i === $currentPage ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                            <?php if ($currentPage < $totalPages) : ?>
                                <a href="<?= shopUrl(array_merge($filterParams, ['page' => $currentPage + 1])) ?>" class="pagination__link pagination__link--next"><?= t('shop_next') ?> <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<script>
(function() {
    var toggle = document.getElementById('shop-filter-toggle');
    var sidebar = document.getElementById('shop-sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            var expanded = toggle.getAttribute('aria-expanded') === 'true' ? false : true;
            toggle.setAttribute('aria-expanded', expanded);
            sidebar.classList.toggle('is-open');
            document.body.classList.toggle('shop-sidebar--open');
        });
    }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
