<?php

declare(strict_types=1);

$footerSettings = isset($settings) && is_array($settings) ? $settings : [];
$footerLogoPath = imagePath('settings', $footerSettings['logo'] ?? '');
$storeName = $footerSettings['store_name'] ?? 'AREACH';
$phoneLink = phoneUrl($footerSettings['telephone'] ?? '');
$emailLink = !empty($footerSettings['email']) ? 'mailto:' . $footerSettings['email'] : '';
$facebookLink = socialUrl('facebook', $footerSettings['facebook'] ?? '');
$instagramLink = socialUrl('instagram', $footerSettings['instagram'] ?? '');
$tiktokLink = socialUrl('tiktok', $footerSettings['tiktok'] ?? '');
$copyrightYear = date('Y');
?>

</div><!-- /.site-wrapper -->

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">

            <div class="footer-col footer-col--brand">
                <a href="<?= pageUrl('index.php') ?>" class="footer__brand" aria-label="<?= e($storeName) ?> — Accueil">

                    <?php if ($footerLogoPath !== '') : ?>
                        <img
                            src="<?= $footerLogoPath ?>"
                            alt="<?= e($storeName) ?>"
                            class="footer__logo"
                            width="140"
                            height="48"
                            loading="lazy"
                        >
                    <?php else : ?>
                        <span class="footer__brand-text"><?= e($storeName) ?></span>
                    <?php endif; ?>

                </a>

                <?php if (!empty($footerSettings['store_name'])) : ?>
                    <p class="footer__store-name"><?= e($footerSettings['store_name']) ?></p>
                <?php endif; ?>
            </div>

            <div class="footer-col">
                <h3 class="footer__title">Informations</h3>
                <ul class="footer__list">

                    <?php if (!empty($footerSettings['address'])) : ?>
                        <li>
                            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                            <span><?= nl2br(e($footerSettings['address'])) ?></span>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($footerSettings['telephone'])) : ?>
                        <li>
                            <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            <a href="<?= e($phoneLink) ?>"><?= e($footerSettings['telephone']) ?></a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($footerSettings['email'])) : ?>
                        <li>
                            <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                            <a href="<?= e($emailLink) ?>"><?= e($footerSettings['email']) ?></a>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer__title">Navigation</h3>
                <ul class="footer__list footer__list--links">
                    <li><a href="<?= pageUrl('index.php') ?>">Accueil</a></li>
                    <li><a href="<?= pageUrl('shop.php') ?>">Boutique</a></li>
                    <li><a href="<?= pageUrl('promotions.php') ?>">Promotions</a></li>
                    <li><a href="<?= pageUrl('cart.php') ?>">Panier</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer__title">Suivez-nous</h3>

                <ul class="footer__social">

                    <?php if ($facebookLink !== '') : ?>
                        <li>
                            <a href="<?= e($facebookLink) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                                <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($instagramLink !== '') : ?>
                        <li>
                            <a href="<?= e($instagramLink) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($tiktokLink !== '') : ?>
                        <li>
                            <a href="<?= e($tiktokLink) ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                                <i class="fa-brands fa-tiktok" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>

        </div>

        <div class="footer__bottom">
            <p class="footer__copyright">
                &copy; <?= e($copyrightYear) ?>
                <?php if (!empty($footerSettings['store_name'])) : ?>
                    <?= e($footerSettings['store_name']) ?>.
                <?php else : ?>
                    AREACH.
                <?php endif; ?>
                Tous droits réservés.
            </p>
        </div>
    </div>
</footer>

<script src="<?= asset('js/app.js') ?>" defer></script>
</body>

</html>
