<?php
// Admin Categories Management - Mobile First
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database not connected.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Category added successfully';
        }
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0c0907">
    <title>Manage Categories - QR Cafe</title>
    <link rel="manifest" href="../manifest.json">
    <link rel="stylesheet" href="../css/spatial.css">
</head>
<body>
    <!-- Mobile Header -->
    <header class="header">
        <div class="mobile-app-shell">
            <a href="index.php" class="logo">
                <span>🏷️</span> Menu Categories
            </a>
            <a href="index.php" style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 700;">Dashboard →</a>
        </div>
    </header>

    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">

        <!-- Add Category Card -->
        <div class="spatial-card" style="padding: 16px; margin-bottom: 20px;">
            <h3 style="font-size: 1rem; font-weight: 800; font-family: var(--font-serif); margin-bottom: 10px;">+ Add Menu Category</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div style="margin-bottom: 10px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Category Name</label>
                    <input type="text" name="name" placeholder="e.g. Beverages, Pastries, Main Course" required style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Description (Optional)</label>
                    <input type="text" name="description" placeholder="Brief description..." style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
                </div>

                <button type="submit" class="checkout-btn" style="padding: 10px;">Save Category</button>
            </form>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 10px; border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 0.85rem;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Categories Mobile List -->
        <h3 style="font-size: 1rem; font-weight: 800; font-family: var(--font-serif); margin-bottom: 10px;">Existing Categories</h3>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
            <?php foreach ($categories as $c): ?>
                <div class="spatial-card" style="padding: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 800; font-size: 0.98rem; color: var(--primary);">🏷️ <?php echo htmlspecialchars($c['name']); ?></div>
                        <?php if (!empty($c['description'])): ?>
                            <div style="font-size: 0.76rem; color: var(--text-muted);"><?php echo htmlspecialchars($c['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    <a href="categories.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete category <?php echo htmlspecialchars($c['name']); ?>?')" style="color: var(--danger); font-size: 0.78rem; text-decoration: none; font-weight: 700;">Delete</a>
                </div>
            <?php endforeach; ?>
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
        <a href="menu-items.php" class="mobile-nav-item">
            <span class="mobile-nav-icon">🍔</span>
            <span>Items</span>
        </a>
        <a href="../kitchen-dashboard.php" class="mobile-nav-item">
            <span class="mobile-nav-icon">👨‍🍳</span>
            <span>KDS</span>
        </a>
    </nav>

    <script src="../js/modern.js"></script>
</body>
</html>
