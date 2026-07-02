<?php

declare(strict_types=1);

if (empty($promotionBanner)) {
    return;
}

$bannerImage = imagePath('campaigns', $promotionBanner['image'] ?? '');
$bannerLink = trim((string) ($promotionBanner['button_link'] ?? ''));
?>

<section class="home-section promo-banner" aria-labelledby="promo-banner-title">
    <div class="promo-banner__inner<?= $bannerImage !== '' ? ' promo-banner__inner--has-image' : '' ?>">

        <?php if ($bannerImage !== '') : ?>
            <img
                src="<?= $bannerImage ?>"
                alt=""
                class="promo-banner__image"
                width="1920"
                height="400"
                loading="lazy"
            >
            <span class="promo-banner__overlay" aria-hidden="true"></span>
        <?php endif; ?>

        <div class="container promo-banner__content">
            <?php if (!empty($promotionBanner['subject'])) : ?>
                <h2 class="promo-banner__title" id="promo-banner-title">
                    <?= e($promotionBanner['subject']) ?>
                </h2>
            <?php endif; ?>

            <?php if (!empty($promotionBanner['button_text'])) : ?>
                <?php
                $bannerLink = $bannerLink !== '' ? $bannerLink : pageUrl('promotions.php');
                ?>
                <a href="<?= e($bannerLink) ?>" class="btn btn--accent promo-banner__btn">
                    <?= e($promotionBanner['button_text']) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
