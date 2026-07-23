<?php
// Admin Payment QR & Settings - Responsive Adaptive Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();
if (!$conn) die("Database error");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = sanitize($_POST['restaurant_name']);
    $payment_note = sanitize($_POST['payment_note']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $qr_image = '';
    $res = $conn->query("SELECT qr_code_image FROM payment_settings LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $qr_image = $row['qr_code_image'];
    }

    if (isset($_FILES['qr_code_image']) && $_FILES['qr_code_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/';
        $file_name = time() . '_' . basename($_FILES['qr_code_image']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['qr_code_image']['tmp_name'], $target_file)) {
            $qr_image = $file_name;
        }
    }

    $check = $conn->query("SELECT id FROM payment_settings LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE payment_settings SET restaurant_name = ?, payment_note = ?, qr_code_image = ?, is_active = ?");
        $stmt->bind_param("sssi", $restaurant_name, $payment_note, $qr_image, $is_active);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO payment_settings (restaurant_name, payment_note, qr_code_image, is_active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $restaurant_name, $payment_note, $qr_image, $is_active);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['success'] = "Payment QR Code settings updated!";
    header('Location: payment-settings.php');
    exit;
}

$setting = $conn->query("SELECT * FROM payment_settings LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Payment QR Settings - QR Cafe</title>
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
            <a href="tables.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">📍</span>
                <span>Seating & Tables</span>
            </a>
            <a href="categories.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-zinc-400 hover:text-white hover:bg-zinc-900 font-bold text-xs transition-all">
                <span class="text-lg">🏷️</span>
                <span>Categories</span>
            </a>
            <a href="payment-settings.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs shadow-lg shadow-amber-500/20">
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
                    <h1 class="text-lg md:text-xl font-black text-white">Payment QR Settings</h1>
                    <p class="text-xs text-zinc-400 hidden sm:block">Upload digital payment QR code (eSewa / Khalti / Fonepay) for instant checkout scanning</p>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 md:px-8 pt-4 space-y-6">

            <!-- Mobile Navigation Carousel -->
            <nav class="md:hidden flex gap-2 overflow-x-auto no-scrollbar py-1">
                <a href="index.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📊 Dashboard</a>
                <a href="menu-items.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🍔 Menu Items</a>
                <a href="payment-settings.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">💳 Payment QR</a>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold flex items-center gap-2">
                    <span>✅</span> <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <section class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-6 shadow-xl space-y-6">
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <span>💳</span> Configure Checkout Payment QR
                </h3>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1.5">Restaurant Merchant Name</label>
                            <input type="text" name="restaurant_name" value="<?php echo htmlspecialchars($setting['restaurant_name'] ?? 'QR Cafe & Dining'); ?>" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1.5">Payment Instructions Note</label>
                            <input type="text" name="payment_note" value="<?php echo htmlspecialchars($setting['payment_note'] ?? 'Scan QR code using eSewa/Khalti/Banking App'); ?>" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Upload Payment QR Image</label>
                        <input type="file" name="qr_code_image" accept="image/*" class="w-full text-xs text-zinc-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-zinc-800 file:text-white hover:file:bg-zinc-700">
                    </div>

                    <?php if (!empty($setting['qr_code_image'])): ?>
                        <div class="p-4 bg-zinc-950 border border-zinc-800 rounded-2xl text-center inline-block">
                            <div class="text-xs text-zinc-400 mb-2 font-bold">Current Merchant Payment QR:</div>
                            <img src="../images/<?php echo htmlspecialchars($setting['qr_code_image']); ?>" alt="Payment QR" class="w-48 h-48 object-contain mx-auto rounded-xl border border-amber-500/40 p-2 bg-white">
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="h-12 w-full md:w-auto md:px-8 rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                        Save Payment QR Settings
                    </button>
                </form>
            </section>

        </main>
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
        <a href="tables.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-[10px]">
            <span class="text-lg">📍</span>
            <span>Tables</span>
        </a>
    </nav>

    <script src="../js/modern.js"></script>
</body>
</html>
