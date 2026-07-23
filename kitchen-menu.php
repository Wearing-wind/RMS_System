<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Today's Menu Catalog - Kitchen View</title>
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
    ?>

    <!-- Sticky Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between gap-2">
            <div class="flex items-center gap-2 font-black text-lg text-white">
                <span>📋</span>
                <span>Today's Menu Catalog (Read-Only)</span>
            </div>
            <a href="kitchen-dashboard.php" class="text-xs font-bold text-amber-400">KDS Stream →</a>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-3">
        <!-- Search Bar -->
        <div class="relative mb-3">
            <input type="text" id="searchInput" placeholder="Search menu catalog & prices..." class="w-full bg-zinc-900 border border-zinc-800 rounded-2xl py-3 pl-11 pr-4 text-sm text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition-all">
            <span class="absolute left-4 top-3.5 text-zinc-500 text-sm">🔍</span>
        </div>

        <!-- Menu List (Synced with DB) -->
        <div class="space-y-3 mb-20" id="kitchenMenuList">
            <?php if ($db_error): ?>
                <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6 text-center text-zinc-400">
                    Database connection error
                </div>
            <?php else: ?>
                <?php
                $res = $conn->query("SELECT mi.*, c.name as category_name FROM menu_items mi LEFT JOIN categories c ON mi.category_id = c.id ORDER BY mi.category_id, mi.name");
                if ($res && $res->num_rows > 0) {
                    while ($item = $res->fetch_assoc()) {
                        $is_out_of_stock = ($item['status'] === 'sold_out' || $item['status'] === 'inactive');
                        $dietary = strtolower($item['dietary_type'] ?? 'veg');
                        $dietary_color = ($dietary === 'non-veg') ? 'border-red-500 bg-red-500' : 'border-emerald-500 bg-emerald-500';

                        echo '<div class="menu-item-row bg-zinc-900/90 border border-zinc-800/80 rounded-2xl p-3 flex items-center justify-between gap-3" data-name="' . strtolower(htmlspecialchars($item['name'])) . '">';
                        echo '<div class="flex items-center gap-3 min-w-0">';
                        echo '<div class="w-12 h-12 rounded-xl bg-zinc-950 border border-zinc-800 overflow-hidden flex items-center justify-center text-2xl shrink-0">';
                        if (!empty($item['image'])) {
                            echo '<img src="images/' . htmlspecialchars($item['image']) . '" alt="img" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML=\'🍽️\'">';
                        } else {
                            echo '🍽️';
                        }
                        echo '</div>';

                        echo '<div class="min-w-0">';
                        echo '<div class="flex items-center gap-1.5">';
                        echo '<span class="w-3 h-3 rounded-sm border ' . $dietary_color . ' flex items-center justify-center"><span class="w-1 h-1 rounded-full bg-white"></span></span>';
                        echo '<h4 class="font-extrabold text-sm text-white truncate">' . htmlspecialchars($item['name']) . '</h4>';
                        echo '</div>';
                        echo '<div class="text-[11px] text-zinc-400">' . htmlspecialchars($item['category_name'] ?? 'General') . '</div>';
                        echo '</div>';
                        echo '</div>';

                        echo '<div class="text-right shrink-0">';
                        echo '<div class="font-black text-sm text-amber-400">Rs. ' . number_format($item['price'], 0) . '</div>';
                        if ($is_out_of_stock) {
                            echo '<span class="px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-400 font-extrabold text-[10px]">Out of stock</span>';
                        } else {
                            echo '<span class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-extrabold text-[10px]">In Stock</span>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                }
                $conn->close();
                ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Kitchen Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="kitchen-dashboard.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-xs">
            <span class="text-lg">👨‍🍳</span>
            <span>KDS Stream</span>
        </a>
        <a href="kitchen-menu.php" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-xs">
            <span class="text-lg">📋</span>
            <span>Today's Menu</span>
        </a>
    </nav>

    <script>
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const q = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.menu-item-row').forEach(row => {
                const name = row.dataset.name || '';
                row.style.display = name.includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
