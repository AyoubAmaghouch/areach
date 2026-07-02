<?php

declare(strict_types=1);

if (empty($categories)) {
    return;
}

$categoryImageMap = [
    1 => 'assets/images/categories/abayas.jpg',
    2 => 'assets/images/categories/hijabs.jpg',
    3 => 'assets/images/categories/robes.jpg',
    4 => 'assets/images/categories/ensembles.jpg',
    5 => 'assets/images/categories/jupes.jpg',
];
?>

<section class="home-section home-categories" aria-labelledby="categories-title">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" id="categories-title"><?= t('home_categories') ?></h2>
        </div>

        <div class="categories-scroll">
            <ul class="categories-list">

                <?php foreach ($categories as $category) : ?>
                    <?php
                    $categoryId = (int) ($category['id_category'] ?? 0);
                    $categoryName = (string) ($category['name'] ?? '');
                    $categoryImage = $categoryImageMap[$categoryId] ?? imagePath('categories', $category['image'] ?? '');
                    ?>
                    <li>
                        <a
                            href="<?= pageUrl('shop.php?category=' . $categoryId) ?>"
                            class="category-item"
                        >
                            <span class="category-item__icon">
                                <?php if ($categoryImage !== '') : ?>
                                    <img
                                        src="<?= $categoryImage ?>"
                                        alt="<?= e($categoryName) ?>"
                                        width="120"
                                        height="120"
                                        loading="lazy"
                                    >
                                <?php endif; ?>
                            </span>
                            <span class="category-item__name"><?= e($categoryName) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>

            </ul>
        </div>
    </div>
</section>
