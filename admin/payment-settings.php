<?php
// Admin Payment Settings Management
require_once '../config.php';
requireAdminLogin();
require_once 'header.php';

$conn = getDBConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = sanitize($_POST['restaurant_name'] ?? 'QR Cafe');
    $payment_note = sanitize($_POST['payment_note'] ?? 'Scan QR code to pay via Esewa / Khalti');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
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
        $error = 'Failed to update payment settings';
    }
    $stmt->close();
}

$settings = null;
$result = $conn->query("SELECT * FROM payment_settings WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
}

renderAdminHeader('payment-settings', 'Payment QR Settings');
?>

<?php if (!empty($message)): ?>
    <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
        ✓ <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background: rgba(244, 63, 94, 0.15); border: 1px solid var(--danger); color: #fda4af; padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
        ⚠️ <?php echo $error; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- Form Card -->
    <div class="spatial-card" style="padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px; color: var(--primary);">💳 Configure Payment QR</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Restaurant / Cafe Name</label>
                <input type="text" name="restaurant_name" required value="<?php echo htmlspecialchars($settings['restaurant_name'] ?? 'QR Cafe'); ?>" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;">
            </div>
            
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Payment Note / Instruction</label>
                <textarea name="payment_note" rows="2" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;"><?php echo htmlspecialchars($settings['payment_note'] ?? 'Scan QR code to pay via Esewa / Khalti'); ?></textarea>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Upload Payment QR Code Image</label>
                <input type="file" name="qr_code_image" accept="image/*" style="width: 100%; color: var(--text-muted); font-size: 0.85rem;">
            </div>
            
            <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($settings['is_active'] ?? 1) ? 'checked' : ''; ?> style="accent-color: var(--primary);">
                <label for="is_active" style="font-weight: 700; font-size: 0.88rem; color: var(--text-primary);">Enable Payment QR on Customer Checkout</label>
            </div>
            
            <button type="submit" class="checkout-btn">Save Payment Settings</button>
        </form>
    </div>

    <!-- Live Preview Card -->
    <div class="spatial-card" style="padding: 24px; text-align: center;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px;">📱 Live Payment QR Preview</h3>
        
        <?php if (!empty($settings['qr_code_image'])): ?>
            <div style="margin-bottom: 14px;">
                <img src="../images/<?php echo htmlspecialchars($settings['qr_code_image']); ?>" alt="Payment QR" style="max-width: 200px; width: 100%; border-radius: var(--radius-sm); border: 2px solid var(--primary); padding: 10px; background: white;">
            </div>
        <?php else: ?>
            <div style="font-size: 4rem; margin-bottom: 10px;">💳</div>
            <p style="color: var(--text-muted); font-size: 0.85rem;">No payment QR image uploaded yet</p>
        <?php endif; ?>
        
        <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-primary); margin-top: 10px;">
            <?php echo htmlspecialchars($settings['restaurant_name'] ?? 'QR Cafe'); ?>
        </div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 6px;">
            <?php echo htmlspecialchars($settings['payment_note'] ?? 'Scan QR code to pay'); ?>
        </p>
    </div>
</div>

<?php
renderAdminFooter();
?>
