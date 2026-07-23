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
    if ($result && $result->num_rows == 0) {
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
    if (!$conn) return;

    // 0. Admin Users table check
    $admin_check = $conn->query("SHOW TABLES LIKE 'admin_users'");
    if (!$admin_check || $admin_check->num_rows == 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
        @$conn->query("INSERT IGNORE INTO admin_users (username, password, full_name) VALUES ('admin', '$default_pass', 'Administrator')");
    }

    // 1. Orders table check & creation
    $orders_check = $conn->query("SHOW TABLES LIKE 'orders'");
    if (!$orders_check || $orders_check->num_rows == 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_number VARCHAR(10) NOT NULL,
            customer_name VARCHAR(100),
            notes TEXT,
            status ENUM('new', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'new',
            total_amount DECIMAL(10, 2) DEFAULT 0,
            payment_status ENUM('pending', 'paid') DEFAULT 'pending',
            payment_method VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    } else {
        // Orders table columns check
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
    }

    // 2. Payment Settings table check
    $table_check = $conn->query("SHOW TABLES LIKE 'payment_settings'");
    if (!$table_check || $table_check->num_rows == 0) {
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
    if (!$waiter_check || $waiter_check->num_rows == 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS waiter_calls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_number VARCHAR(10) NOT NULL,
            status ENUM('pending', 'served') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // 4. Menu Items table check & creation
    $menu_check = $conn->query("SHOW TABLES LIKE 'menu_items'");
    if (!$menu_check || $menu_check->num_rows == 0) {
        // Need to ensure categories exists first due to foreign key
        $cat_check = $conn->query("SHOW TABLES LIKE 'categories'");
        if (!$cat_check || $cat_check->num_rows == 0) {
            @$conn->query("CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            @$conn->query("INSERT IGNORE INTO categories (name, description) VALUES ('Main Dishes', 'Delicious entrees'), ('Beverages', 'Refreshing drinks'), ('Desserts', 'Sweet treats')");
        }
        
        @$conn->query("CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            image VARCHAR(255),
            category_id INT NOT NULL,
            status ENUM('active', 'inactive', 'sold_out') DEFAULT 'active',
            is_popular TINYINT(1) DEFAULT 0,
            preparation_time INT DEFAULT 15,
            dietary_type ENUM('veg', 'non-veg') DEFAULT 'veg',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )");
    } else {
        // Menu Items table columns & status ENUM check
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
        if (!in_array('dietary_type', $menu_cols)) {
            @$conn->query("ALTER TABLE menu_items ADD COLUMN dietary_type ENUM('veg', 'non-veg') DEFAULT 'veg'");
        }

        // Modify status ENUM to include 'sold_out'
        @$conn->query("ALTER TABLE menu_items MODIFY COLUMN status ENUM('active', 'sold_out', 'inactive') DEFAULT 'active'");
    }

    // 5. Tables table check
    $tables_check = $conn->query("SHOW TABLES LIKE 'tables'");
    if (!$tables_check || $tables_check->num_rows == 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS tables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_number VARCHAR(20) NOT NULL UNIQUE,
            qr_code VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        @$conn->query("INSERT IGNORE INTO tables (table_number) VALUES ('1'), ('2'), ('3'), ('4')");
    }

    // 6. Categories table check
    $cat_check = $conn->query("SHOW TABLES LIKE 'categories'");
    if (!$cat_check || $cat_check->num_rows == 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        @$conn->query("INSERT IGNORE INTO categories (name, description) VALUES ('Main Dishes', 'Delicious entrees'), ('Beverages', 'Refreshing drinks'), ('Desserts', 'Sweet treats')");
    }

    // 7. Order Items table check
    $order_items_check = $conn->query("SHOW TABLES LIKE 'order_items'");
    if (!$order_items_check || $order_items_check->num_rows == 0) {
        @$conn->query("CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
        )");
    }
}

// Check if database is setup
function isDatabaseSetup() {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) return false;
    $res = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $exists = ($res && $res->num_rows > 0);
    $conn->close();
    return $exists;
}

// Helper for sanitizing input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Helper for session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn() {
    return (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || isset($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
?>
