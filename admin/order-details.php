<?php
// Admin Order Details - Mobile First
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$items_stmt = $conn->prepare("
    SELECT oi.*, mi.name 
    FROM order_items oi 
    JOIN menu_items mi ON oi.menu_item_id = mi.id 
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
$total = 0;
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
    $total += $item['price'] * $item['quantity'];
}
$items_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0c0907">
    <title>Order #<?php echo $order_id; ?> Details - QR Cafe</title>
    <link rel="manifest" href="../manifest.json">
    <link rel="stylesheet" href="../css/spatial.css">
</head>
<body>
    <!-- Mobile Header -->
    <header class="header">
        <div class="mobile-app-shell">
            <a href="orders.php" class="logo">
                <span>📋</span> Order #<?php echo $order_id; ?>
            </a>
            <a href="orders.php" style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 700;">← Back to Orders</a>
        </div>
    </header>

    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">
        
        <div class="spatial-card" style="padding: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px; margin-bottom: 14px;">
                <div>
                    <h2 style="font-size: 1.2rem; font-weight: 800; font-family: var(--font-serif);">Order #<?php echo $order_id; ?></h2>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">📍 Table <?php echo htmlspecialchars($order['table_number']); ?></div>
                </div>
                <span style="font-weight: 800; font-size: 0.78rem; text-transform: uppercase; padding: 4px 10px; border-radius: var(--radius-pill); background: rgba(217, 155, 38, 0.2); color: var(--primary); border: 1px solid var(--primary);">
                    <?php echo htmlspecialchars($order['status']); ?>
                </span>
            </div>

            <div style="margin-bottom: 14px; font-size: 0.88rem;">
                <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                    <span style="color: var(--text-muted);">Created At:</span>
                    <strong><?php echo htmlspecialchars($order['created_at']); ?></strong>
                </div>
                <?php if (!empty($order['customer_name'])): ?>
                    <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                        <span style="color: var(--text-muted);">Customer:</span>
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['notes'])): ?>
                    <div style="background: rgba(217,155,38,0.1); border: 1px solid var(--glass-border); padding: 8px; border-radius: var(--radius-sm); margin-top: 8px;">
                        <strong>📝 Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <h4 style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 8px;">Ordered Items:</h4>
            <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;">
                <?php foreach ($items as $item): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.88rem; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <span><?php echo htmlspecialchars($item['name']); ?> <strong style="color: var(--primary);">x<?php echo $item['quantity']; ?></strong></span>
                        <span style="font-weight: 700; color: var(--primary);">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--glass-border); padding-top: 10px; font-size: 1.1rem; font-weight: 800; margin-bottom: 20px;">
                <span>Total Amount:</span>
                <span style="color: var(--primary);">Rs. <?php echo number_format($total, 2); ?></span>
            </div>

            <!-- Manage Order Action Buttons -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                <button onclick="updateOrderStatus(<?php echo $order_id; ?>, 'preparing')" class="add-to-cart-btn" style="min-height: 44px; background: rgba(245, 158, 11, 0.2); color: #fde68a; border: 1px solid var(--warning);">
                    🔥 Start Prep
                </button>
                <button onclick="updateOrderStatus(<?php echo $order_id; ?>, 'ready')" class="add-to-cart-btn" style="min-height: 44px; background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid var(--success);">
                    ✅ Mark Ready
                </button>
                <button onclick="updateOrderStatus(<?php echo $order_id; ?>, 'completed')" class="checkout-btn" style="grid-column: span 2; min-height: 44px; padding: 10px;">
                    ✔ Mark Served / Done
                </button>
                <button onclick="updateOrderStatus(<?php echo $order_id; ?>, 'cancelled', 'Cancelled by Manager')" class="add-to-cart-btn" style="grid-column: span 2; min-height: 40px; background: rgba(244, 63, 94, 0.2); color: #fda4af; border: 1px solid var(--danger);">
                    ❌ Cancel Order
                </button>
            </div>
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
    <script>
        function updateOrderStatus(orderId, status, reason = '') {
            fetch('../api/update-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: status, reason: reason })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Order status updated!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error updating order', 'error');
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</body>
</html>
