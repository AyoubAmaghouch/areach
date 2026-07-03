<?php

declare(strict_types=1);
?>

<section class="home-section home-about" id="about-areach" aria-labelledby="about-areach-title">
    <div class="container">
        <div class="about-areach__layout">
            <div class="about-areach__content">
                <span class="about-areach__eyebrow"><?= t('home_about_eyebrow') ?></span>
                <h2 class="about-areach__title" id="about-areach-title"><?= t('home_about_title') ?></h2>
                <div class="about-areach__body">
                    <p><?= t('home_about_p1') ?></p>
                    <p><?= t('home_about_p2') ?></p>
                </div>
                <div class="about-areach__divider" role="presentation"></div>
                <p class="about-areach__signature"><?= t('home_about_signature') ?></p>
                <div class="about-areach__values">
                    <div class="about-areach__value">
                        <span class="about-areach__value-title"><?= t('home_about_value_1_title') ?></span>
                        <p class="about-areach__value-desc"><?= t('home_about_value_1_desc') ?></p>
                    </div>
                    <div class="about-areach__value">
                        <span class="about-areach__value-title"><?= t('home_about_value_2_title') ?></span>
                        <p class="about-areach__value-desc"><?= t('home_about_value_2_desc') ?></p>
                    </div>
                    <div class="about-areach__value">
                        <span class="about-areach__value-title"><?= t('home_about_value_3_title') ?></span>
                        <p class="about-areach__value-desc"><?= t('home_about_value_3_desc') ?></p>
                    </div>
                </div>
            </div>
            <div class="about-areach__visual">
                <div class="about-areach__image-frame" aria-hidden="true"></div>
                <picture class="about-areach__picture">
                    <img
                        src="assets/images/about/about.png"
                        alt="<?= t('home_about_title') ?>"
                        class="about-areach__image"
                        loading="lazy"
                    >
                </picture>
            </div>
        </div>
    </div>
</section>
