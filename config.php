<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qr_restaurant');

// Create database connection with error handling & auto schema migration
function getDBConnection() {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        return null;
    }
    
    // Check if database exists, create if not
    $result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($result->num_rows == 0) {
        $conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    }
    
    if (!$conn->select_db(DB_NAME)) {
        return null;
    }
    
    $conn->set_charset("utf8mb4");
    
    // Ensure all required tables and columns exist seamlessly
    ensureDatabaseSchema($conn);
    
    return $conn;
}

// Auto Schema Migration Helper
function ensureDatabaseSchema($conn) {
    // 1. Orders table columns check
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM orders");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cols[] = $r['Field'];
        }
    }
    
    if (!in_array('total_amount', $cols)) {
        @$conn->query("ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0");
    }
    if (!in_array('payment_status', $cols)) {
        @$conn->query("ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid') DEFAULT 'pending'");
    }
    if (!in_array('payment_method', $cols)) {
        @$conn->query("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'pending'");
    }

    // 2. Payment Settings table check
    $table_check = $conn->query("SHOW TABLES LIKE 'payment_settings'");
    if ($table_check->num_rows == 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS payment_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_name VARCHAR(200) DEFAULT 'QR Restaurant',
            payment_note VARCHAR(500),
            qr_code_image VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        @$conn->query("INSERT INTO payment_settings (restaurant_name, payment_note) VALUES ('QR Restaurant', 'Scan QR to pay via Esewa/Khalti')");
    }

    // 3. Waiter Calls table check
    $waiter_check = $conn->query("SHOW TABLES LIKE 'waiter_calls'");
    if ($waiter_check->num_rows == 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS waiter_calls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_number VARCHAR(10) NOT NULL,
            status ENUM('pending', 'served') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // 4. Menu Items table columns check
    $menu_cols = [];
    $res_m = $conn->query("SHOW COLUMNS FROM menu_items");
    if ($res_m) {
        while ($r = $res_m->fetch_assoc()) {
            $menu_cols[] = $r['Field'];
        }
    }
    if (!in_array('preparation_time', $menu_cols)) {
        @$conn->query("ALTER TABLE menu_items ADD COLUMN preparation_time INT DEFAULT 15");
    }
    if (!in_array('is_popular', $menu_cols)) {
        @$conn->query("ALTER TABLE menu_items ADD COLUMN is_popular TINYINT(1) DEFAULT 0");
    }
}

// Check if database is setup
function isDatabaseSetup() {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    $conn->close();
    return true;
}

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format price - Nepali Rupees (Rs.) with thousand separators
function formatPrice($price) {
    $price = floatval($price);
    if ($price == floor($price)) {
        return 'Rs. ' . number_format($price, 0);
    }
    return 'Rs. ' . number_format($price, 2);
}

// Short format price
function formatPriceShort($price) {
    return 'Rs. ' . number_format(floatval($price), 0);
}

// Get current table from URL
function getCurrentTable() {
    return isset($_GET['table']) ? intval($_GET['table']) : 0;
}

// Check if user is logged in as admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Redirect to login if not admin
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: admin/login.php');
        exit;
    }
}
?>
