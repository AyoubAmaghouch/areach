<?php

declare(strict_types=1);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<section class="hero-slider-section" aria-label="Bannières principales">
    <div class="swiper hero-swiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide hero-slide">
                <div class="container hero-slide__container">
                    <div class="hero-slide__content">
                        <span class="hero-slide__label">Collection printemps</span>
                        <h1 class="hero-slide__title">L'élégance au féminin</h1>
                        <p class="hero-slide__subtitle">Découvrez notre nouvelle collection pensée pour sublimer votre style au quotidien.</p>

                        <div class="hero-slide__actions">
                            <a href="shop.php" class="btn btn--accent hero-slide__btn">
                                Découvrir la collection
                            </a>
                            <a href="nouveautes.php" class="btn btn--outline hero-slide__btn-secondary">
                                Voir les nouveautés
                            </a>
                        </div>
                    </div>

                    <div class="hero-slide__media">
                        <picture class="hero-slide__picture">
                            <img
                                src="assets/images/hero/hero-1.jpg"
                                alt="L'élégance au féminin"
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
                        <span class="hero-slide__label">Collection Exclusive</span>
                        <h1 class="hero-slide__title">Collection Exclusive</h1>
                        <p class="hero-slide__subtitle">Des pièces élégantes conçues pour révéler votre style avec raffinement.</p>

                        <div class="hero-slide__actions">
                            <a href="shop.php" class="btn btn--accent hero-slide__btn">
                                Acheter maintenant
                            </a>
                            <a href="nouveautes.php" class="btn btn--outline hero-slide__btn-secondary">
                                Voir les nouveautés
                            </a>
                        </div>
                    </div>

                    <div class="hero-slide__media">
                        <picture class="hero-slide__picture">
                            <img
                                src="assets/images/hero/hero-2.jpg"
                                alt="Collection Exclusive"
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

        <button
            type="button"
            class="swiper-button-prev hero-swiper-button hero-swiper-button--prev"
            aria-label="Bannière précédente"
        >
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>

        <button
            type="button"
            class="swiper-button-next hero-swiper-button hero-swiper-button--next"
            aria-label="Bannière suivante"
        >
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
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
        navigation: {
            nextEl: '.hero-swiper-button--next',
            prevEl: '.hero-swiper-button--prev',
        },
        speed: 800,
        grabCursor: true
    });
});
</script>
