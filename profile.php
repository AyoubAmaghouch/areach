<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (empty($_SESSION['customer']['id_customer'])) {
    redirect(pageUrl('login.php'));
}

$customerId = (int) $_SESSION['customer']['id_customer'];
$customer = null;
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id_customer = ? LIMIT 1');
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['profile_action'] ?? '');

    if ($action === 'profile') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

        $updateData = [
            'nom' => $lastName,
            'prenom' => $firstName,
            'email' => $email,
            'telephone' => $phone,
        ];

        $columns = [];
        $colStmt = $pdo->query('SHOW COLUMNS FROM customers');
        foreach ($colStmt->fetchAll(PDO::FETCH_COLUMN) as $column) {
            $columns[] = $column;
        }

        $filtered = [];
        foreach ($updateData as $key => $value) {
            if (in_array($key, $columns, true)) {
                $filtered[$key] = $value;
            }
        }

        if ($filtered !== []) {
            $pairs = [];
            foreach ($filtered as $key => $value) {
                $pairs[] = '`' . $key . '` = ?';
            }
            $stmt = $pdo->prepare('UPDATE customers SET ' . implode(', ', $pairs) . ' WHERE id_customer = ?');
            $stmt->execute(array_values($filtered) + [$customerId]);
        }

        $_SESSION['customer']['nom'] = $lastName;
        $_SESSION['customer']['prenom'] = $firstName;
        $_SESSION['customer']['email'] = $email;
        $_SESSION['customer']['phone'] = $phone;
    }

    if ($action === 'password') {
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        if ($newPassword !== '' && $newPassword === $confirmPassword) {
            $pdo->prepare('UPDATE customers SET password = ? WHERE id_customer = ?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), $customerId]);
        }
    }

    redirect(pageUrl('profile.php'));
}

$orders = [];
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id_customer = ? ORDER BY id_order DESC');
$stmt->execute([$customerId]);
$orders = $stmt->fetchAll();

$pageTitle = ($settings['store_name'] ?: 'AREACH') . ' — Profil';
$metaDescription = 'Votre espace client';

include 'includes/header.php';
include 'includes/topbar.php';
include 'includes/navbar.php';
?>

<main id="main-content" class="main-content">
    <section class="page-header">
        <div class="container">
            <h1 class="page-header__title">Profil</h1>
            <p class="page-header__subtitle">Gérez vos informations personnelles</p>
        </div>
    </section>

    <section class="page-section">
        <div class="container profile-layout">
            <div class="checkout-form">
                <h2 class="section-title">Informations personnelles</h2>
                <form method="post" action="<?= pageUrl('profile.php') ?>">
                    <input type="hidden" name="profile_action" value="profile">
                    <div class="product-detail__field">
                        <label for="first_name">Prénom</label>
                        <input type="text" id="first_name" name="first_name" class="input" value="<?= e($customer['prenom'] ?? '') ?>">
                    </div>
                    <div class="product-detail__field">
                        <label for="last_name">Nom</label>
                        <input type="text" id="last_name" name="last_name" class="input" value="<?= e($customer['nom'] ?? '') ?>">
                    </div>
                    <div class="product-detail__field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="input" value="<?= e($customer['email'] ?? '') ?>">
                    </div>
                    <div class="product-detail__field">
                        <label for="phone">Téléphone</label>
                        <input type="tel" id="phone" name="phone" class="input" value="<?= e($customer['telephone'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn--primary">Enregistrer</button>
                </form>
            </div>

            <div class="checkout-form">
                <h2 class="section-title">Changer le mot de passe</h2>
                <form method="post" action="<?= pageUrl('profile.php') ?>">
                    <input type="hidden" name="profile_action" value="password">
                    <div class="product-detail__field">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" class="input">
                    </div>
                    <div class="product-detail__field">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="input">
                    </div>
                    <button type="submit" class="btn btn--outline">Mettre à jour</button>
                </form>
            </div>
        </div>
    </section>

    <section class="page-section page-section--alt">
        <div class="container">
            <h2 class="section-title">Historique des commandes</h2>
            <?php if (empty($orders)) : ?>
                <p>Aucune commande pour le moment.</p>
            <?php else : ?>
                <div class="cart-items">
                    <?php foreach ($orders as $order) : ?>
                        <article class="cart-item">
                            <div class="cart-item__body">
                                <h3 class="cart-item__name">Commande #<?= (int) ($order['id_order'] ?? 0) ?></h3>
                                <p class="cart-item__meta">Statut : <?= e($order['status'] ?? 'pending') ?></p>
                                <p class="cart-item__meta">Total : <?= e(formatCurrency((float) ($order['total'] ?? 0))) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>