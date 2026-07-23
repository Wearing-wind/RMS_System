<?php
// Admin Tables & QR Code Links Management
require_once '../config.php';
requireAdminLogin();
require_once 'header.php';

$conn = getDBConnection();

// Handle add table
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
            header('Location: tables.php');
            exit;
        } else {
            $_SESSION['error'] = 'Table number already exists';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM tables WHERE id = $id");
    $_SESSION['success'] = 'Table deleted successfully';
    header('Location: tables.php');
    exit;
}

$tables = $conn->query("SELECT * FROM tables ORDER BY table_number");

renderAdminHeader('tables', 'Tables & QR Links Management');
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<?php if (isset($_SESSION['success'])): ?>
    <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
        ✓ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div style="background: rgba(244, 63, 94, 0.15); border: 1px solid var(--danger); color: #fda4af; padding: 12px 20px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
        ⚠️ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
    <!-- Add Table Form Card -->
    <div class="spatial-card" style="padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px; color: var(--primary);">+ Add Dining Table</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Table Number / Name</label>
                <input type="text" name="table_number" required placeholder="e.g. 1, 2, VIP-1" style="width: 100%; background: rgba(14,11,8,0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px 14px; color: white; outline: none;">
            </div>
            
            <button type="submit" class="checkout-btn">Add Table</button>
        </form>
    </div>

    <!-- Active Tables Grid Card -->
    <div class="spatial-card" style="padding: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 16px;">📍 Active Dining Tables & QR Codes</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 14px;">
            <?php if ($tables && $tables->num_rows > 0): ?>
                <?php while ($t = $tables->fetch_assoc()): ?>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 14px; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: 800; color: var(--primary); margin-bottom: 6px;">Table <?php echo htmlspecialchars($t['table_number']); ?></div>
                        
                        <div id="qrcode-<?php echo $t['id']; ?>" style="margin: 10px auto; padding: 6px; background: white; border-radius: var(--radius-sm); display: inline-block;"></div>
                        
                        <div style="display: flex; gap: 4px; justify-content: center; margin-top: 8px;">
                            <a href="../menu.php?table=<?php echo urlencode($t['table_number']); ?>" target="_blank" class="add-to-cart-btn" style="padding: 4px 8px; font-size: 0.7rem; text-decoration: none;">Open ↗</a>
                            <a href="tables.php?delete=<?php echo $t['id']; ?>" onclick="return confirm('Delete this table?')" class="add-to-cart-btn" style="padding: 4px 8px; font-size: 0.7rem; background: var(--danger); color: white; text-decoration: none;">✕</a>
                        </div>
                        
                        <script>
                            new QRCode(document.getElementById("qrcode-<?php echo $t['id']; ?>"), {
                                text: window.location.origin + "<?php echo str_replace('admin/tables.php', '', $_SERVER['SCRIPT_NAME']); ?>menu.php?table=<?php echo urlencode($t['table_number']); ?>",
                                width: 80,
                                height: 80
                            });
                        </script>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 20px;">No tables created yet</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
renderAdminFooter();
?>
