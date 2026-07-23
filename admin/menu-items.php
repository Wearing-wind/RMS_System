<?php
// Admin Menu Items Management - Tailwind Mobile Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database connection failed.");
}

// Handle Add/Delete Item Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $status = isset($_POST['status']) ? 'active' : 'sold_out';
            $dietary_type = sanitize($_POST['dietary_type'] ?? 'veg');
            
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../images/';
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image = $file_name;
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO menu_items (name, description, price, image, category_id, status, dietary_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsiss", $name, $description, $price, $image, $category_id, $status, $dietary_type);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Item added successfully';
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Item deleted successfully';
        }
    }
    header('Location: menu-items.php');
    exit;
}

$categories_res = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = [];
while ($cat = $categories_res->fetch_assoc()) {
    $categories[$cat['id']] = $cat['name'];
}

$items_res = $conn->query("SELECT * FROM menu_items ORDER BY category_id, name");
$items = [];
while ($item = $items_res->fetch_assoc()) {
    $items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Menu Items Manager - QR Cafe</title>
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
                <span>🍔</span>
                <span>Menu Inventory</span>
            </a>
            <button onclick="document.getElementById('addItemCard').scrollIntoView({behavior:'smooth'})" class="px-3 py-1.5 rounded-full bg-amber-500 text-zinc-950 font-black text-xs">
                + Add Item
            </button>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-3 space-y-4">

        <!-- Navigation Carousel -->
        <nav class="flex gap-2 overflow-x-auto no-scrollbar py-1">
            <a href="index.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📊 Dashboard</a>
            <a href="menu-items.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">🍔 Menu Items</a>
            <a href="orders.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📋 Live Orders</a>
            <a href="tables.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📍 Tables</a>
            <a href="categories.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🏷️ Categories</a>
            <a href="payment-settings.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">💳 Payment QR</a>
        </nav>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Item Cards with Instant Stock Switch -->
        <section class="space-y-3">
            <h3 class="text-xs font-extrabold text-zinc-400 uppercase tracking-wider">Instant Stock Control (Tap Switch)</h3>

            <?php foreach ($items as $item): ?>
                <?php $is_active = ($item['status'] === 'active'); ?>
                <div class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-3.5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-zinc-950 border border-zinc-800 overflow-hidden flex items-center justify-center text-2xl shrink-0">
                            <?php if (!empty($item['image'])): ?>
                                <img src="../images/<?php echo htmlspecialchars($item['image']); ?>" alt="img" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='🍽️'">
                            <?php else: ?>
                                🍽️
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-extrabold text-sm text-white truncate"><?php echo htmlspecialchars($item['name']); ?></h4>
                            <div class="text-[11px] text-zinc-400"><?php echo htmlspecialchars($categories[$item['category_id']] ?? 'General'); ?></div>
                            <div class="text-xs font-black text-amber-400 mt-0.5">Rs. <?php echo number_format($item['price'], 0); ?></div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <div class="text-right">
                            <span id="stock-lbl-<?php echo $item['id']; ?>" class="text-[10px] font-black block <?php echo $is_active ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                <?php echo $is_active ? 'In Stock' : 'Out of Stock'; ?>
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" <?php echo $is_active ? 'checked' : ''; ?> onchange="toggleItemStock(<?php echo $item['id']; ?>, this.checked)" class="sr-only peer">
                                <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>
                        <form method="POST" onsubmit="return confirm('Delete this item?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="text-xs text-rose-400 font-bold p-1">🗑️</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Add Item Form Card -->
        <section id="addItemCard" class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-5 shadow-2xl mb-20 space-y-4">
            <h3 class="text-base font-black text-white flex items-center gap-2">
                <span>➕</span> Add New Menu Item
            </h3>

            <form method="POST" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="action" value="add">

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Item Name</label>
                    <input type="text" name="name" required placeholder="e.g. Artisanal Cappuccino" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Category</label>
                    <select name="category_id" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                        <?php foreach ($categories as $cat_id => $cat_name): ?>
                            <option value="<?php echo $cat_id; ?>"><?php echo htmlspecialchars($cat_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1">Price (Rs.)</label>
                        <input type="number" step="0.01" name="price" required placeholder="250" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1">Dietary</label>
                        <select name="dietary_type" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                            <option value="veg">Veg</option>
                            <option value="non-veg">Non-Veg</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Description</label>
                    <textarea name="description" rows="2" placeholder="Brief description..." class="w-full bg-zinc-950 border border-zinc-800 rounded-2xl p-3.5 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500 resize-none"></textarea>
                </div>

                <button type="submit" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                    Save Menu Item
                </button>
            </form>
        </section>

    </main>

    <!-- Manager Bottom Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="index.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">📊</span>
            <span>Summary</span>
        </a>
        <a href="orders.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">📋</span>
            <span>Orders</span>
        </a>
        <a href="menu-items.php" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-[10px]">
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
                        label.className = 'text-[10px] font-black block ' + (isChecked ? 'text-emerald-400' : 'text-rose-400');
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
