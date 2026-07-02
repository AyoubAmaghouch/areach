<?php

declare(strict_types=1);

$freeShipping = freeShippingText($settings);
$whatsappLink = socialUrl('whatsapp', $settings['whatsapp'] ?? '');
$facebookLink = socialUrl('facebook', $settings['facebook'] ?? '');
$instagramLink = socialUrl('instagram', $settings['instagram'] ?? '');
$tiktokLink = socialUrl('tiktok', $settings['tiktok'] ?? '');
?>

<div class="topbar">
    <div class="container topbar__inner">

        <?php if ($freeShipping !== '') : ?>
            <p class="topbar__shipping">
                <i class="fa-solid fa-truck-fast" aria-hidden="true"></i>
                <span><?= e($freeShipping) ?></span>
            </p>
        <?php endif; ?>

        <ul class="topbar__contacts">

            <?php if ($whatsappLink !== '') : ?>
                <li>
                    <a href="<?= e($whatsappLink) ?>" class="topbar__link" target="_blank" rel="noopener noreferrer">
                        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                        <span><?= e($settings['whatsapp']) ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($facebookLink !== '') : ?>
                <li>
                    <a href="<?= e($facebookLink) ?>" class="topbar__link topbar__link--icon" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($instagramLink !== '') : ?>
                <li>
                    <a href="<?= e($instagramLink) ?>" class="topbar__link topbar__link--icon" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($tiktokLink !== '') : ?>
                <li>
                    <a href="<?= e($tiktokLink) ?>" class="topbar__link topbar__link--icon" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                        <i class="fa-brands fa-tiktok" aria-hidden="true"></i>
                    </a>
                </li>
            <?php endif; ?>

        </ul>

    </div>
</div>
