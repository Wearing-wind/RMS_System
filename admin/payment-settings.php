<?php
// Admin Payment Settings - Tailwind Mobile Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database connection failed.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = sanitize($_POST['restaurant_name']);
    $payment_note = sanitize($_POST['payment_note']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $qr_code_image = '';
    if (isset($_FILES['qr_code_image']) && $_FILES['qr_code_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/';
        $file_name = 'qr_' . time() . '_' . basename($_FILES['qr_code_image']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['qr_code_image']['tmp_name'], $target_file)) {
            $qr_code_image = $file_name;
        }
    }

    $existing = $conn->query("SELECT * FROM payment_settings LIMIT 1");
    if ($existing && $existing->num_rows > 0) {
        $row = $existing->fetch_assoc();
        if (empty($qr_code_image)) {
            $qr_code_image = $row['qr_code_image'];
        }
        $stmt = $conn->prepare("UPDATE payment_settings SET restaurant_name = ?, payment_note = ?, qr_code_image = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssii", $restaurant_name, $payment_note, $qr_code_image, $is_active, $row['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO payment_settings (restaurant_name, payment_note, qr_code_image, is_active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $restaurant_name, $payment_note, $qr_code_image, $is_active);
    }
    
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = 'Payment settings updated successfully';
    header('Location: payment-settings.php');
    exit;
}

$payment_settings = null;
$res = $conn->query("SELECT * FROM payment_settings LIMIT 1");
if ($res && $res->num_rows > 0) {
    $payment_settings = $res->fetch_assoc();
}
$conn->close();
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
<body class="min-h-full pb-24 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-md mx-auto flex items-center justify-between gap-2">
            <a href="index.php" class="flex items-center gap-2 font-black text-lg text-white">
                <span>💳</span>
                <span>Payment QR Config</span>
            </a>
            <a href="index.php" class="text-xs font-bold text-amber-400">Dashboard →</a>
        </div>
    </header>

    <main class="max-w-md mx-auto px-4 pt-3 space-y-4">

        <!-- Navigation Carousel -->
        <nav class="flex gap-2 overflow-x-auto no-scrollbar py-1">
            <a href="index.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📊 Dashboard</a>
            <a href="menu-items.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🍔 Menu Items</a>
            <a href="orders.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📋 Live Orders</a>
            <a href="tables.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📍 Tables</a>
            <a href="categories.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🏷️ Categories</a>
            <a href="payment-settings.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">💳 Payment QR</a>
        </nav>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Settings Form Card -->
        <section class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 shadow-2xl space-y-4 mb-20">
            <h3 class="text-base font-black text-white flex items-center gap-2">💳 Digital Payment QR Settings</h3>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Restaurant Name</label>
                    <input type="text" name="restaurant_name" value="<?php echo htmlspecialchars($payment_settings['restaurant_name'] ?? 'QR Cafe'); ?>" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Payment Note / Instructions</label>
                    <textarea name="payment_note" rows="2" class="w-full bg-zinc-950 border border-zinc-800 rounded-2xl p-3.5 text-sm text-white outline-none focus:border-amber-500 resize-none"><?php echo htmlspecialchars($payment_settings['payment_note'] ?? 'Scan QR code to pay via digital wallet'); ?></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-2">Current Payment QR Image</label>
                    <?php if (!empty($payment_settings['qr_code_image'])): ?>
                        <div class="text-center mb-3">
                            <img src="../images/<?php echo htmlspecialchars($payment_settings['qr_code_image']); ?>" alt="Payment QR" class="max-w-[150px] mx-auto rounded-2xl border-2 border-amber-500 p-2 bg-white">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="qr_code_image" accept="image/*" class="text-xs text-zinc-400">
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2.5 bg-zinc-950 border border-zinc-800 rounded-2xl p-3.5 cursor-pointer text-xs font-bold text-zinc-200">
                        <input type="checkbox" name="is_active" value="1" <?php echo (!isset($payment_settings) || !empty($payment_settings['is_active'])) ? 'checked' : ''; ?> class="w-4 h-4 accent-amber-500">
                        <span>Enable Digital Payment QR Code</span>
                    </label>
                </div>

                <button type="submit" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                    Save Payment Settings
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
