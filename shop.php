<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$categoryId = ($categoryId !== false && $categoryId > 0) ? $categoryId : 0;
$search = trim((string) ($_GET['q'] ?? ''));
$promoOnly = isset($_GET['promo']) && $_GET['promo'] === '1';
$sort = (string) ($_GET['sort'] ?? 'newest');
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;

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
$currentPage = $listing['page'];
$totalPages = $listing['total_pages'];
$totalProducts = $listing['total'];
$activeCategoryName = '';

foreach ($categories as $category) {
    if ((int) $category['id_category'] === $categoryId) {
        $activeCategoryName = $category['name'];
        break;
    }
}

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
    <section class="page-header">
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

    <section class="page-section">
        <div class="container">
            <form method="get" action="<?= pageUrl('shop.php') ?>" class="shop-toolbar">
                <div class="shop-toolbar__row">
                    <div class="shop-toolbar__search">
                        <label for="shop-search" class="visually-hidden"><?= t('shop_search_label') ?></label>
                        <input type="search" id="shop-search" name="q" class="input shop-toolbar__input" placeholder="<?= t('shop_search_placeholder') ?>" value="<?= e($search) ?>">
                    </div>
                    <div class="shop-toolbar__field">
                        <label for="shop-category" class="visually-hidden"><?= t('shop_category_label') ?></label>
                        <select name="category" id="shop-category" class="input shop-toolbar__select">
                            <option value=""><?= t('shop_all_categories') ?></option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?= (int) $category['id_category'] ?>" <?= $categoryId === (int) $category['id_category'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="shop-toolbar__field">
                        <label for="shop-sort" class="visually-hidden"><?= t('shop_sort_label') ?></label>
                        <select name="sort" id="shop-sort" class="input shop-toolbar__select">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= t('shop_sort_newest') ?></option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>><?= t('shop_sort_price_asc') ?></option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= t('shop_sort_price_desc') ?></option>
                        </select>
                    </div>
                    <label class="shop-toolbar__promo">
                        <input type="checkbox" name="promo" value="1" <?= $promoOnly ? 'checked' : '' ?>>
                        <span><?= t('shop_promo_only') ?></span>
                    </label>
                    <button type="submit" class="btn btn--primary shop-toolbar__submit"><?= t('shop_filter_btn') ?></button>
                </div>
            </form>

            <?php if (!empty($categories)) : ?>
                <div class="shop-filters">
                    <a href="<?= shopUrl(array_merge($filterParams, ['category' => null, 'page' => null])) ?>" class="shop-filter<?= $categoryId === 0 ? ' is-active' : '' ?>"><?= t('shop_all') ?></a>
                    <?php foreach ($categories as $category) : ?>
                        <a href="<?= shopUrl(array_merge($filterParams, ['category' => (int) $category['id_category'], 'page' => null])) ?>" class="shop-filter<?= $categoryId === (int) $category['id_category'] ? ' is-active' : '' ?>"><?= e($category['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="shop-results">
                <?= $totalProducts > 1
                    ? t('shop_results_plural', (int) $totalProducts)
                    : t('shop_results', (int) $totalProducts) ?>
            </p>

            <?php if (empty($products)) : ?>
                <div class="page-empty">
                    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                    <p><?= t('shop_no_products') ?></p>
                    <a href="<?= shopUrl([]) ?>" class="btn btn--accent"><?= t('shop_view_all_products') ?></a>
                </div>
            <?php else : ?>
                <div class="product-grid">
                    <?php foreach ($products as $product) : ?>
                        <?php include __DIR__ . '/includes/shop-product-card.php'; ?>
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
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
