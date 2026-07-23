<?php
// Admin Order Details
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

// Get order
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

// Get order items
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - QR Restaurant Admin</title>
    <link rel="stylesheet" href="../css/style.css">
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
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Admin Content -->
    <section class="admin-content">
        <div class="container">
            <a href="orders.php" class="back-btn">← Back to Orders</a>
            <h1>Order #<?php echo $order_id; ?></h1>
            
            <div class="order-details" style="max-width: 600px;">
                <div class="order-info-item">
                    <span>Table Number:</span>
                    <strong>Table <?php echo htmlspecialchars($order['table_number']); ?></strong>
                </div>
                <?php if (!empty($order['customer_name'])): ?>
                <div class="order-info-item">
                    <span>Customer Name:</span>
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                </div>
                <?php endif; ?>
                <div class="order-info-item">
                    <span>Status:</span>
                    <span class="status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                </div>
                <div class="order-info-item">
                    <span>Order Date:</span>
                    <span><?php echo date('F j, Y h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="order-info-item">
                    <span>Ordered Items:</span>
                </div>
                <?php foreach ($items as $item): ?>
                <div class="order-info-item">
                    <span style="padding-left: 20px;"><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                    <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                </div>
                <?php endforeach; ?>
                <div class="order-info-item" style="border-top: 2px solid #eee; margin-top: 10px; padding-top: 10px;">
                    <span>Total:</span>
                    <strong style="color: #667eea;">$<?php echo number_format($total, 2); ?></strong>
                </div>
                <?php if (!empty($order['notes'])): ?>
                <div class="order-info-item">
                    <span>Notes:</span>
                    <span><?php echo htmlspecialchars($order['notes']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Update Status -->
            <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
            <div style="margin-top: 20px;">
                <h3>Update Status</h3>
                <a href="orders.php?update_status=<?php echo $order_id; ?>&status=new" class="btn <?php echo $order['status'] === 'new' ? 'btn-primary' : ''; ?>">New</a>
                <a href="orders.php?update_status=<?php echo $order_id; ?>&status=preparing" class="btn <?php echo $order['status'] === 'preparing' ? 'btn-primary' : ''; ?>">Preparing</a>
                <a href="orders.php?update_status=<?php echo $order_id; ?>&status=ready" class="btn <?php echo $order['status'] === 'ready' ? 'btn-primary' : ''; ?>">Ready</a>
                <a href="orders.php?update_status=<?php echo $order_id; ?>&status=completed" class="btn <?php echo $order['status'] === 'completed' ? 'btn-primary' : ''; ?>">Completed</a>
                <a href="orders.php?update_status=<?php echo $order_id; ?>&status=cancelled" class="btn btn-danger" onclick="return confirm('Cancel this order?')">Cancel</a>
            </div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
