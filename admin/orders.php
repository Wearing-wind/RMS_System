<?php
// Admin - Orders Manager
require_once '../config.php';
requireAdminLogin();
require_once 'header.php';

$conn = getDBConnection();

// Filter status
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Fetch orders with item breakdown
if ($status_filter !== 'all') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
}

renderAdminHeader('orders', 'Orders Management');
?>

<div style="display: flex; gap: 10px; margin-bottom: 24px; overflow-x: auto;">
    <a href="orders.php?status=all" class="category-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Orders</a>
    <a href="orders.php?status=new" class="category-btn <?php echo $status_filter === 'new' ? 'active' : ''; ?>">🆕 New</a>
    <a href="orders.php?status=preparing" class="category-btn <?php echo $status_filter === 'preparing' ? 'active' : ''; ?>">🔥 Preparing</a>
    <a href="orders.php?status=ready" class="category-btn <?php echo $status_filter === 'ready' ? 'active' : ''; ?>">✅ Ready</a>
    <a href="orders.php?status=completed" class="category-btn <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">✔ Served</a>
    <a href="orders.php?status=cancelled" class="category-btn <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">🚫 Cancelled</a>
</div>

<div class="spatial-card" style="padding: 24px;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Table</th>
                <th>Customer</th>
                <th>Items Ordered</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($o = $result->fetch_assoc()): ?>
                    <?php
                    // Fetch items
                    $items_stmt = $conn->prepare("
                        SELECT oi.*, mi.name 
                        FROM order_items oi 
                        JOIN menu_items mi ON oi.menu_item_id = mi.id 
                        WHERE oi.order_id = ?
                    ");
                    $items_stmt->bind_param("i", $o['id']);
                    $items_stmt->execute();
                    $items_res = $items_stmt->get_result();
                    $itemList = [];
                    while ($it = $items_res->fetch_assoc()) {
                        $itemList[] = $it['quantity'] . 'x ' . htmlspecialchars($it['name']);
                    }
                    $items_stmt->close();
                    ?>
                    <tr>
                        <td><strong style="color: var(--text-primary);">#<?php echo $o['id']; ?></strong></td>
                        <td>📍 Table <?php echo htmlspecialchars($o['table_number']); ?></td>
                        <td><?php echo htmlspecialchars($o['customer_name'] ?: 'Guest'); ?></td>
                        <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo implode(', ', $itemList); ?></td>
                        <td style="color: var(--primary); font-weight: 800;">Rs. <?php echo number_format($o['total_amount'], 2); ?></td>
                        <td>
                            <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: var(--radius-pill); background: rgba(198,124,78,0.2); color: var(--primary); border: 1px solid var(--primary);"><?php echo strtoupper($o['status']); ?></span>
                        </td>
                        <td>
                            <span style="font-size: 0.75rem; color: <?php echo ($o['payment_method'] === 'cash' ? '#4ade80' : 'var(--text-muted)'); ?>;">
                                <?php echo ucfirst($o['payment_method'] ?? 'pending'); ?>
                            </span>
                        </td>
                        <td>
                            <a href="order-details.php?id=<?php echo $o['id']; ?>" class="add-to-cart-btn" style="text-decoration: none; padding: 4px 12px; font-size: 0.75rem;">Details →</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">No orders found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
renderAdminFooter();
?>
