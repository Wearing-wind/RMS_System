<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Restaurant - Home</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .home-container {
            text-align: center;
            color: white;
        }
        .home-container h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .home-container p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        .home-links {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .home-links a {
            background: white;
            color: #667eea;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .home-links a:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="home-container">
        <h1>🍽️ QR Restaurant</h1>
        <p>Welcome to our digital ordering system</p>
        <div class="home-links">
            <a href="menu.php">Browse Menu</a>
            <a href="admin/login.php">Admin Login</a>
            <a href="kitchen-dashboard.php">Kitchen Dashboard</a>
        </div>
    </div>
</body>
</html>
