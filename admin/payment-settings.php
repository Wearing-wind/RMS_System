<?php
// Admin - Payment Settings
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = sanitize($_POST['restaurant_name'] ?? 'QR Restaurant');
    $payment_note = sanitize($_POST['payment_note'] ?? 'Scan QR to pay');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if payment_settings table exists
    $result = $conn->query("SHOW TABLES LIKE 'payment_settings'");
    if ($result->num_rows == 0) {
        $createTable = "CREATE TABLE IF NOT EXISTS payment_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_name VARCHAR(200) DEFAULT 'QR Restaurant',
            payment_note VARCHAR(500),
            qr_code_image VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($createTable);
        $conn->query("INSERT INTO payment_settings (restaurant_name, payment_note) VALUES ('QR Restaurant', 'Scan QR to pay')");
    }
    
    // Handle QR code image upload
    $qr_code_image = '';
    if (isset($_FILES['qr_code_image']) && $_FILES['qr_code_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/payment/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['qr_code_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'qr_' . time() . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['qr_code_image']['tmp_name'], $target_path)) {
            $qr_code_image = 'payment/' . $file_name;
        }
    }
    
    if (!empty($qr_code_image)) {
        $stmt = $conn->prepare("UPDATE payment_settings SET restaurant_name = ?, payment_note = ?, qr_code_image = ?, is_active = ? WHERE id = 1");
        $stmt->bind_param("sssi", $restaurant_name, $payment_note, $qr_code_image, $is_active);
    } else {
        $stmt = $conn->prepare("UPDATE payment_settings SET restaurant_name = ?, payment_note = ?, is_active = ? WHERE id = 1");
        $stmt->bind_param("ssi", $restaurant_name, $payment_note, $is_active);
    }
    
    if ($stmt->execute()) {
        $message = 'Payment settings updated successfully!';
    } else {
        $error = 'Error updating settings';
    }
    
    $stmt->close();
}

// Get current settings
$result = $conn->query("SHOW TABLES LIKE 'payment_settings'");
if ($result->num_rows > 0) {
    $settings_result = $conn->query("SELECT * FROM payment_settings WHERE id = 1");
    $settings = $settings_result->fetch_assoc();
} else {
    $settings = [
        'restaurant_name' => 'QR Restaurant',
        'payment_note' => 'Scan QR to pay',
        'qr_code_image' => '',
        'is_active' => 1
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings - QR Restaurant Admin</title>
    <link rel="stylesheet" href="../css/modern.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f6fa; }
        .admin-header { background: linear-gradient(135deg, #ff6b35, #ff8c5a); padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admin-header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-logo { color: white; font-size: 1.5rem; font-weight: bold; text-decoration: none; }
        .admin-nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .admin-nav a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 10px 18px; border-radius: 25px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); color: white; }
        .admin-content { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .admin-content h1 { color: #2d3436; margin-bottom: 25px; font-size: 2rem; }
        
        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .settings-card h2 {
            margin: 0 0 25px 0;
            color: #2d3436;
            font-size: 1.4rem;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .qr-preview {
            text-align: center;
            margin: 25px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa, #fff);
            border-radius: 15px;
            border: 2px dashed #dfe6e9;
        }
        
        .qr-preview img {
            max-width: 220px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .qr-placeholder-display {
            width: 180px;
            height: 180px;
            margin: 0 auto;
            background: white;
            border: 3px dashed #ddd;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
        }
        
        .qr-placeholder-display span {
            font-size: 0.9rem;
            color: #999;
            margin-top: 10px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2d3436; font-weight: 600; font-size: 0.95rem; }
        .form-group input[type="text"], 
        .form-group input[type="file"] { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #dfe6e9; 
            border-radius: 10px; 
            font-size: 1rem; 
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input[type="text"]:focus { outline: none; border-color: #ff6b35; }
        .form-group input[type="file"] { padding: 10px; background: #f8f9fa; }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .checkbox-group:hover { background: #f0f0f0; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; accent-color: #ff6b35; }
        .checkbox-group span { font-weight: 500; color: #2d3436; }
        
        .btn { padding: 14px 28px; border: none; border-radius: 10px; cursor: pointer; font-size: 1rem; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #ff6b35, #ff8c5a); color: white; width: 100%; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .btn-row { display: flex; gap: 15px; margin-top: 25px; }
        .btn-row .btn { flex: 1; text-align: center; }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .info-box span { font-size: 1.2rem; }
        .info-box p { margin: 0; color: #004085; font-size: 0.9rem; }
        
        .help-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <a href="index.php" class="admin-logo">🍽️ Admin Panel</a>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="menu-items.php">Menu Items</a>
                <a href="categories.php">Categories</a>
                <a href="tables.php">Tables & QR</a>
                <a href="orders.php">Orders</a>
                <a href="payment-settings.php" class="active">Payment</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Content -->
    <section class="admin-content">
        <h1>💳 Payment Settings</h1>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-success">✅ <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="settings-card">
            <h2>📱 QR Code Payment Configuration</h2>
            
            <div class="info-box">
                <span>💡</span>
                <p>Upload a QR code from payment apps like Esewa, Khalti, or IME Pay. Customers will scan this to make payments after placing their order.</p>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="restaurant_name">🏪 Restaurant Name</label>
                    <input type="text" id="restaurant_name" name="restaurant_name" value="<?php echo htmlspecialchars($settings['restaurant_name'] ?? 'QR Restaurant'); ?>" required placeholder="Enter your restaurant name">
                </div>
                
                <div class="form-group">
                    <label for="payment_note">📝 Payment Note</label>
                    <input type="text" id="payment_note" name="payment_note" value="<?php echo htmlspecialchars($settings['payment_note'] ?? 'Scan QR to pay'); ?>" placeholder="e.g., Scan to pay via Esewa/Khalti">
                </div>
                
                <div class="form-group">
                    <label>🖼️ Current QR Code</label>
                    <div class="qr-preview">
                        <?php if (!empty($settings['qr_code_image'])): ?>
                            <img src="../images/<?php echo htmlspecialchars($settings['qr_code_image']); ?>" alt="Payment QR Code">
                        <?php else: ?>
                            <div class="qr-placeholder-display">
                                📱
                                <span>No QR Code</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="qr_code_image">⬆️ Upload New QR Code</label>
                    <input type="file" id="qr_code_image" name="qr_code_image" accept="image/*">
                    <p class="help-text">Supported formats: JPG, PNG, GIF • Max size: 2MB</p>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-group">
                        <input type="checkbox" name="is_active" value="1" <?php echo ($settings['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <span>Show payment QR code on order completion page</span>
                    </label>
                </div>
                
                <div class="btn-row">
                    <button type="submit" class="btn btn-primary">💾 Save Settings</button>
                    <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                </div>
            </form>
        </div>
    </section>
</body>
</html>
