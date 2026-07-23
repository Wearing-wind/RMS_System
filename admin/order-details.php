<?php
// Admin Order Details - Tailwind Mobile Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();
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
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Order #<?php echo $order_id; ?> - QR Cafe</title>
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
    </style>
</head>
<body class="min-h-full pb-24 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between gap-2">
            <a href="orders.php" class="flex items-center gap-2 font-black text-lg text-white">
                <span>📋</span> Order #<?php echo $order_id; ?>
            </a>
            <a href="orders.php" class="text-xs font-bold text-amber-400">← Back to Orders</a>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-4">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 shadow-2xl space-y-4">
            
            <div class="flex justify-between items-start pb-3 border-b border-zinc-800">
                <div>
                    <h2 class="text-lg font-black text-white">Order #<?php echo $order_id; ?></h2>
                    <div class="text-xs text-zinc-400">📍 Table <?php echo htmlspecialchars($order['table_number']); ?></div>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-[10px] uppercase">
                    <?php echo htmlspecialchars($order['status']); ?>
                </span>
            </div>

            <div class="space-y-1.5 text-xs text-zinc-300">
                <div class="flex justify-between">
                    <span class="text-zinc-400">Created At:</span>
                    <strong class="text-white"><?php echo htmlspecialchars($order['created_at']); ?></strong>
                </div>
                <?php if (!empty($order['customer_name'])): ?>
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Customer:</span>
                        <strong class="text-white"><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['notes'])): ?>
                    <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 mt-2">
                        <strong>📝 Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <h4 class="text-xs font-extrabold text-zinc-400 uppercase tracking-wider">Ordered Items:</h4>
            <div class="space-y-2">
                <?php foreach ($items as $item): ?>
                    <div class="flex justify-between items-center p-2.5 rounded-2xl bg-zinc-950/70 border border-zinc-800/60 text-xs">
                        <span><?php echo htmlspecialchars($item['name']); ?> <strong class="text-amber-400">x<?php echo $item['quantity']; ?></strong></span>
                        <span class="font-black text-amber-400">Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-between items-center pt-3 border-t border-zinc-800 font-black text-base">
                <span>Total Amount:</span>
                <span class="text-amber-400 text-xl">Rs. <?php echo number_format($total, 2); ?></span>
            </div>

            <!-- Action Controls -->
            <div class="grid grid-cols-2 gap-2 pt-2">
                <button onclick="updateOrderStatus(<?php echo $order_id; ?>, 'preparing')" class="h-12 rounded-2xl bg-amber-500/20 border border-amber-500 text-amber-300 font-black text-xs active:scale-95">
                    🔥 Start Prep
                </button>
                <button onclick="updateOrderStatus(<?php echo $order_id; ?>, 'ready')" class="h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500 text-emerald-300 font-black text-xs active:scale-95">
                    ✅ Mark Ready
                </button>
                <button onclick="updateOrderStatus(<?php echo $order_id; ?>, 'completed')" class="col-span-2 h-12 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-xs active:scale-95 shadow-lg shadow-amber-500/10">
                    ✔ Mark Served / Done
                </button>
                <button onclick="updateOrderStatus(<?php echo $order_id; ?>, 'cancelled', 'Cancelled by Manager')" class="col-span-2 h-10 rounded-2xl bg-rose-500/20 border border-rose-500/40 text-rose-400 font-bold text-xs">
                    ❌ Cancel Order
                </button>
            </div>
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
    <script>
        function updateOrderStatus(orderId, status, reason = '') {
            fetch('../api/update-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: status, reason: reason })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Order status updated!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message || 'Error updating order', 'error');
                }
            });
        }
    </script>
</body>
</html>
