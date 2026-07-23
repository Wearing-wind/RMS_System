<?php
// Manager Dashboard - Responsive Adaptive Architecture
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
<body class="min-h-full font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <!-- DESKTOP LEFT SIDEBAR (Visible on md: & lg: screens) -->
    <aside class="hidden md:flex flex-col w-64 fixed inset-y-0 left-0 bg-zinc-950 border-r border-zinc-800/80 p-5 z-40">
        <div class="flex items-center gap-3 pb-6 border-b border-zinc-800/80">
            <span class="text-3xl">☕</span>
            <div>
                <h2 class="font-black text-white text-base leading-tight">QR Cafe</h2>
                <p class="text-[10px] text-zinc-400 font-bold uppercase tracking-wider">Manager Console</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1.5 pt-6">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs shadow-lg shadow-amber-500/20">
                <span class="text-lg">📊</span>
                <span>Dashboard Summary</span>
            </a>
            <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">📋</span>
                <span>Live Orders Queue</span>
            </a>
            <a href="menu-items.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">🍔</span>
                <span>Menu Inventory</span>
            </a>
            <a href="tables.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">📍</span>
                <span>Seating & Tables</span>
            </a>
            <a href="categories.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">🏷️</span>
                <span>Categories</span>
            </a>
            <a href="payment-settings.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">💳</span>
                <span>Payment QR Config</span>
            </a>
            <a href="../kitchen-dashboard.php" target="_blank" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-amber-400 hover:bg-amber-500/10 font-bold text-xs transition-all mt-4 border border-amber-500/20">
                <span class="text-lg">👨‍🍳</span>
                <span>Open KDS Monitor ↗</span>
            </a>
        </nav>

        <div class="pt-4 border-t border-zinc-800/80">
            <a href="logout.php" class="flex items-center gap-2 text-xs font-bold text-rose-400 hover:text-rose-300">
                <span>🚪</span> Logout Administrator
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA (Padded on desktop md:pl-64) -->
    <div class="md:pl-64 min-h-screen pb-24 md:pb-8">

        <!-- Header -->
        <header class="sticky top-0 z-30 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 md:px-8 py-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-2">
                <div>
                    <h1 class="text-lg md:text-xl font-black text-white">Dashboard Analytics & Control</h1>
                    <p class="text-xs text-zinc-400 hidden sm:block">Real-time overview of restaurant sales and live order activity</p>
                </div>
                <a href="logout.php" class="md:hidden text-xs font-extrabold text-rose-400 bg-rose-500/10 border border-rose-500/20 px-3 py-1.5 rounded-full">Logout 🚪</a>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 md:px-8 pt-4 space-y-6">

            <!-- Mobile Quick Navigation Carousel (Hidden on md: desktop) -->
            <nav class="md:hidden flex gap-2 overflow-x-auto no-scrollbar py-1">
                <a href="index.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">📊 Dashboard</a>
                <a href="menu-items.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🍔 Menu Items</a>
                <a href="orders.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📋 Live Orders</a>
                <a href="tables.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📍 Tables</a>
                <a href="categories.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🏷️ Categories</a>
                <a href="payment-settings.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">💳 Payment QR</a>
            </nav>

            <!-- Adaptive Metrics Grid (2 cols mobile, 4 cols desktop) -->
            <section>
                <h3 class="text-xs font-extrabold text-zinc-400 uppercase tracking-wider mb-3">Today's Key Performance Metrics</h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 flex flex-col justify-between shadow-xl">
                        <div class="text-xs font-bold text-zinc-400 flex justify-between items-center">
                            <span>💰 Today's Sales</span>
                            <span class="text-amber-500 text-sm">📈</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-black text-amber-400 mt-3">Rs. <?php echo number_format($today_sales, 0); ?></div>
                    </div>
                    <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 flex flex-col justify-between shadow-xl">
                        <div class="text-xs font-bold text-zinc-400 flex justify-between items-center">
                            <span>🔥 Active Orders</span>
                            <span class="text-cyan-400 text-sm">⚡</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-black text-cyan-400 mt-3"><?php echo $active_orders_count; ?></div>
                    </div>
                    <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 flex flex-col justify-between shadow-xl">
                        <div class="text-xs font-bold text-zinc-400 flex justify-between items-center">
                            <span>✅ Served Orders</span>
                            <span class="text-emerald-400 text-sm">✔</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-black text-emerald-400 mt-3"><?php echo $completed_orders; ?></div>
                    </div>
                    <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 flex flex-col justify-between shadow-xl">
                        <div class="text-xs font-bold text-zinc-400 flex justify-between items-center">
                            <span>📋 Total Placed</span>
                            <span class="text-zinc-300 text-sm">📦</span>
                        </div>
                        <div class="text-2xl md:text-3xl font-black text-white mt-3"><?php echo $today_orders; ?></div>
                    </div>
                </div>
            </section>

            <!-- 2-Column Responsive Layout (Active Stream Left, Inventory Stock Toggles Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Live Orders Stream (2 cols wide on desktop) -->
                <section class="lg:col-span-2 bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-5 shadow-xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-extrabold text-sm md:text-base text-white flex items-center gap-2">
                            <span>⚡</span> Active Order Stream
                        </h3>
                        <a href="orders.php" class="text-xs font-bold text-amber-400 hover:underline">View All Orders →</a>
                    </div>

                    <?php if (empty($live_orders)): ?>
                        <div class="text-center py-10 text-xs text-zinc-500">No active orders in live stream</div>
                    <?php else: ?>
                        
                        <!-- Desktop High-Density Data Table (Visible on md: & lg:) -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-zinc-800 text-zinc-400 font-extrabold uppercase">
                                        <th class="pb-3 px-3">Order ID</th>
                                        <th class="pb-3 px-3">Table #</th>
                                        <th class="pb-3 px-3">Time</th>
                                        <th class="pb-3 px-3">Status</th>
                                        <th class="pb-3 px-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-800/60">
                                    <?php foreach ($live_orders as $order): ?>
                                        <tr class="hover:bg-zinc-950/40">
                                            <td class="py-3 px-3 font-black text-white">#<?php echo $order['id']; ?></td>
                                            <td class="py-3 px-3 font-bold text-amber-400">Table <?php echo htmlspecialchars($order['table_number']); ?></td>
                                            <td class="py-3 px-3 text-zinc-400"><?php echo htmlspecialchars($order['created_at']); ?></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-[10px] uppercase">
                                                    <?php echo htmlspecialchars($order['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-right">
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="px-3 py-1.5 rounded-xl bg-amber-500 text-zinc-950 font-black text-xs active:scale-95 shadow-md">Manage →</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card List (Visible on small screens) -->
                        <div class="md:hidden space-y-2">
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

                <!-- Quick Inventory Stock Toggles (1 col wide on desktop) -->
                <section class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-5 shadow-xl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-extrabold text-sm md:text-base text-white flex items-center gap-2">
                            <span>📦</span> Quick Stock Control
                        </h3>
                        <a href="menu-items.php" class="text-xs font-bold text-amber-400 hover:underline">Manage All →</a>
                    </div>

                    <div class="space-y-2.5">
                        <?php foreach ($inventory_items as $item): ?>
                            <?php $is_active = ($item['status'] === 'active'); ?>
                            <div class="bg-zinc-950/70 border border-zinc-800/60 rounded-2xl p-3 flex justify-between items-center">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="text-xl shrink-0">🍽️</span>
                                    <div class="min-w-0">
                                        <div class="font-extrabold text-xs text-white truncate"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="text-[11px] text-amber-400 font-bold">Rs. <?php echo number_format($item['price'], 0); ?></div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span id="stock-lbl-<?php echo $item['id']; ?>" class="text-[10px] font-black <?php echo $is_active ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                        <?php echo $is_active ? 'In Stock' : 'Out of Stock'; ?>
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

            </div>

        </main>
    </div>

    <!-- Mobile Bottom Navigation Bar (Hidden on md: desktop) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
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
        <a href="tables.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">📍</span>
            <span>Tables</span>
        </a>
    </nav>

    <script src="../js/modern.js"></script>
    <script>
        function toggleItemStock(itemId, isChecked) {
            const status = isChecked ? 'active' : 'sold_out';
            const label = document.getElementById('stock-lbl-' + itemId);
            fetch('../api/toggle-stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: itemId, status: status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (label) {
                        label.textContent = isChecked ? 'In Stock' : 'Out of Stock';
                        label.className = 'text-[10px] font-black ' + (isChecked ? 'text-emerald-400' : 'text-rose-400');
                    }
                    showToast(isChecked ? 'Item marked In Stock!' : 'Item marked Out of Stock!', isChecked ? 'success' : 'warning');
                } else {
                    showToast(data.message || 'Failed to update stock status', 'error');
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</body>
</html>
