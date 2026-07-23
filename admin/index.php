<?php
// Admin Dashboard - Main Page
require_once '../config.php';
requireAdminLogin();

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

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$total_orders = $result->fetch_assoc()['count'];

// Weekly stats
$result = $conn->query("SELECT COUNT(*) as count, SUM(oi.quantity * oi.price) as total FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$weekly_stats = $result->fetch_assoc();
$weekly_orders = $weekly_stats['count'] ?: 0;
$weekly_sales = $weekly_stats['total'] ?: 0;

// Monthly stats
$result = $conn->query("SELECT COUNT(*) as count, SUM(oi.quantity * oi.price) as total FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$monthly_stats = $result->fetch_assoc();
$monthly_orders = $monthly_stats['count'] ?: 0;
$monthly_sales = $monthly_stats['total'] ?: 0;

// Get daily orders for the last 7 days for chart
$daily_sales = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $result = $conn->query("SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE DATE(o.created_at) = '$day'");
    $daily_sales[] = $result->fetch_assoc()['total'] ?: 0;
}

// Get category distribution
$category_sales = [];
$result = $conn->query("
    SELECT c.name, SUM(oi.quantity * oi.price) as total 
    FROM order_items oi 
    JOIN menu_items mi ON oi.menu_item_id = mi.id 
    JOIN categories c ON mi.category_id = c.id 
    GROUP BY c.id 
    ORDER BY total DESC
    LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $category_sales[] = $row;
}

// Top selling items
$result = $conn->query("
    SELECT mi.name, SUM(oi.quantity) as total_quantity, SUM(oi.quantity * oi.price) as total_sales 
    FROM order_items oi 
    JOIN menu_items mi ON oi.menu_item_id = mi.id 
    GROUP BY mi.id 
    ORDER BY total_quantity DESC 
    LIMIT 5
");
$top_items = [];
while ($row = $result->fetch_assoc()) {
    $top_items[] = $row;
}

// Recent orders
$recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QR Restaurant</title>
    <link rel="stylesheet" href="../css/modern.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f6fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .admin-header { background: linear-gradient(135deg, #ff6b35, #ff8c5a); padding: 15px 0; }
        .admin-header .container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-logo { color: white; font-size: 1.5rem; font-weight: bold; text-decoration: none; }
        .admin-nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .admin-nav a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 10px 18px; border-radius: 25px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); color: white; }
        .admin-content { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .admin-content h1 { color: #2d3436; margin-bottom: 25px; font-size: 2rem; }
        .admin-content h2 { color: #2d3436; margin: 25px 0 15px; }
        .admin-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .admin-table table { width: 100%; border-collapse: collapse; }
        .admin-table th { background: #ff6b35; color: white; padding: 15px; text-align: left; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #dfe6e9; }
        .admin-table tr:hover { background: #f8f9fa; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; font-weight: 600; }
        .btn-primary { background: #ff6b35; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; }
        .actions { display: flex; gap: 10px; }
        .order-status { padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem; }
        .order-status.new { background: #ff7675; color: white; }
        .order-status.preparing { background: #fdcb6e; color: #2d3436; }
        .order-status.ready { background: #00b894; color: white; }
        .order-status.completed { background: #00b894; color: white; }
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .admin-stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .admin-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }
        
        .admin-stat-card.orders::before { background: var(--primary); }
        .admin-stat-card.completed::before { background: var(--success); }
        .admin-stat-card.sales::before { background: #00b894; }
        .admin-stat-card.total::before { background: #6c5ce7; }
        
        .admin-stat-card:hover {
            transform: translateY(-5px);
        }
        
        .admin-stat-card h3 {
            color: var(--gray);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .admin-stat-card .number {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--secondary);
        }
        
        .admin-stat-card .number.highlight {
            color: var(--primary);
        }
        
        .admin-stat-card .trend {
            font-size: 0.85rem;
            margin-top: 8px;
            color: var(--gray);
        }
        
        .admin-stat-card .trend.up {
            color: var(--success);
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
        }
        
        .chart-card h3 {
            margin-bottom: 20px;
            color: var(--secondary);
        }
        
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .top-item:last-child {
            border-bottom: none;
        }
        
        .top-item-name {
            font-weight: 600;
            color: var(--secondary);
        }
        
        .top-item-qty {
            color: var(--primary);
            font-weight: 700;
        }
        
        .top-item-sales {
            color: var(--success);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-action-btn {
            background: var(--light);
            border: none;
            padding: 20px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
        }
        
        .quick-action-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        .quick-action-btn .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .quick-action-btn .label {
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <a href="index.php" class="admin-logo">🍽️ Admin Panel</a>
            <nav class="admin-nav">
                <a href="index.php" class="active">Dashboard</a>
                <a href="menu-items.php">Menu Items</a>
                <a href="categories.php">Categories</a>
                <a href="tables.php">Tables & QR</a>
                <a href="orders.php">Orders</a>
                <a href="payment-settings.php">Payment Settings</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Admin Content -->
    <section class="admin-content">
        <div class="container">
            <h1>📊 Dashboard</h1>
            
            <!-- Stats -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card orders">
                    <h3>📋 Orders Today</h3>
                    <div class="number"><?php echo $today_orders; ?></div>
                    <div class="trend up">↑ <?php echo $completed_orders; ?> completed</div>
                </div>
                <div class="admin-stat-card completed">
                    <h3>✅ Completed Today</h3>
                    <div class="number"><?php echo $completed_orders; ?></div>
                    <div class="trend">of <?php echo $today_orders; ?> total</div>
                </div>
                <div class="admin-stat-card sales">
                    <h3>💰 Sales Today</h3>
                    <div class="number highlight">Rs. <?php echo number_format($today_sales, 0); ?></div>
                </div>
                <div class="admin-stat-card total">
                    <h3>📈 This Month</h3>
                    <div class="number"><?php echo $monthly_orders; ?></div>
                    <div class="trend">Rs. <?php echo number_format($monthly_sales, 0); ?> revenue</div>
                </div>
            </div>
            
            <!-- Visual Bar Chart -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>📊 Daily Sales (Last 7 Days)</h3>
                    <div class="bar-chart">
                        <?php 
                        $max_sale = max($daily_sales);
                        if ($max_sale == 0) $max_sale = 1;
                        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        $today_day = date('N') - 1;
                        for ($i = 6; $i >= 0; $i--) {
                            $day_index = ($today_day - $i + 7) % 7;
                            $height = ($daily_sales[6-$i] / $max_sale) * 100;
                        ?>
                        <div class="bar-item">
                            <div class="bar-container">
                                <div class="bar" style="height: <?php echo $height; ?>%"></div>
                            </div>
                            <div class="bar-label"><?php echo $days[$day_index]; ?></div>
                            <div class="bar-value">Rs. <?php echo number_format($daily_sales[6-$i], 0); ?></div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>📈 Sales by Category</h3>
                    <?php if (count($category_sales) > 0): ?>
                        <?php $max_cat_values = array_column($category_sales, 'total'); $max_cat = max($max_cat_values); if ($max_cat == 0) $max_cat = 1; ?>
                        <?php foreach ($category_sales as $cat): ?>
                            <div class="category-bar">
                                <div class="category-label"><?php echo htmlspecialchars($cat['name']); ?></div>
                                <div class="category-bar-bg">
                                    <div class="category-bar-fill" style="width: <?php echo ($cat['total'] / $max_cat) * 100; ?>%"></div>
                                </div>
                                <div class="category-value">Rs. <?php echo number_format($cat['total'], 0); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--gray);">No category data yet</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <style>
                .bar-chart {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-end;
                    height: 200px;
                    padding-top: 20px;
                }
                .bar-item {
                    flex: 1;
                    text-align: center;
                    margin: 0 5px;
                }
                .bar-container {
                    height: 150px;
                    background: #f0f0f0;
                    border-radius: 8px 8px 0 0;
                    display: flex;
                    align-items: flex-end;
                    overflow: hidden;
                }
                .bar {
                    width: 100%;
                    background: linear-gradient(180deg, #ff6b35, #ff8c5a);
                    border-radius: 8px 8px 0 0;
                    min-height: 5px;
                    transition: height 0.5s ease;
                }
                .bar-label {
                    font-size: 0.75rem;
                    font-weight: 600;
                    color: var(--gray);
                    margin-top: 8px;
                }
                .bar-value {
                    font-size: 0.65rem;
                    color: var(--secondary);
                    font-weight: 600;
                }
                .category-bar {
                    margin-bottom: 15px;
                }
                .category-label {
                    font-weight: 600;
                    color: var(--secondary);
                    margin-bottom: 5px;
                }
                .category-bar-bg {
                    height: 25px;
                    background: #f0f0f0;
                    border-radius: 12px;
                    overflow: hidden;
                }
                .category-bar-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #00b894, #55efc4);
                    border-radius: 12px;
                    transition: width 0.5s ease;
                }
                .category-value {
                    font-size: 0.85rem;
                    color: var(--gray);
                    margin-top: 3px;
                    text-align: right;
                }
            </style>
            
            <!-- Charts & Top Items -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>🏆 Top Selling Items</h3>
                    <?php if (count($top_items) > 0): ?>
                        <?php foreach ($top_items as $index => $item): ?>
                            <div class="top-item">
                                <span class="top-item-name"><?php echo ($index + 1) . '. ' . htmlspecialchars($item['name']); ?></span>
                                <div>
                                    <span class="top-item-qty"><?php echo $item['total_quantity']; ?> sold</span>
                                    <span class="top-item-sales">Rs. <?php echo number_format($item['total_sales'], 0); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--gray);">No sales data yet</p>
                    <?php endif; ?>
                </div>
                
                <div class="chart-card">
                    <h3>⚡ Quick Actions</h3>
                    <div class="quick-actions">
                        <button class="quick-action-btn" onclick="window.location.href='menu-items.php?action=add'">
                            <div class="icon">➕</div>
                            <div class="label">Add Item</div>
                        </button>
                        <button class="quick-action-btn" onclick="window.location.href='orders.php'">
                            <div class="icon">📋</div>
                            <div class="label">View Orders</div>
                        </button>
                        <button class="quick-action-btn" onclick="window.location.href='../kitchen-dashboard.php'">
                            <div class="icon">👨‍🍳</div>
                            <div class="label">Kitchen</div>
                        </button>
                        <button class="quick-action-btn" onclick="window.location.href='tables.php'">
                            <div class="icon">🪑</div>
                            <div class="label">Tables</div>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <h2>📝 Recent Orders</h2>
            <div class="admin-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Table</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>Table <?php echo htmlspecialchars($order['table_number']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name'] ?: '-'); ?></td>
                            <td><span class="order-status <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                            <td class="actions">
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</body>
</html>
