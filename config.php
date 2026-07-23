<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qr_restaurant');

// Create database connection with error handling
function getDBConnection() {
    // First try to connect without database to check if it exists
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        return null;
    }
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($result->num_rows == 0) {
        return null;
    }
    
    // Select the database
    if (!$conn->select_db(DB_NAME)) {
        return null;
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
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
session_start();

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
    
    // If whole number, don't show decimals
    if ($price == floor($price)) {
        return 'Rs. ' . number_format($price, 0);
    }
    
    // Otherwise show 2 decimal places
    return 'Rs. ' . number_format($price, 2);
}

// Short format price - for small displays
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
