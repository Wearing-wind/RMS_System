<?php
// Admin Orders Management
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database not connected. Please run setup.php first.");
}

// Handle status update
if (isset($_GET['update_status'])) {
    $order_id = intval($_GET['update_status']);
    $status = sanitize($_GET['status']);
    
    $valid_statuses = ['new', 'preparing', 'ready', 'completed', 'cancelled'];
    if (in_array($status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = 'Order status updated';
    }
    
    header('Location: orders.php');
    exit;
}

// Filter by date - with proper input validation and sanitization
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d');
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
    $date_filter = date('Y-m-d');
}

// Build query with prepared statements
$sql = "SELECT * FROM orders WHERE DATE(created_at) = ?";
$params = [$date_filter];

if (!empty($status_filter)) {
    // Validate status against allowed values
    $valid_statuses = ['new', 'preparing', 'ready', 'completed', 'cancelled'];
    if (in_array($status_filter, $valid_statuses)) {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
    }
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - QR Restaurant Admin</title>
    <link rel="stylesheet" href="../css/modern.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f6fa; }
        .admin-header { background: linear-gradient(135deg, #ff6b35, #ff8c5a); padding: 15px 0; }
        .admin-header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-logo { color: white; font-size: 1.5rem; font-weight: bold; text-decoration: none; }
        .admin-nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .admin-nav a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 10px 18px; border-radius: 25px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); color: white; }
        .admin-content { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .admin-content h1 { color: #2d3436; margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .filters { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; margin-bottom: 5px; color: #2d3436; font-weight: 600; font-size: 0.9rem; }
        .form-group input, .form-group select { padding: 10px; border: 2px solid #dfe6e9; border-radius: 8px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #ff6b35; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; font-weight: 600; }
        .btn-primary { background: #ff6b35; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; }
        .admin-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .admin-table table { width: 100%; border-collapse: collapse; }
        .admin-table th { background: #ff6b35; color: white; padding: 15px; text-align: left; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #dfe6e9; }
        .admin-table tr:hover { background: #f8f9fa; }
        .actions { display: flex; gap: 10px; }
        .status-new { background: #ff7675; color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem; }
        .status-preparing { background: #fdcb6e; color: #2d3436; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem; }
        .status-ready { background: #00b894; color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem; }
        .status-completed { background: #00b894; color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem; }
        .status-cancelled { background: #636e72; color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem; }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <a href="index.php" class="admin-logo">🍽️ Admin Panel</a>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="menu-items.php">Menu Items</a>
                <a href="categories.php">Categories</a>
                <a href="tables.php">Tables & QR</a>
                <a href="orders.php" class="active">Orders</a>
                <a href="payment-settings.php">Payment Settings</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Admin Content -->
    <section class="admin-content">
        <h1>Orders Management</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo $date_filter; ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                        <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="orders.php" class="btn btn-primary">Reset</a>
            </form>
        </div>
        
        <!-- Orders Table -->
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
                    <?php if ($orders->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">No orders found</td>
                    </tr>
                    <?php else: ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>Table <?php echo htmlspecialchars($order['table_number']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name'] ?: '-'); ?></td>
                            <td><span class="status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                            <td class="actions">
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">Cancel</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    
    <script>
        function cancelOrder(id) {
            if (confirm('Are you sure you want to cancel this order?')) {
                window.location.href = 'orders.php?update_status=' + id + '&status=cancelled';
            }
        }
    </script>
</body>
</html>
