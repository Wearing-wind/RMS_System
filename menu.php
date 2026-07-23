<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Menu - QR Cafe & Dining</title>
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
<body class="min-h-full pb-24 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <?php
    require_once 'config.php';
    $conn = getDBConnection();
    $db_error = ($conn === null);
    $table_num = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1';
    ?>

    <!-- Sticky Mobile Top Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between gap-3">
            <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="flex items-center gap-2 text-lg font-black tracking-tight text-white">
                <span class="text-xl">☕</span>
                <span>QR Cafe</span>
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-xs">
                <span>📍</span> Table <?php echo $table_num; ?>
            </span>
        </div>
    </header>

    <!-- Mobile Shell Container -->
    <main class="max-w-md mx-auto px-4 pt-3">
        
        <!-- Search Input -->
        <div class="relative mb-3">
            <input type="text" id="searchInput" placeholder="Search coffee, burgers, desserts..." class="w-full bg-zinc-900 border border-zinc-800 rounded-2xl py-3 pl-11 pr-4 text-sm text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition-all">
            <span class="absolute left-4 top-3.5 text-zinc-500 text-sm">🔍</span>
        </div>

        <!-- Sticky Horizontal Category Carousel -->
        <nav class="sticky top-[57px] z-30 bg-zinc-950/90 backdrop-blur-md py-2 -mx-4 px-4 mb-4">
            <div class="flex gap-2 overflow-x-auto no-scrollbar">
                <?php if (!$db_error): ?>
                    <?php
                    $categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
                    $categories = [];
                    while ($cat = $categories_result->fetch_assoc()) {
                        $categories[] = $cat;
                    }
                    $current_cat = isset($_GET['category']) ? intval($_GET['category']) : 0;
                    ?>
                    <button onclick="filterByCategory(0)" class="px-4 py-2.5 rounded-2xl font-bold text-xs whitespace-nowrap transition-all <?php echo $current_cat === 0 ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800/80 text-zinc-300 active:scale-95'; ?>">
                        🔥 All Dishes
                    </button>
                    <?php foreach ($categories as $cat): ?>
                        <button onclick="filterByCategory(<?php echo $cat['id']; ?>)" class="px-4 py-2.5 rounded-2xl font-bold text-xs whitespace-nowrap transition-all <?php echo $current_cat === $cat['id'] ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 shadow-lg shadow-amber-500/20' : 'bg-zinc-900 border border-zinc-800/80 text-zinc-300 active:scale-95'; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Food Grid View (2-Column Mobile Native Layout) -->
        <section class="mb-20">
            <?php if ($db_error): ?>
                <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6 text-center">
                    <div class="text-3xl mb-2">⚠️</div>
                    <h3 class="font-bold text-zinc-200">Database Connection Failed</h3>
                </div>
            <?php else: ?>
                <div id="menuGrid" class="grid grid-cols-2 gap-3.5">
                    <?php
                    $category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
                    if ($category_filter > 0) {
                        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE status != 'inactive' AND category_id = ? ORDER BY name");
                        $stmt->bind_param("i", $category_filter);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } else {
                        $result = $conn->query("SELECT * FROM menu_items WHERE status != 'inactive' ORDER BY category_id, name");
                    }

                    if ($result && $result->num_rows > 0) {
                        while ($item = $result->fetch_assoc()) {
                            $sold_out = ($item['status'] === 'sold_out');
                            $dietary = strtolower($item['dietary_type'] ?? 'veg');
                            $dietary_color = ($dietary === 'non-veg') ? 'border-red-500 bg-red-500' : 'border-emerald-500 bg-emerald-500';
                            
                            echo '<div class="menu-item bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-3 flex flex-col justify-between transition-all active:scale-[0.98]" data-name="' . strtolower(htmlspecialchars($item['name'])) . '" data-description="' . strtolower(htmlspecialchars($item['description'])) . '">';
                            
                            // Image container
                            echo '<div class="relative aspect-square w-full rounded-2xl bg-zinc-950 overflow-hidden mb-2.5 flex items-center justify-center text-4xl">';
                            if (!empty($item['image'])) {
                                echo '<img src="images/' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.innerHTML=\'🍽️\'">';
                            } else {
                                echo '🍽️';
                            }
                            if (!empty($item['is_popular'])) {
                                echo '<span class="absolute top-2 left-2 bg-amber-500 text-zinc-950 font-black text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider">⭐ Top</span>';
                            }
                            echo '</div>';

                            // Food Info
                            echo '<div class="flex-1 flex flex-col mb-2">';
                            echo '<div class="flex items-center gap-1.5 mb-1">';
                            echo '<span class="w-3.5 h-3.5 rounded-sm border-2 ' . $dietary_color . ' flex items-center justify-center"><span class="w-1.5 h-1.5 rounded-full bg-white"></span></span>';
                            echo '<h3 class="font-extrabold text-sm text-zinc-100 truncate">' . htmlspecialchars($item['name']) . '</h3>';
                            echo '</div>';
                            echo '<p class="text-xs text-zinc-400 line-clamp-2 mb-2">' . htmlspecialchars($item['description']) . '</p>';
                            echo '</div>';

                            // Price & Action Button
                            echo '<div class="mt-auto flex flex-col gap-2">';
                            echo '<span class="text-base font-black text-amber-400">Rs. ' . number_format($item['price'], 0) . '</span>';
                            if (!$sold_out) {
                                $prepTime = isset($item['preparation_time']) ? intval($item['preparation_time']) : 15;
                                echo '<button onclick="openCustomModal(' . $item['id'] . ', \'' . addslashes(htmlspecialchars($item['name'])) . '\', ' . $item['price'] . ', \'' . addslashes(htmlspecialchars($item['description'])) . '\', ' . $prepTime . ')" class="h-11 w-full rounded-2xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-zinc-950 font-black text-xs transition-all shadow-lg shadow-amber-500/10 flex items-center justify-center gap-1">+ Add</button>';
                            } else {
                                echo '<button disabled class="h-11 w-full rounded-2xl bg-zinc-800 text-zinc-500 font-bold text-xs">Sold Out</button>';
                            }
                            echo '</div>';

                            echo '</div>';
                        }
                    } else {
                        echo '<div class="col-span-2 bg-zinc-900 border border-zinc-800 rounded-3xl p-8 text-center text-zinc-400">';
                        echo '<div class="text-4xl mb-2">🍽️</div>';
                        echo '<h3 class="font-bold">No dishes found</h3>';
                        echo '</div>';
                    }
                    $conn->close();
                    ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Persistent Floating Cart Bar -->
    <div id="floatingCartBar" class="fixed bottom-20 left-4 right-4 z-40 max-w-md mx-auto bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 p-4 rounded-3xl shadow-2xl flex items-center justify-between font-extrabold cursor-pointer active:scale-95 transition-all" style="display: none;" onclick="openCartPanel()">
        <div class="flex items-center gap-2.5">
            <span id="floatCartCount" class="bg-zinc-950 text-amber-400 w-7 h-7 rounded-full flex items-center justify-center text-xs font-black">0</span>
            <span class="text-sm">Items in Cart</span>
        </div>
        <div class="flex items-center gap-2">
            <span id="floatCartTotal" class="text-base font-black">Rs. 0</span>
            <span class="text-xs bg-zinc-950/20 px-2.5 py-1 rounded-full">Checkout →</span>
        </div>
    </div>

    <!-- Slide-Up Bottom Sheet Customization Modal -->
    <div id="customModal" class="fixed inset-0 z-50 flex items-end justify-center opacity-0 pointer-events-none transition-all duration-300">
        <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-md" onclick="closeCustomModal()"></div>
        <div class="relative z-10 w-full max-w-md bg-zinc-900 border-t border-zinc-800 rounded-t-3xl p-6 shadow-2xl translate-y-full transition-transform duration-300 max-h-[85vh] overflow-y-auto">
            <button onclick="closeCustomModal()" class="absolute top-4 right-4 bg-zinc-800 text-zinc-400 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">✕</button>
            <div class="text-4xl text-center mb-2" id="customModalImage">🍽️</div>
            <h3 class="text-lg font-black text-center text-white" id="customModalTitle">Item Name</h3>
            <p class="text-xs text-zinc-400 text-center mb-3" id="customModalDesc">Description</p>
            <div class="text-center font-black text-amber-400 text-lg mb-4" id="customModalPrice">Rs. 0</div>

            <!-- Customization Options -->
            <div class="space-y-4 mb-6 bg-zinc-950/50 p-4 rounded-2xl border border-zinc-800/60">
                <div>
                    <h4 class="text-xs font-bold text-zinc-300 mb-2">🌶️ Spice Level</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="bg-zinc-900 border border-zinc-800 rounded-xl p-2.5 text-center cursor-pointer text-xs font-bold text-zinc-300 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 has-[:checked]:text-amber-400">
                            <input type="radio" name="spice_level" value="mild" checked class="hidden"> Mild
                        </label>
                        <label class="bg-zinc-900 border border-zinc-800 rounded-xl p-2.5 text-center cursor-pointer text-xs font-bold text-zinc-300 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 has-[:checked]:text-amber-400">
                            <input type="radio" name="spice_level" value="medium" class="hidden"> Medium 🌶️
                        </label>
                        <label class="bg-zinc-900 border border-zinc-800 rounded-xl p-2.5 text-center cursor-pointer text-xs font-bold text-zinc-300 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 has-[:checked]:text-amber-400">
                            <input type="radio" name="spice_level" value="hot" class="hidden"> Spicy 🔥
                        </label>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-zinc-300 mb-2">🧀 Add-ons</h4>
                    <div class="space-y-2">
                        <label class="flex items-center justify-between bg-zinc-900 border border-zinc-800 rounded-xl p-3 cursor-pointer text-xs font-bold text-zinc-300">
                            <span>Extra Cheese (+Rs. 50)</span>
                            <input type="checkbox" id="extraCheese" data-price="50" class="w-4 h-4 accent-amber-500">
                        </label>
                        <label class="flex items-center justify-between bg-zinc-900 border border-zinc-800 rounded-xl p-3 cursor-pointer text-xs font-bold text-zinc-300">
                            <span>Crispy Fries (+Rs. 80)</span>
                            <input type="checkbox" id="extraFries" data-price="80" class="w-4 h-4 accent-amber-500">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer Controls -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-3 bg-zinc-950 border border-zinc-800 rounded-2xl p-1.5 px-3">
                    <button onclick="customModalChangeQty(-1)" class="w-8 h-8 rounded-xl bg-zinc-800 text-white font-black text-lg flex items-center justify-center active:bg-amber-500 active:text-zinc-950">−</button>
                    <span id="customModalQty" class="font-black text-base w-5 text-center text-white">1</span>
                    <button onclick="customModalChangeQty(1)" class="w-8 h-8 rounded-xl bg-zinc-800 text-white font-black text-lg flex items-center justify-center active:bg-amber-500 active:text-zinc-950">+</button>
                </div>
                <button onclick="addCustomToCart()" class="flex-1 h-12 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-sm active:scale-95 shadow-lg shadow-amber-500/20">
                    Add to Cart • <span id="customModalTotal">Rs. 0</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Slide-out Cart Drawer Panel -->
    <div class="cart-overlay fixed inset-0 bg-zinc-950/80 backdrop-blur-sm z-50 opacity-0 pointer-events-none transition-all"></div>
    <div class="cart-panel fixed inset-y-0 right-0 z-50 w-full max-w-md bg-zinc-900 border-l border-zinc-800 p-6 flex flex-col translate-x-full transition-transform duration-300">
        <div class="flex justify-between items-center pb-4 border-b border-zinc-800 mb-4">
            <h3 class="text-lg font-black text-white">🛒 Your Cart</h3>
            <button class="cart-close bg-zinc-800 text-zinc-400 w-8 h-8 rounded-full flex items-center justify-center font-bold">✕</button>
        </div>
        <div class="cart-items flex-1 overflow-y-auto space-y-3 pr-1"></div>
        <div class="pt-4 border-t border-zinc-800 mt-auto">
            <div class="flex justify-between items-center mb-4 text-base font-black">
                <span class="text-zinc-400">Total Amount:</span>
                <span id="cartTotal" class="text-amber-400 text-xl">Rs. 0.00</span>
            </div>
            <a id="checkoutBtn" href="checkout.php?table=<?php echo urlencode($table_num); ?>" class="h-12 w-full rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                Proceed to Checkout →
            </a>
        </div>
    </div>

    <!-- Fixed Bottom Tab Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-[10px]">
            <span class="text-lg">🍽️</span>
            <span>Menu</span>
        </a>
        <button onclick="openCartPanel()" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px] relative">
            <span class="text-lg">🛒</span>
            <span>Cart</span>
            <span class="cart-badge absolute -top-1 right-1 bg-amber-500 text-zinc-950 font-black text-[9px] w-4 h-4 rounded-full flex items-center justify-center" style="display: none;">0</span>
        </button>
        <button onclick="callWaiter()" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">🔔</span>
            <span>Waiter</span>
        </button>
        <a href="order-success.php?table=<?php echo urlencode($table_num); ?>" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">📋</span>
            <span>Tracker</span>
        </a>
    </nav>

    <script src="js/modern.js"></script>
    <script>
        function filterByCategory(catId) {
            const url = new URL(window.location.href);
            if (catId > 0) url.searchParams.set('category', catId);
            else url.searchParams.delete('category');
            window.location.href = url.toString();
        }

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.menu-item').forEach(item => {
                const name = item.dataset.name || '';
                const desc = item.dataset.description || '';
                item.style.display = (name.includes(query) || desc.includes(query)) ? '' : 'none';
            });
        });

        function updateFloatingCartBar() {
            const floatBar = document.getElementById('floatingCartBar');
            const floatCount = document.getElementById('floatCartCount');
            const floatTotal = document.getElementById('floatCartTotal');
            if (!floatBar) return;
            const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
            if (totalCount > 0) {
                if (floatCount) floatCount.textContent = totalCount;
                if (floatTotal) floatTotal.textContent = formatPrice(getCartTotal());
                floatBar.style.display = 'flex';
            } else {
                floatBar.style.display = 'none';
            }
        }
        document.addEventListener('DOMContentLoaded', updateFloatingCartBar);
    </script>
</body>
</html>
