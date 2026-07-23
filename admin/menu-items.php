<?php
// Admin Menu Items Management - Responsive Adaptive Architecture
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database connection failed.");
}

// Handle Add/Edit/Delete Item Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $is_edit = ($_POST['action'] === 'edit');
            $id = intval($_POST['id'] ?? 0);
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $status = isset($_POST['status']) ? 'active' : (isset($_POST['is_active']) && $_POST['is_active'] ? 'active' : 'sold_out');
            $dietary_type = sanitize($_POST['dietary_type'] ?? 'veg');
            
            // Image handling: File Upload or Existing Image Selector
            $image = sanitize($_POST['existing_image'] ?? '');
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../images/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image = $file_name;
                }
            } elseif (!empty($_POST['selected_image'])) {
                $image = sanitize($_POST['selected_image']);
            }

            if ($is_edit && $id > 0) {
                $stmt = $conn->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, image = ?, category_id = ?, status = ?, dietary_type = ? WHERE id = ?");
                $stmt->bind_param("ssdsissi", $name, $description, $price, $image, $category_id, $status, $dietary_type, $id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success'] = 'Item updated successfully';
            } else {
                $stmt = $conn->prepare("INSERT INTO menu_items (name, description, price, image, category_id, status, dietary_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdsiss", $name, $description, $price, $image, $category_id, $status, $dietary_type);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success'] = 'Item added successfully';
            }
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

// Fetch existing image files from ../images/
$existing_images = [];
if (is_dir('../images/')) {
    $files = glob('../images/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    foreach ($files as $f) {
        $existing_images[] = basename($f);
    }
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
            <a href="menu-items.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs shadow-lg shadow-amber-500/20">
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
                    <h1 class="text-lg md:text-xl font-black text-white">Menu Inventory & Image Manager</h1>
                    <p class="text-xs text-zinc-400 hidden sm:block">Upload dish photos, manage descriptions, prices, and stock status</p>
                </div>
                <button onclick="document.getElementById('addItemCard').scrollIntoView({behavior:'smooth'})" class="px-4 py-2 rounded-full bg-amber-500 text-zinc-950 font-black text-xs active:scale-95 shadow-lg shadow-amber-500/20">
                    + Add New Item
                </button>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 md:px-8 pt-4 space-y-6">

            <!-- Navigation Carousel (Mobile Only) -->
            <nav class="md:hidden flex gap-2 overflow-x-auto no-scrollbar py-1">
                <a href="index.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📊 Dashboard</a>
                <a href="menu-items.php" class="px-4 py-2.5 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20 whitespace-nowrap">🍔 Menu Items</a>
                <a href="orders.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📋 Live Orders</a>
                <a href="tables.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">📍 Tables</a>
                <a href="categories.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">🏷️ Categories</a>
                <a href="payment-settings.php" class="px-4 py-2.5 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-300 whitespace-nowrap">💳 Payment QR</a>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold flex items-center gap-2">
                    <span>✅</span> <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Desktop High-Density Data Table vs Mobile Card List -->
            <section class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-5 shadow-xl space-y-4">
                <div class="flex justify-between items-center border-b border-zinc-800 pb-3">
                    <h3 class="text-xs font-extrabold text-zinc-400 uppercase tracking-wider">Inventory Stock Control & Dish Photos</h3>
                    <span class="text-xs text-zinc-500 font-bold"><?php echo count($items); ?> Items Listed</span>
                </div>

                <!-- Desktop Table (Visible on md: & lg:) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-zinc-800 text-zinc-400 font-extrabold uppercase">
                                <th class="pb-3 px-3">Dish Photo & Name</th>
                                <th class="pb-3 px-3">Category</th>
                                <th class="pb-3 px-3">Price</th>
                                <th class="pb-3 px-3">Dietary</th>
                                <th class="pb-3 px-3">Stock Status</th>
                                <th class="pb-3 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/60">
                            <?php foreach ($items as $item): ?>
                                <?php $is_active = ($item['status'] === 'active'); ?>
                                <tr class="hover:bg-zinc-950/40">
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-3">
                                            <!-- RECTANGULAR THUMBNAIL -->
                                            <div class="w-16 h-10 rounded-xl bg-zinc-950 border border-zinc-800 overflow-hidden flex items-center justify-center text-lg shrink-0">
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="../images/<?php echo htmlspecialchars($item['image']); ?>" alt="img" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='🍽️'">
                                                <?php else: ?>
                                                    🍽️
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="font-extrabold text-white text-sm"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="text-[10px] text-zinc-400 line-clamp-1"><?php echo htmlspecialchars($item['description']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-zinc-400 font-medium"><?php echo htmlspecialchars($categories[$item['category_id']] ?? 'General'); ?></td>
                                    <td class="py-3 px-3 font-black text-amber-400">Rs. <?php echo number_format($item['price'], 0); ?></td>
                                    <td class="py-3 px-3 uppercase font-extrabold text-[10px] text-zinc-300"><?php echo htmlspecialchars($item['dietary_type'] ?? 'veg'); ?></td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-2">
                                            <span id="stock-lbl-dt-<?php echo $item['id']; ?>" class="text-xs font-black <?php echo $is_active ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                                <?php echo $is_active ? 'In Stock' : 'Out of Stock'; ?>
                                            </span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" <?php echo $is_active ? 'checked' : ''; ?> onchange="toggleItemStock(<?php echo $item['id']; ?>, this.checked)" class="sr-only peer">
                                                <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-right space-x-1.5">
                                        <button onclick="openEditItemModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 font-bold text-xs hover:bg-amber-500 hover:text-zinc-950 transition-all">
                                            Edit ✏️
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Delete this item?')" class="inline-block">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 font-bold text-xs hover:bg-rose-500 hover:text-white transition-all">Delete 🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card List (Visible on small screens) -->
                <div class="md:hidden space-y-3">
                    <?php foreach ($items as $item): ?>
                        <?php $is_active = ($item['status'] === 'active'); ?>
                        <div class="bg-zinc-950/70 border border-zinc-800/60 rounded-2xl p-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-16 h-10 rounded-xl bg-zinc-950 border border-zinc-800 overflow-hidden flex items-center justify-center text-xl shrink-0">
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

                            <div class="flex items-center gap-2 shrink-0">
                                <button onclick="openEditItemModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-xs text-amber-400 font-bold p-1 bg-zinc-900 border border-zinc-800 rounded-lg px-2">✏️</button>
                                <form method="POST" onsubmit="return confirm('Delete this item?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="text-xs text-rose-400 font-bold p-1">🗑️</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </section>

            <!-- Add Item Form Card with Live Image File Selector & Preview -->
            <section id="addItemCard" class="bg-zinc-900/90 border border-zinc-800/80 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="text-base font-black text-white flex items-center gap-2">
                    <span>➕</span> Add New Menu Item
                </h3>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1.5">Item Name</label>
                            <input type="text" name="name" required placeholder="e.g. Artisanal Cappuccino" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1.5">Category</label>
                            <select name="category_id" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                                <?php foreach ($categories as $cat_id => $cat_name): ?>
                                    <option value="<?php echo $cat_id; ?>"><?php echo htmlspecialchars($cat_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1.5">Price (Rs.)</label>
                            <input type="number" step="0.01" name="price" required placeholder="250" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1.5">Dietary</label>
                            <select name="dietary_type" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                                <option value="veg">Veg</option>
                                <option value="non-veg">Non-Veg</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-300 mb-1.5">Select Existing Image Asset</label>
                            <select name="selected_image" onchange="previewAddAsset(this.value)" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-xs text-white outline-none focus:border-amber-500">
                                <option value="">-- Choose From /images/ Folder --</option>
                                <?php foreach ($existing_images as $img_file): ?>
                                    <option value="<?php echo htmlspecialchars($img_file); ?>"><?php echo htmlspecialchars($img_file); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center bg-zinc-950/60 border border-zinc-800/80 p-4 rounded-2xl">
                        <div>
                            <label class="block text-xs font-bold text-amber-400 mb-1.5">📷 Upload Custom Dish Photo</label>
                            <input type="file" name="image" accept="image/*" onchange="previewUploadFile(this)" class="w-full text-xs text-zinc-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-zinc-800 file:text-white hover:file:bg-zinc-700">
                            <p class="text-[10px] text-zinc-500 mt-1">Accepts JPG, PNG, WEBP. Uploads directly into /images/ folder.</p>
                        </div>
                        <div class="text-center flex flex-col items-center justify-center">
                            <span class="text-[10px] font-extrabold text-zinc-400 mb-1 uppercase tracking-wider">Photo Preview</span>
                            <div class="w-32 h-18 aspect-[16/9] rounded-xl bg-zinc-900 border border-zinc-800 overflow-hidden flex items-center justify-center text-xl">
                                <img id="addPhotoPreview" src="" alt="" class="w-full h-full object-cover hidden">
                                <span id="addPhotoPlaceholder" class="text-zinc-600 text-xs">No Image</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Description</label>
                        <textarea name="description" rows="2" placeholder="Brief description..." class="w-full bg-zinc-950 border border-zinc-800 rounded-2xl p-3.5 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500 resize-none"></textarea>
                    </div>

                    <button type="submit" class="h-12 w-full md:w-auto md:px-8 rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                        Save Menu Item
                    </button>
                </form>
            </section>

        </main>
    </div>

    <!-- Edit Item Modal -->
    <div id="editItemModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 p-4">
        <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-md" onclick="closeEditItemModal()"></div>
        <div class="relative z-10 w-full max-w-lg bg-zinc-900 border border-zinc-800 rounded-3xl p-6 shadow-2xl scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto">
            <button onclick="closeEditItemModal()" class="absolute top-4 right-4 bg-zinc-800 text-zinc-400 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">✕</button>
            <h3 class="text-lg font-black text-white mb-4 flex items-center gap-2">
                <span>✏️</span> Edit Menu Item
            </h3>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id" value="0">
                <input type="hidden" id="edit_existing_image" name="existing_image" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Item Name</label>
                        <input type="text" id="edit_name" name="name" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Category</label>
                        <select id="edit_category_id" name="category_id" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                            <?php foreach ($categories as $cat_id => $cat_name): ?>
                                <option value="<?php echo $cat_id; ?>"><?php echo htmlspecialchars($cat_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Price (Rs.)</label>
                        <input type="number" step="0.01" id="edit_price" name="price" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-zinc-300 mb-1.5">Dietary</label>
                        <select id="edit_dietary_type" name="dietary_type" class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white outline-none focus:border-amber-500">
                            <option value="veg">Veg</option>
                            <option value="non-veg">Non-Veg</option>
                        </select>
                    </div>
                </div>

                <!-- Image Selector & Upload Section -->
                <div class="space-y-3 bg-zinc-950/60 border border-zinc-800/80 p-4 rounded-2xl">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-amber-400">📷 Update Dish Photo</label>
                        <span class="text-[10px] text-zinc-500">Upload or select existing file</span>
                    </div>

                    <div>
                        <label class="block text-[11px] text-zinc-400 mb-1">Select from /images/ folder</label>
                        <select id="edit_selected_image" name="selected_image" onchange="previewEditAsset(this.value)" class="w-full h-10 bg-zinc-900 border border-zinc-800 rounded-xl px-3 text-xs text-white outline-none focus:border-amber-500">
                            <option value="">-- Keep Current Image --</option>
                            <?php foreach ($existing_images as $img_file): ?>
                                <option value="<?php echo htmlspecialchars($img_file); ?>"><?php echo htmlspecialchars($img_file); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] text-zinc-400 mb-1">OR Upload New Photo</label>
                        <input type="file" name="image" accept="image/*" onchange="previewEditUploadFile(this)" class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-zinc-800 file:text-white hover:file:bg-zinc-700">
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <div class="w-24 h-14 rounded-xl bg-zinc-900 border border-zinc-800 overflow-hidden flex items-center justify-center text-xl shrink-0">
                            <img id="editPhotoPreview" src="" alt="" class="w-full h-full object-cover">
                        </div>
                        <div class="text-xs text-zinc-400">Current Photo File:<br><span id="editPhotoFilename" class="font-bold text-amber-400 text-xs">None</span></div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-300 mb-1.5">Description</label>
                    <textarea id="edit_description" name="description" rows="2" class="w-full bg-zinc-950 border border-zinc-800 rounded-2xl p-3 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500 resize-none"></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditItemModal()" class="w-1/3 h-12 rounded-2xl bg-zinc-800 text-zinc-300 font-bold text-xs">Cancel</button>
                    <button type="submit" class="w-2/3 h-12 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs active:scale-95 shadow-lg shadow-amber-500/20">Save Changes</button>
                </div>
            </form>
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
        function previewAddAsset(val) {
            const img = document.getElementById('addPhotoPreview');
            const placeholder = document.getElementById('addPhotoPlaceholder');
            if (val) {
                img.src = '../images/' + val;
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                placeholder.classList.remove('hidden');
            }
        }

        function previewUploadFile(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('addPhotoPreview');
                    const placeholder = document.getElementById('addPhotoPlaceholder');
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function openEditItemModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_price').value = item.price;
            document.getElementById('edit_category_id').value = item.category_id;
            document.getElementById('edit_dietary_type').value = item.dietary_type || 'veg';
            document.getElementById('edit_description').value = item.description || '';
            document.getElementById('edit_existing_image').value = item.image || '';

            const preview = document.getElementById('editPhotoPreview');
            const filenameEl = document.getElementById('editPhotoFilename');
            if (item.image) {
                preview.src = '../images/' + item.image;
                filenameEl.textContent = item.image;
            } else {
                preview.src = '';
                filenameEl.textContent = 'None';
            }

            const modal = document.getElementById('editItemModal');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.children[1].classList.remove('scale-95');
        }

        function closeEditItemModal() {
            const modal = document.getElementById('editItemModal');
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.children[1].classList.add('scale-95');
        }

        function previewEditAsset(val) {
            if (val) {
                document.getElementById('editPhotoPreview').src = '../images/' + val;
                document.getElementById('editPhotoFilename').textContent = val;
            }
        }

        function previewEditUploadFile(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editPhotoPreview').src = e.target.result;
                    document.getElementById('editPhotoFilename').textContent = input.files[0].name + ' (New Upload)';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function toggleItemStock(itemId, isChecked) {
            const status = isChecked ? 'active' : 'sold_out';
            const labelMobile = document.getElementById('stock-lbl-' + itemId);
            const labelDesktop = document.getElementById('stock-lbl-dt-' + itemId);
            fetch('../api/toggle-stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: itemId, status: status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const text = isChecked ? 'In Stock' : 'Out of Stock';
                    const cls = 'text-xs font-black ' + (isChecked ? 'text-emerald-400' : 'text-rose-400');
                    if (labelMobile) { labelMobile.textContent = text; labelMobile.className = cls; }
                    if (labelDesktop) { labelDesktop.textContent = text; labelDesktop.className = cls; }
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
