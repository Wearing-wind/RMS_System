<?php
// Admin Live Orders - Tailwind Mobile Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database connection failed.");
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

if ($status_filter !== 'all') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
}

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
    <title>Manage Orders - QR Cafe</title>
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

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between gap-2">
            <a href="index.php" class="flex items-center gap-2 font-black text-lg text-white">
                <span>📋</span>
                <span>Manage Live Orders</span>
            </a>
            <a href="index.php" class="text-xs font-bold text-amber-400">Dashboard →</a>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-3 space-y-4">

        <!-- Status Filter Segmented Carousel -->
        <nav class="flex gap-2 overflow-x-auto no-scrollbar py-1">
            <a href="orders.php?status=all" class="px-4 py-2 rounded-2xl font-bold text-xs whitespace-nowrap <?php echo $status_filter === 'all' ? 'bg-amber-500 text-zinc-950 font-black shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800 text-zinc-300'; ?>">All Orders</a>
            <a href="orders.php?status=new" class="px-4 py-2 rounded-2xl font-bold text-xs whitespace-nowrap <?php echo $status_filter === 'new' ? 'bg-amber-500 text-zinc-950 font-black shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800 text-zinc-300'; ?>">🆕 New</a>
            <a href="orders.php?status=preparing" class="px-4 py-2 rounded-2xl font-bold text-xs whitespace-nowrap <?php echo $status_filter === 'preparing' ? 'bg-amber-500 text-zinc-950 font-black shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800 text-zinc-300'; ?>">🔥 Prep</a>
            <a href="orders.php?status=ready" class="px-4 py-2 rounded-2xl font-bold text-xs whitespace-nowrap <?php echo $status_filter === 'ready' ? 'bg-amber-500 text-zinc-950 font-black shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800 text-zinc-300'; ?>">✅ Ready</a>
            <a href="orders.php?status=completed" class="px-4 py-2 rounded-2xl font-bold text-xs whitespace-nowrap <?php echo $status_filter === 'completed' ? 'bg-amber-500 text-zinc-950 font-black shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800 text-zinc-300'; ?>">✔ Completed</a>
            <a href="orders.php?status=cancelled" class="px-4 py-2 rounded-2xl font-bold text-xs whitespace-nowrap <?php echo $status_filter === 'cancelled' ? 'bg-amber-500 text-zinc-950 font-black shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800 text-zinc-300'; ?>">🚫 Cancelled</a>
        </nav>

        <!-- Orders Stream Stack -->
        <div class="space-y-3 mb-20">
            <?php if (empty($orders)): ?>
                <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 text-center text-zinc-500">
                    <div class="text-3xl mb-2">📋</div>
                    <h3 class="font-bold">No orders found</h3>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-4 flex flex-col justify-between space-y-3">
                        <div class="flex justify-between items-start pb-2 border-b border-zinc-800">
                            <div>
                                <div class="font-extrabold text-sm text-white">Order #<?php echo $order['id']; ?> • Table <?php echo htmlspecialchars($order['table_number']); ?></div>
                                <div class="text-[11px] text-zinc-400"><?php echo htmlspecialchars($order['created_at']); ?></div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-[10px] uppercase"><?php echo htmlspecialchars($order['status']); ?></span>
                        </div>

                        <div class="flex justify-between items-center pt-1">
                            <div class="text-sm font-black text-amber-400">
                                Total: Rs. <?php echo number_format($order['total_amount'] ?? 0, 2); ?>
                            </div>
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="px-4 py-2 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs active:scale-95 shadow-lg shadow-amber-500/10">
                                View & Edit →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <!-- Manager Bottom Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
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
