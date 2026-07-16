<?php
// Determine active sidebar link
if (!function_exists('isSidebarActive')) {
    function isSidebarActive(string $page): bool {
        $uri = $_SERVER['PHP_SELF'] ?? '';
        return str_contains($uri, $page);
    }
}
?>

<nav id="admin-sidebar">

    <!-- Logo -->
    <a href="<?= adminUrl('dashboard.php') ?>" class="sidebar-logo text-decoration-none">
        <div class="sidebar-logo-icon">
            <i class="fa-solid fa-store"></i>
        </div>
        <span class="sidebar-logo-text">AR<span>EACH</span></span>
    </a>

    <!-- Navigation -->
    <div class="sidebar-nav">

        <div class="sidebar-section-label">Principal</div>

        <a href="<?= adminUrl('dashboard.php') ?>" class="<?= isSidebarActive('dashboard') ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge nav-icon"></i>
            <span>Tableau de bord</span>
        </a>

        <div class="sidebar-section-label">Catalogue</div>

        <a href="<?= adminUrl('products.php') ?>" class="<?= isSidebarActive('products') ? 'active' : '' ?>">
            <i class="fa-solid fa-shirt nav-icon"></i>
            <span>Produits</span>
        </a>

        <a href="<?= adminUrl('categories.php') ?>" class="<?= isSidebarActive('categories') ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group nav-icon"></i>
            <span>Catégories</span>
        </a>

        <div class="sidebar-section-label">Ventes</div>

        <a href="<?= adminUrl('orders.php') ?>" class="<?= isSidebarActive('orders') ? 'active' : '' ?>">
            <i class="fa-solid fa-bag-shopping nav-icon"></i>
            <span>Commandes</span>
        </a>

        <a href="<?= adminUrl('customers.php') ?>" class="<?= isSidebarActive('customers') ? 'active' : '' ?>">
            <i class="fa-solid fa-users nav-icon"></i>
            <span>Clients</span>
        </a>

        <div class="sidebar-section-label">Marketing</div>

        <a href="<?= adminUrl('banners.php') ?>" class="<?= isSidebarActive('banners') ? 'active' : '' ?>">
            <i class="fa-solid fa-rectangle-ad nav-icon"></i>
            <span>Bannières</span>
        </a>

        <a href="<?= adminUrl('campaigns.php') ?>" class="<?= isSidebarActive('campaigns') ? 'active' : '' ?>">
            <i class="fa-solid fa-envelope-open-text nav-icon"></i>
            <span>Campagnes</span>
        </a>

        <a href="<?= adminUrl('notifications.php') ?>" class="<?= isSidebarActive('notifications') ? 'active' : '' ?>">
            <i class="fa-solid fa-bell nav-icon"></i>
            <span>Notifications</span>
        </a>

        <div class="sidebar-section-label">Configuration</div>

        <a href="<?= adminUrl('settings.php') ?>" class="<?= isSidebarActive('settings') ? 'active' : '' ?>">
            <i class="fa-solid fa-sliders nav-icon"></i>
            <span>Paramètres</span>
        </a>

    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="<?= adminUrl('logout.php') ?>">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Déconnexion</span>
        </a>
    </div>

</nav>