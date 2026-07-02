<?php

declare(strict_types=1);

$newsletterSuccess = ($_SESSION['newsletter_flash'] ?? '') === 'success';
unset($_SESSION['newsletter_flash']);
?>

<section class="home-section newsletter" id="newsletter" aria-labelledby="newsletter-title">
    <div class="container">
        <div class="newsletter__inner">
            <div class="newsletter__content">
                <h2 class="newsletter__title" id="newsletter-title">Newsletter</h2>
                <p class="newsletter__text">
                    Inscrivez-vous pour recevoir nos nouveautés et offres exclusives.
                </p>
            </div>

            <?php if ($newsletterSuccess) : ?>
                <p class="newsletter__message newsletter__message--success" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    Merci ! Vous êtes inscrit à notre newsletter.
                </p>
            <?php endif; ?>

            <form
                class="newsletter__form"
                method="post"
                action="<?= pageUrl('index.php') ?>#newsletter"
                novalidate
            >
                <input type="hidden" name="newsletter_action" value="1">

                <label for="newsletter-email" class="visually-hidden">Adresse e-mail</label>
                <input
                    type="email"
                    id="newsletter-email"
                    name="email"
                    class="newsletter__input input"
                    placeholder="Votre adresse e-mail"
                    required
                    autocomplete="email"
                >

                <button type="submit" class="btn btn--accent newsletter__submit">
                    S'inscrire
                </button>
            </form>
        </div>
    </div>
</section>
