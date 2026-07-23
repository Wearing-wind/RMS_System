<?php
// Admin Dashboard - Main Overview Page
require_once '../config.php';
requireAdminLogin();
require_once 'header.php';

$conn = getDBConnection();

// Get stats
$today = date('Y-m-d');

// Total orders today
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today'");
$today_orders = $result->fetch_assoc()['count'];

// Total completed orders today
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today' AND status = 'completed'");
$completed_orders = $result->fetch_assoc()['count'];

// Total sales today
$result = $conn->query("SELECT SUM(oi.quantity * oi.price) as total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) = '$today'");
$today_sales = $result->fetch_assoc()['total'] ?: 0;

// Total orders overall
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$total_orders = $result->fetch_assoc()['count'];

// Recent 10 Orders
$recent_result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8");

// Top Selling Items
$top_items_result = $conn->query("
    SELECT mi.name, SUM(oi.quantity) as total_quantity, SUM(oi.quantity * oi.price) as total_sales 
    FROM order_items oi 
    JOIN menu_items mi ON oi.menu_item_id = mi.id 
    GROUP BY mi.id 
    ORDER BY total_quantity DESC 
    LIMIT 5
");

renderAdminHeader('dashboard', 'Dashboard & Overview');
?>

<!-- High Level Metrics Grid -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon">💰</div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Sales Today</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: var(--primary);">Rs. <?php echo number_format($today_sales, 2); ?></div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">📦</div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Orders Today</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary);"><?php echo $today_orders; ?> <span style="font-size: 0.8rem; color: var(--success);">(<?php echo $completed_orders; ?> done)</span></div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon">📊</div>
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Lifetime Orders</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary);"><?php echo $total_orders; ?></div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Recent Orders Card -->
    <div class="spatial-card" style="padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 1.1rem; font-weight: 800;">📋 Recent Live Orders</h3>
            <a href="orders.php" style="color: var(--primary); font-size: 0.85rem; font-weight: 700; text-decoration: none;">View All →</a>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Table</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                    <?php while ($o = $recent_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong style="color: var(--text-primary);">#<?php echo $o['id']; ?></strong></td>
                            <td>Table <?php echo htmlspecialchars($o['table_number']); ?></td>
                            <td style="color: var(--primary); font-weight: 700;">Rs. <?php echo number_format($o['total_amount'], 2); ?></td>
                            <td>
                                <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: var(--radius-pill); background: rgba(198,124,78,0.2); color: var(--primary); border: 1px solid var(--primary);"><?php echo strtoupper($o['status']); ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No orders recorded yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top Selling Dishes Card -->
    <div class="spatial-card" style="padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px;">🔥 Top Popular Dishes</h3>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Dish Name</th>
                    <th>Qty Sold</th>
                    <th>Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($top_items_result && $top_items_result->num_rows > 0): ?>
                    <?php while ($top = $top_items_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong style="color: var(--text-primary);"><?php echo htmlspecialchars($top['name']); ?></strong></td>
                            <td style="font-weight: 700; color: var(--primary);"><?php echo $top['total_quantity']; ?>x</td>
                            <td style="color: var(--success); font-weight: 700;">Rs. <?php echo number_format($top['total_sales'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No sales data available yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
renderAdminFooter();
?>
