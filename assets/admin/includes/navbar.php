<?php
// Build breadcrumb based on current page
$page = basename($_SERVER['PHP_SELF'], '.php');
$dir  = basename(dirname($_SERVER['PHP_SELF']));

$breadcrumbs = ['Accueil' => adminUrl('dashboard.php')];

$pageLabels = [
    'dashboard'       => 'Tableau de bord',
    'products'        => 'Produits',
    'categories'      => 'Catégories',
    'orders'          => 'Commandes',
    'customers'       => 'Clients',
    'banners'         => 'Bannières',
    'campaigns'       => 'Campagnes',
    'notifications'   => 'Notifications',
    'settings'        => 'Paramètres',
    'profile'         => 'Profil',
    'create'          => 'Nouveau',
    'edit'            => 'Modifier',
    'details'         => 'Détails',
    'update-status'   => 'Changer le statut',
    'manage-variants' => 'Variantes',
];

// Add parent if in a sub-directory of 'crud'
$sectionMap = [
    'products'      => ['label' => 'Produits', 'url' => 'products.php'],
    'orders'        => ['label' => 'Commandes', 'url' => 'orders.php'],
    'categories'    => ['label' => 'Catégories', 'url' => 'categories.php'],
    'customers'     => ['label' => 'Clients', 'url' => 'customers.php'],
    'banners'       => ['label' => 'Bannières', 'url' => 'banners.php'],
];

if (isset($sectionMap[$dir])) {
    $breadcrumbs[$sectionMap[$dir]['label']] = adminUrl($sectionMap[$dir]['url']);
}

$currentLabel = $pageLabels[$page] ?? ucfirst($page);

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
$initials  = strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1));
?>

<header id="admin-topbar">

    <!-- Toggle Sidebar -->
    <button id="sidebar-toggle" onclick="toggleSidebar()" title="Menu">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Breadcrumb -->
    <div class="topbar-breadcrumb">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <?php foreach ($breadcrumbs as $label => $url): ?>
                    <li class="breadcrumb-item">
                        <a href="<?= htmlspecialchars($url) ?>">
                            <?= $label === 'Accueil' ? '<i class="fa-solid fa-house fa-sm"></i>' : htmlspecialchars($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($currentLabel) ?></li>
            </ol>
        </nav>
    </div>

    <!-- User Info -->
    <div class="topbar-user">
        <div class="topbar-user-avatar" title="<?= $adminName ?>">
            <?= $initials ?>
        </div>
        <span class="topbar-user-name d-none d-sm-inline"><?= $adminName ?></span>
        <a href="<?= adminUrl('logout.php') ?>" class="topbar-logout" title="Déconnexion">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="d-none d-md-inline">Déconnexion</span>
        </a>
    </div>

</header>

<script>
function toggleSidebar() {
    const sidebar  = document.getElementById('admin-sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const isOpen   = sidebar.classList.contains('open');
    if (isOpen) {
        sidebar.classList.remove('open');
        overlay.style.display = 'none';
    } else {
        sidebar.classList.add('open');
        overlay.style.display = 'block';
    }
}
function closeSidebar() {
    document.getElementById('admin-sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').style.display = 'none';
}
</script>