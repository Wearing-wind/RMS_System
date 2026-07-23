<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Login - QR Cafe</title>
    <link rel="stylesheet" href="../css/spatial.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding-bottom: 0;">
    <main class="mobile-app-shell" style="width: 100%; margin: auto;">
        <div class="spatial-card" style="padding: 32px 20px; text-align: center;">
            <div style="font-size: 3.2rem; margin-bottom: 8px;">☕</div>
            <h1 style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-serif); margin-bottom: 4px;">Admin Panel</h1>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">QR Cafe Management System</p>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: rgba(244,63,94,0.15); border: 1px solid var(--danger); color: #fda4af; padding: 10px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 0.85rem; text-align: left;">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login-process.php" style="text-align: left;">
                <div style="margin-bottom: 14px;">
                    <label for="username" style="display: block; font-weight: 700; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 6px;">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" required style="width: 100%; background: rgba(14, 11, 8, 0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 12px; color: white; font-family: inherit; font-size: 0.9rem; outline: none;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label for="password" style="display: block; font-weight: 700; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 6px;">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required style="width: 100%; background: rgba(14, 11, 8, 0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 12px; color: white; font-family: inherit; font-size: 0.9rem; outline: none;">
                </div>
                
                <button type="submit" class="checkout-btn">
                    🔑 Login to Admin Panel
                </button>
            </form>
            
            <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; margin: 20px 0 14px; font-size: 0.8rem; color: var(--text-muted);">
                Default Credentials: <strong>admin</strong> / <strong>admin123</strong>
            </div>
            
            <a href="../menu.php" style="color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-decoration: none;">← Back to Menu</a>
        </div>
    </main>
</body>
</html>
