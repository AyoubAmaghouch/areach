<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$categoryId = ($categoryId !== false && $categoryId > 0) ? $categoryId : 0;
$search     = trim((string) ($_GET['q'] ?? ''));
$sort       = (string) ($_GET['sort'] ?? 'newest');
$page       = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;

// Force sort to newest by default on this page
$allowedSorts = ['newest', 'price_asc', 'price_desc'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}

$categories = getHomeCategories($pdo, $langCode);
$listing    = getShopProductsListing($pdo, $langCode, [
    'category' => $categoryId,
    'search'   => $search,
    'sort'     => $sort,
    'page'     => $page,
    'per_page' => 12,
]);

$products       = $listing['products'];
$currentPage    = $listing['page'];
$totalPages     = $listing['total_pages'];
$totalProducts  = $listing['total'];
$activeCategoryName = '';

foreach ($categories as $category) {
    if ((int) $category['id_category'] === $categoryId) {
        $activeCategoryName = $category['name'];
        break;
    }
}

// Build URL params for pagination / filter links (nouveautes.php specific)
$filterParams = [
    'q'        => $search !== '' ? $search : null,
    'category' => $categoryId > 0 ? $categoryId : null,
    'sort'     => $sort !== 'newest' ? $sort : null,
];

$pageTitle       = ($settings['store_name'] ?: 'AREACH') . ' — Nouveautés';
$metaDescription = 'Découvrez les dernières nouveautés de ' . ($settings['store_name'] ?: 'AREACH') . '. Les produits les plus récents de notre collection.';

// Helper to build nouveautes.php URLs
function nouveautesUrl(array $params = []): string
{
    $query = [];
    foreach (['q', 'category', 'sort', 'page'] as $key) {
        if (!array_key_exists($key, $params)) {
            continue;
        }
        $value = $params[$key];
        if ($value === null || $value === '' || $value === false) {
            continue;
        }
        $query[$key] = $value;
    }
    $url = pageUrl('nouveautes.php');
    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">

    <!-- ===== Small Hero ===== -->
    <section class="nouveautes-hero" aria-label="Nouveautés">
        <div class="nouveautes-hero__bg" aria-hidden="true"></div>
        <div class="nouveautes-hero__particles" aria-hidden="true">
            <span></span><span></span><span></span>
            <span></span><span></span><span></span>
        </div>
        <div class="container">
            <div class="nouveautes-hero__inner">
                <span class="nouveautes-hero__eyebrow">Collection <?= date('Y') ?></span>
                <h1 class="nouveautes-hero__title">Nouveautés</h1>
                <p class="nouveautes-hero__subtitle">
                    <?php if ($activeCategoryName !== '') : ?>
                        Les dernières arrivées dans <strong><?= e($activeCategoryName) ?></strong>
                    <?php elseif ($search !== '') : ?>
                        Résultats pour &laquo;&nbsp;<?= e($search) ?>&nbsp;&raquo;
                    <?php else : ?>
                        Découvrez en avant-première nos toutes dernières créations
                    <?php endif; ?>
                </p>
                <div class="nouveautes-hero__stats">
                    <div class="nouveautes-hero__stat">
                        <span class="nouveautes-hero__stat-value"><?= (int) $totalProducts ?></span>
                        <span class="nouveautes-hero__stat-label">produit<?= $totalProducts > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="nouveautes-hero__stat-divider" aria-hidden="true"></div>
                    <div class="nouveautes-hero__stat">
                        <span class="nouveautes-hero__stat-value"><?= count($categories) ?></span>
                        <span class="nouveautes-hero__stat-label">catégorie<?= count($categories) > 1 ? 's' : '' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Filters & Grid ===== -->
    <section class="page-section" id="nouveautes-grid">
        <div class="container">

            <!-- Toolbar -->
            <form method="get" action="<?= pageUrl('nouveautes.php') ?>" class="shop-toolbar" id="nouveautes-toolbar">
                <div class="shop-toolbar__row">
                    <div class="shop-toolbar__search">
                        <label for="nv-search" class="visually-hidden">Rechercher</label>
                        <input
                            type="search"
                            id="nv-search"
                            name="q"
                            class="input shop-toolbar__input"
                            placeholder="Rechercher une nouveauté..."
                            value="<?= e($search) ?>"
                        >
                    </div>
                    <div class="shop-toolbar__field">
                        <label for="nv-category" class="visually-hidden">Catégorie</label>
                        <select name="category" id="nv-category" class="input shop-toolbar__select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $category) : ?>
                                <option
                                    value="<?= (int) $category['id_category'] ?>"
                                    <?= $categoryId === (int) $category['id_category'] ? 'selected' : '' ?>
                                ><?= e($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="shop-toolbar__field">
                        <label for="nv-sort" class="visually-hidden">Trier</label>
                        <select name="sort" id="nv-sort" class="input shop-toolbar__select">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Plus récents</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn--primary shop-toolbar__submit" id="nv-filter-submit">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                        Filtrer
                    </button>
                </div>
            </form>

            <!-- Category pills -->
            <?php if (!empty($categories)) : ?>
                <div class="shop-filters" role="navigation" aria-label="Filtres par catégorie">
                    <a
                        href="<?= nouveautesUrl(array_merge($filterParams, ['category' => null, 'page' => null])) ?>"
                        class="shop-filter<?= $categoryId === 0 ? ' is-active' : '' ?>"
                    >Tous</a>
                    <?php foreach ($categories as $category) : ?>
                        <a
                            href="<?= nouveautesUrl(array_merge($filterParams, ['category' => (int) $category['id_category'], 'page' => null])) ?>"
                            class="shop-filter<?= $categoryId === (int) $category['id_category'] ? ' is-active' : '' ?>"
                        ><?= e($category['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Result count -->
            <p class="shop-results">
                <strong><?= (int) $totalProducts ?></strong>
                produit<?= $totalProducts > 1 ? 's' : '' ?> trouvé<?= $totalProducts > 1 ? 's' : '' ?>
            </p>

            <!-- Product grid -->
            <?php if (empty($products)) : ?>
                <div class="page-empty">
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    <p>Aucune nouveauté trouvée<?= ($search !== '' || $categoryId > 0) ? ' pour ces critères' : '' ?>.</p>
                    <?php if ($search !== '' || $categoryId > 0) : ?>
                        <a href="<?= pageUrl('nouveautes.php') ?>" class="btn btn--accent">
                            Voir toutes les nouveautés
                        </a>
                    <?php else : ?>
                        <a href="<?= pageUrl('shop.php') ?>" class="btn btn--accent">
                            Voir la boutique
                        </a>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="product-grid" id="nouveautes-product-grid">
                    <?php foreach ($products as $product) : ?>
                        <?php include __DIR__ . '/includes/shop-product-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1) : ?>
                    <nav class="pagination" aria-label="Pagination des nouveautés">
                        <?php if ($currentPage > 1) : ?>
                            <a
                                href="<?= nouveautesUrl(array_merge($filterParams, ['page' => $currentPage - 1])) ?>"
                                class="pagination__link pagination__link--prev"
                                aria-label="Page précédente"
                            >
                                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                                Précédent
                            </a>
                        <?php endif; ?>

                        <ul class="pagination__list">
                            <?php
                            // Smart pagination: show first, last, current ±2
                            $range = 2;
                            $pages = [];
                            for ($i = 1; $i <= $totalPages; $i++) {
                                if (
                                    $i === 1 ||
                                    $i === $totalPages ||
                                    ($i >= $currentPage - $range && $i <= $currentPage + $range)
                                ) {
                                    $pages[] = $i;
                                }
                            }
                            $prev = null;
                            foreach ($pages as $i) :
                                if ($prev !== null && $i - $prev > 1) : ?>
                                    <li><span class="pagination__ellipsis" aria-hidden="true">&hellip;</span></li>
                                <?php endif; ?>
                                <li>
                                    <a
                                        href="<?= nouveautesUrl(array_merge($filterParams, ['page' => $i])) ?>"
                                        class="pagination__link<?= $i === $currentPage ? ' is-active' : '' ?>"
                                        <?= $i === $currentPage ? 'aria-current="page"' : '' ?>
                                        aria-label="Page <?= $i ?>"
                                    ><?= $i ?></a>
                                </li>
                            <?php
                                $prev = $i;
                            endforeach; ?>
                        </ul>

                        <?php if ($currentPage < $totalPages) : ?>
                            <a
                                href="<?= nouveautesUrl(array_merge($filterParams, ['page' => $currentPage + 1])) ?>"
                                class="pagination__link pagination__link--next"
                                aria-label="Page suivante"
                            >
                                Suivant
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
