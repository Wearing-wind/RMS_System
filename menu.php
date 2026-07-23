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
<body class="min-h-full pb-24 lg:pb-8 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <?php
    require_once 'config.php';
    $conn = getDBConnection();
    $db_error = ($conn === null);
    $table_num = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '1';
    
    // Check if table has active placed order
    $active_order_id = 0;
    if ($conn) {
        $tbl_safe = $conn->real_escape_string($table_num);
        $res = $conn->query("SELECT id FROM orders WHERE table_number = '$tbl_safe' AND status IN ('new', 'preparing', 'ready') ORDER BY id DESC LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $active_order_id = $row['id'];
        }
    }
    ?>

    <!-- Top Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
            <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="flex items-center gap-2 text-lg font-black tracking-tight text-white">
                <span class="text-xl">☕</span>
                <span>QR Cafe & Dining</span>
            </a>
            <div class="flex items-center gap-3">
                <?php if ($active_order_id > 0): ?>
                    <a href="order-success.php?table=<?php echo urlencode($table_num); ?>&order_id=<?php echo $active_order_id; ?>" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-xs">
                        📋 Active Order #<?php echo $active_order_id; ?> →
                    </a>
                <?php endif; ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-xs">
                    <span>📍</span> Table <?php echo $table_num; ?>
                </span>
            </div>
        </div>
    </header>

    <!-- Main Responsive Layout Wrapper (2-Column on Desktop) -->
    <div class="max-w-7xl mx-auto px-4 pt-4 lg:flex lg:gap-8">
        
        <!-- Left Side: Search, Categories, Food Grid -->
        <main class="flex-1 min-w-0">
            
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

            <!-- Adaptive Food Grid View (2 cols mobile, 3 cols tablet, 3-4 cols desktop) -->
            <section class="mb-24 lg:mb-8">
                <?php if ($db_error): ?>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6 text-center">
                        <div class="text-3xl mb-2">⚠️</div>
                        <h3 class="font-bold text-zinc-200">Database Connection Failed</h3>
                    </div>
                <?php else: ?>
                    <div id="menuGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
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
                                $out_of_stock = ($item['status'] === 'sold_out' || $item['status'] === 'inactive');
                                $dietary = strtolower($item['dietary_type'] ?? 'veg');
                                $dietary_color = ($dietary === 'non-veg') ? 'border-red-500 bg-red-500' : 'border-emerald-500 bg-emerald-500';
                                $prepTime = isset($item['preparation_time']) ? intval($item['preparation_time']) : 15;
                                
                                echo '<div id="item-card-' . $item['id'] . '" class="menu-item bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-3 flex flex-col justify-between transition-all active:scale-[0.98]" data-id="' . $item['id'] . '" data-price="' . $item['price'] . '" data-preptime="' . $prepTime . '" data-name="' . strtolower(htmlspecialchars($item['name'])) . '" data-rawname="' . addslashes(htmlspecialchars($item['name'])) . '" data-rawdesc="' . addslashes(htmlspecialchars($item['description'])) . '" data-description="' . strtolower(htmlspecialchars($item['description'])) . '">';
                                
                                // RECTANGULAR 16:9 Aspect Ratio Image Box
                                echo '<div class="relative aspect-[16/9] w-full rounded-2xl bg-zinc-950 overflow-hidden mb-2.5 flex items-center justify-center text-4xl border border-zinc-800/50">';
                                if (!empty($item['image']) && file_exists(__DIR__ . '/images/' . $item['image'])) {
                                    echo '<img src="images/' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.innerHTML=\'🍽️\'">';
                                } else {
                                    echo '🍽️';
                                }
                                if (!empty($item['is_popular'])) {
                                    echo '<span class="absolute top-2 left-2 bg-amber-500 text-zinc-950 font-black text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider shadow-md">⭐ Top</span>';
                                }
                                echo '</div>';

                                echo '<div class="flex-1 flex flex-col mb-2">';
                                echo '<div class="flex items-center gap-1.5 mb-1">';
                                echo '<span class="w-3.5 h-3.5 rounded-sm border-2 ' . $dietary_color . ' flex items-center justify-center shrink-0"><span class="w-1.5 h-1.5 rounded-full bg-white"></span></span>';
                                echo '<h3 class="font-extrabold text-sm text-zinc-100 truncate">' . htmlspecialchars($item['name']) . '</h3>';
                                echo '</div>';
                                echo '<p class="text-xs text-zinc-400 line-clamp-2 mb-2">' . htmlspecialchars($item['description']) . '</p>';
                                echo '</div>';

                                echo '<div class="mt-auto flex flex-col gap-2 action-container">';
                                echo '<span class="text-base font-black text-amber-400">Rs. ' . number_format($item['price'], 0) . '</span>';
                                if (!$out_of_stock) {
                                    echo '<button onclick="openCustomModal(' . $item['id'] . ', \'' . addslashes(htmlspecialchars($item['name'])) . '\', ' . $item['price'] . ', \'' . addslashes(htmlspecialchars($item['description'])) . '\', ' . $prepTime . ')" class="btn-add h-11 w-full rounded-2xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-zinc-950 font-black text-xs transition-all shadow-lg shadow-amber-500/10 flex items-center justify-center gap-1">+ Add</button>';
                                } else {
                                    echo '<button disabled class="btn-soldout h-11 w-full rounded-2xl bg-zinc-800 text-rose-400/80 font-bold text-xs">Out of stock</button>';
                                }
                                echo '</div>';

                                echo '</div>';
                            }
                        } else {
                            echo '<div class="col-span-full bg-zinc-900 border border-zinc-800 rounded-3xl p-8 text-center text-zinc-400">';
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

        <!-- Right Side: Persistent Desktop Cart Sidebar (Visible on lg: screens) -->
        <aside class="hidden lg:block lg:w-80 xl:w-96 shrink-0">
            <div class="sticky top-20 bg-zinc-900 border border-zinc-800 rounded-3xl p-5 shadow-2xl flex flex-col max-h-[calc(100vh-6rem)]">
                <div class="flex justify-between items-center pb-3 border-b border-zinc-800 mb-3">
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>🛒</span> Live Order Cart
                    </h3>
                    <span class="cart-badge bg-amber-500 text-zinc-950 font-black text-xs px-2.5 py-0.5 rounded-full" style="display: none;">0</span>
                </div>

                <?php if ($active_order_id > 0): ?>
                    <div class="mb-3">
                        <a href="order-success.php?table=<?php echo urlencode($table_num); ?>&order_id=<?php echo $active_order_id; ?>" class="w-full p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-xs flex items-center justify-between active:scale-95 transition-all">
                            <span>📋 Track Order #<?php echo $active_order_id; ?></span>
                            <span>View →</span>
                        </a>
                    </div>
                <?php endif; ?>

                <div id="desktopCartItems" class="cart-items flex-1 min-h-0 overflow-y-auto space-y-3 pr-1 py-1">
                    <!-- Rendered by JS -->
                </div>

                <div class="pt-4 border-t border-zinc-800 mt-auto space-y-3">
                    <div class="flex justify-between items-center text-base font-black">
                        <span class="text-zinc-400">Total:</span>
                        <span id="desktopCartTotal" class="text-amber-400 text-xl">Rs. 0.00</span>
                    </div>
                    <a id="desktopCheckoutBtn" href="checkout.php?table=<?php echo urlencode($table_num); ?>" class="h-12 w-full rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                        Proceed to Checkout →
                    </a>
                </div>
            </div>
        </aside>

    </div>

    <!-- FLOATING WAITER CALL BUTTON -->
    <button onclick="callWaiter()" class="fixed bottom-20 lg:bottom-6 right-4 lg:right-6 z-40 w-14 h-14 lg:w-auto lg:h-12 lg:px-5 rounded-full bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-xl lg:text-xs flex items-center justify-center gap-2 shadow-2xl active:scale-95 transition-all cursor-pointer border-2 border-amber-400/50 hover:shadow-amber-500/20" title="Call Waiter to Table">
        <span>🔔</span>
        <span class="hidden lg:inline font-black uppercase tracking-wider">Call Waiter</span>
    </button>

    <!-- Slide-Up Bottom Sheet Customization Modal -->
    <div id="customModal" class="fixed inset-0 z-50 flex items-end lg:items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
        <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-md" onclick="closeCustomModal()"></div>
        <div class="relative z-10 w-full max-w-md bg-zinc-900 border border-zinc-800 rounded-t-3xl lg:rounded-3xl p-6 shadow-2xl translate-y-full lg:translate-y-0 transition-transform duration-300 max-h-[85vh] overflow-y-auto">
            <button onclick="closeCustomModal()" class="absolute top-4 right-4 bg-zinc-800 text-zinc-400 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">✕</button>
            <div class="text-4xl text-center mb-2" id="customModalImage">🍽️</div>
            <h3 class="text-lg font-black text-center text-white" id="customModalTitle">Item Name</h3>
            <p class="text-xs text-zinc-400 text-center mb-3" id="customModalDesc">Description</p>
            <div class="text-center font-black text-amber-400 text-lg mb-4" id="customModalPrice">Rs. 0</div>

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

    <!-- Slide-out Cart Drawer Panel (Mobile Only) -->
    <div class="lg:hidden cart-overlay fixed inset-0 bg-zinc-950/85 backdrop-blur-md z-50 opacity-0 pointer-events-none transition-opacity duration-300"></div>
    <div class="lg:hidden cart-panel fixed inset-y-0 right-0 z-50 w-full max-w-md bg-zinc-900 border-l border-zinc-800 p-6 flex flex-col translate-x-full transition-transform duration-300 shadow-2xl">
        <div class="flex justify-between items-center pb-4 border-b border-zinc-800 mb-4 shrink-0">
            <h3 class="text-lg font-black text-white">🛒 Your Cart</h3>
            <button class="cart-close bg-zinc-800 text-zinc-400 w-8 h-8 rounded-full flex items-center justify-center font-bold">✕</button>
        </div>

        <?php if ($active_order_id > 0): ?>
            <div class="mb-4 shrink-0">
                <a href="order-success.php?table=<?php echo urlencode($table_num); ?>&order_id=<?php echo $active_order_id; ?>" class="w-full p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-xs flex items-center justify-between active:scale-95 transition-all">
                    <span>📋 Track Active Order #<?php echo $active_order_id; ?></span>
                    <span>View Status →</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Mobile Cart Items List -->
        <div id="mobileCartItems" class="cart-items flex-1 min-h-[180px] max-h-[60vh] overflow-y-auto space-y-3 pr-1 py-1"></div>
        
        <div class="pt-4 border-t border-zinc-800 mt-auto shrink-0 space-y-3">
            <div class="flex justify-between items-center mb-4 text-base font-black">
                <span class="text-zinc-400">Total Amount:</span>
                <span id="cartTotal" class="text-amber-400 text-xl">Rs. 0.00</span>
            </div>
            <a id="checkoutBtn" href="checkout.php?table=<?php echo urlencode($table_num); ?>" class="h-12 w-full rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                Proceed to Checkout →
            </a>
        </div>
    </div>

    <!-- Fixed Customer Bottom Navigation Bar (Hidden on lg: desktop) -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="menu.php?table=<?php echo urlencode($table_num); ?>" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-xs">
            <span class="text-lg">🍽️</span>
            <span>Menu</span>
        </a>
        <button onclick="openCartPanel()" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-xs relative">
            <span class="text-lg">🛒</span>
            <span>Cart</span>
            <span class="cart-badge absolute -top-1 right-2 bg-amber-500 text-zinc-950 font-black text-[9px] w-4 h-4 rounded-full flex items-center justify-center" style="display: none;">0</span>
        </button>
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

        // Live Stock Sync Polling
        function pollLiveStockStatus() {
            fetch('api/menu-status.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.items) {
                        data.items.forEach(item => {
                            const card = document.getElementById('item-card-' + item.id);
                            if (card) {
                                const actionBox = card.querySelector('.action-container');
                                if (actionBox) {
                                    const rawName = card.dataset.rawname;
                                    const rawDesc = card.dataset.rawdesc;
                                    const price = card.dataset.price;
                                    const prepTime = card.dataset.preptime;
                                    const formattedPrice = formatPrice(price);

                                    if (item.status === 'sold_out' || item.status === 'inactive') {
                                        actionBox.innerHTML = `
                                            <span class="text-base font-black text-amber-400">${formattedPrice}</span>
                                            <button disabled class="btn-soldout h-11 w-full rounded-2xl bg-zinc-800 text-rose-400/80 font-bold text-xs">Out of stock</button>
                                        `;
                                    } else if (item.status === 'active') {
                                        actionBox.innerHTML = `
                                            <span class="text-base font-black text-amber-400">${formattedPrice}</span>
                                            <button onclick="openCustomModal(${item.id}, '${rawName}', ${price}, '${rawDesc}', ${prepTime})" class="btn-add h-11 w-full rounded-2xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-zinc-950 font-black text-xs transition-all shadow-lg shadow-amber-500/10 flex items-center justify-center gap-1">+ Add</button>
                                        `;
                                    }
                                }
                            }
                        });
                    }
                })
                .catch(err => console.error(err));
        }

        document.addEventListener('DOMContentLoaded', () => {
            setInterval(pollLiveStockStatus, 4000);
        });
    </script>
</body>
</html>
