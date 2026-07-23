<?php
// Admin Payment QR Settings - Mobile First
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database not connected.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = sanitize($_POST['restaurant_name']);
    $payment_note = sanitize($_POST['payment_note']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle QR image upload
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0c0907">
    <title>Payment QR Settings - QR Cafe</title>
    <link rel="manifest" href="../manifest.json">
    <link rel="stylesheet" href="../css/spatial.css">
</head>
<body>
    <!-- Mobile Header -->
    <header class="header">
        <div class="mobile-app-shell">
            <a href="index.php" class="logo">
                <span>💳</span> Payment QR Settings
            </a>
            <a href="index.php" style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 700;">Dashboard →</a>
        </div>
    </header>

    <main class="mobile-app-shell" style="margin-top: 14px; margin-bottom: 20px;">

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 10px; border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 0.85rem;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="spatial-card" style="padding: 18px;">
            <h3 style="font-size: 1.05rem; font-weight: 800; font-family: var(--font-serif); margin-bottom: 14px;">Digital Payment QR Settings</h3>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Restaurant Name</label>
                    <input type="text" name="restaurant_name" value="<?php echo htmlspecialchars($payment_settings['restaurant_name'] ?? 'QR Cafe'); ?>" required style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Payment Note / Instructions</label>
                    <textarea name="payment_note" rows="2" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;"><?php echo htmlspecialchars($payment_settings['payment_note'] ?? 'Scan QR code to pay via Esewa or Khalti'); ?></textarea>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px;">Current QR Code Image</label>
                    <?php if (!empty($payment_settings['qr_code_image'])): ?>
                        <div style="margin-bottom: 10px; text-align: center;">
                            <img src="../images/<?php echo htmlspecialchars($payment_settings['qr_code_image']); ?>" alt="Payment QR" style="max-width: 150px; border-radius: var(--radius-sm); border: 2px solid var(--primary); padding: 6px; background: white;">
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 8px;">No QR code uploaded yet.</p>
                    <?php endif; ?>
                    <input type="file" name="qr_code_image" accept="image/*" style="font-size: 0.8rem; color: var(--text-secondary);">
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="is_active" value="1" <?php echo (!isset($payment_settings) || !empty($payment_settings['is_active'])) ? 'checked' : ''; ?>>
                        <span class="checkbox-label" style="font-size: 0.88rem;">Enable Digital Payment QR Code</span>
                    </label>
                </div>

                <button type="submit" class="checkout-btn" style="padding: 12px;">Save Payment Settings</button>
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
