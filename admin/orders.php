<?php
// Admin Orders Management - Mobile Card Queue
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database not connected.");
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

if ($status_filter !== 'all') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
}

$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0c0907">
    <title>Manage Orders - QR Cafe</title>
    <link rel="manifest" href="../manifest.json">
    <link rel="stylesheet" href="../css/spatial.css">
</head>
<body>
    <!-- Mobile Header -->
    <header class="header">
        <div class="mobile-app-shell">
            <a href="index.php" class="logo">
                <span>📋</span> Manage Live Orders
            </a>
            <a href="index.php" style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 700;">Dashboard →</a>
        </div>
    </header>

    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">

        <!-- Admin Quick Nav Tabs -->
        <div class="category-nav-scroll" style="margin-bottom: 14px;">
            <a href="orders.php?status=all" class="category-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Orders</a>
            <a href="orders.php?status=new" class="category-btn <?php echo $status_filter === 'new' ? 'active' : ''; ?>">🆕 New</a>
            <a href="orders.php?status=preparing" class="category-btn <?php echo $status_filter === 'preparing' ? 'active' : ''; ?>">🔥 Prep</a>
            <a href="orders.php?status=ready" class="category-btn <?php echo $status_filter === 'ready' ? 'active' : ''; ?>">✅ Ready</a>
            <a href="orders.php?status=completed" class="category-btn <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">✔ Completed</a>
            <a href="orders.php?status=cancelled" class="category-btn <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">🚫 Cancelled</a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (empty($orders)): ?>
                <div class="spatial-card" style="padding: 30px; text-align: center; color: var(--text-muted);">
                    <div style="font-size: 2.5rem; margin-bottom: 6px;">📋</div>
                    <h3>No orders found</h3>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="spatial-card" style="padding: 14px; display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--glass-border); padding-bottom: 8px; margin-bottom: 8px;">
                            <div>
                                <div style="font-weight: 800; font-size: 1rem; color: var(--text-primary);">Order #<?php echo $order['id']; ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">📍 Table <?php echo htmlspecialchars($order['table_number']); ?> • <?php echo htmlspecialchars($order['created_at']); ?></div>
                            </div>
                            <span style="font-weight: 800; font-size: 0.72rem; text-transform: uppercase; padding: 2px 8px; border-radius: var(--radius-pill); background: rgba(217, 155, 38, 0.2); color: var(--primary); border: 1px solid var(--primary);">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                            <div style="font-size: 0.95rem; font-weight: 800; color: var(--primary);">
                                Total: Rs. <?php echo number_format($order['total_amount'] ?? 0, 2); ?>
                            </div>

                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="add-to-cart-btn" style="padding: 6px 14px; font-size: 0.78rem;">
                                View & Edit →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- PINNED MOBILE BOTTOM NAVIGATION BAR FOR ADMIN -->
    <nav class="mobile-nav-bar">
        <a href="index.php" class="mobile-nav-item">
            <span class="mobile-nav-icon">📊</span>
            <span>Summary</span>
        </a>
        <a href="orders.php" class="mobile-nav-item active">
            <span class="mobile-nav-icon">📋</span>
            <span>Orders</span>
        </a>
        <a href="menu-items.php" class="mobile-nav-item">
            <span class="mobile-nav-icon">🍔</span>
            <span>Items</span>
        </a>
        <a href="../kitchen-dashboard.php" class="mobile-nav-item">
            <span class="mobile-nav-icon">👨‍🍳</span>
            <span>KDS</span>
        </a>
    </nav>

    <script src="../js/modern.js"></script>
</body>
</html>
