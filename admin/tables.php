<?php
// Admin Tables & QR Code Management - Mobile First with Host Bottom Sheet
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database not connected.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $table_number = sanitize($_POST['table_number']);
        $check = $conn->query("SELECT id FROM tables WHERE table_number = '$table_number'");
        if ($check->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO tables (table_number) VALUES (?)");
            $stmt->bind_param("s", $table_number);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Table added successfully';
        } else {
            $_SESSION['error'] = 'Table number already exists';
        }
    }
    header('Location: tables.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM tables WHERE id = $id");
    $_SESSION['success'] = 'Table deleted successfully';
    header('Location: tables.php');
    exit;
}

$tables_res = $conn->query("SELECT * FROM tables ORDER BY table_number");
$tables = [];
if ($tables_res) {
    while ($t = $tables_res->fetch_assoc()) {
        // Fetch active order for this table if any
        $t_num = $conn->real_escape_string($t['table_number']);
        $o_res = $conn->query("SELECT * FROM orders WHERE table_number = '$t_num' AND status IN ('new', 'preparing', 'ready') ORDER BY id DESC LIMIT 1");
        $t['active_order'] = ($o_res && $o_row = $o_res->fetch_assoc()) ? $o_row : null;
        $tables[] = $t;
    }
}
$conn->close();

$base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . str_replace('/admin', '', dirname($_SERVER['REQUEST_URI']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0c0907">
    <title>Manage Tables & Seating - QR Cafe</title>
    <link rel="manifest" href="../manifest.json">
    <link rel="stylesheet" href="../css/spatial.css">
</head>
<body>
    <!-- Mobile Header -->
    <header class="header">
        <div class="mobile-app-shell">
            <a href="index.php" class="logo">
                <span>📍</span> Restaurant Tables & Seating
            </a>
            <a href="index.php" style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 700;">Dashboard →</a>
        </div>
    </header>

    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">

        <!-- Quick Add Table Card -->
        <div class="spatial-card" style="padding: 16px; margin-bottom: 20px;">
            <h3 style="font-size: 1rem; font-weight: 800; font-family: var(--font-serif); margin-bottom: 10px;">+ Add New Table</h3>
            <form method="POST" style="display: flex; gap: 10px;">
                <input type="hidden" name="action" value="add">
                <input type="text" name="table_number" placeholder="Table Number (e.g. 1, 2, 3A)" required style="flex: 1; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
                <button type="submit" class="checkout-btn" style="width: auto; padding: 10px 20px; font-size: 0.88rem;">Add Table</button>
            </form>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 10px; border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 0.85rem;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Tables Mobile Card Grid -->
        <h3 style="font-size: 1rem; font-weight: 800; font-family: var(--font-serif); margin-bottom: 10px;">
            Restaurant Seating Grid <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">(Tap table to manage)</span>
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <?php foreach ($tables as $t): ?>
                <?php 
                $menu_link = rtrim($base_url, '/') . '/menu.php?table=' . urlencode($t['table_number']); 
                $has_active = !empty($t['active_order']);
                $order_id = $has_active ? $t['active_order']['id'] : 0;
                $order_status = $has_active ? strtoupper($t['active_order']['status']) : 'VACANT';
                ?>
                <div class="spatial-card" style="padding: 14px; text-align: center; cursor: pointer; border-color: <?php echo $has_active ? 'var(--primary)' : 'var(--glass-border)'; ?>;" onclick="openTableHostSheet('<?php echo htmlspecialchars($t['table_number']); ?>', <?php echo $order_id; ?>, '<?php echo $order_status; ?>', '<?php echo $menu_link; ?>', <?php echo $t['id']; ?>)">
                    <div style="font-size: 1.6rem; margin-bottom: 4px;"><?php echo $has_active ? '🍽️' : '🛋️'; ?></div>
                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary);">Table <?php echo htmlspecialchars($t['table_number']); ?></div>
                    <div style="font-size: 0.72rem; font-weight: 800; margin-top: 4px; color: <?php echo $has_active ? '#fde68a' : 'var(--text-muted)'; ?>;">
                        <?php echo $has_active ? '🔥 ACTIVE #' . $order_id : '🟢 VACANT'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- Table Host View Bottom Sheet -->
    <div class="spatial-modal" id="tableHostSheet">
        <div class="spatial-modal-overlay" onclick="closeTableHostSheet()"></div>
        <div class="spatial-modal-content">
            <button class="spatial-modal-close" onclick="closeTableHostSheet()">✕</button>
            <div style="font-size: 2.5rem; text-align: center; margin-bottom: 4px;">📍</div>
            <h3 style="font-size: 1.3rem; font-weight: 800; text-align: center; font-family: var(--font-serif);" id="sheetTableTitle">Table 1</h3>
            <p style="color: var(--text-muted); text-align: center; font-size: 0.82rem; margin-bottom: 16px;" id="sheetTableStatus">Status: Vacant</p>
            
            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                <a id="sheetOpenMenuBtn" href="#" target="_blank" class="checkout-btn" style="text-align: center; text-decoration: none; padding: 12px;">
                    📱 Open Customer Digital Menu
                </a>
                <a id="sheetViewOrderBtn" href="#" class="category-btn" style="text-align: center; text-decoration: none; padding: 12px; display: none;">
                    📋 View Active Order Details
                </a>
                <a id="sheetDeleteTableBtn" href="#" onclick="return confirm('Delete this table?')" style="color: var(--danger); font-size: 0.85rem; font-weight: 700; text-align: center; text-decoration: none; margin-top: 6px;">
                    🗑️ Delete Table
                </a>
            </div>
        </div>
    </div>

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
    <script>
        function openTableHostSheet(tableNum, orderId, orderStatus, menuUrl, tableDbId) {
            document.getElementById('sheetTableTitle').textContent = 'Table ' + tableNum;
            document.getElementById('sheetTableStatus').textContent = 'Current Status: ' + orderStatus;
            document.getElementById('sheetOpenMenuBtn').href = menuUrl;
            document.getElementById('sheetDeleteTableBtn').href = 'tables.php?delete=' + tableDbId;

            const orderBtn = document.getElementById('sheetViewOrderBtn');
            if (orderId > 0) {
                orderBtn.href = 'order-details.php?id=' + orderId;
                orderBtn.style.display = 'block';
            } else {
                orderBtn.style.display = 'none';
            }

            document.getElementById('tableHostSheet').classList.add('active');
        }

        function closeTableHostSheet() {
            document.getElementById('tableHostSheet').classList.remove('active');
        }
    </script>
</body>
</html>
