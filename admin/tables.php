<?php
// Admin Tables & Seating Management - Responsive Adaptive Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database connection failed. Please check MySQL server.");
}

// Handle Form Submissions with Throwable Exception Catching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $table_number = sanitize($_POST['table_number'] ?? '');
        $table_number = trim($table_number);

        if (!empty($table_number)) {
            try {
                $tbl_safe = $conn->real_escape_string($table_number);
                $check = $conn->query("SELECT id FROM tables WHERE table_number = '$tbl_safe'");

                if ($check && $check->num_rows > 0) {
                    $_SESSION['error'] = "Table '$table_number' already exists!";
                } else {
                    $stmt = $conn->prepare("INSERT INTO tables (table_number) VALUES (?)");
                    if ($stmt) {
                        $stmt->bind_param("s", $table_number);
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "Table '$table_number' added successfully!";
                        } else {
                            $_SESSION['error'] = "Failed to add table '$table_number'.";
                        }
                        $stmt->close();
                    }
                }
            } catch (Throwable $e) {
                $_SESSION['error'] = "Table '$table_number' already exists or invalid format.";
            }
        } else {
            $_SESSION['error'] = "Please enter a valid table number.";
        }
    }
    header('Location: tables.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        try {
            $conn->query("DELETE FROM tables WHERE id = $id");
            $_SESSION['success'] = 'Table deleted successfully';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Failed to delete table.';
        }
    }
    header('Location: tables.php');
    exit;
}

// Fetch all tables
$tables_res = $conn->query("SELECT * FROM tables ORDER BY CAST(table_number AS UNSIGNED) ASC, table_number ASC");
$tables = [];
$max_num = 0;

if ($tables_res && $tables_res->num_rows > 0) {
    while ($t = $tables_res->fetch_assoc()) {
        $t_num = $conn->real_escape_string($t['table_number']);
        $o_res = $conn->query("SELECT * FROM orders WHERE table_number = '$t_num' AND status IN ('new', 'preparing', 'ready') ORDER BY id DESC LIMIT 1");
        $t['active_order'] = ($o_res && $o_res->num_rows > 0 && $o_row = $o_res->fetch_assoc()) ? $o_row : null;
        $tables[] = $t;
        
        if (is_numeric($t['table_number'])) {
            $max_num = max($max_num, intval($t['table_number']));
        }
    }
}
$conn->close();

$suggested_table = $max_num > 0 ? ($max_num + 1) : (count($tables) + 1);

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri_dir = dirname($_SERVER['REQUEST_URI'] ?? '');
$base_url = $scheme . $host . str_replace('/admin', '', $uri_dir);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Manage Tables - QR Cafe</title>
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
            <a href="orders.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">📋</span>
                <span>Live Orders Queue</span>
            </a>
            <a href="menu-items.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">🍔</span>
                <span>Menu Inventory</span>
            </a>
            <a href="tables.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs shadow-lg shadow-amber-500/20">
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
                    <h1 class="text-lg md:text-xl font-black text-white">Restaurant Seating & Tables</h1>
                    <p class="text-xs text-zinc-400 hidden sm:block">Manage dining tables, active status, and printable QR codes</p>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 md:px-8 pt-4 space-y-6">

            <!-- Mobile Navigation Carousel (Hidden on md: desktop) -->
            <nav class="md:hidden flex gap-2 overflow-x-auto no-scrollbar py-1">
                <a href="index.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📊 Dashboard</a>
                <a href="menu-items.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🍔 Menu Items</a>
                <a href="orders.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📋 Live Orders</a>
                <a href="tables.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">📍 Tables</a>
            </nav>

            <!-- Quick Add Table Card -->
            <section class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 shadow-xl space-y-3">
                <h3 class="text-xs font-extrabold text-zinc-300 flex items-center gap-1.5 uppercase tracking-wider">
                    <span>➕</span> Add New Table
                </h3>
                <form method="POST" action="tables.php" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="table_number" value="<?php echo $suggested_table; ?>" placeholder="Table # (e.g. <?php echo $suggested_table; ?>)" required class="flex-1 h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500 font-bold">
                    <button type="submit" class="h-12 px-6 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-sm active:scale-95 shadow-lg shadow-amber-500/10 shrink-0">
                        + Add Table
                    </button>
                </form>
            </section>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold flex items-center gap-2">
                    <span>✅</span> <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-bold flex items-center gap-2">
                    <span>⚠️</span> <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Adaptive Seating Grid (3 cols mobile, 4-6 cols desktop) -->
            <section class="space-y-3">
                <h3 class="text-xs font-extrabold text-zinc-400 uppercase tracking-wider">Seating Grid (Click Table for Printable QR Code & Host Actions)</h3>

                <?php if (empty($tables)): ?>
                    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 text-center text-zinc-500">
                        <div class="text-3xl mb-2">📍</div>
                        <h3 class="font-bold">No tables added yet</h3>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3.5">
                        <?php foreach ($tables as $t): ?>
                            <?php 
                            $menu_link = rtrim($base_url, '/') . '/menu.php?table=' . urlencode($t['table_number']); 
                            $has_active = !empty($t['active_order']);
                            $order_id = $has_active ? $t['active_order']['id'] : 0;
                            $order_status = $has_active ? strtoupper($t['active_order']['status']) : 'VACANT';
                            ?>
                            <div onclick="openTableHostSheet('<?php echo htmlspecialchars($t['table_number']); ?>', <?php echo $order_id; ?>, '<?php echo $order_status; ?>', '<?php echo $menu_link; ?>', <?php echo $t['id']; ?>)" class="bg-zinc-900/90 border <?php echo $has_active ? 'border-amber-500/50 bg-amber-500/5' : 'border-zinc-800'; ?> rounded-3xl p-4 text-center cursor-pointer active:scale-95 hover:border-amber-500/80 transition-all shadow-lg">
                                <div class="text-3xl mb-1.5"><?php echo $has_active ? '🍽️' : '🛋️'; ?></div>
                                <div class="font-black text-sm text-white">Table <?php echo htmlspecialchars($t['table_number']); ?></div>
                                <div class="text-[10px] font-black mt-1 <?php echo $has_active ? 'text-amber-400' : 'text-zinc-500'; ?>">
                                    <?php echo $has_active ? '🔥 #' . $order_id : '🟢 VACANT'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>

    <!-- Table Host View & QR Code Bottom Sheet / Modal -->
    <div id="tableHostSheet" class="fixed inset-0 z-50 flex items-end md:items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
        <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-md" onclick="closeTableHostSheet()"></div>
        <div class="relative z-10 w-full max-w-md bg-zinc-900 border border-zinc-800 rounded-t-3xl md:rounded-3xl p-6 shadow-2xl translate-y-full md:translate-y-0 transition-transform duration-300 space-y-4 max-h-[90vh] overflow-y-auto">
            <button onclick="closeTableHostSheet()" class="absolute top-4 right-4 bg-zinc-800 text-zinc-400 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">✕</button>
            
            <div class="text-center">
                <h3 id="sheetTableTitle" class="text-xl font-black text-white">Table 1</h3>
                <p id="sheetTableStatus" class="text-xs text-zinc-400">Status: Vacant</p>
            </div>
            
            <!-- Dynamic QR Code Card -->
            <div class="bg-zinc-950/80 border border-zinc-800 rounded-2xl p-4 text-center space-y-2">
                <div class="p-3 bg-white rounded-2xl inline-block shadow-lg border border-amber-500/50">
                    <img id="sheetQrImage" src="" alt="Table QR Code" class="w-44 h-44 mx-auto object-contain">
                </div>
                <p class="text-[11px] text-zinc-400 font-medium">Scan to open digital menu for <strong id="sheetTableNumLabel" class="text-amber-400">Table 1</strong></p>
            </div>

            <!-- Download & Print Action Buttons -->
            <div class="grid grid-cols-2 gap-2">
                <a id="sheetDownloadQrBtn" href="#" target="_blank" class="h-11 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-xs flex items-center justify-center gap-1 active:scale-95 shadow-lg shadow-amber-500/20">
                    📥 Download QR
                </a>
                <button onclick="printTableQr()" class="h-11 rounded-2xl bg-zinc-800 border border-zinc-700 text-white font-black text-xs flex items-center justify-center gap-1 active:scale-95">
                    🖨️ Print QR
                </button>
            </div>

            <!-- Management Links -->
            <div class="space-y-2 pt-2 border-t border-zinc-800">
                <a id="sheetOpenMenuBtn" href="#" target="_blank" class="h-11 w-full rounded-2xl bg-zinc-950 border border-zinc-800 text-amber-400 font-bold text-xs flex items-center justify-center active:scale-95">
                    🔗 Open Customer Menu URL
                </a>
                <a id="sheetViewOrderBtn" href="#" class="h-11 w-full rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 font-bold text-xs flex items-center justify-center active:scale-95" style="display: none;">
                    📋 View Active Order Details
                </a>
                <a id="sheetDeleteTableBtn" href="#" onclick="return confirm('Delete this table?')" class="block text-center text-xs font-extrabold text-rose-400 pt-1">
                    🗑️ Delete Table
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation Bar (Hidden on md: desktop) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="index.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
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
        <a href="tables.php" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-[10px]">
            <span class="text-lg">📍</span>
            <span>Tables</span>
        </a>
    </nav>

    <script src="../js/modern.js"></script>
    <script>
        let currentTableNumForPrint = '';
        let currentQrUrlForPrint = '';

        function openTableHostSheet(tableNum, orderId, orderStatus, menuUrl, tableDbId) {
            currentTableNumForPrint = tableNum;
            const encodedMenuUrl = encodeURIComponent(menuUrl);
            const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodedMenuUrl;
            currentQrUrlForPrint = qrApiUrl;

            document.getElementById('sheetTableTitle').textContent = 'Table ' + tableNum;
            document.getElementById('sheetTableNumLabel').textContent = 'Table ' + tableNum;
            document.getElementById('sheetTableStatus').textContent = 'Current Status: ' + orderStatus;
            
            document.getElementById('sheetQrImage').src = qrApiUrl;
            document.getElementById('sheetDownloadQrBtn').href = qrApiUrl;

            document.getElementById('sheetOpenMenuBtn').href = menuUrl;
            document.getElementById('sheetDeleteTableBtn').href = 'tables.php?delete=' + tableDbId;

            const orderBtn = document.getElementById('sheetViewOrderBtn');
            if (orderId > 0) {
                orderBtn.href = 'order-details.php?id=' + orderId;
                orderBtn.style.display = 'flex';
            } else {
                orderBtn.style.display = 'none';
            }

            const sheet = document.getElementById('tableHostSheet');
            sheet.classList.remove('opacity-0', 'pointer-events-none');
            sheet.children[1].classList.remove('translate-y-full', 'md:translate-y-0');
        }

        function closeTableHostSheet() {
            const sheet = document.getElementById('tableHostSheet');
            sheet.classList.add('opacity-0', 'pointer-events-none');
            sheet.children[1].classList.add('translate-y-full');
        }

        function printTableQr() {
            if (!currentQrUrlForPrint) return;
            const printWindow = window.open('', '_blank', 'width=500,height=600');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Print QR - Table ${currentTableNumForPrint}</title>
                    <style>
                        body { font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 40px; background: #fff; color: #000; }
                        .card { border: 3px solid #000; border-radius: 24px; padding: 30px; display: inline-block; max-width: 320px; }
                        h1 { margin: 0 0 5px 0; font-size: 28px; }
                        p { margin: 0 0 20px 0; font-size: 14px; color: #555; }
                        img { width: 220px; height: 220px; }
                        .footer { margin-top: 20px; font-weight: bold; font-size: 16px; color: #d97706; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <h1>☕ QR Cafe</h1>
                        <p>Scan to view digital menu & order</p>
                        <img src="${currentQrUrlForPrint}" alt="Table QR">
                        <div class="footer">📍 TABLE ${currentTableNumForPrint}</div>
                    </div>
                    <script>
                        window.onload = function() { window.print(); window.close(); }
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>
