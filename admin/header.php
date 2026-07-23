<?php
// Unified Admin Header & Navigation Bar
function renderAdminHeader($active_page = 'dashboard', $page_title = 'Dashboard') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    <link rel="stylesheet" href="../css/spatial.css">
</head>
<body class="kitchen-body">
    <div class="admin-shell">
        <!-- Professional Sidebar Nav -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-logo">
                <span>☕</span> QR Cafe Admin
            </div>
            
            <a href="index.php" class="admin-nav-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                <span>📊</span> Dashboard
            </a>
            <a href="orders.php" class="admin-nav-item <?php echo $active_page === 'orders' ? 'active' : ''; ?>">
                <span>📋</span> Orders
            </a>
            <a href="menu-items.php" class="admin-nav-item <?php echo $active_page === 'menu-items' ? 'active' : ''; ?>">
                <span>🍽️</span> Menu Items
            </a>
            <a href="categories.php" class="admin-nav-item <?php echo $active_page === 'categories' ? 'active' : ''; ?>">
                <span>🏷️</span> Categories
            </a>
            <a href="tables.php" class="admin-nav-item <?php echo $active_page === 'tables' ? 'active' : ''; ?>">
                <span>📍</span> Tables & QRs
            </a>
            <a href="payment-settings.php" class="admin-nav-item <?php echo $active_page === 'payment-settings' ? 'active' : ''; ?>">
                <span>💳</span> Payment QR
            </a>
            <a href="../kitchen-dashboard.php" target="_blank" class="admin-nav-item">
                <span>👨‍🍳</span> Kitchen View ↗
            </a>
            <a href="../menu.php?table=1" target="_blank" class="admin-nav-item">
                <span>📱</span> Customer App ↗
            </a>
            <a href="logout.php" class="admin-nav-item" style="margin-top: auto; color: var(--danger);">
                <span>🚪</span> Logout
            </a>
        </aside>
        
        <!-- Main Content Wrapper -->
        <main class="admin-main">
            <div class="admin-top-bar">
                <h1 style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-serif); color: var(--text-primary);"><?php echo htmlspecialchars($page_title); ?></h1>
                <div style="font-size: 0.85rem; color: var(--text-muted);">Administrator Portal</div>
            </div>
<?php
}

function renderAdminFooter() {
?>
        </main>
    </div>
</body>
</html>
<?php
}
?>
