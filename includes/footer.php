<?php

declare(strict_types=1);

$footerSettings = isset($settings) && is_array($settings) ? $settings : [];
$storeName = $footerSettings['store_name'] ?: 'AREACH';
$emailLink = !empty($footerSettings['email']) ? 'mailto:' . $footerSettings['email'] : '';
$instagramLink = socialUrl('instagram', $footerSettings['instagram'] ?? '');
$copyrightYear = date('Y');
$newsletterSuccess = ($_SESSION['newsletter_flash'] ?? '') === 'success';
unset($_SESSION['newsletter_flash']);
?>

</div><!-- /.site-wrapper -->

<footer class="site-footer" id="footer" role="contentinfo">
    <div class="site-footer__bg"></div>
    <div class="site-footer__overlay"></div>

    <div class="container site-footer__inner">

        <!-- ── Newsletter ── -->
        <div class="footer-newsletter" aria-labelledby="footer-newsletter-title">
            <h3 class="footer-newsletter__title" id="footer-newsletter-title"><?= t('footer_newsletter_title') ?></h3>
            <p class="footer-newsletter__text"><?= t('footer_newsletter_text') ?></p>

            <?php if ($newsletterSuccess) : ?>
                <p class="footer-newsletter__msg footer-newsletter__msg--ok" role="status">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12" /></svg>
                    <?= t('footer_newsletter_success') ?>
                </p>
            <?php endif; ?>

            <form class="footer-newsletter__form" method="post" action="<?= pageUrl('index.php') ?>#footer" novalidate>
                <input type="hidden" name="newsletter_action" value="1">
                <label for="footer-newsletter-email" class="visually-hidden"><?= t('footer_newsletter_placeholder') ?></label>
                <input
                    type="email"
                    id="footer-newsletter-email"
                    name="email"
                    class="footer-newsletter__input input"
                    placeholder="<?= t('footer_newsletter_placeholder') ?>"
                    required
                    autocomplete="email"
                >
                <button type="submit" class="footer-newsletter__btn"><?= t('footer_newsletter_submit') ?></button>
            </form>
        </div>

        <!-- ── Columns ── -->
        <div class="footer-cols">

            <!-- Brand -->
            <div class="footer-col footer-col--brand">
                <span class="footer-brand__name"><?= t('footer_brand_title') ?></span>
                <span class="footer-brand__tagline"><?= t('footer_brand_tagline') ?></span>
                <p class="footer-brand__desc"><?= t('footer_brand_desc') ?></p>
            </div>

            <!-- Contact -->
            <div class="footer-col">
                <h3 class="footer-col__title"><?= t('footer_infos_title') ?></h3>
                <ul class="footer-col__list">
                    <?php if (!empty($footerSettings['telephone'])) : ?>
                        <li>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <a href="tel:<?= e($footerSettings['telephone']) ?>"><?= e($footerSettings['telephone']) ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($footerSettings['email'])) : ?>
                        <li>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <a href="<?= e($emailLink) ?>"><?= e($footerSettings['email']) ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($footerSettings['address'])) : ?>
                        <li>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span><?= nl2br(e($footerSettings['address'])) ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Navigation -->
            <div class="footer-col">
                <h3 class="footer-col__title"><?= t('footer_nav_title') ?></h3>
                <ul class="footer-col__list footer-col__list--links">
                    <li><a href="<?= pageUrl('index.php') ?>"><?= t('nav_home') ?></a></li>
                    <li><a href="<?= pageUrl('shop.php') ?>"><?= t('nav_shop') ?></a></li>
                    <li><a href="<?= pageUrl('promotions.php') ?>"><?= t('nav_promotions') ?></a></li>
                    <li><a href="<?= pageUrl('cart.php') ?>"><?= t('nav_cart') ?></a></li>
                </ul>
            </div>

            <!-- Instagram -->
            <div class="footer-col">
                <h3 class="footer-col__title"><?= t('footer_social_title') ?></h3>
                <?php if ($instagramLink !== '') : ?>
                    <a href="<?= e($instagramLink) ?>" target="_blank" rel="noopener noreferrer" class="footer-instagram" aria-label="Instagram">
                        <svg class="footer-instagram__icon" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        <span class="footer-instagram__label">Instagram</span>
                        <span class="footer-instagram__text"><?= t('footer_instagram_text') ?></span>
                    </a>
                <?php else : ?>
                    <div class="footer-instagram footer-instagram--disabled">
                        <svg class="footer-instagram__icon" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        <span class="footer-instagram__label">Instagram</span>
                        <span class="footer-instagram__text"><?= t('footer_instagram_text') ?></span>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ── Trust Row ── -->
        <div class="footer-trust">
            <div class="footer-trust__item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                <span><?= t('footer_trust_shipping') ?></span>
            </div>
            <div class="footer-trust__sep" aria-hidden="true"></div>
            <div class="footer-trust__item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                <span><?= t('footer_trust_payment') ?></span>
            </div>
            <div class="footer-trust__sep" aria-hidden="true"></div>
            <div class="footer-trust__item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                <span><?= t('footer_trust_quality') ?></span>
            </div>
            <div class="footer-trust__sep" aria-hidden="true"></div>
            <div class="footer-trust__item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CFA968" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span><?= t('footer_trust_support') ?></span>
            </div>
        </div>

        <!-- ── Copyright ── -->
        <div class="footer-copyright">
            <p>&copy; <?= e($copyrightYear) ?> <?= e($storeName) ?>. <?= t('footer_copyright') ?></p>
        </div>

    </div>
</footer>

<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>