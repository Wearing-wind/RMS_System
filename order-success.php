<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed - QR Restaurant</title>
    <link rel="stylesheet" href="css/modern.css">
    <style>
        .payment-section {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            margin-top: 30px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .payment-section h3 {
            color: var(--secondary);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .qr-code-container {
            max-width: 250px;
            margin: 0 auto 20px;
            padding: 20px;
            background: white;
            border-radius: var(--radius);
            border: 3px solid var(--primary);
        }
        
        .qr-code-container img {
            width: 100%;
            height: auto;
        }
        
        .qr-placeholder {
            width: 200px;
            height: 200px;
            margin: 0 auto;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
        }
        
        .payment-note {
            color: var(--gray);
            font-size: 1rem;
            margin-bottom: 15px;
        }
        
        .order-progress {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: 0.4;
        }
        
        .progress-step.active {
            opacity: 1;
        }
        
        .progress-step.completed {
            opacity: 1;
        }
        
        .progress-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }
        
        .progress-step.active .progress-icon {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
        
        .progress-step.completed .progress-icon {
            background: var(--success);
            color: white;
        }
        
        .progress-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .progress-step.active .progress-label {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php
    // Get order details from session
    $order = isset($_SESSION['last_order']) ? $_SESSION['last_order'] : null;
    $db_error = false;
    $payment_settings = null;
    
    // If no order in session, try to get from database
    if (!$order && isset($_GET['order_id'])) {
        require_once 'config.php';
        $conn = getDBConnection();
        
        if ($conn === null) {
            $db_error = true;
        } else {
            // Get payment settings
            $payment_result = $conn->query("SELECT * FROM payment_settings WHERE is_active = 1 LIMIT 1");
            if ($payment_row = $payment_result->fetch_assoc()) {
                $payment_settings = $payment_row;
            }
            
            $order_id = intval($_GET['order_id']);
            $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($order = $result->fetch_assoc()) {
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
                
                $order['items'] = $items;
                $order['total'] = $total;
            }
            
            $stmt->close();
            $conn->close();
        }
    }
    
    // If still no order, redirect to menu
    if (!$order && !$db_error) {
        header('Location: menu.php');
        exit;
    }
    
    $table_number = isset($order['table_number']) ? $order['table_number'] : 1;
    $order_status = isset($order['status']) ? $order['status'] : 'new';
    ?>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <a href="menu.php?table=<?php echo $table_number; ?>" class="logo">
                <span class="logo-icon">🍽️</span>
                QR Restaurant
            </a>
            <div class="header-right">
                <span class="table-badge">Table <?php echo $table_number; ?></span>
            </div>
        </div>
    </header>

    <!-- Order Success Page -->
    <section class="order-success">
        <div class="container">
            <?php if ($db_error): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Database Connection Error</h3>
                    <p>Unable to connect to the database. Please contact the administrator.</p>
                </div>
            <?php else: ?>
            <div class="success-icon">✓</div>
            <h1>Order Placed Successfully!</h1>
            <p class="order-number">Order #<?php echo $order['id']; ?></p>
            
            <!-- Order Progress Tracker -->
            <div class="order-progress">
                <div class="progress-step <?php echo in_array($order_status, ['new', 'preparing', 'ready', 'completed']) ? 'completed' : ''; ?>">
                    <div class="progress-icon">✓</div>
                    <span class="progress-label">Order Placed</span>
                </div>
                <div class="progress-step <?php echo in_array($order_status, ['preparing', 'ready', 'completed']) ? 'completed' : ''; ?> <?php echo $order_status === 'preparing' ? 'active' : ''; ?>">
                    <div class="progress-icon">🔥</div>
                    <span class="progress-label">Preparing</span>
                </div>
                <div class="progress-step <?php echo in_array($order_status, ['ready', 'completed']) ? 'completed' : ''; ?> <?php echo $order_status === 'ready' ? 'active' : ''; ?>">
                    <div class="progress-icon">✅</div>
                    <span class="progress-label">Ready</span>
                </div>
                <div class="progress-step <?php echo $order_status === 'completed' ? 'active' : ''; ?>">
                    <div class="progress-icon">🍽️</div>
                    <span class="progress-label">Served</span>
                </div>
            </div>
            
            <div class="order-details">
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
                    <span class="order-status <?php echo $order_status; ?>"><?php echo ucfirst($order_status); ?></span>
                </div>
                <div class="order-info-item">
                    <span>Ordered Items:</span>
                </div>
                <?php foreach ($order['items'] as $item): ?>
                <div class="order-info-item">
                    <span style="padding-left: 20px;"><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                    <strong>Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                </div>
                <?php endforeach; ?>
                <div class="order-info-item" style="border-top: 2px solid #eee; margin-top: 10px; padding-top: 10px;">
                    <span>Total:</span>
                    <strong style="color: var(--primary); font-size: 1.3rem;">Rs. <?php echo number_format($order['total'], 2); ?></strong>
                </div>
                <?php if (!empty($order['notes'])): ?>
                <div class="order-info-item">
                    <span>Notes:</span>
                    <span><?php echo htmlspecialchars($order['notes']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Section with QR Code -->
            <?php if ($payment_settings): ?>
            <div class="payment-section">
                <h3>💳 Payment</h3>
                
                <?php if (!empty($payment_settings['qr_code_image'])): ?>
                <div class="qr-code-container">
                    <img src="images/<?php echo htmlspecialchars($payment_settings['qr_code_image']); ?>" alt="Payment QR Code">
                </div>
                <?php else: ?>
                <div class="qr-placeholder">📱</div>
                <?php endif; ?>
                
                <p class="payment-note"><?php echo htmlspecialchars($payment_settings['payment_note'] ?? 'Scan QR to pay'); ?></p>
                
                <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
                    <span class="order-status <?php echo $order['payment_status'] ?? 'pending'; ?>">
                        Payment: <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <p style="margin-top: 25px; color: var(--gray);">Your order has been sent to the kitchen. Please wait for your order to be prepared.</p>
            
            <a href="menu.php?table=<?php echo $table_number; ?>" class="btn btn-primary" style="display: inline-block; margin-top: 20px;">Order More</a>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Clear cart after successful order
        localStorage.removeItem('cart');
        
        // Check order status periodically
        function checkOrderStatus() {
            const orderId = <?php echo isset($order['id']) ? $order['id'] : 0; ?>;
            if (!orderId) return;
            
            fetch('api/orders.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.order && data.order.status !== '<?php echo $order_status; ?>') {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Check status every 10 seconds
        setInterval(checkOrderStatus, 10000);
    </script>
</body>
</html>
