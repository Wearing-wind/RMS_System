<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Order Tracker - QR Cafe</title>
    <link rel="stylesheet" href="css/spatial.css">
    <style>
        .progress-bar-spatial {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 24px 0;
            position: relative;
        }

        .progress-bar-spatial::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 24px;
            right: 24px;
            height: 3px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            opacity: 0.4;
            transition: var(--transition);
        }

        .progress-step.active, .progress-step.completed {
            opacity: 1;
        }

        .progress-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(22, 17, 13, 0.9);
            border: 2px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-bottom: 4px;
            transition: var(--transition-spring);
        }

        .progress-step.active .progress-icon {
            border-color: var(--primary);
            box-shadow: 0 0 18px var(--primary-glow);
            transform: scale(1.1);
            background: var(--primary);
            color: #0c0907;
        }

        .progress-step.completed .progress-icon {
            border-color: var(--success);
            background: rgba(34, 197, 94, 0.18);
            color: #4ade80;
        }

        .progress-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php
    require_once 'config.php';
    $conn = getDBConnection();
    
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $order = null;
    $payment_settings = null;
    
    if ($conn) {
        $payment_result = $conn->query("SELECT * FROM payment_settings WHERE is_active = 1 LIMIT 1");
        if ($payment_result && $payment_row = $payment_result->fetch_assoc()) {
            $payment_settings = $payment_row;
        }
        
        if ($order_id > 0) {
            $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($order = $res->fetch_assoc()) {
                $items_stmt = $conn->prepare("
                    SELECT oi.*, mi.name 
                    FROM order_items oi 
                    JOIN menu_items mi ON oi.menu_item_id = mi.id 
                    WHERE oi.order_id = ?
                ");
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_res = $items_stmt->get_result();
                $items = [];
                while ($it = $items_res->fetch_assoc()) {
                    $items[] = $it;
                }
                $items_stmt->close();
                $order['items'] = $items;
            }
            $stmt->close();
        }
        $conn->close();
    }
    
    if (!$order && isset($_SESSION['last_order'])) {
        $order = $_SESSION['last_order'];
    }
    
    if (!$order) {
        header('Location: menu.php');
        exit;
    }
    
    $table_number = htmlspecialchars($order['table_number'] ?? '1');
    $order_status = $order['status'] ?? 'new';
    $is_cancelled = ($order_status === 'cancelled');
    $is_completed = ($order_status === 'completed'); // SERVED
    $payment_method = $order['payment_method'] ?? 'pending';
    ?>

    <!-- Mobile Header Bar -->
    <header class="header">
        <div class="mobile-app-shell">
            <a href="menu.php?table=<?php echo $table_number; ?>" class="logo">
                <span>☕</span> QR Cafe & Dining
            </a>
            <span class="table-badge">📍 Table <?php echo $table_number; ?></span>
        </div>
    </header>

    <!-- Main Mobile Shell Container -->
    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">
        <div class="spatial-card" style="padding: 20px 16px;">

            <?php if ($is_cancelled): ?>
                <!-- REJECTED / CANCELLED ORDER DISPLAY -->
                <div style="text-align: center; padding: 10px 0;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">🚫</div>
                    <h1 style="font-size: 1.4rem; font-weight: 800; color: var(--danger); margin-bottom: 4px; font-family: var(--font-serif);">Order Cancelled / Rejected</h1>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 16px;">Order #<?php echo $order['id']; ?> has been cancelled by the kitchen.</p>

                    <div style="background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); border-radius: var(--radius-md); padding: 14px; text-align: left; margin-bottom: 20px;">
                        <h4 style="color: #fda4af; font-size: 0.82rem; margin-bottom: 4px;">Reason for Cancellation:</h4>
                        <p style="color: var(--text-primary); font-size: 0.88rem; font-weight: 600;">
                            <?php 
                            $note_text = $order['notes'] ?? '';
                            if (preg_match('/\[REJECTED:\s*(.*?)\]/', $note_text, $matches)) {
                                echo htmlspecialchars($matches[1]);
                            } else {
                                echo 'Kitchen is unable to process this order or customer requested cancellation due to wrong order/quantity.';
                            }
                            ?>
                        </p>
                    </div>

                    <a href="menu.php?table=<?php echo $table_number; ?>" class="checkout-btn" style="display: block; width: 100%; padding: 12px;">
                        Modify & Place New Order →
                    </a>
                </div>

            <?php else: ?>
                <!-- ACTIVE ORDER TRACKER DISPLAY -->
                <div style="text-align: center;">
                    <div style="font-size: 2.2rem; margin-bottom: 2px;">✨</div>
                    <h1 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 2px; font-family: var(--font-serif);">Order Tracker</h1>
                    <p style="color: var(--primary); font-weight: 800; font-size: 0.95rem; margin-bottom: 16px;">Order #<?php echo $order['id']; ?></p>
                </div>

                <!-- Progress Tracker Bar -->
                <div class="progress-bar-spatial">
                    <div class="progress-step <?php echo in_array($order_status, ['new', 'preparing', 'ready', 'completed']) ? 'completed' : ''; ?>">
                        <div class="progress-icon">✓</div>
                        <span class="progress-label">Placed</span>
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

                <!-- Order Details Breakdown -->
                <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 14px; margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid var(--glass-border); font-size: 0.85rem;">
                        <span style="color: var(--text-muted);">Status:</span>
                        <span id="liveStatusText" style="font-weight: 800; font-size: 0.78rem; text-transform: uppercase; padding: 3px 10px; border-radius: var(--radius-pill); background: rgba(217, 155, 38, 0.2); color: var(--primary); border: 1px solid var(--primary);"><?php echo strtoupper($order_status); ?></span>
                    </div>

                    <div style="margin: 10px 0;">
                        <h4 style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px;">Ordered Items:</h4>
                        <?php foreach ($order['items'] as $item): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 0.88rem; padding: 4px 0;">
                                <span><?php echo htmlspecialchars($item['name']); ?> <strong style="color: var(--primary);">x<?php echo $item['quantity']; ?></strong></span>
                                <span style="font-weight: 700; color: var(--primary);">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--glass-border); padding-top: 8px; font-size: 1.05rem; font-weight: 800;">
                        <span>Total:</span>
                        <span style="color: var(--primary);">Rs. <?php echo number_format($order['total_amount'] ?? $order['total'] ?? 0, 2); ?></span>
                    </div>
                </div>

                <!-- PAYMENT SECTION - SHOW QR ONLY WHEN SERVED (COMPLETED) -->
                <?php if ($is_completed): ?>
                    <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--glass-border-bright); border-radius: var(--radius-md); padding: 16px; text-align: center; margin-bottom: 18px;">
                        <h3 style="font-size: 1rem; font-weight: 800; margin-bottom: 10px; color: var(--primary);">
                            🎉 Order Served! Choose Payment Method
                        </h3>

                        <!-- Payment Method Toggle (QR vs Cash) -->
                        <div class="payment-choice-grid">
                            <div class="payment-choice-card <?php echo $payment_method !== 'cash' ? 'active' : ''; ?>" id="payQrTabBtn" onclick="selectPaymentMethod('qr')">
                                <div class="payment-choice-icon">💳</div>
                                <div class="payment-choice-title">Digital Wallet (QR)</div>
                            </div>
                            <div class="payment-choice-card <?php echo $payment_method === 'cash' ? 'active' : ''; ?>" id="payCashTabBtn" onclick="selectPaymentMethod('cash')">
                                <div class="payment-choice-icon">💵</div>
                                <div class="payment-choice-title">Pay via Cash</div>
                            </div>
                        </div>

                        <!-- DIGITAL WALLET (QR CODE) DISPLAY -->
                        <div id="digitalQrPaySection" style="display: <?php echo $payment_method !== 'cash' ? 'block' : 'none'; ?>; margin-top: 14px;">
                            <?php if ($payment_settings && !empty($payment_settings['qr_code_image'])): ?>
                                <div style="margin-bottom: 12px;">
                                    <img id="paymentQrImage" src="images/<?php echo htmlspecialchars($payment_settings['qr_code_image']); ?>" alt="Payment QR" style="max-width: 160px; width: 100%; border-radius: var(--radius-sm); border: 2px solid var(--primary); padding: 8px; background: white;">
                                </div>
                                <a href="images/<?php echo htmlspecialchars($payment_settings['qr_code_image']); ?>" download="Payment_QR_Order_<?php echo $order['id']; ?>.jpg" class="checkout-btn" style="display: inline-block; width: auto; padding: 8px 20px; font-size: 0.85rem;">
                                    📥 Download Payment QR
                                </a>
                            <?php else: ?>
                                <div style="font-size: 2.8rem; margin-bottom: 6px;">📱</div>
                                <p style="color: var(--text-muted); font-size: 0.8rem;">Scan payment QR code at counter or wallet</p>
                            <?php endif; ?>
                            <p style="color: var(--text-muted); font-size: 0.78rem; margin-top: 8px;"><?php echo htmlspecialchars($payment_settings['payment_note'] ?? 'Scan QR code to pay via digital wallet'); ?></p>
                        </div>

                        <!-- CASH PAYMENT DISPLAY -->
                        <div id="cashPaySection" style="display: <?php echo $payment_method === 'cash' ? 'block' : 'none'; ?>; margin-top: 14px;">
                            <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); border-radius: var(--radius-sm); padding: 14px; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 4px;">💵</div>
                                <h4 style="color: #4ade80; font-size: 0.95rem; margin-bottom: 4px;">Cash Payment Requested</h4>
                                <p style="color: var(--text-secondary); font-size: 0.82rem;">
                                    A server has been notified to collect cash payment of <strong>Rs. <?php echo number_format($order['total_amount'] ?? $order['total'] ?? 0, 2); ?></strong> at <strong>Table <?php echo $table_number; ?></strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- PAYMENT QR NOT SHOWN YET UNTIL ORDER IS SERVED -->
                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px dashed var(--glass-border); border-radius: var(--radius-md); padding: 16px; text-align: center; margin-bottom: 18px;">
                        <div style="font-size: 2rem; margin-bottom: 4px;">⏳</div>
                        <h4 style="font-size: 0.88rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 2px;">Payment Options</h4>
                        <p style="color: var(--text-muted); font-size: 0.78rem;">
                            Payment QR code and cash payment options will automatically be displayed here once your order has been served by the kitchen.
                        </p>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 16px;">
                    <a href="menu.php?table=<?php echo $table_number; ?>" class="checkout-btn" style="display: block; width: 100%; padding: 12px;">
                        + Order More Dishes
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- PINNED MOBILE BOTTOM NAVIGATION BAR -->
    <nav class="mobile-nav-bar">
        <a href="menu.php?table=<?php echo $table_number; ?>" class="mobile-nav-item">
            <span class="mobile-nav-icon">🍽️</span>
            <span>Menu</span>
        </a>
        <a href="cart.php?table=<?php echo $table_number; ?>" class="mobile-nav-item">
            <span class="mobile-nav-icon">🛒</span>
            <span>Cart</span>
            <span class="mobile-nav-badge cart-badge" style="display: none;">0</span>
        </a>
        <button class="mobile-nav-item" onclick="callWaiter()">
            <span class="mobile-nav-icon">🔔</span>
            <span>Waiter</span>
        </button>
        <a href="order-success.php?table=<?php echo $table_number; ?>&order_id=<?php echo $order['id']; ?>" class="mobile-nav-item active">
            <span class="mobile-nav-icon">📋</span>
            <span>Tracker</span>
        </a>
    </nav>

    <script src="js/modern.js"></script>
    <script>
        const currentOrderId = <?php echo $order['id'] ?? 0; ?>;
        const currentStatus = "<?php echo $order_status; ?>";

        function selectPaymentMethod(method) {
            document.getElementById('payQrTabBtn').classList.toggle('active', method === 'qr');
            document.getElementById('payCashTabBtn').classList.toggle('active', method === 'cash');
            document.getElementById('digitalQrPaySection').style.display = (method === 'qr') ? 'block' : 'none';
            document.getElementById('cashPaySection').style.display = (method === 'cash') ? 'block' : 'none';

            if (!currentOrderId) return;

            fetch('api/update-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: currentOrderId, payment_method: method })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && method === 'cash') {
                    showToast('💵 Cash payment requested! Waiter notified to Table <?php echo $table_number; ?>', 'success');
                }
            })
            .catch(err => console.error(err));
        }

        function pollOrderStatus() {
            if (!currentOrderId) return;
            fetch('api/orders.php?id=' + currentOrderId)
                .then(r => r.json())
                .then(data => {
                    if (data.order && data.order.status !== currentStatus) {
                        window.location.reload();
                    }
                })
                .catch(err => console.error(err));
        }

        if (currentOrderId && currentStatus !== 'completed' && currentStatus !== 'cancelled') {
            setInterval(pollOrderStatus, 5000);
        }
    </script>
</body>
</html>
