<?php
// Admin Categories Management - Tailwind Mobile Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database connection failed.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = 'Category added successfully';
    }
    header('Location: categories.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM categories WHERE id = $id");
    $_SESSION['success'] = 'Category deleted successfully';
    header('Location: categories.php');
    exit;
}

$categories_res = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = [];
if ($categories_res) {
    while ($c = $categories_res->fetch_assoc()) {
        $categories[] = $c;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Manage Categories - QR Cafe</title>
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
                <span>🏷️</span>
                <span>Menu Categories</span>
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
            <a href="categories.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">🏷️ Categories</a>
            <a href="payment-settings.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">💳 Payment QR</a>
        </nav>

        <!-- Add Category Form Card -->
        <section class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-5 shadow-2xl space-y-3">
            <h3 class="text-sm font-black text-white flex items-center gap-2">➕ Add Menu Category</h3>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Category Name</label>
                    <input type="text" name="name" placeholder="e.g. Beverages, Pastries" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1">Description (Optional)</label>
                    <input type="text" name="description" placeholder="Brief description..." class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500">
                </div>
                <button type="submit" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                    Save Category
                </button>
            </form>
        </section>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Existing Categories Cards -->
        <section class="space-y-2 mb-20">
            <h3 class="text-xs font-extrabold text-zinc-400 uppercase tracking-wider">Existing Categories</h3>
            <div class="space-y-2">
                <?php foreach ($categories as $c): ?>
                    <div class="bg-zinc-900/90 border border-zinc-800/80 rounded-2xl p-3.5 flex justify-between items-center">
                        <div>
                            <div class="font-extrabold text-sm text-white">🏷️ <?php echo htmlspecialchars($c['name']); ?></div>
                            <?php if (!empty($c['description'])): ?>
                                <div class="text-xs text-zinc-400 mt-0.5"><?php echo htmlspecialchars($c['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <a href="categories.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete category?')" class="text-xs font-bold text-rose-400 p-1">Delete</a>
                    </div>
                <?php endforeach; ?>
            </div>
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
