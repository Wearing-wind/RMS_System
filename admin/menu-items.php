<?php
// Admin Menu Items Management - Mobile First & Instant Availability Toggle
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database not connected.");
}

// Handle AJAX Instant Toggle Switch Request
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    header('Content-Type: application/json');
    $id = intval($_GET['id']);
    $new_status = sanitize($_GET['status']);
    
    if ($id > 0 && in_array($new_status, ['active', 'sold_out', 'inactive'])) {
        $stmt = $conn->prepare("UPDATE menu_items SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    }
    $conn->close();
    exit;
}

// Handle standard form submissions
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

// Get categories & items
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0c0907">
    <title>Menu Items Manager - QR Cafe</title>
    <link rel="manifest" href="../manifest.json">
    <link rel="stylesheet" href="../css/spatial.css">
</head>
<body>
    <!-- Mobile Header -->
    <header class="header">
        <div class="mobile-app-shell">
            <a href="index.php" class="logo">
                <span>🍔</span> Menu Items Manager
            </a>
            <button onclick="document.getElementById('addItemFormCard').scrollIntoView({behavior:'smooth'})" class="add-to-cart-btn" style="padding: 6px 12px; font-size: 0.78rem;">
                + Add Item
            </button>
        </div>
    </header>

    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">

        <!-- Admin Quick Nav Tabs -->
        <div class="category-nav-scroll" style="margin-bottom: 14px;">
            <a href="index.php" class="category-btn">📊 Dashboard</a>
            <a href="menu-items.php" class="category-btn active">🍔 Menu Items</a>
            <a href="orders.php" class="category-btn">📋 All Orders</a>
            <a href="tables.php" class="category-btn">📍 Tables</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 10px; border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 0.85rem;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <h3 style="font-size: 1rem; font-weight: 800; font-family: var(--font-serif); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
            <span>Instant Stock Toggle</span>
            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">Tap switch to toggle Sold Out / Active</span>
        </h3>

        <!-- Mobile Cards List View (Replaces Wide Desktop Table) -->
        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
            <?php foreach ($items as $item): ?>
                <?php 
                $is_active = ($item['status'] === 'active');
                $dietary = strtolower($item['dietary_type'] ?? 'veg');
                $dietary_tag = ($dietary === 'non-veg') ? '<span class="dietary-tag non-veg"></span>' : '<span class="dietary-tag veg"></span>';
                ?>
                <div class="menu-item" id="item-card-<?php echo $item['id']; ?>">
                    <div class="menu-item-image">
                        <?php if (!empty($item['image'])): ?>
                            <img src="../images/<?php echo htmlspecialchars($item['image']); ?>" alt="item" onerror="this.parentElement.innerHTML='🍽️'">
                        <?php else: ?>
                            🍽️
                        <?php endif; ?>
                    </div>
                    
                    <div class="menu-item-content">
                        <div class="menu-item-name">
                            <?php echo $dietary_tag; ?> <?php echo htmlspecialchars($item['name']); ?>
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted);">
                            Category: <?php echo htmlspecialchars($categories[$item['category_id']] ?? 'General'); ?>
                        </div>
                        <div class="menu-item-price" style="margin-top: 4px;">
                            Rs. <?php echo number_format($item['price'], 0); ?>
                        </div>
                    </div>

                    <!-- Instant Toggle Switch Cell -->
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                        <label class="toggle-switch" title="Toggle Item Availability">
                            <input type="checkbox" <?php echo $is_active ? 'checked' : ''; ?> onchange="toggleItemAvailability(<?php echo $item['id']; ?>, this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                        <span id="status-label-<?php echo $item['id']; ?>" style="font-size: 0.7rem; font-weight: 800; color: <?php echo $is_active ? 'var(--success)' : 'var(--danger)'; ?>;">
                            <?php echo $is_active ? 'Available' : 'Sold Out'; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Item Form Card -->
        <div class="spatial-card" id="addItemFormCard" style="padding: 16px;">
            <h3 style="font-size: 1.05rem; font-weight: 800; font-family: var(--font-serif); margin-bottom: 12px;">+ Add New Menu Item</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Item Name</label>
                    <input type="text" name="name" required style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
                </div>

                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Category</label>
                    <select name="category_id" required style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
                        <?php foreach ($categories as $cat_id => $cat_name): ?>
                            <option value="<?php echo $cat_id; ?>"><?php echo htmlspecialchars($cat_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 10px; display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Price (Rs.)</label>
                        <input type="number" step="0.01" name="price" required style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Dietary</label>
                        <select name="dietary_type" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
                            <option value="veg">Vegetarian</option>
                            <option value="non-veg">Non-Veg</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Description</label>
                    <textarea name="description" rows="2" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;"></textarea>
                </div>

                <button type="submit" class="checkout-btn" style="padding: 12px;">Save Menu Item</button>
            </form>
        </div>

    </main>

    <!-- PINNED MOBILE BOTTOM NAVIGATION BAR FOR ADMIN -->
    <nav class="mobile-nav-bar">
        <a href="index.php" class="mobile-nav-item">
            <span class="mobile-nav-icon">📊</span>
            <span>Summary</span>
        </a>
        <a href="orders.php" class="mobile-nav-item">
            <span class="mobile-nav-icon">📋</span>
            <span>Orders</span>
        </a>
        <a href="menu-items.php" class="mobile-nav-item active">
            <span class="mobile-nav-icon">🍔</span>
            <span>Items</span>
        </a>
        <a href="../kitchen-dashboard.php" class="mobile-nav-item">
            <span class="mobile-nav-icon">👨‍🍳</span>
            <span>KDS</span>
        </a>
    </nav>

    <script src="../js/modern.js"></script>
    <script>
        // Instant Availability Toggle Switch Handler
        function toggleItemAvailability(itemId, isChecked) {
            const status = isChecked ? 'active' : 'sold_out';
            const label = document.getElementById('status-label-' + itemId);
            
            fetch('menu-items.php?action=toggle_status&id=' + itemId + '&status=' + status)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (label) {
                            label.textContent = isChecked ? 'Available' : 'Sold Out';
                            label.style.color = isChecked ? 'var(--success)' : 'var(--danger)';
                        }
                        showToast(isChecked ? 'Item marked Available!' : 'Item marked Sold Out!', isChecked ? 'success' : 'warning');
                    } else {
                        showToast('Error updating item availability', 'error');
                    }
                })
                .catch(err => console.error(err));
        }
    </script>
</body>
</html>
