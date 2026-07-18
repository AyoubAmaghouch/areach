<?php

declare(strict_types=1);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<section class="hero-slider-section" aria-label="<?= t('hero_aria_label') ?>">
    <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide hero-slide">
                <div class="container hero-slide__container">
                    <div class="hero-slide__content">
                        <span class="hero-slide__label"><?= t('hero_slide1_label') ?></span>
                        <h1 class="hero-slide__title"><?= t('hero_slide1_title') ?></h1>
                        <p class="hero-slide__subtitle"><?= t('hero_slide1_subtitle') ?></p>

                        <div class="hero-slide__actions">
                            <a href="shop" class="btn btn--accent hero-slide__btn">
                                <?= t('hero_slide1_btn_main') ?>
                            </a>
                            <a href="#nouveautes" class="btn btn--outline hero-slide__btn-secondary">
                                <?= t('hero_slide1_btn_sec') ?>
                            </a>
                        </div>
                    </div>

                    <div class="hero-slide__media">
                        <picture class="hero-slide__picture">
                            <img
                                src="assets/images/hero/hero-1.jpg"
                                alt="<?= t('hero_slide1_title') ?>"
                                class="hero-slide__image"
                                width="1920"
                                height="700"
                                loading="eager"
                                decoding="async"
                            >
                        </picture>
                    </div>
                </div>
            </div>

            <div class="swiper-slide hero-slide">
                <div class="container hero-slide__container">
                    <div class="hero-slide__content">
                        <span class="hero-slide__label"><?= t('hero_slide2_label') ?></span>
                        <h1 class="hero-slide__title"><?= t('hero_slide2_title') ?></h1>
                        <p class="hero-slide__subtitle"><?= t('hero_slide2_subtitle') ?></p>

                        <div class="hero-slide__actions">
                            <a href="shop" class="btn btn--accent hero-slide__btn">
                                <?= t('hero_slide2_btn_main') ?>
                            </a>
                            <a href="#nouveautes" class="btn btn--outline hero-slide__btn-secondary">
                                <?= t('hero_slide2_btn_sec') ?>
                            </a>
                        </div>
                    </div>

                    <div class="hero-slide__media">
                        <picture class="hero-slide__picture">
                            <img
                                src="assets/images/hero/hero-2.jpg"
                                alt="<?= t('hero_slide2_title') ?>"
                                class="hero-slide__image"
                                width="1920"
                                height="700"
                                loading="lazy"
                                decoding="async"
                            >
                        </picture>
                    </div>
                </div>
            </div>
        </div>

        <div class="swiper-pagination hero-swiper-pagination"></div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.hero-swiper', {
        loop: true,
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        },
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.hero-swiper-pagination',
            clickable: true,
        },
        speed: 800,
        grabCursor: true
    });
});
</script>
