<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    if ($email !== '' && $password !== '') {
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer && password_verify($password, (string) ($customer['password'] ?? ''))) {
            $_SESSION['customer'] = [
                'id_customer' => (int) $customer['id_customer'],
                'nom' => $customer['nom'] ?? '',
                'prenom' => $customer['prenom'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => $customer['telephone'] ?? '',
            ];

            if ($remember) {
                setcookie('remember_customer', $email, time() + 60 * 60 * 24 * 30, '/');
            } else {
                setcookie('remember_customer', '', time() - 3600, '/');
            }

            redirect(pageUrl('profile.php'));
        }
    }

    $_SESSION['login_error'] = 'Identifiants invalides.';
    redirect(pageUrl('login.php'));
}

if (isset($_GET['logout'])) {
    unset($_SESSION['customer']);
    setcookie('remember_customer', '', time() - 3600, '/');
    redirect(pageUrl('login.php'));
}

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Connexion';
$metaDescription = 'Connectez-vous à votre compte';

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title">Connexion</h1>
            <p class="page-header__subtitle">Accédez à votre espace client</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container form-shell">
            <form method="post" action="<?= pageUrl('login.php') ?>" class="checkout-form">
                <?php if (!empty($_SESSION['login_error'])) : ?>
                    <p class="form-error"><?= e($_SESSION['login_error']) ?></p>
                    <?php unset($_SESSION['login_error']); ?>
                <?php endif; ?>

                <div class="product-detail__field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="input" required>
                </div>
                <div class="product-detail__field">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" class="input" required>
                </div>
                <label class="checkout-checkbox">
                    <input type="checkbox" name="remember" value="1">
                    <span>Se souvenir de moi</span>
                </label>
                <button type="submit" class="btn btn--primary">Se connecter</button>
                <p class="form-link"><a href="<?= pageUrl('register.php') ?>">Créer un compte</a></p>
            </form>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>