<?php
require_once '../config/database.php';
require_once '../config/session.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {

        $error = "Veuillez remplir tous les champs.";

    } else {

        $sql  = "SELECT * FROM admins WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {

            $_SESSION['admin_id']    = $admin['id_admin'];
            $_SESSION['admin_name']  = $admin['nom'];
            $_SESSION['admin_email'] = $admin['email'];

            header("Location: dashboard");
            exit;

        } else {

            $error = "Email ou mot de passe incorrect.";

        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AREACH Admin — Connexion</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="login-page">

<div class="login-card">

    <!-- Logo -->
    <div class="login-logo">
        <div class="login-logo-icon">
            <i class="fa-solid fa-store"></i>
        </div>
        <h1 class="login-title">AREACH Admin</h1>
        <p class="login-subtitle">Connectez-vous à votre espace d'administration</p>
    </div>

    <!-- Error Alert -->
    <?php if (!empty($error)): ?>
        <div class="flash-alert error mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" novalidate>

        <div class="mb-4">
            <label for="email" class="form-label fw-semibold">
                <i class="fa-solid fa-envelope me-2 text-muted"></i>Email
            </label>
            <input
                type="email"
                class="form-control form-control-lg"
                id="email"
                name="email"
                placeholder="admin@example.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required
                autocomplete="username">
        </div>

        <div class="mb-4">
            <label for="password" class="form-label fw-semibold">
                <i class="fa-solid fa-lock me-2 text-muted"></i>Mot de passe
            </label>
            <div class="input-group input-group-lg">
                <input
                    type="password"
                    class="form-control border-end-0"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password">
                <button
                    type="button"
                    class="input-group-text bg-white border-start-0"
                    id="toggle-password"
                    style="cursor:pointer;"
                    title="Afficher/masquer">
                    <i class="fa-solid fa-eye text-muted" id="eye-icon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg fw-semibold" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;border-radius:10px;">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Se connecter
        </button>

    </form>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('toggle-password').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>

</body>
</html>
