<?php
// Admin Orders Management - Responsive Adaptive Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database connection failed.");
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

$sql = "SELECT o.*, 
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
        (SELECT SUM(quantity * price) FROM order_items WHERE order_id = o.id) as calc_total
        FROM orders o";

if ($status_filter !== 'all') {
    $status_safe = $conn->real_escape_string($status_filter);
    $sql .= " WHERE o.status = '$status_safe'";
}

$sql .= " ORDER BY o.created_at DESC";

$result = $conn->query($sql);
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>All Live Orders - QR Cafe</title>
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

    <!-- DESKTOP LEFT SIDEBAR -->
    <aside class="hidden md:flex flex-col w-64 fixed inset-y-0 left-0 bg-zinc-950 border-r border-zinc-800/80 p-5 z-40">
        <div class="flex items-center gap-3 pb-6 border-b border-zinc-800/80">
            <span class="text-3xl">☕</span>
            <div>
                <h2 class="font-black text-white text-base leading-tight">QR Cafe</h2>
                <p class="text-[10px] text-zinc-400 font-bold uppercase tracking-wider">Manager Console</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1.5 pt-6">
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">📊</span>
                <span>Dashboard Summary</span>
            </a>
            <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs shadow-lg shadow-amber-500/20">
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
        </nav>

        <div class="pt-4 border-t border-zinc-800/80">
            <a href="logout.php" class="flex items-center gap-2 text-xs font-bold text-rose-400 hover:text-rose-300">
                <span>🚪</span> Logout Administrator
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="md:pl-64 min-h-screen pb-24 md:pb-8">

        <!-- Header -->
        <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 md:px-8 py-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-2">
                <div>
                    <h1 class="text-lg md:text-xl font-black text-white">Live Orders Management</h1>
                    <p class="text-xs text-zinc-400 hidden sm:block">Monitor and update active dining table order statuses in real-time</p>
                </div>
                <a href="../kitchen-dashboard.php" target="_blank" class="px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-black text-xs">
                    👨‍🍳 Open KDS Monitor ↗
                </a>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 md:px-8 pt-4 space-y-6">

            <!-- Mobile Navigation Carousel (Hidden on md: desktop) -->
            <nav class="md:hidden flex gap-2 overflow-x-auto no-scrollbar py-1">
                <a href="index.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📊 Dashboard</a>
                <a href="menu-items.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🍔 Menu Items</a>
                <a href="orders.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">📋 Live Orders</a>
                <a href="tables.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📍 Tables</a>
                <a href="categories.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🏷️ Categories</a>
            </nav>

            <!-- Status Filter Tabs Carousel -->
            <div class="flex gap-2 overflow-x-auto no-scrollbar py-1">
                <?php
                $tabs = [
                    'all' => '🔥 All Orders',
                    'new' => '🆕 New',
                    'preparing' => '🍳 Preparing',
                    'ready' => '✅ Ready',
                    'completed' => '✔ Served',
                    'cancelled' => '❌ Rejected'
                ];
                foreach ($tabs as $key => $label):
                    $active = ($status_filter === $key);
                ?>
                    <a href="orders.php?status=<?php echo $key; ?>" class="px-4 py-2 rounded-2xl font-black text-xs whitespace-nowrap transition-all <?php echo $active ? 'bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white'; ?>">
                        <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Orders Table (Desktop) & Cards (Mobile) -->
            <section class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-5 shadow-xl">
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-12 text-zinc-500">
                        <div class="text-4xl mb-2">📋</div>
                        <h3 class="font-bold text-sm">No orders found for this status</h3>
                    </div>
                <?php else: ?>

                    <!-- Desktop High-Density Data Table (Visible on md: & lg:) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-zinc-800 text-zinc-400 font-extrabold uppercase">
                                    <th class="pb-3 px-3">Order ID</th>
                                    <th class="pb-3 px-3">Table #</th>
                                    <th class="pb-3 px-3">Placed Time</th>
                                    <th class="pb-3 px-3">Items Count</th>
                                    <th class="pb-3 px-3">Total Amount</th>
                                    <th class="pb-3 px-3">Status</th>
                                    <th class="pb-3 px-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/60">
                                <?php foreach ($orders as $o): ?>
                                    <?php $amt = $o['calc_total'] ?? $o['total_amount'] ?? 0; ?>
                                    <tr class="hover:bg-zinc-950/40">
                                        <td class="py-3 px-3 font-black text-white">#<?php echo $o['id']; ?></td>
                                        <td class="py-3 px-3 font-bold text-amber-400">Table <?php echo htmlspecialchars($o['table_number']); ?></td>
                                        <td class="py-3 px-3 text-zinc-400"><?php echo htmlspecialchars($o['created_at']); ?></td>
                                        <td class="py-3 px-3 font-bold text-zinc-300"><?php echo $o['item_count']; ?> items</td>
                                        <td class="py-3 px-3 font-black text-amber-400">Rs. <?php echo number_format($amt, 0); ?></td>
                                        <td class="py-3 px-3">
                                            <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-[10px] uppercase">
                                                <?php echo htmlspecialchars($o['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-right">
                                            <a href="order-details.php?id=<?php echo $o['id']; ?>" class="px-3.5 py-1.5 rounded-xl bg-amber-500 text-zinc-950 font-black text-xs active:scale-95 shadow-md">View Details →</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card List (Visible on small screens) -->
                    <div class="md:hidden space-y-3">
                        <?php foreach ($orders as $o): ?>
                            <?php $amt = $o['calc_total'] ?? $o['total_amount'] ?? 0; ?>
                            <div class="bg-zinc-950/70 border border-zinc-800/60 rounded-2xl p-3.5 flex justify-between items-center">
                                <div>
                                    <div class="font-extrabold text-sm text-white">Order #<?php echo $o['id']; ?> • Table <?php echo htmlspecialchars($o['table_number']); ?></div>
                                    <div class="text-xs text-amber-400 font-black mt-0.5">Rs. <?php echo number_format($amt, 0); ?> • <?php echo $o['item_count']; ?> items</div>
                                    <div class="text-[10px] text-zinc-400 mt-1"><?php echo htmlspecialchars($o['created_at']); ?></div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-[10px] uppercase block mb-2">
                                        <?php echo htmlspecialchars($o['status']); ?>
                                    </span>
                                    <a href="order-details.php?id=<?php echo $o['id']; ?>" class="px-3 py-1.5 rounded-xl bg-amber-500 text-zinc-950 font-black text-xs active:scale-95">Manage →</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </section>

        </main>
    </div>

    <!-- Mobile Bottom Navigation Bar (Hidden on md: desktop) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="index.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">📊</span>
            <span>Summary</span>
        </a>
        <a href="orders.php" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-[10px]">
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
</body>
</html>
