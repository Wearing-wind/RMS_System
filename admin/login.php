<?php
// Start session at the very beginning before any output
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - QR Restaurant</title>
    <link rel="stylesheet" href="../css/modern.css">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c5a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-page {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .login-card h1 {
            color: #ff6b35;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .login-card .subtitle {
            color: #636e72;
            margin-bottom: 30px;
        }
        
        .login-card form {
            text-align: left;
        }
        
        .login-card .form-group {
            margin-bottom: 20px;
        }
        
        .login-card .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d3436;
            font-weight: 600;
        }
        
        .login-card .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #dfe6e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .login-card .form-group input:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 4px rgba(255,107,53,0.1);
        }
        
        .login-card button[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #ff6b35, #ff8c5a);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255,107,53,0.3);
            margin-top: 10px;
        }
        
        .login-card button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,107,53,0.4);
        }
        
        .login-card .credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        
        .login-card .credentials p {
            color: #636e72;
            font-size: 0.9rem;
            margin: 5px 0;
        }
        
        .login-card .credentials strong {
            color: #2d3436;
        }
        
        .login-card .alert {
            background: #ffeaa7;
            color: #d63031;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .login-card .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #ff6b35;
            text-decoration: none;
            font-weight: 600;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff6b35, #ff8c5a);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="logo-icon">🍽️</div>
            <h1>Admin Panel</h1>
            <p class="subtitle">QR Restaurant Management</p>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login-process.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>
                
                <button type="submit">🔑 Login to Admin</button>
            </form>
            
            <div class="credentials">
                <p><strong>Default Login:</strong></p>
                <p>Username: <strong>admin</strong></p>
                <p>Password: <strong>admin123</strong></p>
            </div>
            
            <a href="../menu.php" class="back-link">← Back to Menu</a>
        </div>
    </div>
</body>
</html>
