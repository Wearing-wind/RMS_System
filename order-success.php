<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Order Tracker - QR Cafe</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              amber: {
                500: '#f59e0b',
                600: '#d97706',
              }
            }
          }
        }
      }
    </script>
    <style>
        body { overscroll-behavior-y: contain; -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="min-h-full pb-24 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

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

    <!-- Sticky Mobile Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between gap-3">
            <a href="menu.php?table=<?php echo urlencode($table_number); ?>" class="flex items-center gap-2 text-lg font-black text-white">
                <span>☕</span> QR Cafe & Dining
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-xs">
                📍 Table <?php echo $table_number; ?>
            </span>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-4">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 shadow-2xl space-y-4">

            <?php if ($is_cancelled): ?>
                <!-- REJECTED DISPLAY -->
                <div class="text-center py-4">
                    <div class="text-5xl mb-3">🚫</div>
                    <h1 class="text-xl font-black text-rose-500 mb-1">Order Cancelled / Rejected</h1>
                    <p class="text-xs text-zinc-400 mb-4">Order #<?php echo $order['id']; ?> has been cancelled by the kitchen.</p>
                    <a href="menu.php?table=<?php echo urlencode($table_number); ?>" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center">
                        Modify & Place New Order →
                    </a>
                </div>
            <?php else: ?>
                <!-- ACTIVE ORDER TRACKER -->
                <div class="text-center">
                    <div class="text-4xl mb-1">✨</div>
                    <h1 class="text-xl font-black text-white mb-1">Order Tracker</h1>
                    <p class="text-amber-400 font-black text-sm">Order #<?php echo $order['id']; ?></p>
                </div>

                <!-- Status Progress Badges -->
                <div class="grid grid-cols-4 gap-1.5 bg-zinc-950/80 p-2 rounded-2xl border border-zinc-800 text-center">
                    <div class="p-2 rounded-xl border <?php echo in_array($order_status, ['new', 'preparing', 'ready', 'completed']) ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 font-black' : 'border-zinc-800 text-zinc-600'; ?> text-[10px]">
                        ✓ Placed
                    </div>
                    <div class="p-2 rounded-xl border <?php echo in_array($order_status, ['preparing', 'ready', 'completed']) ? 'bg-amber-500/10 border-amber-500/30 text-amber-400 font-black' : 'border-zinc-800 text-zinc-600'; ?> text-[10px]">
                        🔥 Prep
                    </div>
                    <div class="p-2 rounded-xl border <?php echo in_array($order_status, ['ready', 'completed']) ? 'bg-amber-500/10 border-amber-500/30 text-amber-400 font-black' : 'border-zinc-800 text-zinc-600'; ?> text-[10px]">
                        ✅ Ready
                    </div>
                    <div class="p-2 rounded-xl border <?php echo $order_status === 'completed' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 font-black' : 'border-zinc-800 text-zinc-600'; ?> text-[10px]">
                        🍽️ Served
                    </div>
                </div>

                <!-- Order Details Breakdown -->
                <div class="bg-zinc-950/80 border border-zinc-800/80 rounded-2xl p-4 space-y-2">
                    <div class="flex justify-between items-center pb-2 border-b border-zinc-800 text-xs">
                        <span class="text-zinc-400">Current Status:</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-black uppercase text-[10px]"><?php echo strtoupper($order_status); ?></span>
                    </div>

                    <div class="space-y-1 py-1">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="flex justify-between text-xs">
                                <span><?php echo htmlspecialchars($item['name']); ?> <strong class="text-amber-400">x<?php echo $item['quantity']; ?></strong></span>
                                <span class="font-black text-amber-400">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex justify-between items-center pt-2 border-t border-zinc-800 font-black text-base">
                        <span>Total:</span>
                        <span class="text-amber-400 text-lg">Rs. <?php echo number_format($order['total_amount'] ?? $order['total'] ?? 0, 2); ?></span>
                    </div>
                </div>

                <!-- PAYMENT SECTION - UNLOCKS ONLY WHEN SERVED -->
                <?php if ($is_completed): ?>
                    <div class="bg-zinc-950/90 border border-emerald-500/30 rounded-2xl p-4 text-center space-y-3">
                        <h3 class="text-sm font-black text-emerald-400">🎉 Order Served! Select Payment Option</h3>

                        <div class="grid grid-cols-2 gap-2">
                            <button id="payQrTabBtn" onclick="selectPaymentMethod('qr')" class="p-3 rounded-xl border border-amber-500 bg-amber-500/10 text-amber-400 font-bold text-xs active:scale-95">
                                💳 Digital QR
                            </button>
                            <button id="payCashTabBtn" onclick="selectPaymentMethod('cash')" class="p-3 rounded-xl border border-zinc-800 bg-zinc-900 text-zinc-300 font-bold text-xs active:scale-95">
                                💵 Pay Cash
                            </button>
                        </div>

                        <div id="digitalQrPaySection" style="display: block;" class="pt-2">
                            <?php if ($payment_settings && !empty($payment_settings['qr_code_image'])): ?>
                                <img src="images/<?php echo htmlspecialchars($payment_settings['qr_code_image']); ?>" alt="Payment QR" class="max-w-[160px] mx-auto rounded-xl border-2 border-amber-500 p-2 bg-white mb-3">
                                <a href="images/<?php echo htmlspecialchars($payment_settings['qr_code_image']); ?>" download="Payment_QR_Order_<?php echo $order['id']; ?>.jpg" class="h-10 px-4 rounded-xl bg-amber-500 text-zinc-950 font-black text-xs inline-flex items-center gap-1">
                                    📥 Download Payment QR
                                </a>
                            <?php endif; ?>
                        </div>

                        <div id="cashPaySection" style="display: none;" class="pt-2">
                            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold space-y-1">
                                <div class="text-2xl">💵</div>
                                <h4>Cash Payment Requested</h4>
                                <p class="text-[11px] text-zinc-300">A waiter has been dispatched to Table <?php echo $table_number; ?> to collect cash payment.</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-zinc-950/60 border border-zinc-800/80 rounded-2xl p-4 text-center space-y-1">
                        <div class="text-2xl">⏳</div>
                        <h4 class="text-xs font-bold text-zinc-300">Payment Options Locked</h4>
                        <p class="text-[11px] text-zinc-500">Payment QR code and cash payment options will automatically unlock here once your order is served by the kitchen.</p>
                    </div>
                <?php endif; ?>

                <a href="menu.php?table=<?php echo urlencode($table_number); ?>" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                    + Order More Dishes
                </a>
            <?php endif; ?>
        </div>
    </main>

    <!-- Fixed Bottom Tab Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="menu.php?table=<?php echo urlencode($table_number); ?>" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">🍽️</span>
            <span>Menu</span>
        </a>
        <a href="cart.php?table=<?php echo urlencode($table_number); ?>" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">🛒</span>
            <span>Cart</span>
            <span class="cart-badge absolute top-1 right-2 bg-amber-500 text-zinc-950 font-black text-[9px] w-4 h-4 rounded-full flex items-center justify-center" style="display: none;">0</span>
        </a>
        <button onclick="callWaiter()" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">🔔</span>
            <span>Waiter</span>
        </button>
        <a href="order-success.php?table=<?php echo urlencode($table_number); ?>&order_id=<?php echo $order['id']; ?>" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-[10px]">
            <span class="text-lg">📋</span>
            <span>Tracker</span>
        </a>
    </nav>

    <script src="js/modern.js"></script>
    <script>
        const currentOrderId = <?php echo $order['id'] ?? 0; ?>;
        const currentStatus = "<?php echo $order_status; ?>";

        function selectPaymentMethod(method) {
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
            });
        }

        function pollOrderStatus() {
            if (!currentOrderId) return;
            fetch('api/orders.php?id=' + currentOrderId)
                .then(r => r.json())
                .then(data => {
                    if (data.order && data.order.status !== currentStatus) {
                        window.location.reload();
                    }
                });
        }

        if (currentOrderId && currentStatus !== 'completed' && currentStatus !== 'cancelled') {
            setInterval(pollOrderStatus, 5000);
        }
    </script>
</body>
</html>
