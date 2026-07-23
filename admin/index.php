<?php
// Manager Dashboard - Mobile-Native Tailwind Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();
$today = date('Y-m-d');

// Statistics
$today_orders = ($conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc()['count']) ?: 0;
$completed_orders = ($conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today' AND status = 'completed'")->fetch_assoc()['count']) ?: 0;
$today_sales = ($conn->query("SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) = '$today'")->fetch_assoc()['total']) ?: 0;
$active_orders_count = ($conn->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('new', 'preparing', 'ready')")->fetch_assoc()['count']) ?: 0;

// Live Queue
$live_orders_res = $conn->query("SELECT * FROM orders WHERE status IN ('new', 'preparing', 'ready') ORDER BY created_at DESC LIMIT 10");
$live_orders = [];
if ($live_orders_res) {
    while ($row = $live_orders_res->fetch_assoc()) {
        $live_orders[] = $row;
    }
}

// Quick Inventory List for On-the-fly Toggles
$menu_items_res = $conn->query("SELECT * FROM menu_items ORDER BY category_id, name LIMIT 8");
$inventory_items = [];
if ($menu_items_res) {
    while ($item = $menu_items_res->fetch_assoc()) {
        $inventory_items[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Manager Dashboard - QR Cafe</title>
    <link rel="manifest" href="../manifest.json">
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
<body class="min-h-full pb-24 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <!-- Sticky Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 font-black text-lg text-white">
                <span class="text-xl">📊</span>
                <span>Manager Control</span>
            </div>
            <a href="logout.php" class="text-xs font-extrabold text-rose-400 bg-rose-500/10 border border-rose-500/20 px-3 py-1.5 rounded-full">Logout 🚪</a>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-3 space-y-4">

        <!-- Admin Module Quick Navigation Carousel -->
        <nav class="flex gap-2 overflow-x-auto no-scrollbar py-1">
            <a href="index.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">📊 Dashboard</a>
            <a href="menu-items.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🍔 Menu Items</a>
            <a href="orders.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📋 Live Orders</a>
            <a href="tables.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📍 Tables</a>
        </nav>

        <!-- 2x2 Modern Metrics Cards Grid -->
        <section>
            <h3 class="text-xs font-extrabold text-zinc-400 uppercase tracking-wider mb-2.5">Today's Performance</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-4 flex flex-col justify-between">
                    <div class="text-xs font-bold text-zinc-400">💰 Sales Today</div>
                    <div class="text-2xl font-black text-amber-400 mt-2">Rs. <?php echo number_format($today_sales, 0); ?></div>
                </div>
                <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-4 flex flex-col justify-between">
                    <div class="text-xs font-bold text-zinc-400">🔥 Active Orders</div>
                    <div class="text-2xl font-black text-cyan-400 mt-2"><?php echo $active_orders_count; ?></div>
                </div>
                <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-4 flex flex-col justify-between">
                    <div class="text-xs font-bold text-zinc-400">✅ Served Today</div>
                    <div class="text-2xl font-black text-emerald-400 mt-2"><?php echo $completed_orders; ?></div>
                </div>
                <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-4 flex flex-col justify-between">
                    <div class="text-xs font-bold text-zinc-400">📋 Total Orders</div>
                    <div class="text-2xl font-black text-white mt-2"><?php echo $today_orders; ?></div>
                </div>
            </div>
        </section>

        <!-- Quick Live Orders Stream -->
        <section class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-extrabold text-sm text-white flex items-center gap-1.5">
                    <span>⚡</span> Active Order Stream
                </h3>
                <a href="orders.php" class="text-xs font-bold text-amber-400">View All →</a>
            </div>

            <?php if (empty($live_orders)): ?>
                <div class="text-center py-6 text-xs text-zinc-500">No active orders in stream</div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($live_orders as $order): ?>
                        <div class="bg-zinc-950/70 border border-zinc-800/60 rounded-2xl p-3 flex justify-between items-center">
                            <div>
                                <div class="font-extrabold text-xs text-white">Order #<?php echo $order['id']; ?> • Table <?php echo htmlspecialchars($order['table_number']); ?></div>
                                <div class="text-[11px] text-zinc-400">Status: <span class="text-amber-400 font-bold"><?php echo strtoupper($order['status']); ?></span></div>
                            </div>
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="px-3 py-1.5 rounded-xl bg-amber-500 text-zinc-950 font-black text-xs active:scale-95">Manage</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Quick Inventory Stock Toggles -->
        <section class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-4 mb-20">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-extrabold text-sm text-white flex items-center gap-1.5">
                    <span>📦</span> Quick Stock Control
                </h3>
                <a href="menu-items.php" class="text-xs font-bold text-amber-400">Manage All →</a>
            </div>

            <div class="space-y-2">
                <?php foreach ($inventory_items as $item): ?>
                    <?php $is_active = ($item['status'] === 'active'); ?>
                    <div class="bg-zinc-950/70 border border-zinc-800/60 rounded-2xl p-3 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <span class="text-lg">🍽️</span>
                            <div>
                                <div class="font-extrabold text-xs text-white"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="text-[11px] text-amber-400 font-bold">Rs. <?php echo number_format($item['price'], 0); ?></div>
                            </div>
                        </div>

                        <!-- Instant Switch Toggle -->
                        <div class="flex items-center gap-2">
                            <span id="stock-lbl-<?php echo $item['id']; ?>" class="text-[10px] font-black <?php echo $is_active ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                <?php echo $is_active ? 'In Stock' : 'Sold Out'; ?>
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" <?php echo $is_active ? 'checked' : ''; ?> onchange="toggleItemStock(<?php echo $item['id']; ?>, this.checked)" class="sr-only peer">
                                <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <!-- Fixed Bottom Tab Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="index.php" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-[10px]">
            <span class="text-lg">📊</span>
            <span>Summary</span>
        </a>
        <a href="orders.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">📋</span>
            <span>Orders</span>
        </a>
        <a href="menu-items.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">🍔</span>
            <span>Items</span>
        </a>
        <a href="../kitchen-dashboard.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">👨‍🍳</span>
            <span>KDS</span>
        </a>
    </nav>

    <script src="../js/modern.js"></script>
    <script>
        function toggleItemStock(itemId, isChecked) {
            const status = isChecked ? 'active' : 'sold_out';
            const label = document.getElementById('stock-lbl-' + itemId);
            fetch('menu-items.php?action=toggle_status&id=' + itemId + '&status=' + status)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (label) {
                            label.textContent = isChecked ? 'In Stock' : 'Sold Out';
                            label.className = 'text-[10px] font-black ' + (isChecked ? 'text-emerald-400' : 'text-rose-400');
                        }
                        showToast(isChecked ? 'Item marked In Stock!' : 'Item marked Sold Out!', isChecked ? 'success' : 'warning');
                    }
                });
        }
    </script>
</body>
</html>
