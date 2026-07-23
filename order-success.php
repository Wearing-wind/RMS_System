<?php
// Order Tracker & Settlement Page
require_once 'config.php';
$conn = getDBConnection();

$table_num = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1';
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$order = null;
$order_items = [];
$calculated_total = 0;

if ($conn && $order_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        $items_stmt = $conn->prepare("SELECT oi.*, m.name as item_name FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = ?");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $res = $items_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $order_items[] = $row;
            $calculated_total += ($row['quantity'] * $row['price']);
        }
        $items_stmt->close();
    }
}

// Fetch Payment QR Settings
$setting = null;
if ($conn) {
    $res = $conn->query("SELECT * FROM payment_settings WHERE is_active = 1 LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $setting = $res->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Order #<?php echo $order_id; ?> Status - QR Cafe</title>
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
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-full pb-24 lg:pb-12 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-4xl mx-auto flex items-center justify-between gap-3">
            <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="flex items-center gap-2 font-extrabold text-xs text-amber-400">
                <span>← Back to Menu</span>
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-xs">
                <span>📍</span> Table <?php echo $table_num; ?>
            </span>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 pt-4 space-y-6">

        <?php if (!$order): ?>
            <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 text-center space-y-3">
                <div class="text-4xl">❓</div>
                <h2 class="font-black text-white text-lg">No Active Order Found</h2>
                <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="inline-block px-6 py-2.5 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs">Browse Menu</a>
            </div>
        <?php else: ?>

            <?php
            $status = strtolower($order['status']);
            $is_cancelled = ($status === 'cancelled' || $status === 'rejected');
            
            // Extract rejection reason if available
            $reject_reason = '';
            if ($is_cancelled && !empty($order['notes'])) {
                if (preg_match('/\[REJECTED:\s*(.*?)\]/i', $order['notes'], $matches)) {
                    $reject_reason = trim($matches[1]);
                } else {
                    $reject_reason = $order['notes'];
                }
            }
            ?>

            <?php if ($is_cancelled): ?>
                <!-- CANCELLED ORDER DISPLAY CARD -->
                <section class="bg-zinc-900/90 border border-rose-500/40 rounded-3xl p-6 shadow-2xl space-y-5 text-center md:text-left">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-rose-500/20 pb-4">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-400 font-black text-xs mb-2">
                                <span>❌</span> Order Cancelled by Kitchen
                            </div>
                            <h1 class="text-xl md:text-2xl font-black text-white">Order #<?php echo $order_id; ?></h1>
                            <p class="text-xs text-zinc-400">Placed at <?php echo htmlspecialchars($order['created_at']); ?></p>
                        </div>
                        <div class="text-center md:text-right">
                            <span class="px-4 py-2 rounded-2xl bg-rose-500/20 border border-rose-500/40 text-rose-400 font-black text-xs">CANCELLED</span>
                        </div>
                    </div>

                    <div class="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-4 space-y-2 text-rose-300">
                        <div class="font-extrabold text-sm flex items-center gap-2">
                            <span>🚨</span> Order Status Update
                        </div>
                        <p class="text-xs text-zinc-300">This order was cancelled by the kitchen staff. No charges have been processed for Table <?php echo $table_num; ?>.</p>
                        <?php if (!empty($reject_reason)): ?>
                            <div class="pt-2 border-t border-rose-500/20 text-xs font-bold text-rose-400">
                                Reason: <?php echo htmlspecialchars($reject_reason); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="h-14 w-full rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-xl shadow-amber-500/20">
                        🛒 Back to Menu & Order Again
                    </a>
                </section>
            <?php else: ?>
                <!-- ACTIVE ORDER TRACKER CARD -->
                <section class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-6 shadow-2xl space-y-5 text-center md:text-left">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-zinc-800 pb-4">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-black text-xs mb-2">
                                <span>✅</span> Order Received by Kitchen
                            </div>
                            <h1 class="text-xl md:text-2xl font-black text-white">Order #<?php echo $order_id; ?></h1>
                            <p class="text-xs text-zinc-400">Placed at <?php echo htmlspecialchars($order['created_at']); ?></p>
                        </div>

                        <div class="text-center md:text-right">
                            <div class="text-xs text-zinc-400 font-bold">Total Bill</div>
                            <div class="text-2xl font-black text-amber-400">Rs. <?php echo number_format($calculated_total ?: $order['total_amount'], 2); ?></div>
                        </div>
                    </div>

                    <!-- Status Progress Pipeline -->
                    <?php
                    $step1 = true;
                    $step2 = ($status === 'preparing' || $status === 'ready' || $status === 'completed');
                    $step3 = ($status === 'ready' || $status === 'completed');
                    $step4 = ($status === 'completed');
                    ?>

                    <div>
                        <h3 class="text-xs font-extrabold text-zinc-400 uppercase tracking-wider mb-3">Live Kitchen Status Tracker</h3>
                        <div class="grid grid-cols-4 gap-2">
                            <div class="p-3 rounded-2xl border text-center transition-all <?php echo $step1 ? 'bg-amber-500/10 border-amber-500 text-amber-400' : 'bg-zinc-950 border-zinc-800 text-zinc-600'; ?>">
                                <div class="text-lg">🆕</div>
                                <div class="text-[10px] font-black mt-1">Received</div>
                            </div>
                            <div class="p-3 rounded-2xl border text-center transition-all <?php echo $step2 ? 'bg-amber-500/10 border-amber-500 text-amber-400' : 'bg-zinc-950 border-zinc-800 text-zinc-600'; ?>">
                                <div class="text-lg">🍳</div>
                                <div class="text-[10px] font-black mt-1">In Prep</div>
                            </div>
                            <div class="p-3 rounded-2xl border text-center transition-all <?php echo $step3 ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400' : 'bg-zinc-950 border-zinc-800 text-zinc-600'; ?>">
                                <div class="text-lg">✅</div>
                                <div class="text-[10px] font-black mt-1">Ready</div>
                            </div>
                            <div class="p-3 rounded-2xl border text-center transition-all <?php echo $step4 ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black' : 'bg-zinc-950 border-zinc-800 text-zinc-600'; ?>">
                                <div class="text-lg">✔</div>
                                <div class="text-[10px] font-black mt-1">Served</div>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- 2-Column Responsive Layout (Ordered Items Breakdown & Payment Options) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

                <!-- Left Column: Ordered Items List -->
                <section class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 shadow-2xl space-y-3">
                    <h3 class="text-sm font-black text-white border-b border-zinc-800 pb-3 flex items-center gap-2">
                        <span>📋</span> Ordered Items
                    </h3>
                    <div class="space-y-2">
                        <?php foreach ($order_items as $item): ?>
                            <div class="bg-zinc-950/70 border border-zinc-800/60 rounded-2xl p-3 flex justify-between items-center text-xs">
                                <div>
                                    <div class="font-extrabold text-white"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    <div class="text-zinc-400">Rs. <?php echo number_format($item['price'], 0); ?> x <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="font-black text-amber-400">Rs. <?php echo number_format($item['price'] * $item['quantity'], 0); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Right Column: Settlement & Payment Options -->
                <section class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 shadow-2xl space-y-4">
                    <h3 class="text-sm font-black text-white border-b border-zinc-800 pb-3 flex items-center gap-2">
                        <span>💳</span> Settlement & Payment Options
                    </h3>

                    <div class="space-y-3">
                        <div class="bg-zinc-950 border border-zinc-800 rounded-2xl p-4 space-y-3">
                            <div class="font-extrabold text-xs text-white flex items-center gap-2">
                                <span>💵</span> Option 1: Cash at Table
                            </div>
                            <p class="text-[11px] text-zinc-400">Pay cash or card to the waiter when your food is served to Table <?php echo $table_num; ?>.</p>
                        </div>

                        <div class="bg-zinc-950 border border-amber-500/30 rounded-2xl p-4 space-y-3">
                            <div class="font-extrabold text-xs text-amber-400 flex items-center gap-2">
                                <span>📱</span> Option 2: Digital Scan & Pay (eSewa / Khalti)
                            </div>
                            <p class="text-[11px] text-zinc-400">
                                <?php echo htmlspecialchars($setting['payment_note'] ?? 'Scan QR code below to make online payment'); ?>
                            </p>

                            <?php if (!empty($setting['qr_code_image'])): ?>
                                <div class="p-3 bg-white rounded-2xl inline-block shadow-lg border border-amber-500/50 text-center w-full">
                                    <img src="images/<?php echo htmlspecialchars($setting['qr_code_image']); ?>" alt="Merchant Payment QR" class="w-48 h-48 mx-auto object-contain">
                                </div>
                            <?php else: ?>
                                <div class="p-3 bg-zinc-900 border border-zinc-800 rounded-xl text-center text-xs font-bold text-amber-400">
                                    Scan QR code on table to pay via mobile banking
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                        + Add More Items to Table <?php echo $table_num; ?>
                    </a>
                </section>

            </div>

        <?php endif; ?>

    </main>

    <script src="js/modern.js"></script>
    <script>
        const currentOrderId = <?php echo $order_id; ?>;
        let currentOrderStatus = '<?php echo strtolower($order['status'] ?? 'new'); ?>';

        // Continuous Live Status Polling every 3.5 seconds
        function checkLiveOrderStatus() {
            if (!currentOrderId) return;
            fetch('api/get-order-status.php?order_id=' + currentOrderId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.order) {
                        const newStatus = data.order.status.toLowerCase();
                        if (newStatus !== currentOrderStatus) {
                            currentOrderStatus = newStatus;
                            window.location.reload();
                        }
                    }
                })
                .catch(err => console.error(err));
        }

        document.addEventListener('DOMContentLoaded', () => {
            setInterval(checkLiveOrderStatus, 3500);
        });
    </script>
</body>
</html>
