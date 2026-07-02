<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

function insertCustomer(PDO $pdo, array $data): int
{
    $columns = [];
    $stmt = $pdo->query('SHOW COLUMNS FROM customers');
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $column) {
        $columns[] = $column;
    }

    $filtered = [];
    foreach ($data as $key => $value) {
        if (in_array($key, $columns, true)) {
            $filtered[$key] = $value;
        }
    }

    if ($filtered === []) {
        return 0;
    }

    $columnList = implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', array_keys($filtered)));
    $placeholders = implode(', ', array_fill(0, count($filtered), '?'));
    $stmt = $pdo->prepare('INSERT INTO customers (' . $columnList . ') VALUES (' . $placeholders . ')');
    $stmt->execute(array_values($filtered));

    return (int) $pdo->lastInsertId();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');;
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $newsletter = !empty($_POST['newsletter']) ? 1 : 0;

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $password !== $confirmPassword) {
        $_SESSION['register_error'] = 'Veuillez vérifier vos informations.';
        redirect(pageUrl('register.php'));
    }

    $stmt = $pdo->prepare('SELECT id_customer FROM customers WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['register_error'] = 'Cet email est déjà utilisé.';
        redirect(pageUrl('register.php'));
    }

    $customerId = insertCustomer($pdo, [
        'nom' => $lastName,
        'prenom' => $firstName,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'newsletter' => $newsletter,
        'status' => 1,
    ]);

    if ($customerId > 0) {
        $_SESSION['customer'] = [
            'id_customer' => $customerId,
            'nom' => $lastName,
            'prenom' => $firstName,
            'email' => $email,
        ];
        redirect(pageUrl('profile.php'));
    }

    $_SESSION['register_error'] = 'Impossible de créer le compte.';
    redirect(pageUrl('register.php'));
}

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Inscription';
$metaDescription = 'Créer un compte client';

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title">Inscription</h1>
            <p class="page-header__subtitle">Créez votre compte client</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container form-shell">
            <form method="post" action="<?= pageUrl('register.php') ?>" class="checkout-form">
                <?php if (!empty($_SESSION['register_error'])) : ?>
                    <p class="form-error"><?= e($_SESSION['register_error']) ?></p>
                    <?php unset($_SESSION['register_error']); ?>
                <?php endif; ?>

                <div class="product-detail__field">
                    <label for="first_name">Prénom</label>
                    <input type="text" id="first_name" name="first_name" class="input" required>
                </div>
                <div class="product-detail__field">
                    <label for="last_name">Nom</label>
                    <input type="text" id="last_name" name="last_name" class="input" required>
                </div>
                <div class="product-detail__field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="input" required>
                </div>
                <div class="product-detail__field">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" class="input" required>
                </div>
                <div class="product-detail__field">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="input" required>
                </div>
                <label class="checkout-checkbox">
                    <input type="checkbox" name="newsletter" value="1">
                    <span>Recevoir les offres et nouveautés</span>
                </label>
                <button type="submit" class="btn btn--primary">Créer mon compte</button>
                <p class="form-link"><a href="<?= pageUrl('login.php') ?>">J’ai déjà un compte</a></p>
            </form>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>